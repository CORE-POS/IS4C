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

class Signage4UpL extends FannieSignage 
{

    protected $BIG_FONT = 48;
    protected $MED_FONT = 24;
    protected $SMALL_FONT = 14;

    public function drawPDF()
    {
        $pdf = new FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(3.175, 3.175, 3.175);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->SetFont('Gill', '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $width = 136.52;
        $height = 105;
        $top = 35;
        $left = 15;
        $effective_width = $width - (2*$left);
        foreach ($data as $item) {
            if ($count % 4 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = floor($sign / 2);
            $column = $sign % 2;

            $price = sprintf('$%.2f', $item['normal_price']);
            if ($item['scale']) {
                $price .= ' / lb';
            }

            $pdf->SetXY($left + ($width*$column), $top + ($row*$height));
            $pdf->SetFontSize($this->SMALL_FONT);
            $pdf->Cell($effective_width, 10, $item['brand'], 0, 1, 'C');
            $pdf->SetX($left + ($width*$column));
            $pdf->SetFontSize($this->MED_FONT);
            $pdf->MultiCell($effective_width, 10, $item['description'], 0, 'C');
            $pdf->SetX($left + ($width*$column));
            $pdf->SetFontSize($this->BIG_FONT);
            $pdf->Cell($effective_width, 20, $price, 0, 1, 'C');

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                // intl would be nice
                $datestr = date('m/d/Y', strtotime($item['startDate']))
                        . ' - ' 
                        . date('m/d/Y', strtotime($item['endDate']));
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - $top - 20));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
            }

            if ($item['originName'] != '') {
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - $top - 20));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->Cell($effective_width, 20, $item['originName'], 0, 1, 'L');
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Signage4UpL.pdf', 'I');
    }
}

