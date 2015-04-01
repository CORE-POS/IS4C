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

class RecalculateVendorSRPs extends FanniePage {
    protected $title = "Fannie - Vendor SRPs";
    protected $header = "Recalculate SRPs from Margins";

    public $description = '[Calculate Vendor SRPs] recalculates item SRPs based on vendor
    specific margin goals.';
    public $themed = true;

    private $mode = 'form';

    function preprocess(){
        if(FormLib::get_form_value('vendorID') !== '')
            $this->mode = 'results';
        return True;
    }

    function body_content(){
        if ($this->mode == 'form')
            return $this->form_content();
        else if ($this->mode == 'results')
            return $this->results_content();
    }

    function results_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $id = FormLib::get_form_value('vendorID',0);

        $delQ = $dbc->prepare_statement("DELETE FROM vendorSRPs WHERE vendorID=?");
        $delR = $dbc->exec_statement($delQ,array($id));

        $query = 'select v.upc,v.cost,
            case when d.margin is not null then d.margin
                 when m.margin is not null then m.margin
                 else 0 end as margin,
                n.shippingMarkup
            FROM vendorItems as v 
                left join vendorDepartments as d on v.vendorID=d.vendorID and v.vendorDept=d.deptID
                INNER JOIN vendors AS n ON v.vendorID=n.vendorID
            left join products as p
            on v.upc=p.upc ';
        $departments = $dbc->tableDefinition('departments');
        if (isset($departments['margin'])) {
            $query .= ' LEFT JOIN departments AS m
                        ON p.department = m.dept_no ';
        } else if ($dbc->tableExists('deptMargin')) {
            $query .= ' left join deptMargin as m
                        on p.department=m.dept_ID ';
        }
        $query .= ' where v.vendorID=?
            and (d.margin is not null or m.margin is not null)';
        $fetchP = $dbc->prepare($query);
        $fetchR = $dbc->exec_statement($fetchP, array($id));
        $insP = $dbc->prepare_statement('INSERT INTO vendorSRPs VALUES (?,?,?)');
        while ($fetchW = $dbc->fetch_array($fetchR)) {
            // calculate a SRP from unit cost and desired margin
            $srp = round($fetchW['cost'] / (1 - $fetchW['margin']),2);
            $srp *= (1+$fetchW['shippingMarkup']);

            $srp = $this->normalizePrice($srp);

            $insR = $dbc->exec_statement($insP,array($id,$fetchW['upc'],$srp));
        }

        $ret = "<b>SRPs have been updated</b><br />";
        $ret .= "<a href=index.php>Main Menu</a>";
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

    function form_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = $dbc->prepare_statement("SELECT vendorID,vendorName FROM vendors ORDER BY vendorName");
        $r = $dbc->exec_statement($q);
        $opts = "";
        while($w = $dbc->fetch_row($r))
            $opts .= "<option value=$w[0]>$w[1]</option>";
        ob_start();
        ?>
        <form action=RecalculateVendorSRPs.php method=get>
        <p>
        <label>Recalculate SRPs from margins for which vendor?</label>
        <select id="vendor-id" name=vendorID class="form-control">
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
            a given vendor. If margin targets have been assigned to
            vendor-specific departments, those margins are used. Otherwise
            POS departments\' margin targets are used.</p>';
    }
}

FannieDispatch::conditionalExec(false);

?>
