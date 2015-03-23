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


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    
    12Mar2013 Andy Theuninck Use API classes
     7Sep2012 Eric Lee Display vendorID in select.
                       Display both "Select" and "New" options.

*/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class VendorIndexPage extends FanniePage {

    protected $title = "Fannie : Manage Vendors";
    protected $header = "Manage Vendors";

    public $themed = true;

    public $description = '[Vendor Editor] creates or update information about vendors.';

    function preprocess()
    {

        $ajax = FormLib::get_form_value('action');
        if ($ajax !== ''){
            $this->ajax_callbacks($ajax);
            return False;
        }       

        $auto = FormLib::get('autoAdd', 0);
        if ($auto == 1) {
            $vendor = FormLib::get('vid');
            $this->autoPopulate($vendor);
            header('Location: VendorIndexPage.php?vid=' . $vendor);

            return false;
        }

        return True;
    }

    function ajax_callbacks($action)
    {
        global $FANNIE_OP_DB;
        switch($action){
        case 'vendorDisplay':
            $this->getVendorInfo(FormLib::get_form_value('vid',0)); 
            break;
        case 'newVendor':
            $this->newVendor(FormLib::get_form_value('name',''));
            break;
        case 'saveDelivery':
            $delivery = new VendorDeliveriesModel(FannieDB::get($FANNIE_OP_DB));
            $delivery->vendorID(FormLib::get('vID', 0));
            $delivery->frequency(FormLib::get('frequency', 'weekly'));
            $delivery->regular( FormLib::get('regular') ? 1 : 0 );
            $delivery->sunday( FormLib::get('sunday') ? 1 : 0 );
            $delivery->monday( FormLib::get('monday') ? 1 : 0 );
            $delivery->tuesday( FormLib::get('tuesday') ? 1 : 0 );
            $delivery->wednesday( FormLib::get('wednesday') ? 1 : 0 );
            $delivery->thursday( FormLib::get('thursday') ? 1 : 0 );
            $delivery->friday( FormLib::get('friday') ? 1 : 0 );
            $delivery->saturday( FormLib::get('saturday') ? 1 : 0 );
            $ret = array();
            if ($delivery->regular()) {
                $delivery->autoNext();
                $ts1 = strtotime($delivery->nextDelivery());
                $ts2 = strtotime($delivery->nextNextDelivery());
                if ($ts1 !== false && $ts2 !== false) {
                    $ret['next'] = date('D, M jS', $ts1);
                    $ret['nextNext'] = date('D, M jS', $ts2);
                }
            }
            $delivery->save();
            echo json_encode($ret);
            break;
        case 'saveContactInfo':
            $id = FormLib::get_form_value('vendorID','');
            if ($id === ''){
                echo 'Bad request';
                break;
            }
            $web = FormLib::get_form_value('website');
            if (!empty($web) && substr(strtolower($web),0,4) !== "http") {
                $web = 'http://'.$web;
            }
            $localID = FormLib::get_form_value('local-origin-id', 0);
            /** 29Oct2014 Andy
                Widen vendors table so additional vendorContacts
                table can be deprecated in the future
            */
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $vModel = new VendorsModel($dbc);
            $vModel->vendorID($id);
            $vModel->shippingMarkup(FormLib::get('shipping', 0) / 100.00);
            $vModel->phone(FormLib::get_form_value('phone'));
            $vModel->fax(FormLib::get_form_value('fax'));
            $vModel->email(FormLib::get_form_value('email'));
            $vModel->website($web);
            $vModel->notes(FormLib::get_form_value('notes'));
            $vModel->localOriginID($localID);
            $success = $vModel->save();

            $vcModel = new VendorContactModel($dbc);
            $vcModel->vendorID($id);
            $vcModel->phone(FormLib::get_form_value('phone'));
            $vcModel->fax(FormLib::get_form_value('fax'));
            $vcModel->email(FormLib::get_form_value('email'));
            $vcModel->website($web);
            $vcModel->notes(FormLib::get_form_value('notes'));
            $success = $vcModel->save();
            $ret = array('error'=>0, 'msg'=>'');
            if ($success) {
                $ret['msg'] = 'Saved vendor information';
            } else {
                $ret['msg'] = 'Error saving vendor information';
                $ret['error'] = 1;
            }
            echo json_encode($ret);
            break;
        default:
            echo 'Bad request'; 
            break;
        }
    }

    private function autoPopulate($vendorID)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $query = '
            SELECT p.upc,
                p.upc AS sku,
                p.brand,
                p.description,
                p.size,
                p.unitofmeasure,
                p.cost,
                0.00 AS saleCost,
                0 AS vendorDept
            FROM products AS p
                INNER JOIN vendors AS v ON p.default_vendor_id=v.vendorID
            WHERE v.vendorID=?
                AND p.upc NOT IN (
                    SELECT upc FROM vendorItems WHERE vendorID=?
                ) AND p.upc NOT IN (
                    SELECT upc FROM vendorSKUtoPLU WHERE vendorID=?
                )';
        $prep = $dbc->prepare($query);
        $args = array($vendorID, $vendorID, $vendorID);
        $result = $dbc->execute($prep, $args);
        $item = new VendorItemsModel($dbc);
        while ($row = $dbc->fetch_row($result)) {
            $item->vendorID($vendorID);
            $item->upc($row['upc']);
            $item->sku($row['sku']);
            $item->brand($row['brand']);
            $item->description($row['description']);
            $item->units(1);
            $item->size($row['size'] . $row['unitofmeasure']);
            $item->cost($row['cost']);
            $item->saleCost(0);
            $item->vendorDept(0);
            $item->save();
        }
    }

    private function getVendorInfo($id)
    {
        global $FANNIE_OP_DB,$FANNIE_ROOT;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = "";

        $nameQ = $dbc->prepare_statement("SELECT vendorName FROM vendors WHERE vendorID=?");
        $nameR = $dbc->exec_statement($nameQ,array($id));
        $model = new VendorsModel($dbc);
        $model->vendorID($id);
        $model->load();
        $ret .= '<div>';
        $ret .= "<b>Id</b>: $id &nbsp; <b>Name</b>: " . $model->vendorName();
        $ret .= '</div>';

        $itemQ = $dbc->prepare_statement("SELECT COUNT(*) FROM vendorItems WHERE vendorID=?");
        $itemR = $dbc->exec_statement($itemQ,array($id));
        $num = 0;
        if ($itemR && $row = $dbc->fetch_row($itemR)) {
            $num = $row[0];
        }
        $ret .= '<p>';
        if ($num == 0)
            $ret .= "This vendor contains 0 items";
        else {
            $ret .= "This vendor contains $num items";
            $ret .= "<br />";
            $ret .= "<a href=\"BrowseVendorItems.php?vid=$id\">Browse vendor catalog</a>";  
            if ($num <= 750) {
                $ret .= "<br />";
                $ret .= "<a href=\"EditVendorItems.php?id=$id\">Edit vendor catalog</a>";  
            }
        }
        $ret .= "<br />";
        $ret .= "<a href=\"DefaultUploadPage.php?vid=$id\">Upload new vendor catalog</a>";
        $ret .= "<br />";
        $ret .= "<a href=\"VendorIndexPage.php?vid=$id&autoAdd=1\">Add existing items to catalog</a>";
        $ret .= "<br />";
        $ret .= "<a href=\"UploadPluMapPage.php?vid=$id\">Upload PLU/SKU mapping</a>";
        $ret .= "<br />";
        $ret .= "<a href=\"SkuMapPage.php?id=$id\">View or Edit PLU/SKU mapping</a>";
        $ret .= "<br />";
        $ret .= "<a href=\"UnitBreakdownPage.php?id=$id\">View or Edit Breakdown mapping</a>";
        $ret .= "</p>";

        $itemQ = $dbc->prepare_statement("SELECT COUNT(*) FROM vendorDepartments WHERE vendorID=?");
        $itemR = $dbc->exec_statement($itemQ,array($id));
        $num = 0;
        if ($itemR && $row = $dbc->fetch_row($itemR)) {
            $num = $row[0];
        }
        $ret .= '<p>';
        if ($num == 0)
            $ret .= "<a href=\"VendorDepartmentEditor.php?vid=$id\">This vendor's items are not yet arranged into departments</a>";
        else {
            $ret .= "This vendor's items are divided into ";
            $ret .= $num." departments";
            $ret .= "<br />";
            $ret .= "<a href=\"VendorDepartmentEditor.php?vid=$id\">Display/Edit vendor departments</a>";
        }
        $ret .= '</p>';

        $vcModel = new VendorContactModel($dbc);
        $vcModel->vendorID($id);
        $vcModel->load();
        $ret .= '<p><div class="form-alerts"></div>';
        $ret .= '<form role="form" class="form-horizontal" onsubmit="saveVC(' . $id . '); return false;" id="vcForm">';
        $ret .= '<div class="form-group">
            <label for="vc-shipping" class="control-label col-sm-1">Shipping Markup</label>
            <div class="col-sm-10">
                <div class="input-group">
                    <input type="text" id="vc-shipping" name="shipping" 
                        class="form-control" value="' . $model->shippingMarkup() * 100 . '" />
                    <span class="input-group-addon">%</span>
                </div>
            </div>
        </div>';
        $ret .= '<div class="form-group">
            <label for="vcPhone" class="control-label col-sm-1">Phone</label>
            <div class="col-sm-10">
            <input type="tel" class="form-control" id="vcPhone" name="phone" value="' . $vcModel->phone() . '" />
            </div>
            </div>';
        $ret .= '<div class="form-group">
            <label for="vcFax" class="control-label col-sm-1">Fax</label>
            <div class="col-sm-10">
            <input type="text" id="vcFax" class="form-control" name="fax" value="' . $vcModel->fax() . '" />
            </div>
            </div>';
        $ret .= '<div class="form-group">
            <label for="vcEmail" class="control-label col-sm-1">Email</label>
            <div class="col-sm-10">
            <input type="text" class="form-control" id="vcEmail" name="email" value="' . $vcModel->email() . '" />
            </div>
            </div>';
        $ret .= '<div class="form-group">
            <label for="vcWebsite" class="control-label col-sm-1">Website</label>
            <div class="col-sm-10">
            <input type="text" class="form-control" id="vcWebsite" name="website" value="' . $vcModel->website() . '" />
            </div>
            </div>';
        $ret .= '<div class="form-group">
            <label for="vc-local-id" class="control-label col-sm-1">Local</label>
            <div class="col-sm-10">
                <select class="form-control" name="local-origin-id">
                <option value="0">No</option>';
        $origins = new OriginsModel($dbc);
        $origins->local(1);
        foreach ($origins->find('shortName') as $origin) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($origin->originID() == $model->localOriginID() ? 'selected' : ''),
                $origin->originID(), $origin->shortName());
        }
        $ret .= '</select>
                </div>
            </div>';
        $ret .= '<div class="form-group">
            <label for="vcNotes" class="control-label col-sm-1">Ordering Notes</label>
            <div class="col-sm-10">
            <textarea class="form-control" rows="5" id="vcNotes">' . $vcModel->notes() . '</textarea>
            </div>
            </div>';
        $ret .= '<button type="submit" class="btn btn-default">Save Vendor Info</button>';
        $ret .= '</form></p>';

        $delivery = new VendorDeliveriesModel($dbc);
        $delivery->vendorID($id);
        $delivery->load();
        $ret .= '<p class="form-inline form-group"><label class="control-label" for="deliverySelect">Delivery Schedule</label>: ';
        $ret .= '<select class="delivery form-control" name="frequency" id="deliverySelect"><option>Weekly</option></select>';
        $ret .= ' <label for="regular" class="control-label">Regular</label>: <input type="checkbox" class="delivery"
                    name="regular" id="regular" ' . ($delivery->regular() ? 'checked' : '') . ' />';
        
        $dt = mktime(0, 0, 0, 6, 15, 2014); // date doesn't matter; just need a sunday
        $labels = '';
        $checks = '';
        for ($i=0; $i<7; $i++) {
            $func = strtolower(date('l', $dt));
            $labels .= '<th><label for="' . $func . '">' . date('D', $dt) . '</label></th>'; 
            $checks .= '<td><input type="checkbox" id="' . $func . '" name="' . $func . '"
                        ' . ($delivery->$func() ? 'checked' : '') . ' class="delivery" /></td>';
            $dt = mktime(0, 0, 0, date('n', $dt), date('j', $dt)+1, date('Y', $dt));
        }
        $ret .= '<table class="table"><tr>' . $labels . '</tr><tr>' . $checks . '</tr></table>';
        $ret .= 'Next 2 deliveries: '
                . '<span id="nextDelivery">' . date('D, M jS', strtotime($delivery->nextDelivery())) . '</span>'
                . ' and '
                . '<span id="nextNextDelivery">' . date('D, M jS', strtotime($delivery->nextNextDelivery())) . '</span>';
        $ret .= '</p>';

        echo $ret;
    }

    private function newVendor($name){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $id = 1;    
        $p = $dbc->prepare_statement("SELECT max(vendorID) FROM vendors");
        $rp = $dbc->exec_statement($p);
        $rw = $dbc->fetch_row($rp);
        if ($rw[0] != "")
            $id = $rw[0]+1;

        $model = new VendorsModel($dbc);
        $model->vendorID($id);
        $model->vendorName($name);
        $model->save();

        echo $id;
    }

    function body_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vendors = "<option value=\"\">Select a vendor...</option>";
        $vendors .= "<option value=\"new\">New vendor...</option>";
        $q = $dbc->prepare_statement("SELECT * FROM vendors ORDER BY vendorName");
        $rp = $dbc->exec_statement($q);
        $vid = FormLib::get_form_value('vid');
        while($rw = $dbc->fetch_row($rp)){
            if ($vid !== '' && $vid == $rw[0])
                $vendors .= "<option selected value=$rw[0]>$rw[1]</option>";
            else
                $vendors .= "<option value=$rw[0]>$rw[1]</option>";
        }
        ob_start();
        ?>
        <p id="vendorarea">
        <select onchange="vendorchange();" id=vendorselect class="form-control">
        <?php echo $vendors; ?>
        </select>
        </p>
        <p id="contentarea">
        </p>
        <?php

        $this->add_script('index.js');
        $this->add_onload_command('vendorchange();');

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Vendors are the entities the store purchases its 
            products from. The most important data associated with
            a vendor is their catalog of items. A product that the store
            sells may correspond to one or more items in one or more
            catalogs - i.e. the item may be available from more than
            one vendor and/or may be availalbe in more than one case
            size. Keeping vendor catalogs up to date with accurate 
            costs helps manage retail pricing and margin.</p>
            <p>PLU/SKU mapping is for resolving situations where the
            store and the vendor use different UPCs. This is often
            the case with items sold in bulk using a PLU.</p>
            <p>Vendor Departments are optional. If the vendor\'s
            catalog is divided into vendor-specific departments,
            custom margin targeets can be set for those sets of
            items.</p>
            <p>Contact Info and Delivery Schedule are wholly optional.
            Jot down whatever is useful.</p>';
    }
}

FannieDispatch::conditionalExec(false);

?>
