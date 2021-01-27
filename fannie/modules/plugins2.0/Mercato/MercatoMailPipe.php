<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('\COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}
if (!class_exists('MercatoIntake')) {
    include(__DIR__ . '/MercatoIntake.php');
}

class MercatoMailPipe extends \COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe
{
    public function processMail($msg)
    {
        $info = $this->parseEmail($msg);
        $boundary = $this->hasAttachments($info['headers']);
        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $tmp = fopen('/tmp/mcmp.log', 'w');
        fwrite($tmp, "Received Message\n");

        if ($boundary) {
            fwrite($tmp, "Has attachments\n");
            $pieces = $this->extractAttachments($info['body'], $boundary);
            foreach ($pieces['attachments'] as $a) {
                $temp = __DIR__ . '/noauto/archive/' . $a['name'];
                if (strstr($a['name'], '";')) {
                    list($a['name'],) = explode('";', $a['name'], 2);
                    $temp = __DIR__ . '/noauto/archive/' . $a['name'];
                }
                fwrite($tmp, "Checking {$a['name']}\n");
                $orig = explode('.', $a['name'], 2);
                if (count($orig) > 1) { // preserve file extension
                    $temp .= '.' . $orig[count($orig)-1];
                }
                file_put_contents($temp, $a['content']);
                if (substr($temp, -3) != 'csv') {
                    fwrite($tmp, "Discarding non csv\n");
                    unlink($temp);
                    continue;
                }

                $intake = new MercatoIntake($dbc);
                $intake->process($temp);
            }
        }
    }

}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new MercatoMailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 

