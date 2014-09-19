<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

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

class FannieSignage 
{
    protected $items = array();
    protected $source = '';
    protected $source_id = 0;
    protected $data = array();
    protected $overrides = array();

    /**
      constructor
      @param $items [array] of upcs
      @param $source [optional] string shelftags, batchbarcodes, batch, or empty.
        - shelftags => data is in shelftags table
        - batchbarcodes => data is in batchBarcodes table
        - batch => get data from normal product and vendor tables but
            use batch(es) for price
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
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $args = array();
        if ($this->source == 'shelftags') {
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
            $args[] = $this->source_id;
        } else if ($this->source == 'batchbarcodes') {
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
        } else if ($this->source == 'batch') {
            if (!is_array($this->source_id)) {
                $this->source_id = array($this->source_id);
            }
            $ids = '';
            foreach ($this->source_id as $id) {
                $args[] = $id;
                $ids .= '?,';
            }
            $ids = substr($ids, 0, strlen($ids)-1);
            $query = 'SELECT l.upc,
                        l.salePrice AS normal_price,
                        CASE WHEN u.description IS NULL OR u.description=\'\' THEN p.description ELSE u.description END as description,
                        p.description AS posDescription,
                        CASE WHEN u.brand IS NULL OR u.brand=\'\' THEN p.brand ELSE u.brand END as brand,
                        v.units,
                        v.size,
                        v.sku,
                        \'\' AS pricePerUnit,
                        n.vendorName AS vendor,
                        p.scale,
                        p.numflag,
                        b.startDate,
                        b.endDate,
                        o.name AS originName,
                        o.shortName AS originShortName
                     FROM batchList AS l
                        INNER JOIN products AS p ON l.upc=p.upc
                        INNER JOIN batches AS b ON b.batchID=l.batchID
                        LEFT JOIN productUser AS u ON p.upc=u.upc
                        LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                        LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                        LEFT JOIN origins AS o ON p.current_origin_id=o.originID
                     WHERE l.batchID IN (' . $ids . ')
                     ORDER BY p.department, p.upc';
        } else {
            $ids = '';
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
                        v.size,
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
            if ($this->source_id == 1) { // upcoming retail
                $query = 'SELECT p.upc,
                            l.salePrice AS normal_price,
                            CASE WHEN u.description IS NULL OR u.description=\'\' THEN p.description ELSE u.description END as description,
                            p.description AS posDescription,
                            CASE WHEN u.brand IS NULL OR u.brand=\'\' THEN p.brand ELSE u.brand END as brand,
                            v.units,
                            v.size,
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
            } else if ($this->source_id == 2) { // current sale
                $query = 'SELECT p.upc,
                            CASE WHEN p.discounttype <> 0 THEN p.special_price ELSE p.normal_price END AS normal_price,
                            CASE WHEN u.description IS NULL OR u.description=\'\' THEN p.description ELSE u.description END as description,
                            p.description AS posDescription,
                            CASE WHEN u.brand IS NULL OR u.brand=\'\' THEN p.brand ELSE u.brand END as brand,
                            v.units,
                            v.size,
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
            } else if ($this->source_id == 3) { // current sale
                $query = 'SELECT p.upc,
                            l.salePrice AS normal_price,
                            CASE WHEN u.description IS NULL OR u.description=\'\' THEN p.description ELSE u.description END as description,
                            p.description AS posDescription,
                            CASE WHEN u.brand IS NULL OR u.brand=\'\' THEN p.brand ELSE u.brand END as brand,
                            v.units,
                            v.size,
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
            }

        }

        $data = array();
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $mapP = $dbc->prepare('SELECT o.name, o.shortName
                               FROM ProductOriginsMap AS m
                                INNER JOIN origins AS o ON m.originID=o.originID
                               WHERE
                                m.upc = ?
                                AND o.name <> ?
                                AND o.shortName <> ?');

        while($row = $dbc->fetch_row($result)) {

            if ($row['pricePerUnit'] == '') {
                $row['pricePerUnit'] = PriceLib::pricePerUnit($row['normal_price'], $row['size']);
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

            $data[] = $row;
        }

        return $data;
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
        $h = isset($args['height']) ? $args['height'] : 16;
        $w = isset($args['width']) ? $args['width'] : 0.35;
        $align = isset($args['align']) ? $args['align'] : 'C';
        $valign = isset($args['valign']) ? $args['valign'] : 'B';
        $prefix = isset($args['prefix']) ? $args['prefix'] : '';
        $suffix = isset($args['suffix']) ? $args['suffix'] : '';
        $font = isset($args['font']) ? $args['font'] : 'Arial';
        $fontsize = isset($args['fontsize']) ? $args['fontsize'] : 9;

        $upc = str_pad($upc, 12, '0', STR_PAD_LEFT);
        if (BarcodeLib::verifyCheckDigit($upc)) {
            // if an EAN13 with valid check digit is passed
            // in there's no need to add the zero
            if (strlen($upc) == 12) {
                $upc = '0' . $upc;
            }
        } else {
            $check = BarcodeLib::getCheckDigit($upc);
            $upc .= $check;
        }

        //Convert digits to bars
        $codes=array(
            'A'=>array(
                '0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
                '5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011'),
            'B'=>array(
                '0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
                '5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111'),
            'C'=>array(
                '0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
                '5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100')
        );
        $parities=array(
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

        $code='101'; // start bar
        $p = $parities[$upc[0]]; // parity based on first digit
        for ($i=1;$i<=6;$i++) { // first half
            $code .= $codes[$p[$i-1]][$upc[$i]];
        }
        $code .= '01010'; // middle bar
        for ($i=7;$i<=12;$i++) { // second half
            $code .= $codes['C'][$upc[$i]];
        }
        $code.='101'; // end bar

        //Draw bars
        $width = 0;
        for ($i=0;$i<strlen($code);$i++) {
            if ($code{$i}=='1') {
                $pdf->Rect($x+($i*$w), $y, $w, $h, 'F');
            }
            $width += $w;
        }

        // Print text uder barcode
        // omits first digit; should always be zero
        $pdf->SetFont($font, '', $fontsize);
        if ($valign == 'T') {
            $pdf->SetXY($x, $y - 5);
        } else {
            $pdf->SetXY($x, $y + $h);
        }
        $pdf->Cell($width, 5, $prefix . substr($upc, -12) . $suffix, 0, 0, $align);

        return $pdf;
    }

    public function listItems()
    {
        global $FANNIE_URL;
        $ret = '<table>';
        $ret .= '<tr><th>UPC</th><th>Brand</th><th>Description</th><th>Price</th><th>Origin</th></tr>';
        $data = $this->loadItems();
        foreach ($data as $item) {
            $ret .= sprintf('<tr>
                            <td><a href="%sitem/ItemEditorPage.php?searchupc=%s" target="_edit%s">%s</a></td>
                            <input type="hidden" name="update_upc[]" value="%d" />
                            <td><input class="FannieSignageField" type="text" name="update_brand[]" value="%s" /></td>
                            <td><input class="FannieSignageField" type="text" name="update_desc[]" value="%s" /></td>
                            <td>%.2f</td>
                            <td><input class="FannieSignageField" type="text" name="update_origin[]" value="%s" /></td>
                            </tr>',
                            $FANNIE_URL,
                            $item['upc'], $item['upc'], $item['upc'],
                            $item['upc'],
                            $item['brand'],
                            $item['description'],
                            $item['normal_price'],
                            $item['originName']
            );
        }
        $ret .= '</table>';

        return $ret;
    }

    public function updateItem($upc, $brand, $description)
    {
        global $FANNIE_OP_DB;
        switch (strtolower($this->source)) {
            case 'shelftags':
                $model = new ShelftagsModel(FannieDB::get($FANNIE_OP_DB));
                $model->id($this->source_id);
                $model->upc(BarcodeLib::padUPC($upc));
                $model->brand($brand);
                $model->description($description);
                $model->save();
                break;
            case 'batchbarcodes':
                $dbc = FannieDB::get($FANNIE_OP_DB);
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
                $dbc->execute($prep, $args);
                break;
            case 'batch':
            case '':
                $model = new ProductUserModel(FannieDB::get($FANNIE_OP_DB));
                $model->upc(BarcodeLib::padUPC($upc));
                $model->brand($brand);
                $model->description($description);
                $model->save();
                $model = new ProductsModel(FannieDB::get($FANNIE_OP_DB));
                $model->upc(BarcodeLib::padUPC($upc));
                $model->brand($brand);
                $model->save();
                break;
        }
    }

    public function saveItems()
    {
        $upcs = FormLib::get('update_upc', array());
        $brands = FormLib::get('update_brand', array());
        $descs = FormLib::get('update_desc', array());
        for ($i=0; $i<count($upcs); $i++) {
            if (!isset($brands[$i]) || !isset($descs[$i])) {
                continue;
            }
            $this->updateItem($upcs[$i], $brands[$i], $descs[$i]);
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

    public function drawPDF()
    {

    }
}
