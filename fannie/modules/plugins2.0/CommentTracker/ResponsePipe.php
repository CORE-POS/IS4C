<?php

namespace COREPOS\Fannie\Plugin\CommentTracker;
use COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe;
use COREPOS\Fannie\API\data\pipes\OutgoingEmail;

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('\COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}
if (!class_exists('\\ResponsesModel')) {
    include(__DIR__ . '/models/ResponsesModel.php');
}

class ResponsePipe extends AttachmentEmailPipe
{
    public function processMail($msg)
    {
        $fp = fopen('/tmp/resp.log', 'a');
        $info = $this->parseEmail($msg);
        $commentID = false;
        $to = false;
        $from = false;
        foreach ($info['headers'] as $name => $value) {
            if (strtolower($name) == 'to') {
                $to = trim($value);
            } elseif (strtolower($name) == 'from') {
                $from = trim($value);
            } 
        }
        if (preg_match('/(\d+)@/', $to, $matches)) {
            $commentID = $matches[1];
        }
        if ($commentID === false) {
            return false;
        }

        $body = $info['body'];
        $boundary = $this->hasAttachments($info['headers']);
        if ($boundary) {
            $tmp = $this->extractAttachments($body, $boundary); 
            $body = $tmp['body'];
        }
        $reply = '';
        foreach (explode("\n", $body) as $line) {
            if (strstr($reply, 'Manage Comments')) {
                continue;
            } elseif (strstr($reply, 'key/git')) {
                continue;
            }
            $reply .= $line . "\n";
        }
        if ($reply === '') {
            return false;
        }
        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $log = new \FannieLogger();

        $userP = $dbc->prepare('SELECT uid FROM Users WHERE email=?');
        $uid = $dbc->getValue($userP, array($from));

        $settings = \FannieConfig::config('PLUGIN_SETTINGS');
        $dbc = \FannieDB::get($settings['CommentDB']);
        $commentP = $dbc->prepare('SELECT * FROM Comments WHERE commentID=?');
        $comment = $dbc->getRow($commentP, array($commentID));
        if ($comment == false) {
            return false;
        }

        $resp = new \ResponsesModel($dbc);
        $resp->commentID($commentID);
        $resp->userID($uid);
        $resp->response($reply);
        $resp->tdate(date('Y-m-d H:i:s'));
        if (filter_var($comment['email'], FILTER_VALIDATE_EMAIL)) {
            $orig = explode("\n", $comment['comment']);
            $orig = array_map(function ($i) { return '> ' . $i; }, $orig);
            $orig = implode("\n", $orig);
            $orig = html_entity_decode($orig, ENT_QUOTES | ENT_HTML401);
            $resp->sent(1);
            $mail = OutgoingEmail::get();
            $mail->From = 'info@wholefoods.coop';
            if ($from) {
                $mail->From = $from;
            }
            $mail->FromName = 'Whole Foods Co-op';
            $mail->addAddress($comment['email']);
            $mail->Subject = 'WFC Comment Response';
            $mail->CharSet = 'utf-8';
            $mail->Body = $reply . "\n\n" . $orig
                . "\n\n--\nWhole Foods Co-op\n218-728-0884\ninfo@wholefoods.coop\n";
            $mail->send();
        }
        $resp->save();
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
    $obj = new ResponsePipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 

