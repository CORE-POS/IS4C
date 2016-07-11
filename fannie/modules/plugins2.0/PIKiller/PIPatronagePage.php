<?php 
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
}
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PIPatronagePage extends PIKillerPage {

    protected function get_id_handler(){
        global $FANNIE_OP_DB;
        $this->card_no = $this->id;

        $this->title = 'Patronage : Member '.$this->card_no;

        $this->__models['patronage'] = $this->get_model(FannieDB::get($FANNIE_OP_DB),
                        'PatronageModel',
                        array('cardno'=>$this->id),
                        'FY');
        return True;
    }

    protected function get_id_view(){
        ob_start();
        echo '<tr><td>';

        $totals = array('cash'=>0.00,'equity'=>0.00);

        echo '<table border="1" style="background-color:#ffffcc;">';
        echo '<tr><th>FY</th><th>Purchases</th><th>Discounts</th><th>Rewards</th>
            <th>Net Purchases</th><th>Total Patronage</th>
            <th>Cash Portion</th><th>Equity Portion</th>
            <th>Check Number</th><th>Cashed Date</th>
            <th>Cashed Here</th><th>Reprint</th></tr>';
        foreach($this->__models['patronage'] as $obj){
            $reprint_link = 'n/a';
            if ($obj->check_number() != '') {
                $reprint_link = sprintf('<a href="../../../mem/patronage/PatronageChecks.php?reprint=1&mem=%d&fy=%d">Reprint</a>',
                    $obj->cardno(), $obj->FY());
            }
            $cashed_stamp = strtotime($obj->cashed_date());
            if ($obj->cashed_date() != '' && $cashed_stamp) {
                $obj->cashed_date(date('Y-m-d', $cashed_stamp));
            }
            printf('<tr>
                <td>%d</th>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                </tr>',
                $obj->FY(),
                $obj->purchase(),
                $obj->discounts(),
                $obj->rewards(),
                $obj->net_purch(),
                $obj->tot_pat(),
                $obj->cash_pat(),
                $obj->equit_pat(),
                ($obj->check_number() == '' ? 'n/a' : $obj->check_number()),
                ($obj->cashed_date() == '' ? 'n/a' : $obj->cashed_date()),
                ($obj->cashed_here() == 1 ? 'Yes' : 'No'),
                $reprint_link
            );
            $totals['cash'] += $obj->cash_pat();
            $totals['equity'] += $obj->equit_pat();
        }
        echo '</table>';

        echo '<p>Historical Totals:</p>';
        echo '<table border="1" style="background-color:#ffffcc;">';
        echo '<tr><th>Mem#</th><th>Total</th><th>Cash</th><th>Equity</th></tr>';
        printf('<tr><td>%d</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>',
            $this->id,($totals['cash']+$totals['equity']),
            $totals['cash'],$totals['equity']);
        echo '</table>';

        echo '</td></tr>';
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

