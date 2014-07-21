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
include_once(dirname(__FILE__).'/CalendarPluginPermissions.php');

$DAY_NAMES = array(
    array("Sunday","Sun"),
    array("Monday","Mon"),
    array("Tuesday","Tue"),
    array("Wednesday","Wed"),
    array("Thursday","Thu"),
    array("Friday","Fri"),
    array("Saturday","Sat")
);

class CalendarPluginDisplayLib {

    public static function monthView($id,$month,$year,$uid){
        global $DAY_NAMES, $FANNIE_OP_DB;

        $EDIT = CalendarPluginPermissions::can_write($uid,$id);
        $OWNER = CalendarPluginPermissions::is_owner($uid,$id);

        $sql = CalendarPluginDB::get();
        $dataP = $sql->prepare_statement("
            SELECT m.eventDate,m.eventText,m.uid,u.real_name,m.eventID,m.attendeeLimit
            FROM monthview_events as m LEFT JOIN "
            .$FANNIE_OP_DB.$sql->sep()."Users as u on m.uid=u.uid
            WHERE calendarID=? AND
            month(eventDate)=? AND
            year(eventDate)=?
            AND hour(eventDate)=0");
        $dataR = $sql->exec_statement($dataP,array($id,$month,$year));
        $db_data = array();
        while($dataW = $sql->fetch_row($dataR)){
            $dataW[0] = trim(preg_replace('/\d+:\d+:\d+/','',$dataW[0]));
            if (!isset($db_data[$dataW[0]]))
                $db_data[$dataW[0]] = array();
            $db_data[$dataW[0]][] = $dataW;
            //array_unshift($db_data[$dataW[0]],array($dataW[1],$dataW[2],$dataW[3]));
        }

        $calendarModel = new CalendarsModel($sql);
        $calendarModel->calendarID($id);
        $calendarModel->load();
        $name = $calendarModel->name();

        $startTS = mktime(0,0,0,$month,1,$year);

        $prevTS = mktime(0,0,0,$month-1,1,$year);
        $nextTS = mktime(0,0,0,$month+1,1,$year);

        $ret = "<body onload=\"setTimeout('monthview_refresher()',60000)\">";
        $ret .= "<input type=\"hidden\" id=\"calendarID\" value=\"$id\" />";
        $ret .= "<input type=\"hidden\" id=\"uid\" value=\"$uid\" />";

        $ret .= "<div class=\"monthViewHeader\">$name</div>\n";

        $ret .= "<table class=\"monthview\">\n";

        $ret .= "<tr class=\"monthview_paging\">\n";
        $ret .= "<td class=\"monthview_previous\" colspan=\"2\">";
        $ret .= sprintf("<a href=\"?view=month&calID=%s&year=%s&month=%s\">Previous</a>",
                $id,date("Y",$prevTS),date("n",$prevTS));
        $ret .= "</td>\n";
        $ret .= "<td class=\"monthview_current\" colspan=\"3\">";
        $ret .= date("F",$startTS)." ".$year."</td>";
        $ret .= "<td class=\"monthview_next\" colspan=\"2\">";
        $ret .= sprintf("<a href=\"?view=month&calID=%s&year=%s&month=%s\">Next</a>",
                $id,date("Y",$nextTS),date("n",$nextTS));
        $ret .= "</td>\n";
        $ret .= "</tr>\n";
        
        $ret .= "<tr class=\"monthview_head\">\n";
        foreach($DAY_NAMES as $DAY){
            $ret .= "<th class=\"monthview_head\">";
            $ret .= $DAY[0];
            $ret .= "</th>\n";
        }
        $ret .= "</tr>\n";

        $ret .= "<tr class=\"monthview_body\">\n";
        for($i=0;$i<7;$i++){
            if (date("w",$startTS) == $i) break;    
            $ret .= "<td class=\"monthview_body\">";
            $ret .= "&nbsp;";
            $ret .= "</td>\n";
        }
        $classes = array("monthview_box","monthview_box_alt");
        // first week may not contain seven days
        while(date("w",$startTS) > 0) {
            $datestring = date("Y-m-d",$startTS);
            $datebox = "<td class=\"monthview_body\">";
            $datebox .= "<div class=\"monthview_numeral\">\n";
            $datebox .= date("j",$startTS);
            $datebox .= "</div>\n";
            $c = 0;
            $found = false;
            $default_action = '';
            if (isset($db_data[$datestring])) {
                foreach($db_data[$datestring] as $dat) {
                    $datebox .= sprintf("<div class=\"%s\" ",$classes[$c]);
                    $c = ($c+1)%2;
                    if (($EDIT && $uid==$dat['uid']) || $OWNER){
                        $datebox .= " onclick=\"edit_event({$dat['eventID']})\" ";
                        $datebox .= " ondblclick=\"save_open_event()\" ";
                    }
                    if ($dat['uid'] == $uid) {
                        $found = true;
                        $default_action = "edit_event({$dat['eventID']})";
                    }
                    $datebox .= " id=\"event_".$dat['eventID']."\"";
                    $datebox .= sprintf(" title=\"%s\"",($dat['real_name']==""?"Unknown":$dat['real_name']));
                    $datebox .= ">";
                    $datebox .= $dat['eventText'];
                    $datebox .= "</div>";
                    if ($dat['attendeeLimit'] > 0) {
                        $datebox .= '<div id="eventlink_' . $dat['attendeeLimit'] . '">';
                        $datebox .= sprintf('<a href="CalendarAttendedEventPage.php?id=%d">View Event</a>', $dat['eventID']);
                        $datebox .= '</div>';
                    } 
                }
            }
            if (!$found && $EDIT) {
                $datebox .= "<div class=\"monthview_box\" ";
                if ($EDIT){
                    $datebox .= " onclick=\"add_event('$datestring','$uid')\" ";
                    $datebox .= " ondblclick=\"save_open_event()\" ";
                }
                $datebox .= " id=\"event_".$datestring."_".$uid."\"";
                $datebox .= ">";
                $datebox .= "</div>";
                $default_action = "add_event('{$datestring}','{$uid}')";
            }
            $datebox .= "</td>\n";
            $startTS = mktime(0,0,0,date("n",$startTS),date("j",$startTS)+1,date("Y",$startTS));

            $datebox = str_replace("<td", "<td onclick=\"{$default_action}\" ", $datebox);

            $ret .= $datebox;
        }
        $ret .= "</tr>\n";

        // now the remaining weeks
        while(date("n",$startTS) == $month) {
            $ret .= "<tr class=\"monthview_body\">\n";
            for ($i=0;$i<7;$i++){
                $datestring = date("Y-m-d",$startTS);
                $datebox = "<td class=\"monthview_body\">";
                if (date("n",$startTS) == $month) {
                    $datebox .= "<div class=\"monthview_numeral\">\n";
                    $datebox .= date("j",$startTS); 
                    $datebox .= "</div>\n";
                    $c = 0;
                    $found = false;
                    $default_action = '';
                    if (isset($db_data[$datestring])) {
                        foreach($db_data[$datestring] as $dat) {
                            $datebox .= sprintf("<div class=\"%s\" ",$classes[$c]);
                            $c = ($c+1)%2;
                            if (($EDIT && $uid==$dat['uid']) || $OWNER){
                                $datebox .= " onclick=\"edit_event({$dat['eventID']})\" ";
                                $datebox .= " ondblclick=\"save_open_event()\" ";
                            }
                            if ($dat['uid'] == $uid) {
                                $found = true;
                                $default_action = "edit_event({$dat['eventID']})";
                            }
                            $datebox .= " id=\"event_".$dat['eventID']."\"";
                            $datebox .= sprintf(" title=\"%s\"",($dat['real_name']==""?"Unknown":$dat['real_name']));
                            $datebox .= ">";
                            $datebox .= $dat['eventText'];
                            $datebox .= "</div>";
                            if ($dat['attendeeLimit'] > 0) {
                                $datebox .= '<div class="monthview_box" id="eventlink_' . $dat['attendeeLimit'] . '">';
                                $datebox .= sprintf('&nbsp;&nbsp;<a onclick="" href="CalendarAttendedEventPage.php?id=%d">View Event</a>', $dat['eventID']);
                                $datebox .= '</div>';
                            } 
                        }
                    }
                    if (!$found && $EDIT) {
                        $datebox .= "<div class=\"monthview_box\" ";
                        if ($EDIT){
                            $datebox .= " onclick=\"add_event('$datestring','$uid')\" ";
                            $datebox .= " ondblclick=\"save_open_event()\" ";
                        }
                        $datebox .= " id=\"event_".$datestring."_".$uid."\"";
                        $datebox .= ">";
                        $datebox .= "</div>";
                        $default_action = "add_event('{$datestring}','{$uid}')";
                    }
                } else {
                    $datebox .= "&nbsp;";
                }
                $datebox .= "</td>\n";
                $startTS = mktime(0,0,0,date("n",$startTS),date("j",$startTS)+1,date("Y",$startTS));

                $datebox = str_replace("<td", "<td onclick=\"{$default_action}\" ", $datebox);

                $ret .= $datebox;
            }
            $ret .= "</tr>\n";
        }
        $ret .= "</table>\n";

        $ret .= "<div style=\"text-align:center;\">\n";
        $ret .= "<a href=?view=index>Back to list of calendars</a>";
        if ($OWNER) {
            $ret .= " || <a href=?view=prefs&calID=$id>Settings for this calendar</a>\n";
        }
        $ret .= "</div>\n";
            
        return $ret;
    }

    public static function weekView($id, $year, $week)
    {
        $sql = CalendarPluginDB::get();
        $calendarModel = new CalendarsModel($sql);
        $calendarModel->calendarID($id);
        $calendarModel->load();
        $name = $calendarModel->name();

        $uid = FannieAuth::getUID(FannieAuth::checkLogin());
        $EDIT = CalendarPluginPermissions::can_write($uid,$id);
        $OWNER = CalendarPluginPermissions::is_owner($uid,$id);

        $startTS = strtotime($year . '-W' . str_pad($week,2,'0',STR_PAD_LEFT) . '-1');
        $endTS = mktime(0, 0, 0, date('n', $startTS), date('j', $startTS)+6, date('Y', $startTS));
        $query = 'SELECT eventDate, eventText, eventID
                  FROM monthview_events
                  WHERE calendarID=?
                    AND eventDate BETWEEN ? AND ?';
        $prep = $sql->prepare($query);
        $args = array($id, date('Y-m-d 00:00:00', $startTS), date('Y-m-d 23:59:59', $endTS));
        $result = $sql->execute($prep, $args);
        $cal_data = array();
        while($row = $sql->fetch_row($result)) {
            $cal_ts = strtotime($row['eventDate']);
            $cal_data[$cal_ts] = array('id'=>$row['eventID'], 'text'=>$row['eventText']);
        }
        $startT = 7;
        $endT = 21;

        $prevWeek = mktime(0, 0, 0, date('n', $startTS), date('j', $startTS)-7, date('Y', $startTS));
        $nextWeek = mktime(0, 0, 0, date('n', $startTS), date('j', $startTS)+7, date('Y', $startTS));

        $ret = '<table cellpadding="4" cellspacing="0" border="1">';

        // paging
        $ret .= '<tr>';
        $ret .= sprintf('<td colspan="3" align="left">
                        <a href="?view=week&calID=%d&week=%d&year=%d">Prev</a></td>',
                        $id, date('W', $prevWeek), date('Y', $prevWeek));
        $ret .= '<td align="center">' . date('Y', $startTS) . '</td>';
        $ret .= sprintf('<td colspan="4" align="right">
                        <a href="?view=week&calID=%d&week=%d&year=%d">Next</a></td>',
                        $id, date('W', $nextWeek), date('Y', $nextWeek));
        $ret .= '</tr>';

        $ret .= '<tr><th>' . $name . '</th>';
        for($i=0; $i<7; $i++) {
            $ts = mktime(0, 0, 0, date('n', $startTS), date('j', $startTS) + $i, date('Y', $startTS));
            $ret .= '<th>' . date('M j', $ts) . '<br />' . date('l', $ts) . '</th>';
        }
        $ret .= '</tr>';
        for ($hour = $startT; $hour < $endT; $hour++) {
            $ret .= '<tr>';
            $ret .= '<td>' . date('h:i A', mktime($hour, 0)) . '</td>';
            for($i=0; $i<7; $i++) {
                $entry_ts = mktime($hour, 0, 0, date('n', $startTS), date('j', $startTS)+$i, date('Y', $startTS));
                if ($EDIT) {
                    $ret .= sprintf('<td id="weekEntry%d" class="weekEntry"
                                    onclick="weekClickCallback(%d);"
                                    ondblclick="saveCallback(%d);">
                                    <input type="hidden" class="weekEntryTS" value="%d" />
                                    <span class="weekEntryContent">%s</span>',
                                    $entry_ts,
                                    $entry_ts,
                                    $entry_ts,
                                    $entry_ts,
                                    (isset($cal_data[$entry_ts]) ? $cal_data[$entry_ts]['text'] : '')
                    );
                    if (isset($cal_data[$entry_ts])) {
                        $ret .= sprintf('<input type="hidden" class="weekEntryEventID" value="%d" />',
                                        $cal_data[$entry_ts]['id']);
                    }
                } else {
                    $ret .= '<td class="weekEntry"><span class="weekEntryContent">';
                    $ret .= isset($cal_data[$entry_ts]) ? $cal_data[$entry_ts]['text'] : '';
                    $ret .= '</span>';
                }
                $ret .= '</td>';
            }
            $ret .= '</tr>';
            $ret .= '<tr>';
            $ret .= '<td>' . date('h:i A', mktime($hour, 30)) . '</td>';
            for($i=0; $i<7; $i++) {
                $entry_ts = mktime($hour, 30, 0, date('n', $startTS), date('j', $startTS)+$i, date('Y', $startTS));
                if ($EDIT) {
                    $ret .= sprintf('<td id="weekEntry%d" class="weekEntry"
                                    onclick="weekClickCallback(%d);"
                                    ondblclick="saveCallback(%d);">
                                    <input type="hidden" class="weekEntryTS" value="%d" />
                                    <span class="weekEntryContent">%s</span>',
                                    $entry_ts,
                                    $entry_ts,
                                    $entry_ts,
                                    $entry_ts,
                                    (isset($cal_data[$entry_ts]) ? $cal_data[$entry_ts]['text'] : '')
                    );
                    if (isset($cal_data[$entry_ts])) {
                        $ret .= sprintf('<input type="hidden" class="weekEntryEventID" value="%d" />',
                                        $cal_data[$entry_ts]['id']);
                    }
                } else {
                    $ret .= '<td class="weekEntry"><span class="weekEntryContent">';
                    $ret .= isset($cal_data[$entry_ts]) ? $cal_data[$entry_ts]['text'] : '';
                    $ret .= '</span>';
                }
                $ret .= '</td>';
            }
            $ret .= '</tr>';
        }
        $ret .= '</table>';
        $ret .= '<input type="hidden" id="calendarID" value="' . $id . '" />';

        return $ret;
    }

    public static function indexView($uid){
        global $FANNIE_URL;
        $yours = CalendarPluginPermissions::get_own_calendars($uid);
        $theirs = CalendarPluginPermissions::get_other_calendars($uid);             

        $ret = "<body>";
        $ret .= "<div class=indexTitle>Your Calendars</div>";
        $ret .= "<div id=yours>";
        foreach($yours as $k=>$v){
            $ret .= "<p class=\"index\"><a href=\"?calID=$k&view=week\">$v</a></p>";
        }
        $ret .= "</div>";
        $ret .= "<p class=\"index\" id=\"indexCreateNew\">";
        $ret .= "<a href=\"\" onclick=\"newCalendar('$uid');return false;\">";
        $ret .= "Create a new calendar</a></p>";
        $ret .= "<p class=\"index\" id=\"indexAttendedEvent\">";
        $ret .= "<a href=\"CalendarAttendedEventPage.php\">";
        $ret .= "Create an attended event</a></p>";

        $ret .= "<div class=indexTitle>Other Calendars</div>";
        $ret .= "<div id=theirs>";
        foreach($theirs as $k=>$v){
            $ret .= "<p class=\"index\"><a href=\"?calID=$k&view=week\">$v</a></p>";
        }
        $ret .= "</div>";

        $ret .= "<div class=indexTitle>Tools</div>";
        $ret .= "<div id=theirs>";
        $ret .= "<p class=\"index\"><a href=index.php?view=overlays>Compare Calendars</a></p>";
        $ret .= "</div>";
        $url = $FANNIE_URL."auth/ui/loginform.php?logout=yes";
        $ret .= "<p><a href=\"$url\">Logout</a></p>";

        return $ret;
    }

    public static function showoverlayView($calIDs,$startDate,$endDate){
        $ret = "<body>";

        $sql = CalendarPluginDB::get();
        $ids = "(";
        $args = array();
        foreach($calIDs as $c){
            $ids .= "?,";
            $args[] = $c;
        }
        $ids = rtrim($ids,",");
        $ids .= ")";

        $dataP = $sql->prepare_statement("
            SELECT m.eventDate,m.eventText,c.name
            FROM monthview_events as m LEFT JOIN
            calendars as c on m.calendarID=c.calendarID
            WHERE m.calendarID in $ids AND
            datediff(eventDate,?)>=0
            and datediff(eventDate,?)<=0
            order by eventDate,c.name");
        $args[] = $startDate;
        $args[] = $endDate;
        $dataR = $sql->exec_statement($dataP,$args);
        $curDate = "";
        $classes = array("overlay_one","overlay_two");
        $c = 0;
        while($dataW = $sql->fetch_row($dataR)){
            if ($curDate != $dataW[0]){
                if ($curDate != "")
                    $ret .= "</div>";
                $ret .= "<div class=\"overlay_outerbox\">";
                $temp = explode("-",$dataW[0]);
                $ts = mktime(0,0,0,$temp[1],$temp[2],$temp[0]);
                $str = date("l, F j, Y",$ts);
                $ret .= "<div class=\"overlay_date\"><h3>$str</h3></div>";
                $c = 0;
                $curDate = $dataW[0];
            }
            $ret .= "<div class=\"".$classes[$c]."\">";
            $ret .= "<b>$dataW[2]</b>: $dataW[1]</div>";
            $c = ($c+1) % 2;
        }
        $ret .= "</div>";
        $ret .= "</body>";

        $ret .= "<div style=\"text-align:center;\">\n";
        $ret .= "<a href=?view=index>Back to list of calendars</a>";
        $ret .= "&nbsp;&nbsp;&nbsp;";
        $ret .= "::";
        $ret .= "&nbsp;&nbsp;&nbsp;";
        $ret .= "<a href=?view=overlays>View a different combination</a>";
        $ret .= "</div>";

        return $ret;
    }

    public static function overlaysView($uid){
        $yours = CalendarPluginPermissions::get_own_calendars($uid);
        $theirs = CalendarPluginPermissions::get_other_calendars($uid);

        $ret = "<body>";
        $ret .= "<form action=index.php method=get>";
        $ret .= "<p>Select calendars (hold apple or ctrl to select multiple)</p>";
        $ret .= "<div style=\"float: left; margin-right: 10px;\">";
        $ret .= "<select name=cals[] multiple size=15>";
        foreach($yours as $k=>$v){
            $ret .= "<option value=$k>$v</option>";
        }
        foreach($theirs as $k=>$v){
            $ret .= "<option value=$k>$v</option>";
        }
        $ret .= "</select>";
        $ret .= "</div>";
        $ret .= "<div id=overlayinput>";
        $ret .= "<b>Start Date</b>: <input type=text name=startdate id=\"startdate\" />";
        $ret .= "<p />";
        $ret .= "<b>End Date</b>: <input type=text name=enddate id=\"enddate\" />";
        $ret .= "<p />";
        $ret .= "<input type=submit value=Submit />";
        $ret .= "<input type=hidden name=view value=showoverlay />";
        $ret .= "</div>";
        $ret .= "</form>";

        $ret .= "<div style=\"clear:left; text-align:center;\">\n";
        $ret .= "<a href=?view=index>Back to list of calendars</a>";
        $ret .= "</div>";
        $ret .= "</body>";

        return $ret;
    }

    public static function prefsView($calID,$uid){
        global $FANNIE_OP_DB;
        if (!CalendarPluginPermissions::is_owner($uid,$calID)){
            return "<h2>Either something goofed up or you aren't allowed to change
                settings for this calendar</h2>";
        }

        $db = CalendarPluginDB::get();
        $name = array_pop(
            $db->fetch_row(
                $db->exec_statement(
                    $db->prepare_statement('SELECT name FROM calendars
                                WHERE calendarID=?'),
                    array($calID)
                )
            )
        );

        $ret = "<body>";
        $ret .= "<p>Name: <input type=text size=15 id=prefName value=\"$name\" />";
        $ret .= "</p><hr />";

        $userP = $db->prepare_statement("SELECT uid,real_name,name FROM "
                    .$FANNIE_OP_DB.$db->sep()."Users 
                    WHERE uid<>? order by name,real_name");
        $userR = $db->exec_statement($userP,array($uid));
        $userOpts = array();
        while ($userW = $db->fetch_row($userR)){
            $name = $userW['real_name'];
            if ($name == '') $name = $userW['name'];
            else if ($name == 'Array') $name = $userW['name'];
            $userOpts[$userW['uid']] = "<option value=\"{$userW['uid']}\">{$name}</option>";
        }

        $ret .= "<p>Users who can view this calendar (<i>left</i>):";
        $ret .= "<table><tr>";
        $viewP = $db->prepare_statement("SELECT p.uid,u.real_name,u.name FROM permissions as p
              LEFT JOIN ".$FANNIE_OP_DB.$db->sep()."Users as u on p.uid=u.uid
              WHERE p.calendarID=?
              AND p.classID = 1");
        $viewR = $db->exec_statement($viewP,array($calID));
        $ret .= "<td><select id=prefViewers multiple size=10 style=\"min-width:50px\">";
        while($viewW = $db->fetch_row($viewR)){
            $name = $userW['real_name'];
            if ($viewW[0] == -1) $name = "Everyone";
            elseif ($name == '') $name = $userW['name'];
            else if ($name == 'Array') $name = $userW['name'];
            $ret .= "<option value=$viewW[0]>$name</option>";
        }
        $ret .= "</select></td>";
        $ret .= "<td><input type=submit value=\"<<\" onclick=\"select_add('prefViewers2','prefViewers');\" /><p />";
        $ret .= "<input type=submit value=\">>\" onclick=\"select_remove('prefViewers');\" /></td>";
        $ret .= "<td><select id=prefViewers2 multiple size=10>";
        $ret .= "<option value=-1>Everyone</option>";
        foreach($userOpts as $k=>$v)
            $ret .= $v;
        $ret .= "</select></td>";
        $ret .= "</tr></table>";
        $ret .= "</p><hr />";

        $ret .= "<p>Users who can write on this calendar (<i>left</i>):";
        $ret .= "<table><tr>";
        $viewP = $db->prepare_statement("SELECT p.uid,u.real_name,u.name FROM permissions as p
              LEFT JOIN ".$FANNIE_OP_DB.$db->sep()."Users as u on p.uid=u.uid
              WHERE p.calendarID=?
              AND p.classID = 2");
        $viewR = $db->exec_statement($viewP,array($calID));
        $ret .= "<td><select id=prefWriters multiple size=10 style=\"min-width:50px\">";
        while($viewW = $db->fetch_row($viewR)){
            $name = $userW['real_name'];
            if ($viewW[0] == -1) $name = "Everyone";
            elseif ($name == '') $name = $userW['name'];
            else if ($name == 'Array') $name = $userW['name'];
            $ret .= "<option value=$viewW[0]>$name</option>";
        }
        $ret .= "</select></td>";
        $ret .= "<td><input type=submit value=\"<<\" onclick=\"select_add('prefWriters2','prefWriters');\" /><p />";
        $ret .= "<input type=submit value=\">>\" onclick=\"select_remove('prefWriters');\" /></td>";
        $ret .= "<td><select id=prefWriters2 multiple size=10>";
        $ret .= "<option value=-1>Everyone</option>";
        foreach($userOpts as $k=>$v)
            $ret .= $v;
        $ret .= "</select></td>";
        $ret .= "</tr></table>";
        $ret .= "</p><hr />";

        $ret .= "<input type=submit value=\"Save Settings\" onclick=\"savePrefs($calID);return false;\" /> ";
        $ret .= "<input type=submit value=\"Back to Calendar\" onclick=\"top.location='?view=month&calID=$calID';\" /> ";

        return $ret;
    }

}

?>
