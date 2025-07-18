<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

*********************************************************************************/

namespace COREPOS\Fannie\Plugin\CoopDealsSigns;
use \FannieDB;
use \FannieConfig;

class SingleWfcSmartSigns16UpL extends \COREPOS\Fannie\API\item\signage\SingleCompact16UpL
{
    protected $BIG_FONT = 30;
    protected $MED_FONT = 14;
    protected $SMALL_FONT = 10;
    protected $SMALLER_FONT = 8;
    protected $SMALLEST_FONT = 5;

    protected $width = 53;
    protected $height = 68.96;

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
        $localP = $dbc->prepare("SELECT 'true' FROM products WHERE local > 0 AND upc = ?");

        $data = $this->loadItems();
        $data = $this->sortProductsByPhysicalLocation($this->getDB(), $data, $this->store);
        $count = 0;
        $sign = 0;
        $width = 53.975;
        $height = 68.96;
        $top = 20;
        // moving smart width only moves the smart style (and is GOOD)
        //$left = 5.175;
        $left = 15.175;
        $effective_width = $width - (2*$left);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        foreach ($data as $item) {
            if ($count % 2 == 0) {
                $pdf->AddPage("L", "A6");
                // draw tick marks for cutting
                //$pdf->Line(2, $height+1.5, 6, $height+1.5);
                //$pdf->Line(2, (2*$height)+1.5, 6, (2*$height)+1.5);
                //$pdf->Line(2, (3*$height)+1.5, 6, (3*$height)+1.5);
                //$pdf->Line($width, 2, $width, 6);
                //$pdf->Line(2*$width, 2, 2*$width, 6);
                //$pdf->Line(3*$width, 2, 3*$width, 6);
                //$pdf->Line($width, (4*$height)-4, $width, 4*$height);
                //$pdf->Line(2*$width, (4*$height)-4, 2*$width, 4*$height);
                //$pdf->Line(3*$width, (4*$height)-4, 3*$width, 4*$height);
                //$pdf->Line(4*$width-6, $height+1.5, 4*$width-2, $height+1.5);
                //$pdf->Line(4*$width-6, (2*$height)+1.5, 4*$width-2, (2*$height)+1.5);
                //$pdf->Line(4*$width-6, (3*$height)+1.5, 4*$width-2, (3*$height)+1.5);
                //$sign = 0;
            }

            //$row = floor($sign / 2);
            $row = 0;
            $column = $sign % 2;

            $pdf = $this->drawItem($pdf, $item, $row, $column);

            $item['basic'] = $dbc->getValue($basicP, $item['upc']);
            $item['organicLocal'] = $dbc->getValue($organicLocalP, $item['upc']);
            $item['organic'] = $dbc->getValue($organicP, $item['upc']);
            $item['local'] = $dbc->getValue($localP, $item['upc']);

            $pdf->Image($this->getTopImage($item), ($left-2) + ($width*$column), ($top-17) + ($row*$height), $width-6);
            $bottomImg = $this->getBottomImage($item);
            if (!isset($item['smartType']))
                $item['smartType'] = null;
            if ($bottomImg == 'EXTRAS' || $item['smartType'] == 'CoopDeals') {
                $pdf->SetXY(($left-2)+($width*$column), $top + ($height*$row) + ($height-$top-2));
                $pdf->SetFillColor(0xFB, 0xAA, 0x28);
                $pdf->SetTextColor(0xff, 0xff, 0xff);
                $pdf->Rect(($left-2)+($width*$column), $top + ($height*$row) + ($height-$top-8), 48, 8, 'F');
                $pdf->SetFont($this->font, '', $this->MED_FONT-3);
                $pdf->setXY(($left-1)+($width*$column), $top + ($height*$row) + ($height-$top-7));
                $pdf->Cell(25, 6, 'owners save an ', 0, 0);
                $pdf->SetFont($this->font, 'B', $this->MED_FONT-3);
                $pdf->Cell(15, 6, 'extra 10%', 0, 0);
                $pdf->SetTextColor(0, 0, 0);
            } else {
                $pdf->Image($bottomImg, ($left-2)+($width*$column), $top + ($height*$row) + ($height-$top-2) - 4, $width-6);
            }

            // if sale is new NCG BOGO
            if (strstr($item['batchName'], 'Co-op Deals') && $item['signMultiplier'] == -3) {
                $bogoImg = __DIR__ . '/noauto/images/bogo-circle.png';
                $pdf->Image($bogoImg,  ($left-1) + ($width*$column) + 37  , ($top-19) + ($row*$height) + 8, 8, 8);
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Signage16UpL.pdf', 'I');
    }

    private function getTopImage($item)
    {
        // Manual Signs Page && smartType override checked takes precedence
        if (isset($item['smartType']) && $item['smartType'] == 'CoopDeals') {
            return __DIR__ . '/noauto/images/codeals_top_12.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'ChaChing') {
            return __DIR__ . '/noauto/images/chaching_top_12.png';
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

