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

class Signage12UpL extends FannieSignage 
{
    protected $BIG_FONT = 24;
    protected $MED_FONT = 18;
    protected $SMALL_FONT = 12;

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
        $width = 66.67;
        $height = 70;
        $top = 18;
        $left = 10;
        $effective_width = $width - $left;
        foreach ($data as $item) {
            if ($count % 12 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = floor($sign / 4);
            $column = $sign % 4;

            $price = sprintf('$%.2f', $item['normal_price']);
            if ($item['scale']) {
                $price .= ' / lb';
            }

            $pdf->SetXY($left + ($width*$column), $top + ($row*$height));
            $pdf->SetFontSize($this->SMALL_FONT);
            $pdf->MultiCell($effective_width, 8, $item['brand'], 0, 'C');
            $pdf->SetX($left + ($width*$column));
            $pdf->SetFontSize($this->MED_FONT);
            $pdf->MultiCell($effective_width, 8, $item['description'], 0, 'C');
            $pdf->SetX($left + ($width*$column));
            $pdf->SetFontSize($this->BIG_FONT);
            $pdf->Cell($effective_width, 10, $price, 0, 1, 'C');

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                // intl would be nice
                $datestr = _('Thru') . ' ' . date('m/d/Y', strtotime($item['endDate']));
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - $top - 15));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
            }

            if ($item['originShortName'] != '') {
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - $top - 15));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->Cell($effective_width, 20, $item['originShortName'], 0, 1, 'L');
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Signage12UpL.pdf', 'I');
    }
}

