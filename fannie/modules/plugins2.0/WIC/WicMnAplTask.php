<?php

use COREPOS\Fannie\API\data\FileData;

use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Filesystem;

class WicMnAplTask extends FannieTask
{
    private $URL = 'https://www.health.state.mn.us/docs/people/wic/vendor/fpchng/upc/apl.xlsx';

    private function download($url)
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $url = $settings['WicAplURL'];
        $port = 22;
        if (strstr($url, ':')) {
            list($url, $port) = explode(':', $url, 2);
        }
        $adapter = new SftpAdapter(array(
            'host' => $url,
            'port' => $port,
            'username' => $settings['WicAplUser'],
            'password' => $settings['WicAplPass'],
        ));
        $filesystem = new Filesystem($adapter);
        $contents = $filesystem->listContents('.', false);
        foreach ($contents as $c) {
            if ($c['extension'] == 'apl' && substr($c['filename'], -3) == '04b') {
                $apl = $filesystem->read($c['path']);
                $tempfile = tempnam(sys_get_temp_dir(), 'apl');
                file_put_contents($tempfile, $apl);

                return $tempfile;
            }
        }

        return false;
        /*
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
         */
    }

    public function run()
    {
        $aplfile = $this->download($this->URL);
        if ($aplfile === false) {
            $this->cronMsg('MN APL file is empty or damaged', FannieLogger::ALERT);
            return false;
        }

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $addItem = $dbc->prepare('INSERT INTO EWicItems (upc, upcCheck, eWicCategoryID, eWicSubCategoryID, broadband) VALUES (?, ?, ?, ?, ?)');

        $count = 0;
        $dbc->query("DROP TABLE IF EXISTS EWicBackup");
        $dbc->query("CREATE TABLE EWicBackup LIKE EWicItems");
        $dbc->query("INSERT INTO EWicBackup SELECT * FROM EWicItems");
        $dbc->query("TRUNCATE TABLE EWicItems");
        $dbc->startTransaction();
        $fp = fopen($aplfile, 'r');
        while (($line = fgets($fp)) !== false) {

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

            $end = trim(substr($line, -21));
            $broadband = substr($end, -1);
            $startTS = mktime(0, 0, 0, substr($end, 4, 2), substr($end, 6, 2), substr($end, 0, 4));
            $endTS = mktime(0, 0, 0, substr($end, 12, 2), substr($end, 14, 2), substr($end, 8, 4));
            $now = time();

            if ($now < $startTS || $now > $endTS) {
                // not currently valid
            } else {
                $dbc->execute($addItem, array($upc, $upcCheck, $catID, $subID, $broadband));
            }


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

        unlink($aplfile);

        $this->cronMsg("Reloaded MN APL with {$count} items");
    }
}

