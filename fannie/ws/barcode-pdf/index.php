<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

$upc = FormLib::get('upc');
$name = FormLib::get('name');
if ($upc === '') {
    echo '<strong>Error</strong>: No UPC provided';
    return false;
}

if ($name === '') {
    $name = $upc;
}

if (!class_exists('FPDF')) {
    include(dirname(__FILE__) . '/../../src/fpdf/fpdf.php');
}

$pdf = new FPDF('P', 'mm', 'Letter');
$pdf->AddPage();
$pdf->SetFont('Arial','',8);
$upc = ltrim($upc, '0'); // let library deal with check digits
$signage = new \COREPOS\Fannie\API\item\FannieSignage(array());
for ($i=0; $i<32; $i++) {
    $col = $i % 4;
    $row = floor($i / 4);
    $signage->drawBarcode($upc, $pdf, 50*$col + 10, 32*$row + 10);
}
$pdf->Output($name . '.pdf', 'I');

