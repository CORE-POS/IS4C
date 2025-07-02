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

class SingleCompact16UpL extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 30;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;
    protected $SMALLER_FONT = 8;
    protected $SMALLEST_FONT = 5;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 53;
    protected $height = 69.35;
    protected $top = 15;
    //protected $left = 15.175;
    // adjusting compact left adjusts width of all text
    protected $left = 5.175;

    protected function createPDF()
    {
        $pdf = new \FPDF('L', 'mm', array(105, 148));
        $pdf->SetMargins(0, 3.175, 0);
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

        $pdf->SetXY($this->left + 6 + ($this->width*$column), 14 );
        $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
        $pdf->Cell($this->width, 10, strtoupper($item['brand']), 0, 'L', 'C');
        //$pdf = $this->fitText($pdf, $this->SMALL_FONT, 
        //    strtoupper($item['brand']), array($column, 6, 1));

        $pdf->SetXY($this->left + 17 + ($this->width*$column), 20 );
        $pdf->SetFont($this->font, '', $this->MED_FONT);

        // use line break to split str if exists; else wordwrap if 2 lines req.
        $desc = $item['description'];
        $lines = array();
        if (strstr($desc, "\r\n")) {
            $lines = explode ("\r\n", $desc);
        } elseif (strlen($desc) > 20) {
            $wrp = wordwrap($desc, strlen($desc)/1.5, "*", false);
            $lines = explode('*', $wrp);
        } else {
            $lines[0] = $desc;
        }

        $strlen = strlen($lines[0]);
        if (isset($lines[1]) && strlen($lines[1]) > $strlen) {
            $strlen = strlen($lines[1]);
        }
        if ($strlen >= 30) {
            //$pdf->SetFont('Gill','', 7);
            $pdf->SetFont($this->font, '', $this->SMALLER_FONT);
        } elseif ($strlen > 26 && $strlen < 36) {
            //$pdf->SetFont('Gill','', 10);
            $pdf->SetFont($this->font, '', $this->SMALL_FONT);
        } else {
            //$pdf->SetFont('Gill','', 14);
            $pdf->SetFont($this->font, '', $this->MED_FONT);
        }
        $pdf->SetFillColor(0xff, 0xff, 0xff);
        //$x = $this->left + 6;
        $x = $this->left + 6 + ($this->width*$column);
        $y = 10;
        if (count($lines) > 1) {
            $pdf->SetXY($x,$y+13);
            $pdf->Cell($this->width, 5, $lines[0], 0, 1, 'C', true); 
            $pdf->SetXY($x, $y+19);
            $pdf->Cell($this->width, 5, $lines[1], 0, 1, 'C', true); 
        } else {
            $pdf->SetXY($x,$y+16.5);
            $pdf->Cell($this->width, 5, $lines[0], 0, 1, 'C', true); 
        }

        //$pdf->Cell($this->width, 10, $desc, 0, 'L', 2, 'C');
        //$pdf = $this->fitText($pdf, $this->MED_FONT, 
        //    $item['description'], array($column, 6, 2));

        $pdf->SetXY($this->left + 10 + ($this->width*$column) + 3,  $this->top + ($row*$this->height) + 19);
        $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
        $item['size'] = $this->formatSize($item['size'], $item);
        $pdf->Cell($effective_width, 6, $item['size'], 0, 1, 'C');

        $pdf->Ln(3);
        $pdf->SetFont($this->font, '', $this->BIG_FONT);
        $font_shrink = 0;
        while (true) {
            $pdf->SetX($this->left + 10 + ($this->width*$column));
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
                $pdf->Rect($this->left + 10 + ($this->width*$column), ($y-2), 
                           $this->left + ($this->width*$column) + $effective_width, 
                           $pdf->GetY(), 'F');
                $font_shrink++;
                if ($font_shrink >= $this->BIG_FONT) {
                    break;
                }
                $pdf->SetFontSize($this->BIG_FONT - $font_shrink);
                $pdf->SetXY($this->left + 10 + ($this->width*$column), $y);
            } else {
                break;
            }
        }

        // If BOGO, clear previous formatting with white rectangle, then print new NCG BOGO format
        if (isset($item['signMultiplier'])) {
            if ($item['signMultiplier'] == -3) {

                $pdf->SetFillColor(0xff, 0xff, 0xff);
                $pdf->Rect($this->left + 10 + ($this->width*$column), ($y-2), 
                           $this->left + 10 + ($this->width*$column) + $effective_width, 
                           $pdf->GetY(), 'F');

                $pdf->SetTextColor(244, 116, 30);

                $pdf->SetXY($this->left + ($this->width*$column) - 3, $this->top + ($this->height*$row) + ($this->height - 45) - 3);
                $pdf->SetFont($this->font, 'B', 12);
                $pdf->Cell($this->width, 12, 'Buy One, Get One', 0, 1, 'C');

                $pdf->SetXY($this->left + ($this->width*$column) - 3, $this->top + ($this->height*$row) + ($this->height - 36) - 3);
                $pdf->SetFont($this->font, 'B', $this->BIG_FONT);
                $pdf->Cell($this->width, 12, 'FREE', 0, 1, 'C');

                $pdf->SetTextColor(0, 0, 0);
            }
            // BOGO limit
            if ($item['transLimit'] > 0) {
                $pdf->SetFont($this->font, 'B', $this->SMALLEST_FONT);
                $pdf->SetXY($this->left + 10 + ($this->width*$column) - 3, $this->top + ($this->height*$row) + ($this->height - 31) + 1.5);
                $pdf->Cell($this->width, 12, 'Limit ' . $item['transLimit'] / 2 . ' per customer', 0, 1, 'C');
                $pdf->SetFont($this->font, '', $this->SMALLEST_FONT);
            }
        }

        if ($this->validDate($item['startDate']) && $this->validDate($item['endDate'])) {
            // intl would be nice
            $datestr = $this->getDateString($item['startDate'], $item['endDate']);
            $pdf->SetXY($this->left + 10 + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 15));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $pdf->Cell($effective_width, 6, strtoupper($datestr), 0, 1, 'R');
        }

        if ($item['originShortName'] != '' || (isset($item['nonSalePrice']) && $item['nonSalePrice'] > $item['normal_price'])) {
            $pdf->SetXY($this->left + 10 + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 15));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $text = ($item['originShortName'] != '') ? $item['originShortName'] : sprintf('Regular Price: $%.2f', $item['nonSalePrice']);
            $pdf->Cell($effective_width, 6, $text, 0, 1, 'L');
        }

        if (isset($item['locAbbr'])) {
            //$pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            //$pdf->SetXY($this->left + ($this->width*$column) + 14, $this->top + ($this->height*$row) + ($this->height - 33));
            //$pdf->Cell($effective_width, 20, " ".$item['locAbbr'], 0, 1, 'L');
            $pdf->SetXY($this->left + 10 + ($this->width*$column) + 20, $this->top + ($this->height*$row) + ($this->height - $this->top - 15));
            $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
            $pdf->Cell($effective_width, 6, $item['locAbbr'], 0, 1, 'L');
        }

        return $pdf;
    }

    public function drawPDF()
    {
        $pdf = $this->createPDF();
        $store = $this->store;

        $data = $this->loadItems();
        $data = $this->sortProductsByPhysicalLocation($this->getDB(), $data, $this->store);
        $count = 0;
        $sign = 0;
        $this->height = 69.35;
        $this->top = 15;
        $this->left = 5.175;
        $effective_width = $this->width - (2*$this->left);
        foreach ($data as $item) {
            $item = $this->decodeItem($item);
            if ($count % 2 == 0) {
                $pdf->AddPage("L", "A6");
                $sign = 0;
            }

            $row = floor($sign / 2);
            $column = $sign % 2;

            $pdf = $this->drawItem($pdf, $item, $row, $column);

            $count++;
            $sign++;
        }

        $pdf->Output('SingleCompact16UpL.pdf', 'I');
    }

}

}

