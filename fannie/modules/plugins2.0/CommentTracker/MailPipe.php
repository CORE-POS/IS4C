<?php

namespace COREPOS\Fannie\Plugin\CommentTracker;
use COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe;

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('\COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}
if (!class_exists('\\CommentsModel')) {
    include(__DIR__ . '/models/CommentsModel.php');
}

class MailPipe extends AttachmentEmailPipe
{
    public function processMail($msg)
    {
        $info = $this->parseEmail($msg);
        $boundary = $this->hasAttachments($info['headers']);
        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $log = new \FannieLogger();

        $body = strip_tags($info['body']);

        $location = $this->getValue('Location_', $body);
        $email = $this->getValue('Email_', $body);
        $publish = $this->getValue('Publish', $body);
        $comment = explode('Comment_:', $body, 2);
        $comment = trim($comment[1]);

        $settings = \FannieConfig::config('PLUGIN_SETTINGS');
        $dbc = \FannieDB::get($settings['CommentDB']);

        $catP = $dbc->prepare('SELECT categoryID FROM Categories WHERE name=?');
        $catID = $dbc->getValue($catP, array($location));

        $model = new \CommentsModel($dbc);
        $model->categoryID($catID);
        $model->publishable(trim($publish) === 'Yes' ? 1 : 0);
        $model->email($email);
        $model->comment($comment);
        $model->tdate(date('Y-m-d H:i:s'));
        $model->save();
    }

    private function getValue($field, $str)
    {
        $found = preg_match('/' . $field . ':(.+)/', $str, $matches);
        if ($found) {
            return trim($matches[1]); 
        }

        return false;
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new MailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 

