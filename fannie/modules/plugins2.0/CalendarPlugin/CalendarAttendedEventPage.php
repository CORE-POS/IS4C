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

include_once(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FanniePage'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists('CalendarPlugin'))
    include(dirname(__FILE__).'/CalendarPlugin.php');
if (!class_exists('CalendarPluginDB'))
    include(dirname(__FILE__).'/CalendarPluginDB.php');
if (!class_exists('PermissionsModel'))
    include(dirname(__FILE__).'/models/PermissionsModel.php');
if (!class_exists('CalendarsModel'))
    include(dirname(__FILE__).'/models/CalendarsModel.php');
if (!class_exists('MonthviewEventsModel'))
    include(dirname(__FILE__).'/models/MonthviewEventsModel.php');

class CalendarAttendedEventPage extends FannieRESTfulPage 
{

    protected $must_authenticate = true;

    public $page_set = 'Plugin :: Calendar';
    public $description = '[Attended Event] is a calendar entry with an attendee list attached.';
    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<id><join_id>';
        $this->__routes[] = 'get<id><leave_id>';

        return parent::preprocess();
    }

    public function get_handler()
    {
        global $FANNIE_URL;
        $this->header = 'Create Attended Event';
        $this->title = 'Create Attended Event';

        return true;
    }

    public function get_view()
    {
        $uid = FannieAuth::getUID(FannieAuth::checkLogin());
        $dbc = CalendarPluginDB::get();

        $perm = new PermissionsModel($dbc);
        $perm->uid($uid);
        $calIDs = array();
        foreach($perm->find() as $obj) {
            if ($obj->classID() > 1) {
                $calIDs[] = $obj->calendarID();
            } 
        }

        $ret = '<form action="CalendarAttendedEventPage.php" method="post">';
        $ret .= '<div class"form-group">
            <label>Calendar</label>: 
            <select name="calendarID" class="form-control">';
        $cal = new CalendarsModel($dbc);
        foreach($calIDs as $id) {
            $cal->calendarID($id);
            $cal->load();
            $ret .= sprintf('<option value="%d">%s</option>', $id, $cal->name());
        }
        $ret .= '</select></div>';

        $ret .= '<div class="form-group">
            <label>Date</label>: 
            <input type="text" class="form-control date-field" id="datestr" 
                required name="datestr" /></div>';

        $ret .= '<div class="form-group">
            <label>Max Attendees</label>: 
            <input type="number" class="form-control" required name="limit" />
            </div>';

        $ret .= '<div class="form-group">
                <label>Event Description</label>:
                <textarea name="text" class="form-control"></textarea>
                </div>';

        $ret .= '<p><button type="submit" class="btn btn-default">Create Event</button></p>';
        $ret .= '</form>';

        $ret .= '<p><a class="btn btn-default" href="CalendarMainPage.php">Home</a></p>';

        return $ret;
    }

    public function post_handler()
    {
        $uid = FannieAuth::getUID(FannieAuth::checkLogin());
        $dbc = CalendarPluginDB::get();
        
        $textstr = FormLib::get('text');
        $calID = FormLib::get('calendarID');
        $datestr = FormLib::get('datestr');
        $limit = FormLib::get('limit');

        if (empty($datestr) || empty($textstr)) {
            echo 'Error: date and description are required';
            return false;
        } else if (!is_numeric($calID)) {
            echo 'Error: invalid calendar';
            return false;
        } else if (!is_numeric($limit)) {
            echo 'Error: attendence limit required';
            return false;
        }

        $model = new MonthviewEventsModel($dbc);
        $model->uid($uid);
        $model->calendarID($calID);
        $model->eventDate($datestr);
        $model->attendeeLimit($limit);
        $textstr = str_replace("\r", '', $textstr);
        $textstr = str_replace("\n", '<br>', $textstr);
        $model->eventText($textstr);

        $id = $model->save();
        if ($id === false) {
            echo 'Error saving event!';
        } else {
            header('Location: CalendarAttendedEventPage.php?id=' . $id);
        }

        return false;
    }

    public function get_id_handler()
    {
        $this->header = 'View Event';
        $this->title = 'View Event';

        return true;
    }

    public function get_id_view()
    {
        global $FANNIE_OP_DB;
        $uid = FannieAuth::getUID(FannieAuth::checkLogin());
        $dbc = CalendarPluginDB::get();

        $event = new MonthviewEventsModel($dbc);
        $event->eventID($this->id);
        $event->load();

        list($date, $time) = explode(' ', $event->eventDate());
        $ret = '<h3>' . $date . '</h3>';
        $ret .= '<div class="eventDesc">' . $event->eventText() . '</div>';

        $ret .= '<hr />';

        $attending = false;
        $ret .= '<h3>Attendees</h3>';
        $ret .= '<ol>';
        $query = 'SELECT m.uid, u.real_name
                  FROM attendees AS m
                    INNER JOIN '.$FANNIE_OP_DB.$dbc->sep().'Users AS u ON m.uid=u.uid
                  WHERE m.eventID=?
                  ORDER BY attendeeID';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $this->id);
        $num = ($result) ? $dbc->num_rows($result) : 0;
        while($row = $dbc->fetch_row($result)) {
            $ret .= '<li>' . $row['real_name'] . '</li>';
            if ($row['uid'] == $uid) {
                $attending = true;
            }
        }

        $ret .= '</ol>';

        if (!$attending && $num < $event->attendeeLimit()) {
            $ret .= sprintf('<a href="CalendarAttendedEventPage.php?id=%d&join_id=%d">Sign up for this Event</a>',
                    $this->id, $uid);
        } else if (!$attending) {
            $ret .= 'This event is at capacity';
        } else {
            $ret .= sprintf('<a href="CalendarAttendedEventPage.php?id=%d&leave_id=%d">Take myself off the List</a>',
                    $this->id, $uid);
        }

        $ret .= '<div style="margin-top:20px"><a href="CalendarMainPage.php">Home</a></div>';

        return $ret;
    }

    public function get_id_join_id_handler()
    {
        $dbc = CalendarPluginDB::get();
        $model = new AttendeesModel($dbc);
        $model->eventID($this->id);
        $model->uid($this->join_id);
        $model->save();

        header('Location: CalendarAttendedEventPage.php?id=' . $this->id);

        return false;
    }

    public function get_id_leave_id_handler()
    {
        $dbc = CalendarPluginDB::get();
        $model = new AttendeesModel($dbc);
        $model->eventID($this->id);
        $model->uid($this->leave_id);
        $model->delete();

        header('Location: CalendarAttendedEventPage.php?id=' . $this->id);

        return false;
    }

}

FannieDispatch::conditionalExec();

