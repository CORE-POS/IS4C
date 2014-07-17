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

class Tags4x8P extends FannieSignage 
{
    protected $BIG_FONT = 18;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;

    public function drawPDF()
    {
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');

        $width = 52; // tag width in mm
        $height = 31; // tag height in mm
        $left = 5; // left margin
        $top = 15; // top margin
        $pdf->SetTopMargin($top);  //Set top margin of the page
        $pdf->SetLeftMargin($left);  //Set left margin of the page
        $pdf->SetRightMargin($left);  //Set the right margin of the page
        $pdf->SetAutoPageBreak(False); // manage page breaks yourself

        $data = $this->loadItems();
        $num = 0; // count tags 
        $x = $left;
        $y = $top;
        foreach ($data as $item) {

            // extract & format data
            $price = $item['normal_price'];
            $desc = strtoupper(substr($item['posDescription'],0,27));
            $brand = ucwords(strtolower(substr($item['brand'],0,13)));
            $pak = $item['units'];
            $size = $item['units'] . "-" . $item['size'];
            $sku = $item['sku'];
            $ppu = $item['pricePerUnit'];
            $vendor = substr($item['vendor'],0,7);
            $upc = $item['upc'];

            if ($num % 32 == 0) {
                $pdf->AddPage();
                $x = $left;
                $y = $top;
            } else if ($num % 4 == 0) {
                $x = $left;
                $y += $height;
            }

            $args = array(
                'height' => 7,
                'valign' => 'T',
                'align' => 'L',
                'suffix' => date('  n/j/y'),
                'fontsize' => 8,
            );
            $pdf = $this->drawBarcode($upc, $pdf, $x + 7, $y + 4, $args);

            $pdf->SetFont('Gill', '', 8);

            $pdf->SetXY($x,$y+12);
            $pdf->Cell($width,4,$desc,0,1,'L');

            $pdf->SetX($x);
            $pdf->Cell($width,4,$brand,0,1,'L');

            $pdf->SetX($x);
            $pdf->Cell($width,4,$size,0,1,'L');

            $pdf->SetX($x);
            $pdf->Cell($width,4,$sku.' '.$vendor,0,0,'L');

            if (strstr($ppu, '/') && $ppu[strlen($ppu)-1] != '/') {
                $pdf->SetX($x);
                $pdf->Cell($width-5,4,$ppu,0,0,'R');
            }

            $pdf->SetXY($x, $y+16);
            $pdf->SetFont('Arial','B',24);  //change font size
            $pdf->Cell($width-5,8,$price,0,0,'R');

            // move right by tag width
            $x += $width;

            $num++;
        }

        $pdf->Output('Tags4x8P.pdf', 'I');
    }
}

