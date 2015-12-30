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

class HalfTags4x8P extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 18;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 52; // tag width in mm
    protected $height = 31; // tag height in mm
    protected $left = 6; // left margin
    protected $top = 16; // top margin

    public function drawPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf = $this->loadPluginFonts($pdf);

        $pdf->SetMargins($this->left, $this->top, $this->left);
        $pdf->SetAutoPageBreak(false); // manage page breaks yourself

        $data = $this->loadItems();
        $num = 0; // count tags 
        $posX = $this->left;
        $posY = $this->top;
        foreach ($data as $item) {

            // extract & format data
            $price = $item['normal_price'];
            $desc = strtoupper(substr($item['posDescription'],0,25));
            $brand = ucwords(strtolower(substr($item['brand'],0,13)));
            $pak = $item['units'];
            $size = $item['units'] . "-" . $item['size'];
            $sku = $item['sku'];
            $ppu = $item['pricePerUnit'];
            $vendor = substr($item['vendor'],0,7);
            $upc = $item['upc'];

            if ($num % 32 == 0) {
                $pdf->AddPage();
                $posX = $this->left;
                $posY = $this->top;
            } elseif ($num % 4 == 0) {
                $posX = $this->left;
                $posY += $this->height;
            }

            $pdf->SetFont($this->font, '', 8);
            $pdf->SetXY($posX,$posY);
            // try normal wordwrap
            // but squeeze into two lines if needed
            $wrapped = wordwrap($desc, 12, "\n", true);
            if (count(explode("\n", $wrapped)) > 2) {
                $wrapped = substr($desc, 0, 12);
                if ($wrapped[11] != ' ') {
                    $wrapped .= '-';
                }
                $wrapped .= "\n";
                $wrapped .= trim(substr($desc, 12));
            }
            $pdf->MultiCell($this->width/2, 3, $wrapped, 0, 'L');

            $pdf->SetX($posX);
            $pdf->Cell($this->width/2,3,date('n/j/y ') . $size,0,1,'L');

            $pdf->SetFont($this->font,'B',18); //change font for price
            $pdf->SetX($posX);
            $pdf->Cell($this->width/2,8,$price,0,1,'L');

            $args = array(
                'height' => 7,
                'align' => 'L',
                'fontsize' => 8,
                'width' => 0.23,
                'font' => $this->font,
            );
            $b_y = $pdf->GetY();
            $pdf = $this->drawBarcode($upc, $pdf, $posX, $b_y, $args);

            // move right by tag width
            $posX += $this->width;

            $num++;
        }

        $pdf->Output('Tags4x8P.pdf', 'I');
    }
}

}

