<?php
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
class WFC_Aisle_Tags_Full_PDF extends FpdfWithBarcode
{
    private $tagdate;
    //$dbc->$this->connection;

    function setTagDate($str){
        $this->tagdate = $str;
    }

    function barcodeText($x, $y, $h, $barcode, $len)
    {
        $this->SetFont('Arial','',8);
        $this->Text($x,$y-$h+(17/$this->k),substr($barcode,-$len).' '.$this->tagdate);
    }
}

/*
    Autogenerate entire store by query
$storeID = 2;
$args = array($storeID);
$prep = $dbc->prepare("
    SELECT fs.name, s.floorSectionID,
    GROUP_CONCAT(DISTINCT s.subSection ORDER BY s.subSection ASC),
    SUBSTRING(GROUP_CONCAT(DISTINCT s.subSection ORDER BY s.subSection ASC), 1,1) AS Min ,
    SUBSTRING(GROUP_CONCAT(DISTINCT s.subSection ORDER BY s.subSection ASC), -1) AS Max 
    FROM FloorSubSections AS s
    LEFT JOIN FloorSections AS fs ON fs.floorSectionID=s.floorSectionID
    WHERE fs.storeID = ?
    GROUP BY floorSectionID
    ORDER BY fs.name, s.subSection
");
$res = $dbc->execute($prep, $args);
while ($row = $dbc->fetchRow($res)) {
    $min = $row['min'];
    $max = $row['max'];
    $name = $row['name'];
    $tmp = explode(" ", $name);

    $num = end($tmp);
    $name = $tmp[0];

    $aisles[] = array($name, $num, $min, $max);
}
*/

/*
    All data can be manually entered below
    $aisles[] = array('Grocery', 1, 'A', 'I');
*/
$aisles = array();

$aisles[] = array("Meat", null, "A","G");
//$aisles[] = array("Grocery 5", null, "D","D");

$data = array();
$i=0;

foreach ($aisles as $arr) {
    $min = $arr[2];
    $max = $arr[3];
    foreach (range($min, $max) as $v) {
        $data[$i]['subsection'] = $arr[1]."$v";
        $data[$i]['aisle'] = $arr[0];
        $i++;
        $data[$i]['subsection'] = $arr[1]."$v";
        $data[$i]['aisle'] = $arr[0];
        $i++;
    }
}


function WFC_Aisle_Tags_Full($data,$offset=0){

    $pdf=new WFC_Aisle_Tags_Full_PDF('P','mm','Letter'); //start new instance of PDF
    $pdf->Open(); //open new PDF Document
    $pdf->setTagDate(date("m/d/Y"));

    $width = 52; // tag width in mm 53 = full tag
    //$width = 30; // tag width in mm 30 = narrow tag

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

    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 48);

    foreach($data as $row){
       // extract & format data
       if (isset($row['normal_price']))
           $price = $row['normal_price'];
       if (isset($row['description']))
           $desc = strtoupper(substr($row['description'],0,27));
       if (isset($row['brand']))
           $brand = ucwords(strtolower(substr($row['brand'],0,13)));
       if (isset($row['units']))
           $pak = $row['units'];
       if (isset($row['size']))
           $size = $row['units'] . "-" . $row['size'];
       if (isset($row['sku']))
           $sku = $row['sku'];
       if (isset($row['pricePerUnit']))
           $ppu = $row['pricePerUnit'];
       if (isset($row['upc']))
           $upc = ltrim($row['upc'],0);
       if (isset($upc))
           $check = $pdf->GetCheckDigit($upc);
       if (isset($row['vendor']))
           $vendor = substr($row['vendor'],0,7);


       //$pdf->SetFont('Arial','',48);  //Set the font 
       //$pdf->SetFontColor(255,255,255);

       // "yellow" top & bottom border
       $pdf->SetFillColor(255,255,255);
       $pdf->SetDrawColor(255,255,255);
       $pdf->Rect($x, $y, $width, $height, 'F');

       //grey border 
       $pdf->SetFillColor(155,155,155);
       $pdf->SetDrawColor(155,155,155);
       $pdf->Rect($x, $y, $width, $height);

       /* blue interior
       $pdf->SetFillColor(100,100,255);
       $pdf->SetDrawColor(100,100,255);
       $pdf->Rect($x, $y+5, $width, $height-10, 'F');
       */

       // black interior
       $pdf->SetFillColor(0, 0, 0);
       $pdf->SetDrawColor(0, 0, 0);
       $pdf->Rect($x, $y+5, $width, $height-10, 'F');
       

       // print sub section text 
       $pdf->SetFont('Gill','B', 48);
       $pdf->SetFillColor(255,0,0);
       $pdf->SetDrawColor(255,0,0);
       $pdf->SetTextColor(255,255,255);
       $pdf->SetXY($x+1, $y+6);
       //$subsection = $row;
       $pdf->Cell($width-2, $height-12, $row['subsection'], 0, 0, 'C');

       // aisle text
       $pdf->SetFont('Gill','B', 10);
       $pdf->SetFillColor(255,0,0);
       $pdf->SetDrawColor(255,0,0);
       $pdf->SetTextColor(0,0,0);
       $pdf->SetXY($x+1, $y+0.75);
       $pdf->Cell($width-2, 4, $row['aisle'], 0, 0, 'C');
       $pdf->SetXY($x+1, $y+26.75);
       $pdf->Cell($width-2, 4, $row['aisle'], 0, 0, 'C');

        /*
            Testing adding icons
            glutenfree-icon.png  inclusive-icon.png   local-icon.png       new-icon.png         vegan-icon.png

        $IconImages = array(
             __DIR__ . '/noauto/glutenfree-icon.png',
             __DIR__ . '/noauto/inclusive-icon.png',
             __DIR__ . '/noauto/local-icon.png',
             __DIR__ . '/noauto/new-icon.png',
             __DIR__ . '/noauto/vegan-icon.png',
        );
        //$IconImg = __DIR__ . '/noauto/inclusive-icon.png';
        $column = 4;
        $row = 1;
        $pdf->Image($IconImages[0], $x, $y, 15, 15);
        $pdf->Image($IconImages[1], $x+15, $y, 15, 15);
        $pdf->Image($IconImages[2], $x+15, $y+15, 15, 15);
        $pdf->Image($IconImages[3], $x, $y+15, 15, 15);
        */




       // move right by tag width
       $x += $width;

       // if it's the end of a page, add a new
       // one and reset x/y top left margins
       // otherwise if it's the end of a line,
       // reset x and move y down by tag height
       if ($num % 56 == 0){
        $pdf->AddPage();
        $x = $left;
        $y = $top;
       }
       //else if ($num % 7 == 0){
       else if ($num % 4 == 0){
        $x = $left;
        $y += $height;
       }

       $num++;
    }

    $pdf->Output();  //Output PDF file to screen.
}

