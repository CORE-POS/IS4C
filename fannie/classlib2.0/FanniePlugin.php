<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

namespace COREPOS\Fannie\API {

/**
  FanniePlugin class

  Plugins are collections of modules. Each collection should
  contain one module that subclasses 'Plugin'. This module
  provides meta-information about the plugin like settings
  and enable/disable hooks
*/
class FanniePlugin 
{

    /**
      Desired settings. These are automatically exposed
      on the 'Plugins' area of the install page and
      written to ini.php
    */
    public $plugin_settings = array(
    'example1' => array('default'=>'','label'=>'Setting #1',
            'description'=>'Text goes here'),
    'example2' => array('default'=>1,
            'options'=>array('Yes'=>1,'No'=>0)
        )
    );

    public $plugin_description = 'This author didn\'t provide anything. Shame!';


    /**
      Callback. Triggered when plugin is enabled
    */
    public function pluginEnable()
    {

    }

    /**
      Callback. Triggered when plugin is disabled
    */
    public function pluginDisable()
    {

    }

    /**
      Callback. Triggered when a setting is modified
    */
    public function settingChange()
    {

    }

    /**
      Get a URL for the plugin's directory    
    */
    public function pluginUrl()
    {
        $url = \FannieConfig::factory()->get('URL');
        $info = new \ReflectionClass($this);

        return $url . 'modules/plugins2.0/'.basename(dirname($info->getFileName()));
    }

    /**
      Get filesystem path for the plugin's directory
    */
    public function pluginDir()
    {
        $info = new \ReflectionClass($this);

        return dirname($info->getFileName());
    }

    public function pluginDbStruct($db, $struct_name, $db_name="")
    {
        if ($db->table_exists($struct_name)) {
            return true;
        }

        $dir = $this->pluginDir();
        if (!file_exists($dir.'/sql/'.$struct_name.'.php')) {
            return 'No create file for: '.$struct_name;
        }
        include($dir.'/sql/'.$struct_name.'.php');
        if (!isset($PLUGIN_CREATE) || !isset($PLUGIN_CREATE[$struct_name])) {
            return 'No definition for: '.$struct_name;
        }

        $result = $db->query($PLUGIN_CREATE[$struct_name], $db_name);
        if ($result) {
            return true;
        } else {
            return $db->error($db_name);
        }
    }
    
    /**
      Find the plugin containing a given file
      @param $file string filename
      @return plugin name or boolean False
    */
    public static function memberOf($file)
    {
        $file = realpath($file);
        $sep = '/';
        if (strstr($file,'/')) {
            $sep = '/';
        } elseif (strstr($file,'\\')) {
            $sep = '\\';
        } else {
            return false;
        }

        $dirs = explode($sep, $file);
        for($i=0;$i<count($dirs);$i++) {
            if ($dirs[$i] == "plugins2.0" && isset($dirs[$i+1])) {
                return $dirs[$i+1];
            }
        }

        return false;
    }

    /**
      Check whether a given plugin is enabled
      @param $plugin string plugin name
      @return True or False
    */
    public static function isEnabled($plugin)
    {
        $plugin_list = \FannieConfig::factory()->get('PLUGIN_LIST');
        if (!is_array($plugin_list)) {
            return false;
        }

        return (in_array($plugin, $plugin_list)) ? true : false;
    }

    /**
      Find potential class files in a given directory
      @param $path starting directory
      @return array of class name => full file name
    */
    public static function pluginMap($path="",$carry=array())
    {
        if ($path == '') {
            $path = realpath(dirname(__FILE__).'/../modules/plugins2.0');
        }
        $dir = opendir($path);
        while (($file = readdir($dir)) !== false) {
            if ($file[0] != '.' && $file != 'noauto' && is_dir($path."/".$file)) {
                $carry = self::pluginMap(realpath($path.'/'.$file),$carry);
            } elseif (substr($file,-4)==".php") {
                $carry[substr($file,0,strlen($file)-4)] = realpath($path.'/'.$file);
            }
        }
        closedir($dir);

        return $carry;
    }
}

}

namespace {
    class FanniePlugin extends \COREPOS\Fannie\API\FanniePlugin {}
}

