<?php

namespace COREPOS\common;
use \Exception;

/**
  @class DeComposer

  Extracts information about the current status of composer package
  installation
*/
class DeComposer
{
    private $localPackages = array();
    private $lockPackages = array();

    public function __construct($path)
    {
        if (!file_exists($path . DIRECTORY_SEPARATOR . 'composer.json')) {
            throw new Exception("$path does not contain a composer.json file");
        }
        $this->localPackages = $this->readLocalPackages($path);
        $this->lockPackages = $this->readLockPackages($path);
    }

    public function extraPackages()
    {
        $local = $this->localPackages;
        $lock = $this->lockPackages;
        $extra = array_filter($local, function ($i) use ($lock) { return !isset($lock[$i]); });

        return array_values($extra);
    }

    public function missingPackages()
    {
        $local = $this->localPackages;
        $lock = $this->lockPackages;
        $missing = array_filter($lock, function ($i) use ($local) { return !isset($local[$i]); });

        return array_values($missing);
    }

    /**
      Traverse the "vendor" directory to determine which packages
      are currently installed.
      @param $path [string] path to the directory that *contains*
        vendor, composer.json, and composer.lock
      @return [array] package-name => package-name

      Keying the array here is for convenience in checking whether a
      package is in the set.
    */
    private function readLocalPackages($path)
    {
        $vendor = $path . DIRECTORY_SEPARATOR . 'vendor';
        if (!file_exists($vendor) || !is_dir($vendor) || !is_readable($vendor)) {
            return array();
        }
        $vendorDH = opendir($vendor);
        $ret = array();
        while (($file=readdir($vendorDH)) !== false) {
            if ($file[0] == '.') continue;
            $orgDir = $vendor . DIRECTORY_SEPARATOR . $file;
            if (!is_dir($orgDir) || !is_readable($orgDir)) continue; 
            $org = $file;
            $orgDH = opendir($orgDir);
            while (($orgFile=readdir($orgDH)) !== false) {
                if ($orgFile[0] == '.') continue;
                $packDir = $orgDir . DIRECTORY_SEPARATOR . $orgFile;
                if (is_dir($packDir)) {
                    $packageName = $org . '/' . $orgFile;
                    $ret[$packageName] = $packageName;
                }
            }
            closedir($orgDH);
        }
        closedir($vendorDH);

        return $ret;
    }

    /**
      Read the "composer.lock" file to determine which packages
      are supposed to be installed. If for whatever reason that file
      is not present this will fall back to using the composer.json
      file.
      @param $path [string] path to the directory that *contains*
        vendor, composer.json, and composer.lock
      @return [array] package-name => package-name

      Keying the array here is for convenience in checking whether a
      package is in the set.
    */
    private function readLockPackages($path)
    {
        $lock = $path . DIRECTORY_SEPARATOR . 'composer.lock';
        if (!file_exists($lock) || !is_readable($lock)) {
            return $this->readComposerJSON($path);
        }
        $lockFile = file_get_contents($lock);
        $arr = json_decode($lockFile, true);
        if (!is_array($arr)) {
            throw new Exception('JSON error in lock file: ' . json_last_error_msg());
        }
        $packages = array_merge($arr['packages'], $arr['packages-dev']);
        $packages = array_map(function ($i) { return $i['name']; }, $packages);
        $ret = array();
        foreach ($packages as $p) {
            $ret[$p] = $p;
        }

        return $ret;
    }

    /**
      Read the "composer.json" file to determine which packages
      are supposed to be installed. This may produce some false
      positive "extra" packages since the .json file does not
      contain dependencies like the .lock file.
      @param $path [string] path to the directory that *contains*
        vendor, composer.json, and composer.lock
      @return [array] package-name => package-name

      Keying the array here is for convenience in checking whether a
      package is in the set.
    */
    private function readComposerJSON($path)
    {
        $lock = $path . DIRECTORY_SEPARATOR . 'composer.json';
        if (!file_exists($lock) || !is_readable($lock)) {
            return array();
        }
        $lockFile = file_get_contents($lock);
        $arr = json_decode($lockFile, true);
        if (!is_array($arr)) {
            throw new Exception('JSON error in lock file: ' . json_last_error_msg());
        }
        $packages = array_merge($arr['require'], $arr['require-dev']);
        $ret = array();
        foreach ($packages as $k => $v) {
            $ret[$k] = $k;
        }

        return $k;
    }
}

