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

class Signage2UpP extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 96;
    protected $MED_FONT = 34;
    protected $SMALL_FONT = 24;
    protected $SMALLER_FONT = 18;
    protected $SMALLEST_FONT = 12;

    protected $font = 'Arial';

    public function drawPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $width = 203.2;
        $height = 138.35;
        $top = 43;
        $left = 5;
        $effective_width = $width - (2*$left);
        foreach ($data as $item) {
            if ($count % 2 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = $sign;
            $price = $this->printablePrice($item);

            $pdf->SetXY($left, $top + ($row*$height));
            $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
            $pdf->Cell($width, 10, $item['brand'], 0, 1, 'C');
            $pdf->SetX($left);
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $item['description'] = str_replace("\r", '', $item['description']);
            $item['description'] = str_replace("\n", '', $item['description']);
            $pdf->Cell($width, 10, $item['description'], 0, 1, 'C');
            $pdf->SetX($left);
            $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
            $item['size'] = $this->formatSize($item['size'], $item);
            $pdf->Cell($width, 10, $item['size'], 0, 1, 'C');
            $pdf->SetX($left);
            $pdf->SetFont($this->font, '', $this->BIG_FONT);
            $pdf->Cell($width, 40, $price, 0, 1, 'C');

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                $datestr = $this->getDateString($item['startDate'], $item['endDate']);
                $pdf->SetXY($left, $top + ($height*$row) + ($height - $top - 20));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
            }
            if ($item['originShortName'] != '' || isset($item['nonSalePrice'])) {
                $pdf->SetXY($left, $top + ($height*$row) + ($height - $top - 20));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $text = ($item['originShortName'] != '') ? $item['originShortName'] : sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
                $pdf->Cell($effective_width, 20, $text, 0, 1, 'L');
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Signage2UpL.pdf', 'I');
    }
}

}

namespace {
    class Signage2UpP extends \COREPOS\Fannie\API\item\signage\Signage2UpP {}
}


