<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2013 César Rodas                                                  |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/
namespace WatchFiles;

use crodas\Path;
use crodas\File;

class Watch
{
    protected $files = array();
    protected $dirs  = array();
    protected $globs = array();
    protected $fnc;
    protected $ns;
    protected $file;

    protected static $namespaces = array();

    public function __construct($file)
    {
        if (!is_file($file)) {
            if (!touch($file)) {
                throw new \Exception("Cannot create file {$file}");
            }
        }
        $this->file = realpath($file);

        if (!empty(self::$namespaces[$this->file])) {
            $this->ns = self::$namespaces[$this->file];
        } else {
            $this->ns = 'WatchFiles\\Generated\\Label_' . sha1($this->file);
        }
        $this->fnc  = $this->ns . '\\has_changed';

    }

    /**
     *  Based on the glob pattern return a list 
     *  of paths to watch in order to detect changes.
     *  
     *  For instance if we have `foo/some*dir/xxx` we must
     *  watch `foo/` for changes.
     *  
     */
    protected function getCommonParentDir($pattern, $dirs)
    {
        $comodin = array();
        $parts   = array_filter(explode(DIRECTORY_SEPARATOR, $pattern));
        foreach($parts as $i => $part) {
            if (strpos($part, '*') !== false) {
                $comodin[] = max($i-1, 0);
            }
        }

        $tmpDirs = array(); 
        foreach ($dirs as $dir) {
            $parts = array_filter(explode(DIRECTORY_SEPARATOR, $dir));
            foreach ($comodin as $i) {
                if (DIRECTORY_SEPARATOR == '\\') {
                    $tmpDirs[] = implode(DIRECTORY_SEPARATOR, array_slice($parts, 0, $i));
                } else {
                    $tmpDirs[] = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($parts, 0, $i));
                }
            }
        }

        return array_merge($dirs, array_unique($tmpDirs));
    }

    public function hasChanged()
    {
        if ($this->isWatching()) {
            $function = $this->fnc;
            $data = $this->ns . '\\get_watched_files';
            $same = true;
            foreach ($data() as $type => $value) {
                $same &= count(array_diff($this->$type, $value)) == 0;
            }
            if ($same) {
                return $function();
            }
        }

        // no files are generated
        // so we asume the files/dirs
        // had changed
        return true;
    }

    public function watch()
    {
        $files  = array();
        $dirs   = array();
        $globs  = $this->globs; 
        $zdirs  = $this->dirs;
        $zfiles = $this->files;
        $watching = $this->isWatching();

        foreach ($globs as $glob) {
            $xdirs = array();
            foreach (glob($glob) as $file) {
                if (is_dir($file)) {
                    $xdirs[] = $file;
                } else {
                    $xdirs[]  = dirname($file);
                    $zfiles[] = $file;
                }
            }
            if (!empty($xdirs)) {
                $zdirs = array_merge($zdirs, $this->getCommonParentDir($glob, $xdirs));
            }
        }

        foreach (array('files', 'dirs') as $type) {
            $var = "z$type";
            foreach (array_unique($$var) as $file) {
                $rfile = Path::getRelative($file, $this->file);
                ${$type}[$rfile] = filemtime($file);
            }
        }

        $input = array(
            'globs' => $this->globs,
            'dirs'  => $this->dirs,
            'files' => $this->files,
        );
        
        $ns   = 'WatchFiles\\Generated\\Label_' . sha1($this->file);
        $tpl  = Templates::get('template');
        $code = $tpl->render(compact('dirs', 'files', 'ns', 'globs', 'input'), true);

        File::write($this->file, $code);

        if ($watching) {
            $this->ns  = $ns = 'WatchFiles\\Runtime\\r' . uniqid(true);
            $this->fnc = $this->ns . '\\has_changed';
            static::$namespaces[ $this->file ] = $ns;
            $prefix = dirname($this->file);
            $code   = $tpl->render(compact('dirs', 'files', 'ns', 'globs', 'input', 'prefix'), true);

            eval(substr($code,5));
        } else {
            require $this->file;
        }

        return $this;
    }

    public function watchGlob($glob)
    {
        if (is_array($glob)) {
            foreach ($glob as $g) {
                $this->watchGlob($g);
            }
            return $this;
        }
        $this->globs[] = $glob;
        return $this;
    }

    public function watchGlobs(Array $globs)
    {
        $this->globs = array_merge($this->blogs, $globs);
        return $this;
    }

    public function rebuild()
    {
        if (!$this->isWatching()) {
            throw new \Exception("Cannot rebuild if you're not watching");
        }

        $function = $this->ns . '\\get_list';
        $watched  = $function();
        $watcher  = new self($this->file);
        foreach ($watched as $type => $list) {
            $method = 'watch' . $type;
            $watcher->$method( $list );
        }

        return $watcher->watch();
    }

    function getFiles()
    {
        if (!$this->isWatching()) {
            throw new \Exception("Cannot rebuild if you're not watching");
        }
        $function = $this->ns . '\\get_list';
        $data     = $function();

        return $data['files'];
    }

    public function isWatching()
    {
        $function = $this->fnc;
        if (is_callable($function)) {
            return true;
        }

        if (is_file($this->file)) {
            require $this->file;
            if (is_callable($function)) {
                return true;
            }
            unlink($this->file);
        }

        return false;
    }

    public function watchFile($file)
    {
        $this->files[] = $file;
        return $this;
    }

    public function watchDir($dir)
    {
        $this->dirs[] = $dir;
        return $this;
    }

    public function watchFiles(Array $files)
    {
        $this->files = array_merge($this->files, $files);
        return $this;
    }

    public function watchDirs(Array $dirs)
    {
        $this->dirs = array_merge($this->dirs, $dirs);
        return $this;
    }
}
