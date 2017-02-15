<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

class EditVendorItems extends FannieRESTfulPage 
{
    protected $title = "Fannie : Edit Vendor Catalog";
    protected $header = "Edit Vendor Catalog";

    public $description = '[Edit Vendor Items] edits items in the vendor\'s catalog. Must be
    accessed via the Vendor Editor.';
    public $themed = true;

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    public function preprocess()
    {
        $this->__routes[] = 'post<id><sku><field><value>';
        $this->__routes[] = 'post<id><oldSKU><newSKU>';

        return parent::preprocess();
    }

    public function post_id_oldSKU_newSKU_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $query = $dbc->prepare('
            UPDATE vendorItems
            SET sku=?
            WHERE sku=?
                AND vendorID=?');
        $result = $dbc->execute($query, array($this->newSKU, $this->oldSKU, $this->id));

        $ret = array('error'=>0);
        if ($result) {
            $ret['sku'] = $this->newSKU;
        } else {
            $ret['error_msg'] = 'Failed to update SKU';
        }
        echo json_encode($ret);

        return false;
    }

    public function post_id_sku_field_value_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $item = new VendorItemsModel($dbc);
        $item->vendorID($this->id);
        $item->sku($this->sku);

        $ret = array('error'=>0);
        if ($this->field === 'brand') {
            $item->brand($this->value);
        } elseif ($this->field === 'description') {
            $item->description($this->value);
        } elseif ($this->field === 'unitSize') {
            $item->size($this->value);
        } elseif ($this->field === 'caseQty') {
            $item->units($this->value);
        } elseif ($this->field === 'cost') {
            $item->cost($this->value);
        } else {
            $ret['error'] = 1;
            $ret['error_msg'] = 'Unknown field';
        }

        if ($ret['error'] == 0) {
            $saved = $item->save();
            if (!$saved) {
                $ret['error'] = 1;
                $ret['error_msg'] = 'Save failed';
            } elseif ($this->field === 'cost') {
                /**
                  If cost was updated, update the corresponding
                  product cost
                */
                $prodP = $dbc->prepare('
                    SELECT p.upc
                    FROM products AS p
                        INNER JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    WHERE v.vendorID=?
                        AND v.sku=?');
                $prodR = $dbc->execute($prodP, array($this->id, $this->sku));
                $model = new ProductsModel($dbc);
                while ($prodW = $dbc->fetch_row($prodR)) {
                    $model->reset();
                    $model->upc($prodW['upc']);
                    foreach ($model->find('store_id') as $obj) {
                        $obj->cost($this->value);
                        $obj->save();
                    }
                }
            }
        }

        echo json_encode($ret);

        return false;
    }

    public function get_id_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $items = new VendorItemsModel($dbc);
        $items->vendorID($this->id);

        $ret = '<div id="alert-area"></div>
            <table class="table tablesorter">';
        $ret .= '<thead><tr>
            <th>Vendor SKU</th>
            <th>Our UPC</th>
            <th>Brand</th>
            <th>Description</th>
            <th>Unit Size</th>
            <th>Case Qty</th>
            <th>Unit Cost</th>
            <th></th>
            </tr></thead>';
        $ret .= '<tbody>';
        foreach ($items->find() as $item) {
            $ret .= sprintf('<tr>
                <input type="hidden" class="original-sku" name="originalSKU" value="%s" />
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm sku-field" name="editSKU" value="%s" size="13" /></td>
                <td><a href="../ItemEditorPage.php?searchupc=%s" target="_edit%s">%s</a></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="brand" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="description" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="unitSize" value="%s" size="5" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="caseQty" value="%.2f" size="5" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm costing" name="unitCost" value="%.2f" size="5" /></td>
                    
                </td><td><button href="" class="btn btn-danger btn-xs"
                    onclick="deleteVendorItem(this, \'%s\', \'%s\', \'%s\', \'%s\'); return false;"><span class="glyphicon glyphicon-trash" title="Delete"></span></button></td>
                    
                </tr>',
                $item->sku(),
                $item->sku(),
                $item->sku(),
                $item->upc(),
                $item->upc(),
                $item->upc(),
                $item->brand(),
                $item->brand(),
                $item->description(),
                $item->description(),
                $item->size(),
                $item->size(),
                $item->units(),
                $item->units(),
                $item->cost(),
                $item->cost(),
                $item->upc(),
                $item->sku(),
                $item->description(),
                $item->vendorID()
                
            );
        }
        $ret .= '</tbody></table>';
        $ret .= '<input type="hidden" id="vendor-id" value="' . $this->id . '" />';
        $ret .= '<p><a href="VendorIndexPage.php?vid=' . $this->id . '" class="btn btn-default">Home</a></p>';
        //$this->add_onload_command('deleteVendorItem(\'button\',1234,4567);');
        $this->add_onload_command('itemEditing();');
        $this->add_script('../../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addCssFile('../../src/javascript/tablesorter/themes/blue/style.css');
        $this->add_onload_command("\$('.tablesorter').tablesorter({sortList:[[0,0]], widgets:['zebra']});");
        

        return $ret;
    }

    public function javascriptContent()
    {
        ob_start();
        ?>
function itemEditing()
{
    $('.sku-field').change(function(){
        var current_sku = $(this).closest('tr').find('.original-sku').val();
        $(this).prev('span.collapse').html($(this).val());
        $('.tablesorter').trigger('update');
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: 'id='+$('#vendor-id').val()+'&oldSKU='+current_sku+'&newSKU='+$(this).val()
        }).done(function(resp) {
            if (!resp.error) {
                elem.closest('tr').find('.original-sku').val(resp.sku);
                showBootstrapPopover(elem, orig, '');
            } else {
                showBootstrapAlert('#alert-area', 'danger', resp.error_msg);
            }
        });
    });
    $('.editable').change(function(){
        var current_sku = $(this).closest('tr').find('.original-sku').val();
        $(this).prev('span.collapse').html($(this).val());
        $('.tablesorter').trigger('update');
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: 'id='+$('#vendor-id').val()+'&sku='+current_sku+'&field='+$(this).attr('name')+'&value='+$(this).val()
        }).done(function(resp) {
            if (resp.error) {
                showBootstrapAlert('#alert-area', 'danger', resp.error_msg);
            } else {
                showBootstrapPopover(elem, orig, '');
            }
        });
    });
    $('.costing').change(function(){
        var current_sku = $(this).closest('tr').find('.original-sku').val();
        var elem = $(this);
        var orig = this.defaultValue;
        var newCost = $(this).val();
        if (newCost.indexOf('/') > -1) {
            var divisors = newCost.split('/');
            var base = Number(divisors[0]);
            for (var d = 1; d < divisors.length; d++) {
                base /= Number(divisors[d]);
            }
            newCost = Math.round(base*100) / 100.0;
        }
        $(this).val(newCost);
        $(this).prev('span.collapse').html($(this).val());
        $('.tablesorter').trigger('update');
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: 'id='+$('#vendor-id').val()+'&sku='+current_sku+'&field=cost&value='+newCost
        }).done(function(resp) {
            if (resp.error) {
                showBootstrapAlert('#alert-area', 'danger', resp.error_msg);
            } else {
                showBootstrapPopover(elem, orig, '');
            }
        });
    });
}
function deleteVendorItem(button, upc, sku, desc, vendorID)
{
    //alert('this button is doing something');
    var r = confirm("Are you sure you want to delete: \n\nUPC\n" +upc+"\n\nSKU\n"+sku+"\n\nDescription\n"+desc);
    if (r == true) {
        $.ajax({
            url: 'DeleteVendorItems.php',
            data: 'upc='+upc+'&sku='+sku+'&vendorID='+vendorID,
            success: function(response)
            {
                $(button).closest('tr').hide();
            }
        });
    } else {
        resp = "Item not deleted.";
    }
}
        <?php
        return ob_get_clean();
    }
    
    public function helpContent()
    {
        return '<p>
            Edit invidual records in the vendor item catalog
            in a grid layout. Saving is instantanoues on each
            field and includes a small popup notification. The
            editor only works with catalogs containing less than
            a thousand items.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertNotEquals(0, strlen($this->javascriptContent()));
    }
}

FannieDispatch::conditionalExec();


