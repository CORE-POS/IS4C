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
class FanniePlugin extends \COREPOS\common\CorePlugin
{
    /**
      Get a URL for the plugin's directory    
    */
    public function pluginUrl()
    {
        $url = \FannieConfig::factory()->get('URL');
        $info = new \ReflectionClass($this);

        return $url . 'modules/plugins2.0/' . basename(dirname($info->getFileName()));
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
    public static function memberOf($file, $exclude='plugins')
    {
        return parent::memberOf($file, 'plugins2.0');
    }

    protected static function getPluginList()
    {
        $plugin_list = \FannieConfig::factory()->get('PLUGIN_LIST');
        if (is_array($plugin_list)) {
            return $plugin_list;
        } else {
            return array();
        }
    }

    protected static function defaultSearchDir()
    {
        return realpath(dirname(__FILE__).'/../modules/plugins2.0');
    }
}

}

namespace {
    class FanniePlugin extends \COREPOS\Fannie\API\FanniePlugin {}
}

