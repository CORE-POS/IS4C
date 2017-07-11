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

class DeleteVendorPage extends FannieRESTfulPage
{
    protected $title = "Fannie : Delete Vendor";
    protected $header = "Delete Vendors";
    public $discoverable = false;

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');
    private $error = '';

    protected function post_id_handler()
    {
        try {
            $newID = $this->form->newID;
            $dbc = $this->connection;
            if ($newID === $this->id) {
                $this->error = 'Cannot move items to same vendor';
                return true;
            }
            $vendor = new VendorsModel($dbc);
            $vendor->vendorID($newID);
            if (!$vendor->load()) {
                $this->error = 'Cannot move items to non-existant vendor';
                return true;
            }
            $prep = array();
            $prep[] = $dbc->prepare('UPDATE products SET default_vendor_id=? WHERE default_vendor_id=?');
            $prep[] = $dbc->prepare('UPDATE vendorItems SET vendorID=? WHERE vendorID=?');
            $prep[] = $dbc->prepare('UPDATE vendorDepartments SET vendorID=? WHERE vendorID=?');
            $prep[] = $dbc->prepare('UPDATE vendorSKUtoPLU SET vendorID=? WHERE vendorID=?');
            $prep[] = $dbc->prepare('UPDATE VendorBreakdowns SET vendorID=? WHERE vendorID=?');
            $prep[] = $dbc->prepare('UPDATE VendorAliases SET vendorID=? WHERE vendorID=?');
            foreach ($prep as $p) {
                $dbc->execute($p, array($newID, $this->id));
            }
            $del = $dbc->prepare('DELETE FROM vendors WHERE vendorID=?');
            $dbc->execute($del, array($this->id));
            return 'VendorIndexPage.php?vid=' . $newID;
        } catch (Exception $ex) {
            $this->error = 'Missing new vendor';
        }

        return true;
    }

    protected function post_id_view()
    {
        return '<div class="alert alert-danger">' . $this->error . '</div>'
            . $this->get_id_view(); 
    }

    protected function get_id_view()
    {
        $vendor = new VendorsModel($this->connection);
        $vendor->vendorID($this->id);
        $vendor->load();
        $name = $vendor->vendorName();
        $vendor->reset();
        $this->addOnloadCommand("\$('input[name=newID]').focus();\n");

        return '<form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />
            <div class="form-group">
                <strong>Deleting Vendor ' . $name . '</strong>
            </div>
            <div class="form-group">
                <label>Move existing items to</label>
                <select name="newID" class="form-control" required">'
                . $vendor->toOptions() . '
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-danger btn-core">Delete</button>
                <a href="VendorIndexPage.php?vid=' . $this->id . '" class="btn btn-default btn-reset">Back</a>
            </div>
            </form>';
    }

    protected function get_handler()
    {
        return 'VendorIndexPage.php';
    }

    public function helpContent()
    {
        return '<p>When deleting a vendor, all existing items from that vendor
            must be re-assigned to a new vendor. This is best suited for merging
            catalogs when two different names have been created for a single
            vendor. If items are no longer being ordered from the vendor it makes
            more sense to flag the vendor as inactive.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertEquals('VendorIndexPage.php', $this->get_handler());
        $this->id = 1;
        $this->error = 'error';
        $phpunit->assertNotEquals(0, strlen($this->post_id_view()));
        $phpunit->assertEquals(true, $this->post_id_handler());
        $form = new COREPOS\common\mvc\ValueContainer();
        $this->form->newID = 1;
        $phpunit->assertEquals(true, $this->post_id_handler());
    }
}

FannieDispatch::conditionalExec();

