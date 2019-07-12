<?php

use COREPOS\Fannie\API\data\FileData;

class WicMnAplTask extends FannieTask
{
    private $URL = 'https://www.health.state.mn.us/docs/people/wic/vendor/fpchng/upc/apl.xlsx';

    private function download($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $filename = tempnam(sys_get_temp_dir(), 'apl');
        $file = fopen($filename, 'w');
        curl_setopt($curl, CURLOPT_FILE, $file);
        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        fclose($file);
        if ($code != 200) {
            if (file_exists($file)) {
                unlink($file);
            }
            $this->cronMsg('Failed to download MN APL - ' . $code, FannieLogger::ALERT);
            exit;
        }

        return $filename;
    }

    public function run()
    {
        $xlsx = $this->download($this->URL);
        $data = FileData::xlsxToArray($xlsx);
        if ($data === false || count($data) === 0) {
            $this->cronMsg('MN APL file is empty or damaged', FannieLogger::ALERT);
            return false;
        }

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $addItem = $dbc->prepare('INSERT INTO EWicItems (upc, upcCheck, eWicCategoryID, eWicSubCategoryID) VALUES (?, ?, ?, ?)');

        $count = 0;
        $dbc->query("DROP TABLE IF EXISTS EWicBackup");
        $dbc->query("CREATE TABLE EWicBackup LIKE EWicItems");
        $dbc->query("INSERT INTO EWicBackup SELECT * FROM EWicItems");
        $dbc->query("TRUNCATE TABLE EWicItems");
        $dbc->startTransaction();
        foreach ($data as $line) {

            $upc = trim($line[2]);
            $catID = trim($line[4]);
            $catName = trim($line[5]);
            $subCatID = trim($line[6]);
            $subCatName = trim($line[7]);

            $upc = str_replace(" ","",$upc);
            $upc = str_replace("-","",$upc);
            if (!is_numeric($upc)) continue; // skip header(s) or blank rows
            $ourUPC = BarcodeLib::padUPC(substr($upc, 0, strlen($upc)-1));

            $dbc->execute($addItem, array($ourUPC, $upc, $catID, $subCatID));

            $count++;
            if ($count % 1000 == 0) {
                $dbc->commitTransaction();
                $dbc->startTransaction();
            }
        }
        $dbc->commitTransaction();
        if ($count < 50) {
            $this->cronMsg('Problems encountered updating APL; rolling back to previous', FannieLogger::ALERT);
            $dbc->query("TRUNCATE TABLE EWicItems");
            $dbc->query("INSERT INTO EWicItems SELECT * FROM EWicBackup");
        } else {
            $dbc->query("INSERT INTO EWicItems
                (upc, upcCheck, alias, eWicCategoryID, eWicSubCategoryID)
                SELECT a.upc, i.upcCheck, i.upc, i.eWicCategoryID, i.eWicSubCategoryID
                FROM EWicAliases AS a
                    INNER JOIN EWicItems AS i ON a.aliasedUPC=i.upc");
        }
        $dbc->query("DROP TABLE IF EXISTS EWicBackup");

        unlink($xlsx);

        $this->cronMsg("Reloaded MN APL with {$count} items");
    }
}

