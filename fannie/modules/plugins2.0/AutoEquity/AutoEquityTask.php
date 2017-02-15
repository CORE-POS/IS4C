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

use COREPOS\Fannie\API\data\SyncLanes;

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class AutoEquityTask extends FannieTask 
{
    public $name = 'Automatic Equity';

    public $description = 'Updates automatic equity payment setup for customers.';

    public $default_schedule = array(
        'min' => '*/15',
        'hour' => '7-22',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $changed = false;

        $date = date('Y-m-d', strtotime('7 days ago'));
        $dtrans = DTransactionModel::selectDTrans($date);
        $enrollP = $dbc->prepare("
            SELECT card_no
            FROM {$dtrans}
            WHERE trans_subtype='CM'
                AND description='AUTOMATIC EQUITY'
            ");
        $chkP = $dbc->prepare("SELECT * FROM AutomaticEquity WHERE cardNo=?");
        $enrollR = $dbc->execute($enrollP);
        while ($row = $dbc->fetchRow($enrollR)) {
            $chkR = $dbc->execute($chkP, array($row['card_no']));
            if ($dbc->numRows($chkR) == 0) {
                $model = new AutomaticEquityModel($dbc);
                $model->cardNo($row['card_no']);
                $model->department(991);
                $model->amount(5);
                $model->save();
                $changed = true;
            }
        }

        $eqP = $dbc->prepare("
            SELECT payments
            FROM " . $this->config->get('TRANS_DB') . $dbc->sep() . "equity_live_balance
            WHERE memnum=?");
        $auto = new AutomaticEquityModel($dbc);
        foreach ($auto->find() as $obj) {
            $current = $dbc->getValue($eqP, array($obj->cardNo()));
            if ($current >= 100) {
                $obj->delete();
                $changed = true;
            }
        }

        if ($changed) {
            SyncLanes::pushTable('AutomaticEquity');
        }
    }
}

