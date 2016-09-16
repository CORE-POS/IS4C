<?php
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
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

class WFC_Hybrid_PDF extends FpdfWithBarcode
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
}

function WFC_Hybrid($data,$offset=0){

$pdf=new WFC_Hybrid_PDF('P','mm','Letter'); //start new instance of PDF
$pdf->Open(); //open new PDF Document
$pdf->setTagDate(date("m/d/Y"));
$dbc = FannieDB::get(FannieConfig::config('OP_DB'));
$narrowP = $dbc->prepare('SELECT upc FROM woodshed_no_replicate.NarrowTags WHERE upc=?');

$full = array();
$half = array();
foreach ($data as $row) {
    if ($dbc->getValue($narrowP, array($row['upc']))) {
        $row['full'] = false;
        $half[] = $row;
    } else {
        $row['full'] = true;
        $full[] = $row;
    }
}
$data = array_merge($full, $half);

$width = 52; // tag width in mm
$height = 31; // tag height in mm
$left = 5; // left margin
$top = 15; // top margin

// undo margin if offset is true
if($offset) {
    $top = 32;
}

$pdf->SetTopMargin($top);  //Set top margin of the page
$pdf->SetLeftMargin($left);  //Set left margin of the page
$pdf->SetRightMargin($left);  //Set the right margin of the page
$pdf->SetAutoPageBreak(False); // manage page breaks yourself
$pdf->AddPage();  //Add page #1

$num = 1; // count tags 
// full size tag settings
$full_x = $left;
$full_y = $top;

// half size tag settings
$i = 7;  //x location of barcode
$j = 31; //y locaton of barcode
$l = 30; //y location of size and price on label
$k = 8; //x location of date and price on label
$m = 0;  //number of labels created
$n = 18; //y location of description for label
$r = 24; //y location of date for label
$p = 6;  //x location fields on label
$t = 28; //y location of SKU and vendor info
$u = 24; //x locaiton of vendor info for label
$w = 7; //x location of Brand info for label
$half_x = 42; //y location of Brand info for label
$down = 31.0;

//cycle through result array of query

foreach($data as $row) {
   // extract & format data

   if ($row['full']) {
        $price = $row['normal_price'];
        $desc = strtoupper(substr($row['description'],0,27));
        $brand = ucwords(strtolower(substr($row['brand'],0,13)));
        $pak = $row['units'];
        $size = $row['units'] . "-" . $row['size'];
        $sku = $row['sku'];
        $ppu = $row['pricePerUnit'];
        $upc = ltrim($row['upc'],0);
        $check = $pdf->GetCheckDigit($upc);
        $vendor = substr($row['vendor'],0,7);

        //Start laying out a label 
        $newUPC = $upc . $check; //add check digit to upc
        if (strlen($upc) <= 11)
        $pdf->UPC_A($full_x+7,$full_y+4,$upc,7);  //generate barcode and place on label
        else
        $pdf->EAN13($full_x+7,$full_y+4,$upc,7);  //generate barcode and place on label

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

        $pdf->SetFont('Arial','B',24);  //change font size
        $pdf->SetXY($full_x,$full_y+16);
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
        $vendor = substr($row['vendor'],0,7);

        //Start laying out a label 
        $pdf->SetFont('Arial','',8);  //Set the font 

        $words = preg_split('/[ ,-]+/',$desc);
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
        $pdf->SetXY($p, $n-3);
        $pdf->MultiCell(100, 3, $curStr);

        //$pdf->TEXT($p,$n,$desc);   //Add description to label

        $pdf->TEXT($p,$r,$tagdate);  //Add date to label
        $pdf->TEXT($p+12,$r,$size);  //Add size to label

        $words = preg_split('/[ ,-]+/',$brand);
        $curStr = "";
        $curCnt = 0;
        $length = 0;
        foreach ($words as $word) {
           if ($curCnt == 0) {
               $curStr .= $word . " ";
               $length += strlen($word)+1;
           } elseif ($curCnt == 1 && ($length + strlen($word + 1)) < 17) {
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
        $pdf->TEXT($w,$half_x,$curStr);  //add brand
        $pdf->SetFont('Arial','B',18); //change font for price
        $pdf->TEXT($k,$l,$price);  //add price

        $newUPC = $upc . $check; //add check digit to upc
        if (strlen($upc) <= 11)
            $pdf->UPC_A($i,$j,$upc,4,.25);  //generate barcode and place on label
        else
            $pdf->EAN13($i,$j,$upc,4,.25);  //generate barcode and place on label
   }

   // move right by tag width

   // full size
   $full_x += $width;

   // half size
   $i = $i + 52.7;
   $k = $k + 52.7;
   $m = $m + 1;
   $p = $p + 52.7;
   $u = $u + 52.7;
   $w = $w + 52.7;

   // if it's the end of a page, add a new
   // one and reset x/y top left margins
   // otherwise if it's the end of a line,
   // reset x and move y down by tag height
   if ($num % 32 == 0){
    $pdf->AddPage();
    // full size
    $full_x = $left;
    $full_y = $top;
    
    // half size
    $i = 7;  //x location of barcode
    $j = 31; //y locaton of barcode
    $l = 30; //y location of size and price on label
    $k = 8; //x location of date and price on label
    $m = 0;  //number of labels created
    $m = 0;  //number of labels created
    $n = 18; //y location of description for label
    $r = 24; //y location of date for label
    $p = 6;  //x location fields on label
    $t = 28; //y location of SKU and vendor info
    $u = 24; //x location of vendor info for label
    $w = 7; //x location of Brand info for label
    $half_x = 42; //y location of Brand info for label
   }
   else if ($num % 4 == 0){
       // full size
    $full_x = $left;
    $full_y += $height;

      // half size
      $i = 7;
      $j = $j + $down;
      $k = 8;
      $l = $l + $down;
      $n = $n + $down;
      $r = $r + $down;
      $p = 6;
      $u = 24;
      $t = $t + $down;
      $w = 7;
      $half_x = $half_x + $down;
   }

   $num++;
}

    $pdf->Output();  //Output PDF file to screen.
}

