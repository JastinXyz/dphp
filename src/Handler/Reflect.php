<?php

namespace Bot\Handler;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Reflect extends RecursiveDirectoryIterator
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
        parent::__construct($this->path);
    }

    public function getFiles()
    {
        $files = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    public function getClasses()
    {
        $classes = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $className = $this->getClassName($file->getPathname());
                if ($className) {
                    // replace the "src" with namespace
                    $path = str_replace("src", "Bot", $file->getPath());
                    $path = str_replace("/", "\\", $path);
                    $classes[$className] = $path."\\".$className;
                }
            }
        }
        return $classes;
    }

    private function getClassName($file)
    {
        $fileName = pathinfo($file, PATHINFO_FILENAME);
        if (strpos($fileName, '.') === false) {
            return $fileName;
        } else {
            return null;
        }
    }
}
