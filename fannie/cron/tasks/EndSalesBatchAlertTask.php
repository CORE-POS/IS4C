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
if (!class_exists('BasicModel')) {
    include(dirname(__FILE__).'/../../classlib2.0/data/models/BasicModel.php');
}
if (!class_exists('ProdUpdateModel')) {
    include(dirname(__FILE__).'/../../classlib2.0/data/models/op/ProdUpdateModel.php');
}

class EndSalesBatchAlertTask extends FannieTask
{
    public $name = 'Sale Batch End Alert';

    public $description = 'Sends an email to grocery department management with
        information regarding close-end-date batches.';

    public $default_schedule = array(
        'min' => 50,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {

        $superDepts = array('GROCERY','REFRIGERATED','FROZEN','GEN MERCH','BULK','WELLNESS','MEAT');
        $grocEmail = $this->config->get('GROCERY_EMAIL');
        $scanEmail = $this->config->get('SCANCOORD_EMAIL');
        $contacts = array($grocEmail,$scanEmail);
        $contacts[] = 'jmatthews@wholefoods.coop';
        $this->getBathchesBySuperDept($superDepts,$contacts);

        return false;
    }

    private function getBathchesBySuperDept($superDepts,$contacts)
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $date = date('Y-m-d').' 00:00:00';
        $d = date('d');
        $m = date('m');
        $y = date('Y');

        $model = new BatchesModel($dbc);
        $model->endDate($date);

        list($inClause,$args) = $dbc->safeInClause($superDepts);
        $query = "SELECT batchName, batchID, startDate, endDate, owner, batchType
            FROM batches
            WHERE endDate BETWEEN CURDATE() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
                AND owner IN (".$inClause.")
                AND batchName not like '%Co-op Deals%'
                AND batchType <> 4;
        ";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);

        $ret = '';
        $style = '
            <style>
                table, th, td {
                    border: 1px solid black;
                    border-collapse: collapse;
                }
                th {
                    width: 210px;
                    background-color: lightblue;
                }
                tr.danger {
                    background-color: tomato;
                }
                td.produce {
                    background-color: #71c98a;
                }
                td.grocery, td.refrigerated, td.frozen,
                    td.bulk, td.gen_merch, td.meat {
                    background-color: #ffa72b;
                }
                td.wellness {
                    background-color: cyan;
                }
                td.deli {
                    background-color: #c674cc;
                }
                td.it {
                    background-color: lightgrey;
                }
                span.danger {
                    background-color: tomato;
                    background-radius: 2px;
                    padding: 2px;
                }
            </style>
        ';

        $table = '<table><thead><th>Batch Name</th><th>Batch ID</th><th>Start Date</th>
                <th>End Date</th><th>Owner</th></thead><tbody>';
        $tableA = $table;
        $tableB = $table;
        $countA = 0;
        $countB = 0;

        if ($dbc->numRows($result) > 0) {
            while ($row = $dbc->fetch_row($result)) {
                if ($row['batchType'] == 11) {
                    $route = 'B';
                    $countB++;
                } else {
                    $route = 'A';
                    $countA++;
                }
                if ($row['endDate'] == $date) {
                    ${'table'.$route} .= '<tr class="danger">';
                } else {
                       ${'table'.$route} .= '<tr>';
                }
                ${'table'.$route} .= '<td>' . $row['batchName'] . '</td>';
                ${'table'.$route} .= '<td>' . $row['batchID'] . '</td>';
                ${'table'.$route} .= '<td>' . substr($row['startDate'],0,10) . '</td>';
                ${'table'.$route} .= '<td>' . substr($row['endDate'],0,10) . '</td>';
                ${'table'.$route} .= '<td class="'.$row['owner'].'">' . $row['owner'] . '</td>';
                ${'table'.$route} .= '</tr>';
            }
            $tableA .= '</tbody></table>';
            $tableB .= '</tbody></table>';
            if ($countA > 0) {
                $ret .= '<h4>Sales Batches</h4>'.$tableA;
            }
            if ($countB > 0) {
                $ret .= '<h4>Disco Batches</h4>'.$tableB;
            }

            if (class_exists('PHPMailer') && ($countA > 0 || $countB > 0)) {
                $mail = new PHPMailer();                
                foreach ($contacts as $contact) {
                    $mail->addAddress($contact);
                }
                $mail->isHTML();
                $mail->From = 'automail@wholefoods.coop';
                $mail->FromName = 'CORE POS Monitoring';
                $mail->Subject ='Report: Sales Batches End Alerts';
                $msg = $style;
                $msg .= date('m-d-y').' Sales batches ending <span class="danger">today</span> 
                    through the next 7 days. <br /><br />';
                $msg .= $ret;
                $mail->Body = $msg;
                if (!$mail->send()) {
                    $this->logger->error('Error emailing monitoring notification');
                }
            }

            return false;
        }
    }

}
