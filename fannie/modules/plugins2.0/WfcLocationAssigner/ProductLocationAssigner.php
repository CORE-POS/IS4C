<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ProductLocationAssigner extends FanniePage
{
    public $description = '[Location Assigner] assigns FloorSectionID to items without';
    public $report_set = 'Scan Tools';
    public $themed = true;

    protected $title = "Fannie : Product Location Assign";
    protected $header = "Product Location Assign";

    public function body_content()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        ob_start();
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = array();
        $floorSectionID = array();
   
        // Find Items not in use in past 12 months
        $query = "SELECT p.upc, l.floorSectionID, d.dept_name, p.department
                FROM products AS p
                LEFT JOIN prodPhysicalLocation AS l ON l.upc=p.upc
                LEFT JOIN departments as d ON d.dept_no=p.department
                WHERE l.floorSectionID IS NULL and p.inUse > 0
                ;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            //bulk 1
            if($row['department']>=1 && $row['department']<=17){
                $upc[] = $row['upc'];
                $floorSectionID[] = 16;
            }
            
            // Cool 1
            if ($row['department']==39 || $row['department']==40 || $row['department']==44 || $row['department']==45) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 11;
            }
            
            // Cool 2
            if ($row['department']==38 || $row['department']==41 || $row['department']==42){
                $upc[] = $row['upc'];
                $floorSectionID[] = 13;
            }
            
            // Cool 3
            if ($row['department']==32 || $row['department']==35 || $row['department']==37 ) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 14;
            }
            
            // Cool 4
            if ( ($row['department']>=26 && $row['department']<=31)
                || ($row['department']==34 ) ) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 15;
            }
            
            // Grocery 1
            if( ($row['department']>=170 && $row['department']<=173)
                || ($row['department']==160 ) || ($row['department']==169) ) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 1;
            }
            
            // Grocery 2
            if( ($row['department']==156) || ($row['department']==161) || ($row['department']==163)
                || ($row['department']==166) || ($row['department']==172) || ($row['department']==174)
                || ($row['department']==175) || ($row['department']==177) ) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 2;
            }
            
            // Grocery 3
            if( ($row['department']==153) || ($row['department']==157)
                || ($row['department']==164) || ($row['department']==167) || ($row['department']==168)
                || ($row['department']==176) ) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 3;
            }
            
            // Grocery 4
            if( ($row['department']==151) || ($row['department']==152) ) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 4;
            }
            
            // Grocery 5
            if( ($row['department']==159) || ($row['department']==155) ) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 5;
            }
            
            // Grocery 6
            if($row['department']==158) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 6;
            }
            
            // Grocery 7
            if($row['department']==165) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 7;
            }
            
            // Grocery 8
            if($row['department']==159 || $row['department']==162 || $row['department']==179) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 8;
            }
            
            // Wellness 1
            if($row['department']==88 || $row['department']==90 || $row['department']==95 ||
                $row['department']==96 || $row['department']==98 || $row['department']==99 ) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 9;
            }
            
            // Wellness 2
            if($row['department']==86 ||$row['department']==87 || $row['department']==89 ||
                $row['department']==90 || $row['department']==90 || $row['department']==91 ||
                $row['department']==94 || $row['department']==97 || $row['department']==102) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 10;
            }
            
            // Wellness 3
            if($row['department']==101 || ($row['department']>=105 && $row['department']<=109) ) {
                $upc[] = $row['upc'];
                $floorSectionID[] = 12;
            }
        }
        if ($dbc->error()) {
            echo ":" . $dbc->error();
        }
        
        $floorsectionP = $dbc->prepare("
            UPDATE prodPhysicalLocation
            SET floorSectionID=?
            WHERE upc=?;");
        $existsP = $dbc->prepare('SELECT upc FROM prodPhysicalLocation WHERE upc=?');
        $addP = $dbc->prepare('INSERT INTO prodPhysicalLocation (upc, floorSectionID) VALUES (?, ?)');
        for($i=0; $i<count($upc); $i++) {
            $exists = $dbc->execute($existsP, array($upc[$i]));
            if ($exists && $dbc->numRows($exists) > 0) {
                $floorsectionR = $dbc->execute($floorsectionP, array($floorSectionID[$i], $upc[$i]));
            } else {
                $floorsectionR = $dbc->execute($addP, array($upc[$i], $floorSectionID[$i]));
            }
        }

        if ($dbc->error()) {
            echo $dbc->error();
        } elseif (count($upc) > 0) {
            echo count($upc) . " item locations have been updated.";
        } else {
            echo "There were no items found missing floor locations.";
        }       

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

