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

class WfcProduceSingle extends \COREPOS\Fannie\API\item\FannieSignage 
{

    protected $BIG_FONT = 64;
    protected $MED_FONT = 24;
    protected $SMALL_FONT = 14;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 136.52;
    protected $height = 105;
    protected $top = 90;
    protected $left = 15;

    public function drawPDF()
    {
        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(3.175, 3.175, 3.175);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);

        $data = $this->loadItems();
        $effective_width = $this->width - (2*$this->left);
        foreach ($data as $item) {
            $pdf->AddPage();

            $column = 1; // right aligned

            $price = $this->printablePrice($item);

            $pdf->SetXY($this->left + ($this->width*$column), $this->top);
            $pdf->SetFontSize($this->SMALL_FONT);
            $pdf->Cell($effective_width, 10, $item['brand'], 0, 1, 'C');
            $pdf->SetX($this->left + ($this->width*$column));
            $pdf->SetFontSize($this->MED_FONT);
            $pdf->MultiCell($effective_width, 12, $item['description'], 0, 'C');
            $pdf->SetX($this->left + ($this->width*$column));
            $pdf->SetFontSize($this->BIG_FONT);
            $pdf->Cell($effective_width, 25, $price, 0, 1, 'C');
            $y_pos = $pdf->GetY();

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                // intl would be nice
                $datestr = $this->getDateString($item['startDate'], $item['endDate']);
                $pdf->SetXY($this->left + ($this->width*$column), $this->top + ($this->height - 40));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->Cell($effective_width, 20, $datestr, 0, 1, 'R');
            }

            if ($item['originName'] != '') {
                $pdf->SetXY($this->left + ($this->width*$column), $y_pos);
                $pdf->SetFontSize($this->SMALL_FONT);
                if (strlen($item['originName']) < 50) {
                    $pdf->Cell($effective_width, 20, $item['originName'], 0, 1, 'L');
                } else {
                    $pdf->Cell($effective_width, 20, $item['originShortName'], 0, 1, 'L');
                }
            }
        }

        $pdf->Output('WfcProdSingle.pdf', 'I');
    }
}

}

