<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

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

class StaffArPayrollTask extends FannieTask
{
    public $name = 'Staff AR Deduction';

    public $description = 'Adds AR payments as scheduled by the 
StaffArPayrollDeduction plugin.';

    public $default_schedule = array(
        'min' => 5,
        'hour' => 0,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_AR_DEPARTMENTS, $FANNIE_TRANS_DB, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['StaffArPayrollDB']);

        $chkQ = 'SELECT staffArDateID FROM StaffArDates WHERE ' . $dbc->datediff($dbc->now(), 'tdate') . ' = 0 ';
        $chkR = $dbc->query($chkQ);
        if ($dbc->num_rows($chkR) == 0) {
            // not scheduled for today
            return true;
        } 

        /**
          Update plugin's table from legacy table, if present.
          Can go away once WFC transistions away from legacy
          table.
        */
        $legacy_table = $FANNIE_TRANS_DB . $dbc->sep() . 'staffAR';
        if ($dbc->tableExists($legacy_table)) {
            $query = 'SELECT cardNo, adjust FROM ' . $legacy_table;
            $result = $dbc->query($query);
            $cards = '';
            $args = array();
            while($row = $dbc->fetch_row($result)) {
                $model = new StaffArAccountsModel($dbc);
                $model->card_no($row['cardNo']);
                $model->nextPayment($row['adjust']);
                $model->save();
                $cards .= '?,';
                $args[] = $row['cardNo'];
            }

            // remove records that aren't in legacy table
            if (count($args) > 0) {
                $cards = substr($cards, 0, strlen($cards)-1);
                $query = "DELETE FROM StaffArAccounts WHERE card_no NOT IN ($cards)";
                $prep = $dbc->prepare($query);
                $dbc->execute($prep, $args);
            }
        } // end legacy table handling

        // build department list
        $ar_dept = 0;
        $ret = preg_match_all("/[0-9]+/",$FANNIE_AR_DEPARTMENTS,$depts);
        $depts = array_pop($depts);
        if (!is_array($depts) || count($depts) == 0) {
            $this->cronMsg('Could not locate any AR departments in Fannie configuration', FannieLogger::NOTICE);
            return false;
        } else {
            $ar_dept = $depts[0];
        }
        $dept_desc = '';
        $dept_model = new DepartmentsModel($dbc);
        $dept_model->whichDB($FANNIE_OP_DB);
        $dept_model->dept_no($ar_dept);
        if ($dept_model->load()) {
            $dept_desc = $dept_model->dept_name();
        }

        $dtrans = $FANNIE_TRANS_DB . $dbc->sep() . 'dtransactions'; 
        $emp = isset($FANNIE_PLUGIN_SETTINGS['StaffArPayrollEmpNo']) ? $FANNIE_PLUGIN_SETTINGS['StaffArPayrollEmpNo'] : 1001;
        $reg = isset($FANNIE_PLUGIN_SETTINGS['StaffArPayrollRegNo']) ? $FANNIE_PLUGIN_SETTINGS['StaffArPayrollRegNo'] : 20;
        $query = 'SELECT MAX(trans_no) as maxt 
                    FROM ' . $dtrans . '
                    WHERE emp_no=? AND register_no=?';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($emp, $reg));
        $trans_no = 1;
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            if ($row['maxt'] != '') {
                $trans_no = $row['maxt'] + 1;
            }
        }

        $model = new StaffArAccountsModel($dbc);
        foreach($model->find() as $obj) {
            if ($obj->nextPayment() == 0) {
                // no need to write empty records
                continue;
            }

            $record = DTrans::defaults();
            $record['emp_no'] = $emp;
            $record['register_no'] = $reg;
            $record['trans_no'] = $trans_no;
            $record['trans_id'] = 1;
            $record['trans_type'] = 'D';
            $record['card_no'] = $obj->card_no();
            $record['department'] = $ar_dept;
            $record['description'] = $dept_desc;
            $record['upc'] = sprintf('%.2fDP%d', $obj->nextPayment(), $ar_dept);
            $record['total'] = sprintf('%.2f', $obj->nextPayment());
            $record['unitPrice'] = sprintf('%.2f', $obj->nextPayment());
            $record['regPrice'] = sprintf('%.2f', $obj->nextPayment());
        
            $p = DTrans::parameterize($record, 'datetime', date("'Y-m-d 23:59:59'", strtotime('yesterday')));
            $query = "INSERT INTO {$dtrans} ({$p['columnString']}) VALUES ({$p['valueString']})";
            $prep = $dbc->prepare($query);
            $write = $dbc->execute($prep, $p['arguments']);
            if ($write === false) {
                $this->cronMsg('Error making staff AR deduction for #' . $obj->card_no(), FannieLogger::ERROR);
            }

            $trans_no++;
        }
    }
}

