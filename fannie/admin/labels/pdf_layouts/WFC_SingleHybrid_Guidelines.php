<?php
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FpdfLib')) {
    include(dirname(__FILE__) . '/FpdfLib.php');
}
/*
    Using layouts
    1. Make a file, e.g. New_Layout.php
    2. Make a PDF class New_Layout_PDF extending FPDF
       (just copy an existing one to get the UPC/EAN/Barcode
        functions)
    3. Make a function New_Layout($data)
       Data is an array database rows containing:
        normal_price
        description
        brand
        units
        size
        sku
        pricePerUnit
        upc
        vendor
        scale
    4. In your function, build the PDF. Look at
       existings ones for some hints and/or FPDF
       documentation

    Name matching is important
*/
class WFC_SingleHybrid_Guidelines_PDF extends FpdfWithBarcode
{
    private $tagdate;
    function setTagDate($str){
        $this->tagdate = $str;
    }

    function barcodeText($x, $y, $h, $barcode, $len)
    {
        if ($h != 4) {
            $this->SetFont('Arial','',8);
            $this->Text($x,$y-$h+(17/$this->k),substr($barcode,-$len).' '.$this->tagdate);
        } else {
            $this->SetFont('Arial','',9);
            $this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
        }
    }

    function Circle($x, $y, $r, $style='D')
    {
        $this->Ellipse($x,$y,$r,$r,$style);
    }

    function Ellipse($x, $y, $rx, $ry, $style='D')
    {
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $lx=4/3*(M_SQRT2-1)*$rx;
        $ly=4/3*(M_SQRT2-1)*$ry;
        $k=$this->k;
        $h=$this->h;
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x+$rx)*$k,($h-$y)*$k,
            ($x+$rx)*$k,($h-($y-$ly))*$k,
            ($x+$lx)*$k,($h-($y-$ry))*$k,
            $x*$k,($h-($y-$ry))*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x-$lx)*$k,($h-($y-$ry))*$k,
            ($x-$rx)*$k,($h-($y-$ly))*$k,
            ($x-$rx)*$k,($h-$y)*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x-$rx)*$k,($h-($y+$ly))*$k,
            ($x-$lx)*$k,($h-($y+$ry))*$k,
            $x*$k,($h-($y+$ry))*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c %s',
            ($x+$lx)*$k,($h-($y+$ry))*$k,
            ($x+$rx)*$k,($h-($y+$ly))*$k,
            ($x+$rx)*$k,($h-$y)*$k,
            $op));
    }
}

function WFC_SingleHybrid_Guidelines($data,$offset=0){

//$pdf=new WFC_SingleHybrid_Guidelines_PDF('P','mm','Letter'); //start new instance of PDF
$pdf = new WFC_SingleHybrid_Guidelines_PDF('L', 'mm', array(105, 148));
$pdf->Open(); //open new PDF Document
$pdf->setTagDate(date("m/d/Y"));
$dbc = FannieDB::get(FannieConfig::config('OP_DB'));
$store = COREPOS\Fannie\API\lib\Store::getIdByIp();

$s_def = $dbc->tableDefinition('SignProperties');
$narrowTable = (isset($s_def['narrow'])) ? 'SignProperties' : 'productUser';

$narrowQ = "SELECT upc FROM $narrowTable WHERE upc=? AND narrow=1 ";
if ($narrowTable == 'SignProperties') {
    $narrowQ .= " AND storeID = ? ";
}

$narrowP = $dbc->prepare($narrowQ);

$upcs = array();
$locations = array();
$locNames = array();
$dots = array();
foreach ($data as $k => $row) {
    $upc = $row['upc'];
    $upcs[] = $upc;
}
list($inStr, $locationA) = $dbc->safeInClause($upcs);
$locationP = $dbc->prepare("
SELECT f.upc,
UPPER( CONCAT( SUBSTR(name, 1, 1), SUBSTR(name, 2, 1), SUBSTR(name, -1), '-', sub.SubSection)) AS location,
UPPER( CONCAT( SUBSTR(name, 1, 1), SUBSTR(name, 2, 1), SUBSTR(name, -1))) AS noSubLocation,
name AS name
FROM FloorSectionProductMap AS f
    LEFT JOIN FloorSections AS s ON f.floorSectionID=s.floorSectionID
    LEFT JOIN FloorSubSections AS sub ON f.floorSectionID=sub.floorSectionID 
        AND sub.upc=f.upc
    WHERE f.upc IN ($inStr)
        AND s.storeID = ?
");
$locationA[count($locationA)] = $store;
$res = $dbc->execute($locationP, $locationA);
while ($row = $dbc->fetchRow($res)) {
    $upc = ltrim($row['upc'],0);
    $locations[$upc][] = ($row['location'] != null) ? $row['location'] : $row['noSubLocation'];
    $locNames[$upc][] = $row['name'];
}

list($superIn, $superA) = $dbc->safeInClause($upcs);
$superP = $dbc->prepare("
SELECT p.upc, m.super_name
FROM products AS p
    LEFT JOIN MasterSuperDepts AS m ON m.dept_ID=p.department
WHERE p.upc IN ($superIn)
AND p.store_id = ?
");
$superA[] = $store;
$superR = $dbc->execute($superP, $superA);
while ($row = $dbc->fetchRow($superR)) {
    $upc = ltrim($row['upc'],0);
    $dots[$upc] = $row['super_name'];
}

$mtLength = $store == 1 ? 3 : 7;
$signage = new COREPOS\Fannie\API\item\FannieSignage(array());
$mtP = $dbc->prepare('SELECT p.auto_par
    FROM MovementTags AS m 
        INNER JOIN products AS p ON m.upc=p.upc AND m.storeID=p.store_id
    WHERE m.upc=? AND m.storeID=?');
$updateMT = $dbc->prepare('
    UPDATE MovementTags
    SET lastPar=?,
        modified=' . $dbc->now() . '
    WHERE upc=?
        AND storeID=?');

$full = array();
$half = array();
foreach ($data as $k => $row) {
    if ($dbc->getValue($narrowP, array($row['upc'], $store))) {
        $row['full'] = false;
        $row['movementTag'] = $dbc->getValue($mtP, array($row['upc'], $store));
        $half[] = $row;
    } else {
        $row['full'] = true;
        $row['movementTag'] = $dbc->getValue($mtP, array($row['upc'], $store));
        $full[] = $row;
    }
}


$full= FpdfLib::sortProductsByPhysicalLocation($dbc, $full, $store);
$half= FpdfLib::sortProductsByPhysicalLocation($dbc, $half, $store);
$data = array_merge($full, $half);


$width = 52; // tag width in mm
$height = 31; // tag height in mm
$left = 15; // left margin
$top = 8; // top margin -5
$bTopOff = 0;

// undo margin if offset is true
if($offset) {
    $top = 32;
    $bTopOff = 17;
}

$pdf->SetTopMargin($top);  //Set top margin of the page
$pdf->SetLeftMargin($left);  //Set left margin of the page
$pdf->SetRightMargin($left);  //Set the right margin of the page
$pdf->SetAutoPageBreak(False); // manage page breaks yourself
$pdf->AddPage("L", "A6");  //Add page #1

$num = 1; // count tags
// full size tag settings
$full_x = $left;
$full_y = $top;

// half size tag settings
$upcX = 17;  //x location of barcode
$upcY = $top; //y locaton of barcode
$priceY = 14 + $top; //y location of size and price on label
$priceX = 18; //x location of date and price on label
$count = 0;  //number of labels created
$baseY = 31 + $bTopOff; // baseline Y location of label
$baseX = 16;  // baseline X location of label
$down = 31.0;

//cycle through result array of query
foreach($data as $rowIndex => $row) {
   // extract & format data

   if ($row['full']) {
        $price = $row['normal_price'];
        $desc = strtoupper(substr($row['description'],0,27));
        $brand = ucwords(strtolower(substr($row['brand'],0,13)));
        $pak = $row['units'];
        $size = $row['units'] . "-" . $row['size'];
        $sku = $row['sku'];
        $ppu = isset($row['pricePerUnit']) ? $row['pricePerUnit'] : '';
        $upc = ltrim($row['upc'],0);
        $check = $pdf->GetCheckDigit($upc);
        $vendor = substr(isset($row['vendor']) ? $row['vendor'] : '',0,7);

        /**
         * Full tags are further sub-divided.
         * A MovementTag has a slightly different
         * UPC placement and needs to update the related table
         */
        if ($row['movementTag']) {
            if (strlen($upc) <= 11) {
                $pdf->UPC_A($full_x+3,$full_y+4,$upc,7);  //generate barcode and place on label
            } else {
                $pdf->EAN13($full_x+3,$full_y+4,$upc,7);  //generate barcode and place on label
            }
            $pdf->SetXY($full_x+38, $full_y+4);
            $border = $mtLength == 7 ? 'TBR' : 'TBL';
            $pdf->Cell(9, 4, sprintf('%.1f', ($row['movementTag']*$mtLength)), $border, 1, 'C');
            $dbc->execute($updateMT, array(($row['movementTag']*$mtLength), $row['upc'], $store));
        } else {
            //Start laying out a label
            if (strlen($upc) <= 11)
            $pdf->UPC_A($full_x+3,$full_y+4,$upc,7);  //generate barcode and place on label
            else
            $pdf->EAN13($full_x+3,$full_y+4,$upc,7);  //generate barcode and place on label
        }

        $pdf->SetXY($full_x+38, $full_y+8);
        $pdf->Cell(9, 4, isset($locations[$upc]) ? current($locations[$upc]) : '', 0, 1, 'C');
        if (isset($locations[$upc])) {
            $key = key($locations[$upc]);
            if (isset($locations[$upc][$key+1])) {
                next($locations[$upc]);
            }
        }


        // add a blue dot to items in REFRIGERATED department STORE 2 = DENFELD = BLUE DOT = REFR
        $pdf->SetXY($full_x+48.5, $full_y+3);
        if (isset($dots[$upc]) && $dots[$upc] == 'REFRIGERATED'  && isset($locNames[$upc])) {
            foreach ($locNames[$upc] as $name) {
                if (strpos(strtoupper($name), 'BEV') !== false) {
                    $pdf->SetFillColor(0, 100, 255); // blue
                    if ($full_x < 100) {
                        // move the first two columns in a little
                        $pdf->Circle($full_x+48, $full_y+4.5, 1.25, 'F');
                    } else {
                        $pdf->Circle($full_x+48.5, $full_y+4.5, 1.25, 'F');
                    }
                    $pdf->SetFillColor(255,255,255);
                    $pdf->SetTextColor(255,255,255);
                    $pdf->SetFontSize(8);
                    //$pdf->Text($full_x+47.6, $full_y+5.3, 'r');
                    $pdf->SetFillColor(0, 0, 0);
                    $pdf->SetTextColor(0,0,0);
                }
            }
        }
        if (isset($dots[$upc]) && $dots[$upc] == 'PRODUCE') {
            $pdf->SetFillColor(0, 255, 100); // green 
            $pdf->Circle($full_x+48.5, $full_y+9.5, 1.25, 'F');
            $pdf->SetFillColor(255,255,255);
            $pdf->SetTextColor(255,255,255);
            $pdf->Text($full_x+47.3, $full_y+10, '');
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(0,0,0);
        }

        // add a red dot to items with > 1 physical location
        if (count(isset($locations[$upc]) ? $locations[$upc] : array()) > 1 && $store == 2) {
            $pdf->SetFillColor(255, 100, 0); // orange
            $pdf->SetDrawColor(255,0,0);
            $pdf->Circle($full_x+48.5, $full_y+7, 1.25, 'D');
            $pdf->SetFillColor(255,255,255);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFontSize(6);
            $pdf->Text($full_x+47.5, $full_y+7.5, '');
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetDrawColor(0,0,0);
        }
        $pdf->SetFillColor(0, 0, 0);

        // writing data
        // basically just set cursor position
        // then write text w/ Cell
        $pdf->SetFont('Arial','',8);  //Set the font
        $pdf->SetXY($full_x,$full_y+12);
        $pdf->Cell($width,4,$desc,0,1,'L');
        $pdf->SetX($full_x);
        $pdf->Cell($width,4,$brand,0,1,'L');
        $pdf->SetX($full_x);
        $pdf->Cell($width,4,$size,0,1,'L');
        $pdf->SetX($full_x);
        $pdf->Cell($width,4,$sku.' '.$vendor,0,0,'L');
        $pdf->SetX($full_x);
        $pdf->Cell($width-5,4,$ppu,0,0,'R');

        $lbMod = 0;
        if (strpos($size, '#') != 0) {
            $lbMod = -5;
            $pdf->SetFont('Arial','B',12);
            $pdf->SetXY($full_x,$full_y+17);
            $pdf->Cell($width-5,8,'/lb',0,0,'R');
        }

        $pdf->SetFont('Arial','B',24);  //change font size
        $pdf->SetXY($full_x+$lbMod,$full_y+16);
        $pdf->Cell($width-5,8,$price,0,0,'R');

   } else {
        $price = $row['normal_price'];
        $desc = strtoupper(substr($row['description'],0,27));
        $brand = ucwords(strtolower(substr($row['brand'],0,30)));
        $pak = $row['units'];
        $size = $row['units'] . "-" . $row['size'];
        $sku = $row['sku'];
        $upc = ltrim($row['upc'],0);
        $check = $pdf->GetCheckDigit($upc);
        $tagdate = date('m/d/y');
        $vendor = substr(isset($row['vendor']) ? $row['vendor'] : '',0,7);
        $vfixes = array(
            'ANCIENT' => 'ANCIEN',
            'SPROUT' => 'SPROU',
            'WILD FE' => 'WILDFE',
            'COUNTRY' => 'COUNTR',
            'HERB PH' => 'HERBPH',
            'HERBS E' => 'HERBETC',
            'UNFI' => '   UNFI',
            'THRESHO' => 'THRESH',
            'AMAZING' => 'AMAZNG',
        );
        if (array_key_exists($vendor, $vfixes)) {
            $vendor = $vfixes[$vendor];
        }
        $bfixes = array(
            'Natural Factors ' => 'Natural Fac',
            'Source Naturals ' => 'Source Nat',
            'Nordic Naturals ' => 'Nordic Nat',
            'Amazing Grass ' => 'AmazingGr',
            'Whole Foods C' => 'WFC',
            'Oregon\'s Wild H' => 'OWH',
            'Superior/finnegan\'s ' => 'Finnegan',
            'Thousand Hills ' => 'ThousHill',
            'Earth Science ' => 'EarthSci',
            'Sweet Land F' => 'Sweet Land',
            'Dr. Bronner\'s ' => 'Dr Bronner',
        );

        //Start laying out a label
        $pdf->SetFont('Arial','',8);  //Set the font
        $signage->drawBarcode($upc, $pdf, $upcX, $upcY, array('height'=>4, 'width'=>0.25, 'fontsize'=>6.5, 'align'=>'L'));
        if ($row['movementTag']) {
            $pdf->SetXY($upcX + 18, $upcY + 5);
            $border = $mtLength == 7 ? 'TBR' : 'TBL';
            $pdf->Cell(7, 4, sprintf('%.1f', ($row['movementTag']*$mtLength)), $border, 1, 'C');
            $dbc->execute($updateMT, array(($row['movementTag']*$mtLength), $row['upc'], $store));

        }

        $pdf->SetXY($full_x+20, $full_y+9);
        $pdf->Cell(9, 4, isset($locations[$upc]) ? current($locations[$upc]) : '', 0, 1, 'C');
        if (isset($locations[$upc])) {
            $key = key($locations[$upc]);
            if (isset($locations[$upc][$key+1])) {
                next($locations[$upc]);
            }
        }
        /*
        if (strlen($upc) <= 11)
            $pdf->UPC_A($upcX,$upcY,$upc,4,.25);  //generate barcode and place on label
        else
            $pdf->EAN13($upcX,$upcY,$upc,4,.25);  //generate barcode and place on label
         */


        $pdf->SetFont('Arial','B',18); //change font for price
        $pdf->TEXT($priceX,$priceY,$price);  //add price

        // print narrow tag cut guide line
        $pdf->SetDrawColor(200,200,200);
        $pdf->Line($priceX+29, $priceY-14, $priceX+29, $priceY+16);
        $pdf->SetDrawColor(0,0,0);

        $words = preg_split('/[\s,-]+/',$desc);
        $limit = 13;
        $lineheight = 0;
        $curStr = "";
        $length = 0;
        $lines = 0;
        foreach ($words as $word) {
            if ($length + strlen($word) <= $limit) {
                $curStr .= $word . ' ';
                $length += strlen($word) + 1;
            } else {
                $lines++;
                if ($lines >= 2) {
                    break;
                }
                $curStr = trim($curStr) . "\n" . $word . ' ';
                $length = strlen($word)+1;
            }
        }
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY($baseX, $baseY-7.5);
        $pdf->MultiCell(100, 3, $curStr);
        $pdf->SetX($baseX);
        $pdf->Cell(0, 3, $tagdate);
        $pdf->SetX($baseX+12);
        $pdf->Cell(0, 3, $size, 0, 1);

        $words = preg_split('/[ ,-]+/',$brand);
        $curStr = "";
        $curCnt = 0;
        $length = 0;
        foreach ($words as $word) {
           if ($curCnt == 0) {
               $curStr .= $word . " ";
               $length += strlen($word)+1;
           } elseif ($curCnt == 1 && ($length + strlen($word) + 1) < 17) {
               $curStr .= $word . " ";
               $length += strlen($word)+1;
           } elseif ($curCnt > 1 && ($length + 1) < 17) {
               $chars = str_split($word);
               foreach ($chars as $char) {
                   $curStr .= strtoupper($char);
                   $length += 2;
                   break;
                }
           }
           $curCnt++;
        }

        $printbrand = $curStr;
        if (array_key_exists($curStr, $bfixes)) {
            $brand= $bfixes[$curStr];
            $printbrand = $bfixes[$curStr];
        }
        $printbrand = substr($printbrand, 0, 10);

        $pdf->SetX($baseX);
        //$pdf->Cell(0, 3, "'".$printbrand."'");
        $pdf->Cell(0, 3, $printbrand);
        $pdf->SetX($baseX + 18);
        $pdf->Cell(0, 3, strtoupper($vendor));

        // add a blue dot to items in REFRIGERATED department
        $pdf->SetXY($baseX+48.5, $baseY+3);
        if ($dots[$upc] == 'REFRIGERATED' && $store == 2) {
            $pdf->SetFillColor(0, 100, 255); // blue
            $pdf->Circle($baseX+27.5, $baseY-10, 1.25, 'F');
            $pdf->SetFillColor(255,255,255);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFontSize(8);
            $pdf->Text($full_x+28.7, $baseY-9.3, 'r');
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(0,0,0);
        }

        // add green dot to produce
        if (isset($dots[$upc]) && $dots[$upc] == 'PRODUCE') {
            $pdf->SetFillColor(0, 255, 100); // green
            $pdf->Circle($baseX+27.5, $baseY-10, 1.25, 'F');

            $pdf->SetTextColor(255,255,255);
            $pdf->Text($full_x+27.9, $baseY-9.3, 'p');
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(0,0,0);
        }

        // add a red dot to items with > 1 physical location
        if (count(isset($locations[$upc]) ? $locations[$upc] : array()) > 1 && $store == 2) {
            $pdf->SetFillColor(255, 100, 0); // orange
            $pdf->Circle($baseX+27.5, $baseY-7.5, 1.25, 'F');
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFontSize(6);
            $pdf->Text($full_x+29, $baseY-7, 'm');
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(0,0,0);
        }
        $pdf->SetFillColor(0, 0, 0);
   }

   // move right by tag width

   // full size
   $full_x += $width;

   // half size
   $upcX = $upcX + 52.7;
   $priceX = $priceX + 52.7;
   $count = $count + 1;
   $baseX = $baseX + 52.7;

   // if it's the end of a page, add a new
   // one and reset x/y top left margins
   // otherwise if it's the end of a line,
   // reset x and move y down by tag height
   $modTagsPerPage = ($offset == 0) ? 32 : 24;
   $modTagsPerPage = 6;
   if ($num % $modTagsPerPage == 0){
       if (isset($data[$rowIndex+1])) {
        $pdf->AddPage();
       }
    // full size
    $full_x = $left;
    $full_y = $top;

    // half size
    $upcX = 17;  //x location of barcode
    $upcY = $top; //y locaton of barcode
    $priceY = 29 - 7 + $bTopOff; //y location of size and price on label
    $priceX = 18; //x location of date and price on label
    $count = 0;  //number of labels created
    $baseY = 31 + $bTopOff; // baseline Y location of label
    $baseX = 16;  // baseline X location of label
   }
   else if ($num % 2 == 0){
       // full size
    $full_x = $left;
    $full_y += $height;

      // half size
      $upcX = 17;
      $upcY = $upcY + $down;
      $priceX = 18;
      $priceY = $priceY + $down;
      $baseY = $baseY + $down;
      $baseX = 16;
   }

    /*
        Create Guidelines
    */
    $pdf->SetDrawColor(0x9B,0x9B,0x9B);
    // print top and left guide lines only once
    $y = $full_y;
    $x = $full_x;

    // Horizontal 
    $pdf->Rect(0, 6, 200, 0.01, 'DF'); // full line
    $pdf->Rect(0, 6+31, 200, 0.01, 'DF'); // full line
    $pdf->Rect(0, 6+(31*2), 200, 0.01, 'DF'); // full line
    $pdf->Rect(0, 6+(31*3), 200, 0.01, 'DF'); // full line

    // Vertical 
    //$pdf->Rect($x-3.3+3.2, 0, 0.01, $height*9, 'DF'); // full line
    $pdf->Rect(14.6, 0, 0.01, 200, 'DF'); // full line
    $pdf->Rect(14.6+52, 0, 0.01, 200, 'DF'); // full line
    $pdf->Rect(14.6+(52*2), 0, 0.01, 200, 'DF'); // full line
    //$pdf->Rect($x+55-3.2, 0, 0.01, $height*9, 'DF'); // full line

    $pdf->SetDrawColor(0, 0, 0);

   $num++;
}

    $pdf->Output();  //Output PDF file to screen.
}

