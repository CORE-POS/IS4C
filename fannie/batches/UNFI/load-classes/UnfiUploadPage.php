<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class UnfiUploadPage extends FannieUploadPage {

    public $title = "Fannie - UNFI Prices";
    public $header = "Upload UNFI price file";

    public $description = '[UNFI Catalog Import] specialized vendor import tool. Column choices
    default to UNFI price file layout.';

    protected $preview_opts = array(
        'upc' => array(
            'name' => 'upc',
            'display_name' => 'UPC *',
            'default' => 14,
            'required' => True
        ),
        'srp' => array(
            'name' => 'srp',
            'display_name' => 'SRP *',
            'default' => 16,
            'required' => True
        ),
        'brand' => array(
            'name' => 'brand',
            'display_name' => 'Brand *',
            'default' => 2,
            'required' => True
        ),
        'desc' => array(
            'name' => 'desc',
            'display_name' => 'Description *',
            'default' => 6,
            'required' => True
        ),
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU *',
            'default' => 1,
            'required' => true
        ),
        'qty' => array(
            'name' => 'qty',
            'display_name' => 'Case Qty *',
            'default' => 3,
            'required' => True
        ),
        'size' => array(
            'name' => 'size',
            'display_name' => 'Unit Size',
            'default' => 4,
            'required' => False
        ),
        'cost' => array(
            'name' => 'cost',
            'display_name' => 'Case Cost (Reg) *',
            'default' => 8,
            'required' => True
        ),
        'saleCost' => array(
            'name' => 'saleCost',
            'display_name' => 'Case Cost (Sale)',
            'default' => 12,
            'required' => false
        ),
        'cat' => array(
            'name' => 'cat',
            'display_name' => 'UNFI Category # *',
            'default' => 5,
            'required' => True
        )
    );

    protected $use_splits = false;
    protected $use_js = true;

    function process_file($linedata){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $idP = $dbc->prepare_statement("SELECT vendorID FROM vendors WHERE vendorName='UNFI' ORDER BY vendorID");
        $idR = $dbc->exec_statement($idP);
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return False;
        }
        $VENDOR_ID = array_pop($dbc->fetch_row($idR));

        $SKU = $this->get_column_index('sku');
        $BRAND = $this->get_column_index('brand');
        $DESCRIPTION = $this->get_column_index('desc');
        $QTY = $this->get_column_index('qty');
        $SIZE1 = $this->get_column_index('size');
        $UPC = $this->get_column_index('upc');
        $CATEGORY = $this->get_column_index('cat');
        $REG_COST = $this->get_column_index('cost');
        $NET_COST = $this->get_column_index('saleCost');
        $SRP = $this->get_column_index('srp');

        // PLU items have different internal UPCs
        // map vendor SKUs to the internal PLUs
        $SKU_TO_PLU_MAP = array();
        $skusP = $dbc->prepare_statement('SELECT sku, upc FROM vendorSKUtoPLU WHERE vendorID=?');
        $skusR = $dbc->execute($skusP, array($VENDOR_ID));
        while($skusW = $dbc->fetch_row($skusR)) {
            $SKU_TO_PLU_MAP[$skusW['sku']] = $skusW['upc'];
        }

        $extraP = $dbc->prepare_statement("update prodExtra set cost=? where upc=?");
        $itemP = $dbc->prepare_statement("INSERT INTO vendorItems 
                    (brand,sku,size,upc,units,cost,description,vendorDept,vendorID)
                    VALUES (?,?,?,?,?,?,?,?,?)");
        $vi_def = $dbc->table_definition('vendorItems');
        if (isset($vi_def['saleCost'])) {
            $itemP = $dbc->prepare_statement("INSERT INTO vendorItems 
                        (brand,sku,size,upc,units,cost,description,vendorDept,vendorID,saleCost)
                        VALUES (?,?,?,?,?,?,?,?,?,?)");
        }
        /** deprecating unfi_* structures 22Jan14
        $uuP = $dbc->prepare_statement("INSERT INTO unfi_order 
                    (unfi_sku,brand,item_desc,pack,pack_size,upcc,cat,wholesale,vd_cost,wfc_srp) 
                    VALUES (?,?,?,?,?,?,?,?,?,?)");
        */
        $srpP = $dbc->prepare_statement("INSERT INTO vendorSRPs (vendorID, upc, srp) VALUES (?,?,?)");

        /** deprecating unfi_* structures 22Jan14
        $dupeP = $dbc->prepare_statement("SELECT upcc FROM unfi_order WHERE upcc=?");
        */

        foreach($linedata as $data){
            if (!is_array($data)) continue;

            if (!isset($data[$UPC])) continue;

            // grab data from appropriate columns
            $sku = ($SKU !== false) ? $data[$SKU] : '';
            $sku = str_pad($sku, 7, '0', STR_PAD_LEFT);
            $brand = $data[$BRAND];
            $description = $data[$DESCRIPTION];
            $qty = $data[$QTY];
            $size = ($SIZE1 !== false) ? $data[$SIZE1] : '';
            $upc = substr($data[$UPC],0,13);
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000")
                continue;
            if (isset($SKU_TO_PLU_MAP[$sku])) {
                $upc = $SKU_TO_PLU_MAP[$sku];
            }
            $category = $data[$CATEGORY];
            $reg = trim($data[$REG_COST]);
            $net = ($NET_COST !== false) ? trim($data[$NET_COST]) : 0.00;
            // blank spreadsheet cell
            if (empty($net)) {
                $net = 0;
            }
            $srp = trim($data[$SRP]);
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg) or empty($srp))
                continue;

            /** deprecating unfi_* structures 22Jan14
            // don't repeat items
            $dupeR = $dbc->exec_statement($dupeP,array($upc));
            if ($dbc->num_rows($dupeR) > 0) continue;
            */

            // syntax fixes. kill apostrophes in text fields,
            // trim $ off amounts as well as commas for the
            // occasional > $1,000 item
            $brand = str_replace("'","",$brand);
            $description = str_replace("'","",$description);
            $reg = str_replace('$',"",$reg);
            $reg = str_replace(",","",$reg);
            $net = str_replace('$',"",$net);
            $net = str_replace(",","",$net);
            $srp = str_replace('$',"",$srp);
            $srp = str_replace(",","",$srp);

            // sale price isn't really a discount
            if ($reg == $net) {
                $net = 0;
            }

            // skip the item if prices aren't numeric
            // this will catch the 'label' line in the first CSV split
            // since the splits get returned in file system order,
            // we can't be certain *when* that chunk will come up
            if (!is_numeric($reg) or !is_numeric($srp))
                continue;

            // need unit cost, not case cost
            $reg_unit = $reg / $qty;
            $net_unit = $net / $qty;

            // set cost in $PRICEFILE_COST_TABLE
            $dbc->exec_statement($extraP, array($reg_unit,$upc));
            ProductsModel::update($upc, array('cost'=>$reg_unit), True);
            // end $PRICEFILE_COST_TABLE cost tracking

            $args = array($brand,($sku===False?'':$sku),($size===False?'':$size),
                    $upc,$qty,$reg_unit,$description,$category,$VENDOR_ID);
            if (isset($vi_def['saleCost'])) {
                $args[] = $net_unit;
            }
            $dbc->exec_statement($itemP,$args);

            /** deprecating unfi_* structures 22Jan14
            // unfi_order is what the UNFI price change page builds on,
            // that's why it's being populated here
            // it's just a table containing all items in the current order
            $args = array(($sku===False?'':$sku),$brand,$description,$qty,
                    ($size===False?'':$size),$upc,$category,$reg,
                    $reg,$srp);
            $dbc->exec_statement($uuP,$args);
            */

            $dbc->exec_statement($srpP,array($VENDOR_ID,$upc,$srp));
        }

        return True;
    }

    /* clear tables before processing */
    function split_start(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $idP = $dbc->prepare_statement("SELECT vendorID FROM vendors WHERE vendorName='UNFI' ORDER BY vendorID");
        $idR = $dbc->exec_statement($idP);
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return False;
        }
        $VENDOR_ID = array_pop($dbc->fetch_row($idR));

        $viP = $dbc->prepare_statement("DELETE FROM vendorItems WHERE vendorID=?");
        $vsP = $dbc->prepare_statement("DELETE FROM vendorSRPs WHERE vendorID=?");
        /** deprecating unfi_* structures 22Jan14
        $uoP = $dbc->prepare_statement("TRUNCATE TABLE unfi_order");
        $dbc->exec_statement($uoP);
        */
        $dbc->exec_statement($viP,array($VENDOR_ID));
        $dbc->exec_statement($vsP,array($VENDOR_ID));
    }

    function preview_content(){
        return '';  
        return '<input type="checkbox" name="rm_cds" checked /> Remove check digits';
    }

    function results_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = "Price data import complete<p />";
        $ret .= '<a href="'.$_SERVER['PHP_SELF'].'">Upload Another</a>';

        /** Changed 22Jan14
            PLU substitution by SKU happens during import
            This is more consistent in updating all instances
            of a given item UPC
        if ($dbc->table_exists("vendorSKUtoPLU")){

            $idP = $dbc->prepare_statement("SELECT vendorID FROM vendors WHERE vendorName='UNFI' ORDER BY vendorID");
            $idR = $dbc->exec_statement($idP);
            $VENDOR_ID=0;
            if ($dbc->num_rows($idR) > 0)
                $VENDOR_ID = array_pop($dbc->fetch_row($idR));

            $pluQ1 = $dbc->prepare_statement("UPDATE unfi_order AS u
                INNER JOIN vendorSKUtoPLU AS p
                ON u.unfi_sku = p.sku
                SET u.upcc = p.upc
                WHERE p.vendorID=?");
            $pluQ2 = $dbc->prepare_statement("UPDATE vendorItems AS u
                INNER JOIN vendorSKUtoPLU AS p
                ON u.sku = p.sku
                SET u.upc = p.upc
                WHERE u.vendorID=?");
            $pluQ3 = $dbc->prepare_statement("UPDATE prodExtra AS x
                INNER JOIN vendorSKUtoPLU AS p
                ON x.upc=p.upc
                INNER JOIN unfi_order AS u
                ON u.unfi_sku=p.sku
                SET x.cost = u.vd_cost / u.pack
                WHERE p.vendorID=?");
            $args = array($VENDOR_ID);
            $args2 = array($VENDOR_ID); // kludge
            if ($FANNIE_SERVER_DBMS == "MSSQL"){
                $pluQ1 = $dbc->prepare_statement("UPDATE unfi_order SET upcc = p.wfc_plu
                    FROM unfi_order AS u RIGHT JOIN
                    UnfiToPLU AS p ON u.unfi_sku = p.unfi_sku
                    WHERE u.unfi_sku IS NOT NULL");
                $pluQ2 = $dbc->prepare_statement("UPDATE vendorItems SET upc = p.wfc_plu
                    FROM vendorItems AS u RIGHT JOIN
                    UnfiToPLU AS p ON u.sku = p.unfi_sku
                    WHERE u.sku IS NOT NULL
                    AND u.vendorID=?");
                $pluQ3 = $dbc->prepare_statement("UPDATE prodExtra
                    SET cost = u.vd_cost / u.pack
                    FROM UnfiToPLU AS p LEFT JOIN
                    unfi_order AS u ON p.unfi_sku = u.unfi_sku
                    LEFT JOIN prodExtra AS x
                    ON p.wfc_plu = x.upc");
                $args = array();
            }
            $dbc->exec_statement($pluQ1,$args);
            $dbc->exec_statement($pluQ2,$args2);
            $dbc->exec_statement($pluQ3,$args);
        }
        */

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

