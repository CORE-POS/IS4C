<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op, Duluth, MN

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

namespace COREPOS\Fannie\API\monitor;
use COREPOS\Fannie\API\lib\FannieUI;
use \FannieDB;

class ServerMonitor extends Monitor
{
    private function versionInfo()
    {
        return array(
            'osVersion' => php_uname(),
            'phpVersion' => phpversion(), 
            'coreVersion' => file_get_contents(dirname(__FILE__) . '/../../../VERSION'),
        );
    }

    private function diskSpace()
    {
        $free = disk_free_space(dirname(__FILE__));
        $total = disk_total_space(dirname(__FILE__));
        return array(
            'free' => $free,
            'total' => $total,
            'used' => sprintf('%.2f', 100*($free/$total)),
        );
    }

    public function check()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $ret = array(
            'versions' => $this->versionInfo(),
            'disk' => $this->diskSpace(),
            'products' => $this->productStats($dbc),
            'custdata' => $this->custdataStats($dbc),
        );
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $ret['archiving'] = $this->archiveStatus($dbc);

        return json_encode($ret);
    }

    private function productStats($dbc)
    {
        $prodP = $dbc->prepare('SELECT COUNT(*) FROM products');
        $backP = $dbc->prepare('SELECT COUNT(*) FROM productBackup');
        $saleP = $dbc->prepare('SELECT COUNT(*) FROM products WHERE discounttype <> 0'); 

        return array(
            'total' => $dbc->getValue($prodP),
            'backup' => $dbc->getValue($backP),
            'onSale' => $dbc->getValue($saleP),
        );
    }

    private function custdataStats($dbc)
    {
        $custP = $dbc->prepare('SELECT COUNT(*) FROM custdata WHERE personNum=1');
        $backP = $dbc->prepare('SELECT COUNT(*) FROM custdataBackup');
        $memP = $dbc->prepare('SELECT COUNT(*) FROM custdata WHERE personNum=1 AND Type=\'PC\''); 

        return array(
            'total' => $dbc->getValue($custP),
            'backup' => $dbc->getValue($backP),
            'onSale' => $dbc->getValue($memP),
        );
    }

    private function archiveStatus($dbc)
    {
        $prep = $dbc->prepare('
            SELECT MAX(tdate)
            FROM dlog_15
        ');
        $dlog15 = $dbc->getValue($prep);
        $prep = $dbc->prepare('
            SELECT MAX(tdate)
            FROM dlog_90_view
        ');
        $dlog90 = $dbc->getValue($prep);
        $old_dlog = \DTransactionsModel::selectDlog(date('Y-m-d', strtotime('100 days ago')), date('Y-m-d', strtotime('yesterday')));
        $prep = $dbc->prepare('
            SELECT MAX(tdate)
            FROM ' . $old_dlog . '
            WHERE tdate >= ?
        ');
        $arch = $dbc->getValue($prep, array(date('Y-m-d', strtotime('7 days ago'))));

        return array(
            'dlog15' => $dlog15,
            'dlog90' => $dlog90,
            'archive' => $arch,
        );
    }

    /**
      Escalate if any lane is offline,
      any lane database is unreachable, or
      any measured lane table is empty
    */
    public function escalate($json)
    {
        return false;
    }

    /**
      Proof of concept: just dumping out JSON is not ideal
    */
    public function display($json)
    {
        return '<pre>' . FannieUI::prettyJSON($json) . '</pre>';
    }
}

