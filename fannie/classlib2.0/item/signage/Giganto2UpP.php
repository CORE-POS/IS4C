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

class Giganto2UpP extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 200;
    protected $MED_FONT = 22;
    protected $SMALL_FONT = 18;
    protected $SMALLER_FONT = 14;
    protected $SMALLEST_FONT = 10;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 203.2;
    protected $height = 138.35;
    protected $top = 44;
    protected $left = 5;

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
        $effective_width = $this->width - (2*$this->left);
        foreach ($data as $item) {
            if ($count % 2 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = $sign;
            $price = $this->printablePrice($item);

            $pdf->SetXY($this->left, $this->top + ($row*$this->height));
            $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
            $pdf->Cell($this->width, 8, strtoupper($item['brand']), 0, 1, 'C');
            $pdf->SetX($this->left);
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $item['description'] = str_replace("\r", '', $item['description']);
            $item['description'] = str_replace("\n", '', $item['description']);
            $pdf->Cell($this->width, 8, $item['description'], 0, 1, 'C');
            $pdf->SetX($this->left);
            /* skip over size for the sake of spacing
            $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
            $item['size'] = $this->formatSize($item['size'], $item);
            $pdf->Cell($this->width, 8, $item['size'], 0, 1, 'C');
            */
            $pdf->Ln(8);
            $pdf->SetX($this->left);
            $pdf->SetFont($this->font, '', $this->BIG_FONT);
            if (strstr($price, 'lb')) {
                $price = str_replace(' /lb.', '/lb.', $price);
                $pdf->SetFont($this->font, '', $this->BIG_FONT-29);
            } elseif (strstr($price, 'OFF/LB')) {
                $pdf->SetFont($this->font, '', $this->BIG_FONT-80);
            } elseif (strstr($price, 'OFF')) {
                $pdf->SetFont($this->font, '', $this->BIG_FONT-27);
            } elseif (strstr($price, 'SAVE')) {
                $pdf->SetFont($this->font, '', $this->BIG_FONT-60);
            }
            $pdf->Cell($this->width, 50, $price, 0, 1, 'C');

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                $datestr = $this->getDateString($item['startDate'], $item['endDate']);
                $pdf->SetXY($this->left, $this->top + ($this->height*$row) + ($this->height - $this->top - 19));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
            }
            if ($item['originShortName'] != '' || isset($item['nonSalePrice'])) {
                $pdf->SetXY($this->left, $this->top + ($this->height*$row) + ($this->height - $this->top - 19));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $text = ($item['originShortName'] != '') ? $item['originShortName'] : sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
                $pdf->Cell($effective_width, 20, $text, 0, 1, 'L');
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Giganto2UpP.pdf', 'I');
    }
}

}


