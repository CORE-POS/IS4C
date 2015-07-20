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

class DefectorTargetTask extends FannieTask
{

    public function run()
    {
        $settings = $this->config->PLUGIN_SETTINGS;
        $dbc = FannieDB::get($settings['TargetedPromosDB']);
        $warehouse = $settings['WarehouseDatabase'];

        $rules = new DefectorRulesModel($dbc);
        $all_rules = $rules->find('defectorRulesID', true);
        if (count($all_rules) == 0) {
            $rules->couponUPC('');
            $rules->save();
            $all_rules = $rules->find('defectorRulesID', true);
        }
        $rules = $all_rules[0];

        $targets = new DefectorTargetsModel($dbc);

        $inactive_period = new stdClass();
        $inactive_period->start = date('Ymd', strtotime($rules->emptyDays() . ' days ago'));
        $inactive_period->end = date('Ymd');

        $active_period = new stdClass();
        $active_period->start = date('Ymd', strtotime($rules->activeDays() . ' days ago'));
        $active_period->end = date('Ymd', strtotime(($rules->emptyDays()+1) . ' days ago'));

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
            HAVING SUM(CASE WHEN date_id BETWEEN ? AND ? THEN transCount ELSE 0 END) = 0
                AND SUM(CASE WHEN date_id BETWEEN ? AND ? THEN transCount ELSE 0 END) >= ?
                AND SUM(CASE WHEN date_id BETWEEN ? AND ? THEN total ELSE 0 END) >= ?
        ');
        $args = array(
            $active_period->start,
            $inactive_period->end,
            $inactive_period->start,
            $inactive_period->end,
            $active_period->start,
            $active_period->end,
            $rules->minVisits()+1,
            $active_period->start,
            $active_period->end,
            $rules->minPurchases(),
        );
        $activityR = $dbc->execute($activityP, $args);
        $dev_targets = new DeveloperTargetsModel($dbc);
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
            echo $w['card_no'] . "\n";

            $targets->reset();
            $targets->card_no($w['card_no']);
            $dev_targets->card_no($w['card_no']);
            if (!$dev_targets->load()) {
                echo "Adding " . $w['card_no'] . "\n";
                $targets->addedDate(date('Y-m-d H:i:s'));
                $targets->card_no($w['card_no']);
                $targets->save();
            }
        }
    }
}

