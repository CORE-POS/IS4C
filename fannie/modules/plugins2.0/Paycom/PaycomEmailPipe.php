<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__.'/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('\COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe')) {
    include_once(__DIR__.'/../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}
/**
*/
class PaycomEmailPipe extends \COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe
{
    public function processMail($msg)
    {
        $log = fopen('/tmp/pcemp', 'a');
        $info = $this->parseEmail($msg);
        $boundary = $this->hasAttachments($info['headers']);
        
        if ($boundary) {
            $pieces = $this->extractAttachments($info['body'], $boundary);
            fwrite($log, "Attachments: " . count($pieces['attachments']) . "\n");
            foreach($pieces['attachments'] as $a) {
                fwrite($log, "File: {$a['name']}\n");
                fwrite($log, "Mime-type: {$a['type']}\n");
                $fp = fopen(__DIR__ . '/noauto/queue/' . $a['name'], 'w');
                if ($fp === false) {
                    fwrite($log, 'File open failed' . "\n");
                    continue;
                }
                fwrite($fp, $a['content']);
                fclose($fp);
                fwrite($log, 'Wrote file ' . __DIR__ . '/noauto/queue/' . $a['name'] . "\n");
                chmod(__DIR__ . '/noauto/queue/' . $a['name'], 0666);
            }
        }
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new PaycomEmailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 
