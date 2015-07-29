<?php
/**
 @class EmailPrintHandler

 Distribute receipt via email

 Most methods are not implemented
 because they have no purpose in
 a non-physical receipt
*/

class HtmlEmailPrintHandler extends EmailPrintHandler 
{
    public function centerString($text, $big=false)
    {
        if ($big) {
            $text = '<strong>' . $text . '</strong>';
        }

        return '<div style="text-align:center">' . $text . '</div>';
    }
    
    /**
      Write output to device
      @param the output string
    */
    public function writeLine($text, $to=false)
    {
        $text = substr($text,0,strlen($text)-2);
        if (CoreLocal::get("print") != 0 && $to !== False) {

            $subject = "Receipt ".date("Y-m-d");
            $subject .= " ".CoreLocal::get("CashierNo");
            $subject .= "-".CoreLocal::get("laneno");
            $subject .= "-".CoreLocal::get("transno");
            
            $headers = "From: ".CoreLocal::get("emailReceiptFrom") . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

            $start_message = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
                <title></title>
                <style></style>
            </head>
            <body>
            <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="body-table">
                <tr>
                    <td align="center" valign="top">
                        <table border="0" cellpadding="10" cellspacing="0" width="600" id="email-container">';
            $message = '';
            foreach (explode("\n", $text) as $line) {
                $message .= '<tr>';
                if (preg_match('/^(.*)(\d+\.\d\d)(.*)$/', $line, $matches)) {
                    $message .= '<td>' . $matches[1] . '</td>';
                    $message .= '<td>' . $matches[2] . '</td>';
                    $message .= '<td>' . $matches[3] . '</td>';
                } else {
                    $message .= '<td colspan="3">' . $line . '</td>';
                }
                $message .= '</tr>' . "\n";
            }
            $end_message = "
                        </table>\n
                    </td>\n
                </tr>\n
            </table>\n
            </body>\n
            </html>\n";

            mail($to, $subject, $start_message . $message . $end_message, $headers);
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

