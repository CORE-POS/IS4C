<?php

if (!class_exists('Gohanman\\Otto\\Otto')) {
    include(__DIR__ . '/noauto/Otto.php');
}
if (!class_exists('Gohanman\\Otto\\Message')) {
    include(__DIR__ . '/noauto/Message.php');
}

class OttoMem
{
    public function post($cardNo, $source)
    {
        $settings = FannieConfig::config('PLUGIN_SETTINGS');
        $url = $settings['OttoMemUrl'];

        $otto = new Gohanman\Otto\Otto($url);
        $msg = new Gohanman\Otto\Message();
        $msg->title("New Owner Joined");

        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $prep = $dbc->prepare("SELECT FirstName, LastName FROM custdata WHERE CardNo=? AND personNum=1");
        $row = $dbc->getRow($prep, array($cardNo));
        if ($row !== false) {
            $body = 'Owner #' . $cardNo . ' ' . $row['FirstName'] . ' ' . substr(trim($row['LastName']), 0, 1) . '.' . "\n\n";
            $body .= 'Joined via ' . $source . "\n\n";

            $msg->body($body);
            var_dump($otto->post($msg));
        }
    }
}
