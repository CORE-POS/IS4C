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

class Signage4UpL extends \COREPOS\Fannie\API\item\FannieSignage 
{

    protected $BIG_FONT = 85;
    protected $MED_FONT = 24;
    protected $SMALL_FONT = 20;
    protected $SMALLER_FONT = 13;
    protected $SMALLEST_FONT = 8;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 139;
    protected $height = 108;
    protected $top = 30;
    protected $left = 16;

    protected function createPDF()
    {
        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(3.175, 3.175, 3.175);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);

        return $pdf;
    }

    protected function drawItem($pdf, $item, $row, $column)
    {
        $effective_width = $this->width - (2*$this->left);
        $price = $this->printablePrice($item);

        $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($row*$this->height));
        $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
        $pdf->Cell($effective_width, 10, strtoupper($item['brand']), 0, 1, 'C');
        $pdf->SetX($this->left + ($this->width*$column));
        $pdf->SetFont($this->font, '', $this->MED_FONT);
        $item['description'] = str_replace("\r", '', $item['description']);
        $pdf->Cell($effective_width, 10, str_replace("\n", '', $item['description']), 0, 1, 'C');

        $pdf->SetX($this->left + ($this->width*$column));
        $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
        $item['size'] = $this->formatSize($item['size'], $item);
        $pdf->Cell($effective_width, 6, $item['size'], 0, 1, 'C');

        $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($row*$this->height) + 35);
        $pdf->SetFont($this->font, '', $this->BIG_FONT);
        $pdf->Cell($effective_width, 20, $price, 0, 1, 'C');

        if ($this->validDate($item['startDate']) && $this->validDate($item['endDate'])) {
            // intl would be nice
            $datestr = $this->getDateString($item['startDate'], $item['endDate']);
            $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 20));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
        }

        if ($item['originShortName'] != '' || (isset($item['nonSalePrice']) && $item['nonSalePrice'] > $item['normal_price'])) {
            $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 20));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $text = ($item['originShortName'] != '') ? $item['originShortName'] : sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
            if ($item['originShortName'] != '' && $item['nonSalePrice'] != '') {
                $text .= sprintf('%sRegular Price: $%.2f', str_repeat(' ', 52-strlen($text)), $item['nonSalePrice']);
            }
            $pdf->Cell($effective_width, 20, $text, 0, 1, 'L');
        }

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
            if ($count % 4 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = floor($sign / 2);
            $column = $sign % 2;

            $pdf = $this->drawItem($pdf, $item, $row, $column);

            $count++;
            $sign++;
        }

        $pdf->Output('Signage4UpL.pdf', 'I');
    }
}

}

