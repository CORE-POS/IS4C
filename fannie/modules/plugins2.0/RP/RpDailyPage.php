<?php

use COREPOS\Fannie\API\lib\Operators as Op;
use COREPOS\Fannie\API\data\DataConvert; 

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpDailyPage extends FannieRESTfulPage
{
    protected $header = 'Daily Page';
    protected $title = 'Daily Page';

    public function preprocess()
    {
        $this->addRoute('get<pdf>');

        return parent::preprocess();
    }

    protected function css_content()
    {
        return <<<CSS
#primary-content {
}
table {
    page-break-inside: avoid;
}
CSS;
    }

    protected function get_pdf_handler()
    {
        $store = FormLib::get('store', COREPOS\Fannie\API\lib\Store::getIdByIp());

        $ts = time();
        while (date('N', $ts) != 1) {
            $ts = mktime(0, 0, 0, date('n', $ts), date('j', $ts) - 1, date('Y', $ts));
        }

        $sales = $this->salesTable($store, $ts);
        $stock = $this->stockFirst($store);
        $sale = $this->onSale($store);
        $today = date('l, F jS');
        $model = new StoresModel($this->connection);
        $model->storeID($store);
        $model->load();

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetXY(5, 5);
        $pdf->Cell(200, 15, $today, 0, 0, 'L');
        $pdf->SetXY(5, 5);
        $pdf->Cell(200, 15, $model->description(), 0, 0, 'R');

        $sales = DataConvert::htmlToArray($sales);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetXY(5, 20);
        foreach ($sales as $row) {
            $pdf->SetX(5);
            if (count($row) == 4) {
                $pdf->Cell(30, 7, str_replace('bold', '', $row[0]), 1, 0, 'L');
                $pdf->Cell(30, 7, str_replace('bold', '', $row[1]), 1, 0, 'L');
                $pdf->Cell(30, 7, str_replace('bold', '', $row[2]), 1, 0, 'L');
                $pdf->Cell(30, 7, str_replace('bold', '', $row[3]), 1, 1, 'L');
            } else {
                $pdf->Cell(60, 7, str_replace('bold', '', $row[0]), 1, 0, 'L');
                $pdf->Cell(60, 7, str_replace('bold', '', $row[1]), 1, 1, 'L');
            }
        }

        $pdf->SetXY(5, 85);
        $pdf->Cell(120, 7, 'On Shift Today / Samples', 1, 1, 'C');
        $pdf->SetX(5);
        $pdf->Cell(120, 130, '', 1, 1, 'C');

        $pdf->SetX(5);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(120, 7, 'Check Floral Plants:', 1, 0, 'L');
        $pdf->SetX(40);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(12, 7, 'am', 0, 0);
        $pdf->Cell(12, 7, 'pm', 0, 0);

        $pdf->Ln();
        $pdf->SetX(5);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(120, 7, 'Front and Face:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX(30);
        $pdf->Cell(12, 7, '6am', 0, 0);
        $pdf->Cell(12, 7, '10am', 0, 0);
        $pdf->Cell(12, 7, '2pm', 0, 0);
        $pdf->Cell(12, 7, '6pm', 0, 0);
        $pdf->Ln();
        $pdf->SetX(5);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(120, 7, 'Daily Critical Temp Food spot check:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX(63);
        $pdf->Cell(12, 8, '_________', 0, 0);
        $pdf->Ln();


        $stock = str_replace('&', '&amp;', $stock);
        $stock = DataConvert::htmlToArray($stock);
        $pdf->SetXY(160, 20);
        foreach ($stock as $row) {
            $pdf->SetX(160);
            if (!empty($row[0])) {
                $pdf->Cell(50, 7, str_replace('bold', '', $row[0]), 1, 1, 'L');
            }
        }

        $sale = str_replace('&', '&amp;', $sale);
        $sale = DataConvert::htmlToArray($sale);
        $pdf->SetXY(160, 110);
        foreach ($sale as $row) {
            $pdf->SetX(160);
            if (!empty($row[0])) {
                $pdf->Cell(50, 7, str_replace('bold', '', $row[0]), 1, 1, 'L');
            }
        }

        $pdf->AddPage();
        $pdf->SetDrawColor(0,0,0);
        $pdf->Line(108,0,108,279);
        $pdf->Line(0,135,215,135);

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetAutoPageBreak(false);
        for ($i=0; $i<4; $i++) {
            $posX = $i % 2 == 0 ? 5 : 110;
            $posY = ($i/2) % 2 == 0 ? 10 : 140;
            $pdf->SetXY($posX,$posY);

            $header = true;
            foreach ($stock as $row) {
                $pdf->SetX($posX);
                if (!empty($row[0])) {
                    $pdf->Cell(60, 5, str_replace('bold', '', $row[0]), 1, 0, 'L');
                    $pdf->Cell(20, 5, $header ? 'Qty' : '', 1, 0, 'L');
                    $pdf->Cell(20, 5, $header ? 'Initial' : '', 1, 1, 'L');
                    if ($header) {
                        $header = false;
                    }
                }
            }

            $pdf->Ln(5);

            $pdf->SetX($posX);
            $header = true;
            foreach ($sale as $row) {
                $pdf->SetX($posX);
                if (!empty($row[0])) {
                    $pdf->Cell(60, 5, str_replace('bold', '', $row[0]), 1, 0, 'L');
                    $pdf->Cell(20, 5, $header ? 'Qty' : '', 1, 0, 'L');
                    $pdf->Cell(20, 5, $header ? 'Initial' : '', 1, 1, 'L');
                    if ($header) {
                        $header = false;
                    }
                }
            }
        }

        $pdf->Output('Daily.pdf', 'I');

        return false;
    }

    protected function get_view()
    {
        $store = FormLib::get('store', COREPOS\Fannie\API\lib\Store::getIdByIp());

        $ts = time();
        while (date('N', $ts) != 1) {
            $ts = mktime(0, 0, 0, date('n', $ts), date('j', $ts) - 1, date('Y', $ts));
        }

        $sales = $this->salesTable($store, $ts);
        $greens = '';
        $stock = $this->stockFirst($store);
        $sale = $this->onSale($store);
        $today = date('l, F jS');

        $stores = FormLib::storePicker('store', false, "window.location='RpDailyPage.php?store='+this.value");

        return <<<HTML
<div style="">
<div class="row">
    <div class="col-sm-5">
        <h3>{$today}</h3>
    </div>
    <div class="col-sm-2">
        <a href="RpDailyPage.php?pdf=1&store={$store}" class="btn btn-default">Print</a>
    </div>
    <div class="col-sm-3">
        {$stores['html']}
    </div>
</div>
<div class="row">
    <div class="col-sm-8">
        <div class="row=">
            {$sales}
        </div>
        <div class="row=">
            <table class="table table-bordered">
                <tr><th style="text-align: center;" align="center">On Shift Today / Samples</th></tr>
                <tr style="height: 600px"><td>&nbsp;</td></tr>
                <tr><td><b>Check Floral Plants</b>:
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    am
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    pm
                </td></tr>
                <tr><td><b>Front and Face</b>:
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    6am
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    10am
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    2pm
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    6pm
                </td></tr>
                <tr><td><b>Daily Critical Temp Food spot check</b>: ____________</td></tr>
            </table>
        </div>
        <div class="row=">
            <div class="col-sm-6">
                {$preps}
            </div>
            <div class="col-sm-6">
                {$greens}
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        {$stock}
        <br />
        {$sale}
    </div>
</div>
</div>
HTML;
    }

    private function salesTable($store, $ts)
    {
        $segP = $this->connection->prepare("SELECT * FROM RpSegments WHERE storeID=? AND startDate=?");
        if (date('N') == 1) {
            $ts = mktime(0, 0, 0, date('n', $ts), date('j',$ts) - 7, date('Y',$ts));
        }
        $seg = $this->connection->getRow($segP, array($store, date('Y-m-d', $ts)));
        $ret = '<table class="table table-bordered table-striped">
            <tr><th>Day</th><th>Goal</th><th>Actual</th><th>Growth</th></tr>';
        if ($seg === false) {
            return $ret . '<tr><td colspan="4">No Data</td></tr></table>';
        }

        $ttl = $seg['sales'];
        $pcts = json_decode($seg['segmentation'], true);
        $thisYear = json_decode($seg['thisYear'], true);
        $modify = array('plan'=>0, 'this'=>0, 'points'=>0);
        $planTTL = 0;
        foreach ($pcts as $day => $pct) {
            $plan = $ttl * $pct;
            $cur = $thisYear[$day] > 0 ? $thisYear[$day] : '';
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%.2f%%</td></tr>',
                $day,
                number_format($plan, 2),
                ($cur ? number_format($cur, 2) : ''),
                ($cur ? (($cur - $plan) / $cur) * 100 : 0)
            );
            if ($cur) {
                $modify['plan'] += $plan;
                $modify['this'] += $cur;
                $modify['points']++;
            }
            $planTTL += $plan;
        }
        if ($modify['points']) {
            $mod = ($modify['this'] - $modify['plan']) / $modify['plan'];
            $mod *= $modify['points'] / 7;
            $ttl = $ttl * (1 + $mod);
        }
        if (date('N') == 1) {
            $ttl = $modify['this'];
        }
        $growth = ($ttl - $planTTL) / $ttl * 100;
        if (date('N') == 1) {
            $lastYear = json_decode($seg['lastYear'], true);
            $ly = 0;
            foreach ($lastYear as $day => $amount) {
                $ly += $amount;
            }
            $growth = ($ttl - $ly) / $ttl * 100;
        }
        $ret .= '<tr><th colspan="2">Projected Total</th><th>'
            . number_format($ttl, 2) . '</th><th>'
            . sprintf('%.2f%%', $growth) . '</th></tr></table>';


        return $ret;
    } 

    protected function stockFirst($store)
    {
        $nameP = $this->connection->prepare("SELECT likeCodeDesc FROM likeCodes WHERE likeCode=?");
        $batchP = $this->connection->prepare("SELECT l.batchID FROM batchList AS l
            INNER JOIN batches AS b ON l.batchID=b.batchID
            WHERE l.upc=?
                AND " . $this->connection->curdate() . " BETWEEN b.startDate AND b.endDate
                AND b.discountType > 0");
        $dataP = $this->connection->prepare("SELECT r.upc
            FROM RpSubTypes AS r
                LEFT JOIN " . FannieDB::fqn('Smoothed', 'plugin:WarehouseDatabase') . " AS w ON r.upc=w.upc
            WHERE w.storeID=?
                AND r.upc <> 'LC50'
                AND subType='stock'
            ORDER BY w.movement * r.price DESC");
        $ret = '<table class="table table-bordered table-striped">
                <tr><th>Stock First</th></tr>';
        $dataR = $this->connection->execute($dataP, array($store));
        $count = 0;
        while ($row = $this->connection->fetchRow($dataR)) {
            $name = $this->connection->getValue($nameP, array(substr($row['upc'], 2)));
            if ($this->connection->getValue($batchP, array($row['upc']))) {
                $name = '** ' . $name;
            }
            $ret .= '<tr><td>' . $name . '</td></tr>';
            $count++;
            if ($count >= 10) {
                break;
            }
        }

        return $ret . '</table>';
    }

    protected function onSale($store)
    {
        $nameP = $this->connection->prepare("SELECT likeCodeDesc FROM likeCodes WHERE likeCode=?");
        $batchR = $this->connection->query("SELECT l.upc FROM batchList AS l
            INNER JOIN batches AS b ON l.batchID=b.batchID
            WHERE l.upc LIKE 'LC%'
                AND " . $this->connection->curdate() . " BETWEEN b.startDate AND b.endDate
                AND b.discountType > 0
                AND b.batchName LIKE '%Pro%Deal%'");
        $count = 0;
        $ret = '<table class="table table-bordered table-striped">
                <tr><th>On Sale</th></tr>';
        while ($row = $this->connection->fetchRow($batchR)) {
            $name = $this->connection->getValue($nameP, array(substr($row['upc'], 2)));
            $ret .= '<tr><td>' . $name . '</td></tr>';
            $count++;
            if ($count >= 20) {
                break;
            }
        }

        return $ret . '</table>';
    }
}

FannieDispatch::conditionalExec();

