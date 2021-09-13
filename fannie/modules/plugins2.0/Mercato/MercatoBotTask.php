<?php

class MercatoBotTask extends FannieTask 
{
    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');

        $user = $settings['MercatoBotUser'];
        $pass = $settings['MercatoBotPw'];
        $dsn = 'mysql://' 
            . $this->config->get('SERVER_USER') . ':'
            . $this->config->get('SERVER_PW') . '@'
            . $this->config->get('SERVER') . '/'
            . $this->config->get('OP_DB');

        chdir(__DIR__ . '/noauto');
        $cmd = './mercato.py'
            . ' ' . escapeshellarg('-u')
            . ' ' . escapeshellarg($user)
            . ' ' . escapeshellarg('-p')
            . ' ' . escapeshellarg($pass)
            . ' ' . escapeshellarg('-d')
            . ' ' . escapeshellarg($dsn);

        $ret = exec($cmd, $output);
        echo implode("\n", $output) . "\n";

        if ($ret != 0) {
            $this->cronMsg("Mercato Bot errored\n" . implode("\n", $output) . "\n", FannieLogger::ALERT);
        }
    }
}

