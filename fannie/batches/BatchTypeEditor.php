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
        2 => "Sale for Members",
        3 => 'Sliding % Off for Members',
        5 => 'Sliding $ Off for Members',
    );

    private $editor_uis = array(
        1 => 'Standard',
        2 => 'Paired Sale',
    );

    protected $title = 'Fannie - Batch Module';
    protected $header = 'Sales Batches';

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;

    public $description = '[Batch Type Editor] manages different kinds of batches.';
    public $themed = true;

    function preprocess()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $json = array('error'=>'');
        $model = new BatchTypeModel($dbc);
        if (FormLib::get_form_value('saveDesc') !== '') {
            $model->batchTypeID(FormLib::get('bid'));
            $model->typeDesc(FormLib::get('saveDesc'));
            if ($model->save() === false) {
                $json['error'] = 'Error saving description';
            }
            echo json_encode($json);

            return false; // ajax call
        }
        if (FormLib::get_form_value('saveType') !== '') {
            $model->batchTypeID(FormLib::get('bid'));
            $model->discType(FormLib::get('saveType'));
            if ($model->save() === false) {
                $json['error'] = 'Error saving sale type';
            }
            echo json_encode($json);

            return false; // ajax call
        }
        if (FormLib::get('saveDated') !== '') {
            $model->batchTypeID(FormLib::get('bid'));
            $model->datedSigns(FormLib::get('saveDated'));
            if ($model->save() === false) {
                $json['error'] = 'Error saving date setting';
            }
            echo json_encode($json);

            return false; // ajax call
        }
        if (FormLib::get('saveSO') !== '') {
            $model->batchTypeID(FormLib::get('bid'));
            $model->specialOrderEligible(FormLib::get('saveSO'));
            if ($model->save() === false) {
                $json['error'] = 'Error saving SO eligibility';
            }
            echo json_encode($json);

            return false; // ajax call
        }
        if (FormLib::get('saveUI') !== '') {
            $model->batchTypeID(FormLib::get('bid'));
            $model->editorUI(FormLib::get('saveUI'));
            if ($model->save() === false) {
                $json['error'] = 'Error saving UI setting';
            }
            echo json_encode($json);

            return false; // ajax call
        }
        if (FormLib::get_form_value('addtype') !== ''){
            $p = $dbc->prepare_statement("SELECT MAX(batchTypeID) FROM batchType");
            $r = $dbc->exec_statement($p);
            $id = array_pop($dbc->fetch_row($r));
            $id = (empty($id)) ? 1 : $id + 1;

            $ins = $dbc->prepare_statement("INSERT INTO batchType (batchTypeID,typeDesc,discType)
                VALUES (?,'New Type',1)");
            $dbc->exec_statement($ins,array($id));
        } elseif (FormLib::get_form_value('deltype') !== ''){
            $q = $dbc->prepare_statement("DELETE FROM batchType WHERE batchTypeID=?");
            $dbc->exec_statement($q,array(FormLib::get_form_value('bid')));
        }

        return true;
    }

    function javascript_content()
    {
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
function saveDated(bid){
    var elem = $(this);
    var val = 0;
    if ($(this).prop('checked')) {
        val = 1;
    }
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveDated='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
function saveSO(bid){
    var elem = $(this);
    var val = 0;
    if ($(this).prop('checked')) {
        val = 1;
    }
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveSO='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
function saveUI(val,bid){
    var elem = $(this);
    var orig = this.defaultValue;
    $.ajax({
        url: 'BatchTypeEditor.php',
        cache: false,
        type: 'post',
        data: 'saveUI='+val+'&bid='+bid,
        dataType: 'json',
        success: function(data){
            showBootstrapPopover(elem, orig, data.error);
        }
    });
}
        <?php
        return ob_get_clean();
    }

    function body_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new BatchTypeModel($dbc);

        $ret = '<table class="table">';
        $ret .= '<tr>
            <th>ID#</th>
            <th>Description</th>
            <th>Discount Type</th>
            <th>Dated Signs</th>
            <th title="Special Order Eligible">SO Eligible</th>
            <th>Editing Interface</th>
            <th>&nbsp;</td>
        </tr>';
        foreach ($model->find('batchTypeID') as $obj) {
            $ret .= sprintf('<tr>
                <td>%d</td>
                <td><input type="text" class="form-control" onchange="saveDesc.call(this,this.value,%d)" value="%s" /></td>
                <td><select onchange="saveType.call(this, $(this).val(),%d);" class="form-control">',
                $obj->batchTypeID(), $obj->batchTypeID(), $obj->typeDesc(), $obj->batchTypeID());
        $found = false;
        foreach ($this->price_methods as $id=>$desc) {
            if ($id == $obj->discType()) {
                $found = true;
                $ret .= sprintf('<option value="%d" selected>%d %s</option>',$id,$id,$desc);
            } else {
                $ret .= sprintf('<option value="%d">%d %s</option>',$id,$id,$desc);
            }
        }
        if (!$found)
            $ret .= sprintf('<option value="%d" selected>%d (Custom)</option>',$w['discType'],$w['discType']);
        $ret .= '</select></td>';
        $ret .= sprintf('<td align="center">
                    <input type="checkbox" %s onchange="saveDated.call(this, %d);" />
                    </td>',
                    ($obj->datedSigns() ? 'checked' : ''),
                    $obj->batchTypeID()
                );
        $ret .= sprintf('<td align="center">
                    <input type="checkbox" %s onchange="saveSO.call(this, %d);" />
                    </td>',
                    ($obj->specialOrderEligible() ? 'checked' : ''),
                    $obj->batchTypeID()
                );
        $ret .= sprintf('<td>
                    <select onchange="saveUI.call(this, $(this).val(),%d);" class="form-control">',
                    $obj->batchTypeID()
        );
        foreach ($this->editor_uis as $id => $desc) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                    ($id == $obj->editorUI() ? 'selected' : ''),
                    $id, $desc
            );
        }
        $ret .= '</select></td>';
        $ret .= sprintf('<td><a href="BatchTypeEditor.php?deltype=yes&bid=%d"
                class="btn btn-danger btn-sm"
                onclick="return confirm(\'Are you sure?\');">%s</a>
            </td></tr>',$obj->batchTypeID(), \COREPOS\Fannie\API\lib\FannieUI::deleteIcon());
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
            <p>
            <ul>
                <li>Discount type zero is not a sale at all. Price change batches
                update items\' regular price. The biggest advantage of changing
                regular prices via batches is that shelf tags can be prepared
                ahead of time before the new prices are applied.</li>
                <li>Sale for Everyone causes items to ring up at the sale price
                for the duration of the batch.</li>
                <li>Sale for Members causes items to ring up at the sale price
                for the duration of the batch but only if the customer is a
                member. What price the item rings up at initially depends whether
                or not the member\'s number has been entered. Prices will be
                adjusted as needed if the member\'s number is entered after the
                item is scanned.</li>
                <li>Sliding % off for Members is also a member-only sale but
                rather than ringing at a fixed price for the duration of the sale
                it takes a percentage off the retail price. This is used with
                price-volatile items like produce where retail pricing may change
                on a weekly or even daily basis.</li>
                <li>Sliding $ off for Members is much like Sliding % off. It
                reduces the retail price by a fixed amount instead of a percentage.
                The use case is similar. Choosing between the two sliding options
                is a matter of preference.</li>
            </ul>
            </p>
            <p><i>Date Signs</i> controls how POS-generated signs appear. By default,
            sale signs include the batch\'s start and end dates. If Date Signs is not
            enabled for a given batch type, signs will instead say <i>While supplies
            last</i>. This may be useful with a dedicated batch type for temporary
            overstock / inventory reduction sales.</p>
            <p><i>SO Eligible</i> controls how sale pricing interacts with special
            orders. If a sale type is special order eligible, customers who order
            items that are currently on sale will get the sale price.</p>
            <p><i>Editor Interface</i> is an option to provide alternate tools to
            create batches. Some more complex types of sales do not fit neatly
            into the standard batch editor. The current options are:
            <ul>
                <li>Standard. This is the default and handles the most straightforward
                sales. It works fine for putting an item on sale at a given price
                as well as with both sliding options.</li>
                <li>Paired Sale. This is used for sales where the customer must
                buy two <i>different</i> items to qualify for the deal. For example:
                save $1 when you buy a bag of chips and a jar of salsa.</li>
            </ul>
            </p>
            ';
    }
}

FannieDispatch::conditionalExec(false);

?>
