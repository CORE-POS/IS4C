<?php

class CommentSummaryTask extends FannieTask
{
    public $name = 'Comment Tracker Summary';

    public $description = 'Summarizes comments received in the last day';

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['CommentDB']);
        foreach (array('primary', 'cc') as $type) {
            $column = $type == 'primary' ? 'primaryNotified' : 'ccNotified';
            $cmP = $dbc->prepare("SELECT * FROM Comments WHERE categoryID=? AND {$column}=0");
            $upP = $dbc->prepare("UPDATE Comments SET {$column}=1 WHERE commentID=?");
            $rsP = $dbc->prepare('SELECT * FROM Responses WHERE commentID=? ORDER BY tdate');
            $query = "
                SELECT c.categoryID, t.name, t.notifyAddress, t.ccAddress,
                    COUNT(*) AS numberOfComments
                FROM Comments AS c
                    INNER JOIN Categories AS t ON c.categoryID=t.categoryID
                WHERE c.categoryID > 0
                    AND {$column}=0
                GROUP BY c.categoryID, t.name, t.notifyAddress, t.ccAddress";
            $yesterday = date('Y-m-d', strtotime('yesterday'));
            $prep = $dbc->prepare($query);
            $res = $dbc->execute($prep, array($yesterday));
            $notified = array();
            while ($row = $dbc->fetchRow($res)) {
                $mail = new PHPMailer();
                $mail->From = 'comments@wholefoods.coop';
                $mail->FromName = 'Comment Tracker';
                $mail->Subject = $row['name'] . ' Comment Summary';
                $mail->isHTML(true);
                $address = '';
                if ($type == 'primary') {
                    $address = $row['notifyAddress'];
                } else {
                    $address = $row['ccAddress'];
                }
                foreach (explode(',', $address) as $a) {
                    $mail->addAddress(trim($a));
                }
                $body = <<<HTML
<p>WFC received {$row['numberOfComments']} {$row['name']} comments</p>
HTML;
                $cmR = $dbc->execute($cmP, array($row['categoryID']));
                while ($cmW = $dbc->fetchRow($cmR)) {
                    $body .= '<hr />';
                    $body .= '<p>';
                    $body .= 'Comment from ' . $cmW['email'] . '<br />';
                    $body .= $cmW['comment'];
                    $body .= '</p>';
                    $resp = $dbc->getAllRows($rsP, array($cmW['commentID']));
                    if (count($resp) == 0) {
                        $body .= '<p><a href="http://key/git/fannie/modules/plugins2.0/CommentTracker/ManageComments.php?id='
                            . $cmW['commentID'] . '">Add Response</a></p>';
                    } else {
                        $body .= '<p>WFC Response(s)</p>';
                        foreach ($resp as $r) {
                            $body .= '<p>' . $r['response'] . '</p>';
                        }
                    }
                    $notified[] = $cmW['commentID'];
                }
                $mail->Body = $body;
                $sent = false;
                if ($address != '') {
                    $sent = $mail->send();
                }
                if ($sent || $address == '') {
                    foreach ($notified as $id) {
                        $dbc->execute($upP, array($id));
                    }
                }
            }
        }
    }
}

