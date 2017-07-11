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

class TagsNoPrice extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 18;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 52; // tag width in mm
    protected $height = 31; // tag height in mm
    protected $left = 5.5; // left margin
    protected $top = 15; // top margin

    public function drawPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');

        $pdf->SetTopMargin($this->top);  //Set top margin of the page
        $pdf->SetLeftMargin($this->left);  //Set left margin of the page
        $pdf->SetRightMargin($this->left);  //Set the right margin of the page
        $pdf->SetAutoPageBreak(False); // manage page breaks yourself

        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $mapP = $dbc->prepare("
            SELECT i.units,i.size,s.sku
            FROM VendorAliases AS s
                INNER JOIN vendorItems AS i ON s.sku=i.sku AND s.vendorID=i.vendorID
                INNER JOIN vendors AS v ON s.vendorID=v.vendorID
            WHERE s.upc=?
                AND (v.vendorName=? OR 1=1)");

        $data = $this->loadItems();
        $num = 0; // count tags 
        $x = $this->left;
        $y = $this->top;
        foreach ($data as $item) {

            // extract & format data
            $price = $item['normal_price'];
            $desc = isset($item['posDescription']) && !empty($item['posDescription']) ? $item['posDescription'] : $item['description'];
            $brand = strtoupper(substr($item['brand'],0,13));
            $pak = $item['units'];
            $size = $item['units'] . "-" . $item['size'];
            $sku = $item['sku'];
            $ppu = $item['pricePerUnit'];
            $vendor = substr($item['vendor'],0,7);
            $upc = $item['upc'];

            if ($num % 32 == 0) {
                $pdf->AddPage();
                $x = $this->left;
                $y = $this->top;
            } else if ($num % 4 == 0) {
                $x = $this->left;
                $y += $this->height;
            }

            $mapped = $dbc->getRow($mapP, array($upc, $item['vendor']));
            if ($mapped) {
                $sku = $mapped['sku'];
                $size = $mapped['units'] . '-' . $mapped['size'];
            }

            $args = array(
                'height' => 5,
                'valign' => 'T',
                'align' => 'L',
                'suffix' => date('  n/j/y'),
                'fontsize' => 8,
                'font' => $this->font,
            );
            $pdf = $this->drawBarcode($upc, $pdf, $x + 7, $y + 4, $args);

            $pdf->SetFont($this->font, '', 8);

            $pdf->SetXY($x,$y+9);
            $pdf->MultiCell($this->width, 4, substr($desc, 0, 25), 0, 'L');

            $pdf->SetX($x);
            $pdf->Cell($this->width,4,$vendor . ' - ' . $brand,0,1,'L');

            $pdf->SetX($x);
            $pdf->Cell($this->width,4,$size,0,0,'L');
            if ($sku != $upc && trim($sku) != '') {
                $pdf->SetX($x);
                $pdf->Cell($this->width-10,4,$sku,0,0,'R');
                if (class_exists('\\Image_Barcode2')) {
                    $img = \Image_Barcode2::draw($sku, 'code128', 'png', false, 5, 1, false);
                    $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
                    imagepng($img, $file);
                    $pdf->Image($file, $x, $y+21);
                    unlink($file);
                }
            }

            // move right by tag width
            $x += $this->width;

            $num++;
        }

        $pdf->Output('TagsNoPrice.pdf', 'I');
    }
}

}

