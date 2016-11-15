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

class AutoOrderTask extends FannieTask
{
    public $name = 'Auto Re-order';

    public $description = 'Generates orders AND sends based on inventory info';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    private function getMailer()
    {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = '127.0.0.1';
        $mail->Port = 25;
        $mail->SMTPAuth = false;
        $mail->From = 'it@wholefods.coop';
        $mail->FromName = 'Whole Foods Co-op';
        $mail->isHTML = true;
    }

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $place = $dbc->prepare("UPDATE PurchaseOrder SET placed=1, placedDate=? WHERE orderID=?");
        $map = new AutoOrderMapModel($dbc);
        if (!class_exists('WfcPoExport')) {
            include(__DIR__ . '/../../purchasing/exporters/WfcPoExport');
        }
        $export = new WfcPoExport();
        $vendor = new VendorsModel($dbc);
        $mail = $this->getMailer();
        foreach ($map->find() as $obj) {
            $task = new OrderGenTask();
            $task->setConfig($this->config);
            $task->setLogger($this->logger);
            $task->setVendors(array($obj->vendorID()));
            $task->setStore($obj->storeID());
            $ids = $task->run();
            if (count($ids) == 0) {
                continue;
            }

            $orderID = $ids[0];
            $csv = $export->export_order($orderID);
            $vendor->vendorID($obj->vendorID());
            if (!$vendor->load()) {
                $this->cronMsg("Could not find vendor ID: " . $obj->vendorID());
                continue;
            }
            $addr = $vendor->email();
            $mail->addAddress($addr);
            $mail->Subject = 'WFC Purchase Order ' . date('Y-m-d');
            $mail->Body = $this->csvToHtml($csv);
            $mail->AltBody = str_replace("\r", "", $csv);
            $mail->addStringAttachment(
                $csv,
                'WFC Order ' . date('Y-m-d') . '.csv',
                'base64',
                'text/csv'
            );
            $sent = $mail->send();
            if ($sent) {
                // mark order as placed
                $dbc->execute($place, array(date('Y-m-d H:i:s'), $orderID));
            } else {
                $this->cronMsg("Failed emailing order for vendor ID: " . $obj->vendorID());
            }
        }
    }

    private function csvToHtml($csv)
    {
        $lines = explode("\r\n", $csv);
        $ret = "<table>\n";
        foreach ($lines as $line) {
            $ret .= "<tr>\n";
            $row = str_getcsv($line);
            foreach ($row as $entry) {
                $ret .= "<td>{$entry}</td>";
            }
            $ret .= "</tr>\n";
        }
        $ret .= "</table>\n";

        return $ret;
    }
}

