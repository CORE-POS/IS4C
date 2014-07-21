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

class Signage16UpP extends FannieSignage 
{
    protected $BIG_FONT = 18;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;

    public function drawPDF()
    {
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(0, 3.175, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->SetFont('Gill', '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $width = 53.975;
        $height = 66.68;
        $top = 15;
        $left = 5.175;
        $effective_width = $width - (2*$left);
        foreach ($data as $item) {
            if ($count % 16 == 0) {
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
            $pdf->MultiCell($effective_width, 6, $item['brand'], 0, 'C');
            $pdf->SetX($left + ($width*$column));
            $pdf->SetFontSize($this->MED_FONT);
            $pdf->MultiCell($effective_width, 6, $item['description'], 0, 'C');
            $pdf->SetX($left + ($width*$column));
            $pdf->SetFontSize($this->BIG_FONT);
            $pdf->MultiCell($effective_width, 8, $price, 0, 'C');

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                // intl would be nice
                $datestr = _('Thru') . ' ' . date('n/j/y', strtotime($item['endDate'])) . '  '; // margin padding
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - $top - 10));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->Cell($effective_width, 6, $datestr, 0, 1, 'R');
            }

            if ($item['originShortName'] != '') {
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - $top - 10));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->Cell($effective_width, 6, $item['originShortName'], 0, 1, 'L');
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Signage16UpP.pdf', 'I');
    }
}

