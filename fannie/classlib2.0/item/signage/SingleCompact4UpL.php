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

class SingleCompact4UpL extends \COREPOS\Fannie\API\item\FannieSignage 
{

    protected $BIG_FONT = 130;
    protected $MED_FONT = 18;
    protected $SMALL_FONT = 14;
    protected $SMALLER_FONT = 11;
    protected $SMALLEST_FONT = 8;
    protected $BOGO_BIG_FONT = 80;
    protected $BOGO_MED_FONT = 23;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 139 + 10; 
    protected $height = 108;
    //protected $top = 30;
    protected $top = 30 + 5; // just until fresh deals is over
    protected $left = 16; // only moves regular price & while supplies last

    protected function createPDF()
    {
        //$pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf = new \FPDF('L', 'mm', array(105, 148));
        $pdf->SetMargins(3.175, 3.175, 3.175);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);

        return $pdf;
    }

    protected function drawItem($pdf, $item, $row, $column)
    {
        $item['description'] = preg_replace("/[^\x01-\x7F]/"," ", $item['description']);
        $item['description'] = str_replace("  ", " ", $item['description']);
        $effective_width = $this->width - (2*$this->left);
        $price = $this->printablePrice($item);

        $pdf->SetXY($this->left + ($this->width*$column) + 5, $this->top + ($row*$this->height));
        $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
        $pdf->Cell($effective_width, 10, strtoupper($item['brand']), 0, 1, 'C');
        $pdf->SetX($this->left + ($this->width*$column));
        $pdf->SetFont($this->font, '', $this->MED_FONT);
        $item['description'] = str_replace("\r", '', $item['description']);
        $item['description'] = preg_replace("/[^\x01-\x7F]/"," ", $item['description']);
        $item['description'] = str_replace("  ", " ", $item['description']);
        $pdf->Cell($effective_width, 6, str_replace("\n", '', $item['description']), 0, 1, 'C');

        $pdf->SetX($this->left + ($this->width*$column));
        $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
        $item['size'] = $this->formatSize($item['size'], $item);
        $pdf->Cell($effective_width, 6, $item['size'], 0, 1, 'C');

        if (!isset($item['signMultiplier']) || $item['signMultiplier'] != -3) {
            $pdf->SetXY($this->left + ($this->width*$column)+5, $this->top + ($row*$this->height) + 30);
            $pdf->SetFont($this->font, '', $this->BIG_FONT-32);
            if (strstr($price, 'lb')) {
                $price = str_replace(' /lb.', '/lb.', $price);
                $pdf->SetFont($this->font, '', $this->BIG_FONT-29);
            } elseif (strstr($price, 'OFF/LB')) {
                $pdf->SetFont($this->font, '', $this->BIG_FONT-45);
            } elseif (strstr($price, 'OFF')) {
                if (strstr($price, '.')) {
                    // price has decimal point in it
                    $pdf->SetFont($this->font, '', $this->BIG_FONT-42);
                } elseif (2) {
                    $pdf->SetFont($this->font, '', $this->BIG_FONT-27);
                }
            } 
            if (strstr($price, 'SAVE')) {
                $pdf->SetFont($this->font, '', $this->BIG_FONT-55);
            }
            $pdf->Cell($effective_width, 20, $price, 0, 1, 'C');
        } else {
            // Deal is BOGO
            $pdf->SetTextColor(244, 116, 30);
            $pdf->SetXY($this->left + ($this->width*$column) + 16, $this->top + ($row*$this->height) + 27 - 2);
            $pdf->SetFont($this->font, 'B', $this->BOGO_MED_FONT);
            $pdf->Cell($effective_width, 6, "Buy One, Get One", 0, 'C');

            $pdf->SetXY($this->left + ($this->width*$column) + 1, $this->top + ($row*$this->height) + 45 - 2);
            $pdf->SetFont($this->font, 'B', $this->BOGO_BIG_FONT);
            $pdf->Cell($effective_width, 6, 'FREE', 0, 1, 'C');

            $pdf->SetTextColor(0, 0, 0);

            // BOGO limit
            if ($item['transLimit'] > 0) {
                $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 13));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                //$pdf->SetXY($this->left, $this->top + ($this->height*$row) + ($this->height - $this->top - 13));
                $pdf->Cell($effective_width, 6, 'Limit ' . $item['transLimit'] / 2 . ' per customer', 0, 1, 'C');
            }

            // BOGO regular price
            $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 20 + 06));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $text = sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
            $pdf->Cell($effective_width, 20, $text, 0, 1, 'L');
        }

        if ($this->validDate($item['startDate']) && $this->validDate($item['endDate'])) {
            // intl would be nice
            $datestr = $this->getDateString($item['startDate'], $item['endDate']);
            $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 26 + 5.2));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
        }

        if (isset($item['nonSalePrice']) && $item['nonSalePrice'] > $item['normal_price']) {
            $pdf->SetXY($this->left + ($this->width*$column) + 15, $this->top + ($this->height*$row) + ($this->height - $this->top - 26 + 5.2));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $text = sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
            $pdf->Cell($effective_width, 20, $text, 0, 1, 'L');
        }

        if ($item['originShortName'] != '') {
            $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 26));
            $pdf->SetFont($this->alt_font, '', $this->MED_FONT);
            $lower = trim(strtolower($item['originShortName']));
            if (substr($lower, 0, 10) !== 'product of') {
                $item['originShortName'] = 'Product of ' . trim($item['originShortName']);
            }
            $pdf->Cell($effective_width, 20, $item['originShortName'], 0, 1, 'C');
        }

        return $pdf;
    }

    public function drawPDF()
    {
        $pdf = $this->createPDF();

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        foreach ($data as $item) {
            if ($count % 1 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            //$row = floor($sign / 2);
            $row = 0;
            //$column = $sign % 2;
            $column = 0;

            $pdf = $this->drawItem($pdf, $item, $row, $column);

            $count++;
            $sign++;
        }

        $pdf->Output('Giganto4UpL.pdf', 'I');
    }
}

}

