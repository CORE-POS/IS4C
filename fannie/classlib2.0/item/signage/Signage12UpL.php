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

    public function drawPDF()
    {
        $pdf = new \FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(3.175, 3.175, 3.175);
        $pdf->SetAutoPageBreak(false);
        if (\COREPOS\Fannie\API\FanniePlugin::isEnabled('CoopDealsSigns')) {
            $this->font = 'Gill';
            $this->alt_font = 'GillBook';
            define('FPDF_FONTPATH', dirname(__FILE__) . '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
            $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
            $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
            $pdf->AddFont('GillBook', '', 'GillSansMTPro-Book.php');
        }
        $pdf->SetFont($this->font, '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $width = 68.67;
        $height = 70.5;
        $top = 17;
        $left = 8.5;
        $effective_width = $width - $left;
        foreach ($data as $item) {
            if ($count % 12 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = floor($sign / 4);
            $column = $sign % 4;

            $price = $item['normal_price'];
            if ($item['scale']) {
                if (substr($price, 0, 1) != '$') {
                    $price = sprintf('$%.2f', $price);
                }
                $price .= ' /lb.';
            } elseif (isset($item['signMultiplier'])) {
                $price = $this->formatPrice($item['normal_price'], $item['signMultiplier']);
            } else {
                $price = $this->formatPrice($item['normal_price']);
            }

            $pdf->SetXY($left + ($width*$column), $top + ($row*$height) + 6);
            $pdf->SetFont($this->font, 'B', $this->SMALL_FONT);
            $font_shrink = 0;
            while (true) {
                $pdf->SetX($left + ($width*$column));
                $y = $pdf->GetY();
                $pdf->MultiCell($effective_width, 6, strtoupper($item['brand']), 0, 'C');
                if ($pdf->GetY() - $y > 6) {
                    $pdf->SetFillColor(0xff, 0xff, 0xff);
                    $pdf->Rect($left + ($width*$column), $y, $left + ($width*$column) + $effective_width, $pdf->GetY(), 'F');
                    $font_shrink++;
                    if ($font_shrink >= $this->SMALL_FONT) {
                        break;
                    }
                    $pdf->SetFontSize($this->SMALL_FONT - $font_shrink);
                    $pdf->SetXY($left + ($width*$column), $y);
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
                $pdf->SetX($left + ($width*$column));
                $y = $pdf->GetY();
                $pdf->MultiCell($effective_width, 6, $item['description'], 0, 'C');
                if ($pdf->GetY() - $y > 12) {
                    $pdf->SetFillColor(0xff, 0xff, 0xff);
                    $pdf->Rect($left + ($width*$column), $y, $left + ($width*$column) + $effective_width, $pdf->GetY(), 'F');
                    $font_shrink++;
                    if ($font_shrink >= $this->MED_FONT) {
                        break;
                    }
                    $pdf->SetFontSize($this->MED_FONT - $font_shrink);
                    $pdf->SetXY($left + ($width*$column), $y);
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
                        $pdf->Rect($left + ($width*$column), $y, $left + ($width*$column) + $effective_width, $pdf->GetY(), 'F');
                        $pdf->SetXY($left + ($width*$column), $y);
                        $pdf->MultiCell($effective_width, 6, $item['description'], 0, 'C');
                    }
                    break;
                }
            }

            $pdf->SetX($left + ($width*$column));
            $pdf->SetFont($this->alt_font, '', $this->SMALLER_FONT);
            $item['size'] = trim(strtolower($item['size']));
            if ($item['size'] == '0' || $item['size'] == '00' || $item['size'] == '') {
                $item['size'] = '';
            } elseif (substr($item['size'], -1) != '.') {
                $item['size'] .= '.'; // end abbreviation w/ period
                $item['size'] = str_replace('fz.', 'fl oz.', $item['size']);
            }
            if (substr($item['size'], 0, 1) == '.') {
                $item['size'] = '0' . $item['size']; // add leading zero on decimal qty
            }
            if (strlen(ltrim($item['upc'], '0')) < 5 && $item['scale']) {
                $item['size'] = 'PLU# ' . ltrim($item['upc'], '0'); // show PLU #s on by-weight
            }
            $pdf->Cell($effective_width, 6, $item['size'], 0, 1, 'C');

            $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - 39));
            $pdf->SetFont($this->font, '', $this->BIG_FONT);
            $pdf->Cell($effective_width, 12, $price, 0, 1, 'C');

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                // intl would be nice
                if ($item['startDate'] == 'While supplies last') {
                    $datestr = $item['startDate'];
                } else {
                    $datestr = date('M d', strtotime($item['startDate']))
                        . chr(0x96) // en dash in cp1252
                        . date('M d', strtotime($item['endDate']));
                }
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - 33));
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width, 20, strtoupper($datestr), 0, 1, 'R');
            }

            if ($item['originShortName'] != '') {
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - 33));
                $pdf->SetFontSize($this->SMALL_FONT);
                $pdf->SetFont($this->alt_font, '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width, 20, $item['originShortName'], 0, 1, 'L');
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Signage12UpL.pdf', 'I');
    }
}

}

namespace {
    class Signage12UpL extends \COREPOS\Fannie\API\item\signage\Signage12UpL {}
}

