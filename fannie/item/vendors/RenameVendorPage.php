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

class RenameVendorPage extends FannieRESTfulPage
{
    protected $title = "Fannie : Rename Vendor";
    protected $header = "Rename Vendors";
    public $description = '[Rename Vendor] is a tool to rename a vendor';

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');
    private $error = '';

    protected function post_id_handler()
    {
        try {
            $name = trim($this->form->name);
            if ($name === '') {
                throw new Exception('missing name');
            }
            $vendor = new VendorsModel($this->connection);
            $vendor->vendorName($name);
            if (count($vendor->find()) > 0) {
                $this->error = 'The name <em>' . $name . '</em> is already in use.';
            } else {
                $vendor->vendorID($this->id);
                if ($vendor->save()) {
                    return 'VendorIndexPage.php?vid=' . $this->id;
                } else {
                    $this->error = 'Error saving new name';
                }
            }
        } catch (Exception $ex) {
            $this->error = 'No name specified';
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
        $this->addOnloadCommand("\$('input[name=name]').focus();\n");

        return '<form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />
            <div class="form-group">
                <strong>Renaming Vendor ' . $vendor->vendorName() . '</strong>
            </div>
            <div class="form-group">
                <label>New Name</label>
                <input type="text" name="name" class="form-control" required />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Rename</button>
                <a href="VendorIndexPage.php?vid=' . $this->id . '" class="btn btn-default btn-reset">Back</a>
            </div>
            </form>';
    }

    protected function get_handler()
    {
        return 'VendorIndexPage.php';
    }

    public function helpText()
    {
        return '<p>Enter a new name for the vendor. Vendor names are required
            to be unique so you cannot use the same name as an existing vendor.</p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertEquals('VendorIndexPage.php', $this->get_handler());
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $this->error = 'error';
        $phpunit->assertNotEquals(0, strlen($this->post_id_view()));
        $phpunit->assertEquals(true, $this->post_id_handler());
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->name = '';
        $this->setForm($form);
        $phpunit->assertEquals(true, $this->post_id_handler());
        $form->name = 'Not a real vendor';
        $this->setForm($form);
        $phpunit->assertNotEquals(true, $this->post_id_handler());
    }

    public function helpContent()
    {
        return '<p>Enter a new name for the vendor. Duplicates are not allowed.</p>';
    }
}

FannieDispatch::conditionalExec();

