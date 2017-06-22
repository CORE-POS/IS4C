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
        
        $p_def = $dbc->tableDefinition('products');
        if (!isset($p_def['last_sold'])) {
            $this->logger->warning('products table does not have a last_sold column');
            return;
        }
        
        $upcs = array();
        $prepZ = $dbc->prepare("SELECT upc FROM products GROUP BY upc");
        $resZ = $dbc->execute($prepZ);
        while ($row = $dbc->fetchRow($resZ)) {
            $upcs[] = $row['upc'];
        }
        
        $y = date('Y');
        $m = date('m') - 1;
        $d = date('d');
        $checkDate = $y.'-'.$m.'-'.$d;
        
        $exempts1 = array();
        $exempts2 = array();
        foreach ($upcs as $upc) {
            $stores = array(1,2);
            foreach ($stores as $store) {
                $args = array($store,$upc,$checkDate);
                $prepA = $dbc->prepare("SELECT upc, modified, inUse FROM products WHERE store_id = ? AND upc = ? AND modified >= ? ORDER BY modified DESC LIMIT 1;");
                $resA = $dbc->execute($prepA,$args);
                while ($row = $dbc->fetchRow($resA)) {
                    if($row['inUse'] == 1 && $store == 1) {
                        $exempts1[] = $row['upc'];
                    } elseif ($row['inUse'] == 1 && $store == 2) {
                        $exempts2[] = $row['upc'];
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
            AND p.inUse = 0;                                                      
        ");                                                                                                                                                 
        $reportUnUse = $dbc->prepare("                                            
            SELECT upc, brand, description, last_sold, store_id                                       
                FROM products AS p                                                
                INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department      
                INNER JOIN inUseTask AS i ON s.superID = i.superID                
            WHERE UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.last_sold) > i.time
            AND p.inUse = 1;                                                      
        ");                                                                                                                                                 
        $resultA = $dbc->execute($reportInUse);                                   
        $resultB = $dbc->execute($reportUnUse);                                   

        list($inClause1,$args1) = $dbc->safeInClause($exempts1);
        list($inClause2,$args2) = $dbc->safeInClause($exempts2);
        array_unshift($args1,1);
        array_unshift($args2,2);
        $updateQunuse1 = '
            UPDATE products p
                INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department 
                INNER JOIN inUseTask AS i ON s.superID = i.superID 
            SET p.inUse = 0, p.modified = '.$dbc->now().'
            WHERE UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.last_sold) > i.time
                AND p.store_id = ?
                AND p.upc NOT IN ('.$inClause1.')
            ';
        $updateQunuse2 = '
            UPDATE products p
                INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department 
                INNER JOIN inUseTask AS i ON s.superID = i.superID 
            SET p.inUse = 0, p.modified = '.$dbc->now().'
            WHERE UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.last_sold) > i.time
                AND p.store_id = ?
                AND p.upc NOT IN ('.$inClause2.')
            ';
        $updateUnuse1 = $dbc->prepare($updateQunuse1);
        $updateUnuse2 = $dbc->prepare($updateQunuse2);
        
        $updateUse = $dbc->prepare('
            UPDATE products p
                INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department 
            SET p.inUse = 1, p.modified = '.$dbc->now().'
            WHERE UNIX_TIMESTAMP(p.last_sold) >= (UNIX_TIMESTAMP(CURDATE()) - 84600)
                AND p.store_id = ?;
        ');
        $dbc->execute($updateUnuse1,$args1);
        $dbc->execute($updateUnuse2,$args2);
        
        $dbc->execute($updateUse,1);
        $dbc->execute($updateUse,2);
        
        $data = '';
        $inUseData = '<table><thead><th>UPC</th><th>Brand</th><th>Description</th><th>Last Sold On</th><th>Store ID</th></thead><tbody>';
        $unUseData = '<table><thead><th>UPC</th><th>Brand</th><th>Description</th><th>Last Sold On</th><th>Store ID</th></thead><tbody>';
        $updateUpcs = array();

        $fields = array('upc','brand','description','last_sold','store_id');
        while ($row = $dbc->fetch_row($resultA)) {
            $inUseData .= '<tr>';
            foreach ($fields as $column) {
                if ($column == '' || empty($column)) {
                    $column = '<i>data missing</i>';
                }
                $inUseData .= '<td>' . $row[$column] . '</td>';
            }
            $inUseData .= '</tr>';
            $updateUpcs[] = $row['upc'];
        }
        $inUseData .= '</tbody></table>';
        
        while ($row = $dbc->fetch_row($resultB)) {
            if ($row['store_id'] == 1) {
                if (!in_array($row['upc'],$exempts1)) {
                    $unUseData .= '<tr>';
                    foreach ($fields as $column) {
                        if ($column == '' || empty($column)) {
                            $column = '<i>no data</i>';
                        }
                        $unUseData .= '<td>' . $row[$column] . '</td>';
                    }
                    $unUseData .= '</tr>';
                    $updateUpcs[] = $row['upc'];
                }
            } elseif ($row['store_id'] == 2) {
                if (!in_array($row['upc'],$exempts2)) {
                    $unUseData .= '<tr>';
                    foreach ($fields as $column) {
                        if ($column == '' || empty($column)) {
                            $column = '<i>no data</i>';
                        }
                        $unUseData .= '<td>' . $row[$column] . '</td>';
                    }             
                    $unUseData .= '</tr>';   
                    $updateUpcs[] = $row['upc'];
                }
            }            
        }
        $unUseData .= '</tbody></table>';

        $prodUpdate = new ProdUpdateModel($dbc);
        $prodUpdate->logManyUpdates($updateUpcs);

        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=iso-8859-1',
            'from: automail@wholefoods.coop'            
        );

        $end = time();
        $runtime = ($end - $start);
        $runtime = $this->convert_unix_time($runtime);
        
        $to = $this->config->get('SCANCOORD_EMAIL');
        
        if (class_exists('PHPMailer')) {
            $msg = '<style>table, tr, td { border-collapse: collapse; border: 1px solid black; 
                padding: 5px; }</style>';
            $msg .= 'In Use Task (Product In-Use Management) completed at '.date('Y-m-d');
            $msg .= ' [ Runtime: '.$runtime.' ]<br />';
            $msg .= '<br />';
            $msg .= 'Items removed from use' . '<br />';
            $msg .= $unUseData;
            $msg .= '<br />';
            $msg .= 'Items added to use' . '<br />';
            $msg .= $inUseData;
            $msg .= '<br />';
            $msg .= '<br />';
                
            $mail = new PHPMailer();
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
