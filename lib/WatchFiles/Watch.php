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

use crodas\FileUtil\Path;
use crodas\FileUtil\File;
use RuntimeException;

class Watch
{
    protected $files = array();
    protected $dirs  = array();
    protected $globs = array();
    protected $file;
    protected $obj;
    protected static $loaded = array();

    protected static $namespaces = array();

    public function __construct($file)
    {
        if (!is_file($file)) {
            if (!touch($file)) {
                throw new RuntimeException("Cannot create file {$file}");
            }
        }

        $this->file = $file;
        $this->obj  = self::Load($file);
    }
    
    protected static function Load($file)
    {
        if (empty(self::$loaded[$file])) {
            self::$loaded[$file] = require $file;
        }

        return self::$loaded[$file];
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
            $same = true;
            foreach ($this->obj->get_watched_files() as $type => $value) {
                $same &= count(array_diff($this->$type, $value)) == 0;
            }
            if ($same) {
                return $this->obj->has_changed();
            }
        }

        // no files are generated
        // so we asume the files/dirs
        // had changed
        return true;
    }

    protected function realPath(Array $files)
    {
        foreach ($files as $id => $file) {
            if (file_exists($file)) {
                $files[$id] = realpath($file);
            }
        }

        return $files;
    }

    public function watch()
    {
        $files  = array();
        $dirs   = array();
        $globs  = $this->globs; 
        $zdirs  = $this->dirs;
        $zfiles = $this->files;

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

        $now = time();
        foreach (array('files', 'dirs') as $type) {
            $var = "z$type";
            foreach (array_unique($$var) as $file) {
                ${$type}[$file] = is_readable($file) ? filemtime($file) : $now;
                ${$type}[$file] = ${$type}[$file] ? ${$type}[$file] : $now;
            }
        }

        $input = array(
            'globs' => $this->globs,
            'dirs'  => $this->realPath($this->dirs),
            'files' => $this->realPath($this->files),
        );
        
        $tpl  = Templates::get('template');
        $code = $tpl->render(compact('dirs', 'files', 'globs', 'input'), true);

        

        return self::$loaded[$this->file] = $this->obj = File::writeAndInclude($this->file, $code);
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
        $this->globs = array_merge($this->globs, $globs);
        return $this;
    }

    public function rebuild()
    {
        if (!$this->isWatching()) {
            throw new RuntimeException("Cannot rebuild if you're not watching");
        }
        
        unlink($this->file);
        unset(self::$loaded[$this->file]);

        $watched  = $this->obj->get_list();
        $watcher  = new self($this->file);
        foreach ($watched as $type => $list) {
            $method = 'watch' . $type;
            $watcher->$method( $list );
        }

        $watcher->watch();
        $this->obj = $watcher->obj;

        return $this;
    }

    public function get($type = 'files')
    {
        if (!$this->isWatching()) {
            throw new RuntimeException("Cannot rebuild if you're not watching");
        }

        $data = $this->obj->get_list();

        return $data[$type];
    }

    public function getDirs()
    {
        return $this->get('dirs');
    }

    function getFiles()
    {
        return $this->get('files');
    }

    public function isWatching()
    {
        return is_object($this->obj);
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
