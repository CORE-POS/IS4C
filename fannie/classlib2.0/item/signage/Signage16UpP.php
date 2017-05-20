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

class Signage16UpP extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 30;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;
    protected $SMALLER_FONT = 8;
    protected $SMALLEST_FONT = 5;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 53.975;
    protected $height = 69.35;
    protected $top = 15;
    protected $left = 5.175;

    protected function createPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(0, 3.175, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);

        return $pdf;
    }

    protected function drawItem($pdf, $item, $row, $column)
    {
        $effective_width = $this->width - (2*$this->left);

        $price = $this->printablePrice($item);

        $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($row*$this->height)+6);
        $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
        $pdf = $this->fitText($pdf, $this->SMALL_FONT, 
            strtoupper($item['brand']), array($column, 6, 1));

        $pdf->SetFont($this->font, '', $this->MED_FONT);
        $pdf = $this->fitText($pdf, $this->MED_FONT, 
            $item['description'], array($column, 6, 2));

        $pdf->SetX($this->left + ($this->width*$column));
        $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
        $item['size'] = $this->formatSize($item['size'], $item);
        $pdf->Cell($effective_width, 6, $item['size'], 0, 1, 'C');

        $pdf->Ln(4);
        $pdf->SetFont($this->font, '', $this->BIG_FONT);
        $font_shrink = 0;
        while (true) {
            $pdf->SetX($this->left + ($this->width*$column));
            $y = $pdf->GetY();
            $pdf->MultiCell($effective_width, 8, $price, 0, 'C');
            /* If the current vertical position of the cursor indicates
             * that the price caused a line break
             * - "erase" the price by writing a white rectangle over it
             * - reduce the font size
             * - try again
             */
            if ($pdf->GetY() - $y > 8) {
                $pdf->SetFillColor(0xff, 0xff, 0xff);
                $pdf->Rect($this->left + ($this->width*$column), ($y-2), 
                           $this->left + ($this->width*$column) + $effective_width, 
                           $pdf->GetY(), 'F');
                $font_shrink++;
                if ($font_shrink >= $this->BIG_FONT) {
                    break;
                }
                $pdf->SetFontSize($this->BIG_FONT - $font_shrink);
                $pdf->SetXY($this->left + ($this->width*$column), $y);
            } else {
                break;
            }
        }

        if ($this->validDate($item['startDate']) && $this->validDate($item['endDate'])) {
            // intl would be nice
            $datestr = $this->getDateString($item['startDate'], $item['endDate']);
            $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 10));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $pdf->Cell($effective_width, 6, strtoupper($datestr), 0, 1, 'R');
        }

        if ($item['originShortName'] != '' || (isset($item['nonSalePrice']) && $item['nonSalePrice'] > $item['normal_price'])) {
            $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 10));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $text = ($item['originShortName'] != '') ? $item['originShortName'] : sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
            $pdf->Cell($effective_width, 6, $text, 0, 1, 'L');
        }

        return $pdf;
    }

    public function drawPDF()
    {
        $pdf = $this->createPDF();

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $this->height = 69.35;
        $this->top = 15;
        $this->left = 5.175;
        $effective_width = $this->width - (2*$this->left);
        foreach ($data as $item) {
            $item = $this->decodeItem($item);
            if ($count % 16 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = floor($sign / 4);
            $column = $sign % 4;

            $pdf = $this->drawItem($pdf, $item, $row, $column);

            $count++;
            $sign++;
        }

        $pdf->Output('Signage16UpP.pdf', 'I');
    }
}

}

