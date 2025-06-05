<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

*********************************************************************************/

namespace COREPOS\Fannie\Plugin\CoopDealsSigns;
use \FannieDB;
use \FannieConfig;

class WfcSmartSigns2UpP extends \COREPOS\Fannie\API\item\signage\Signage2UpP 
{
    protected $BIG_FONT = 200;
    protected $MED_FONT = 22;
    protected $SMALL_FONT = 18;
    protected $SMALLER_FONT = 14;
    protected $SMALLEST_FONT = 16;

    protected $width = 203.2;
    protected $height = 138.35;

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
        $count = 0;
        $sign = 0;
        $width = 203.2;
        $height = 138.35;
        $top = 43;
        $left = 5;
        $effective_width = $width - (2*$left);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        foreach ($data as $k => $item) {
            $data[$k]['size'] = null;
        }
        foreach ($data as $item) {
            $item = $this->getLikeCodeBatchName($this->source_id, $item);
            if ($count % 2 == 0) {
                $pdf->AddPage();
                $sign = 0;
            }

            $row = $sign;
            $column = 1;

            $pdf = $this->drawItem($pdf, $item, $row, $column);

            $item['basic'] = $dbc->getValue($basicP, $item['upc']);
            $item['organicLocal'] = $dbc->getValue($organicLocalP, $item['upc']);
            $item['organic'] = $dbc->getValue($organicP, $item['upc']);
            $item['local'] = $dbc->getValue($localP, $item['upc']);

            $pdf->Image($this->getTopImage($item), ($left-1), ($top-42) + ($row*$height), $width);
            $bottomImg = $this->getBottomImage($item);
            if (!isset($item['smartType']))
                $item['smartType'] = null;
            if ($bottomImg != 'EXTRAS' && $item['smartType'] != 'CoopDeals') {
                $pdf->Image($bottomImg, ($left-1), $top + ($height*$row) + ($height-$top-6), $width, 2);
            } else {
                $pdf->SetFillColor(0xFB, 0xAA, 0x28);
                $pdf->Rect(($left-1), $top + ($height*$row) + ($height-$top-14), $width, 12, 'F');
                $pdf->SetTextColor(0xff, 0xff, 0xff);
                $pdf->SetFont($this->font, '', $this->MED_FONT);
                $pdf->SetXY(($left+1) + 50, $top + ($height*$row) + ($height-$top-12));
                $pdf->Cell(51, 8, 'owners save an ', 0, 0);
                $pdf->SetFont($this->font, 'B', $this->MED_FONT);
                $pdf->Cell(15, 8, 'extra 10%', 0, 0);
                $pdf->SetTextColor(0, 0, 0);
            }

            // if sale is new NCG BOGO
            if (strstr($item['batchName'], 'Co-op Deals') && $item['signMultiplier'] == -3) {
                $bogoImg = __DIR__ . '/noauto/images/bogo-circle.png';
                $pdf->Image($bogoImg,  ($left-1)+160, ($top-22) + ($row*$height), 26, 26);
            }

            $count++;
            $sign++;
        }

        $pdf->Output('Signage2UpP.pdf', 'I');
    }

    private function getTopImage($item)
    {
        // Manual Signs Page && smartType override checked takes precedence
        if (isset($item['smartType']) && $item['smartType'] == 'CoopDeals') {
            return __DIR__ . '/noauto/images/codeals_top_2.png';
        } else if (isset($item['smartType']) && $item['smartType'] == 'ChaChing') {
            return __DIR__ . '/noauto/images/chaching_top_2.png';
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
            return __DIR__ . '/noauto/images/codeals_top_2.png';
        } elseif (isset($item['batchName']) && strstr(strtoupper($item['batchName']), 'FRESH DEALS')) {
            return __DIR__ . '/noauto/images/freshdeals_top_2.png';
        } elseif (!empty($item['batchName'])) {
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
        } elseif (strstr($item['batchName'], 'Co-op Deals') && !strstr($item['batchName'], 'TPR')) {
            return __DIR__ . '/cd_line_16.png';
        } elseif (isset($item['batchName']) && strstr(strtoupper($item['batchName']), 'FRESH DEALS')) {
            return __DIR__ . '/noauto/images/freshdeals_bottom_2.png';
        } elseif (!empty($item['batchName'])) {
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

