<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CoopCredMemberPage extends FanniePage {

    protected $pluginHome = 'modules/plugins2.0/CoopCred/';
    private $country;
    private $memNum;

    function preprocess(){
        global $FANNIE_PLUGIN_LIST, $FANNIE_PLUGIN_SETTINGS;

        if (!isset($FANNIE_PLUGIN_LIST) || !in_array('CoopCred', $FANNIE_PLUGIN_LIST)) {
            $this->errors .= _("Error: The Coop Cred Plugin is not enabled.");
            return True;
        }

        if (!array_key_exists('CoopCredDatabase', $FANNIE_PLUGIN_SETTINGS) ||
            $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'] == "") {
            $this->errors .= _("Error: Coop Cred Database not named in Plugin Settings.");
            return True;
        }

        $this->country = (isset($FANNIE_COUNTRY)&&!empty($FANNIE_COUNTRY))?$FANNIE_COUNTRY:"US";
        $this->memNum = FormLib::get_form_value('memNum');

        /* Support ajax calls to this program.
         * If there is a form submission with an action go do it.
         * The form submission may be via AJAX instead of <form ...>
         *  with action= in the query string with other parameters.
         */
        if(FormLib::get_form_value('action') !== ''){
            $this->ajax_response(FormLib::get_form_value('action'));
            return False;
        }

        /* If the call was not by form, e.g. from the initial menu
         * or the <form action=> is '' (when does that happen?)
         * FanniePage::draw_page() continues to $this->body_content()
         *  which returns the the program-select form.
         */
        return True;

    // preprocess()
    }

    /* Handles one of the requests assumed to be AJAX.
     * The output of the handler (responder) is a string which can be HTML
     *  formatted if the ajax caller success: function can deal with it.
     *  In this program, success: either:
     *   - assigns to <div id=infodiv>
     *   - displays with JS alert()
     */
    function ajax_response($action){
        switch($action){
        case 'programDisplay':
            $this->ajax_display_program(FormLib::get_form_value('pid',0));
            break;
        case 'programSave':
            /* Return success/failure/problem (duplicate-use)?
             * I want to return to the display with error messages
             *  and/or error inputs highlighted.
             */
            $this->ajax_save_program();
            break;
        default:
            echo 'Bad request';
            break;
        }
    }

    /* Echo the data capture form.
     */
    private function ajax_display_program($pid){
        global $FANNIE_ROOT;

        $mm = 'CoopCredMember';
        if (!class_exists($mm)) {
            include("{$FANNIE_ROOT}{$this->pluginHome}membership/" .
                $mm . '.php');
        }
        else {
            $ret = "<p>Already have access to $mm for member {$this->memNum} for program $pid</p>";
        }
        $instance = new $mm();
        $ret = $instance->ShowEditForm($this->memNum, $this->country, $pid);

        echo $ret;

    // ajax_display_program()
    }

// CoopCredMemberPage class
}

FannieDispatch::conditionalExec();

