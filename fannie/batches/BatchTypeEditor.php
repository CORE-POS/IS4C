<?php
/*******************************************************************************

    Copyright 2011,2013 Whole Foods Co-op

    This file is part of CORE-POS.

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
include(dirname(__FILE__). '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BatchTypeEditor extends FanniePage {

    private $price_methods = array(
        0 => "None (Change regular price)",
        1 => "Sale for Everyone",
        2 => "Sale for Members"
    );

    protected $title = 'Fannie - Batch Module';
    protected $header = 'Sales Batches';

    public $description = '[Batch Type Editor] manages different kinds of batches.';
    public $themed = true;

    function preprocess()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $json = array('error'=>'');
        if (FormLib::get_form_value('saveDesc') !== ''){
            $q = $dbc->prepare_statement("UPDATE batchType
                SET typeDesc=? WHERE batchTypeID=?");
            $r = $dbc->exec_statement($q,array(
                FormLib::get_form_value('saveDesc'),
                FormLib::get_form_value('bid')
            ));
            if ($r === false) {
                $json['error'] = 'Error saving sale type';
            }
            echo json_encode($json);

            return False; // ajax call
        }
        if (FormLib::get_form_value('saveType') !== ''){
            $q = $dbc->prepare_statement("UPDATE batchType
                SET discType=? WHERE batchTypeID=?");
            $r = $dbc->exec_statement($q,array(
                FormLib::get_form_value('saveType'),
                FormLib::get_form_value('bid')
            ));
            if ($r === false) {
                $json['error'] = 'Error saving description';
            }
            echo json_encode($json);

            return False; // ajax call
        }
        if (FormLib::get_form_value('addtype') !== ''){
            $p = $dbc->prepare_statement("SELECT MAX(batchTypeID) FROM batchType");
            $r = $dbc->exec_statement($p);
            $id = array_pop($dbc->fetch_row($r));
            $id = (empty($id)) ? 1 : $id + 1;

            $ins = $dbc->prepare_statement("INSERT INTO batchType (batchTypeID,typeDesc,discType)
                VALUES (?,'New Type',1)");
            $dbc->exec_statement($ins,array($id));
        }
        else if (FormLib::get_form_value('deltype') !== ''){
            $q = $dbc->prepare_statement("DELETE FROM batchType WHERE batchTypeID=?");
            $dbc->exec_statement($q,array(FormLib::get_form_value('bid')));
        }

        return True;
    }

    function javascript_content(){
        ob_start();
        ?>
function saveDesc(val,bid){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveDesc='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
function saveType(val,bid){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveType='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
        <?php
        return ob_get_clean();
    }

    function body_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = $dbc->prepare_statement("SELECT batchTypeID,typeDesc,discType FROM batchType ORDER BY batchTypeID");
        $r = $dbc->exec_statement($q);

        $ret = '<table class="table">';
        $ret .= '<tr><th>ID#</th><th>Description</th><th>Discount Type</th><th>&nbsp;</td></tr>';
        while($w = $dbc->fetch_row($r)){
            $ret .= sprintf('<tr><td>%d</td>
                <td><input type="text" class="form-control" onchange="saveDesc.call(this,this.value,%d)" value="%s" /></td>
                <td><select onchange="saveType.call(this, $(this).val(),%d);" class="form-control">',
                $w['batchTypeID'],$w['batchTypeID'],$w['typeDesc'],$w['batchTypeID']);
        $found = False;
        foreach($this->price_methods as $id=>$desc){
            if ($id == $w['discType']){
                $found = True;
                $ret .= sprintf('<option value="%d" selected>%d %s</option>',$id,$id,$desc);
            }
            else
                $ret .= sprintf('<option value="%d">%d %s</option>',$id,$id,$desc);
        }
        if (!$found)
            $ret .= sprintf('<option value="%d" selected>%d (Custom)</option>',$w['discType'],$w['discType']);
        $ret .= '</select></td>';
        $ret .= sprintf('<td><a href="BatchTypeEditor.php?deltype=yes&bid=%d"
                onclick="return confirm(\'Are you sure?\');">%s</a>
            </td></tr>',$w['batchTypeID'], \COREPOS\Fannie\API\lib\FannieUI::deleteIcon());
        }
        $ret .= '</table>';

        $ret .= '<p><button onclick="location=\'BatchTypeEditor.php?addtype=yes\';"
            class="btn btn-default">Create New Type</button></p>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Batch types control what kind of change a batch makes and
            may also be used for organization. Discount type controls the
            batch type\'s behavior. You may have multiple batch types with
            identical discount type for organizational purposes.</p>
            <p>Discount type zero is not a sale at all. Price change batches
            update items\' regular price. The biggest advantage of changing
            regular prices via batches is that shelf tags can be prepared
            ahead of time before the new prices are applied.</p>
            <p>Sale for Everyone causes items to ring up at the sale price
            for the duration of the batch.</p>
            <p>Sale for Members causes items to ring up at the sale price
            for the duration of the batch but only if the customer is a
            member. What price the item rings up at initially depends whether
            or not the member\'s number has been entered. Prices will be
            adjusted as needed if the member\'s number is entered after the
            item is scanned.</p>
            ';
    }
}

FannieDispatch::conditionalExec(false);

?>
