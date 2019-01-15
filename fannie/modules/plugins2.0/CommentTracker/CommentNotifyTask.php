<?php

class CommentNotifyTask extends FannieTask
{
    public $name = 'Comment Tracker Notifications';
    public $log_start_stop = false;

    public $description = 'Sends notifications to primary responders';

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['CommentDB']);
        $upP = $dbc->prepare("UPDATE Comments SET primaryNotified=1 WHERE commentID=?");
        $query = "
            SELECT c.categoryID, t.name, t.notifyAddress, t.ccAddress,
                c.name as commenter, c.email, c.comment, c.commentID
            FROM Comments AS c
                INNER JOIN Categories AS t ON c.categoryID=t.categoryID
            WHERE c.categoryID > 0
                AND primaryNotified=0";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep);
        $historyP = $dbc->prepare("INSERT INTO CommentHistory (commentID, userID, tdate, log) VALUES (?, ?, ?, ?)");
        while ($row = $dbc->fetchRow($res)) {
            $mail = new PHPMailer();
            $mail->From = 'comments@wholefoods.coop';
            $mail->FromName = 'Comment Tracker';
            $mail->addReplyTo('ff8219e9ba6148408c89232465df9e53+' . $row['commentID'] . '@wholefoods.coop');
            $mail->Subject = 'New ' . $row['name'] . ' Comment Needing Response';
            $mail->isHTML(true);
            $address = $row['notifyAddress'];
            foreach (explode(',', $address) as $a) {
                $mail->addAddress(trim($a));
            }
            $row['commenter'] = trim($row['commenter']);
            if (ord(substr($row['commenter'], 0, 1)) == 0xc2 && ord(substr($row['commenter'], 1, 1)) == 0xa0) {
                $row['commenter'] = substr($row['commenter'], 2);
            }
            $row['comment'] = nl2br($row['comment']);
            $body = <<<HTML
<p>New comment from {$row['commenter']} ({$row['email']})</p>
<p>Comment:</p>
<p>{$row['comment']}</p>
<p><a href="http://key/git/fannie/modules/plugins2.0/CommentTracker/ManageComments.php?id={$row['commentID']}">Manage Comment</a></p>
<p>You can also respond by replying to this message</p>
<p>You're receiving this because you're responsible for responding to comments in this category
<b>or</b> re-assigning them to a more appropriate category.</p>
HTML;
            $mail->Body = $body;
            $sent = false;
            if ($address != '') {
                $sent = $mail->send();
            }
            if ($sent || $address == '') {
                $dbc->execute($upP, array($row['commentID']));
                $dbc->execute($historyP, array($row['commentID'], 0, date('Y-m-d H:i:s'), 'Email ' . $address));
            }
        }
    }
}

