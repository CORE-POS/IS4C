<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class WicAplReport extends FannieReportPage
{
    protected $title = "Fannie :: WIC APL Items";
    protected $header = "Fannie :: WIC APL Items";
    public $description = '[WIC APL Items] shows stocked items that are on the approved product list';

    protected $report_headers = array('UPC', 'Brand', 'Description', 'WIC Category', 'Mapped');
    protected $new_tablesorter = true;
    protected $sort_column = 3;

    public function report_description_content()
    {
        return array(
            'Sold since ' . date('F j, Y', strtotime('90 days ago')),
        );
    }

    public function fetch_report_data()
    {
        $query = "SELECT i.upc, p.brand, p.description, c.name AS category,
                CASE WHEN MAX(i.alias) IS NULL THEN 'NO' ELSE 'YES' END AS mapped
            FROM EWicItems AS i
                INNER JOIN products AS p ON i.upc=p.upc
                LEFT JOIN EWicCategories AS c ON i.eWicCategoryID=c.eWicCategoryID
            GROUP BY i.upc, p.brand, p.description, c.name
            HAVING MAX(p.last_sold) >= ? OR MAX(p.last_sold) IS NULL";
        $cutoff = date('Y-m-d', strtotime('90 days ago'));
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($cutoff));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['category'],
                $row['mapped'],
            );
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

