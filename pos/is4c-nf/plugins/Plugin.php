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

/**
  Plugin class

  Plugins are collections of modules. Each collection should
  contain one module that subclasses 'Plugin'. This module
  provides meta-information about the plugin like settings
  and enable/disable hooks
*/
class Plugin 
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
	public function plugin_enable(){

	}

	/**
	  Callback. Triggered when plugin is disabled
	*/
	public function plugin_disable(){

	}

    /**
      Callback. Triggered when plugin settings are updated.
    */
    public function settingChange()
    {

    }

	/**
	  Callback. Triggered after every transaction.
	  Use for reseting any session/state info.
	*/
	public function plugin_transaction_reset(){

	}

	public function plugin_draw_icon(){
		return '';
	}

	/**
	  Get a URL for the plugin's directory	
	*/
	public function plugin_url(){
		$info = new ReflectionClass($this);
		return MiscLib::base_url().'plugins/'.basename(dirname($info->getFileName()));
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
			if ($dirs[$i] == "plugins" && isset($dirs[$i+1])) {
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
	static public function isEnabled($plugin)
    {
		global $CORE_LOCAL;
		$list = $CORE_LOCAL->get("PluginList");
		if (!is_array($list)) {
            return false;
        }

		return (in_array($plugin, $list)) ? true : false;
	}

	/**
	  Find potential class files in a given directory
	  @param $path starting directory
	  @return array of class name => full file name
	*/
	static public function pluginMap($path="",$in=array())
    {
		if($path=="") $path = dirname(__FILE__);
		$dh = opendir($path);
		while( ($file = readdir($dh)) !== False) {
			if ($file[0] == ".") continue;

			if (is_dir($path."/".$file)) {
				$in = self::pluginMap(realpath($path.'/'.$file),$in);
            }
			if (substr($file,-4)==".php" && $file != "Plugin.php") {
				$in[substr($file,0,strlen($file)-4)] = realpath($path.'/'.$file);
            }
		}
		closedir($dh);

		return $in;
	}
}

