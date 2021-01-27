<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpExportPars extends FannieReportPage
{
    protected $header = 'RP Export Pars';
    protected $title = 'RP Export Pars';
    protected $required_fields = array('store');
    protected $report_headers = array('LikeCode', 'Name', 'Category', 'Current Par');

    public function fetch_report_data()
    {
        $nameP = $this->connection->prepare("SELECT likeCodeDesc FROM likeCodes WHERE likeCode=?");
        $parP = $this->connection->prepare("SELECT movement FROM ". FannieDB::fqn('Smoothed', 'plugin:WarehouseDatabase') . "
            WHERE upc=? AND storeID=?");
        $prep = $this->connection->prepare("SELECT upc, c.name FROM RpOrderItems  as i
            LEFT JOIN RpOrderCategories AS c ON i.categoryID=c.rpOrderCategoryID WHERE storeID=?");
        $res = $this->connection->execute($prep, array($this->form->store));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $likeCode = str_replace('LC', '', $row['upc']);
            if (!is_numeric($likeCode)) {
                continue;
            }
            $name = $this->connection->getValue($nameP, array($likeCode));
            $par = $this->connection->getValue($parP, array('LC' . $likeCode, $this->form->store));
            $data[] = array(
                $likeCode,
                $name,
                $row['name'],
                round($par, 2),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $stores = FormLib::storePicker('store', 1);
        return <<<HTML
<form method="get" action="RpExportPars.php">
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Export</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

