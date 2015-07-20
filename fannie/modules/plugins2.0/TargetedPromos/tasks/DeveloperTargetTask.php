<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class DeveloperTargetTask extends FannieTask
{

    public function run()
    {
        $settings = $this->config->PLUGIN_SETTINGS;
        $dbc = FannieDB::get($settings['TargetedPromosDB']);
        $warehouse = $settings['WarehouseDatabase'];

        $rules = new DeveloperRulesModel($dbc);
        $all_rules = $rules->find('developerRulesID', true);
        if (count($all_rules) == 0) {
            $rules->couponUPC('');
            $rules->save();
            $all_rules = $rules->find('developerRulesID', true);
        }
        $rules = $all_rules[0];

        $targets = new DeveloperTargetsModel($dbc);

        $shopping_period = new stdClass();
        $shopping_period->start = date('Ymd', strtotime($rules->examineMonths() . ' months ago'));
        $shopping_period->end = date('Ymd');

        $statusP = $dbc->prepare('
            SELECT Type,
                memType,
                staff
            FROM ' . $this->config->OP_DB . $dbc->sep() . 'custdata
            WHERE CardNo=?
                AND personNum=1
        ');

        $meminfoP = $dbc->prepare('
            SELECT card_no
            FROM ' . $this->config->OP_DB . $dbc->sep() . 'meminfo
            WHERE ads_OK = 1
                AND (zip LIKE \'55%\' OR zip LIKE \'56%\')
                AND card_no=?
        ');

        /**
          Lookup accounts with activity matching
          the developer criteria
        */
        $activityP = $dbc->prepare('
            SELECT s.card_no
            FROM ' . $warehouse . $dbc->sep() . 'sumMemSalesByDay AS s
            WHERE date_id BETWEEN ? AND ?
            GROUP BY s.card_no
            HAVING SUM(transCount) >= ?
                AND AVG(total) <= ?
                AND SUM(total) <= ?
        ');
        $args = array(
            $shopping_period->start,
            $shopping_period->end,
            $rules->minVisits(),
            $rules->minVisitAvg(),
            ($rules->examineMonths() * $rules->minMonthAvg()),
        );
        $activityR = $dbc->execute($activityP, $args);
        $def_target = new DefectorTargetsModel($dbc);
        while ($w = $dbc->fetchRow($activityR)) {
            /**
              Enforce rules about account status
            */
            $statusR = $dbc->execute($statusP, array($w['card_no']));
            if (!$statusR || $dbc->numRows($statusR) == 0) {
                continue;
            }
            $status = $dbc->fetchRow($statusR);
            if ($rules->memberOnly() && $status['Type'] != 'PC') {
                continue;
            }
            if (!$rules->includeStaff() && $status['staff']) {
                continue;
            }
            $infoP =  $dbc->execute($meminfoP, array($w['card_no']));
            if (!$infoP || $dbc->numRows($infoP) == 0) {
                continue;
            }

            /**
              If the account is already in the developer
              program, check whether any coupons have been
              redeemed. If so, decrement the issued counter
              and reset the redeemed counter. Issued is not
              reset so that "issued - redeemed" should always
              represent the number of outstanding coupons.

              Other accounts are simply added to the program.
            */
            $targets->reset();
            $targets->card_no($w['card_no']);
            $def_target->card_no($w['card_no']);
            if (!$def_target->load()) {
                echo "Adding: " . $w['card_no'] . "\n";
                $targets->card_no($w['card_no']);
                $targets->addedDate(date('Y-m-d H:i:s'));
                $targets->save();
            }
        }
    }
}

