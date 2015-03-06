<?php

class EmailLogParseTask extends FannieTask
{

    public function run()
    {
        $file_format = array(
            'OpenWebMail' => '/var/log/openwebmail.log',
            'WFC' => '/var/log/mail.log',
        );
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['EmailReportingDB']);

        foreach ($file_format as $format => $file) {
            if (file_exists($file) && is_readable($file)) {
                $fp = fopen($file, 'r');
                $method = 'parse' . $format;
                $this->$method($fp, $dbc);
            }
        }
    }

    public function parseOpenWebMail($fp, $dbc)
    {
        $limit = '
            SELECT MAX(tdate) AS tdate
            FROM EmailUsageLog
            WHERE client=\'OpenWebMail\'';
        $res = $dbc->query($limit);
        $limit_dt = new DateTime('1900-01-01');
        if ($res && $dbc->numRows($res)) {
            $row = $dbc->fetchRow($res);
            if ($row['tdate'] != '') {
                $limit_dt = new DateTime($row['tdate']);
            }
        }

        $logP = $dbc->prepare('
            INSERT INTO EmailUsageLog
            (tdate, username, action, client)
            VALUES (?, ?, ?, ?)');
        $pattern = '/^(.+) - \[\d+\] \([0-9\.]+\) (\w+) - ([\w ]+) - .*$/S';

        while (($line=fgets($fp)) !== false) {
            if (preg_match($pattern, $line, $matches)) {
                $name = $matches[2];
                $action = $matches[3];
                if ($action == 'move message' || $action == 'delete message' || substr($action, 0, 7) == 'session') {
                    continue;
                }
                $line_dt = new DateTime($matches[1]);
                if ($line_dt > $limit_dt) {
                    $args = array(
                        $line_dt->format('Y-m-d H:i:s'),
                        $name,
                        $action,
                        'OpenWebMail'
                    );
                    $dbc->execute($logP, $args);
                }
            } 
        }
    }

    public function parseWFC($fp, $dbc)
    {
        $limit = '
            SELECT MAX(tdate) AS tdate
            FROM EmailUsageLog
            WHERE client=\'syslog\'';
        $res = $dbc->query($limit);
        $limit_dt = new DateTime('1900-01-01');
        if ($res && $dbc->numRows($res)) {
            $row = $dbc->fetchRow($res);
            if ($row['tdate'] != '') {
                $limit_dt = new DateTime($row['tdate']);
            }
        }

        $logP = $dbc->prepare('
            INSERT INTO EmailUsageLog
            (tdate, username, action, client)
            VALUES (?, ?, ?, ?)');
        $login_pattern = '/^(\w+\s+\d+ \d\d:\d\d:\d\d) \w+ dovecot: imap-login: Login: user=<(\w+)>.*$/S';
        $send_pattern = '/^(\w+\s+\d+ \d\d:\d\d:\d\d) \w+ postfix.*sasl_method=LOGIN, sasl_username=(\w+).*$/S';

        $prev_name = false;
        $prev_action = false;
        $lastLogins = array();
        while (($line=fgets($fp)) !== false) {
            $line_dt = false;
            $action = false;
            $name = false;
            if (preg_match($login_pattern, $line, $matches)) {
                $name = $matches[2];
                $line_dt = new DateTime($matches[1]);
                $action = 'login';
            } elseif (preg_match($send_pattern, $line, $matches)) {
                $name = $matches[2];
                $line_dt = new DateTime($matches[1]);
                $action = 'send';
            }

            if ($action) {

                if ($action == 'login') {
                    if (!isset($lastLogins[$name])) {
                        $lastLogins[$name] = new DateTime('1900-01-01');
                    }
                }

                if ($action == 'login' && (($line_dt->getTimestamp() - $lastLogins[$name]->getTimestamp())/3600.00) < 2) {
                    // skip noisy imap login activity
                } elseif ($line_dt > $limit_dt) {
                    $args = array(
                        $line_dt->format('Y-m-d H:i:s'),
                        $name,
                        $action,
                        'syslog',
                    );
                    $dbc->execute($logP, $args);
                    if ($action == 'login') {
                        $lastLogins[$name] = $line_dt;
                    }
                }
            }
        }
    }
}

