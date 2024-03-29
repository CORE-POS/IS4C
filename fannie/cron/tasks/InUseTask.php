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

use COREPOS\Fannie\API\data\pipes\OutgoingEmail;

if (!class_exists('BasicModel')) {
    include(dirname(__FILE__).'/../../classlib2.0/data/models/BasicModel.php');
}
if (!class_exists('ProdUpdateModel')) {
    include(dirname(__FILE__).'/../../classlib2.0/data/models/op/ProdUpdateModel.php');
}

class InUseTask extends FannieTask
{
    public $name = 'Product In-Use Maintenance';

    public $description = 'Scans last-sold dates for all registered prodcuts and
        updates in-use status based on a determined range by super-department.';

    public $default_schedule = array(
        'min' => 50,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    /*
    *   UNIX TIME
    *
    *   86400 = 1 Day
    *   2678400 = 1 Month
    *   5356800 = ~2 Months
    *   13392000 = ~5 Months
    */

    public function run()
    {

        $start = time();
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        // Get a list of UPC that are currently on sale
        $saleUpcs = array();
        $p = $dbc->prepare("
            SELECT bl.upc, 
                MAX(bl.salePrice) AS salePrice,
                MAX(bl.batchID) AS batchID,
                MAX(p.brand) AS brand,
                MAX(p.description) AS description,
                date(MAX(b.startDate)) AS startDate,
                date(MAX(b.endDate)) AS endDate
            FROM batchList AS bl
                LEFT JOIN products AS p ON bl.upc=p.upc
                LEFT JOIN batches AS b ON bl.batchID=b.batchID
            WHERE bl.batchID IN ( SELECT batchID FROM batches WHERE NOW() BETWEEN startDate AND endDate)
            GROUP BY bl.upc;
        ");
        $r = $dbc->execute($p);
        while ($row = $dbc->fetchRow($r)) {
            $saleUpcs[] = $row['upc'];
        }

        $p_def = $dbc->tableDefinition('products');
        if (!isset($p_def['last_sold'])) {
            $this->logger->warning('products table does not have a last_sold column');
            return;
        }

        $upcs = array();
        // procure a list of every applicable upc in POS to check
        $prepZ = $dbc->prepare("
            SELECT p.upc
            FROM products AS p
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE m.superID IN (1,4,5,8,9,13,17,18)
                AND p.department NOT IN (225,226,228,229,602)
                AND p.department NOT BETWEEN 60 AND 81
            GROUP BY upc;
        ");
        $resZ = $dbc->execute($prepZ);
        while ($row = $dbc->fetchRow($resZ)) {
            $upcs[] = $row['upc'];
        }

        $date = new DateTime();
        $date ->sub(new DateInterval('P1M'));
        $checkDate = $date->format('Y-m-d');

        // exclude items that have been modified within last 30 days from "set inUse = 0" query
        $exempts = array();
        $stores = array(1,2);
        foreach ($stores as $store) {
            $exempts[$store] = array();
        }
        foreach ($upcs as $upc) {
            foreach ($stores as $store) {
                $args = array($store,$upc,$checkDate);
                $prepA = $dbc->prepare("SELECT upc, modified, inUse FROM products WHERE store_id = ? AND upc = ? AND modified >= ? ORDER BY modified DESC LIMIT 1;");
                $resA = $dbc->execute($prepA,$args);
                while ($row = $dbc->fetchRow($resA)) {
                    if ($row['inUse'] == 1) {
                        $exempts[$store][] = $row['upc'];
                    }
                }
            }
        }

        $reportInUse = $dbc->prepare("
            SELECT upc, brand, description, last_sold, store_id
                FROM products AS p
                INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department
                INNER JOIN inUseTask AS i ON s.superID = i.superID
            WHERE UNIX_TIMESTAMP(p.last_sold) >= (UNIX_TIMESTAMP(CURDATE()) - 84600)
            AND p.inUse = 0
            ORDER BY p.store_id;
        ");
        $reportUnUse = $dbc->prepare("
            SELECT upc, brand, description, last_sold, store_id
                FROM products AS p
                INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department
                INNER JOIN inUseTask AS i ON s.superID = i.superID
            WHERE (
                    UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.last_sold) > i.time
                    OR (UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.created) > i.time AND p.last_sold IS NULL)
            )
                AND p.inUse = 1
                AND s.superID IN (1,4,5,8,9,13,17,18)
                AND p.department NOT IN (225,226,228,229,602)
                AND p.department NOT BETWEEN 60 AND 81
            ORDER BY p.store_id;
        ");
        $resultA = $dbc->execute($reportInUse);
        $resultB = $dbc->execute($reportUnUse);

        foreach (array(1, 2) as $store) {
            list($inClause,$argsUnuse) = $dbc->safeInClause($exempts[$store]);
            array_unshift($argsUnuse, $store);
            $updateQunuse = '
                UPDATE products p
                    INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department
                    INNER JOIN inUseTask AS i ON s.superID = i.superID
                SET p.inUse = 0, p.modified = '.$dbc->now().'
                WHERE (
                    UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.last_sold) > i.time
                    OR (UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.created) > i.time AND p.last_sold IS NULL)
                    )
                    AND p.store_id = ?
                    AND p.inUse = 1
                    AND s.superID IN (1,4,5,8,9,13,17,18)
                    AND p.department NOT IN (225,226,228,229,602)
                    AND p.department NOT BETWEEN 60 AND 81
                    AND p.upc NOT IN ('.$inClause.')
                ';
            $updateUnuse = $dbc->prepare($updateQunuse);
            $dbc->execute($updateUnuse,$argsUnuse);

            $updateUse = $dbc->prepare('
                UPDATE products p
                    INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department
                SET p.inUse = 1, p.modified = '.$dbc->now().'
                WHERE UNIX_TIMESTAMP(p.last_sold) >= (UNIX_TIMESTAMP(CURDATE()) - 84600)
                    AND p.store_id = ?
                    AND p.inUse = 0
            ');

            $dbc->execute($updateUse, $store);
        }

        $data = '';
        $inUseData = '<table><thead><th>UPC</th><th>Brand</th><th>Description</th><th>Last Sold On</th><th>Store ID</th></thead><tbody>';
        $unUseData = '<table><thead><th>UPC</th><th>Brand</th><th>Description</th><th>Last Sold On</th><th>Store ID</th></thead><tbody>';
        $updateUpcs = array();

        $fields = array('upc','brand','description','last_sold','store_id');
        while ($row = $dbc->fetch_row($resultA)) {
            $inUseData .= '<tr>';
            foreach ($fields as $column) {
                $class = ($column == 'upc' && in_array($row[$column], $saleUpcs)) ? 'saleitem' : '';
                if ($column == '' || empty($column)) {
                    $column = '<i>data missing</i>';
                }
                $inUseData .= '<td class="'.$class.'">' . $row[$column] . '</td>';
            }
            $inUseData .= '</tr>';
            $updateUpcs[] = $row['upc'];
        }
        $inUseData .= '</tbody></table>';

        while ($row = $dbc->fetch_row($resultB)) {
            if (!in_array($row['upc'],$exempts[$row['store_id']])) {
                $unUseData .= '<tr>';
                foreach ($fields as $column) {
                    $class = ($column == 'upc' && in_array($row[$column], $saleUpcs)) ? 'saleitem' : '';
                    if ($column == '' || empty($column)) {
                        $column = '<i>no data</i>';
                    }
                    $unUseData .= '<td class="'.$class.'">' . $row[$column] . '</td>';
                }
                $unUseData .= '</tr>';
                $updateUpcs[] = $row['upc'];
            }
        }
        $unUseData .= '</tbody></table>';

        $prodUpdate = new ProdUpdateModel($dbc);
        $prodUpdate->logManyUpdates($updateUpcs);

        $callbacks = \FannieConfig::config('ITEM_CALLBACKS');
        foreach ($callbacks as $cb) {
            $obj = new $cb();
            $obj->run($updateUpcs);
        }

        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=iso-8859-1',
            'from: automail@wholefoods.coop'
        );

        $end = time();
        $runtime = ($end - $start);
        $runtime = $this->convert_unix_time($runtime);

        $to = $this->config->get('FANNIE_ADMIN_EMAIL');

        if (OutgoingEmail::available()) {
            $msg = '
<style>
    table, tr, td { 
        border-collapse: collapse; 
        border: 1px solid black;
        padding: 5px; 
    } 
    .saleitem { 
        background-color: lightgreen; 
        color: black;
    } 
</style>';
            $msg .= 'In Use Task (Product In-Use Management) completed on '.date('Y-m-d h:i:s');
            $msg .= ' [ Runtime: '.$runtime.' ]<br />';
            $msg .= 'UPCs highlighted in <span class="saleitem">green</span> are currently on sale
                and may require further attention.';
            $msg .= '<br />';
            $msg .= '<br />';
            $msg .= 'Items removed from use' . '<br />';
            $msg .= $unUseData;
            $msg .= '<br />';
            $msg .= 'Items added to use' . '<br />';
            $msg .= $inUseData;
            $msg .= '<br />';
            $msg .= '<br />';

            $mail = OutgoingEmail::get();
            $mail->isHTML();
            $mail->addAddress($to);
            $mail->From = 'automail@wholefoods.coop';
            $mail->FromName = 'CORE POS Monitoring';
            $mail->Subject = 'Report: In Use Task';
            $mail->Body = $msg;
            if (!$mail->send()) {
                $this->logger->error('Error emailing monitoring notification');
            }
        } else {
            $msg = 'The In Use Task $message could not be formatted. [Error] : class PHPMailer could not found.';
            mail($to,'Report: In Use Task',$msg,implode("\r\n",$headers));
        }

    }

    public function convert_unix_time($secs) {
        /*
        $bit = array(
            'y' => $secs / 31556926 % 12,
            'w' => $secs / 604800 % 52,
            'd' => $secs / 86400 % 7,
            'h' => $secs / 3600 % 24,
            'm' => $secs / 60 % 60,
            's' => $secs % 60
            );
        */
        $bit = array(
            'h' => $secs / 3600 % 24,
            'm' => $secs / 60 % 60,
            's' => $secs % 60
        );

        foreach($bit as $k => $v)
            if($k == 's') {
                $ret[] = $v;
            } else {
                $ret[] = $v . ':';
            }
            if ($v == 0) $ret[] = '0';

        return join('', $ret);
    }

}
