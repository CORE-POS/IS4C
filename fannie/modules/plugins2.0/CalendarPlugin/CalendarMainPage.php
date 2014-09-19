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
if (!class_exists('FannieAuth'))
    include($FANNIE_ROOT.'classlib2.0/auth/FannieAuth.php');
include_once(dirname(__FILE__).'/CalendarPluginDisplayLib.php');

class CalendarMainPage extends FanniePage {

    public $page_set = 'Plugin :: Calendar';
    public $description = '[Calendar Plugin] is a simple click to edit shared calendar.';

    protected $must_authenticate = True;
    private $uid;

    function preprocess(){
        global $FANNIE_URL;
        $this->uid = ltrim(FannieAuth::getUID($this->current_user),"0");
        $this->title = "Cal";
        $this->header = "Calendars";
        
        $plugin = new CalendarPlugin(); 
        $this->add_script($FANNIE_URL . 'src/javascript/jquery.js');
        $this->add_script($FANNIE_URL . 'src/javascript/jquery-ui.js');
        $this->add_script($plugin->plugin_url().'/javascript/calendar.js');
        $this->add_script($plugin->plugin_url().'/javascript/ajax.js');

        $view = FormLib::get_form_value('view','index');
        if ($view == 'month') 
            $this->window_dressing = False;
        else
            $this->add_css_file($FANNIE_URL.'src/javascript/jquery-ui.css');

        if (file_exists(dirname(__FILE__).'/css/'.$view.'.css'))
            $this->add_css_file($plugin->plugin_url().'/css/'.$view.'.css');

        return True;
    }
    
    function body_content(){
        $view = FormLib::get_form_value('view','index');
        switch ($view){
        case 'month':
            $editable = True;

            $year = FormLib::get_form_value('year',date('Y'));
            $month = FormLib::get_form_value('month',date('n'));
            $calID = FormLib::get_form_value('calID',0);

            echo CalendarPluginDisplayLib::monthView($calID,$month,$year,$this->uid);
            break;
        case 'week':
            $year = FormLib::get_form_value('year',date('Y'));
            $week = FormLib::get_form_value('week', date('W'));
            $calID = FormLib::get_form_value('calID',0);
            
            if ($calID == 0) {
                echo CalendarPluginDisplayLib::indexView($this->uid);
            } else {
                echo CalendarPluginDisplayLib::weekView($calID, $year, $week);
                $this->add_onload_command('weekBootstrap();');
            }
            break;
        case 'prefs':
            $calID = FormLib::get_form_value('calID','');
            echo CalendarPluginDisplayLib::prefsView($calID,$this->uid);
            break;
        case 'overlays':
            echo CalendarPluginDisplayLib::overlaysView($this->uid);
            $this->add_onload_command("\$('#startdate').datepicker();\n");
            $this->add_onload_command("\$('#enddate').datepicker();\n");
            break;
        case 'showoverlay':
            $cals = FormLib::get_form_value('cals');
            $start = FormLib::get_form_value('startdate');
            $end = FormLib::get_form_value('enddate');
            echo CalendarPluginDisplayLib::showoverlayView($cals,$startdate,$enddate);
            break;
        case 'index':
        default:
            echo CalendarPluginDisplayLib::indexView($this->uid);
            break;
        }
    }

}

FannieDispatch::conditionalExec(false);

?>
