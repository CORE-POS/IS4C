<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SyncIndexPage extends FanniePage {

    protected $title = "Fannie : Sync Lane";
    protected $header = "Sync Lane Operational Tables";
    public $themed = true;

    function body_content(){
        ob_start();
        ?>
        <form action="TableSyncPage.php" method="get" class="form">
        <p>
            <label>Table</label><select name="tablename" class="form-control">
            <option value="">Select a table</option>
            <option value="products">Products</option>
            <option value="productUser">Extra Product Info</option>
            <option value="custdata">Members</option>
            <option value="memberCards">Membership Cards</option>
            <option value="employees"><?php echo _('Cashiers'); ?></option>
            <option value="departments">Departments</option>
            <option value="tenders">Tenders</option>
            <option value="houseCoupons">House Coupons</option>
            <option value="houseVirtualCoupons">House Virtual Coupons</option>
            <option value="houseCouponItems">House Coupon Items</option>
            <option value="memtype">Member Types</option>
            </select><br /><br />

            <label>Other table</label><input type="text" name="othertable" class="form-control" />
        </p>
        <p>
            <button type="submit" value="Send Data" class="btn btn-default">Send Data</button>
        </p>
        </form>
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Send data from the server to the lanes. The sync operations
            discards current lane-side data and completely replaces it with
            the server\'s data.</p>
            <p>The <em>Table</em> dropdown contains the most common options
            but any other operational table can be sent using the <emOther table</em>
            field.</p>
            ';
    }
}

FannieDispatch::conditionalExec(false);

