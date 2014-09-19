<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

if (isset($_REQUEST['upc'])){
    define('FPDF_FONTPATH','font/');
    include($FANNIE_ROOT.'src/fpdf/fpdf.php');

    $pdf=new FPDF('P','mm','Letter'); //start new instance of PDF
    $pdf->Open(); //open new PDF Document

    $count = 0;
    $x = 0;
    $y = 0;
    $date = date("m/d/Y");
    for($i=0;$i<4;$i++){
        if ($count % 4 == 0){ 
            $pdf->AddPage();
            $pdf->SetDrawColor(0,0,0);
            $pdf->Line(108,0,108,279);
            $pdf->Line(0,135,215,135);
        }

        $x = $count % 2 == 0 ? 5 : 115;
        $y = ($count/2) % 2 == 0 ? 10 : 145;
        $pdf->SetXY($x,$y);

        $tmp = explode(":",$toid);

        $pdf->SetFont('Arial','','12');
        $pdf->Text($x+85,$y,"1 / 1");

        $pdf->SetFont('Arial','B','24');
        $pdf->Cell(100,10,'WFC Special',0,1,'C');
        $pdf->SetFont('Arial','','12');
        $pdf->SetX($x);
        $pdf->SetFont('Arial','','16');
        $pdf->Cell(100,9,$_REQUEST['desc'],0,1,'C');
        $pdf->SetX($x);
        $pdf->Cell(100,9,"",0,1,'C');
        $pdf->SetX($x);
        $pdf->SetFont('Arial','B','16');
        $txt = explode("\n",wordwrap($_REQUEST['disc'],30));
        foreach($txt as $t){
            $pdf->Cell(100,9,$t,0,1,'C');
            $pdf->SetX($x);
        }
        $pdf->SetFont('Arial','','12');
        if (!isset($_REQUEST['owner'])){
            $pdf->Cell(100,9,'Sale Price',0,1,'C');
            $pdf->SetX($x);

        }
        else{
            $pdf->Cell(100,9,'Owner-only Special',0,1,'C');
            $pdf->SetX($x);
        }
        $pdf->Cell(100,6,"Print Date: ".$date,0,1,'C');
        $pdf->SetX($x);
        $pdf->Cell(100,6,"Dept #".$_REQUEST['dept'],0,1,'C');
        $pdf->SetX($x);
        $pdf->Cell(100,6,"",0,1,'C');
        $pdf->SetXY($x,$y+85);
        $pdf->Cell(160,10,"Notes: _________________________________");  
        $pdf->SetX($x);
        
        $upc = str_pad($_REQUEST['upc'],11,'0',STR_PAD_LEFT);
        $upc = $_REQUEST['upc'];

        $pdf = FannieSignage::drawBarcode($upc, $pdf, $x+30, $y+95, array('height'=>14,'fontsize'=>8));
        
        $count++;
    }

    $pdf->Output();
    exit;
}

$page_title = "Fannie :: Special Orders";
$header = "Special Orders";
include($FANNIE_ROOT.'src/header.html');

echo '<form action="genericpdf.php" method="get">';
echo '<table cellpadding="0" cellspacing="4">';
echo '<tr><th>UPC</th><td><input type="text" name="upc" /></td></tr>';
echo '<tr><th>Main Text</th><td><input type="text" name="desc" /></td></tr>';
echo '<tr><th>Discount Text</th><td><input type="text" name="disc" /></td></tr>';
echo '<tr><th>Dept#</th><td><input type="text" name="dept" /></td></tr>';
echo '<tr><th>Owner-only</th><td><input type="checkbox" name="owner" /></td></tr>';
echo '</table>';
echo '<br />';
echo '<input type="submit" value="Print Tags" />';
echo '</form>';

include($FANNIE_ROOT.'src/footer.html');
?>
