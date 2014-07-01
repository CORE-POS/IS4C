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
if (!class_exists('FannieAPI'))
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PIEquityPage extends PIKillerPage {

    protected function get_id_handler(){
        global $FANNIE_TRANS_DB;
        $this->card_no = $this->id;

        $this->title = 'Equity History : Member '.$this->card_no;

        $this->__models['equity'] = $this->get_model(FannieDB::get($FANNIE_TRANS_DB), 'StockpurchasesModel',
                        array('card_no'=>$this->id),'tdate');
        $this->__models['equity'] = array_reverse($this->__models['equity']);
    
        return True;
    }

    protected function get_id_view(){
        global $FANNIE_URL;
        echo '<table border="1" style="background-color: #ffff99;">';
        echo '<tr align="left"></tr>';
        foreach($this->__models['equity'] as $transaction){
            $stamp = strtotime($transaction->tdate());
            printf('<tr>
                <td><a href="%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&receipt=%s">%s</a></td>
                <td>%.2f</td>
                <td>%d</td>
                </tr>',
                $FANNIE_URL, date('Y-m-d',$stamp), $transaction->trans_num(), date('Y-m-d',$stamp),
                $transaction->stockPurchase(),
                $transaction->card_no()
            );
        }
        echo '</table>';
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

?>
