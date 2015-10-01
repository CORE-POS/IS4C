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

namespace COREPOS\Fannie\API\item {

class FannieSignage 
{
    protected $items = array();
    protected $source = '';
    protected $source_id = 0;
    protected $data = array();
    protected $overrides = array();
    protected $excludes = array();

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

    public function loadItems()
    {
        if ($this->source == 'provided') {
            return $this->items;
        }

        $op_db = \FannieConfig::factory()->get('OP_DB');
        $dbc = \FannieDB::get($op_db);
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

        $mapP = $dbc->prepare('SELECT o.name, o.shortName
                               FROM ProductOriginsMap AS m
                                INNER JOIN origins AS o ON m.originID=o.originID
                               WHERE
                                m.upc = ?
                                AND o.name <> ?
                                AND o.shortName <> ?');

        while ($row = $dbc->fetch_row($result)) {

            if (in_array($row['upc'], $this->excludes)) {
                continue;
            }

            if ($row['pricePerUnit'] == '') {
                $row['pricePerUnit'] = \COREPOS\Fannie\API\lib\PriceLib::pricePerUnit($row['normal_price'], $row['size']);
            }

            if ($row['originName'] != '') {
                // check for additional origins
                $mapR = $dbc->execute($mapP, array($row['upc'], $row['originName'], $row['originShortName']));
                while ($mapW = $dbc->fetch_row($mapR)) {
                    $row['originName'] .= _(' or ') . $mapW['name'];
                    $row['originShortName'] .= _(' or ') . $mapW['shortName'];
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
            for ($i=0; $i<$row['signCount']; $i++) {
                $data[] = $row;
            }
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
                    o.name AS originName,
                    o.shortName AS originShortName
                  FROM shelftags AS s
                    INNER JOIN products AS p ON s.upc=p.upc
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
                    s.vendor,
                    p.scale,
                    p.numflag,
                    b.startDate,
                    b.endDate,
                    o.name AS originName,
                    o.shortName AS originShortName
                  FROM batchBarcodes AS s
                    INNER JOIN products AS p ON s.upc=p.upc
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
                    CASE WHEN u.description IS NULL OR u.description=\'\' THEN p.description ELSE u.description END as description,
                    p.description AS posDescription,
                    CASE WHEN u.brand IS NULL OR u.brand=\'\' THEN p.brand ELSE u.brand END as brand,
                    v.units,
                    CASE WHEN p.size IS NULL OR p.size=\'\' OR p.size=\'0\' THEN v.size ELSE p.size END AS size,
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
                    o.shortName AS originShortName,
                    b.batchType
                 FROM batchList AS l
                    INNER JOIN products AS p ON l.upc=p.upc
                    INNER JOIN batches AS b ON b.batchID=l.batchID
                    LEFT JOIN batchType AS t ON b.batchType=t.batchTypeID
                    LEFT JOIN productUser AS u ON p.upc=u.upc
                    LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                 WHERE l.batchID IN (' . $ids . ')
                 ORDER BY brand, description';

        return array('query' => $query, 'args' => $args);
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
                    CASE WHEN u.description IS NULL OR u.description=\'\' THEN p.description ELSE u.description END as description,
                    p.description AS posDescription,
                    CASE WHEN u.brand IS NULL OR u.brand=\'\' THEN p.brand ELSE u.brand END as brand,
                    v.units,
                    CASE WHEN p.size IS NULL OR p.size=\'\' OR p.size=\'0\' THEN v.size ELSE p.size END AS size,
                    v.sku,
                    \'\' AS pricePerUnit,
                    n.vendorName AS vendor,
                    p.scale,
                    p.numflag,
                    \'\' AS startDate,
                    \'\' AS endDate,
                    o.name AS originName,
                    o.shortName AS originShortName
                 FROM products AS p
                    LEFT JOIN productUser AS u ON p.upc=u.upc
                    LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                 WHERE p.upc IN (' . $ids . ')
                 ORDER BY p.department, p.upc';

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
                    CASE WHEN u.description IS NULL OR u.description=\'\' THEN p.description ELSE u.description END as description,
                    p.description AS posDescription,
                    CASE WHEN u.brand IS NULL OR u.brand=\'\' THEN p.brand ELSE u.brand END as brand,
                    v.units,
                    CASE WHEN p.size IS NULL OR p.size=\'\' OR p.size=\'0\' THEN v.size ELSE p.size END AS size,
                    v.sku,
                    \'\' AS pricePerUnit,
                    n.vendorName AS vendor,
                    p.scale,
                    p.numflag,
                    \'\' AS startDate,
                    \'\' AS endDate,
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
                    AND b.startDate >= ' . $dbc->curdate() . '
                 ORDER BY p.department, p.upc';

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
                    CASE WHEN u.description IS NULL OR u.description=\'\' THEN p.description ELSE u.description END as description,
                    p.description AS posDescription,
                    CASE WHEN u.brand IS NULL OR u.brand=\'\' THEN p.brand ELSE u.brand END as brand,
                    v.units,
                    CASE WHEN p.size IS NULL OR p.size=\'\' OR p.size=\'0\' THEN v.size ELSE p.size END AS size,
                    v.sku,
                    \'\' AS pricePerUnit,
                    n.vendorName AS vendor,
                    p.scale,
                    p.numflag,
                    p.start_date AS startDate,
                    p.end_date AS endDate,
                    o.name AS originName,
                    o.shortName AS originShortName
                 FROM products AS p
                    LEFT JOIN productUser AS u ON p.upc=u.upc
                    LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                 WHERE p.upc IN (' . $ids . ')
                         ORDER BY p.department, p.upc';

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
                    CASE WHEN u.description IS NULL OR u.description=\'\' THEN p.description ELSE u.description END as description,
                    p.description AS posDescription,
                    CASE WHEN u.brand IS NULL OR u.brand=\'\' THEN p.brand ELSE u.brand END as brand,
                    v.units,
                    CASE WHEN p.size IS NULL OR p.size=\'\' OR p.size=\'0\' THEN v.size ELSE p.size END AS size,
                    v.sku,
                    \'\' AS pricePerUnit,
                    n.vendorName AS vendor,
                    p.scale,
                    p.numflag,
                    b.startDate,
                    b.endDate,
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
                    AND b.discounttype <> 0
                    AND b.startDate > ' . $dbc->now() . '
                 ORDER BY p.department, p.upc';

        return array('query' => $query, 'args' => $args);
    }

    protected $codes=array(
        'A'=>array(
            '0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
            '5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011'),
        'B'=>array(
            '0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
            '5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111'),
        'C'=>array(
            '0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
            '5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100'),
    );

    protected $parities=array(
        '0'=>array('A','A','A','A','A','A'),
        '1'=>array('A','A','B','A','B','B'),
        '2'=>array('A','A','B','B','A','B'),
        '3'=>array('A','A','B','B','B','A'),
        '4'=>array('A','B','A','A','B','B'),
        '5'=>array('A','B','B','A','A','B'),
        '6'=>array('A','B','B','B','A','A'),
        '7'=>array('A','B','A','B','A','B'),
        '8'=>array('A','B','A','B','B','A'),
        '9'=>array('A','B','B','A','B','A')
    );

    protected function upcToBitString($upc)
    {
        $code='101'; // start bar
        $parity = $this->parities[$upc[0]]; // parity based on first digit
        for ($i=1;$i<=6;$i++) { // first half
            $code .= $this->codes[$parity[$i-1]][$upc[$i]];
        }
        $code .= '01010'; // middle bar
        for ($i=7;$i<=12;$i++) { // second half
            $code .= $this->codes['C'][$upc[$i]];
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

        $upc = ltrim($upc, '0');
        $is_ean = false;
        if (strlen($upc) == 12) { 
            // must be EAN
            $check = \BarcodeLib::getCheckDigit($upc);
            $upc .= $check;
            $is_ean = true;
        } else {
            $upc = str_pad($upc, 11, '0', STR_PAD_LEFT);
            $check = \BarcodeLib::getCheckDigit($upc);
            $upc = '0' . $upc . $check;
        }

        //Convert digits to bars
        $code = $this->upcToBitString($upc);

        //Draw bars
        $full_width = 0;
        for ($i=0;$i<strlen($code);$i++) {
            if ($code{$i}=='1') {
                $pdf->Rect($x+($i*$width), $y, $width, $height, 'F');
            }
            $full_width += $width;
        }

        // Print text under barcode
        // omits first digit; should always be zero
        if ($fontsize > 0) {
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

    public function listItems()
    {
        $url = \FannieConfig::factory()->get('URL');
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
        foreach ($data as $item) {
            $ret .= sprintf('<tr>
                            <td><a href="%sitem/ItemEditorPage.php?searchupc=%s" target="_edit%s">%s</a></td>
                            <input type="hidden" name="update_upc[]" value="%d" />
                            <td>
                                <span class="collapse">%s</span>
                                <input class="FannieSignageField form-control" type="text" 
                                name="update_brand[]" value="%s" /></td>
                            <td>
                                <span class="collapse">%s</span>
                                <input class="FannieSignageField form-control" type="text" 
                                name="update_desc[]" value="%s" /></td>
                            <td>%.2f</td>
                            <td><input class="FannieSignageField form-control" type="text" 
                                name="update_origin[]" value="%s" /></td>
                            <td><input type="checkbox" name="exclude[]" class="exclude-checkbox" value="%s" /></td>
                            </tr>',
                            $url,
                            $item['upc'], $item['upc'], $item['upc'],
                            $item['upc'],
                            $item['brand'],
                            $item['brand'],
                            str_replace('"', '&quot;', $item['description']),
                            str_replace('"', '&quot;', $item['description']),
                            $item['normal_price'],
                            $item['originName'],
                            $item['upc']
            );
        }
        $ret .= '</tbody></table>';

        return $ret;
    }

    public function updateItem($upc, $brand, $description)
    {
        $op_db = \FannieConfig::factory()->get('OP_DB');
        switch (strtolower($this->source)) {
            case 'shelftags':
                $this->updateShelftagItem($op_db, $upc, $brand, $description);
                break;
            case 'batchbarcodes':
                $this->updateBatchBarocdeItem($op_db, $upc, $brand, $description);
                break;
            case 'batch':
            case '':
                $this->updateRealItem($op_db, $upc, $brand, $description);
                break;
        }
    }

    protected function updateShelftagItem($dbc, $upc, $brand, $description)
    {
        $model = new \ShelftagsModel(\FannieDB::get($dbc));
        $model->id($this->source_id);
        $model->upc(\BarcodeLib::padUPC($upc));
        $model->brand($brand);
        $model->description($description);
        return $model->save();
    }

    protected function updateBatchBarcodeItem($dbc, $upc, $brand, $description)
    {
        $args = array($brand, $description, \BarcodeLib::padUPC($upc));
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

    protected function updateRealItem($dbc, $upc, $brand, $description)
    {
        $model = new \ProductUserModel(\FannieDB::get($dbc));
        $model->upc(\BarcodeLib::padUPC($upc));
        $model->brand($brand);
        $model->description($description);
        $model->save();
        $model = new \ProductsModel(\FannieDB::get($dbc));
        $model->upc(\BarcodeLib::padUPC($upc));
        foreach ($model->find('store_id') as $obj) {
            $obj->brand($brand);
            $obj->save();
        }
    }

    public function saveItems()
    {
        $upcs = \FormLib::get('update_upc', array());
        $brands = \FormLib::get('update_brand', array());
        $descs = \FormLib::get('update_desc', array());
        for ($i=0; $i<count($upcs); $i++) {
            if (!isset($brands[$i]) || !isset($descs[$i])) {
                continue;
            }
            $this->updateItem($upcs[$i], $brands[$i], $descs[$i]);
        }
    }

    public function addOverride($upc, $field_name, $value)
    {
        $upc = \BarcodeLib::padUPC($upc);
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
            $ttl = round($multiplier*$price);
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
        } elseif (substr($price, -3) == '.00' && $price <= 5.00) {
            $mult = 2;
            while (($mult+1)*$price <= 10) {
                $mult++;
            }
            return sprintf('%d/$%d', $mult, round($mult*$price));
        } elseif (substr($price, 0, 1) == '$') {
            return $price;
        } elseif (strstr($price, '/')) {
            return $price;
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
        if ($regPrice == 0) {
            return sprintf('%.2f', $price);
        } elseif ($multiplier == -1) {
            return sprintf('$%.2f OFF', self::dollarsOff($price, $regPrice));
        } elseif ($multiplier == -2) {
            return self::percentOff($price, $regPrice);
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
}

}

namespace {
    class FannieSignage extends \COREPOS\Fannie\API\item\FannieSignage {}
}

