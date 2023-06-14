<?php

use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Filesystem;

if (!class_exists('MercatoIntake')) {
    include(__DIR__ . '/MercatoIntake.php');
}

class MercatoImportTask extends FannieTask 
{
    public $name = 'Import Mercato transaction data';

    public $description = 'Imports Mercato transaction data via FTP';

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $intake = new MercatoIntake($dbc);

        if (class_exists('League\\Flysystem\\Sftp\\SftpAdapter')) {
            $settings = $this->config->get('PLUGIN_SETTINGS');
            $adapter = new SftpAdapter(array(
                'host' => $settings['MercatoFtpHost'],
                'username' => $settings['MercatoFtpUser'],
                'password' => $settings['MercatoFtpPw'],
                'port' => 22,
            ));
            $filesystem = new Filesystem($adapter);
            $contents = $filesystem->listContents('outbound/orders');
            $chkP = $dbc->prepare("SELECT hash FROM MercatoHashes WHERE hash=?");
            $insP = $dbc->prepare("INSERT INTO MercatoHashes (hash) VALUES (?)");
            foreach ($contents as $c) {
                if (isset($c['extension']) && $c['extension'] == 'csv') {
                    $filename = __DIR__ . '/noauto/archive/' . $c['basename'];
                    if (file_exists($filename)) {
                        continue; // already imported
                    }
                    $csv = $filesystem->read($c['path']);
                    file_put_contents($filename, $csv);
                    $output = array();
                    exec('sha1sum ' . escapeshellarg($filename), $output);
                    if (!isset($output[0])) {
                        $this->cronMsg("Could not obtain hash for " . basename($filename) . ", skipping for now", FannieLogger::ALERT);
                        continue;
                    }
                    list($hash,) = explode(' ', $output[0], 2);
                    $chkR = $dbc->execute($chkP, array($hash));
                    if ($dbc->numRows($chkR) > 0) {
                        $this->cronMsg("Appears to be a duplicate: " . basename($filename) . ", skipping for now", FannieLogger::ALERT);
                        continue;
                    }
                    $dbc->execute($insP, array($hash));
                    echo "Processing {$c['basename']}\n";
                    $intake->shift();
                    $intake->process($filename);
                }
            }
        }
    }
}

