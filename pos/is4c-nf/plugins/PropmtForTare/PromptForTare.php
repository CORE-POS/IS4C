<?php
/*******************************************************************************

    CCopyright 2013 Franklin Community Co-op

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
class PromptForTare extends Plugin 
{

	/**
	  Desired settings. These are automatically exposed
	  on the 'Plugins' area of the install page and
	  written to ini.php
	*/
	public $plugin_settings = array(
	'DefaultTare' => array('default'=>0.01,'label'=>'Default Tare',
			'description'=>'For locations where there may be legal requirements to enter a default tare.'),
	);

	public $plugin_description = 'Prompts the user for tare weight on items. Only ask if there is no default
	                                and if the user has not used the tare key to enter a value.';

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
}