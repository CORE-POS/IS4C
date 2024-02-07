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
use \COREPOS\Fannie\API\item\Margin;

/* configuration for your module - Important */
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
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
                a.margin,
                COALESCE(n.shippingMarkup, 0) as shipping,
                COALESCE(n.discountRate, 0) as discount
            FROM vendorItems as v 
                LEFT JOIN vendorDepartments AS a ON v.vendorID=a.vendorID AND v.vendorDept=a.deptID
                INNER JOIN vendors AS n ON v.vendorID=n.vendorID
            WHERE v.vendorID=?';
        $dbc2 = FannieDB::getReadOnly($this->config->get('OP_DB'));
        $fetchP = $dbc2->prepare($query);
        $fetchR = $dbc2->execute($fetchP, array($id));
        $prodP = $dbc2->prepare("SELECT p.upc, p.cost, b.margin, c.margin AS specificMargin
            FROM products as p 
                LEFT JOIN departments AS b ON p.department=b.dept_no
                LEFT JOIN VendorSpecificMargins AS c ON c.vendorID=p.default_vendor_id AND p.department=c.deptID
            WHERE p.default_vendor_id=?");
        $prodR = $dbc2->execute($prodP, array($id));
        $prodData = array();
        while ($row = $dbc2->fetchRow($prodR)) {
            $prodData[$row['upc']] = array(
                'cost' => $row['cost'],
                'margin' => $row['margin'],
                'specific' => $row['specificMargin'],
            );
        }
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
        $upcs = array();
        $dbc->startTransaction();
        while ($fetchW = $dbc2->fetchRow($fetchR)) {
            if (isset($upcs[$fetchW['upc']])) {
                continue;
            }
            $upcs[$fetchW['upc']] = true;
            // products data overrides, if available
            $upc = $fetchW['upc'];
            if (isset($prodData[$upc])) {
                if ($prodData[$upc]['cost']) {
                    $fetchW['cost'] = $prodData[$upc]['cost'];
                }
                if ($prodData[$upc]['specific']) {
                    $fetchW['margin'] = $prodData[$upc]['specific'];
                } elseif ($prodData[$upc]['margin']) {
                    $fetchW['margin'] = $prodData[$upc]['margin'];
                }
            }
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


        $ret = "<div><b>SRPs have been updated</b></div>";
        if ($this->config->get('COOP_ID') == 'WFC_Duluth' && !in_array($id, array(1,2))) {
            list($found, $vendorName) = $this->checkPriceChanges();
            if ($found == 0) {
                $ret .= sprintf("
                    <div>%d possible price changes found for <strong>%s</strong>
                    </div>",
                    $found, $vendorName);
            } else {
                $ret .= sprintf("
                    <div>%d possible price changes found for <strong>%s</strong>
                    </div><div>Please run the Vendor Pricing Batch Page.</div>",
                    $found, $vendorName);
            }
        }

        $ret .= sprintf('<p>
            <a class="btn btn-default" href="index.php">Price Batch Tools</a>
            <a class="btn btn-default" 
            href="%s/item/vendors/VendorIndexPage.php?vid=%d">Vendor Settings &amp; Catalog</a>
            </p>',
            $this->config->get('URL'), $id);

        return $ret;
    }

    private function checkPriceChanges()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $marginCase = '
            CASE
                WHEN g.margin IS NOT NULL AND g.margin <> 0 THEN g.margin
                WHEN s.margin IS NOT NULL AND s.margin <> 0 THEN s.margin
                ELSE d.margin
            END';
        $costSQL = Margin::adjustedCostSQL('p.cost', 'b.discountRate', 'b.shippingMarkup');
        $marginSQL = Margin::toMarginSQL($costSQL, 'p.normal_price');
        $srpSQL = Margin::toPriceSQL($costSQL, $marginCase);
        $vendorID = FormLib::get('id');

        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($vendorID);
        $vendor->load();

        $query = "SELECT p.upc,
            p.description,
            p.brand,
            p.cost,
            b.shippingMarkup,
            b.discountRate,
            p.normal_price,
            " . Margin::toMarginSQL($costSQL, 'p.normal_price') . " AS current_margin,
            " . Margin::toMarginSQL($costSQL, 'v.srp') . " AS desired_margin,
            " . $costSQL . " AS adjusted_cost,
            v.srp,
            " . $srpSQL . " AS rawSRP,
            v.vendorDept,
            p.price_rule_id AS variable_pricing,
            prt.priceRuleTypeID,
            prt.description AS prtDesc,
            " . $marginCase . " AS margin,
            CASE WHEN a.sku IS NULL THEN 0 ELSE 1 END as alias,
            CASE WHEN l.upc IS NULL THEN 0 ELSE 1 END AS likecoded,
            c.difference,
            c.date,
            r.reviewed,
            v.sku
            FROM products AS p
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN VendorAliases AS a ON p.upc=a.upc AND p.default_vendor_id=a.vendorID
                INNER JOIN vendors as b ON v.vendorID=b.vendorID
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN vendorDepartments AS s ON v.vendorDept=s.deptID AND v.vendorID=s.vendorID
                LEFT JOIN VendorSpecificMargins AS g ON p.department=g.deptID AND v.vendorID=g.vendorID
                LEFT JOIN upcLike AS l ON v.upc=l.upc 
                LEFT JOIN productCostChanges AS c ON p.upc=c.upc 
                LEFT JOIN prodReview AS r ON p.upc=r.upc
                LEFT JOIN PriceRules AS pr ON p.price_rule_id=pr.priceRuleID
                LEFT JOIN PriceRuleTypes AS prt ON pr.priceRuleTypeID=prt.priceRuleTypeID
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
        WHERE v.cost > 0
            AND v.vendorID = ?
            AND m.superID IN (1, 3, 4, 5, 8, 9, 13, 17, 18)
            AND p.normal_price <> v.srp 
        ";

        $args = array($vendorID);
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $query .= ' AND p.store_id=? ';
            $args[] = $this->config->get('STORE_ID');
        }
        $query .= ' AND p.upc IN (SELECT upc FROM products WHERE inUse = 1) ';
        $query .= " ORDER BY p.upc";

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
        $upcs = array();
        while ($row = $dbc->fetchRow($result)) {
            $upcs[$row['upc']] = 1;
        }

        return array(count($upcs), $vendor->vendorName());
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
        <div class="form-group">
            <label>Recalculate SRPs from margins for which vendor?</label>
            <select id="vendor-id" name="id" class="form-control">
                <?php echo $opts; ?></select>
        </div>
        <div class="form-group">
            <button type=submit class="btn btn-default">Recalculate</button>
        </div>
        <div class="form-group">
            <button type="button" onclick="location='VendorPricingIndex.php';return false;"
                class="btn btn-default">Back to Vendor Pricing</button>
        </div>
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

