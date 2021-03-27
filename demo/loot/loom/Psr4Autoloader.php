<?php

  class Psr4Autoloader
  {
    protected $prefixes = array();
    
    public function register()
    {
      spl_autoload_register(array($this, 'loadClass'));
    }

    public function addNamespace($prefix, $baseDir, $prepend = false)
    {
      $prefix = trim($prefix, '\\').'\\';
      $baseDir = rtrim(rtrim($baseDir, '/'), DIRECTORY_SEPARATOR).'/';
      if(isset($this->prefixes[$prefix]) === false) {
        $this->prefixes[$prefix] = array();
      }

      if ($prepend)
        array_unshift($this->prefixes[$prefix], $baseDir);
      else
        array_push($this->prefixes[$prefix], $baseDir);
    }

    public function loadClass($class)
    {
      $prefix = $class;
      while (false !== $pos = strrpos($prefix, '\\')) {
        $prefix = substr($class, 0, $pos + 1);
       
        $relativeClass = substr($class, $pos + 1);

        $mappedFile = $this->loadMappedFile($prefix, $relativeClass);

        if ($mappedFile)
          return $mappedFile;
        
        $prefix = rtrim($prefix, '\\');
      }
      return false;
    }


    protected function loadMappedFile($prefix, $relativeClass)
    {
      if (isset($this->prefixes[$prefix]) === false)
        return false;
      foreach ($this->prefixes[$prefix] as $baseDir) {
        $file = $baseDir.str_replace('\\', '/', $relativeClass).'.php';
        if ($this->requireFile($file)) {
          return $file;
        }
        return false;
      }
    }

    protected function requireFile($file)
    {
      if (is_file($file)) {
        require $file;
        return true;
      }
      return false;
    }
  }