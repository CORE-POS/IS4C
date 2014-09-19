<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class VendorDepartmentEditor extends FanniePage {
    protected $title = "Fannie : Manage Vendors";
    protected $header = "Manage Vendors";

    public $description = '[Vendor Departments] manages vendor-specific departments or categories.
    These are distinct from POS departments.';

    function preprocess(){

        $ajax = FormLib::get_form_value('action');
        if ($ajax !== ''){
            $this->ajax_callbacks($ajax);
            return False;
        }       

        return True;
    }

    function ajax_callbacks($action){
        global $FANNIE_OP_DB;
        switch($action){
        case 'createCat':
            $this->createVendorDepartment(
                FormLib::get_form_value('vid'),
                FormLib::get_form_value('deptID'),
                FormLib::get_form_value('name')
            );
            break;
        case 'deleteCat':
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $q = $dbc->prepare_statement("DELETE FROM vendorDepartments
                WHERE vendorID=? AND deptID=?");
            $dbc->exec_statement($q,
                array(FormLib::get_form_value('vid'),
                FormLib::get_form_value('deptID')) );
            echo "Department deleted";
            break;
        case 'updateCat':
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $q = $dbc->prepare_statement("UPDATE vendorDepartments
                SET name=?, margin=?
                WHERE vendorID=? AND deptID=?");
            $args = array(
                FormLib::get_form_value('name'),
                trim(FormLib::get_form_value('margin',0),'%')/100,
                FormLib::get_form_value('vid'),
                FormLib::get_form_value('deptID')
            );
            $dbc->exec_statement($q,$args);
            echo 'Saved';
            break;
        default:
            echo 'bad request';
            break;
        }
    }

    private function createVendorDepartment($vid,$did,$name){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $chkQ = $dbc->prepare_statement("SELECT * FROM vendorDepartments WHERE
                vendorID=? AND deptID=?");
        $chkR = $dbc->exec_statement($chkQ,array($vid,$did));
        if ($dbc->num_rows($chkR) > 0){
            echo "Number #$did is already in use!";
            return;
        }

        $insQ = $dbc->prepare_statement("INSERT INTO vendorDepartments (vendorID,deptID,
            name,margin,testing,posDeptID) VALUES (?,?,
            ?,0.00,0.00,0)");
        $insR = $dbc->exec_statement($insQ,array($vid,$did,$name));
    
        echo "Department created";
    }

    function body_content(){
        global $FANNIE_URL;
        $vid = FormLib::get_form_value('vid');
        if ($vid === '')
            return "<i>Error: no vendor selected</i>";

        ob_start();
        ?>
        <div id="newdeptdiv">
        <a href="" onclick="$('#newform').show(); return false;">New vendor department</a>
        <div id="newform" style="display:none;">
        <p />
        <b>No.</b> <input type=text size=4 id=newno />
        &nbsp;&nbsp;&nbsp;
        <b>Name</b> <input type=text id=newname />
        <p />
        <input onclick="newdept();" type=submit value="Add department" />
        &nbsp;&nbsp;&nbsp;
        <a href="" onclick="$('#newform').hide(); return false;">Cancel</a>
        </div>
        </div>
        <hr />
        <div id="contentarea">
        <?php echo $this->vendorDeptDisplay($vid); ?>
        </div>
        <input type="hidden" id="vendorID" value="<?php echo $vid; ?>" />
        <input type="hidden" id="urlpath" value="<?php echo $FANNIE_URL; ?>" />
        <?php

        $this->add_script('vdepts.js');

        return ob_get_clean();
    }

    private function vendorDeptDisplay($id){
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $nameQ = $dbc->prepare_statement("SELECT vendorName FROM vendors WHERE vendorID=?");
        $nameR = $dbc->exec_statement($nameQ,array($id));
        $name = array_pop($dbc->fetch_row($nameR));

        $ret = "<b>Departments in $name</b><br />";
        $ret .= "<table cellspacing=0 cellpadding=4 border=1>";
        $ret .= "<tr><th>No.</th><th>Name</th><th>Margin</th>
            <th>&nbsp;</th><th>&nbsp;</th></tr>";

        $deptQ = $dbc->prepare_statement("SELECT * FROM vendorDepartments WHERE vendorID=?
            ORDER BY deptID");;
        $deptR = $dbc->exec_statement($deptQ,array($id));
        while($row = $dbc->fetch_row($deptR)){
            $ret .= sprintf("<tr>
                <td>%d</td>
                <td id=nametd%d>%s</td>
                <td id=margintd%d>%.2f%%</td>
                <td id=button%d><a href=\"\" onclick=\"edit(%d);return false;\">
                <img src=\"%s\" alt=\"Edit\" border=0 /></a></td>
                <td><a href=\"\" onclick=\"deleteCat(%d,'%s');return false\">
                <img src=\"%s\" alt=\"Delete\" border=0 /></a></td>
                </tr>",
                $row[1],$row[1],$row[2],$row[1],
                $row[3]*100,
                $row[1],$row[1],
                $FANNIE_URL.'src/img/buttons/b_edit.png',
                $row[1],$row[2],
                $FANNIE_URL.'src/img/buttons/b_drop.png');
        }
        $ret .= "</table>";
        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

?>
