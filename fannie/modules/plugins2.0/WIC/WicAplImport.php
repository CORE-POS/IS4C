<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class WicAplImport extends FannieRESTfulPage
{
    protected $header = 'Import APL File';
    protected $title = 'Import APL File';

    protected function post_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $fp = fopen($_FILES['apl']['tmp_name'], 'r');
        if (!$fp) {
            var_dump($_FILES);
            return '<div class="alert alert-danger">Could not open file</div>';
        }
        $headers = fgets($fp);
        $dbc->query("DROP TABLE IF EXISTS EWicBackup");
        $dbc->query("CREATE TABLE EWicBackup LIKE EWicItems");
        $dbc->query("INSERT INTO EWicBackup SELECT * FROM EWicItems");
        $dbc->query("TRUNCATE TABLE EWicItems");
        $addItem = $dbc->prepare('INSERT INTO EWicItems (upc, upcCheck, eWicCategoryID, eWicSubCategoryID) VALUES (?, ?, ?, ?)');
        $catP = $dbc->prepare("UPDATE EWicCategories SET units=? WHERE eWicCategoryID=?");
        $subP = $dbc->prepare("UPDATE EWicSubCategories SET units=? WHERE eWicCategoryID=? AND eWicSubCategoryID=?");
        $dbc->startTransaction();
        while (!feof($fp)) {
            $line = fgets($fp);
            $upc = substr($line, 12, 17);
            if (!is_numeric($upc)) continue;
            $upc = BarcodeLib::padUPC(substr($upc, 0, strlen($upc)-1));
            $upcCheck = substr($line, 12, 17);
            if ($upcCheck[0] == '0') {
                $upcCheck = substr($upcCheck, -12);
            }
            $item = rtrim(substr($line, 29, 50));
            $catID = substr($line, 79, 2);
            $cat = substr($line, 81, 50);
            $subID = substr($line, 131, 4);
            $sub = rtrim(substr($line, 134, 50));
            $unit = substr($line, 184, 3);
            $dbc->execute($addItem, array($upc, $upcCheck, $catID, $subID));
            $dbc->execute($catP, array($unit, ltrim($catID, '0')));
            $dbc->execute($subP, array($unit, ltrim($catID, '0'), ltrim($subID, '0')));
        }
        $dbc->commitTransaction();
        $dbc->query("INSERT INTO EWicItems
            (upc, upcCheck, alias, eWicCategoryID, eWicSubCategoryID)
            SELECT a.upc, i.upcCheck, i.upc, i.eWicCategoryID, i.eWicSubCategoryID
            FROM EWicAliases AS a
                INNER JOIN EWicItems AS i ON a.aliasedUPC=i.upc");
    }

    protected function get_view()
    {
        return <<<HTML
<form method="post" action="WicAplImport.php" enctype = "multipart/form-data">
    <input type="file" class="form-control" name="apl" />
    <div class="form-group">
        <button type="submit" class="btn btn-default">Upload APL</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

