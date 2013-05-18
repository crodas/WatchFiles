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

use Artifex;

class Watch
{
    protected $files = array();
    protected $dirs  = array();
    protected $globs = array();
    protected $fnc;
    protected $ns;
    protected $file;

    // getRelativePath {{{
    public static function getRelativePath($dir1, $dir2=NULL)
    {
        if (empty($dir2)) {
            $dir2 = getcwd();
        }

        $slash = DIRECTORY_SEPARATOR;

        $file = basename($dir1);
        $dir1 = trim(realpath(dirname($dir1)), $slash);
        $dir2 = trim(realpath(dirname($dir2)), $slash);
        $to   = explode($slash, $dir1);
        $from = explode($slash, $dir2);

        $realPath = $to;

        foreach ($from as $depth => $dir) {
            if(isset($to[$depth]) && $dir === $to[$depth]) {
                array_shift($realPath);
            } else {
                $remaining = count($from) - $depth;
                if($remaining) {
                    // add traversals up to first matching dir
                    $padLength = (count($realPath) + $remaining) * -1;
                    $realPath  = array_pad($realPath, $padLength, '..');
                    break;
                }
            }
        }

        $rpath = implode($slash, $realPath);
        if ($rpath && $rpath[0] != $slash) {
            $rpath = $slash . $rpath;
        }
        
        if ($file) {
            $rpath .= $slash . $file;
        }

        return $rpath;
    }
    // }}}

    public function __construct($file)
    {
        if (!is_file($file)) {
            if (!touch($file)) {
                throw new \Exception("Cannot create file {$file}");
            }
        }
        $this->file = realpath($file);
        $this->ns   = 'WatchFiles\\Generated\\Label_' . sha1($this->file);
        $this->fnc  = $this->ns . '\\has_changed';

    }

    public function hasChanged()
    {
        if ($this->isWatching()) {
            $function = $this->fnc;
            $data = $this->ns . '\\get_watched_files';
            $same = true;
            foreach ($data() as $type => $value) {
                $same &= count(array_diff($value, $this->$type)) == 0;
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
        $files = array();
        $dirs  = array();
        $ns    = $this->ns;
        $globs = $this->globs; 

        foreach ($globs as $glob) {
            foreach (glob($glob) as $file) {
                if (is_dir($file)) {
                    $this->dirs[] = $file;
                } else {
                    $this->dirs[]  = dirname($file);
                    $this->files[] = $file;
                }
            }
        }


        foreach (array('files', 'dirs') as $type) {
            foreach (array_unique($this->$type) as $file) {
                $rfile = self::getRelativePath($file, $this->file);
                ${$type}[$rfile] = filemtime($file);
            }
        }

        $input = array(
            'globs' => $this->globs,
            'dirs'  => $this->dirs,
            'files' => $this->files,
        );
        
        $code = Artifex::load(__DIR__ . '/Template.tpl.php')
            ->setContext(compact('dirs', 'files', 'ns', 'globs', 'input'))
            ->run();

        Artifex::save($this->file, $code);
        return $this;
    }

    public function watchGlob($glob)
    {
        $this->globs[] = $glob;
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
        }

        return false;
    }

    public function watchFile($file)
    {
        return $this->watchFiles(array($file));
    }

    public function watchDir($dir)
    {
        return $this->watchDirs(array($dir));
    }

    public function watchFiles(Array $files)
    {
        foreach ($files as $file) {
            $this->files[] = realpath($file);
        }
        return $this;
    }

    public function watchDirs(Array $dirs)
    {
        foreach ($dirs as $dir) {
            $this->dirs[] = realpath($dir);
        }
        return $this;
    }
}
