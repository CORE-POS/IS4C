<?php

use COREPOS\Fannie\API\lib\Operators as Op;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpMarginEst extends FannieReportPage
{
    protected $header = "RP Margin Estimate";
    protected $title = "RP Margin Estimate";
    protected $required_fields = array('store', 'drop');
    protected $report_headers = array('LC', 'Case Cost', 'Case Size', 'Unit Cost', 'Retail', 'Est. Movement', 'Total Cost', 'Total Retail', 'Margin');

    public function fetch_report_data()
    {
        $data = array();
        $parP = $this->connection->prepare("
            SELECT movement
            FROM " . FannieDB::fqn('Smoothed', 'plugin:WarehouseDatabase') . "
            WHERE storeID=?
                AND upc=?"); 
        $lcP = $this->connection->prepare("SELECT likeCodeDesc FROM likeCodes WHERE likeCode=?");
        foreach (explode("\n", $this->form->drop) as $line) {
            if (preg_match('/(\d+)].*{(\d\.\d\d)}.*\[\$(\d+\.\d\d)\].*\s(\d+)\s*$/', $line, $matches)) {
                $lc = $matches[1];
                $retail = $matches[2];
                $caseCost = $matches[3];
                $caseSize = $matches[4];
                $unitCost = round($caseCost / $caseSize, 3);
                $mvmt = $this->connection->getValue($parP, array($this->form->store, 'LC' . $lc));
                $name = $this->connection->getValue($lcP, array($lc));
                $data[] = array(
                    $lc . ' ' . $name,
                    $caseCost,
                    $caseSize,
                    sprintf('%.3f', $unitCost),
                    $retail,
                    sprintf('%.2f', $mvmt),
                    sprintf('%.2f', $unitCost * $mvmt),
                    sprintf('%.2f', $retail * $mvmt),
                    sprintf('%.2f', Op::div($retail - $unitCost, $retail) * 100),
                );
            }
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sums = array(0, 0);
        foreach ($data as $row) {
            $sums[0] += $row[6];
            $sums[1] += $row[7];
        }
        $margin = Op::div($sums[1] - $sums[0], $sums[1]) * 100;

        return array('Total', '', '', '', '', '', sprintf('%.2f', $sums[0]), sprintf('%.2f', $sums[1]), sprintf('%.2f', $margin));
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="post">
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <label>Paste Drop Here</label>
        <textarea name="drop" class="form-control" rows="10"></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Run Report</button>
    </div>
</form>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

