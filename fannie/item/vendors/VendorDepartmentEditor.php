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
    public $themed = true;

    function preprocess()
    {

        $ajax = FormLib::get_form_value('action');
        if ($ajax !== ''){
            $this->ajax_callbacks($ajax);
            return False;
        }       

        return True;
    }

    function ajax_callbacks($action)
    {
        global $FANNIE_OP_DB;
        $json = array('error' => 0);
        switch($action){
        case 'createCat':
            $json = $this->createVendorDepartment(
                FormLib::get_form_value('vid'),
                FormLib::get_form_value('deptID'),
                FormLib::get_form_value('name')
            );
            echo json_encode($json);
            break;
        case 'deleteCat':
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $q = $dbc->prepare_statement("DELETE FROM vendorDepartments
                WHERE vendorID=? AND deptID=?");
            $gone = $dbc->exec_statement($q,
                    array(FormLib::get_form_value('vid'),
                        FormLib::get_form_value('deptID')) );
            if (!$gone) {
                $json['error'] = 'Error deleting #' + FormLib::get('deptID');
            }
            echo json_encode($json);
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
            $saved = $dbc->exec_statement($q,$args);
            if ($saved === false) {
                $json['error'] = 'Error saving #' . FormLib::get('deptID');
            }
            echo json_encode($json);
            break;
        default:
            echo 'bad request';
            break;
        }
    }

    private function createVendorDepartment($vid,$did,$name)
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $json = array('error' => 0);
        
        $chkQ = $dbc->prepare_statement("SELECT * FROM vendorDepartments WHERE
                vendorID=? AND deptID=?");
        $chkR = $dbc->exec_statement($chkQ,array($vid,$did));
        if ($dbc->num_rows($chkR) > 0) {
            $json['error'] = "Number #$did is already in use!";
        }

        $insQ = $dbc->prepare_statement("INSERT INTO vendorDepartments (vendorID,deptID,
            name,margin,testing,posDeptID) VALUES (?,?,
            ?,0.00,0.00,0)");
        $insR = $dbc->exec_statement($insQ,array($vid,$did,$name));

        if ($insR) {
            $new_row .= sprintf("<tr id=\"row-%d\">
                <td>%d</td>
                <td id=nametd%d>%s</td>
                <td id=margintd%d>%.2f%%</td>
                <td id=button%d>
                    <a href=\"\" onclick=\"edit(%d);return false;\"
                        class=\"edit-link\">%s<a>
                    <a href=\"\" onclick=\"save(%d);return false;\"
                        class=\"save-link collapse\">%s</a>
                </td>
                <td><a href=\"\" onclick=\"deleteCat(%d,'%s');return false\">%s</a></td>
                </tr>",
                $did,
                $did, $did,
                $name, $did,
                0,
                $did, $did,
                \COREPOS\Fannie\API\lib\FannieUI::editIcon(),
                $did,
                \COREPOS\Fannie\API\lib\FannieUI::saveIcon(),
                $did, $name,
                \COREPOS\Fannie\API\lib\FannieUI::deleteIcon());
            $json['row'] = $new_row;
        } else {
            $json['error'] = 'Error creating new department';
        }
    
        return $json;
    }

    function body_content()
    {
        global $FANNIE_URL;
        $vid = FormLib::get_form_value('vid');
        if ($vid === '') {
            return '<div class="alert alert-danger">Error: no vendor selected</div>';
        }

        ob_start();
        ?>
        <div id="alert-area"></div>
        <div id="newdeptdiv">
        <a href="" onclick="$('#newform').show();$('#newno').focus(); return false;">New vendor department</a>
        <div id="newform" class="collapse">
            <div class="form-group">
                <label>No.</label>
                <input type=number id=newno class="form-control" />
                <label>Name</label>
                <input type=text id=newname class="form-control" />
            </div>
            <button onclick="newdept();return false;" type=submit 
                class="btn btn-default">Add department</button>
            &nbsp;&nbsp;&nbsp;
            <a href="" onclick="$('#newform').hide(); return false;">Cancel</a>
        </div>
        </div>
        <hr />
        <div id="contentarea">
        <?php echo $this->vendorDeptDisplay($vid); ?>
        </div>
        <input type="hidden" id="vendorID" value="<?php echo $vid; ?>" />
        <?php

        $this->add_script('vdepts.js');

        return ob_get_clean();
    }

    private function vendorDeptDisplay($id)
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $nameQ = $dbc->prepare_statement("SELECT vendorName FROM vendors WHERE vendorID=?");
        $nameR = $dbc->exec_statement($nameQ,array($id));
        $name = array_pop($dbc->fetch_row($nameR));

        $ret = "<strong>Departments in $name</strong><br />";
        $ret .= "<table class=\"table\">"; 
        $ret .= "<tr><th>No.</th><th>Name</th><th>Margin</th>
            <th>&nbsp;</th><th>&nbsp;</th></tr>";

        $deptQ = $dbc->prepare_statement("
            SELECT vendorID,
                deptID,
                name,
                margin,
                testing,
                posDeptID
            FROM vendorDepartments
            WHERE vendorID=?
            ORDER BY deptID");
        $deptR = $dbc->exec_statement($deptQ,array($id));
        while($row = $dbc->fetch_row($deptR)){
            $ret .= sprintf("<tr id=\"row-%d\">
                <td>%d</td>
                <td id=nametd%d>%s</td>
                <td id=margintd%d>%.2f%%</td>
                <td id=button%d>
                    <a href=\"\" onclick=\"edit(%d);return false;\"
                        class=\"edit-link\">%s</a>
                    <a href=\"\" onclick=\"save(%d);return false;\"
                        class=\"save-link collapse\">%s</a>
                </td>
                <td><a href=\"\" onclick=\"deleteCat(%d,'%s');return false\">%s</a></td>
                </tr>",
                $row['deptID'],
                $row['deptID'],$row['deptID'],
                $row['name'],$row['deptID'],
                $row['margin']*100,
                $row['deptID'],$row['deptID'],
                \COREPOS\Fannie\API\lib\FannieUI::editIcon(),
                $row['deptID'],
                \COREPOS\Fannie\API\lib\FannieUI::saveIcon(),
                $row['deptID'],$row['name'],
                \COREPOS\Fannie\API\lib\FannieUI::deleteIcon());
        }
        $ret .= "</table>";

        $ret .= '<p><a href="VendorIndexPage.php?vid=' . $id . '" class="btn btn-default">Home</a></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

?>
