<?php

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class CwCoEfficient extends FannieRESTfulPage 
{
    public $description = '[CoEfficient Report] lists some NCG stats';
    protected $header = 'CoEfficient';
    protected $title = 'CoEfficient';

    public function preprocess()
    {
        $this->addRoute('get<date1><date2>');

        return parent::preprocess();
    }

    protected function get_date1_date2_view()
    {
        $date1 = date('Ymd', strtotime($this->date1));
        $date2 = date('Ymd', strtotime($this->date2));
        $skuP = $this->connection->prepare("
            SELECT COUNT(DISTINCT d.upc) AS items
            FROM " . FannieDB::fqn('sumUpcSalesByDay', 'plugin:WarehouseDatabase') . " AS d
            WHERE date_id BETWEEN ? AND ?
                AND length(upc)=13
                AND upc <> '0000000000000'
                AND upc REGEXP '^[0-9]+$'");
        $skus = $this->connection->getValue($skuP, array($date1, $date2));
        $skus = array('items' => $skus);
        $salesP = $this->connection->prepare("
            SELECT SUM(total) AS ttl
            FROM " . FannieDB::fqn('sumDeptSalesByDay', 'plugin:WarehouseDatabase') . " AS d
                INNER JOIN MasterSuperDepts AS m ON m.dept_ID=d.department
            WHERE date_id BETWEEN ? AND ?
                AND m.superID<>0");
        $sales = $this->connection->getValue($salesP, array($date1, $date2));
        $skus['ttl'] = $sales;

        $upcP = $this->connection->prepare("SELECT upc FROM products WHERE local > 0 GROUP BY upc");
        $upcs = $this->connection->getAllValues($upcP);
        list($inStr, $args) = $this->connection->safeInClause($upcs, array($date1, $date2));
        $localP = $this->connection->prepare("
            SELECT COUNT(DISTINCT d.upc) AS items, SUM(total) AS ttl
            FROM " . FannieDB::fqn('sumUpcSalesByDay', 'plugin:WarehouseDatabase') . " AS d
            WHERE date_id BETWEEN ? AND ? 
                AND d.upc IN ({$inStr})
            ");
        $local = $this->connection->getRow($localP, $args);
        $local['%items'] = sprintf('%.2f', ($local['items'] / $skus['items']) * 100);
        $local['%ttl'] = sprintf('%.2f', ($local['ttl'] / $skus['ttl']) * 100);

        $upcP = $this->connection->prepare("SELECT upc FROM products WHERE (numflag & (1 << 16)) <> 0 GROUP BY upc");
        $upcs = $this->connection->getAllValues($upcP);
        list($inStr, $args) = $this->connection->safeInClause($upcs, array($date1, $date2));
        $organicP = $this->connection->prepare("
            SELECT COUNT(DISTINCT d.upc) AS items, SUM(total) AS ttl
            FROM " . FannieDB::fqn('sumUpcSalesByDay', 'plugin:WarehouseDatabase') . " AS d
            WHERE date_id BETWEEN ? AND ? 
                AND upc IN ({$inStr})
            ");
        $organic = $this->connection->getRow($organicP, $args);
        $organic['%items'] = sprintf('%.2f', ($organic['items'] / $skus['items']) * 100);
        $organic['%ttl'] = sprintf('%.2f', ($organic['ttl'] / $skus['ttl']) * 100);

        $freshP = $this->connection->prepare("
            SELECT SUM(total) AS ttl
            FROM " . FannieDB::fqn('sumDeptSalesByDay', 'plugin:WarehouseDatabase') . " AS d
                INNER JOIN MasterSuperDepts AS m ON m.dept_ID=d.department
            WHERE date_id BETWEEN ? AND ?
                AND (
                    m.superID IN (3,6,8)
                    OR
                    d.department IN (26, 27, 30, 35)
            )");
        $fresh = $this->connection->getValue($freshP, array($date1, $date2));
        $fresh = array('ttl' => $fresh, '%ttl' => sprintf('%.2f', ($fresh / $skus['ttl']) * 100));

        return <<<HTML
<p>
<b>Total SKUs</b>: {$skus['items']}
<br />
<b>Total Sales</b>: \${$skus['ttl']}
</p>
<p>
<b>Local SKUs</b>: {$local['items']} ({$local['%items']}%)
<br />
<b>Local Sales</b>: \${$local['ttl']} ({$local['%ttl']}%)
<br />
</p>
<p>
<b>Organic SKUs</b>: {$organic['items']} ({$organic['%items']}%)
<br />
<b>Organic Sales</b>: \${$organic['ttl']} ({$organic['%ttl']}%)
<br />
</p>
<p>
<b>Fresh Sales</b>: \${$fresh['ttl']} ({$fresh['%ttl']}%)
<br />
</p>
HTML;
    }

    protected function get_view()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    {$dates}
    <div class="row"></div>
    <p>
        <button class="btn btn-default" type="submit">Submit</button>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

