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

include(dirname(__FILE__).'/../../../config.php');
if(!class_exists("CalendarPluginDB")) include(dirname(__FILE__).'/CalendarPluginDB.php');
if(!class_exists("FannieWebService")) include($FANNIE_ROOT.'classlib2.0/FannieWebService.php');
if(!class_exists("FormLib")) include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class CalendarAjax extends FannieWebService {

	public $type = 'backtick';

	function renderBacktick($arr){
		$ret = '';
		foreach($arr as $a) $ret .= $a.'`';
		return $ret;
	}	

	function run(){
		$data = array();
		$action = FormLib::get_form_value('action');
		if ($action !== ''){
			$data[] = $action;
			switch($action){
			case 'monthview_save':
				$date = FormLib::get_form_value('date');
				$id = FormLib::get_form_value('id',0);
				$text = FormLib::get_form_value('text');
				$uid = FormLib::get_form_value('uid',0);

				$db = CalendarPluginDB::get();
				$chkP = $db->prepare_statement("SELECT calendarID FROM monthview_events 
						WHERE eventDate=? and uid=? and calendarID=?");
				$rowCheck = $db->exec_statement($chkP,array($date,$uid,$id));
				if ($db->num_rows($rowCheck) <= 0 && $text != ""){
					$insP = $db->prepare_statement("INSERT INTO monthview_events VALUES (?,?,?,?)");
					$db->exec_statement($insP,array($id,$date,$text,$uid));
				}
				else if ($text == ""){
					$delP = $db->prepare_statement("DELETE FROM monthview_events WHERE
							calendarID=? AND eventDate=?
							AND uid=?");
					$db->exec_statement($delP,array($id,$date,$uid));
				}
				else {
					$upP = $db->prepare_statement("UPDATE monthview_events SET
							eventText=?
							WHERE calendarID=? AND eventDate=?
							AND uid=?");
					$db->exec_statement($upP,array($text,$id,$date,$uid));
				}
				break;
			case 'createCalendar':
				$name = FormLib::get_form_value('name');
				$uid = FormLib::get_form_value('uid',0);

				$db = CalendarPluginDB::get();
				$p = $db->prepare_statement("INSERT INTO calendars (name) VALUES (?)");
				$db->exec_statement($p,array($name));

				$id = $db->insert_id();

				$p = $db->prepare_statement("INSERT INTO permissions (calendarID,uid,classID)
								VALUES (?,?,4)");
				$db->exec_statement($p,array($id,$uid));

				$data[] = "<p class=\"index\"><a href=\"?calID=$id&view=month\">$name</a></p>";
				break;
			case 'savePrefs':
				$calID = FormLib::get_form_value('calID');
				$name = str_replace("'","''",$_GET['name']);
				$name = FormLib::get_form_value('name');
				$viewers = FormLib::get_form_value('viewers',array());
				$writers = FormLib::get_form_value('writers',array());

				$db = CalendarPluginDB::get();
				$p = $db->prepare_statement("UPDATE calendars SET name=? WHERE calendarID=?");
				$db->exec_statement($p,array($name,$calID));

				$p = $db->prepare_statement("DELETE FROM permissions WHERE calendarID=? and classID < 4");
				$db->exec_statement($p,array($calID));
				$insP = $db->prepare_statement("INSERT INTO permissions VALUES (?,?,?)");
				if ($viewers != ""){
					foreach(explode(",",$viewers) as $v){
						$db->exec_statement($insP,array($calID,$v,1));
					}
				}
				if ($writers != ""){
					foreach(explode(",",$writers) as $w){
						$db->exec_statement($insP,array($calID,$w,2));
					}
				}
				break;
			}
		}
		return $data;
	}

}

new CalendarAjax();

?>
