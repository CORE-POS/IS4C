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
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CalendarDeletePage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('admin');
    protected $header = 'Delete Calendars';
    protected $title = 'Delete Calendars';

    public $page_set = 'Plugin :: Calendar';
    public $discoverable = false;

    public function delete_id_handler()
    {
        $dbc = CalendarPluginDB::get();

        $prep = $dbc->prepare('DELETE FROM monthview_events WHERE calendarID=?');
        $res = $dbc->execute($prep, array($this->id));

        $prep = $dbc->prepare('DELETE FROM permissions WHERE calendarID=?');
        $res = $dbc->execute($prep, array($this->id));

        $calendar = new CalendarsModel($dbc);
        $calendar->calendarID($this->id);
        $calendar->load();

        $prep = $dbc->prepare('DELETE FROM CalendarSubscriptions WHERE calendarSubscriptionID=?');
        $res = $dbc->execute($prep, array($calendar->calendarSubscriptionID()));

        $calendar->delete();

        return $_SERVER['PHP_SELF'];
    }

    public function get_view()
    {
        $dbc = CalendarPluginDB::get();
        $calendars = new CalendarsModel($dbc);

        $ret = '<form method="get" action="' . $_SERVER['PHP_SELF'] . '">
            <input type="hidden" name="_method" value="delete" />
            <div class="form-group">
            <label>Delete Calendar</label>
            <select name="id" class="form-control">
                <option value="0">Choose one...</option>';
        $ret .= $calendars->toOptions();
        $ret .= '</select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
            </form>';

        return $ret;
    }

}

FannieDispatch::conditionalExec();

