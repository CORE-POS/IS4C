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

class RemotePrintOTC extends Plugin
{
    public $plugin_settings = array(
        'RemotePrintDeviceOTC-1' => array(
            'label' => 'Printer 1',
            'description' => 'A printer device name like LPT2: or a URL',
            'default' => '',
        ),
		'RemotePrintDeviceOTC-2' => array(
            'label' => 'Printer 2',
            'description' => 'A printer device name like LPT2: or a URL',
            'default' => '',
        ),
        'RemotePrintHandlerOTC' => array(
            'label' => 'Driver',
            'description' => 'Handles communication with device',
            'default' => 'COREPOS-pos-lib-PrintHandlers-ESCPOSPrintHandler',
            'options' => array(
                'ESCPOS' => 'COREPOS-pos-lib-PrintHandlers-ESCPOSPrintHandler',
                'RAW/TCP' => 'COREPOS-pos-lib-PrintHandlers-ESCNetRawHandler',
                'HTTP' => 'RemotePrintHandler',
            ),
        ),
        'RemotePrintDebugOTC' => array(
            'label' => 'Debug mode',
            'description' => 'Print debugging info instead of the normal receipt',
            'default' => 0,
            'options' => array(
                'Yes' => 1,
                'No' => 0,
            ),
        ),
    );

    public $plugin_description = 'Send same info to up to two remote printers';

    public function plugin_transaction_reset()
    {
        list($emp, $reg, $trans) = explode('-', ReceiptLib::mostRecentReceipt(), 3);
        $driverClass = CoreLocal::get('RemotePrintHandlerOTC');
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
            ORDER BY trans_id");
        $infoR = $dbc->execute($infoP, array($emp, $reg, $trans));
        $lines = array();
        $comments = array();
        $hri = false;
        while ($row = $dbc->fetchRow($infoR)) {
/*            if (CoreLocal::get('RemotePrintDebugOTC')) {
                $lines[] = array(
                    'qty'=>1,
                    'upc'=>'',
                    'description'=>"{$row['upc']} | {$row['charflag']} | {$row['trans_status']} | {$row['trans_subtype']}",
                );
                continue;
            } */
            if ($row['trans_status'] == 'X' && $row['charflag'] != 'S') {
                // This is a canceled line. Skip it.
                continue;
            }
            if ($row['upc'] == 'RESUME' && ($row['charflag'] == 'SR' || $row['charflag'] == 'S')) {
                // Resumed transaction here. Reset accumulators.
                $lines = array();
                $comments = array();
            }
            if ($row['remote'] || ($row['trans_subtype'] == 'CM' && $row['charflag'] != 'HR')) {
                $lines[] = array('upc'=>$row['upc'], 'description'=>$row['description'], 'qty'=>$row['quantity']);
            }
			if ($row['trans_subtype'] == 'CM' && $row['charflag'] == 'HR') {
                $hri = $row['description'];
            }
        }

        if (count($lines) > 0) {
			$receipt = "\n";
			/***********/
			//Our Table specific
			$receipt .= str_repeat("*",10) . " OTC KITCHEN " . str_repeat("*",10) . "\n\n";
			//Original: $receipt .= str_repeat("*",35) . "\n\n";
			if ($hri) {
				$receipt .= chr(29).chr(33).chr(17);	/* big font */
                $receipt .= $hri;
				$receipt .= chr(29).chr(33).chr(00);	/* normal font */
				$receipt .= "\n\n";
            }
			$receipt .= date('Y-m-d h:i:sA') . "\n\n";
            $receipt .= str_repeat("*",35) . "\n\n";
			$receipt .= $emp . '-' . $reg . '-' . $trans . "\n\n";

            foreach ($lines as $line) {
				if ($line['qty'] == 0) {
					$receipt .= str_pad($line['description'], 35, ' ', STR_PAD_RIGHT) . "\n\n";
				} else {
					$receipt .= str_pad($line['description'], 35, ' ', STR_PAD_RIGHT)
						. str_pad($line['qty'], 5, ' ', STR_PAD_LEFT)
						. "\n";
				}
            }
            $receipt .= "\n\n";
            //$receipt .= implode("\n", $comments);
            $receipt .= "\n\n";
            $receipt = ReceiptLib::cutReceipt($receipt,false);

            if ($driverClass == 'COREPOS\\pos\\lib\\PrintHandlers\ESCPOSPrintHandler') {
                //Write to first printer
				$port = fopen(CoreLocal::get('RemotePrintDeviceOTC-1'), 'w');
                fwrite($port, $receipt);
                fclose($port);
                //Write to second printer
				$port = fopen(CoreLocal::get('RemotePrintDeviceOTC-2'), 'w');
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
