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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class OutdatedProductFinder extends FanniePage
{
    public $description = '[Outdated Products] Finds products not sold in over 12 months, marks them as not-in-use';
    public $report_set = 'Scan Tools';

    protected $report_headers = array('Items');
    protected $sort_direction = 1;
    protected $title = "Fannie : Outdated Product Finder";
    protected $header = "Outdated Product Finder";

    public function body_content()
    {        
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = array();
        $check = array();
        $datetime = date('Y-m-d', strtotime('1 year ago'));
   
        // Find Items not in use in past 12 months
        $query = "SELECT upc, last_sold 
                FROM products 
                WHERE last_sold < '{$datetime}' and inUse =1
                GROUP BY upc;
                ";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            $upc[] = $row['upc'];
        }
        ob_start();
        print count($upc) . " items found that are in use and have not been sold in over 1 year.<br><br>";

        // CORE shortand for isset($_GET['apply']) || isset($_POST['apply'])
        if (FormLib::get('apply')) {
            // Change found items to 'not-in-use'
            $prep = $dbc->prepare('
                UPDATE products
                SET inUse=0
                WHERE upc=?');
            for ($i=0; $i<count($upc); $i++) {
                $dbc->execute($prep, array($upc[$i]));
            }
            
            // Check to see if the script made changes
            $query = "SELECT upc, last_sold 
                    FROM products 
                    WHERE last_sold < '{$datetime}' and inUse = 1
                    GROUP BY upc;
                    ";
            $result = $dbc->query($query);
            while ($row = $dbc->fetch_row($result)) {
                $check[] = $row['upc'];
            }
            print count($check) . " there are now items found that are in use and have not been sold in over 1 year.<br>";
            print "If this number is greater than zero, this script did not work<br>";
        } else {
            print '<p>
                <a href="OutdatedProductFinder.php?apply=1"
                    class="btn btn-default">Mark ' . count($upc) . ' Items Not In Use</a>
                </p>';
        }

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }

    public function helpContent()
    {
        return '<p>Any items that have not sold in a year or more can be marked no longer in use.
This is a very blunt instrument. The <em>Product Last-Sold Maintenance</em> task must be enabled
for this to work.</p>';
    }
}

FannieDispatch::conditionalExec();

