<?php
namespace COREPOS\Fannie\API\item\signage {

class FancyTags_Less_Short extends \COREPOS\Fannie\API\item\FannieSignage 
{

    public function drawPDF()
    {
        $dbc = $this->connection;
        $pdf = new \FPDF('L', 'mm', array(105, 148));
        $pdf->AddPage("L", "A6");
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
        $pdf->SetFont('Gill','B', 16);

        $data = $this->loadItems();
        $showBarcode = \FormLib::get('showBarcode');
        $showPrice = \FormLib::get('showPrice');

        $width = 68;
        $height = 34 - 5;
        $left = 8;  
        $top = 9;
        $guide = 0.3;

        $x = $left+$guide; $y = $top+$guide;

        $pdf->SetTopMargin($top); 
        $pdf->SetLeftMargin($left);
        $pdf->SetRightMargin($left);
        $pdf->SetAutoPageBreak(False);

        $mirrorData = $this->arrayMirrorRowsSingleNewDeliRegular_24UP($data, 2);

        $i = 0;
        $page = 0;
        foreach($data as $k => $row){
            $upc = $row['upc'];
            if ($i % 6 == 0 && $i != 0) {

                $start = 6 * $page;
                $tagData = array_slice($mirrorData, $start, 6);
                $this->prepSingleNewDeliRegularMirrorTags($start, $tagData, $pdf, $width, $height, $left, $top, $guide, $dbc);

                $pdf->AddPage('L');
                $x = $left;
                $y = $top;
                $i = 0;
                $page += 1;
            }
            if ($i == 0) {
                $pdf = $this->generateSingleNewDeliRegular_24UPTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $showBarcode, $offset=false);
            } else if ($i % 2 == 0 && $i != 0) {
                $x = $left+$guide;
                $y += $height+$guide;
            } else {
                $x += $width+$guide;
            }
            $pdf = $this->generateSingleNewDeliRegular_24UPTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $showBarcode, $offset=false);
            $i++;
        }

        $start = 6 * $page;
        $tagData = array_slice($mirrorData, $start, 6);
        $end = count($mirrorData);
        $this->prepSingleNewDeliRegularMirrorTags($start, $tagData, $pdf, $width, $height, $left, $top, $guide, $dbc);

        $pdf = $pdf->Output();
    }

    private function prepSingleNewDeliRegularMirrorTags($start, $data, $pdf, $width, $height, $left, $top, $guide, $dbc)
    {
        /*
            Print additional mirror images for back side of tags
        */
        $i = 0;
        $x = $left+$guide; $y = $top+$guide;
        if (count($data) == 3 || count($data) == 1) {
            array_splice($data, -1, 0, array(''));
        }
        $pdf->AddPage('L');
        foreach($data as $k => $row){
            if ($i % 6 == 0 && $i != 0) {
                return false;
            }
            if ($i == 0) {
                $pdf = $this->generateNewDeliSingleRegularMirrorTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
            } else if ($i % 2 == 0 && $i != 0) {
                $x = $left+$guide;
                $y += $height+$guide;
            } else {
                $x += $width+$guide;
            }
            $pdf = $this->generateNewDeliSingleRegularMirrorTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
            $i++;
        }
        }


    private function generateNewDeliSingleRegularMirrorTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
    {
        $upc = isset($row['upc']) ? $row['upc'] : '';
        $sku = isset($row['sku']) ? $row['sku'] : '';
        if (strlen($sku) < 1)
            $sku = '(no sku in POS)';
        $desc = isset($row['description']) ? $row['description'] : '';
        $brand = isset($row['brand']) ? $row['brand'] : '';
        $price = isset($row['normal_price']) ? $row['normal_price'] : '';
        $vendor = isset($row['vendor']) ? $row['vendor'] : '';
        $size = isset($row['size']) ? $row['size'] : '';
        $storeID = \FormLib::get('store');
        $date = new \DateTime();
        $today = $date->format('Y-m-d');

        $parA = array($storeID, $upc);
        $parP = $dbc->prepare("SELECT ROUND(auto_par,1) AS auto_par FROM products WHERE store_id = ? AND upc = ?");
        $parR = $dbc->execute($parP, $parA);
        $parW = $dbc->fetchRow($parR);
        $par = isset($parW['auto_par']) ? $parW['auto_par']*7 : 'n/a';
        if ($par == 0)
            $par = 'n/a';

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Gill','', 9);

        // prep tag canvas
        $pdf->SetXY($x+5,$y);
        $pdf->Cell($width-5, $height, '', 0, 1, 'C', true); 

        /*
            Add UPC Text
        */
        $pdf->SetXY($x+5,$y+13);
        $pdf->Cell(15, 8, $upc, 0, 1, 'L', true); 

        /*
            Add Date Text 
        */
        $pdf->SetXY($x+53,$y);
        $pdf->Cell(10, 4, $today, 0, 1, 'R', true); 

        /*
            Add PAR Text 
        */
        if ($storeID != 0) {
            $pdf->SetXY($x+53,$y);
            $pdf->Cell(10, 4, 'PAR '.$par, 0, 1, 'R', true); 
        }

        /*
            Add Brand & Description Text
        */
        $pdf->SetXY($x,$y+3);
        $pdf->Cell($width, 5, $brand, 0, 1, 'C', true); 
        $pdf->SetXY($x,$y+9);
        $pdf->Cell($width, 5, $desc, 0, 1, 'C', true); 

        /*
            Add Vendor SKU 
        */
        $pdf->SetXY($x+5,$y+19);
        $pdf->Cell($width, 5, "SKU ".$sku, 0, 1, 'L', true); 

        /*
            Add Vendor Text
        */
        $pdf->SetXY($x+5,$y+23);
        $pdf->Cell($width, 5, $vendor, 0, 1, 'L', true); 

        /*
            Add Size Text
        */
        if ($size > 0) {
            $pdf->SetXY($x+47,$y+23);
            $pdf->Cell('15', 5, $size, 0, 1, 'R', true); 
        }

        $pdf->SetFillColor(255, 255, 255);

        return $pdf;

    }

    private function generateSingleNewDeliRegular_24UPTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $showBarcode, $offset=false)
    {
        $upc = $row['upc'];
        $desc = $row['description'];
        $brand = $row['brand'];
        $price = $row['normal_price'];

        $updateUpcs = \FormLib::get('update_upc');
        $manualDescs = \FormLib::get('update_desc');
        $manualBrand = \FormLib::get('update_brand');

        $args = array($row['upc']);
        $prep = $dbc->prepare("
            SELECT pu.description, p.scale, p.numflag
            FROM productUser AS pu
                INNER JOIN products AS p ON pu.upc=p.upc
            WHERE pu.upc = ?");
        $res = $dbc->execute($prep, $args);
        $row = $dbc->fetchRow($res);
        $desc = $row['description'];
        $numflag = $row['numflag'];

        $scaleP = $dbc->prepare("SELECT * FROM scaleItems WHERE plu = ?"); 
        $scaleR = $dbc->execute($scaleP, $args);
        $scaleW = $dbc->fetchRow($scaleR);
        $randoWeight = ($scaleW['weight'] == 0) ? true : false;
        $lb = ($randoWeight && substr($upc, 0, 3) == '002') ? '/lb' : '';

        $MdescKey = array_search($upc, $updateUpcs);
        $Mdesc = $manualDescs[$MdescKey];
        $Mbrand = $manualBrand[$MdescKey];
        $desc = $Mdesc;
        $brand = $Mbrand;
        $brand = strtoupper($brand);

        // get local info
        $localP = $dbc->prepare("SELECT 'true' FROM products WHERE local > 0 AND upc = ?");
        $item['local'] = $dbc->getValue($localP, $upc);

        // prep tag canvas
        $pdf->SetXY($x,$y);
        $pdf->Cell($width, $height, '', 0, 1, 'C', true); 

        /*
            Add UPC Barcode 
        */

        /* UPC / PLU Barcode */
        if ($showBarcode == 1 ) {
            $pdf->SetFillColor(0,0,0);
            $pdf = $this->drawBarcode($upc, $pdf, $x + 17, $y, array());
            $pdf->SetFillColor(255,255,255);

            // Cover up the bottom half of the Barcode
            $pdf->SetFillColor(255,255,255);
            $pdf->Rect($x + 17, $y + 4, 50, 35, 'F');
        }

        // use line break to split str if exists; else wordwrap if 2 lines req.
        $lines = array();
        if (strstr($desc, "\r\n")) {
            $lines = explode ("\r\n", $desc);
        } elseif (strlen($desc) > 20) {
            $wrp = wordwrap($desc, strlen($desc)/1.5, "*", false);
            $lines = explode('*', $wrp);
        } else {
            $lines[0] = $desc;
        }

        /*
            Add Brand Text
        */
        $textWidth = \SignsLib::getStrWidthGillSans($brand);
        $tmpFontSize = 14;
        if ($textWidth > 19)
            $tmpFontSize = 13;
        if ($textWidth > 24)
            $tmpFontSize = 12;
        if ($textWidth > 34)
            $tmpFontSize = 11;
        if ($textWidth > 44)
            $tmpFontSize = 9;
        if ($textWidth > 50)
            $tmpFontSize = 8.5;

        $pdf->SetFont('Gill','B', $tmpFontSize);
        $pdf->SetXY($x,$y+3);
        $pdf->Cell($width, 8, $brand, 0, 1, 'C', true); 


        /*
            Add Price Text
        */
        $pdf->SetFont('Gill','B', 19);  //Set the font 
        $pdf->SetXY($x,$y+23);
        if ($showPrice == 1 ) 
            $pdf->Cell($width, 5, "$".$price.$lb, 0, 1, 'C', true); 

        /* 
            Print Local Star & Text
        */
        if ($item['local']) {
            $localX = 2;
            $localY = 18.5;
            $pdf->Image(__DIR__ . '/../../../admin/labels/noauto/localST.jpg', $x+$localX, $y+$localY+1, 14, 8.5);
            $pdf->SetDrawColor(243, 115, 34);
            //$pdf->Rect($x+$localX, $y+$localY, 15, 9.4, 'D');
            $pdf->SetDrawColor(0, 0, 0);
        }

        /* 
            Print Vegan
        */
        if ($numflag & (1<<2)) {
            $localX = 52;
            $localY = 18.5;
            $pdf->Image(__DIR__ . '/../../../admin/labels/noauto/veganST.jpg', $x+$localX, $y+$localY+1, 14, 8.5);
            $pdf->SetDrawColor(243, 115, 34);
            $pdf->SetDrawColor(0, 0, 0);
        }

        /*
            Add Description Text
        */
        $strlen = strlen($lines[0]);
        if (isset($lines[1]) && strlen($lines[1]) > $strlen) {
            $strlen = strlen($lines[1]);
        }
        if ($strlen >= 30) {
            $pdf->SetFont('Gill','', 7);
        } elseif ($strlen > 26 && $strlen < 36) {
            $pdf->SetFont('Gill','', 10);
        } else {
            $pdf->SetFont('Gill','', 14);
        }
        if (count($lines) > 1) {
            $pdf->SetXY($x,$y+11);
            $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
            $pdf->SetXY($x, $y+16);
            $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
        } else {
            $pdf->SetXY($x,$y+13);
            $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        }


        $textWidth = \SignsLib::getStrWidthGillSans($brand);
        $tmpFontSize = 14;
        if ($textWidth > 19)
            $tmpFontSize = 13;
        if ($textWidth > 24)
            $tmpFontSize = 12;
        if ($textWidth > 34)
            $tmpFontSize = 11;
        if ($textWidth > 44)
            $tmpFontSize = 9;
        if ($textWidth > 50)
            $tmpFontSize = 8.5;

        $pdf->SetFont('Gill','B', $tmpFontSize);
        //$pdf->Rect($x+11, $y-2, 50, 3, 'F');


        /*
            Create Guide-Lines
        */ 
        $pdf->SetFillColor(155, 155, 155);
        // vertical 
        $pdf->SetXY($width+$x, $y);
        $pdf->Cell($guide, $height+$guide, '', 0, 1, 'C', true);

        $pdf->SetXY($x-$guide, $y-$guide); 
        $pdf->Cell($guide, $height+$guide, '', 0, 1, 'C', true);

        // horizontal
        $pdf->SetXY($x, $y-$guide); 
        $pdf->Cell($width+$guide, $guide, '', 0, 1, 'C', true);

        $pdf->SetXY($x, $y+$height); 
        $pdf->Cell($width+$guide, $guide, '', 0, 1, 'C', true);

        // inset left-hand border
        if ($x < 10) {
            $pdf->SetXY($x, $y);
            $pdf->Cell(1, $height+1, '', 0, 1, 'C', true);
        }
        // inset right-hand border
        if ($x > 60) {
            $pdf->SetXY($x+$width-1.5, $y);
            $pdf->Cell(1, $height, '', 0, 1, 'C', true);
        }
        // inset top border
        //if ($y < 10) {
        //    $pdf->SetXY($x, $y);
        //    $pdf->Cell($width+1, 1, '', 0, 1, 'C', true);
        //}

        $pdf->SetFillColor(255, 255, 255);

        return $pdf;
    }

    private function arrayMirrorRowsSingleNewDeliRegular_24UP($array, $cols)
    {
        $newArray = array();
        $chunks = array_chunk($array, $cols);
        foreach ($chunks as $chunk) {
            $chunk = array_reverse($chunk);
            foreach ($chunk as $v) {
                $newArray[] = $v;
            }
        }

        return $newArray;
    }

}

}
