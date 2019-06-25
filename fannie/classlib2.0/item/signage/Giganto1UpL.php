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

namespace COREPOS\Fannie\API\item\signage;

class Giganto1UpL extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 250;
    protected $MED_FONT = 32;
    protected $SMALL_FONT = 28;
    protected $SMALLER_FONT = 22;
    protected $SMALLEST_FONT = 22;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 275;
    protected $height = 190;
    protected $top = 54;
    protected $left = 5;

    public function drawPDF()
    {
        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);

        $data = $this->loadItems();
        $count = 0;
        $row = 0;
        $effective_width = $this->width - (2*$this->left);
        foreach ($data as $item) {
            $pdf->AddPage();

            $price = $this->printablePrice($item);

            $pdf->SetXY($this->left, $this->top);
            $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
            $pdf->Cell($this->width, 12, strtoupper($item['brand']), 0, 1, 'C');
            $pdf->SetX($this->left);
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $item['description'] = str_replace("\r", '', $item['description']);
            $item['description'] = str_replace("\n", '', $item['description']);
            $pdf->Cell($this->width, 12, $item['description'], 0, 1, 'C');
            $pdf->SetX($this->left);
            $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
            /*
            $item['size'] = $this->formatSize($item['size'], $item);
            $pdf->Cell($this->width, 12, $item['size'], 0, 1, 'C');
             */
            if ($item['originShortName'] != '') {
                if (substr($lower, 0, 10) !== 'product of') {
                    $item['originShortName'] = 'Product of ' . trim($item['originShortName']);
                }
                $pdf->Cell($this->width, 12, $item['originShortName'], 0, 1, 'C');
            } else {
                $pdf->Ln(12);
            }
            $pdf->Ln(24);
            $pdf->SetX($this->left);
            $pdf->SetFont($this->font, '', $this->BIG_FONT);
            if (strstr($price, 'lb')) {
                $price = str_replace(' /lb.', '/lb.', $price);
                $pdf->SetFont($this->font, '', $this->BIG_FONT-29);
            } elseif (strstr($price, 'OFF/LB')) {
                $pdf->SetFont($this->font, '', $this->BIG_FONT-80);
            } elseif (strstr($price, 'OFF')) {
                $pdf->SetFont($this->font, '', $this->BIG_FONT-27);
            } 
            if (strstr($price, 'SAVE')) {
                $pdf->SetFont($this->font, '', $this->BIG_FONT-70);
            }
            $pdf->Cell($this->width, 50, $price, 0, 1, 'C');

            if ($this->validDate($item['startDate']) && $this->validDate($item['endDate'])) {
                $datestr = $this->getDateString($item['startDate'], $item['endDate']);
                $pdf->SetXY($this->left, $this->top + ($this->height - $this->top - 9));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
            }
            if (isset($item['nonSalePrice']) && $item['nonSalePrice'] > $item['normal_price']) {
                $pdf->SetXY($this->left, $this->top + ($this->height - $this->top - 9));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $text = sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
                $pdf->Cell($effective_width, 20, $text, 0, 1, 'L');
            }
            if (isset($item['nonSalePrice']) && $item['nonSalePrice'] > $item['normal_price']) {
                $pdf->SetXY($this->left, $this->top + ($this->height - $this->top - 9));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $saved = $item['nonSalePrice'] - $item['normal_price'];
                if (isset($item['signMultiplier']) && $item['signMultiplier'] > 1) {
                    $saved *= $item['signMultiplier'];
                }
                $format = sprintf('$%.2f', $saved);
                if (substr($format, -3) == '.00') {
                    $format = substr($format, 0, strlen($format) - 3);
                } elseif (substr($saved, 0, 3) == '$0.') {
                    $format = substr($saved, 3) . chr(0xA2);
                }
                if (isset($item['signMultiplier']) && $item['signMultiplier'] > 1) {
                    $format .= ' on ' . $item['signMultiplier'];
                }
                $pdf->Cell($effective_width, 20, 'You Save ' . $format, 0, 1, 'C');
            }

            $count++;
        }

        $pdf->Output('Giganto1UpL.pdf', 'I');
    }
}



