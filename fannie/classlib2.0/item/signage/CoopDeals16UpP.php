<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

namespace COREPOS\Fannie\API\item\signage {

class CoopDeals16UpP extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 36;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 8;

    public function drawPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(0, 3.175, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->SetFont('Gill', '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $width = 53.975;
        $height = 66.68;
        $top = 20;
        $left = 5.175;
        $effective_width = $width - (2*$left);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        foreach ($data as $item) {
            if ($count % 16 == 0) {
                $pdf->AddPage();
                // draw tick marks for cutting
                $pdf->Line(2, $height+1.5, 6, $height+1.5);
                $pdf->Line(2, (2*$height)+1.5, 6, (2*$height)+1.5);
                $pdf->Line(2, (3*$height)+1.5, 6, (3*$height)+1.5);
                $pdf->Line($width, 2, $width, 6);
                $pdf->Line(2*$width, 2, 2*$width, 6);
                $pdf->Line(3*$width, 2, 3*$width, 6);
                $pdf->Line($width, (4*$height)-4, $width, 4*$height);
                $pdf->Line(2*$width, (4*$height)-4, 2*$width, 4*$height);
                $pdf->Line(3*$width, (4*$height)-4, 3*$width, 4*$height);
                $pdf->Line(4*$width-6, $height+1.5, 4*$width-2, $height+1.5);
                $pdf->Line(4*$width-6, (2*$height)+1.5, 4*$width-2, (2*$height)+1.5);
                $pdf->Line(4*$width-6, (3*$height)+1.5, 4*$width-2, (3*$height)+1.5);
                $sign = 0;
            }

            $row = floor($sign / 4);
            $column = $sign % 4;

            $price = sprintf('$%.2f', $item['normal_price']);
            if ($item['scale']) {
                $price .= ' / lb';
            }
            $pdf->Image(dirname(__FILE__) . '/cd_head_16.png', ($left-2) + ($width*$column), ($top-17) + ($row*$height), $width-6);
            $pdf->SetXY($left + ($width*$column), $top + ($row*$height) - 2);
            $pdf->SetFontSize($this->SMALL_FONT);
            $pdf->MultiCell($effective_width, 6, $item['brand'], 0, 'C');
            $pdf->SetX($left + ($width*$column));
            $pdf->SetFontSize($this->MED_FONT);
            $pdf->MultiCell($effective_width, 6, $item['description'], 0, 'C');
            $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height-$top-18));
            $pdf->SetFontSize($this->BIG_FONT);
            $pdf->MultiCell($effective_width, 10, $price, 0, 'C');

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                // intl would be nice
                $datestr = _('Thru') . ' ' . date('n/j/y', strtotime($item['endDate'])) . '  '; // margin padding
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - $top - 7));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->Cell($effective_width+4, 6, $datestr, 0, 1, 'R');
            }

            if ($item['upc'] != '') {
                $pdf->SetXY($left-2 + ($width*$column), $top + ($height*$row) + ($height - $top - 7));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->Cell($effective_width, 6, $item['upc'], 0, 1, 'L');
            }

            $pdf->Image(dirname(__FILE__) . '/cd_line_16.png', ($left-2)+($width*$column), $top + ($height*$row) + ($height-$top-2), $width-6);

            $count++;
            $sign++;
        }

        $pdf->Output('Signage16UpP.pdf', 'I');
    }
}

}

namespace {
    class CoopDeals16UpP extends \COREPOS\Fannie\API\item\signage\CoopDeals16UpP {}
}

