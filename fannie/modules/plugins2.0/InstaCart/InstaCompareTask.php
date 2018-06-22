<?php

class InstaCompareTask extends FannieTask 
{
    public $name = 'Compare InstaCart data';

    public $description = 'Update comparison pricing data from InstaCart';

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['InstaCartDB']);

        $stale = date('Y-m-d', strtotime('7 days ago'));
        $prep = $dbc->prepare('SELECT url FROM InstaCompares WHERE modified < ?');
        $urls = array();
        $res = $dbc->execute($prep, array($stale));
        while ($row = $dbc->fetchRow($res)) {
            $urls[] = $row['url'];
            if (count($urls) >= 10) {
                break;
            }
        }

        $user = $settings['InstaCartCompUser'];
        $pass = $settings['InstaCartCompPw'];
        $dsn = 'mysql://' 
            . $this->config->get('SERVER_USER') . ':'
            . $this->config->get('SERVER_PW') . '@'
            . $this->config->get('SERVER') . '/'
            . $settings['InstaCartDB'];

        $cmd = __DIR__ . '/noauto/scrape.py'
            . ' ' . escapeshellarg('-v')
            . ' ' . escapeshellarg('-u')
            . ' ' . escapeshellarg($user)
            . ' ' . escapeshellarg('-p')
            . ' ' . escapeshellarg($pass)
            . ' ' . escapeshellarg('-d')
            . ' ' . escapeshellarg($dsn);
        foreach ($urls as $u) {
            $cmd .= ' ' . escapeshellarg($u);
        }

        echo $cmd . "\n";
        $ret = exec($cmd, $output);
        echo implode("\n", $output) . "\n";
    }
}

