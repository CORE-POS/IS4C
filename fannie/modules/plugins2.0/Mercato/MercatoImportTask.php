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
            foreach ($contents as $c) {
                if ($c['extension'] == 'csv') {
                    $filename = __DIR__ . '/noauto/archive/' . $c['basename'];
                    if (file_exists($filename)) {
                        continue; // already imported
                    }
                    $csv = $filesystem->read($c['path']);
                    file_put_contents($filename, $csv);
                    echo "Processing {$c['basename']}\n";
                    $intake->shift();
                    $intake->process($filename);
                }
            }
        }
    }
}

