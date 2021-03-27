<?php
  
  class ClassmapAutoloader
  {
    private $classmap;

    /**
     * Filters a classmap array
     * 
     * @param array $classmap a classmap array to filter
     * 
     * @return false on failure
     * @return array filtered classmap
     **/
    private static function filterClassmap(array $classmap)
    {
      $filteredClassmap = array();
      foreach ($classmap as $classmapElement) {
        if (is_string($classmapElement) && self::isUsefulNode($classmapElement)) {
          array_push($filteredClassmap, $classmapElement);
        }
      }
      return $filteredClassmap;
    }

    /**
     * Parses a classmap array to make it workful :D
     * 
     * @param array $pureClassmap description.
     * 
     * @return false on failure
     * @return array on success
     **/
    private static function parseClassmap(array $pureClassmap)
    {
      $filteredClassmap = self::filterClassmap($pureClassmap);
      return empty($filteredClassmap) ? false : $filteredClassmap;
    }

    private static function isUsefulDirectory($dirPath) {
			if(is_dir($dirPath) && is_readable($dirPath) && is_writable($dirPath)) {
				return true;
			} else return false;
		}
		
		private static function isUsefulFile($filePath) {
			if(is_file($filePath) && is_readable($filePath) && is_writable($filePath)) {
				return true;
			} else return false;
    }
    
    private static function isUsefulNode($filePath) {
			if(self::isUsefulDirectory($filePath) || self::isUsefulFile($filePath)) {
				return true;
			} else return false;
    }

    /**
     * @param string $directoryPath directory to look up in.
     * @return array possible files on success
     **/
    private function addFilesFromDirectory($directoryPath)
    {
      if(self::isUsefulDirectory($directoryPath)) {
        $srcDir = dir($directoryPath);
        while(gettype($entry = $srcDir->read()) !== "boolean") {
          if($entry == '.' || $entry == '..') {
            continue;
          }

          $node = $directoryPath.'/'.$entry;
          if (is_dir($entry)) {
            $this->addFilesFromDirectory($node);
          } else {
            $this->addFileToClassmap($node);
          }
        }
        $srcDir->close();
        return true;
      } else return false; # not a useful filesystem node
    }

    /**
     * Adds a PHP file to classmap
     * 
     * @param string $entryPath the file path to work on
     * 
     * @return bool true on success, false on failure
     */
    private function addFileToClassmap($entryPath)
    {
      if (preg_match("@^(.*).php$@", $entryPath, $results)) {
        $temp = explode(DIRECTORY_SEPARATOR, $results[1]);
        $className = $temp[count($temp) - 1];
        $this->classmap[$className] = $entryPath;
        return true;
      } else return false;
    }

    private static function resolveClassName($longClassName)
    {
      $temp = explode("\\", $longClassName);
      return $temp[count($temp) - 1];
    }

    /**
     * Adds an entry to class map
     * 
     * @param $entryPath path to push to the global class map.
     * 
     * @return 
     **/
    private function addClassmapEntry($entryPath)
    {
      if (self::isUsefulFile($entryPath)) {
        $this->addFileToClassmap($entryPath);
        return true;
      } elseif (self::isUsefulDirectory($entryPath)) {
        $this->addFilesFromDirectory($entryPath);
        return true;
      } else return false;
    }

    /**
     * loads a class from classmap
     *
     * @param string $class class name to autoload
     * 
     * @return void
     */
    public function autoloadFromClassmap($class)
    {
      $pureClassName = self::resolveClassName($class);

      if (array_key_exists($pureClassName, $this->classmap)) {
        $filePath = $this->classmap[$pureClassName];
        require $filePath;
        unset($pureClassName);
        
        if (class_exists($class)) {
          return $filePath;
        } else return false; # class doesnt exist bro ?!
      } else return false; # not registered, sorry :(
    }

    /**
     * Registers a classmap.
     * @param array $classmap a classmap to register.
     **/
    public function register(array $primitiveClassmap)
    {
      $possibleClassmap = self::parseClassmap($primitiveClassmap);
      if (is_array($possibleClassmap)) {
        foreach ($possibleClassmap as $possibleEntry) {
          $this->addClassmapEntry($possibleEntry);
        }
        return spl_autoload_register(array($this, 'autoloadFromClassmap'));
      } else return false; # given classmap is ugly
    }
  }