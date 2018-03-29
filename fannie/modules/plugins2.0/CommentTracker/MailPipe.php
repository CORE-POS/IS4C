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

        $catP = $dbc->prepare('SELECT categoryID, name, notifyMethod, notifyAddress FROM Categories WHERE name=?');
        $catW = $dbc->getRow($catP, array($location));

        $model = new \CommentsModel($dbc);
        $model->categoryID($catW['categoryID']);
        $model->publishable(trim($publish) === 'Yes' ? 1 : 0);
        $model->email($email);
        $model->comment($comment);
        $model->tdate(date('Y-m-d H:i:s'));
        $commentID = $model->save();

        if ($catW && $catW['notifyAddress']) {
            $mail = new \PHPMailer();
            $mail->From = 'comments@wholefoods.coop';
            $mail->FromName = 'Comment Tracker';
            $mail->Subject = 'New Comment';
            $mail->addAddress($catW['notifyAddress']);
            $mail->isHTML(true);
            $mail->Body = <<<HTML
<p>New {$catW['name']} comment received from {$email}</p>
<p>Comment: $comment</p>
<p><a href="http://key/git/fannie/modules/plugins2.0/CommentTracker/ManageComments.php?id={$commentID}">Manage Comment</a></p>
HTML;
            $mail->send();
        }
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

