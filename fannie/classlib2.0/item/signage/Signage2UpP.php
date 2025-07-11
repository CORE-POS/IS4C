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
    protected $BOGO_FONT = 28;
    protected $BOGO_FONT_B = 96;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 203.2;
    protected $height = 138.35;
    protected $top = 43;
    protected $left = 5;

    protected function createPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35);
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

        $pdf->SetXY($this->left, $this->top + ($row*$this->height));
        $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
        $pdf->Cell($this->width, 11, strtoupper($item['brand']), 0, 1, 'C');
        $pdf->SetX($this->left);
        $pdf->SetFont($this->font, '', $this->MED_FONT);
        $item['description'] = str_replace("\r", '', $item['description']);
        $item['description'] = str_replace("\n", '', $item['description']);
        $pdf->Cell($this->width, 10, $item['description'], 0, 1, 'C');
        $pdf->SetX($this->left);
        $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
        if ($item['super_name'] != 'PRODUCE') {
            $item['size'] = $this->formatSize($item['size'], $item);
        } else {
            $item['size'] = '';
        }
        $pdf->Cell($this->width, 10, $item['size'], 0, 1, 'C');
        $pdf->SetX($this->left);
        $pdf->SetFont($this->font, '', $this->BIG_FONT);
        if (strpos($price, 'FREE') != false) {
            // BOGO text
            $pdf->SetTextColor(244, 116, 30);
            $pdf->SetFont($this->font, 'B', $this->BOGO_FONT);
            $pdf->Cell($this->width, 20, 'Buy One, Get One', 0, 1, 'C');
            $pdf->SetX($this->left);
            $pdf->SetFont($this->font, 'B', $this->BOGO_FONT_B);
            $pdf->Cell($this->width, 20, 'FREE', 0, 1, 'C');

            $pdf->SetTextColor(0, 0, 0);

            // BOBO regular price text
            $pdf->SetXY($this->left, $this->top + ($this->height*$row) + ($this->height - $this->top - 20));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $text = sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
            $pdf->Cell($effective_width, 20, $text, 0, 1, 'L');

            // BOGO limit
            if ($item['transLimit'] > 0) {
                $pdf->SetXY($this->left, $this->top + ($this->height*$row) + ($this->height - $this->top - 20));
                $pdf->Cell($this->width, 20, 'Limit ' . $item['transLimit'] / 2 . ' per customer', 0, 1, 'C');
            }

        } elseif (strpos($price, "lb") != false) {
            $pdf->SetFont($this->font, '', $this->BIG_FONT-44);
            $price = str_replace(' /lb.', '/lb', $price);
            $pdf->Cell($this->width, 40, $price, 0, 1, 'C');
        } elseif (strstr($price, 'SAVE')) {
            $pdf->SetFont($this->font, '', $this->BIG_FONT-58);
            $pdf->Cell($this->width, 40, $price, 0, 1, 'C');
        } else {
            $pdf->Cell($this->width, 40, $price, 0, 1, 'C');
        }

        //$pdf->SetFont($this->font, '', $this->SMALL_FONT);
        //$pdf->Cell($this->width, 40, $item['department'], 0, 1, 'C');

        if ($item['startDate'] != '' && $item['endDate'] != '') {
            $datestr = $this->getDateString($item['startDate'], $item['endDate']);
            $pdf->SetXY($this->left, $this->top + ($this->height*$row) + ($this->height - $this->top - 20));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
        }
        if (isset($item['nonSalePrice']) && $item['nonSalePrice'] > $item['normal_price']) {
            $pdf->SetXY($this->left, $this->top + ($this->height*$row) + ($this->height - $this->top - 20));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $text = sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
            $pdf->Cell($effective_width, 20, $text, 0, 1, 'L');
        }
        if ($item['originShortName'] != '') {
            $pdf->SetXY($this->left, $this->top + ($this->height*$row) + ($this->height - $this->top - 20));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $lower = trim(strtolower($item['originShortName']));
            if (substr($lower, 0, 10) !== 'product of') {
                $item['originShortName'] = 'Product of ' . trim($item['originShortName']);
            }
            $pdf->Cell($effective_width, 20, $item['originShortName'], 0, 1, 'C');
        }

        /*
            Create Guide-Lines
        */
        $pdf->SetFillColor(155, 155, 155);

        // horizontal
        $pdf->SetXY(0, $this->height-2 );
        $pdf->Cell(10, 0.3, ' ', 0, 1, 'C', true);

        $pdf->SetXY($this->width - 1, $this->height-2 );
        $pdf->Cell(5, 0.3, ' ', 0, 1, 'C', true);

        $pdf->SetFillColor(0, 0, 0);

        return $pdf;
    }

    public function drawPDF()
    {
        $pdf = $this->createPDF();

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
            $pdf = $this->drawItem($pdf, $item, $row, 1);

            $count++;
            $sign++;
        }

        $pdf->Output('Signage2UpL.pdf', 'I');
    }
}

}


