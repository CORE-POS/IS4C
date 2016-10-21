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

class PIArPage extends PIKillerPage 
{
    protected function get_id_handler()
    {
        global $FANNIE_TRANS_DB;
        $this->card_no = $this->id;

        $this->title = 'AR History : Member '.$this->card_no;

        $this->all = FormLib::get('all', 0);
        $this->connection->selectDB($this->config->get('TRANS_DB'));
        $model = new ArHistoryModel($this->connection);
        $model->card_no($this->id);
        if (!$this->all) {
            $model->setFindLimit(100);
        }
        $this->__models['ar'] = $model->find('tdate', true);
        
        return true;
    }

    protected function get_id_view()
    {
        global $FANNIE_URL;
        echo '<table border="1" style="background-color: #ffff99;">';
        echo '<tr align="left"></tr>';
        $rowcount = 0;
        foreach ($this->__models['ar'] as $transaction) {
            $stamp = strtotime($transaction->tdate());
            if ($transaction->payments() != 0){
                printf('<tr>
                    <td><a href="%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&receipt=%s">%s</a></td>
                    <td>%.2f</td>
                    <td>%d</td>
                    <td style="background-color:#ff66ff;">P</td>
                    </tr>',
                    $FANNIE_URL, date('Y-m-d',$stamp), $transaction->trans_num(), date('Y-m-d',$stamp),
                    $transaction->payments(),
                    $transaction->card_no()
                );
            }
            if ($transaction->charges() != 0){
                printf('<tr>
                    <td><a href="%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&receipt=%s">%s</a></td>
                    <td>%.2f</td>
                    <td>%d</td>
                    <td style="background-color:#0055ff;">C</td>
                    </tr>',
                    $FANNIE_URL, date('Y-m-d',$stamp), $transaction->trans_num(), date('Y-m-d',$stamp),
                    $transaction->charges(),
                    $transaction->card_no()
                );
            }
            $rowcount++;
            if ($rowcount > 100 && !$this->all) {
                break;
            }
        }
        echo '</table>';
        if (!$this->all) {
            echo ' <a href="?id=' . $this->id . '&all=1">Show Entire AR History</a>';
        }
    }
}

FannieDispatch::conditionalExec();

