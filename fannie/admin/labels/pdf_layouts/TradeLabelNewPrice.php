<?php
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FpdfLib')) {
    include(dirname(__FILE__) . '/FpdfLib.php');
}
class TradeLabelNewPrice_PDF extends FpdfWithBarcode
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

function TradeLabelNewPrice($data,$offset=0){

$pdf=new TradeLabelNewPrice_PDF('P','mm','Letter'); //start new instance of PDF
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
$left = 5; // left margin
$top = 15; // top margin
$bTopOff = 0;

if($offset) {
    $top = 32;
    $bTopOff = 17;
}

$pdf->SetTopMargin($top);  //Set top margin of the page
$pdf->SetLeftMargin($left);  //Set left margin of the page
$pdf->SetRightMargin($left);  //Set the right margin of the page
$pdf->SetAutoPageBreak(False); // manage page breaks yourself
$pdf->AddPage();  //Add page #1

$num = 1; // count tags
$full_x = $left;
$full_y = $top;

$upcX = 7;  //x location of barcode
$upcY = $top; //y locaton of barcode
$priceY = 14 + $top; //y location of size and price on label
$priceX = 8; //x location of date and price on label
$count = 0;  //number of labels created
$baseY = 31 + $bTopOff; // baseline Y location of label
$baseX = 6;  // baseline X location of label
$down = 31.0;

foreach($data as $rowIndex => $row) {
   // extract & format data

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
    /*
        Top of the tag
    */
    $pdf->SetXY($upcX + 18, $upcY + 5);

    $pdf->SetXY($full_x+20, $full_y+9);
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

    $lbMod = 0;
    $lbText = '';
    if (strpos($size, '#') != 0) {
        $lbMod = -5;
        $lbText = '/lb';
    }

    /*
        Print The Price
    */
    $pdf->SetFont('Arial','B',18); //change font for price
    $pdf->TEXT($priceX,$priceY-8,"$".$price.$lbText);  //add price


    /*
        Print The PLU (for reference only) 
        & Description
    */

    $pdf->TEXT($priceX+31,$priceY-8,$upc);  //add price
    $pdf->SetFont('Arial','',8); //change font for price
    $pdf->TEXT($priceX,$priceY+8,$desc);  //add price
    $pdf->SetFont('Arial','B',18); //change font for price

    /*
        Draw Lines
    */
    $pdf->SetDrawColor(200,200,200);
    // Vertical Line 
    $pdf->Line($priceX+29, $priceY-14, $priceX+29, $priceY+16);
    // Horizontal Lines 
    $pdf->Line($priceX, $priceY-15, $priceX+$width, $priceY-15);
    $pdf->Line($priceX, $priceY-0, $priceX+$width, $priceY-0);
    $pdf->SetDrawColor(0,0,0);


    /*
        Print the Vendor & SKU
    */
    $pdf->SetFont('Arial','',8);
    $pdf->SetXY($priceX-2.5, $priceY-6);
    $string = strtoupper($vendor)."# ".$sku;
    $string = str_replace(" ", "", $string);
    $pdf->Cell($width/2, 5, $string, 0, 0, 'L'); 


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
   if ($num % $modTagsPerPage == 0){
       if (isset($data[$rowIndex+1])) {
            $pdf->AddPage();
       }
        // full size
        $full_x = $left;
        $full_y = $top;

        // half size
        $upcX = 7;  //x location of barcode
        $upcY = $top; //y locaton of barcode
        $priceY = 29 + $bTopOff; //y location of size and price on label
        $priceX = 8; //x location of date and price on label
        $count = 0;  //number of labels created
        $baseY = 31 + $bTopOff; // baseline Y location of label
        $baseX = 6;  // baseline X location of label
   }
   else if ($num % 4 == 0){
       // full size
    $full_x = $left;
    $full_y += $height;

      // half size
      $upcX = 7;
      $upcY = $upcY + $down;
      $priceX = 8;
      $priceY = $priceY + $down;
      $baseY = $baseY + $down;
      $baseX = 6;
   }

   $num++;
}

    $pdf->Output();  //Output PDF file to screen.
}

