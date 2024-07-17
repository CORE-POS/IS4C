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

class WfcSmartSigns77UpP extends \COREPOS\Fannie\API\item\signage\Compact4UpP
{
    public function drawPDF()
    {
        $pdf = $this->createPDF();
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $basicP = $dbc->prepare("SELECT
            CASE WHEN pr.priceRuleTypeID = 6 OR pr.priceRuleTypeID = 12 THEN 1 ELSE 0 END
            FROM products AS p
                LEFT JOIN PriceRules AS pr ON p.price_rule_id=pr.priceRuleID
            WHERE upc = ?;");
        $organicLocalP = $dbc->prepare("SELECT 'true' FROM products WHERE numflag & (1<<16) != 0 AND upc = ? AND local > 0");
        $organicP = $dbc->prepare("SELECT 'true' FROM products WHERE numflag & (1<<16) != 0 AND upc = ?");
        $localP = $dbc->prepare("SELECT 'true' FROM products WHERE local > 0 AND upc = ?");

        $data = $this->loadItems();
        $data = $this->sortProductsByPhysicalLocation($this->getDB(), $data, $this->store);
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
            if (trim(strtolower($item['brand'])) == 'field day') {
                $item['basic'] = 1;
            }
            $item['organicLocal'] = $dbc->getValue($organicLocalP, $item['upc']);
            $item['organic'] = $dbc->getValue($organicP, $item['upc']);
            $item['local'] = $dbc->getValue($localP, $item['upc']);

            $pdf->Image($this->getTopImage($item), ($left-1) + ($width*$column), ($top-19) + ($row*$height), 133);
            $bottomImg = $this->getBottomImage($item);
            if (!isset($item['smartType']))
                $item['smartType'] = null;
            if ($bottomImg != 'EXTRAS' && $item['smartType'] != 'CoopDeals') {
                $pdf->Image($this->getBottomImage($item), ($left-1)+($width*$column), $top + ($height*$row) + ($height-$top-8), 133);
            } else {
                $pdf->SetFillColor(0xFB, 0xAA, 0x28);
                $pdf->Rect(($left-1)+($width*$column), $top + ($height*$row) + ($height-$top-13), 133, 12, 'F');
                $pdf->SetTextColor(0xff, 0xff, 0xff);
                $pdf->SetFont($this->font, '', $this->MED_FONT);
                $pdf->SetXY(($left+1)+($width*$column) + 25, $top + ($height*$row) + ($height-$top-11));
                $pdf->Cell(41, 8, 'owners save an ', 0, 0);
                $pdf->SetFont($this->font, 'B', $this->MED_FONT);
                $pdf->Cell(15, 8, 'extra 10%', 0, 0);
                $pdf->SetTextColor(0, 0, 0);
            }

            // if sale is new NCG BOGO
            if (strstr($item['batchName'], 'Co-op Deals') && $item['signMultiplier'] == -3) {
                $bogoImg = __DIR__ . '/noauto/images/bogo-circle.png';
                $pdf->Image($bogoImg,  ($left-1) + ($width*$column) + 106, ($top-19) + ($row*$height) +14, 20, 20);
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Signage12UpL.pdf', 'I');
    }

    private function getTopImage($item)
    {
        // Manual Signs Page && smartType override checked takes precedence
        if (isset($item['smartType']) && $item['smartType'] == 'CoopDeals') {
            return __DIR__ . '/noauto/images/codeals_top_2.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'ChaChing') {
            return __DIR__ . '/noauto/images/chaching_top_2.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'FreshDeals') {
            return __DIR__ . '/noauto/images/freshdeals_top_4.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'Regular') {
            return __DIR__ . '/noauto/images/standard_top_12.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'RegularLocal') {
            return __DIR__ . '/noauto/images/local-top.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'Organic') {
            return __DIR__ . '/noauto/images/organic_top_12.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'OrganicLocal') {
            return __DIR__ . '/noauto/images/local_og_top.png';
        }

        if (strstr($item['batchName'], 'Co-op Deals') && !strstr($item['batchName'], 'TPR')) {
            return __DIR__ . '/noauto/images/codeals_top_2.png';
        } elseif (isset($item['batchName']) && strstr(strtoupper($item['batchName']), 'FRESH DEALS')) {
            return __DIR__ . '/noauto/images/freshdeals_top_4.png';
        } elseif (!empty($item['batchName']) && ((isset($item['batchType']) && $item['batchType'] != 4) || !isset($item['batchType']))) {
            return __DIR__ . '/noauto/images/chaching_top_2.png';
        } elseif ($item['basic']) {
            return __DIR__ . '/noauto/images/basics_top_12.png';
        } elseif ($item['organicLocal']) {
            return __DIR__ . '/noauto/images/local_og_top.png';
        } elseif ($item['organic']) {
            return __DIR__ . '/noauto/images/organic_top_12.png';
        } elseif ($item['local']) {
            return __DIR__ . '/noauto/images/local-top.png';
        }

        return __DIR__ . '/noauto/images/standard_top_12.png';
    }

    private function getBottomImage($item)
    {
        // Manual Signs Page && smartType override checked takes precedence
        if (isset($item['smartType']) && $item['smartType'] == 'CoopDeals') {
            return __DIR__ . '/cd_line_16.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'ChaChing') {
            return __DIR__ . '/noauto/images/chaching_bottom_12.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'FreshDeals') {
            return __DIR__ . '/noauto/images/freshdeals_bottom_4.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'Organic') {
            return __DIR__ . '/noauto/images/organic_bottom_12.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'OrganicLocal') {
            return __DIR__ . '/noauto/images/organic_bottom_12.png';
        }

        if (strstr($item['batchName'], 'Co-op Deals') && !strstr($item['batchName'], 'TPR') && $item['signMultiplier'] != -3) {
            return 'EXTRAS';
        } elseif (strstr($item['batchName'], 'Co-op Deals') && !strstr($item['batchName'], 'TPR') && $item['signMultiplier'] == -3) {
            return __DIR__ . '/cd_line_16.png';
        } elseif (isset($item['batchName']) && strstr(strtoupper($item['batchName']), 'FRESH DEALS')) {
            return __DIR__ . '/noauto/images/freshdeals_bottom_4.png';
        } elseif (!empty($item['batchName']) && ((isset($item['batchType']) && $item['batchType'] != 4) || !isset($item['batchType']))) {
            return __DIR__ . '/noauto/images/chaching_bottom_12.png';
        } elseif ($item['basic']) {
            return __DIR__ . '/noauto/images/basics_bottom_12.png';
        } elseif ($item['organicLocal']) {
            return __DIR__ . '/noauto/images/local_og_bottom.png';
        } elseif ($item['organic']) {
            return __DIR__ . '/noauto/images/organic_bottom_12.png';
        } elseif ($item['local']) {
            return __DIR__ . '/noauto/images/local-bottom.png';
        }



        return __DIR__ . '/noauto/images/standard_bottom_12.png';
    }
}

