<?php

namespace COREPOS\pos\lib\PrintHandlers;
use \CoreLocal;
use \PHPMailer;

/**
 @class EmailPrintHandler

 Distribute receipt via email

 Most methods are not implemented
 because they have no purpose in
 a non-physical receipt
*/

class HtmlEmailPrintHandler extends EmailPrintHandler 
{
    public function centerString($text)
    {
        return '<div style="text-align:center">' . $text . '</div>';
    }
    
    /**
      Write output to device
      @param the output string
    */
    public function writeLine($text, $to=false)
    {
        $text = substr($text,0,strlen($text)-2);
        if (CoreLocal::get("print") != 0 && $to !== false) {

            $subject = "Receipt ".date("Y-m-d");
            $subject .= " ".CoreLocal::get("CashierNo");
            $subject .= "-".CoreLocal::get("laneno");
            $subject .= "-".CoreLocal::get("transno");

            $mail = new PHPMailer();
            if (CoreLocal::get('emailReceiptSmtp') == 1) {
                /** setup SMTP parameters **/
                $mail->isSMTP();
                $mail->Host = CoreLocal::get('emailReceiptHost');
                $mail->Port = CoreLocal::get('emailReceiptPort');
                if (CoreLocal::get('emailReceiptSecurity') == 'SSL') {
                    $mail->SMTPSecure = 'ssl';
                } elseif (CoreLocal::get('emailReceiptSecurity') == 'TLS') {
                    $mail->SMTPSecure = 'tls';
                }
                $mail->SMTPAuth = false;
                if (CoreLocal::get('emailReceiptUser') != '' && CoreLocal::get('emailReceiptPw') != '') {
                    $mail->SMTPAuth = true;
                    $mail->Username = CoreLocal::get('emailReceiptUser');
                    $mail->Password = CoreLocal::get('emailReceiptPw');
                }
            } else {
                /** or just use PHP mail() **/
                $mail->isMail();
            }
            $mail->From = CoreLocal::get('emailReceiptFrom');
            $mail->FromName = CoreLocal::get('emailReceiptName');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            
            $eClass = CoreLocal::get('emailReceiptHtml');
            $eObj = new $eClass();
            $message = $eObj->receiptHeader();

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
            $message .= $eObj->receiptFooter();

            $mail->isHTML(true);
            $mail->Body = $message;
            $mail->AltBody = $text;
            
            $mail->send();
        }
    }

    /**
      Insert a chunk of information into the
      receipt that writeLine() will later use
      during rendering. By default adds nothing.
    */
    public function addRenderingSpacer($str)
    {
        return '<!-- ' . $str . ' -->';
    }
} 

