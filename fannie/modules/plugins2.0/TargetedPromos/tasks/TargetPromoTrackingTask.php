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

class TargetPromoTrackingTask extends FannieTask
{
    public function run()
    {
        $settings = $this->config->PLUGIN_SETTINGS;
        $dbc = FannieDB::get($settings['TargetedPromosDB']);

        /**
          Lookup developer coupon usage for yesterday
          and update targets table
        */
        $rules = new DeveloperRulesModel($dbc);  
        $all_rules = $rules->find('developerRuleID', true);
        if (count($all_rules) > 0) {
            $rules = $all_rules[0];
            $couponUPC = $rules->couponUPC();
            $prep = $dbc->prepare('
                SELECT card_no,
                    SUM(quantity) AS qty,
                    MAX(tdate) AS tdate
                FROM ' . $this->config->TRANS_DB . $dbc->sep() . 'dlog_90_view
                WHERE ' . $dbc->datediff($dbc->curdate(), 'tdate') . ' <> 0
                    AND trans_type=\'T\'
                    AND trans_subtype=\'IC\'
                    AND upc=?
                GROUP BY card_no');
            echo $couponUPC . "\n";
            $res = $dbc->execute($prep, array($couponUPC));
            $updateP = $dbc->prepare('
                UPDATE DeveloperTargets
                SET redeemed = redeemed + ?,
                    lastRedeemDate=?
                WHERE card_no=?');
            while ($w = $dbc->fetchRow($res)) {
                echo $w['card_no'] . "\n";
                $dbc->execute($updateP, array($w['qty'], $w['tdate'], $w['card_no']));
            }
        }

        /**
          Lookup defector coupon usage for yesterday
          and update targets table
        */
        $rules = new DefectorRulesModel($dbc);  
        $all_rules = $rules->find('defectorRuleID', true);
        if (count($all_rules) > 0) {
            $rules = $all_rules[0];
            $couponUPC = $rules->couponUPC();
            $prep = $dbc->prepare('
                SELECT card_no,
                    SUM(quantity) AS qty,
                    MAX(tdate) AS tdate
                FROM ' . $this->config->TRANS_DB . $dbc->sep() . 'dlog_90_view
                WHERE ' . $dbc->datediff($dbc->curdate(), 'tdate') . ' <> 0
                    AND trans_type=\'T\'
                    AND trans_subtype=\'IC\'
                    AND upc=?
                GROUP BY card_no');
            $res = $dbc->execute($prep, array($couponUPC));
            $updateP = $dbc->prepare('
                UPDATE DefectorTargets
                SET redeemed = redeemed + ?
                WHERE card_no=?');
            while ($w = $dbc->fetchRow($res)) {
                $dbc->execute($updateP, array($w['qty'], $w['card_no']));
            }
        }
    }
}

