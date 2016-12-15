<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

*********************************************************************************/

namespace COREPOS\Fannie\Plugin\CoopDealsSigns {

class CoopDeals16UpDarkP extends \COREPOS\Fannie\API\item\signage\Signage16UpP 
{
    protected $BIG_FONT = 40;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;
    protected $SMALLER_FONT = 8;
    protected $SMALLEST_FONT = 5;

    protected $footer_image = 'cd_line_16.png';

    protected $width = 53.975;
    protected $height = 68.96;

    public function drawPDF()
    {
        $pdf = $this->createPDF();

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

            $pdf = $this->drawItem($pdf, $item, $row, $column);

            $pdf->Image(dirname(__FILE__) . '/cd_head_16.png', ($left-2) + ($width*$column), ($top-17) + ($row*$height), $width-6);
            $pdf->Image(dirname(__FILE__) . '/' . $this->footer_image, ($left-2)+($width*$column), $top + ($height*$row) + ($height-$top-2), $width-6);

            $count++;
            $sign++;
        }

        $pdf->Output('Signage16UpP.pdf', 'I');
    }
}

}

