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

class WFC_New_PDF extends FpdfWithBarcode
{
    private $tagdate;
    function setTagDate($str){
        $this->tagdate = $str;
    }

    function barcodeText($x, $y, $h, $barcode, $len)
    {
        $this->SetFont('Arial','',8);
        $this->Text($x,$y-$h+(17/$this->k),substr($barcode,-$len).' '.$this->tagdate);
    }
}

function WFC_New($data,$offset=0){

$pdf=new WFC_New_PDF('P','mm','Letter'); //start new instance of PDF
$pdf->Open(); //open new PDF Document
$pdf->setTagDate(date("m/d/Y"));

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
$x = $left;
$y = $top;
//cycle through result array of query

/*
for ($ocount=0;$ocount<$offset;$ocount++){
    // move right by tag width
    $x += $width;

    if ($num % 32 == 0){
        $pdf->AddPage();
        $x = $left;
        $y = $top;
    }
    else if ($num % 4 == 0){
        $x = $left;
        $y += $height;
    }

    $num++;
}
*/

foreach($data as $row){
/*
    if (strlen(ltrim($row['upc'], '0')) <= 4) continue;
    elseif (substr($row['upc'], -6) == '000000') continue;
*/
   // extract & format data
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
    $pdf->UPC_A($x+7,$y+4,$upc,7);  //generate barcode and place on label
   else
    $pdf->EAN13($x+7,$y+4,$upc,7);  //generate barcode and place on label

   // writing data
   // basically just set cursor position
   // then write text w/ Cell
   $pdf->SetFont('Arial','',8);  //Set the font 
   $pdf->SetXY($x,$y+12);
   $pdf->Cell($width,4,$desc,0,1,'L');
   $pdf->SetX($x);
   $pdf->Cell($width,4,$brand,0,1,'L');
   $pdf->SetX($x);
   $pdf->Cell($width,4,$size,0,1,'L');
   $pdf->SetX($x);
   $pdf->Cell($width,4,$sku.' '.$vendor,0,0,'L');
   $pdf->SetX($x);
   $pdf->Cell($width-5,4,$ppu,0,0,'R');

   $pdf->SetFont('Arial','B',24);  //change font size
   $pdf->SetXY($x,$y+16);
   $pdf->Cell($width-5,8,$price,0,0,'R');

   // move right by tag width
   $x += $width;

   // if it's the end of a page, add a new
   // one and reset x/y top left margins
   // otherwise if it's the end of a line,
   // reset x and move y down by tag height
   if ($num % 32 == 0){
    $pdf->AddPage();
    $x = $left;
    $y = $top;
   }
   else if ($num % 4 == 0){
    $x = $left;
    $y += $height;
   }

   $num++;
}

    $pdf->Output();  //Output PDF file to screen.
}

