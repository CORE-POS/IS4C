<?php

namespace COREPOS\Fannie\Plugin\IncidentTracker\notifiers;
use \FannieDB;
use \FannieConfig;

class AlertDigest
{
    public function send($incident, $address)
    {
        if (!class_exists('PHPMailer')) {
            // can't send
            return false;
        }
        $mail = new \PHPMailer();
        $mail->From = 'alerts@wholefoods.coop';
        $mail->FromName = 'Alerts Digest';
        foreach (explode(',', $address) as $a) {
            $mail->addAddress(trim($a));
        }
        $mail->Subject = 'WFC Incident Digest';

        $today = date('Y-m-d');
        $start = date('Y-m-d', strtotime('6 days ago'));
        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['IncidentDB']);

        $prep = $dbc->prepare("
            SELECT i.*,
                COALESCE(s.incidentSubType, 'Other') AS incidentSubType
            FROM Incidents AS i
                LEFT JOIN IncidentSubTypes AS s ON i.incidentSubTypeID=s.incidentSubTypeID
            WHERE i.tdate BETWEEN ? AND ?
                AND i.incidentTypeID=1
                AND i.deleted=0
            ORDER BY tdate");
        $res = $dbc->execute($prep, array($start . ' 00:00:00', $today . ' 23:59:59'));
        $incidents = array();
        $types = array();
        while ($row = $dbc->fetchRow($res)) {
            $subtype = $row['incidentSubType'];
            if (!isset($types[$subtype])) {
                $types[$subtype] = 0;
            }
            $types[$subtype] += 1;
            $incidents[] = $row;
        }
        if (count($incidents) == 0) {
            return false;
        }

        $msg = "Summary for {$start} through {$today}\n";
        $msg .= "\n";
        foreach ($types as $name => $count) {
            $msg .= str_pad($count, 3) . ' ' . $name . "\n";
        }
        $msg .= "\n";
        $msg .= "-----------------\n";
        $msg .= "\n";
        foreach ($incidents as $i) {
            $tstamp = strtotime($i['tdate']);
            $i['tdate'] = date('D, M jS h:ia', $tstamp);
            $msg .= "{$i['tdate']} - {$i['incidentSubType']}\n\n";
            $msg .= substr($i['details'], 0, 500) . "...\n\n";
            $msg .= 'More: http://' . $config->get('HTTP_HOST') . $config->get('URL')
                . 'modules/plugins2.0/IncidentTracker/AlertIncident.php?id='
                . $i['incidentID']
                . "\n\n";
            $msg .= "-----------------\n";
            $msg .= "\n";
        }
        $mail->Body = $msg;
        $ret = $mail->send();

        return $ret ? true : false;
    }
}

