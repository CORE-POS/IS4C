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

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $p_def = $dbc->tableDefinition('products');
        if (!isset($p_def['last_sold'])) {
            $this->logger->warning('products table does not have a last_sold column');
            return;
        }

		$reportInUse = $dbc->prepare("                                            
		    SELECT upc, last_sold, store_id                                       
		        FROM products AS p                                                
		        INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department      
		        INNER JOIN inUseTask AS i ON s.superID = i.superID                
		    WHERE UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.last_sold) < i.time
		    AND p.inUse = 0;                                                      
		");                                                                                                                                                 
		$reportUnUse = $dbc->prepare("                                            
		    SELECT upc, last_sold, store_id                                       
		        FROM products AS p                                                
		        INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department      
		        INNER JOIN inUseTask AS i ON s.superID = i.superID                
		    WHERE UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.last_sold) > i.time
	    	AND p.inUse = 1;                                                      
		");                                                                                                                                                 
		$resultA = $dbc->execute($reportInUse);                                   
		$resultB = $dbc->execute($reportUnUse);                                   

        $updateUnuse = $dbc->prepare('
            UPDATE products p
                INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department 
                INNER JOIN inUseTask AS i ON s.superID = i.superID 
            SET p.inUse = 0
            WHERE UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.last_sold) > i.time
                AND p.store_id = ?;
        ');
        $updateUse = $dbc->prepare('
            UPDATE products p
                INNER JOIN MasterSuperDepts AS s ON s.dept_ID = p.department 
                INNER JOIN inUseTask AS i ON s.superID = i.superID 
            SET p.inUse = 1
            WHERE UNIX_TIMESTAMP(CURDATE()) - UNIX_TIMESTAMP(p.last_sold) < i.time
                AND p.store_id = ?;
        ');
        $dbc->execute($updateUnuse,1);
        $dbc->execute($updateUnuse,2);
        $dbc->execute($updateUse,1);
        $dbc->execute($updateUse,2);
        
        $data = '';        
        while ($row = $dbc->fetch_row($resultA)) {
            $inUseData .= $row['upc'] . "\t" . $row['last_sold'] . "\t" . $row['store_id'] . "\r\n";
        }
        
        while ($row = $dbc->fetch_row($resultB)) {
            $unUseData .= $row['upc'] . "\t" . $row['last_sold'] . "\t" . $row['store_id'] . "\r\n";
        }
        
		$date = date('Y-m-d h:i:s');
        $to = 'csather@wholefoods.coop';
        $headers = "from: automail@wholfoods.coop";
        $msg = '';
		$msg .= 'In Use Task (Product In-Use Management) ran '.$date."\r\n";
		$msg .= "\r\n";
        $msg .= 'Items removed from use' . "\r\n";
        $msg .= $unUseData;
        $msg .= "\r\n";
        $msg .= 'Items added to use' . "\r\n";
        $msg .= $inUseData;
        $msg .= "\r\n";
        $msg .= "\r\n";
        
        mail($to,'Report: In Use Task',$msg,$headers);
        
    }
    
}
