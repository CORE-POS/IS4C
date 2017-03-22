<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op

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

/* SRPs are re-calculated based on the current margin or testing
   settings, which may have changed since the order was imported */

/* configuration for your module - Important */
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class RecalculateVendorSRPs extends FannieRESTfulPage
{
    protected $title = "Fannie - Vendor SRPs";
    protected $header = "Recalculate SRPs from Margins";

    public $description = '[Calculate Vendor SRPs] recalculates item SRPs based on vendor
    specific margin goals.';
    public $themed = true;

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;

    public function get_id_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $id = $this->id;

        $delQ = $dbc->prepare("DELETE FROM vendorSRPs WHERE vendorID=?");
        $delR = $dbc->execute($delQ,array($id));

        $query = '
            SELECT v.upc,
                v.sku,
                v.cost,
                CASE
                    WHEN a.margin IS NOT NULL THEN a.margin
                    WHEN b.margin IS NOT NULL THEN b.margin
                    ELSE 0 
                END AS margin,
                COALESCE(n.shippingMarkup, 0) as shipping,
                COALESCE(n.discountRate, 0) as discount
            FROM vendorItems as v 
                LEFT JOIN vendorDepartments AS a ON v.vendorID=a.vendorID AND v.vendorDept=a.deptID
                INNER JOIN vendors AS n ON v.vendorID=n.vendorID
                LEFT JOIN products as p ON v.upc=p.upc AND v.vendorID=p.default_vendor_id
                LEFT JOIN departments AS b ON p.department=b.dept_no
            WHERE v.vendorID=?
                AND (a.margin IS NOT NULL OR b.margin IS NOT NULL)';
        $fetchP = $dbc->prepare($query);
        $fetchR = $dbc->execute($fetchP, array($id));
        $upP = $dbc->prepare('
            UPDATE vendorItems
            SET srp=?,
                modified=' . $dbc->now() . '
            WHERE vendorID=?
                AND sku=?');
        $insP = false;
        if ($dbc->tableExists('vendorSRPs')) {
            $insP = $dbc->prepare('INSERT INTO vendorSRPs VALUES (?,?,?)');
        }
        $rounder = new \COREPOS\Fannie\API\item\PriceRounder();
        $dbc->startTransaction();
        while ($fetchW = $dbc->fetchRow($fetchR)) {
            // calculate a SRP from unit cost and desired margin
            $adj = \COREPOS\Fannie\API\item\Margin::adjustedCost($fetchW['cost'], $fetchW['discount'], $fetchW['shipping']);
            $srp = \COREPOS\Fannie\API\item\Margin::toPrice($adj, $fetchW['margin']);

            $srp = $rounder->round($srp);

            $upR = $dbc->execute($upP, array($srp, $id, $fetchW['sku']));
            if ($insP) {
                $insR = $dbc->execute($insP,array($id,$fetchW['upc'],$srp));
            }
        }
        $dbc->commitTransaction();

        $ret = "<b>SRPs have been updated</b><br />";
        $ret .= sprintf('<p>
            <a class="btn btn-default" href="index.php">Price Batch Tools</a>
            <a class="btn btn-default" 
            href="%s/item/vendors/VendorIndexPage.php?vid=%d">Vendor Settings &amp; Catalog</a>
            </p>',
            $this->config->get('URL'), $id);

        return $ret;
    }

    private function normalizePrice($price)
    {
        $int_price = floor($price * 100);
        while ($int_price % 10 != 5 && $int_price % 10 != 9) {
            $int_price++;
        }
        if ($int_price % 100 == 5 || $int_price % 100 == 9) {
            $int_price += 10;
        }

        return round($int_price/100.00, 2);
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = $dbc->prepare("SELECT vendorID,vendorName FROM vendors ORDER BY vendorName");
        $r = $dbc->execute($q);
        $opts = "";
        while($w = $dbc->fetch_row($r))
            $opts .= "<option value=$w[0]>$w[1]</option>";
        ob_start();
        ?>
        <form action=RecalculateVendorSRPs.php method=get>
        <p>
        <label>Recalculate SRPs from margins for which vendor?</label>
        <select id="vendor-id" name="id" class="form-control">
            <?php echo $opts; ?></select>
        <button type=submit class="btn btn-default">Recalculate</button>
        <button type="button" onclick="location='VendorPricingIndex.php';return false;"
            class="btn btn-default">Back</button>
        </p>
        </form>
        <?php
        $this->add_onload_command('$(\'#vendor-id\').focus();');

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Recalculate suggested retail prices for items from
            a given vendor. This takes place in three steps.
            <ul>
                <li>First, the vendor catalog unit costs are adjusted
                    upwards or downwards to account for volume discounts
                    or shipping markups. The adjusted cost is the catalog
                    cost times (1 - volume discount) times (1 + shipping markup).
                    If the cost is 1.99, volume discount is 15%, and shipping
                    markup is 5%, the adjusted cost is 1.99 * (1 - 0.15) *
                    (1 + 0.05) = 1.78.</li>
                <li>Second, the margin target is applied to the adjusted cost
                    to calculate an approximate SRP. In this case the adjusted 
                    cost is divided by (1 - margin). If the adjusted cost is 1.78
                    as before and the margin target is 35%, the appoximate
                    SRP is 1.78 / (1 - 0.35) = 2.74.
                    <ul>
                        <li>There are two tiers of margin targets. If the
                        item belongs to a vendor subcategory with a non-zero margin
                        target, that margin is used in this calculation. Otherwise
                        the POS department\'s margin target it used.
                        </li>
                    </ul>
                </li>
                <li>Finally, the price is rounded to conform with standards.
                    <em>This is not currently configurable but probably should be</em>.
                    In general prices round upward with exceptions around certain
                    key price points. Higher prices will make larger rounding jumps.
                    <ul>
                        <li>Prices between $0.00 and $0.99
                            <ul>
                                <li>Round upward to next x.29, x.39, x.49, x.69, x.79, x.89, x.99</li>
                                <li>Exceptions: none</li>
                            </ul>
                        </li>
                        <li>Prices between $1.00 and $2.99
                            <ul>
                                <li>Round upward to next x.19, x.39, x.49, x.69, x.89, x.99</li>
                                <li>Exceptions: pricing ending in x.15 or less round down to
                                    the previous x.99</li>
                            </ul>
                        </li>
                        <li>Prices between $3.00 and $5.99
                            <ul>
                                <li>Round upward to next x.39, x.69, x.99</li>
                                <li>Exceptions: pricing ending in x.15 or less round down to
                                    the previous x.99</li>
                            </ul>
                        </li>
                        <li>Prices between $6.00 and $9.99
                            <ul>
                                <li>Round upward to next x.69, x.99</li>
                                <li>Exceptions: pricing ending in x.29 or less round down to
                                    the previous x.99</li>
                            </ul>
                        </li>
                        <li>Prices $10.00 and higher
                            <ul>
                                <li>Round upward to next x.99</li>
                                <li>Exceptions: Prices including a zero always round down.
                                    For example, 30.99 rounds down to 29.99 where as
                                    31.01 rounds up to 31.99.</li>
                            </ul>
                        </li>
                    </ul>
                </li>
                </ul>
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }
}

FannieDispatch::conditionalExec();

