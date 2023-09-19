<?php

class EReceiptLib
{
    static public function getReceipt($trans_num, $cardno)
    {
        $ret = self::receiptFromBuilders($trans_num);
        $ret .= '<!-- end of items -->' . "\n";
        $ret .= '<div style="text-align:center;">thank you'
            . ($cardno != 11 ? ' - owner ' . $cardno : '')
            . '</div>' . "\n";

        return $ret;
    }

    static public function sendEmail($text, $addr, $trans_num)
    {
        $mail = COREPOS\Fannie\API\data\pipes\OutgoingEmail::get();
        $subject = 'Receipt ' . date('Y-m-d') . ' ' . $trans_num;
        $message = file_get_contents(__DIR__ . '/_head.html');
        $message .= '<table border="0" cellpadding="10" cellspacing="0" width="600" id="email-container">';
        $table = true;
        /** rewrite item lines in a table; end the table at
            the end-of-items spacer and add any footers 
            as simple lines of text
        **/
        foreach (explode("\n", $text) as $line) {
            if ($table && strstr($line, '<!-- end of items -->')) {
                $message .= '</table>' . $line . '<br>';
                $table = false;
            } elseif ($table) {
                $message .= '<tr>';
                if (preg_match('/^(.*?)(-?\d+\.\d\d)(.*)$/', $line, $matches)) {
                    $message .= '<td>' . $matches[1] . '</td>';
                    $message .= '<td>' . $matches[2] . '</td>';
                    $message .= '<td>' . $matches[3] . '</td>';
                } else {
                    $message .= '<td colspan="3">' . $line . '</td>';
                }
                $message .= '</tr>' . "\n";
            } else {
                $message .= $line ."<br>\n";
            }
        }
        $message .= file_get_contents(__DIR__ . '/_foot.html');

        $mail->From = 'receipts@wholefoods.coop';
        $mail->FromName = 'Whole Foods Co-op';
        $mail->addAddress($addr);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $message;
        $mail->AltBody = $text;

        $mail->send();
    }

    static private function receiptFromBuilders($trans_num)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $fetch = new EReceiptDataFetch();
        $data = $fetch->fetch($dbc, $trans_num);
        $filter = new EReceiptFilter();
        $recordset = $filter->filter($dbc, $data);
        $sort = new EReceiptSort();
        $recordset = $sort->sort($recordset);
        $tag = new EReceiptTag();
        $recordset = $tag->tag($recordset);

        $ret = '';
        foreach ($recordset as $record) {
            $className = $record['tag'] . 'EReceiptFormat';
            if (!class_exists($className)) {
                continue;
            }
            $obj = new $className();
            
            $line = $obj->format($record);
            $ret .= $line . "\n";
        }

        return $ret;
    }

}
