<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
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

class PcDetailReport extends FannieReportPage 
{

    protected $required_fields = array('date');
    protected $header = 'Integrated Transaction Details';
    protected $title = 'Integrated Transaction Details';
    public $report_set = 'Tenders';
    public $description = '[Paycard Details] show individual integration transactions. Potential problems are flagged in red.';

    protected $report_headers = array('Date and Time', 'Receipt#', 'Line#', 'Amount', 'Processor', 'Card Type', 'PAN', 'Result', 'Seconds', 'Error Code', 'HTTP Code', 'Reference#');
    
    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $dateID = date('Ymd', strtotime($this->form->date));

        $query = '
            SELECT requestDatetime,
                empNo,
                registerNo,
                transNo,
                transID,
                processor,
                amount,
                PAN,
                issuer,
                seconds,
                commErr,
                httpCode,
                xResultMessage,
                refNum
            FROM PaycardTransactions
            WHERE dateID=?
            ORDER BY requestDatetime';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($dateID));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        $record = array(
            $row['requestDatetime'],
            $row['empNo'] . '-' . $row['registerNo'] . '-' . $row['transNo'],
            $row['transID'],
            sprintf('%.2f', $row['amount']),
            $row['processor'],
            $row['issuer'],
            $row['PAN'],
            $row['xResultMessage'],
            $row['seconds'],
            $row['commErr'],
            $row['httpCode'],
            $row['refNum'],
        );
        if ($row['commErr'] == 0 && $row['httpCode'] != 200 && $row['httpCode'] != 100) {
            $record['meta'] = FannieReportPage::META_COLOR;
            $record['meta_background'] = '#ffe066';
        } elseif ($row['commErr'] != 0 || $row['httpCode'] != 200) {
            $record['meta'] = FannieReportPage::META_COLOR;
            $record['meta_background'] = '#ff8080';
        }

        return $record;
    }

    public function form_content()
    {
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Date</label>
        <input type="text" name="date" class="form-control date-field" />
    </div>        
    <div class="form-group">
        <button type="submit" class="btn btn-default">Submit</button>
    </div>        
</form>
HTML;
        
    }

    public function helpContent()
    {
        return <<<HTML
<p>
View all integrated card transactions that occurred on a given day.
Some of the columns aren't completely intuitive.
<ul>
    <li>Line# refers to the where the charge occurred within a POS transaction.
        This is mostly only note worthy on transactions that include multiple
        payments or multiple attempted payments.</li>
    <li>Result is the response that came back from the processor, if any. An attempted
        charge that received no response from the processor can be a problem since POS
        does not know whether the charge succeeded.</li>
    <li>Seconds is the time that elapsed between sending a request to the processor
        and receiving a response (or timing out).</li>
    <li>Error Code indicates a connection problem occurred. The most common one is usually
        28 indicating the connection timed out. They're technically cURL error codes if
        you need a full reference for the meaning of all values.</li>
    <li>HTTP Code is the status code from the processor's response. The normal status code
        is 200.</li>
    <li>Reference# is provided to help quickly locate a transaction in the processors'
        reporting portal. Mercury calls this "Invoice" in their forms.</li>
</ul>
</p>
<p>
</p>
Potential problems are highlighted in color. Yellow rows indicate the processor was completely
down. While this is not good it is the lesser of two evils. When the processor is rejecting
transactions in this manner there is little to no chance of inadvertently charging a customer 
twice. Red rows indicate a problem occurred in mid-communication with the processor. These 
situations can result in a card being charged on the processor's side but not charge in POS.
</p>
<p>
Mercury requires an extra consideration. Mercury provides an automatic duplication detection
service that eliminates double charges in many circumstances. If a red row is followed by another
identical charge (same card, amount, POS transaction and line#) and the second, identical charge
receives a response, the customer should only be charged once. However, this service is not 100%
reliable. On any day where Mercury has a large number of connection errors, red charges probably
merit manual verification. If the second, identical charge takes more than 15 seconds to complete
that also probably merits manual verification. If the second, identical charge is also red then
manual verification via reporting on Mercury's side is the only way to determine whether or not
the charge went through.
</p>
HTML;
    }

    public function unitTest($phpunit)
    {
        $data = array('requestDatetime'=>'2000-01-01', 'empNo'=>1, 'registerNo'=>1,
            'transNo'=>1, 'transID'=>1, 'amount'=>1.99, 'processor'=>'test',
            'issuer'=>'test', 'PAN'=>'1111', 'xResultMessage'=>'APPROVED', 'seconds'=>1,
            'commErr'=>0, 'httpCode'=>403, 'refNum'=>'1234');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
        $data['commErr'] = 28;
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

