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

class PIB2BListPage extends PIKillerPage 
{
    protected function get_id_handler()
    {
        global $FANNIE_TRANS_DB;
        $this->card_no = $this->id;

        $this->title = 'B2B Invoices : Member '.$this->card_no;

        $this->all = FormLib::get('all', 0);
        $this->connection->selectDB($this->config->get('TRANS_DB'));
        $model = new B2BInvoicesModel($this->connection);
        $model->cardNo($this->id);
        if (!$this->all) {
            $model->setFindLimit(100);
        }
        $this->__models['b2b'] = $model->find('createdDate', true);
        
        return true;
    }

    protected function get_id_view()
    {
        global $FANNIE_URL;
        echo '<table border="1" style="background-color: #ffff99;">';
        echo '<tr><th>Invoice #</th><th>Created</th><th colspan="2">Paid</th></tr>';
        echo '<tr align="left"></tr>';
        $rowcount = 0;
        foreach ($this->__models['b2b'] as $inv) {
            $stamp = strtotime($inv->createdDate());
            printf('<tr><td><a href="B2BInvoicePage.php?id=%s">%s (view)</a></td><td>%s</td>',
                $inv->b2bInvoiceID(),
                $inv->b2bInvoiceID(),
                date('Y-m-d', strtotime($inv->createdDate()))
            );
            if (!$inv->isPaid()) {
                echo '<td>n/a</td></tr>';
            } else {
                $date = date('Y-m-d', strtotime($inv->paidDate()));
                printf('<td><a href="%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&receipt=%s">%s %s</a></td></tr>',
                    $date, $inv->paidTransNum(), $date, $inv->paidTransNum());
            }
            $rowcount++;
            if ($rowcount > 100 && !$this->all) {
                break;
            }
        }
        echo '</table>';
        if (!$this->all) {
            echo ' <a href="?id=' . $this->id . '&all=1">Show Entire History</a>';
        }
        echo '<br /><a href="B2BInvoicePage.php">Lookup Invoice by #</a>';
    }
}

FannieDispatch::conditionalExec();

