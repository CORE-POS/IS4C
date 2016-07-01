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
    protected $BIG_FONT = 12;
    protected $MED_FONT = 9;
    protected $SMALL_FONT = 7;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 52; // tag width in mm
    protected $height = 31; // tag height in mm
    protected $left = 5.5; // left margin
    protected $top = 15; // top margin

    public function drawPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf = $this->loadPluginFonts($pdf);

        $bar_width = 50;
        $pdf->SetTopMargin($this->top);  //Set top margin of the page
        $pdf->SetLeftMargin($this->left);  //Set left margin of the page
        $pdf->SetRightMargin($this->left);  //Set the right margin of the page
        $pdf->SetAutoPageBreak(False); // manage page breaks yourself

        $data = $this->loadItems();
        $num = 0; // count tags 
        $x = $this->left;
        $y = $this->top;
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
                $x = $this->left;
                $y = $this->top;
                $sign = 0;
            } else if ($num % 4 == 0) {
                $x = $this->left;
                $y += $this->height;
            }

            $row = floor($sign / 4);
            $column = $sign % 4;

            $pdf->SetFillColor(86, 90, 92);
            $pdf->Rect($this->left + ($this->width*$column), $this->top + ($row*$this->height), $bar_width, 5, 'F');
            $pdf->Rect($this->left + ($this->width*$column), $this->top + ($row*$this->height) + 25, $bar_width, 2, 'F');

            $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($row*$this->height)+6);
            $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
            $pdf->MultiCell($this->width, 5, $brand, 0, 'C');

            $pdf->SetX($this->left + ($this->width*$column));
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $pdf->MultiCell($this->width, 5, $item['description'], 0, 'C');

            $pdf->SetX($this->left + ($this->width*$column));
            $pdf->SetFont($this->font, '', $this->BIG_FONT);
            $pdf->Cell($this->width, 8, $price, 0, 1, 'C');

            // move right by tag width
            $x += $this->width;

            $num++;
            $sign++;
        }

        $pdf->Output('Tags4x8P.pdf', 'I');
    }
}

}

