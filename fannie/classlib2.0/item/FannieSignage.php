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

namespace COREPOS\Fannie\API\item;
use COREPOS\Fannie\API\FanniePlugin;
use COREPOS\Fannie\API\lib\PriceLib;
use COREPOS\Fannie\API\lib\Store;
use \BarcodeLib;
use \DTrans;
use \FannieConfig;
use \FannieDB;
use \FormLib;
use \ProductsModel;
use \ProductUserModel;
use \OriginsModel;
use \ShelftagsModel;

class FannieSignage 
{
    protected $items = array();
    protected $source = '';
    protected $source_id = 0;
    protected $data = array();
    protected $overrides = array();
    protected $excludes = array();
    protected $in_use_filter = 0;
    protected $repeats = 1;

    protected $width;
    protected $height;
    protected $top;
    protected $left;

    /**
      constructor
      @param $items [array] of upcs
      @param $source [optional] string shelftags, batchbarcodes, batch, or empty.
        - shelftags => data is in shelftags table
        - batchbarcodes => data is in batchBarcodes table
        - batch => get data from normal product and vendor tables but
            use batch(es) for price
        - provided => $items contains all necessary data
        - empty => get data from normal product and vendor tables
      @param $source_id [optional]
        - for shelftags, shelftags.id
        - for batchbarcodes, array of batchIDs
        - for batch, array of batchIDs
        - for empty:
            0 => use current price
            1 => use upcoming retail from price change batch
            2 => use current sale price
            3 => use upcoming sale price from sale batch
    */
    public function __construct($items, $source='', $source_id=0)
    {
        $this->items = $items;
        $this->source = strtolower($source);
        $this->source_id = $source_id;
    }

    protected $connection = null;

    public function setDB($dbc)
    {
        $this->connection = $dbc;
    }

    public function setInUseFilter($store)
    {
        $this->in_use_filter = $store;
    }

    public function setRepeats($repeats)
    {
        $this->repeats = $repeats;
    }

    protected function getDB()
    {
        if (!is_object($this->connection)) {
            $op_db = FannieConfig::factory()->get('OP_DB');
            $this->connection = FannieDB::get($op_db);
        }
        return $this->connection;
    }

    public function loadItems()
    {
        if ($this->source == 'provided') {
            return $this->items;
        }

        $dbc = $this->getDB();
        if ($this->source == 'shelftags') {
            $sql = $this->listFromShelftags();
        } elseif ($this->source == 'batchbarcodes') {
            $sql = $this->listFromBatchBarcodes();
        } else if ($this->source == 'batch') {
            $sql = $this->listFromBatches($dbc);
        } else {
            $sql = $this->listFromCurrentRetail($dbc);
            if ($this->source_id == 1) { // upcoming retail
                $sql = $this->listFromUpcomingRetail($dbc);
            } elseif ($this->source_id == 2) { // current sale
                $sql = $this->listFromCurrentSale($dbc);
            } elseif ($this->source_id == 3) { // current sale
                $sql = $this->listFromUpcomingSale($dbc);
            }

            $u_def = $dbc->tableDefinition('productUser');
            if (isset($u_def['signCount'])) {
                $sql['query'] = str_replace('p.upc,', 'p.upc, u.signCount,', $sql['query']);
            } else {
                $sql['query'] = str_replace('p.upc,', 'p.upc, 1 AS signCount,', $sql['query']);
            }
        }

        $data = array();
        $prep = $dbc->prepare($sql['query']);
        $result = $dbc->execute($prep, $sql['args']);
        $lastUPC = null;

        $mapP = $dbc->prepare('SELECT o.name, o.shortName
                               FROM ProductOriginsMap AS m
                                INNER JOIN origins AS o ON m.originID=o.originID
                               WHERE
                                m.upc = ?
                                AND o.name <> ?
                                AND o.shortName <> ?');
        if ($this->in_use_filter) {
            $useP = $dbc->prepare('SELECT inUse FROM products WHERE upc=? AND store_id=?');
        }

        while ($row = $dbc->fetch_row($result)) {

            if ($row['upc'] == $lastUPC) {
                continue;
            }

            if (substr($row['upc'], 0, 2) == 'LC') {
                $row = $this->unrollLikeCode($dbc, substr($row['upc'], 2), $row);
            }

            if (in_array($row['upc'], $this->excludes)) {
                continue;
            }

            if ($this->in_use_filter && !$dbc->getValue($useP, array($row['upc'], $this->in_use_filter))) {
                continue;
            }

            if ($row['unitofmeasure'] && is_numeric($row['size'])) {
                $row['size'] .= $row['unitofmeasure'];
            }
            if ($row['pricePerUnit'] == '') {
                $row['pricePerUnit'] = PriceLib::pricePerUnit($row['normal_price'], $row['size']);
            }
            if ($row['sku'] == $row['upc']) {
                $row['sku'] = '';
            }
            if (!isset($row['signMultiplier'])) {
                $row['signMultiplier'] = 1;
            }

            if ($row['originName'] != '') {
                // check for additional origins
                $mapR = $dbc->execute($mapP, array($row['upc'], $row['originName'], $row['originShortName']));
                while ($mapW = $dbc->fetch_row($mapR)) {
                    $row['originName'] .= _(' and ') . $mapW['name'];
                    $row['originShortName'] .= _(' and ') . $mapW['shortName'];
                }
            }

            if (isset($this->overrides[$row['upc']])) {
                foreach ($this->overrides[$row['upc']] as $key => $val) {
                    if ($key == 'originName' && $val != $row['originName']) {
                        $row['originShortName'] = $val;
                    }
                    $row[$key] = $val;
                }
            }

            if (!isset($row['signCount']) || $row['signCount'] < 0) {
                $row['signCount'] = 1;
            }
            for ($i=0; $i<$row['signCount']*$this->repeats; $i++) {
                $data[] = $row;
            }

            $lastUPC = $row['upc'];
        }

        return $data;
    }

    protected function listFromShelftags()
    {
        $query = 'SELECT s.upc,
                    s.description,
                    s.brand,
                    s.units,
                    s.size,
                    s.sku,
                    s.pricePerUnit,
                    s.vendor,
                    p.scale,
                    p.numflag,
                    \'\' AS startDate,
                    \'\' AS endDate,
                    \'\' AS batchName,
                    \'\' AS unitofmeasure,
                    o.originID,
                    o.name AS originName,
                    o.shortName AS originShortName
                  FROM shelftags AS s
                    ' . DTrans::joinProducts('s', 'p', 'INNER') . '
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                  WHERE s.id=?
                  ORDER BY p.department, s.upc';
        $args = array($this->source_id);

        return array('query' => $query, 'args' => $args);
    }

    protected function listFromBatchBarcodes()
    {
        if (!is_array($this->source_id)) {
            $this->source_id = array($this->source_id);
        }
        $ids = '';
        foreach ($this->source_id as $id) {
            $args[] = $id;
            $ids .= '?,';
        }
        $ids = substr($ids, 0, strlen($ids)-1);
        $query = 'SELECT s.upc,
                    s.description,
                    s.description AS posDescription,
                    s.brand,
                    s.units,
                    s.size,
                    s.sku,
                    \'\' AS pricePerUnit,
                    \'\' AS unitofmeasure,
                    s.vendor,
                    p.scale,
                    p.numflag,
                    b.startDate,
                    b.batchName,
                    b.endDate,
                    o.originID,
                    o.name AS originName,
                    o.shortName AS originShortName
                  FROM batchBarcodes AS s
                    ' . DTrans::joinProducts('s', 'p', 'INNER') . '
                    INNER JOIN batches AS b ON s.batchID=b.batchID
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                  WHERE s.batchID IN (' . $ids . ')
                  ORDER BY p.department, s.upc';

        return array('query' => $query, 'args' => $args);
    }

    protected function listFromBatches($dbc)
    {
        if (!is_array($this->source_id)) {
            $this->source_id = array($this->source_id);
        }
        $ids = '';
        $args = array();
        foreach ($this->source_id as $id) {
            $args[] = $id;
            $ids .= '?,';
        }
        $ids = substr($ids, 0, strlen($ids)-1);
        $b_def = $dbc->tableDefinition('batchType');
        $l_def = $dbc->tableDefinition('batchList');
        $u_def = $dbc->tableDefinition('productUser');
        $query = 'SELECT l.upc,
                    l.salePrice AS normal_price,
                    p.normal_price AS nonSalePrice,
                    ' . ItemText::longDescriptionSQL() . ',
                    p.description AS posDescription,
                    ' . ItemText::longBrandSQL() . ',
                    v.units,
                    ' . ItemText::signSizeSQL() . ',
                    v.sku,
                    \'\' AS pricePerUnit,
                    n.vendorName AS vendor,
                    p.scale,
                    p.numflag,';
        // 22Jul2015 check table compatibility
        if (isset($b_def['datedSigns'])) {
            $query .= 'CASE 
                        WHEN t.datedSigns=0 AND t.typeDesc LIKE \'%DISCO%\' THEN \'Discontinued\' 
                        WHEN t.datedSigns=0 AND t.typeDesc NOT LIKE \'%DISCO%\' THEN \'While supplies last\' 
                        ELSE b.startDate END AS startDate,';
            $query .= 'CASE 
                        WHEN t.datedSigns=0 AND t.typeDesc LIKE \'%DISCO%\' THEN \'Discontinued\' 
                        WHEN t.datedSigns=0 AND t.typeDesc NOT LIKE \'%DISCO%\' THEN \'While supplies last\' 
                        ELSE b.endDate END AS endDate,';
        } else {
            $query .= 'b.startDate, b.endDate,';
        }
        if (isset($l_def['signMultiplier'])) {
            $query .= 'l.signMultiplier,';
        } else {
            $query .= '1 AS signMultiplier,';
        }
        if (isset($u_def['signCount'])) {
            $query .= 'u.signCount,';
        } else {
            $query .= '1 AS signCount,';
        }
        $query .= ' o.name AS originName,
                    o.originID,
                    o.shortName AS originShortName,
                    p.unitofmeasure,
                    b.batchName,
                    b.batchType
                 FROM batchList AS l
                    ' . DTrans::joinProducts('l', 'p', 'LEFT') . '
                    INNER JOIN batches AS b ON b.batchID=l.batchID
                    LEFT JOIN batchType AS t ON b.batchType=t.batchTypeID
                    LEFT JOIN productUser AS u ON p.upc=u.upc
                    LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                 WHERE l.batchID IN (' . $ids . ') ';
        $query .= ' ORDER BY l.batchID, brand, description';

        return array('query' => $query, 'args' => $args);
    }

    protected function unrollLikeCode($dbc, $code, $item)
    {
        $likeP = $dbc->prepare('
            SELECT u.upc,
                ' . ItemText::longBrandSQL('s', 'p') . ',
                CASE WHEN s.description IS NULL OR s.description=\'\' THEN l.likeCodeDesc ELSE s.description END AS likeCodeDesc,
                p.normal_price,
                p.scale,
                p.numflag,
                p.size,
                COALESCE(s.signCount, 1) AS signCount
            FROM upcLike AS u
                INNER JOIN likeCodes AS l ON u.likeCode=l.likeCode
                ' . DTrans::joinProducts('u', 'p', 'INNER') . '
                LEFT JOIN productUser AS s ON u.upc=s.upc
            WHERE u.likeCode=?
            ORDER BY u.upc
        ');
        $info = $dbc->getRow($likeP, array($code));
        $item['description'] = $info['likeCodeDesc'];
        $item['brand'] = $info['brand'];
        $item['posDescription'] = $info['likeCodeDesc'];
        $item['nonSalePrice'] = $info['normal_price'];
        $item['scale'] = $info['scale'];
        $item['numflag'] = $info['numflag'];
        $item['upc'] = $info['upc'];
        $item['size'] = $info['size'];
        $item['signCount'] = $info['signCount'];

        return $item;
    }

    protected function listFromCurrentRetail($dbc)
    {
        $ids = '';
        $args = array();
        foreach ($this->items as $id) {
            $args[] = $id;
            $ids .= '?,';
        }
        $ids = substr($ids, 0, strlen($ids)-1);
        $query = 'SELECT p.upc,
                    p.normal_price,
                    ' . ItemText::longDescriptionSQL() . ',
                    p.description AS posDescription,
                    ' . ItemText::longBrandSQL() . ',
                    v.units,
                    ' . ItemText::signSizeSQL() . ',
                    v.sku,
                    \'\' AS pricePerUnit,
                    n.vendorName AS vendor,
                    p.scale,
                    p.numflag,
                    \'\' AS startDate,
                    \'\' AS endDate,
                    \'\' AS batchName,
                    p.unitofmeasure,
                    o.originID,
                    o.name AS originName,
                    o.shortName AS originShortName
                 FROM products AS p
                    LEFT JOIN productUser AS u ON p.upc=u.upc
                    LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                 WHERE p.upc IN (' . $ids . ') ';
        if (FannieConfig::config('STORE_MODE') == 'HQ') {
            $query .= ' AND p.store_id=? ';
            $args[] = FannieConfig::config('STORE_ID');
        }
        $query .= 'ORDER BY p.department, p.upc';

        return array('query' => $query, 'args' => $args);
    }

    protected function listFromUpcomingRetail($dbc)
    {
        $ids = '';
        $args = array();
        foreach ($this->items as $id) {
            $args[] = $id;
            $ids .= '?,';
        }
        $ids = substr($ids, 0, strlen($ids)-1);
        $query = 'SELECT p.upc,
                    l.salePrice AS normal_price,
                    ' . ItemText::longDescriptionSQL() . ',
                    p.description AS posDescription,
                    ' . ItemText::longBrandSQL() . ',
                    v.units,
                    ' . ItemText::signSizeSQL() . ',
                    v.sku,
                    \'\' AS pricePerUnit,
                    n.vendorName AS vendor,
                    p.scale,
                    p.numflag,
                    \'\' AS startDate,
                    \'\' AS endDate,
                    \'\' AS batchName,
                    p.unitofmeasure,
                    o.originID,
                    o.name AS originName,
                    o.shortName AS originShortName
                 FROM products AS p
                    LEFT JOIN productUser AS u ON p.upc=u.upc
                    LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                    LEFT JOIN batchList AS l ON p.upc=l.upc
                    LEFT JOIN batches AS b ON l.batchID=b.batchID
                 WHERE p.upc IN (' . $ids . ')
                    AND b.discounttype = 0
                    AND b.startDate >= ' . $dbc->curdate() . ' ';
        if (FannieConfig::config('STORE_MODE') == 'HQ') {
            $query .= ' AND p.store_id=? ';
            $args[] = FannieConfig::config('STORE_ID');
        }
        $query .= 'ORDER BY p.department, p.upc';

        return array('query' => $query, 'args' => $args);
    }

    protected function listFromCurrentSale($dbc)
    {
        $ids = '';
        $args = array();
        foreach ($this->items as $id) {
            $args[] = $id;
            $ids .= '?,';
        }
        $ids = substr($ids, 0, strlen($ids)-1);
        $query = 'SELECT p.upc,
                    CASE WHEN p.discounttype <> 0 THEN p.special_price ELSE p.normal_price END AS normal_price,
                    p.normal_price AS nonSalePrice,
                    ' . ItemText::longDescriptionSQL() . ',
                    p.description AS posDescription,
                    ' . ItemText::longBrandSQL() . ',
                    v.units,
                    ' . ItemText::signSizeSQL() . ',
                    v.sku,
                    \'\' AS pricePerUnit,
                    n.vendorName AS vendor,
                    p.scale,
                    p.numflag,
                    CASE
                        WHEN t.datedSigns=0 AND t.typeDesc LIKE \'%DISCO%\' THEN \'Discontinued\' 
                        WHEN t.datedSigns=0 AND t.typeDesc NOT LIKE \'%DISCO%\' THEN \'While supplies last\' 
                        ELSE p.start_date 
                    END AS startDate,
                    CASE
                        WHEN t.datedSigns=0 AND t.typeDesc LIKE \'%DISCO%\' THEN \'Discontinued\' 
                        WHEN t.datedSigns=0 AND t.typeDesc NOT LIKE \'%DISCO%\' THEN \'While supplies last\' 
                        ELSE p.end_date 
                    END AS endDate,
                    p.unitofmeasure,
                    o.originID,
                    b.batchName,
                    o.name AS originName,
                    o.shortName AS originShortName,
                    CASE WHEN l.signMultiplier IS NULL THEN 1 ELSE l.signMultiplier END AS signMultiplier
                 FROM products AS p
                    LEFT JOIN productUser AS u ON p.upc=u.upc
                    LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                    LEFT JOIN batchList AS l ON p.batchID=l.batchID AND p.upc=l.upc
                    LEFT JOIN batches AS b ON l.batchID=b.batchID
                    LEFT JOIN batchType AS t ON b.batchType=t.batchTypeID
                 WHERE p.upc IN (' . $ids . ') ';
        if (FannieConfig::config('STORE_MODE') == 'HQ') {
            $query .= ' AND p.store_id=? ';
            $args[] = Store::getIdByIp();
        }
        $query .= 'ORDER BY p.department, p.upc';

        return array('query' => $query, 'args' => $args);
    }

    protected function listFromUpcomingSale($dbc)
    {
        $ids = '';
        $args = array();
        foreach ($this->items as $id) {
            $args[] = $id;
            $ids .= '?,';
        }
        $ids = substr($ids, 0, strlen($ids)-1);
        $query = 'SELECT p.upc,
                    l.salePrice AS normal_price,
                    p.normal_price AS nonSalePrice,
                    ' . ItemText::longDescriptionSQL() . ',
                    p.description AS posDescription,
                    ' . ItemText::longBrandSQL() . ',
                    v.units,
                    ' . ItemText::signSizeSQL() . ',
                    v.sku,
                    \'\' AS pricePerUnit,
                    n.vendorName AS vendor,
                    p.scale,
                    p.numflag,
                    CASE
                        WHEN t.datedSigns=0 AND t.typeDesc LIKE \'%DISCO%\' THEN \'Discontinued\' 
                        WHEN t.datedSigns=0 AND t.typeDesc NOT LIKE \'%DISCO%\' THEN \'While supplies last\' 
                        ELSE b.startDate 
                    END AS startDate,
                    CASE
                        WHEN t.datedSigns=0 AND t.typeDesc LIKE \'%DISCO%\' THEN \'Discontinued\' 
                        WHEN t.datedSigns=0 AND t.typeDesc NOT LIKE \'%DISCO%\' THEN \'While supplies last\' 
                        ELSE b.endDate 
                    END AS endDate,
                    p.unitofmeasure,
                    b.batchName,
                    o.originID,
                    o.name AS originName,
                    o.shortName AS originShortName
                 FROM products AS p
                    LEFT JOIN productUser AS u ON p.upc=u.upc
                    LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                    LEFT JOIN batchList AS l ON p.upc=l.upc
                    LEFT JOIN batches AS b ON l.batchID=b.batchID
                    LEFT JOIN batchType AS t ON b.batchType=t.batchTypeID
                 WHERE p.upc IN (' . $ids . ')
                    AND b.discounttype <> 0
                    AND b.startDate > ' . $dbc->now() . ' ';
        if (FannieConfig::config('STORE_MODE') == 'HQ') {
            $query .= ' AND p.store_id=? ';
            $args[] = FannieConfig::config('STORE_ID');
        }
        $query .= 'ORDER BY p.department, p.upc';

        return array('query' => $query, 'args' => $args);
    }

    protected function upcToBitString($upc)
    {
        $code='101'; // start bar
        $parity = BarcodeLib::$PARITIES[$upc[0]]; // parity based on first digit
        for ($i=1;$i<=6;$i++) { // first half
            $code .= BarcodeLib::$CODES[$parity[$i-1]][$upc[$i]];
        }
        $code .= '01010'; // middle bar
        for ($i=7;$i<=12;$i++) { // second half
            $code .= BarcodeLib::$CODES['C'][$upc[$i]];
        }
        $code.='101'; // end bar

        return $code;
    }

    /**
      Draw barcode on given PDF
      @param $upc [string] barcode value (UPC or EAN)
      @param $pdf [object] FPDF instance
      @param $x [numeric] x-coordinate of barcode
      @param $y [numeric] y-coordinate of barcode
      @param $args [keyed array] of extra options
        - height [default 16] height of the barcode
        - width [default 0.35] width of *each* bar
        - align [default C] horizontal alignment of barcode number (L/C/R)
        - valign [default B] vertical alignment of barcode number
            (T, "top", above barcode) or (B, "botton", below barcode)
        - prefix [default empty] prepend value to barcode number
        - suffix [default empty] append value to barcode number
        - font [default Arial] name of font for barcode number
        - fontsize [default 9] size of font for barcode number
    */
    public function drawBarcode($upc, $pdf, $x, $y, $args=array())
    {
        $height = isset($args['height']) ? $args['height'] : 16;
        $width = isset($args['width']) ? $args['width'] : 0.35;
        $align = isset($args['align']) ? $args['align'] : 'C';
        $valign = isset($args['valign']) ? $args['valign'] : 'B';
        $prefix = isset($args['prefix']) ? $args['prefix'] : '';
        $suffix = isset($args['suffix']) ? $args['suffix'] : '';
        $font = isset($args['font']) ? $args['font'] : 'Arial';
        $fontsize = isset($args['fontsize']) ? $args['fontsize'] : 9;
        $vertical = isset($args['vertical']) ? $args['vertical'] : false;

        $upc = ltrim($upc, '0');
        $is_ean = false;
        if (strlen($upc) == 12) { 
            // must be EAN
            $check = BarcodeLib::getCheckDigit($upc);
            $upc .= $check;
            $is_ean = true;
        } else {
            $upc = str_pad($upc, 11, '0', STR_PAD_LEFT);
            $check = BarcodeLib::getCheckDigit($upc);
            $upc = '0' . $upc . $check;
        }

        //Convert digits to bars
        $code = $this->upcToBitString($upc);

        //Draw bars
        $full_width = 0;
        for ($i=0;$i<strlen($code);$i++) {
            if ($code{$i}=='1') {
                if ($vertical) {
                    $pdf->Rect($x, $y+($i*$height), $width, $height, 'F');
                } else {
                    $pdf->Rect($x+($i*$width), $y, $width, $height, 'F');
                }
            }
            $full_width += $width;
        }

        // Print text under barcode
        // omits first digit; should always be zero
        if ($fontsize > 0 && !$vertical) {
            $pdf->SetFont($font, '', $fontsize);
            if ($valign == 'T') {
                $pdf->SetXY($x, $y - 5);
            } else {
                $pdf->SetXY($x, $y + $height);
            }
            $pdf->Cell($full_width, 5, $prefix . substr($upc, ($is_ean?-13:-12)) . $suffix, 0, 0, $align);
        }

        return $pdf;
    }

    public function getOrigins()
    {
        $dbc = $this->getDB();
        $model = new OriginsModel($dbc);
        $origins = array();
        foreach ($model->find('shortName') as $o) {
            $origins[$o->originID()] = $o->shortName();
        }

        return $origins;
    }

    public function listItems()
    {
        // preserve values from re-posting form
        $overrides = array();
        $upc = FormLib::get('update_upc', array());
        $brand = FormLib::get('update_brand', array());
        $desc = FormLib::get('update_desc', array());
        $ignore = FormLib::get('ignore_desc', array());
        for ($i=0; $i<count($upc); $i++) {
            $bOver = isset($brand[$i]) ? $brand[$i] : '';
            $dOver = '';
            if (isset($ignore[$i]) && $ignore[$i] == 0 && isset($desc[$i])) {
                $dOver = $desc[$i];
            }
            $overrides[$upc[$i]] = array('brand' => $bOver, 'desc' => $dOver);
        }
        $excludes = array();
        foreach (FormLib::get('exclude', array()) as $e) {
            $excludes[] = $e;
        }

        $url = FannieConfig::factory()->get('URL');
        $ret = '<table class="table tablesorter tablesorter-core">';
        $ret .= '<thead>';
        $ret .= '<tr>
            <th>UPC</th><th>Brand</th><th>Description</th><th>Price</th><th>Origin</th>
            <td><label>Exclude
                <input type="checkbox" onchange="$(\'.exclude-checkbox\').prop(\'checked\', $(this).prop(\'checked\'));" />
                </label>
            </td>
            </tr>';
        $ret .= '</thead><tbody>';
        $data = $this->loadItems();
        $origins = $this->getOrigins();
        foreach ($data as $item) {
            $oselect = '<select name="update_origin[]" class="FannieSignageField form-control originField"><option value="0"></option>'; 
            foreach ($origins as $id => $name) {
                $oselect .= sprintf('<option %s value="%d">%s</option>',
                    ($id == $item['originID'] ? 'selected' : ''), $id, $name);
            }
            $oselect .= '</select>';
            if (isset($overrides[$item['upc']]) && $overrides[$item['upc']]['brand'] != '') {
                $item['brand'] = $overrides[$item['upc']]['brand'];
            }
            if (isset($overrides[$item['upc']]) && $overrides[$item['upc']]['desc'] != '') {
                $item['desc'] = $overrides[$item['upc']]['desc'];
            }
            $ret .= sprintf('<tr>
                            <td><a href="%sitem/ItemEditorPage.php?searchupc=%s" target="_edit%s">%s</a></td>
                            <input type="hidden" name="update_upc[]" value="%s" />
                            <td>
                                <span class="collapse">%s</span>
                                <input class="FannieSignageField form-control" type="text" 
                                name="update_brand[]" value="%s" /></td>
                            <td>
                                <span class="collapse">%s</span>
                                <input class="FannieSignageField form-control" type="text" 
                                name="update_desc[]" value="%s" />
                                <input type="hidden" name="ignore_desc[]" value="%d" />
                            </td>
                            <td>%.2f</td>
                            <td class="form-inline">%s<input type="text" name="custom_origin[]" 
                                class="form-control FannieSignageField originField" placeholder="Custom origin..." value="" />
                            </td>
                            <td><input type="checkbox" name="exclude[]" class="exclude-checkbox" value="%s" %s /></td>
                            </tr>',
                            $url,
                            $item['upc'], $item['upc'], $item['upc'],
                            $item['upc'],
                            $item['brand'],
                            $item['brand'],
                            str_replace('"', '&quot;', $item['description']),
                            str_replace('"', '&quot;', $item['description']),
                            (strstr($item['description'], "\n") ? 1 : 0),
                            $item['normal_price'],
                            $oselect,
                            $item['upc'],
                            (in_array($item['upc'], $excludes) ? 'checked' : '')
            );
        }
        $ret .= '</tbody></table>';

        return $ret;
    }

    public function updateItem($upc, $brand, $description, $originID)
    {
        switch (strtolower($this->source)) {
            case 'shelftags':
                $this->updateShelftagItem($upc, $brand, $description);
                break;
            case 'batchbarcodes':
                $this->updateBatchBarcodeItem($upc, $brand, $description);
                break;
            case 'batch':
            case '':
                $this->updateRealItem($upc, $brand, $description, $originID);
                break;
        }
    }

    protected function updateShelftagItem($upc, $brand, $description)
    {
        $dbc = $this->getDB();
        $model = new ShelftagsModel($dbc);
        $model->id($this->source_id);
        $model->upc(BarcodeLib::padUPC($upc));
        $model->brand($brand);
        $model->description($description);
        return $model->save();
    }

    protected function updateBatchBarcodeItem($upc, $brand, $description)
    {
        $dbc = $this->getDB();
        $args = array($brand, $description, BarcodeLib::padUPC($upc));
        if (!is_array($this->source_id)) {
            $this->source_id = array($this->source_id);
        }
        $ids = '';
        foreach ($this->source_id as $id) {
            $args[] = $id;
            $ids .= '?,';
        }
        $ids = substr($ids, 0, strlen($ids)-1);
        $prep = $dbc->prepare('UPDATE batchBarcodes
                               SET brand=?,
                                description=?
                               WHERE upc=?
                                AND batchID IN (' . $ids . ')');
        return $dbc->execute($prep, $args);
    }

    protected function updateRealItem($upc, $brand, $description, $originID)
    {
        $dbc = $this->getDB();
        $model = new ProductUserModel($dbc);
        $model->upc(BarcodeLib::padUPC($upc));
        $model->brand($brand);
        $model->description($description);
        $model->save();
        $model = new ProductsModel($dbc);
        $model->upc(BarcodeLib::padUPC($upc));
        foreach ($model->find('store_id') as $obj) {
            $obj->current_origin_id($originID);
            $obj->save();
        }
    }

    public function saveItems()
    {
        $upcs = FormLib::get('update_upc', array());
        $brands = FormLib::get('update_brand', array());
        $descs = FormLib::get('update_desc', array());
        $origins = FormLib::get('update_origin', array());
        for ($i=0; $i<count($upcs); $i++) {
            if (!isset($brands[$i]) || !isset($descs[$i])) {
                continue;
            }
            $this->updateItem($upcs[$i], $brands[$i], $descs[$i], $origins[$i]);
        }
    }

    public function addOverride($upc, $field_name, $value)
    {
        $upc = BarcodeLib::padUPC($upc);
        if (!isset($this->overrides[$upc])) {
            $this->overrides[$upc] = array();
        }
        $this->overrides[$upc][$field_name] = $value;
    }

    public function addExclude($upc)
    {
        $this->excludes[] = $upc;
    }

    public function formatPrice($price, $multiplier=1, $regPrice=0)
    {
        if ($multiplier > 1) {
            // if the multiplier results in a nearly round number, just use the round number
            // otherwise use two decimal places.
            // the 2.5 cent threshold corresponds to existing advertisements
            $ttl = abs(($multiplier*$price) - round($multiplier*$price)) < 0.025 ? round($multiplier*$price) : sprintf('%.2f', $multiplier*$price);
            return $multiplier . '/$' . $ttl;
        } elseif ($multiplier < 0) {
            return self::formatOffString($price, $multiplier, $regPrice);
        }


        if (substr($price, -3) == '.33') {
            $ttl = round(3*$price);
            return '3/$' . $ttl;
        } elseif (substr($price, -3) == '.66' || substr($price, -3) == '.67') {
            $ttl = round(3*$price);
            return '3/$' . $ttl;
        } elseif (substr($price, -3) == '.50') {
            $ttl = round(2*$price);
            return '2/$' . $ttl;
        } elseif (substr($price, -3) == '.80') {
            $ttl = round(5*$price);
            return '5/$' . $ttl;
        } elseif (substr($price, -3) == '.25') {
            $ttl = round(4*$price);
            return '4/$' . $ttl;
        } elseif ($price == 1) {
            return '5/$5';
        } elseif ($price > 0 && substr($price, -3) == '.00' && $price <= 5.00) {
            $mult = 2;
            while (($mult+1)*$price <= 10) {
                $mult++;
            }
            return sprintf('%d/$%d', $mult, round($mult*$price));
        } elseif (substr($price, 0, 1) == '$') {
            return $price;
        } elseif (strstr($price, '/') || strstr($price, '%')) {
            return $price;
        } elseif ($price < 1) {
            // weird contortions because floating-point rounding
            return substr(sprintf('%.2f', $price),-2) . chr(0xA2);
        } else {
            return sprintf('$%.2f', $price);
        }
    }

    protected static function formatScalePrice($price, $multiplier, $regPrice)
    {
        if ($multiplier == -1) {
            return 'SAVE $' . self::dollarsOff($price, $regPrice) . '/lb';
        } elseif ($multiplier == -2) {
            return self::percentOff($price, $regPrice);
        } else {
            return sprintf('$%.2f /lb.', $price);
        }
    }

    protected static function formatOffString($price, $multiplier, $regPrice)
    {
        if ($regPrice == 0 || $multiplier == -4) {
            return sprintf('%.2f', $price);
        } elseif ($multiplier == -1) {
            $off = self::dollarsOff($price, $regPrice);
            if (substr(sprintf('%.2f', $off), -2) == '00') {
                return sprintf('$%d OFF', $off);
            } else {
                return sprintf('$%.2f OFF', $off);
            }
        } elseif ($multiplier == -2) {
            return self::percentOff($price, $regPrice);
        } elseif ($multiplier == -3) {
            return _('BUY ONE GET ONE FREE');
        }
    }

    protected static function dollarsOff($price, $regPrice)
    {
        // floating point arithmetic goes bonkers here
        $signPrice = sprintf('%.2f', ($regPrice - $price));
        if (substr($signPrice, -3) === '.00') {
            $signPrice = substr($signPrice, 0, strlen($signPrice)-3);
        }
        return $signPrice;
    }

    protected static function percentOff($price, $regPrice)
    {
        $percent = 1.0 - ($price/$regPrice);
        return sprintf('SAVE %d%%', round($percent*100));
    }

    public function drawPDF()
    {

    }

    /**
      Convert HTML entities in strings to normal characters
      for PDF output
    */
    protected function decodeItem($item)
    {
        $decode_fields = array('description', 'brand', 'size', 'vendor');
        foreach ($decode_fields as $field) {
            if (isset($item[$field])) {
                $item[$field] = html_entity_decode($item[$field], ENT_QUOTES);
            }
        }

        return $item;
    }

    protected function getDateString($start, $end)
    {
        if ($start == 'While supplies last') {
            return $start;
        } elseif ($start == 'Discontinued') {
            return $start;
        } else {
            return date('M d', strtotime($start))
                . chr(0x96) // en dash in cp1252
                . date('M d', strtotime($end));
        }
    }

    protected function formatSize($size, $item)
    {
        $plu = ltrim($item['upc'], '0');
        if (strlen($plu) < 5 && strlen($plu) > 0 && $item['scale']) {
            return 'PLU# ' . ltrim($item['upc'], '0'); // show PLU #s on by-weight
        }

        $size = trim(strtolower($size));
        if ($size == '0' || $size == '00' || $size == '') {
            return '';
        } elseif (substr($size, -1) != '.') {
            $size .= '.'; // end abbreviation w/ period
            $size = str_replace('fz.', 'fl oz.', $size);
        }
        if (substr($size, 0, 1) == '.') {
            $size = '0' . $size; // add leading zero on decimal qty
        }

        return $size;
    }

    protected function printablePrice($item)
    {
        $price = $item['normal_price'];
        if ($item['scale'] && isset($item['signMultiplier']) && $item['signMultiplier'] < 0) {
            $price = $this->formatScalePrice($item['normal_price'], $item['signMultiplier'], $item['nonSalePrice']);
        } elseif ($item['scale']) {
            if (substr($price, 0, 1) != '$') {
                $price = sprintf('$%.2f', $price);
            }
            $price .= ' /lb.';
        } elseif (isset($item['signMultiplier'])) {
            if (!isset($item['nonSalePrice'])) { // Fix for NOTICES if this didn't get supplied
                $item['nonSalePrice'] = $item['normal_price'];
            }
            $price = $this->formatPrice($item['normal_price'], $item['signMultiplier'], $item['nonSalePrice']);
        } else {
            $price = $this->formatPrice($item['normal_price']);
        }

        return $price;
    }

    protected function loadPluginFonts($pdf)
    {
        if (FanniePlugin::isEnabled('CoopDealsSigns')) {
            $this->font = 'Gill';
            $this->alt_font = 'GillBook';
            define('FPDF_FONTPATH', dirname(__FILE__) . '/../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
            $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
            $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
            $pdf->AddFont('GillBook', '', 'GillSansMTPro-Book.php');
        }

        return $pdf;
    }

    protected function fitText($pdf, $font_size, $text, $spacing)
    {
        $font_shrink = 0;
        $effective_width = $this->width - $this->left;
        $column = $spacing[0];
        $line_height = $spacing[1];
        $lines = $spacing[2];
        while (true) {
            $pdf->SetX($this->left + ($this->width*$column));
            $y = $pdf->GetY();
            $pdf->MultiCell($effective_width, $line_height, $text, 0, 'C');
            if ($pdf->GetY() - $y > ($line_height*$lines)) {
                $pdf->SetFillColor(0xff, 0xff, 0xff);
                $pdf->Rect($this->left + ($this->width*$column), $y, $this->left + ($this->width*$column) + $effective_width, $pdf->GetY(), 'F');
                $font_shrink++;
                if ($font_shrink >= $font_size) {
                    break;
                }
                $pdf->SetFontSize($font_size - $font_shrink);
                $pdf->SetXY($this->left + ($this->width*$column), $y);
            } else {
                if ($lines == 2 && $pdf->GetY() - $y < ($lines*$line_height)) {
                    $pdf = $this->twoLineText($pdf, $text, $y, $spacing);
                }
                break;
            }
        }

        return $pdf;
    }

    protected function twoLineText($pdf, $text, $y, $spacing)
    {
        $effective_width = $this->width - $this->left;
        $column = $spacing[0];
        $line_height = $spacing[1];
        $words = explode(' ', $text);
        $multi = '';
        for ($i=0;$i<floor(count($words)/2);$i++) {
            $multi .= $words[$i] . ' ';
        }
        $multi = trim($multi) . "\n";
        for ($i=floor(count($words)/2); $i<count($words); $i++) {
            $multi .= $words[$i] . ' ';
        }
        $text = trim($multi);
        $pdf->SetFillColor(0xff, 0xff, 0xff);
        $pdf->Rect($this->left + ($this->width*$column), $y, $this->left + ($this->width*$column) + $effective_width, $pdf->GetY(), 'F');
        $pdf->SetXY($this->left + ($this->width*$column), $y);
        $pdf->MultiCell($effective_width, $line_height, $text, 0, 'C');

        return $pdf;
    }

    protected function validDate($date)
    {
        if ($date == '') {
            return false;
        } elseif (substr($date,0,10) == '0000-00-00') {
            return false;
        }

        return true;
    }
}

