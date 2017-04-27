<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class LikeCodePriceUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Upload Likecode Prices";
    protected $header = "Upload Likecode Prices";

    public $description = '[Like Code Prices] uploads a spreadsheet of like codes and prices
    and immediately updates the prices for those like coded items.';

    protected $preview_opts = array(
        'likecode' => array(
            'display_name' => 'Like Code #',
            'default' => 0,
            'required' => true
        ),
        'description' => array(
            'display_name' => 'Description',
            'default' => 1,
        ),
        'price' => array(
            'display_name' => 'Price',
            'default' => 2,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Cost (Unit)',
            'default' => 3,
        ),
        'scale' => array(
            'display_name' => 'LB / EA',
            'default' => 4,
        ),
        'department' => array(
            'display_name' => 'Department',
            'default' => 5,
        ),
        'wicable' => array(
            'display_name' => 'WIC',
            'default' => 6,
        ),
        'numflag' => array(
            'display_name' => 'Organic',
            'default' => 7,
        ),
        'local' => array(
            'display_name' => 'Local',
            'default' => 8,
        ),
    );

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = true;
        $getUPCs = $dbc->prepare("SELECT upc FROM upcLike WHERE likeCode=?");
        $getItem = $dbc->prepare('SELECT description, normal_price, cost, department, numflag, scale, wicable, local FROM products WHERE upc=?');
        $getAttr = $dbc->prepare('SELECT attributes FROM ProductAttributes WHERE upc=? ORDER BY modified DESC');
        $setAttr = $dbc->prepare('INSERT INTO ProductAttributes (upc, modified, attributes) VALUES (?, ?, ?)');

        // build update query based on selected columns
        $update = "UPDATE products SET normal_price=?";
        $updateCols = array();
        $numflag = false;
        if ($indexes['cost'] !== false) {
            $update .= ', cost=?';
            $updateCols[] = 'cost';
        }
        if ($indexes['department'] !== false) {
            $update .= ', department=?';
            $updateCols[] = 'department';
        }
        if ($indexes['scale'] !== false) {
            $update .= ', scale=?';
            $updateCols[] = 'scale';
        }
        if ($indexes['wicable'] !== false) {
            $update .= ', wicable=?';
            $updateCols[] = 'wic';
        }
        if ($indexes['description'] !== false) {
            $update .= ', description=?';
            $updateCols[] = 'description';
        }
        if ($indexes['local'] !== false) {
            $update .= ', local=?';
            $updateCols[] = 'local';
        }
        if ($indexes['numflag'] !== false) {
            $update .= ', numflag=?';
            $numflag = true;
        }
        $update .= ' WHERE upc=?';
        
        $this->stats = array('done' => 0, 'error' => array());
        $upcs = array();
        $sets = array();
        $dbc->startTransaction();
        foreach ($linedata as $line) {
            $likecode = trim($line[$indexes['likecode']]);
            $price =  trim($line[$indexes['price']], ' $');  
            
            if (!is_numeric($likecode)) continue; // skip header(s) or blank rows

            $upcR = $dbc->execute($getUPCs, array($likecode));
            // compare each item in the likecode to
            // the uploaded values to flag which UPCs
            // are actually changing
            while ($upcW = $dbc->fetchRow($upcR)) {
                $changed = false;
                $args = array($price);
                $item = $dbc->getRow($getItem, array($upcW['upc']));
                foreach ($updateCols as $col) {

                    $normalize = trim(strtoupper($line[$indexes[$col]]));
                    if ($normalize === 'Y' || $normalize === 'YES' || $normalize === 'X') {
                        $line[$indexes[$col]] = 1;
                    } elseif ($normalize === 'N' || $normalize === 'NO' || $normalize === '') {
                        $line[$indexes[$col]] = 0;
                    }

                    $args[] = $line[$indexes[$col]];
                    if ($line[$indexes[$col]] != $item[$col]) {
                        $changed = true;
                    }
                }

                // numflag needs special handling since we're only
                // dealing with organic. This sets or unsets the 
                // appropriate bit in numflag then if necessary
                // adds a new record to ProductAttributes
                if ($numflag) {
                    $current = $item['numflag'];
                    $flag = (1 << 16);
                    if ($line[$indexes['numflag']]) {
                        $new = $current | (1 << 16);
                    } else {
                        $new = $current & (~$flag);
                    }
                    $args[] = $new;
                    if ($current != $new) {
                        $changed = true;
                        $attr = $dbc->getRow($getAttr, array($upcW['upc']));
                        $newAttrs = json_decode($attr['attributes'], true);
                        $newAttrs['Organic'] = ($line[$indexes['numflag']]) ? true : false;
                        $dbc->execute($setAttr, array($upcW['upc'], date('Y-m-d H:i:s'), json_encode($newAttrs)));
                    }
                }

                $args[] = $upcW['upc'];
                $dbc->execute($update, $args);
                if ($changed) {
                    $upcs[] = $upcW['upc'];
                    $sets[] = array($price, $upcW['upc']);
                }
            }
            $this->stats['done']++;
        }

        // log the updates
        $model = new ProdUpdateModel($dbc);
        $model->logManyUpdates($upcs, 'EDIT');
        $dbc->commitTransaction();

        // push updates to local lanes
        $FANNIE_LANES = FannieConfig::config('LANES');
        for ($i = 0; $i < count($FANNIE_LANES); $i++) {
            $lane_sql = new SQLManager($FANNIE_LANES[$i]['host'],$FANNIE_LANES[$i]['type'],
                $FANNIE_LANES[$i]['op'],$FANNIE_LANES[$i]['user'],
                $FANNIE_LANES[$i]['pw']);
            
            if (!isset($lane_sql->connections[$FANNIE_LANES[$i]['op']]) || $lane_sql->connections[$FANNIE_LANES[$i]['op']] === false) {
                // connect failed
                continue;
            }
            $upP = $lane_sql->prepare('UPDATE products SET normal_price=? WHERE upc=?');
            foreach ($sets as $set) {
                $upR = $lane_sql->execute($upP, $set);
            }
        }

        // push updates to other stores
        if (FannieConfig::config('STORE_MODE') === 'HQ' && class_exists('\\Datto\\JsonRpc\\Http\\Client')) {
            $prep = $this->connection->prepare('
                SELECT webServiceUrl FROM Stores WHERE hasOwnItems=1 AND storeID<>?
                ');
            $res = $this->connection->execute($prep, array(\FannieConfig::config('STORE_ID')));
            while ($row = $this->connection->fetchRow($res)) {
                $client = new \Datto\JsonRpc\Http\Client($row['webServiceUrl']);
                $client->query(time(), 'COREPOS\\Fannie\\API\\webservices\\FannieItemLaneSync', array('upc'=>$upcs, 'fast'=>true));
                $client->send();
            }
        }

        return $ret;
    }

    function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing likecode #s and prices. Cost 
        may also optionally be included.
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function results_content()
    {
        \COREPOS\Fannie\API\data\SyncLanes::pushTable('products');
        $ret = '<p>Import Complete</p>';
        $ret .= '<div class="alert alert-success">Updated ' . $this->stats['done'] . ' likecodes</div>';
        if (count($this->stats['error']) > 0) {
            $ret .= '<div class="alert alert-danger"><ul>';
            foreach ($this->stats['error'] as $error) {
                $ret .= '<li>' . $error . '</li>';
            }
            $ret .= '</ul></div>';
        }

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $data = array(1, 1.99, 0.99, 'foo', 1, 1, 0, 1, 0);
        $indexes = array('likecode'=>0, 'price'=>1, 'cost'=>2, 'description'=>3, 
            'scale'=>4, 'department'=>5, 'wicable'=>6, 'numflag'=>7, 'local'=>8);
        $this->process_file(array($data), $indexes);
    }
}

FannieDispatch::conditionalExec();

