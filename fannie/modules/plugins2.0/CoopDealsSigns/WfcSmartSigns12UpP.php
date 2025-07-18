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

class WfcSmartSigns12UpP extends \COREPOS\Fannie\API\item\signage\Compact12UpL 
{
    protected $BIG_FONT = 40;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;
    protected $SMALLER_FONT = 8;
    protected $SMALLEST_FONT = 6;

    protected $width = 68.67;
    protected $left = 6.0;

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
        $width = 68.67;
        $height = 71;
        $top = 22;
        $left = 6.0;
        foreach ($data as $item) {
            $item = $this->getLikeCodeBatchName($this->source_id, $item);
            if ($count % 12 == 0) {
                if ($count != 0) {
                    // draw tick marks again
                    // sometimes other content of the page
                    // overwrites them
                    $pdf = $this->tickMarks($pdf, $width, $height);
                }
                $pdf->AddPage();
                // draw tick marks for cutting
                $pdf = $this->tickMarks($pdf, $width, $height);
                $sign = 0;
            }

            $row = floor($sign / 4);
            $column = $sign % 4;

            $pdf = $this->drawItem($pdf, $item, $row, $column);

            $item['basic'] = $dbc->getValue($basicP, $item['upc']);
            $item['organicLocal'] = $dbc->getValue($organicLocalP, $item['upc']);
            $item['organic'] = $dbc->getValue($organicP, $item['upc']);
            $item['local'] = $dbc->getValue($localP, $item['upc']);

            $pdf->Image($this->getTopImage($item), ($left-1) + ($width*$column), ($top-19) + ($row*$height), 62.67);
            $bottomImg = $this->getBottomImage($item);
            if (!isset($item['smartType']))
                $item['smartType'] = null;
            if ($bottomImg != 'EXTRAS' && $item['smartType'] != 'CoopDeals') {
                $pdf->Image($bottomImg, ($left-1)+($width*$column), $top + ($height*$row) + ($height-$top-4) - 8, 62.67);
            } else {
                $pdf->SetFillColor(0xFB, 0xAA, 0x28);
                $pdf->Rect(($left-1)+($width*$column), $top + ($height*$row) + ($height-$top-10), 62.5, 10, 'F');
                $pdf->SetTextColor(0xff, 0xff, 0xff);
                $pdf->SetFont($this->font, '', $this->MED_FONT);
                $pdf->SetXY(($left+1)+($width*$column), $top + ($height*$row) + ($height-$top-8));
                $pdf->Cell(32, 6, 'owners save an ', 0, 0);
                $pdf->SetFont($this->font, 'B', $this->MED_FONT);
                $pdf->Cell(15, 6, 'extra 10%', 0, 0);
                $pdf->SetTextColor(0, 0, 0);
            }

            // if sale is new NCG BOGO
            if (strstr($item['batchName'], 'Co-op Deals') && $item['signMultiplier'] == -3) {
                $bogoImg = __DIR__ . '/noauto/images/bogo-circle.png';
                $pdf->Image($bogoImg,  ($left) + ($width*$column) + 48, ($top-10) + ($row*$height) - 2, 12, 12);
            }

            // white out dates on Basics Signs
            if ($item['basic'] && empty($item['batchName'])) {
                $pdf->SetXY(($left+45)+($width*$column), $top + ($height*$row) + ($height-$top-18));
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(18, 4, ' ', 0, 0, 'L', 1);
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
            return __DIR__ . '/noauto/images/codeals_top_12.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'ChaChing') {
            return __DIR__ . '/noauto/images/chaching_top_12.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'FreshDeals') {
            return __DIR__ . '/noauto/images/freshdeals_top_2_300x2dpi.png';
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
            return __DIR__ . '/noauto/images/codeals_top_12.png';
        } elseif (isset($item['batchName']) && strstr(strtoupper($item['batchName']), 'FRESH DEALS')) {
            return __DIR__ . '/noauto/images/freshdeals_top_4.png';
        } elseif (!empty($item['batchName']) && ((isset($item['batchType']) && $item['batchType'] != 4) || !isset($item['batchType']))) {
            return __DIR__ . '/noauto/images/chaching_top_12.png';
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

