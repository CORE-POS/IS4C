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

class Signage2UpP extends FannieSignage 
{
    public function drawPDF()
    {
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->SetFont('Gill', '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $width = 203.2;
        $height = 133.35;
        $top = 35;
        $left = 5;
        foreach ($data as $item) {
            if ($count % 2 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = $sign;

            $pdf->SetXY($left, $top + ($row*$height));
            $pdf->Cell($width, 10, $item['brand'], 0, 1, 'C');
            $pdf->SetX($left);
            $pdf->Cell($width, 10, $item['description'], 0, 1, 'C');
            $pdf->SetX($left);
            $pdf->Cell($width, 10, $item['normal_price'], 0, 1, 'C');

            $count++;
            $sign++;
        }

        $pdf->Output('Signage4UpL.pdf', 'I');
    }
}

