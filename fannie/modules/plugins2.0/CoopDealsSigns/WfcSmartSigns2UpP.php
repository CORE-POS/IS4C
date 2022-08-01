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
            $pdf->Image($this->getBottomImage($item), ($left-1), $top + ($height*$row) + ($height-$top-6), $width, 2);

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
        } elseif ($item['local']) {
            return __DIR__ . '/noauto/images/local-top.png';
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
        } elseif ($item['local']) {
            return __DIR__ . '/noauto/images/local-bottom.png';
        }

        return __DIR__ . '/noauto/images/standard_bottom_12.png';
    }
}

