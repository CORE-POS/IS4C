<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

use COREPOS\pos\plugins\Plugin;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;

class RemotePrint extends Plugin 
{
    public $plugin_settings = array(
        'RemotePrintDevice' => array(
            'label' => 'Printer',
            'description' => 'A printer device name like LPT2: or a URL',
            'default' => '',
        ),
        'RemotePrintHandler' => array(
            'label' => 'Driver',
            'description' => 'Handles communication with device',
            'default' => 'COREPOS-pos-lib-PrintHandlers-ESCPOSPrintHandler',
            'options' => array(
                'ESCPOS' => 'COREPOS-pos-lib-PrintHandlers-ESCPOSPrintHandler',
                'RAW/TCP' => 'COREPOS-pos-lib-PrintHandlers-ESCNetRawHandler',
                'HTTP' => 'RemotePrintHandler',
            ),
        ),
        'RemotePrintDebug' => array(
            'label' => 'Debug mode',
            'description' => 'Print debugging info instead of the normal receipt',
            'default' => 0,
            'options' => array(
                'Yes' => 1,
                'No' => 0,
            ),
        ),
    );

    public $plugin_description = 'Send some info to a remote printer';

    public function plugin_transaction_reset()
    {
        list($emp, $reg, $trans) = explode('-', ReceiptLib::mostRecentReceipt(), 3);
        $driverClass = CoreLocal::get('RemotePrintHandler');
        if (!class_exists($driverClass)) {
            $driverClass = 'COREPOS\\pos\\lib\\PrintHandlers\ESCPOSPrintHandler';
        }
        $driver = new $driverClass();

        $dbc = Database::tDataConnect();
        $infoP = $dbc->prepare("
            SELECT upc, description, quantity, charflag, trans_status, trans_subtype,
                CASE WHEN a.identifier IS NOT NULL OR b.identifier IS NOT NULL THEN 1 ELSE 0 END as remote
            FROM localtranstoday AS l
                LEFT JOIN " . CoreLocal::get('pDatabase') . $dbc->sep() . "RemotePrint AS a
                    ON l.upc=a.identifier AND a.type='UPC'
                LEFT JOIN " . CoreLocal::get('pDatabase') . $dbc->sep() . "RemotePrint AS b
                    ON l.department=b.identifier AND b.type='Department'
            WHERE emp_no=? AND register_no=? AND trans_no=?
                AND trans_status NOT IN ('V','R')
                AND voided = 0
                AND quantity > 0
            ORDER BY trans_id");
        $infoR = $dbc->execute($infoP, array($emp, $reg, $trans));
        $lines = array();
        $comments = array();
        $hri = array();
        while ($row = $dbc->fetchRow($infoR)) {
            if (CoreLocal::get('RemotePrintDebug')) {
                $lines[] = array(
                    'qty'=>1, 
                    'upc'=>'', 
                    'description'=>"{$row['upc']} | {$row['charflag']} | {$row['trans_status']} | {$row['trans_subtype']}",
                );
                continue;
            }
            if ($row['trans_status'] == 'X' && $row['charflag'] != 'S' && $row['charflag'] != 'HR') {
                // This is a canceled line. Skip it.
                continue;
            }
            if ($row['upc'] == 'RESUME' && ($row['charflag'] == 'SR' || $row['charflag'] == 'S')) {
                // Resumed transaction here. Reset accumulators.
                $lines = array();
                $comments = array();
            }
            if ($row['remote']) {
                $lines[] = array('upc'=>$row['upc'], 'description'=>$row['description'], 'qty'=>$row['quantity']);
            } elseif ($row['trans_subtype'] == 'CM' && $row['charflag'] == 'HR') {
                $hri[] = $row['description'];
            } elseif ($row['trans_subtype'] == 'CM') {
                $lines[] = $row['description'];
            }
        }

        if (count($lines) > 0) {
            $stars = str_repeat('*', 40);
            $receipt = $stars . "\n\n";
            $receipt .= date('Y-m-d h:i:sA') . ' ' . $emp . '-' . $reg . '-' . $trans . "\n\n";
            if (count($hri) > 0) {
                $receipt = $stars . "\n\n";
                $receipt .= date('Y-m-d h:i:sA') . "\n" . implode("\n", $hri) . "\n";
            }
            $receipt .= $stars . "\n\n";

            $prevWasItem = true;
            foreach ($lines as $line) {
                if (is_array($line)) {
                    $receipt .= str_pad($line['description'], 35, ' ', STR_PAD_RIGHT)
                        . str_pad($line['quantity'], 5, ' ', STR_PAD_LEFT)
                        . "\n";
                    $prevWasItem = true;
                } else {
                    $receipt .= $line . "\n";
                    $prevWasItem = false;
                }
                if ($prevWasItem) {
                    $receipt .= "\n";
                }
            }

            $receipt .= $stars . "\n\n";
            $receipt = ReceiptLib::cutReceipt($receipt, false);
            
            if ($driverClass == 'COREPOS\\pos\\lib\\PrintHandlers\ESCPOSPrintHandler') {
                $port = fopen(CoreLocal::get('RemotePrintDevice'), 'w');
                fwrite($port, $receipt);
                fclose($port);
            } elseif ($driverClass == 'COREPOS\\pos\\lib\\PrintHandlers\\ESCNetRawHandler') {
                $driver->setTarget(CoreLocal::get('RemotePrint'));
                $driver->writeLine($receipt);
            } else {
                $driver->writeLine($receipt);
            }
        }
    }
}

