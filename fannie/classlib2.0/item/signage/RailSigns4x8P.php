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

class RailSigns4x8P extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 10;
    protected $MED_FONT = 8;
    protected $SMALL_FONT = 7;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    public function drawPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        if (\COREPOS\Fannie\API\FanniePlugin::isEnabled('CoopDealsSigns')) {
            $this->font = 'Gill';
            $this->alt_font = 'GillBook';
            define('FPDF_FONTPATH', dirname(__FILE__) . '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
            $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
            $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
        }

        $width = 52; // tag width in mm
        $bar_width = 50;
        $height = 31; // tag height in mm
        $left = 5.5; // left margin
        $top = 15; // top margin
        $pdf->SetTopMargin($top);  //Set top margin of the page
        $pdf->SetLeftMargin($left);  //Set left margin of the page
        $pdf->SetRightMargin($left);  //Set the right margin of the page
        $pdf->SetAutoPageBreak(False); // manage page breaks yourself

        $data = $this->loadItems();
        $num = 0; // count tags 
        $x = $left;
        $y = $top;
        $sign = 0;
        foreach ($data as $item) {

            // extract & format data
            $price = $item['normal_price'];
            $desc = $item['description'];
            $brand = strtoupper($item['brand']);

            $price = $item['normal_price'];
            if ($item['scale']) {
                if (substr($price, 0, 1) != '$') {
                    $price = sprintf('$%.2f', $price);
                }
                $price .= ' /lb.';
            } else {
                $price = $this->formatPrice($item['normal_price']);
            }

            if ($num % 32 == 0) {
                $pdf->AddPage();
                $x = $left;
                $y = $top;
                $sign = 0;
            } else if ($num % 4 == 0) {
                $x = $left;
                $y += $height;
            }

            $row = floor($sign / 4);
            $column = $sign % 4;

            $pdf->SetFillColor(0x0, 0x0, 0x0);
            $pdf->Rect($left + ($width*$column), $top + ($row*$height), $bar_width, 5, 'F');
            $pdf->Rect($left + ($width*$column), $top + ($row*$height) + 25, $bar_width, 2, 'F');

            $pdf->SetXY($left + ($width*$column), $top + ($row*$height)+6);
            $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
            $pdf->MultiCell($width, 5, $brand, 0, 'C');

            $pdf->SetX($left + ($width*$column));
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $pdf->MultiCell($width, 5, $item['description'], 0, 'C');

            $pdf->SetX($left + ($width*$column));
            $pdf->SetFont($this->font, '', $this->BIG_FONT);
            $pdf->Cell($width, 8, $price, 0, 1, 'C');

            // move right by tag width
            $x += $width;

            $num++;
            $sign++;
        }

        $pdf->Output('Tags4x8P.pdf', 'I');
    }
}

}

namespace {
    class RailSigns4x8P extends \COREPOS\Fannie\API\item\signage\RailSigns4x8P {}
}

