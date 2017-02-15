<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PatronageGrossPurchases extends FannieRESTfulPage
{
    protected $title = "Fannie :: Patronage Tools";
    protected $header = "Calculate Gross Purchases &amp; Discounts";
    public $description = '[Patronage Totals] calculates total purchases and discounts for work-in-progress patronage data.';
    public $themed = true;

    protected function get_id_handler()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if ($dbc->table_exists("patronage_workingcopy")) {
            $drop = $dbc->prepare("DROP TABLE patronage_workingcopy");
            $dbc->execute($drop);
        }
        if (!function_exists('duplicate_structure')) {
            include_once(dirname(__FILE__) . '/../../install/db.php');
        }
        $create = $dbc->prepare(
            duplicate_structure(strtoupper($dbc->dbmsName()),'patronage','patronage_workingcopy')
        );
        $dbc->execute($create);

        $insQ = sprintf("INSERT INTO patronage_workingcopy
            (cardno, purchase, discounts, rewards, net_purch, tot_pat, cash_pat, equit_pat, FY)
            SELECT card_no,
            SUM(CASE WHEN trans_type IN ('I','D') THEN total ELSE 0 END),
            SUM(CASE WHEN trans_type IN ('S') then total ELSE 0 END),
            0,0,0,0,0,?
            FROM %s%sdlog_patronage as d
            GROUP BY card_no",$FANNIE_TRANS_DB,$dbc->sep());
        $prep = $dbc->prepare($insQ);
        $worked = $dbc->execute($prep,array($this->id));
    
        if ($worked) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Purchases and Discounts calculated');\n");
        } else {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'Error running calculations');\n");
        }

        return true;
    }

    protected function get_id_view()
    {
        return '
            <div id="alert-area"></div>
            <p>
            <button type="button" class="btn btn-default"
                onclick="location=\'index.php\'; return false;">Patronage Menu</button>
            <button type="button" class="btn btn-default"
                onclick="location=\'PatronageGrossPurchases.php\'; return false;">Re-Calculate</button>
            </p>';
    }

    public function get_view()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        ob_start();
        ?>
        <div class="well">
        Step two: calculate totals sales and percent discounts per member for the year
        </div>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
        <label>Fiscal Year</label>
        <select name="id" class="form-control">
        <?php
        $q = $dbc->prepare("
            SELECT min_year,
                max_year 
            FROM $FANNIE_TRANS_DB".$dbc->sep()."dlog_patronage");
        $r = $dbc->execute($q);
        $w = $dbc->fetch_row($r);
        printf('<option>%d</option>',$w[0]);
        printf('<option>%d</option>',$w[1]);
        ?>
        </select>
        <p>
            <button type="submit" class="btn btn-default">Calculate Purchases</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>The next step is to pull some initial totals
            from the compiled member transaction data. Gross purchases
            is calculated as the sum of all transactions with type I
            or type D (excepting super department zero transactions that
            were excluded in the previous step). Discounts is calculated
            as the sum of all transactions with type S. This is the percent
            discount that may be assigned to a member\'s account.</p>';
    }
}

FannieDispatch::conditionalExec();

