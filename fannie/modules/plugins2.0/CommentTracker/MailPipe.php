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
    /**
     * Form sends text as quoted-printable.
     * Decode it.
     */
    private function deQuote($str)
    {
        // line wrapping
        $str = str_replace("=\n", "", $str);

        // fancy apostrophe
        $str = str_replace("=E2=80=99", "'", $str);

        // non-breaking space
        $str = str_replace("=C2=A0", " ", $str);

        return $str;
    }

    public function processMail($msg)
    {
        $info = $this->parseEmail($msg);
        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $log = new \FannieLogger();

        $body = strip_tags($info['body']);

        $location = $this->getValue('Location_', $body);
        $email = $this->getValue('Email_', $body);
        $name = $this->getValue('Name_', $body);
        $comment = explode('Comment_:', $body, 2);
        $comment = trim($comment[1]);
        $comment = $this->deQuote($comment);

        $settings = \FannieConfig::config('PLUGIN_SETTINGS');
        $dbc = \FannieDB::get($settings['CommentDB']);

        $catP = $dbc->prepare('SELECT categoryID, name, notifyMethod, notifyAddress FROM Categories WHERE name=?');
        $catW = $dbc->getRow($catP, array($location));

        $model = new \CommentsModel($dbc);
        $model->categoryID($catW['categoryID']);
        $model->publishable(1);
        $model->name($name);
        $model->email($email);
        $model->comment($comment);
        $model->tdate(date('Y-m-d H:i:s'));
        $commentID = $model->save();

        if ($catW && $catW['notifyAddress']) {
            $mail = new \PHPMailer();
            $mail->addReplyTo('ff8219e9ba6148408c89232465df9e53+' . $commentID . '@wholefoods.coop');
            $mail->setFrom('comments@wholefoods.coop', 'Comment Tracker');
            $mail->Subject = 'New Comment Needing Response';
            $mail->addAddress($catW['notifyAddress']);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $htmlComment = nl2br($comment);
            $mail->Body = <<<HTML
<p>New {$catW['name']} comment received from {$email}</p>
<p>Comment:</p>
<p>$htmlComment</p>
<p><a href="http://key/git/fannie/modules/plugins2.0/CommentTracker/ManageComments.php?id={$commentID}">Manage Comment</a></p>
<p>You can also respond by replying to this message</p>
<p>You're receiving this because you're responsible for responding to comments in this category
<b>or</b> re-assigning them to a more appropriate category.</p>
HTML;
            $sent = $mail->send();
            if ($sent) {
                $prep = $dbc->prepare('UPDATE Comments SET primaryNotified=1 WHERE commentID=?');
                $dbc->execute($prep, array($commentID));
            }
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

