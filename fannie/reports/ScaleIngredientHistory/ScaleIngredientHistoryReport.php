<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class ScaleIngredientHistoryReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Scale Ingredient Edits";
    protected $header = "Scale Ingredient Edits";
    public $discoverable = false;

    protected $required_fields = array('upc');
    protected $report_headers = array('Date & Time', 'User', 'Store ID', 'Ingredients');
    protected $sort_direction = 1;

    public function fetch_report_data()
    {
        $prep = $this->connection->prepare("SELECT s.tdate, u.name, s.storeID, s.ingredients
            FROM ScaleIngredientHistory AS s
                LEFT JOIN Users AS u ON s.userID=u.uid
            WHERE s.upc=?
            ORDER BY s.tdate DESC");
        $res= $this->connection->execute($prep, array($this->form->upc));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['tdate'],
                $row['name'],
                $row['storeID'],
                $row['ingredients'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '<div class="alert alert-error">Access this via Item Editor</div>';
    }
}

FannieDispatch::conditionalExec();

