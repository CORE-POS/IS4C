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

if(!class_exists("CalendarPluginDB")) include(dirname(__FILE__).'/CalendarPluginDB.php');

class CalendarPluginPermissions {

    public static function get_own_calendars($uid){
        $db = CalendarPluginDB::get();

        $p =  $db->prepare_statement("SELECT c.calendarID,c.name FROM
                calendars AS c LEFT JOIN permissions AS p
                ON c.calendarID=p.calendarID WHERE
                p.uid=? AND p.classID=4 order by c.name");
        $results = $db->exec_statement($p,array($uid));
        $ret = array();
        while($row = $db->fetch_row($results))
            $ret[$row[0]] = $row[1];    

        return $ret;
    }

    public static function get_other_calendars($uid){
        $db = CalendarPluginDB::get();

        $p =  $db->prepare_statement("SELECT c.calendarID,c.name,p.classID FROM
                calendars AS c LEFT JOIN permissions AS p
                ON c.calendarID=p.calendarID WHERE
                p.uid=? or p.uid=-1 order by c.name");
        $results = $db->exec_statement($p,array($uid));
        $ret = array();
        while($row = $db->fetch_row($results)){
            $ret[$row[0]] = $row[1];
            if ($row[2] == 4)
                unset($ret[$row[0]]);
        }

        return $ret;
    }

    public static function can_read($uid,$calID){
        $db = CalendarPluginDB::get();
        $p =  $db->prepare_statement("SELECT c.calendarID,c.name FROM
                calendars AS c LEFT JOIN permissions AS p
                ON c.calendarID=p.calendarID WHERE
                p.uid=? OR p.uid=-1 AND c.calendarID=?");
        $results = $db->exec_statement($p,array($uid,$calID));
        if ($db->num_rows($results) > 0) return True;
        return False;
    }

    public static function can_write($uid,$calID){
        $db = CalendarPluginDB::get();
        $p =  $db->prepare_statement("SELECT c.calendarID,c.name FROM
                calendars AS c LEFT JOIN permissions AS p
                ON c.calendarID=p.calendarID WHERE
                (p.uid=? OR p.uid=-1) AND p.classID > 1 and c.calendarID=?");
        $results = $db->exec_statement($p,array($uid,$calID));
        if ($db->num_rows($results) > 0) return True;
        return False;
    }

    public static function can_admin($uid,$calID){
        $db = CalendarPluginDB::get();
        $p =  $db->prepare_statement("SELECT c.calendarID,c.name FROM
                calendars AS c LEFT JOIN permissions AS p
                ON c.calendarID=p.calendarID WHERE
                p.uid=? AND p.classID > 2 and c.calendarID=?");
        $results = $db->exec_statement($p,array($uid,$calID));
        if ($db->num_rows($results) > 0) return True;
        return False;
    }

    public static function is_owner($uid,$calID){
        $db = CalendarPluginDB::get();
        $p =  $db->prepare_statement("SELECT c.calendarID,c.name FROM
                calendars AS c LEFT JOIN permissions AS p
                ON c.calendarID=p.calendarID WHERE
                p.uid=? AND p.classID = 4 and c.calendarID=?");
        $results = $db->exec_statement($p,array($uid,$calID));
        if ($db->num_rows($results) > 0) return True;
        return False;
    }

}

?>
