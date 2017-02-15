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

class GravityBin extends \COREPOS\Fannie\API\item\FannieSignage 
{

    protected $BIG_FONT = 23;
    protected $MED_FONT = 15;
    protected $SMALL_FONT = 10;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 95.25;
    protected $height = 101.6;

    public function drawPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(3.175, 3.175, 3.175);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        foreach ($data as $item) {
            if ($count % 4 === 0) {
                $pdf->AddPage();
                $sign = 0;
            }
            $row = floor($sign / 2);
            $column = $sign % 2;

            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Rect(7 + ($this->width*$column), 5+($row*$this->height), $this->width, $this->height);

            $pdf->SetXY(10 + ($this->width*$column), 5 + ($row*$this->height));
            $pdf->SetFont($this->font, 'B', $this->BIG_FONT);
            $pdf->MultiCell($this->width, 8, $item['description'], 0, 'C');
            $pdf->Ln(1);
            
            $pdf->SetX(10 + ($this->width*$column));
            $pdf->SetFillColor(0, 0x99, 0x66);
            $pdf->SetTextColor(0xff, 0xff, 0xff);
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $pdf->Cell($this->width-5, 6, 'ORGANIC', 0, 1, 'C', true);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont($this->font, '', $this->SMALL_FONT);
            $info = $this->getExtraInfo($item['upc']);
            $pdf->SetX(10 + ($this->width*$column));
            $pdf->MultiCell($this->width-5, 4, str_replace('<br />', "\n", $info['long_text']), 0, 'L');

            $pdf->SetX(10 + ($this->width*$column));
            $pdf->SetFont($this->font, 'B', $this->MED_FONT);
            $pdf->Cell($this->width-5, 9, $item['originName'], 0, 1, 'R');

            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $pdf->SetXY(10 + ($this->width*$column), 5 + ($row*$this->height) + $this->height - 10 - 1);
            $pdf->Cell($this->width-5, 7, '', 1); // blank box
            $pdf->SetXY(15 + ($this->width*$column), 5 + ($row*$this->height) + $this->height - 10);
            $pdf->Cell(20, 5, 'PLU#', 0, 0, 'L');
            $pdf->SetFont($this->font, 'B', $this->MED_FONT);
            $pdf->Cell(30, 5, ltrim($item['upc'], '0'), 0, 0, 'L');
            $pdf->SetFont($this->font, '', $this->MED_FONT);
            $pdf->Cell(20, 5, sprintf('$%.2f/lb.', $item['normal_price']), 0, 0, 'L');

            $file = dirname(__FILE__) . '/../../../item/images/nutrition-facts/' . $info['nutritionFacts'];
            if (file_exists($file) && is_file($file)) {
                $pdf->Image($file, 10+($this->width*$column), 11.5 + ($row*$this->height) + 52, 88.9);
            }

            $count++;
            $sign++;
        }

        $pdf->Output('GravityBin.pdf', 'I');
    }

    protected function getExtraInfo($upc)
    {
        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $prep = $dbc->prepare('SELECT * FROM productUser WHERE upc=?');
        return $dbc->getRow($prep, array($upc));
    }
}

