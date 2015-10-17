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

class AddPatronageEntryPage extends FannieRESTfulPage
{
    protected $header = 'Add Patronage Entry';
    protected $title = 'Add Patronage Entry';
    public $themed = true;
    public $description = '[Patronage Additional Entry] adds a patronage record for a given member
    that was omitted from the original allocation for the year.';

    public function post_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $prep = $dbc->prepare('
            INSERT INTO patronage
            (cardno, purchase, discounts, rewards, net_purch, tot_pat, cash_pat, equit_pat, FY)
            SELECT cardno,
                purchase,
                discounts,
                rewards,
                net_purch,
                ?,
                ?,
                ?,
                FY
            FROM patronage_workingcopy
            WHERE FY=?
                AND cardno=?');
        $args = array(
            FormLib::get('cash') + FormLib::get('retain'),
            FormLib::get('cash'),
            FormLib::get('retain'),
            FormLib::get('fy'),
            $this->id
        );
        $this->success = $dbc->execute($prep, $args);
        if ($this->success) {
            $pat = new PatronageModel($dbc);
            $pat->cardno($this->id);
            $pat->FY(FormLib::get('fy'));
            $number = GumLib::allocateCheck($patronage, false);
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $pat->check_number($number);
            $pat->save();
        }

        return true;
    }

    public function post_id_view()
    {
        if ($this->success) {
            return '<div class="alert alert-success">Created patronage entry for member #' . $this->id . '</div>';
        } else {
            return '<div class="alert alert-danger">Error creating patronage entry for member #' . $this->id . '</div>';
        }
    }

    public function get_id_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $patronage = new PatronageModel($dbc);
        $patronage->cardno($this->id);
        $patronage->FY(FormLib::get('fy'));
        $exists = $patronage->load();
        if ($exists) {
            return '<div class="alert alert-danger">Member ' . $this->id . ' already has
                a patronage entry for ' . FormLib::get('fy') . '</div>';
        }

        $workingP = $dbc->prepare('
            SELECT net_purch 
            FROM patronage_workingcopy
            WHERE FY=?
                AND cardno=?');
        $workingR = $dbc->execute($workingP, array(FormLib::get('fy'), $this->id));
        if (!$workingR || $dbc->num_rows($workingR) == 0) {
            return '<div class="alert alert-danger">Member ' . $this->id . ' has
                no patronage info for ' . FormLib::get('fy') . '</div>';
        }
        $workingW = $dbc->fetch_row($workingR);

        $infoP = $dbc->prepare('
            SELECT SUM(net_purch) AS spendingTotal,
                SUM(tot_pat) AS patronageTotal,
                SUM(cash_pat) AS cashTotal,
                SUM(equit_pat) AS retainedTotal
            FROM patronage AS p
            WHERE FY=?');
        $infoR = $dbc->execute($infoP, array(FormLib::get('fy')));
        $infoW = $dbc->fetch_row($infoR);

        $ratio = $workingW['net_purch'] / $infoW['spendingTotal'];
        $cash = round($infoW['cashTotal'] * $ratio, 2);
        $equity = round($infoW['retainedTotal'] * $ratio, 2);

        $ret = '<form method="post"> 
            <input type="hidden" name="id" value="' . $this->id . '" />
            <input type="hidden" name="fy" value="' . FormLib::get('fy') . '" />
            <div class="form-group">
                <label>Suggested Cash Portion</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" required class="form-control" name="cash" value="' . $cash . '" />
                </div>
            </div>
            <div class="form-group">
                <label>Suggested Retained Portion</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" required class="form-control" name="retain" value="' . $equity . '" />
                </div>
            </div>
            <div class="form-group">
                <button class="btn btn-default" type="submit">Submit
            </div>
            </form>';

            return $ret;
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $this->add_onload_command("\$('input.form-control').focus();\n");
        $ret = '<form method="get">
            <div class="form-group">
                <label>Member #</label>
                <input type="text" name="id" class="form-control" required />
            </div>
            <div class="form-group">
                <label>FY</label>
                <select class="form-control" name="fy">';
        $result = $dbc->query('SELECT FY FROM patronage GROUP BY FY ORDER BY FY DESC');
        while ($row = $dbc->fetch_row($result)) {
            $ret .= '<option>' . $row['FY'] . '</option>';
        }
        $ret .= '</select>
            </div>
            <div class="form-group">
                <button class="btn btn-default" type="submit">Submit
            </div>
            </form>';

        return $ret;
    }

    public function helpContent()
    {
        return '
            <p>
            Create a patronage entry for an member who
            was not originally issued one. The working
            copy calculation data must still exist in the
            system. This will only work for the fiscal
            year most recently calculated via CORE
            patronage tools.
            </p>';
    }
}

FannieDispatch::conditionalExec();

