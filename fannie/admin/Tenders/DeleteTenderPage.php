<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
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

class DeleteTenderPage extends FannieRESTfulPage 
{
    protected $title = "Fannie : Tenders";
    protected $header = "Tenders";
    protected $must_authenticate = True;
    protected $auth_classes = array('tenders');

    public $description = '[Delete Tender] gets rid of a tender type.';
    public $themed = true;

    protected function get_id_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $id = $this->id;
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
    
    protected function get_view()
    {
        $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $ret = "<div class=\"well\">Be careful. Deleting a tender could make a mess.
            If you run into problems, re-add the tender using
            the same two-character code.</div>";
        $ret .= '<form action="DeleteTenderPage.php" method="post">';
        $ret .= '<p><select name="id" class="form-control">';
        $ret .= '<option>Select a tender...</option>';
        $tender = new TendersModel($dbc);
        foreach($tender->find('TenderID') as $obj){
            $ret .= sprintf('<option value="%d">%s - %s</option>',
                $obj->TenderID(),$obj->TenderCode(),
                $obj->TenderName());
        }
        $ret .= "</select></p>";
        $ret .= '<p><button type="submit" class="btn btn-default">Delete Selected Tender</button></p>';
        $ret .= '<p><button type="button" class="btn btn-default" onclick="location=\'TenderEditor.php\';">Back to Tenders</button></p>';
        $ret .= "</form>";
        $this->add_onload_command("\$('select.form-control').focus();\n");

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Deleting a tender will not remove it from the lanes
            until the next time tenders are synced. Deleting a tender 
            that has been used in the past may create problems with some
            reports on historical data where that tender was used.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 'FOO';
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }
}

FannieDispatch::conditionalExec();

