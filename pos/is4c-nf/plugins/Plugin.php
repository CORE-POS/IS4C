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

namespace COREPOS\pos\plugins;
use COREPOS\pos\lib\MiscLib;
use \CoreLocal;
use \ReflectionClass;

/**
  Plugin class

  Plugins are collections of modules. Each collection should
  contain one module that subclasses 'Plugin'. This module
  provides meta-information about the plugin like settings
  and enable/disable hooks
*/
class Plugin extends \COREPOS\common\CorePlugin
{

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
    public function pluginUrl()
    {
        $info = new ReflectionClass($this);
        return MiscLib::base_url().'plugins/'.basename(dirname($info->getFileName()));
    }

    protected static function getPluginList()
    {
        $list = CoreLocal::get("PluginList");
        if (is_array($list)) {
            return $list;
        } else {
            return array();
        }
    }

    protected static $unmapped_files = array('Plugin.php');

    protected static function defaultSearchDir()
    {
        return realpath(dirname(__FILE__));
    }
}

