<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class WicAplImport extends FannieRESTfulPage
{
    protected $header = 'Import APL File';
    protected $title = 'Import APL File';

    private $stats = array(
        'imported' => 0,
        'skipped' => 0,
    );

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
        $addItem = $dbc->prepare('INSERT INTO EWicItems (upc, upcCheck, eWicCategoryID, eWicSubCategoryID, broadband, multiplier) VALUES (?, ?, ?, ?, ?, ?)');
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
            $mult = substr($line, 197, 7);
            $mult = ltrim($mult, '0');
            $mult /= 100;

            $end = trim(substr($line, -21));
            $broadband = substr($end, -1);
            $startTS = mktime(0, 0, 0, substr($end, 4, 2), substr($end, 6, 2), substr($end, 0, 4));
            $endTS = mktime(0, 0, 0, substr($end, 12, 2), substr($end, 14, 2), substr($end, 8, 4));
            $now = time();
            /*
            var_dump($end);
            var_dump($broadband);
            var_dump(date('Y-m-d', $startTS));
            var_dump(date('Y-m-d', $endTS));
            break;
            */
            if ($now < $startTS || $now > $endTS) {
                $this->stats['skipped']++;
            } else {
                $dbc->execute($addItem, array($upc, $upcCheck, $catID, $subID, $broadband, $mult));
                $dbc->execute($catP, array($unit, ltrim($catID, '0')));
                $dbc->execute($subP, array($unit, ltrim($catID, '0'), ltrim($subID, '0')));
                $this->stats['imported']++;
            }
        }
        $dbc->commitTransaction();
        $dbc->query("INSERT INTO EWicItems
            (upc, upcCheck, alias, eWicCategoryID, eWicSubCategoryID)
            SELECT a.upc, i.upcCheck, i.upc, i.eWicCategoryID, i.eWicSubCategoryID
            FROM EWicAliases AS a
                INNER JOIN EWicItems AS i ON a.aliasedUPC=i.upc");

        return <<<HTML
<p>
Records imported: {$this->stats['imported']}<br />
Records skipped: {$this->stats['skipped']}<br />
</p>
HTML;
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

