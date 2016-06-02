<?php

namespace COREPOS\Fannie\Plugin\CpwInvoiceImport;

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('\COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}

class MailPipe extends \COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe
{
    public function processMail($msg)
    {
        $info = $this->parseEmail($msg);
        $boundary = $this->hasAttachments($info['headers']);
        $fto = new FileToOrder();
        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $otc = new OrderToCore($dbc);
        $log = new \FannieLogger();

        if ($boundary) {
            $pieces = $this->extractAttachments($info['body'], $boundary);
            foreach ($pieces['attachments'] as $a) {
                $log->debug('Checking attachment ' . $a['name']);
                $temp = tempnam(sys_get_temp_dir(), 'cpw');
                $orig = explode('.', $a['name']);
                if (count($orig) > 1) { // preserve file extension
                    $temp .= '.' . $orig[count($orig)-1];
                }
                file_put_contents($temp, $a['content']);
                try {
                    $order = $fto->read($temp);
                    $otc->import($order); 
                } catch (\Exception $ex) {
                    $log->debug('Error: ' . $ex->getMessage());
                }
                unlink($temp);
            }
        } else {
            $log->debug('Message had zero attachments');
        }
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new MailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 

