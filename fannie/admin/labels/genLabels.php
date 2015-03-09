<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    require_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}

$layout = FormLib::get_form_value('layout',$FANNIE_DEFAULT_PDF);
$layout = str_replace(" ","_",$layout);
$offset = (isset($_REQUEST['offset']) && is_numeric($_REQUEST['offset']))?$_REQUEST['offset']:0;
$offset = FormLib::get_form_value('offset',0);
$data = array();

$id = FormLib::get_form_value('id',False);
$batchID = FormLib::get_form_value('batchID',False);

$dbc = FannieDB::get($FANNIE_OP_DB);

if ($id !== False){
    $query = $dbc->prepare_statement("SELECT s.*,p.scale,p.numflag
        FROM shelftags AS s
        INNER JOIN products AS p ON s.upc=p.upc
        WHERE s.id=? ORDER BY
        p.department,s.upc");
    $result = $dbc->exec_statement($query,array($id));

    while ($row = $dbc->fetch_row($result)) {
        $count = 1;
        // add multiples of the give tag if requested
        if (isset($row['count']) && $row['count'] > 0) {
            $count = $row['count'];
        }
        for ($i=0; $i<$count; $i++) {
            $myrow = array(
                'normal_price' => $row['normal_price'],
                'description' => $row['description'],
                'brand' => $row['brand'],
                'units' => $row['units'],
                'size' => $row['size'],
                'sku' => $row['sku'],
                'pricePerUnit' => $row['pricePerUnit'],
                'upc' => $row['upc'],
                'vendor' => $row['vendor'],
                'scale' => $row['scale'],
                'numflag' => $row['numflag']
            );          
            $data[] = $myrow;
        }
    }
}
elseif ($batchID !== False){
    $batchIDList = '';
    $args = array();
    foreach($batchID as $x){
        $batchIDList .= '?,';
        $args[] = $x;
    }
    $batchIDList = substr($batchIDList,0,strlen($batchIDList)-1);
    $testQ = $dbc->prepare_statement("select b.*,p.scale,p.numflag
        FROM batchBarcodes as b INNER JOIN products AS p
        ON b.upc=p.upc
        WHERE batchID in ($batchIDList) and b.description <> ''
        ORDER BY batchID");
    $result = $dbc->exec_statement($testQ,$args);
    while($row = $dbc->fetch_row($result)){
        $myrow = array(
        'normal_price' => $row['normal_price'],
        'description' => $row['description'],
        'brand' => $row['brand'],
        'units' => $row['units'],
        'size' => $row['size'],
        'sku' => $row['sku'],
        'pricePerUnit' => '',
        'upc' => $row['upc'],
        'vendor' => $row['vendor'],
        'scale' => $row['scale'],
        'numflag' => $row['numflag']
        );          
        $data[] = $myrow;
    }

}

include("pdf_layouts/".$layout.".php");
$layout($data,$offset);

?>
