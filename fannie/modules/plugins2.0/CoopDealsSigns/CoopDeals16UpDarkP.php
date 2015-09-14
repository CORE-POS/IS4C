<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

*********************************************************************************/

namespace COREPOS\Fannie\Plugin\CoopDealsSigns {

class CoopDeals16UpDarkP extends \COREPOS\Fannie\API\item\FannieSignage 
{
    protected $BIG_FONT = 40;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;
    protected $SMALLER_FONT = 8;
    protected $SMALLEST_FONT = 5;

    protected $footer_image = 'cd_line_16.png';

    public function drawPDF()
    {
        $pdf = new \FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(0, 3.175, 0);
        $pdf->SetAutoPageBreak(false);
        define('FPDF_FONTPATH', dirname(__FILE__) . '/noauto/fonts/');
        $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
        $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
        $pdf->AddFont('GillBook', '', 'GillSansMTPro-Book.php');
        $pdf->SetFont('Gill', '', 16);

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $width = 53.975;
        $height = 68.96;
        $top = 20;
        $left = 5.175;
        $effective_width = $width - (2*$left);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        foreach ($data as $item) {
            if ($count % 16 == 0) {
                $pdf->AddPage();
                // draw tick marks for cutting
                $pdf->Line(2, $height+1.5, 6, $height+1.5);
                $pdf->Line(2, (2*$height)+1.5, 6, (2*$height)+1.5);
                $pdf->Line(2, (3*$height)+1.5, 6, (3*$height)+1.5);
                $pdf->Line($width, 2, $width, 6);
                $pdf->Line(2*$width, 2, 2*$width, 6);
                $pdf->Line(3*$width, 2, 3*$width, 6);
                $pdf->Line($width, (4*$height)-4, $width, 4*$height);
                $pdf->Line(2*$width, (4*$height)-4, 2*$width, 4*$height);
                $pdf->Line(3*$width, (4*$height)-4, 3*$width, 4*$height);
                $pdf->Line(4*$width-6, $height+1.5, 4*$width-2, $height+1.5);
                $pdf->Line(4*$width-6, (2*$height)+1.5, 4*$width-2, (2*$height)+1.5);
                $pdf->Line(4*$width-6, (3*$height)+1.5, 4*$width-2, (3*$height)+1.5);
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

            $pdf->Image(dirname(__FILE__) . '/cd_head_16.png', ($left-2) + ($width*$column), ($top-17) + ($row*$height), $width-6);

            $pdf->SetXY($left + ($width*$column), $top + ($row*$height) - 2);
            $pdf->SetFont('Gill', 'B', $this->SMALL_FONT);
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
                    $pdf->SetFontSize($this->MED_FONT - $font_shrink);
                    $pdf->SetXY($left + ($width*$column), $y);
                } else {
                    break;
                }
            }

            $pdf->SetX($left + ($width*$column));
            $pdf->SetFont('Gill', '', $this->MED_FONT);
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
            $pdf->SetFont('GillBook', '', $this->SMALLER_FONT);
            $item['size'] = strtolower($item['size']);
            if (substr($item['size'], -1) != '.') {
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
            $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height-$top-18));
            $pdf->SetFont('Gill', '', $this->BIG_FONT);
            $pdf->MultiCell($effective_width, 10, $price, 0, 'C');

            if ($item['startDate'] != '' && $item['endDate'] != '') {
                // intl would be nice
                $datestr = date('M d', strtotime($item['startDate']))
                    . chr(0x96) // en dash in cp1252
                    . date('M d', strtotime($item['endDate']));
                $pdf->SetXY($left + ($width*$column), $top + ($height*$row) + ($height - $top - 7));
                $pdf->SetFont('GillBook', '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width+4, 6, strtoupper($datestr), 0, 1, 'R');
            }

            if ($item['upc'] != '') {
                $pdf->SetXY($left-2 + ($width*$column), $top + ($height*$row) + ($height - $top - 7));
                $pdf->SetFont('GillBook', '', $this->SMALLEST_FONT);
                $pdf->Cell($effective_width, 6, $item['upc'], 0, 1, 'L');
            }

            $pdf->Image(dirname(__FILE__) . '/' . $this->footer_image, ($left-2)+($width*$column), $top + ($height*$row) + ($height-$top-2), $width-6);

            $count++;
            $sign++;
        }

        $pdf->Output('Signage16UpP.pdf', 'I');
    }
}

}

