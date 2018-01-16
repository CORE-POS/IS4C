<?php

use COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe;
use COREPOS\common\ErrorHandler;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('COREPOS\\Fannie\\API\\data\\pipes\\AttachmentEmailPipe')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}

/**
 * @class MySpoMailPipe
 *
 * This class simply receives incoming email addresses from Postfix.
 * On receiving any message it triggers the cron task that will
 * import new, pending re-orders from the website. This should allow
 * orders to appear near-instantly internally without constant polling
 * or permantent connectivity between internal & the website
 */
class MySpoMailPipe extends AttachmentEmailPipe
{
    public function processMail($msg)
    {
        $config = FannieConfig::factory();
        $logger = FannieLogger::factory();
        COREPOS\common\ErrorHandler::setLogger($logger);
        COREPOS\common\ErrorHandler::setErrorHandlers();
        $task = new MyWebSpoImport();
        $task->setConfig($config);
        $task->setLogger($logger);
        $task->run();
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new MySpoMailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 

