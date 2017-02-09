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

class ItemList2UpP extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 96;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 24;
    protected $SMALLER_FONT = 18;
    protected $SMALLEST_FONT = 12;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 203.2;
    protected $height = 138.35;
    protected $top = 43;
    protected $left = 5;

    public function drawPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(6.35, 6.35, 6.35);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);
        $pdf->AddPage();

        $data = $this->loadItems();
        usort($data, function($a, $b) {
            if ($a['description'] < $b['description']) {
                return -1;
            } elseif ($a['description'] > $b['description']) {
                return 1;
            }
            return 0;
        });
        $count = 0;
        $sign = 0;
        $row = 0;
        $effective_width = $this->width - (2*$this->left);
        $col_width = ($effective_width / 2.0);
        $columns = 2;
        $itemsPerCol = 12;
        $first = true;
        $column = 0;
        foreach ($data as $item) {
            $item['description'] = str_replace("\n", '', trim($item['description']));
            $item['description'] = str_replace("\r", '', $item['description']);
            if ($first) {
                $pdf->SetXY($this->left, $this->top + ($row*$this->height));
                $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
                $pdf->Cell($this->width, 11, strtoupper($item['brand']), 0, 1, 'C');
                if ($item['startDate'] != '' && $item['endDate'] != '') {
                    $datestr = $this->getDateString($item['startDate'], $item['endDate']);
                    $pdf->SetXY($this->left, $this->top + ($this->height*$row) + ($this->height - $this->top - 20));
                    $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                    $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
                }
                $first = false;
            }
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            /*
              X = margin plus column width if 2nd column
              Y = (current entry in column * line height) + top margin + 10 for space between columns
                  + row alignment
            */
            $pdf->SetXY($this->left + ($col_width*$column) + ($column*10), (($sign % $itemsPerCol)*6)+$this->top + 10 + ($this->height*$row));
            $item['size'] = $this->formatSize($item['size'], $item);
            $pdf->Cell($col_width, 6, $item['description'] . ' ' . $item['size'], 0, 0, 'L');
            $pdf->SetXY($this->left + ($col_width*$column) + ($column*10), (($sign % $itemsPerCol)*6)+$this->top + 10 + ($this->height*$row) + 0.5);
            $pdf->Cell($col_width, 6, $this->printablePrice($item), 0, 0, 'R');
            $sign++;
            if ($sign % $itemsPerCol == 0) {
                $column++;
            }
            if ($column >= $columns) {
                $column = 0;
                $sign = 0;
                $first = true;
                $row++;
            }
        }

        $pdf->Output('Signage2UpL.pdf', 'I');
    }
}

