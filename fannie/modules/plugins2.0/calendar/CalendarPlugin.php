<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

if (!class_exists('FanniePlugin'))
	include($FANNIE_ROOT.'classlib2.0/FanniePlugin.php');

/**
*/
class CalendarPlugin extends FanniePlugin {

	/**
	  Desired settings. These are automatically exposed
	  on the 'Plugins' area of the install page and
	  written to ini.php
	*/
	public $plugin_settings = array(
	'CalendarDatabase' => array('default'=>'core_calendar','label'=>'Database',
			'description'=>'Database to calendars. Can
					be one of the default CORE databases or a 
					separate one.')
	);

	public $plugin_description = 'Plugin for calendars';


	public function setting_change(){
		global $FANNIE_ROOT, $FANNIE_PLUGIN_SETTINGS;

		$db_name = $FANNIE_PLUGIN_SETTINGS['CalendarDatabase'];
		if (empty($db_name)) return;

		if (!class_exists('FannieDB'))
			include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');

		$dbc = FannieDB::get($db_name);

		$errors = array();
		$errors[] = $this->plugin_db_struct($dbc, 'account_classes', $db_name);
		$errors[] = $this->plugin_db_struct($dbc, 'calendars', $db_name);
		$errors[] = $this->plugin_db_struct($dbc, 'monthview_events', $db_name);
		$errors[] = $this->plugin_db_struct($dbc, 'permissions', $db_name);

		if ($dbc->table_exists('account_classes')){
			/* populate account classes */
			$classes = array(
				1 => 'VIEWER',
				2 => 'CONTRIBUTOR',
				3 => 'ADMIN',
				4 => 'OWNER'
			);
			$chkP = $dbc->prepare_statement("SELECT classID FROM account_classes WHERE classID=?");
			$insP = $dbc->prepare_statement("INSERT INTO account_classes (classID, classDesc) VALUES (?, ?)");
			$upP = $dbc->prepare_statement("UPDATE account_classes SET classDesc=? WHERE classID=?");
			foreach($classes as $id => $name){
				$chkR = $dbc->exec_statement($chkP,array($id));
				if ($dbc->num_rows($chkR) == 0){
					$dbc->exec_statement($insP,array($id,$name));
				}
				else {
					$dbc->exec_statement($upP,array($name,$id));
				}
			}
		}

		foreach($errors as $e){
			if ($e === True) continue;
			echo 'CalendarPlugin error: '.$e.'<br />';
		}
	}

	public function plugin_enable(){
	}
}

?>
