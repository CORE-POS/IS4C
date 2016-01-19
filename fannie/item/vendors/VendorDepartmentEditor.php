<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class VendorDepartmentEditor extends FanniePage {
    protected $title = "Fannie : Manage Vendors";
    protected $header = "Manage Vendors";

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    public $description = '[Vendor Subcategories] manages vendor-specific subcategories.
    These are distinct from POS departments.';

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
                FormLib::get('vid'),
                FormLib::get('deptID'),
                FormLib::get('name')
            );
            echo json_encode($json);
            break;
        case 'deleteCat':
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $delP = $dbc->prepare("DELETE FROM vendorDepartments
                WHERE vendorID=? AND deptID=?");
            $gone = $dbc->execute($delP, array(FormLib::get('vid'), FormLib::get('deptID')));
            if (!$gone) {
                $json['error'] = 'Error deleting #' + FormLib::get('deptID');
            }
            echo json_encode($json);
            break;
        case 'updateCat':
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $dept = new VendorDepartmentsModel($dbc);
            $dept->vendorID(FormLib::get('vid'));
            $dept->deptID(FormLib::get('deptID'));
            $dept->name(FormLib::get('name'));
            $dept->margin(trim(FormLib::get('margin',0), '%') / 100.00);
            $dept->posDeptID(FormLib::get('pos'));
            $saved = $dept->save();
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
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $json = array('error' => 0);
        
        $chkQ = $dbc->prepare("SELECT * FROM vendorDepartments WHERE
                vendorID=? AND deptID=?");
        $chkR = $dbc->execute($chkQ,array($vid,$did));
        if ($dbc->numRows($chkR) > 0) {
            $json['error'] = "Number #$did is already in use!";
        }

        $insQ = $dbc->prepare("INSERT INTO vendorDepartments (vendorID,deptID,
            name,margin,testing,posDeptID) VALUES (?,?,
            ?,0.00,0.00,0)");
        $insR = $dbc->execute($insQ,array($vid,$did,$name));

        if ($insR) {
            $row = array('deptID'=>$did, 'name'=>$name, 'margin'=>0, 'posDeptID'=>0);
            $json['row'] = $this->rowToTable($did, $row);
        } else {
            $json['error'] = 'Error creating new subcategory';
        }
    
        return $json;
    }

    function body_content()
    {
        $vid = FormLib::get_form_value('vid');
        if ($vid === '') {
            return '<div class="alert alert-danger">Error: no vendor selected</div>';
        }

        ob_start();
        ?>
        <div id="alert-area"></div>
        <div id="newdeptdiv">
        <a href="" onclick="$('#newform').show();$('#newno').focus(); return false;">New vendor subcategory</a>
        <div id="newform" class="collapse">
            <div class="form-group">
                <label>No.</label>
                <input type=number id=newno class="form-control" />
                <label>Name</label>
                <input type=text id=newname class="form-control" />
            </div>
            <button onclick="vDept.newdept();return false;" type=submit 
                class="btn btn-default">Add subcategory</button>
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
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $v = new VendorsModel($dbc);
        $v->vendorID($id);
        $v->load();
        $name = $v->vendorName();

        $ret = "<strong>Subcategories in $name</strong><br />";
        $ret .= "<table class=\"table\">"; 
        $ret .= "<tr><th>No.</th><th>Name</th><th>Margin</th><th>POS Dept#</th>
            <th>&nbsp;</th><th>&nbsp;</th></tr>";

        $deptQ = $dbc->prepare("
            SELECT d.vendorID,
                deptID,
                name,
                margin,
                testing,
                posDeptID
            FROM vendorDepartments AS d
            WHERE d.vendorID=?
            ORDER BY deptID");
        $deptR = $dbc->execute($deptQ,array($id));
        while ($row = $dbc->fetchRow($deptR)){
            $ret .= $this->rowToTable($id, $row);
        }
        $ret .= "</table>";

        $ret .= '<p><a href="VendorIndexPage.php?vid=' . $id . '" class="btn btn-default">Home</a></p>';

        return $ret;
    }

    private function rowToTable($id, $row)
    {
        return sprintf("<tr id=\"row-%d\">
            <td>%d</td>
            <td id=nametd%d>%s</td>
            <td id=margintd%d>%.2f%%</td>
            <td id=posdepttd%d>%d</td>
            <td id=button%d>
                <a href=\"\" onclick=\"vDept.edit(%d);return false;\"
                    class=\"edit-link\">%s</a>
                <a href=\"\" onclick=\"vDept.save(%d);return false;\"
                    class=\"save-link collapse\">%s</a>
            </td>
            <td><a href=\"\" onclick=\"vDept.deleteCat(%d,'%s');return false\">%s</a></td>
            <td><a href=\"../../reports/VendorCategory/VendorCategoryReport.php?id=%d&category=%d\"
                title=\"View Items in this Category\">
                <span class=\"glyphicon glyphicon-th-list\"></span>
                </a></td>
            </tr>",
            $row['deptID'],
            $row['deptID'],
            $row['deptID'], $row['name'],
            $row['deptID'], $row['margin']*100,
            $row['deptID'], $row['posDeptID'],
            $row['deptID'],
            $row['deptID'],
            \COREPOS\Fannie\API\lib\FannieUI::editIcon(),
            $row['deptID'],
            \COREPOS\Fannie\API\lib\FannieUI::saveIcon(),
            $row['deptID'], $row['name'],
            \COREPOS\Fannie\API\lib\FannieUI::deleteIcon(),
            $id, $row['deptID'] 
        );
    }

    public function helpContent()
    {
        return '<p>
            Vendor subcategories are distinct from POS\' department
            hierarchy. The primary purpose for dividing vendor
            items into vendor-specific subcategories is that specialize
            margin targets can be assigned to each vendor subcategory.
            </p>
            <p>
            To simply set a vendor-specific margin, create a single
            vendor subcategory containing all the vendor\'s items.
            Depending how entries were created, this should be
            vendor subcategory #0 or #1.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $phpunit->assertNotEquals(0, strlen($this->vendorDeptDisplay(1)));
    }
}

FannieDispatch::conditionalExec();

