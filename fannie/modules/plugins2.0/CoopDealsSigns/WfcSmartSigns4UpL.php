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

namespace COREPOS\Fannie\Plugin\CoopDealsSigns;
use \FannieDB;
use \FannieConfig;

class WfcSmartSigns4UpL extends \COREPOS\Fannie\API\item\signage\Giganto4UpP 
{
    public function drawPDF()
    {
        $pdf = $this->createPDF();
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $basicP = $dbc->prepare("SELECT
            CASE WHEN pr.priceRuleTypeID = 6 THEN 1 ELSE 0 END
            FROM products AS p
                LEFT JOIN PriceRules AS pr ON p.price_rule_id=pr.priceRuleID
            WHERE upc = ?;");
        $organicLocalP = $dbc->prepare("SELECT 'true' FROM products WHERE numflag & (1<<16) != 0 AND upc = ? AND local > 0");
        $organicP = $dbc->prepare("SELECT 'true' FROM products WHERE numflag & (1<<16) != 0 AND upc = ?");

        $data = $this->loadItems();
        $count = 0;
        $sign = 0;
        $width = 139;
        $height = 108;
        $top = 23;
        $left = 6;
        foreach ($data as $item) {
            if ($count % 4 == 0) {
                if ($count != 0) {
                    // draw tick marks again
                    // sometimes other content of the page
                    // overwrites them
                    $pdf->Line(2, $height+0.0, 6, $height+0.0);
                    $pdf->Line(2, (2*$height)+1.0, 6, (2*$height)+1.0);
                    $pdf->Line(4*$width-3, $height+0.0, 4*$width+1, $height+0.0);
                    $pdf->Line(4*$width-3, (2*$height)+1.0, 4*$width+1, (2*$height)+1.0);

                    $pdf->Line($width+1.5, 2, $width+1.5, 8);
                    $pdf->Line($width+1.5, (3*$height)-6, $width+1.5, 3*$height);
                }
                $pdf->AddPage();
                // draw tick marks for cutting
                $pdf->Line(2, $height+0.0, 6, $height+0.0);
                $pdf->Line(2, (2*$height)+1.0, 6, (2*$height)+1.0);
                $pdf->Line(4*$width-3, $height+0.0, 4*$width+1, $height+0.0);
                $pdf->Line(4*$width-3, (2*$height)+1.0, 4*$width+1, (2*$height)+1.0);

                $pdf->Line($width+1.5, 2, $width+1.5, 8);
                $pdf->Line($width+1.5, (3*$height)-6, $width+1.5, 3*$height);
                $sign = 0;
            }

            $row = floor($sign / 2);
            $column = $sign % 2;

            $pdf = $this->drawItem($pdf, $item, $row, $column);

            $item['basic'] = $dbc->getValue($basicP, $item['upc']);
            $item['organicLocal'] = $dbc->getValue($organicLocalP, $item['upc']);
            $item['organic'] = $dbc->getValue($organicP, $item['upc']);

            $pdf->Image($this->getTopImage($item), ($left-1) + ($width*$column), ($top-19) + ($row*$height), 133);
            $pdf->Image($this->getBottomImage($item), ($left-1)+($width*$column), $top + ($height*$row) + ($height-$top-8), 133);

            $count++;
            $sign++;
        }

        $pdf->Output('Signage4UpL.pdf', 'I');
    }

    private function getTopImage($item)
    {
        if (strstr($item['batchName'], 'Co-op Deals') && !strstr($item['batchName'], 'TPR')) {
            return __DIR__ . '/noauto/images/codeals_top_12.png';
        } elseif (!empty($item['batchName'])) {
            return __DIR__ . '/noauto/images/chaching_top_12.png';
        } elseif ($item['basic']) {
            return __DIR__ . '/noauto/images/basics_top_12.png';
        } elseif ($item['organicLocal']) {
            return __DIR__ . '/noauto/images/local_og_top.png';
        } elseif ($item['organic']) {
            return __DIR__ . '/noauto/images/organic_top_12.png';
        }


        return __DIR__ . '/noauto/images/standard_top_12.png';
    }

    private function getBottomImage($item)
    {
        if (strstr($item['batchName'], 'Co-op Deals') && !strstr($item['batchName'], 'TPR')) {
            return __DIR__ . '/cd_line_16.png';
        } elseif (!empty($item['batchName'])) {
            return __DIR__ . '/noauto/images/chaching_bottom_12.png';
        } elseif ($item['basic']) {
            return __DIR__ . '/noauto/images/basics_bottom_12.png';
        } elseif ($item['organicLocal']) {
            return __DIR__ . '/noauto/images/local_og_bottom.png';
        } elseif ($item['organic']) {
            return __DIR__ . '/noauto/images/organic_bottom_12.png';
        }


        return __DIR__ . '/noauto/images/standard_bottom_12.png';
    }
}

