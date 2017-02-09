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

class ItemList4UpL extends \COREPOS\Fannie\API\item\FannieSignage 
{

    protected $BIG_FONT = 85;
    protected $MED_FONT = 12;
    protected $SMALL_FONT = 20;
    protected $SMALLER_FONT = 13;
    protected $SMALLEST_FONT = 8;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 139;
    protected $height = 108;
    protected $top = 30;
    protected $left = 16;

    public function drawPDF()
    {
        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(3.175, 3.175, 3.175);
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
        $itemsPerSign = 9;
        $effective_width = $this->width - (2*$this->left);
        foreach ($data as $item) {
            $effective_sign = floor($sign / $itemsPerSign);
            $row = floor($effective_sign / 2);
            $column = $effective_sign % 2;
            $item['description'] = str_replace("\n", '', trim($item['description']));
            $item['description'] = str_replace("\r", '', $item['description']);

            $price = $this->printablePrice($item);
            $item['size'] = $this->formatSize($item['size'], $item);

            if ($sign % $itemsPerSign == 0) {
                $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($row*$this->height));
                $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
                $pdf->Cell($effective_width, 10, strtoupper($item['brand']), 0, 1, 'C');
                if ($item['startDate'] != '' && $item['endDate'] != '') {
                    // intl would be nice
                    $datestr = $this->getDateString($item['startDate'], $item['endDate']);
                    $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height*$row) + ($this->height - $this->top - 20));
                    $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                    $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
                }
            }
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $pdf->SetXY($this->left + ($this->width*$column) + ($column*5), (($sign % $itemsPerSign)*6)+$this->top + 10 + ($this->height*$row));
            $pdf->Cell($effective_width, 6, $item['description'] . ' ' . $item['size'], 0, 0, 'L');
            $pdf->SetXY($this->left + ($this->width*$column) + ($column*5), (($sign % $itemsPerSign)*6)+$this->top + 10 + ($this->height*$row));
            $pdf->Cell($effective_width, 6, $this->printablePrice($item), 0, 0, 'R');

            $sign++;
        }

        $pdf->Output('Signage4UpL.pdf', 'I');
    }
}

