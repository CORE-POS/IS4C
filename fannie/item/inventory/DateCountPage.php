<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DateCountPage extends FannieRESTfulPage
{
    protected $header = 'Inventory Counts';
    protected $title = 'Inventory Counts';
    protected $must_authenticate = true;
    protected $enable_linea = true;
    public $description = '[Re-Date Counts] adjusts the count date for an item or vendor';

    public function preprocess()
    {
        $this->addRoute('get<vendor>');
        $this->addRoute('post<vendor>');
        return parent::preprocess();
    }

    public function post_vendor_handler()
    {
        try {
            $date = $this->form->date;
            $upP = $this->connection->prepare('
                UPDATE InventoryCounts AS i
                    INNER JOIN products AS p ON p.upc=i.upc AND p.store_id=i.storeID
                SET i.countDate=?
                WHERE p.default_vendor_id=?
                    AND i.mostRecent=1
                    AND i.storeID=?
            ');
            $upR = $this->connection->execute($upP, array($date, $this->vendor,$this->form->store));
        } catch (Exception $ex) {
        }

        return 'InvCountPage.php?vendor=' . $this->vendor . '&store=' . $this->form->store;
    }

    public function get_vendor_view()
    {
        $vendor = new VendorsModel($this->connection);
        $vendor->vendorID($this->vendor);
        if (!$vendor->load()) {
            return '<div class="alert alert-danger">Unknown vendor</div>';
        }
        $stores = FormLib::storePicker('store', false);

        $ret = '<form method="post">
            <div class="form-group">
                <label>Vendor</label>
                ' . $vendor->vendorName() . '
                <input type="hidden" name="vendor" value="' . $vendor->vendorID() . '" />
            </div>
            <div class="form-group">
                <label>Set Count Date to</label>
                <input type="text" name="date" class="form-control date-field" />
            </div>
            <div class="form-group">
                <label>Store</label>
                ' . $stores['html'] . '
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Update</button>
            </div>
            </form>';

        return $ret;
    }

    public function post_id_handler()
    {
        $upc = BarcodeLib::padUPC($this->id);
        try {
            $date = $this->form->date;
            $store = $this->form->store;
            $upP = $this->connection->prepare('
                UPDATE InventoryCounts
                SET countDate=?
                WHERE upc=?
                    AND mostRecent=1
                    AND storeID=?');
            $upR = $this->connection->execute($upP, array($date, $upc, $store));
        } catch (Exception $ex) {
        }

        return 'InvCountPage.php?id=' . $upc;
    }

    public function get_id_view()
    {
        $stores = FormLib::storePicker('store', false);
        $store = FormLib::get('store', 1);
        $upc = BarcodeLib::padUPC($this->id);
        $inv = new InventoryCountsModel($this->connection);
        $inv->upc($upc);
        $inv->mostRecent(1);
        $inv->setFindLimit(1);
        $inv->storeID($store);
        if (count($inv->find()) == 0) {
            return '<div class="alert alert-danger">No count for item ' . $upc . '</div>';
        }

        $ret = '<form method="post">
            <div class="form-group">
                <label>UPC</label>
                ' . $upc . '
                <input type="hidden" name="id" value="' . $upc . '" />
            </div>
            <div class="form-group">
                <label>Set Count Date to</label>
                <input type="text" name="date" class="form-control date-field" />
            </div>
            <div class="form-group">
                <label>Store</label>
                ' . $stores['html'] . '
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Update</button>
            </div>
            </form>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $this->id = '4011';
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->store=1;
        $form->date = date('Y-m-d');
        $this->setForm($form);
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertNotEquals(0, strlen($this->post_id_handler()));
        $this->vendor = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_vendor_view()));
        $phpunit->assertNotEquals(0, strlen($this->post_vendor_handler()));
    }

    public function helpContent()
    {
        return '<p>On-hand inventory is based on activity since the most recent count.
            Enter a new date and time to adjust <em>when</em> the count occurred. If for
            example counts were collected yesterday on paper but entered into the computer
            today those counts should likely be backdated to yesterday.</p>';
    }
}

FannieDispatch::conditionalExec();

