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

class Signage12UpL extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 40;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;
    protected $SMALLER_FONT = 8;
    protected $SMALLEST_FONT = 6;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 68.67;
    protected $height = 70.5;
    protected $top = 17;
    protected $left = 8.5;

    public function drawPDF()
    {
        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(3.175, 3.175, 3.175);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $this->top = 17;
        $effective_width = $this->width - $this->left;
        foreach ($data as $item) {
            $item = $this->decodeItem($item);
            if ($count % 12 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = floor($sign / 4);
            $column = $sign % 4;

            $price = $this->printablePrice($item);

            $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($row*$this->height) + 6);
            $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
            $font_shrink = 0;
            while (true) {
                $pdf->SetX($this->left + ($this->width*$column));
                $y = $pdf->GetY();
                $pdf->MultiCell($effective_width, 6, strtoupper($item['brand']), 0, 'C');
                if ($pdf->GetY() - $y > 6) {
                    $pdf->SetFillColor(0xff, 0xff, 0xff);
                    $pdf->Rect($this->left + ($this->width*$column), $y, $this->left + ($this->width*$column) + $effective_width, $pdf->GetY(), 'F');
                    $font_shrink++;
                    if ($font_shrink >= $this->SMALL_FONT) {
                        break;
                    }
                    $pdf->SetFontSize($this->SMALL_FONT - $font_shrink);
                    $pdf->SetXY($this->left + ($this->width*$column), $y);
                } else {
                    break;
                }
            }

            /**
              This block attempts to write the description then
              checks how many lines it took. If the description was
              longer than two lines, it whites the whole thing out,
              drops one font size, and tries again. Calculating
              effective text size with smart line breaks seems
              really tough.
            */
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $font_shrink = 0;
            while (true) {
                $pdf->SetX($this->left + ($this->width*$column));
                $y = $pdf->GetY();
                $pdf->MultiCell($effective_width, 6, $item['description'], 0, 'C');
                if ($pdf->GetY() - $y > 12) {
                    $pdf->SetFillColor(0xff, 0xff, 0xff);
                    $pdf->Rect($this->left + ($this->width*$column), $y, $this->left + ($this->width*$column) + $effective_width, $pdf->GetY(), 'F');
                    $font_shrink++;
                    if ($font_shrink >= $this->MED_FONT) {
                        break;
                    }
                    $pdf->SetFontSize($this->MED_FONT - $font_shrink);
                    $pdf->SetXY($this->left + ($this->width*$column), $y);
                } else {
                    if ($pdf->GetY() - $y < 12) {
                        $words = explode(' ', $item['description']);
                        $multi = '';
                        for ($i=0;$i<floor(count($words)/2);$i++) {
                            $multi .= $words[$i] . ' ';
                        }
                        $multi = trim($multi) . "\n";
                        for ($i=floor(count($words)/2); $i<count($words); $i++) {
                            $multi .= $words[$i] . ' ';
                        }
                        $item['description'] = trim($multi);
                        $pdf->SetFillColor(0xff, 0xff, 0xff);
                        $pdf->Rect($this->left + ($this->width*$column), $y, $this->left + ($this->width*$column) + $effective_width, $pdf->GetY(), 'F');
                        $pdf->SetXY($this->left + ($this->width*$column), $y);
                        $pdf->MultiCell($effective_width, 6, $item['description'], 0, 'C');
                    }
                    break;
                }
            }

            $pdf->SetX($this->left + ($this->width*$column));
            $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
            $item['size'] = $this->formatSize($item['size'], $item);
            $pdf->Cell($effective_width, 6, $item['size'], 0, 1, 'C');

            if ($item['signMultiplier'] != -3) {
                $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - 39));
                $pdf->SetFont($this->font, '', $this->BIG_FONT);
                $pdf->Cell($effective_width, 12, $price, 0, 1, 'C');
            } else {
                $pdf->SetXY(-5 + $this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - 39));
                $pdf->SetFont($this->font, '', $this->MED_FONT);
                $pdf->MultiCell($effective_width/2, 6, "BUY ONE\nGET ONE", 0, 'R');
                $pdf->SetXY(-5 + $this->left + ($this->width*$column) + ($effective_width/2), $this->top + ($this->height*$row) + ($this->height - 39));
                $pdf->SetFont($this->font, '', $this->BIG_FONT);
                $pdf->Cell($effective_width/2, 12, 'FREE', 0, 1, 'L');
            }

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                // intl would be nice
                $datestr = $this->getDateString($item['startDate'], $item['endDate']);
                $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - 33));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width, 20, strtoupper($datestr), 0, 1, 'R');
            }

            if ($item['originShortName'] != '' || isset($item['nonSalePrice'])) {
                $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - 33));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $text = ($item['originShortName'] != '') ? $item['originShortName'] : sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
                $pdf->Cell($effective_width, 20, $text, 0, 1, 'L');
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Signage12UpL.pdf', 'I');
    }
}

}

