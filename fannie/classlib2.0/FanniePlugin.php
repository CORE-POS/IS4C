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

namespace COREPOS\Fannie\API;

/**
  FanniePlugin class

  Plugins are collections of modules. Each collection should
  contain one module that subclasses 'Plugin'. This module
  provides meta-information about the plugin like settings
  and enable/disable hooks
*/
class FanniePlugin extends \COREPOS\common\CorePlugin
{
    /*
     * The plugin version controls where
     * settings are stored. Version 1 keeps settings
     * in config.php. Version 2 will store them
     * in the database.
     */
    public $version = 1;

    /**
     * The settings namespace is added to each settings as a
     * prefix to avoid collisions.
     */
    public $settingsNamespace = '';

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

        return $result ? true : $db->error($db_name);
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

    public static function getPluginList()
    {
        $plugin_list = \FannieConfig::factory()->get('PLUGIN_LIST');
        if (is_array($plugin_list)) {
            $plugin_list = array_map(function ($i) {
                if (strstr($i, "\\")) {
                    $namespaceParts = explode("\\", $i);
                    return $namespaceParts[count($namespaceParts) - 1];
                }

                return $i;
            }, $plugin_list);
            return $plugin_list;
        }

        return array();
    }

    public static function mySettings($file)
    {
        $pluginClass = basename(dirname($file));
        if (class_exists($pluginClass)) {
            $obj = new $pluginClass();
            return $obj->getSettings();
        }
        $pluginClass = "\\COREPOS\\Fannie\\Plugin\\" . $pluginClass;
        if (class_exists($pluginClass)) {
            $obj = new $pluginClass();
            return $obj->getSettings();
        }

        return array();
    }

    protected static function defaultSearchDir()
    {
        return realpath(__DIR__ . '/../modules/plugins2.0');
    }

    /**
     * Fetches the plugin's settings based on version.
     * Do note that this method will return settings
     * without their namespace prefix, if any
     * @return [array] settings
     */
    public function getSettings()
    {
        switch ($this->version) {
            case 2:
                return $this->getFromDB();
            case 1:
                return $this->getFromConfig();
            default:
                return $this->getFromConfig();
        }
    }

    private function getFromConfig()
    {
        $config = \FannieConfig::factory();
        $allSettings = $config->get('PLUGIN_SETTINGS');
        $ret = array();
        foreach ($this->plugin_settings as $name => $definition) {
            $key = $name;
            if (strlen($this->settingsNamespace) > 0) {
                $key = $this->settingsNamespace . '.' . $key;
            }
            $ret[$name] = isset($allSettings[$key]) ? $allSettings[$key] : '';
        }

        return $ret;
    }

    private function getFromDB()
    {
        $ret = array();
        $keys = array_keys($this->plugin_settings);
        if (strlen($this->settingsNamespace) > 0) {
            for ($i=0; $i<count($keys); $i++) {
                $keys[$i] = $this->settingsNamespace . '.' . $keys[$i];
            }
        }
        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        list($inStr, $args) = $dbc->safeInClause($keys);
        $prep = $dbc->prepare("SELECT * FROM PluginSettings WHERE name IN ({$inStr})");
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            if (strlen($this->settingsNamespace) > 0) {
                $key = str_replace($this->settingsNamespace . '.', '', $row['name']);
            }
            $ret[$key] = $row['setting'];
        }

        return $ret;
    }

}

