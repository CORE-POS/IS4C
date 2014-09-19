<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DeleteTenderPage extends FanniePage {

    protected $title = "Fannie : Tenders";
    protected $header = "Tenders";
    protected $must_authenticate = True;
    protected $auth_classes = array('tenders');

    public $description = '[Delete Tender] gets rid of a tender type.';

    private $mode = 'form';

    function preprocess(){
        $id = FormLib::get_form_value('TenderID',False);
        if (is_numeric($id))
            $this->mode = 'results';
        return True;
    }

    function body_content(){
        if ($this->mode == 'form')
            return $this->form_content();
        elseif ($this->mode == 'results')
            return $this->results_content();
    }

    function results_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $id = FormLib::get_form_value('TenderID');
        $tender = new TendersModel($dbc);
        $tender->TenderID($id);
        $tender->delete();

        $ret = "<i>Tender deleted</i>";
        $ret .= "<br /><br />";
        $ret .= '<a href="DeleteTenderPage.php">Delete another</a>';
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= '<a href="index.php">Back to edit tenders</a>';
        return $ret;
    }
    
    function form_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = "<b>Be careful. Deleting a tender could make a mess.
            If you run into problems, re-add the tender using
            the same two-character code.</b>";
        $ret .= "<br /><br />";
        $ret .= '<form action="DeleteTenderPage.php" method="post">';
        $ret .= '<select name="TenderID">';
        $ret .= '<option>Select a tender...</option>';
        $tender = new TendersModel($dbc);
        foreach($tender->find('TenderID') as $obj){
            $ret .= sprintf('<option value="%d">%s - %s</option>',
                $obj->TenderID(),$obj->TenderCode(),
                $obj->TenderName());
        }
        $ret .= "</select>";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= '<input type="submit" value="Delete Selected Tender" />';
        $ret .= "</form>";
        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

