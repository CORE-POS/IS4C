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

namespace COREPOS\Fannie\Plugin\CoopDealsSigns {

class CoopDeals12UpDarkL extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 40;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;
    protected $SMALLER_FONT = 8;
    protected $SMALLEST_FONT = 6;

    protected $footer_image = 'cd_line_16.png';

    protected $width = 68.67;
    protected $left = 6.0;

    public function drawPDF()
    {
        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(3.175, 3.175, 3.175);
        $pdf->SetAutoPageBreak(false);
        define('FPDF_FONTPATH', dirname(__FILE__) . '/noauto/fonts/');
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
        $pdf->AddFont('GillBook', '', 'GillSansMTPro-Book.php');
        $pdf->SetFont('Gill', '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $width = 68.67;
        $height = 71;
        $top = 22;
        $left = 6.0;
        $effective_width = $width - $left;
        foreach ($data as $item) {
            if ($count % 12 == 0) {
                if ($count != 0) {
                    // draw tick marks again
                    // sometimes other content of the page
                    // overwrites them
                    $pdf->Line(2, $height+0.0, 6, $height+0.0);
                    $pdf->Line(2, (2*$height)+1.0, 6, (2*$height)+1.0);
                    $pdf->Line(4*$width-3, $height+0.0, 4*$width+1, $height+0.0);
                    $pdf->Line(4*$width-3, (2*$height)+1.0, 4*$width+1, (2*$height)+1.0);

                    $pdf->Line($width+1.5, 2, $width+1.5, 8);
                    $pdf->Line(2*$width+1.5, 2, 2*$width+1.5, 8);
                    $pdf->Line(3*$width+1.5, 2, 3*$width+1.5, 8);
                    $pdf->Line($width+1.5, (3*$height)-6, $width+1.5, 3*$height);
                    $pdf->Line(2*$width+1.5, (3*$height)-6, 2*$width+1.5, 3*$height);
                    $pdf->Line(3*$width+1.5, (3*$height)-6, 3*$width+1.5, 3*$height);
                }
                $pdf->AddPage();
                // draw tick marks for cutting
                $pdf->Line(2, $height+0.0, 6, $height+0.0);
                $pdf->Line(2, (2*$height)+1.0, 6, (2*$height)+1.0);
                $pdf->Line(4*$width-3, $height+0.0, 4*$width+1, $height+0.0);
                $pdf->Line(4*$width-3, (2*$height)+1.0, 4*$width+1, (2*$height)+1.0);

                $pdf->Line($width+1.5, 2, $width+1.5, 8);
                $pdf->Line(2*$width+1.5, 2, 2*$width+1.5, 8);
                $pdf->Line(3*$width+1.5, 2, 3*$width+1.5, 8);
                $pdf->Line($width+1.5, (3*$height)-6, $width+1.5, 3*$height);
                $pdf->Line(2*$width+1.5, (3*$height)-6, 2*$width+1.5, 3*$height);
                $pdf->Line(3*$width+1.5, (3*$height)-6, 3*$width+1.5, 3*$height);
                $sign = 0;
            }

            $row = floor($sign / 4);
            $column = $sign % 4;

            $price = $item['normal_price'];
            if ($item['scale']) {
                if (substr($price, 0, 1) != '$') {
                    $price = sprintf('$%.2f', $price);
                }
                $price .= ' /lb.';
            } elseif (isset($item['signMultiplier'])) {
                $price = $this->formatPrice($item['normal_price'], $item['signMultiplier']);
            } else {
                $price = $this->formatPrice($item['normal_price']);
            }

            $pdf->Image(dirname(__FILE__) . '/cd_head_16.png', ($left-1) + ($width*$column), ($top-19) + ($row*$height), $width-6);

            $pdf->SetXY($left + ($width*$column), $top + ($row*$height) + 1);
            $pdf->SetFont('Gill', 'B', $this->SMALL_FONT);
            $pdf->MultiCell($effective_width, 6, strtoupper($item['brand']), 0, 'C');

            /**
              This block attempts to write the description then
              checks how many lines it took. If the description was
              longer than two lines, it whites the whole thing out,
              drops one font size, and tries again. Calculating
              effective text size with smart line breaks seems
              really tough.
            */
            $pdf->SetFont('Gill', '', $this->MED_FONT);
            $pdf = $this->fitText($pdf, $this->MED_FONT, 
                $item['description'], array($column, 7, 2));

            $pdf->SetX($left + ($width*$column));
            $pdf->SetFont('GillBook', '', $this->SMALLER_FONT);
            $item['size'] = strtolower($item['size']);
            if (substr($item['size'], -1) != '.') {
                $item['size'] .= '.'; // end abbreviation w/ period
                $item['size'] = str_replace('fz.', 'fl oz.', $item['size']);
            }
            if (substr($item['size'], 0, 1) == '.') {
                $item['size'] = '0' . $item['size']; // add leading zero on decimal qty
            }
            if (strlen(ltrim($item['upc'], '0')) < 5 && $item['scale']) {
                $item['size'] = 'PLU# ' . ltrim($item['upc'], '0'); // show PLU #s on by-weight
            }
            $pdf->Cell($effective_width, 6, $item['size'], 0, 1, 'C');

            $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - 42));
            $pdf->SetFont('Gill', '', $this->BIG_FONT);
            $pdf->Cell($effective_width, 12, $price, 0, 1, 'C');

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                // intl would be nice
                $datestr = date('M d', strtotime($item['startDate']))
                    . chr(0x96) // en dash in cp1252
                    . date('M d', strtotime($item['endDate']));
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - 37));
                $pdf->SetFont('GillBook', '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width, 20, strtoupper($datestr), 0, 1, 'R');
            }

            if ($item['upc'] != '') {
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - 37));
                $pdf->SetFont('GillBook', '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width, 20, $item['upc'], 0, 1, 'L');
            }

            $pdf->Image(dirname(__FILE__) . '/' . $this->footer_image, ($left-1)+($width*$column), $top + ($height*$row) + ($height-$top-4), $width-6);

            $count++;
            $sign++;
        }

        $pdf->Output('Signage12UpL.pdf', 'I');
    }
}

}

