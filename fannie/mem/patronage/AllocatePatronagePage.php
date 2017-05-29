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

class AllocatePatronagePage extends FannieRESTfulPage
{
    protected $title = "Fannie :: Patronage Tools";
    protected $header = "Fannie :: Patronage Tools";
    public $description = '[Allocate Patronage] divies up calculated patronage amounts amongst
        qualifiying members';
    public $themed = true;

    public function helpContent()
    {
        return '<p>This tool takes the total net purchases for active
            owners in the draft patronage data and allocates the given
            amount to each owner based on their share of total net
            purchases. The allocation is split between a "paid" amount
            that is disbursed to owners and a "retained" amount that
            is kept in the owner\'s name.</p>
            <p>For example, if the total net purchases by all members is
            $100,000 and an individual member\'s net purchases were $1,000
            then 1% of allocated amount will be distributed to that member.
            That allocated amount is then split into paid &amp; retained
            portions.<p>
            <p>
            All the owners who spent money during the fiscal year may not
            still be active owners when patronage is distributed or may
            no longer be owners at all. There are currently three options
            to choose from:
                <ul>
                    <li><i>All Currently Active Owners</i> will allocate 
                        distributions for owner accounts that are in good
                        standing at the time of the distribution. Owners 
                        that spent money during the fiscal year but are 
                        inactive at the time of the distribution are excluded.
                    </li>
                    <li><i>All Owners Regardless of Current Status</i> will
                        allocate distributions for all owner accounts that
                        spent money during the fiscal year.
                    </li>
                    <li><i>All Non-Termed Owners</i> will allocate distributions
                        for all owner accounts that spent money during the fiscal
                        year <strong>except</strong> accounts that have been
                        formally closed.
                    </li>
                </ul>
            </p>';
    }

    public function post_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $amount = FormLib::get('amount');
        $paid = FormLib::get('paid') / 100.00;
        $retained = FormLib::get('retained') / 100.00;

        $owners = FormLib::get('owners');
        $typeClause = '';
        switch ($owners) {
            case 1:
            default:
                $typeClause = " c.Type='PC' ";
                break;
            case 2:
                $typeClause = " 1=1 ";
                break;
            case 3:
                $typeClause = " c.Type <> 'TERM' ";
                break;
        }

        $netQ = '
            SELECT SUM(p.net_purch) AS ttl
            FROM patronage_workingcopy AS p
                INNER JOIN custdata AS c ON p.cardno=c.CardNo AND c.personNum=1
            WHERE ' . $typeClause;
        $netR = $dbc->query($netQ);
        $netW = $dbc->fetch_row($netR);
        $purchases = $netW['ttl'];

        $personQ = '
            SELECT p.net_purch,
                c.cardno
            FROM patronage_workingcopy AS p
                INNER JOIN custdata AS c ON p.cardno=c.CardNo AND c.personNum=1
            WHERE ' . $typeClause;
        $personR = $dbc->query($personQ);
        $this->insertRecords($dbc, $personR, $purchases, $paid, $retained, $amount);

        $finishQ = '
            INSERT INTO patronage
            (cardno, purchase, discounts, rewards, net_purch, tot_pat, cash_pat, equit_pat, FY)
            SELECT 
                p.cardno, purchase, discounts, rewards, net_purch, tot_pat, cash_pat, equit_pat, FY
            FROM patronage_workingcopy AS p
                INNER JOIN custdata AS c ON p.cardno=c.CardNo AND c.personNum=1
            WHERE ' . $typeClause;
        $dbc->query($finishQ);

        return true;
    }

    private function insertRecords($dbc, $result, $purchases, $paid, $retained, $amount)
    {
        $assignP = $dbc->prepare('
            UPDATE patronage_workingcopy
            SET tot_pat=?,
                cash_pat=?,
                equit_pat=?
            WHERE cardno=?');
        while ($personW = $dbc->fetch_row($result)) {
            $share = $personW['net_purch'] / $purchases;
            $patronage = round($amount * $share, 2);
            $cash = round($patronage * $paid, 2);
            $equity = round($patronage * $retained, 2);

            $dbc->execute($assignP, array($patronage, $cash, $equity, $personW['cardno']));
        }
    }

    public function post_view()
    {
        return '<div class="alert alert-success">Patronage Allocated to Owners</div>';
    }

    public function get_view()
    {
        return '<form method="post">
            <div class="form-group">
                <label>Total Amount Allocated</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" class="form-control" name="amount" />
                </div>
            </div>
            <div class="form-group">
                <label>Percent Paid Out</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="paid" />
                    <span class="input-group-addon">%</span>
                </div>
            </div>
            <div class="form-group">
                <label>Percent Retained as Equity</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="retained" />
                    <span class="input-group-addon">%</span>
                </div>
            </div>
            <div class="form-group">
                <label>Include which Owners</label>
                <select name="owners" class="form-control">
                    <option value="1">All Currently Active Owners</option>
                    <option value="2">All Owners Regardless of Current Status</option>
                    <option value="3">All Non-Termed Owners</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Allocate</button>
            </div>
            </form>';
    }
}

FannieDispatch::conditionalExec();

