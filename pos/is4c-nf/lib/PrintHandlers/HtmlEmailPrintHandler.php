<?php
/**
 @class EmailPrintHandler

 Distribute receipt via email

 Most methods are not implemented
 because they have no purpose in
 a non-physical receipt
*/

class HtmlEmailPrintHandler extends PrintHandler 
{
    
    /**
     Get printer tab
     @return string printer command
    */
    function Tab() {
        return "    ";
    }
    
    /**
      Get newlines
      @param $lines number of lines
      @return string printer command
    */
    function LineFeed($lines=1) {
        $ret = "<br />\n";
        for($i=1;$i<$lines;$i++)
            $ret .= "<br />\n";
        return $ret;
    }
    
    function PageFeed($reset=true) {
        return "";
    }
    
    /**
      Get carriage return
      @return string printer command
    */
    function CarriageReturn() {
        return "";
    }
    
    function ClearPage() {
        return "";
    }
    
    function CharacterSpacing($dots=0) {
        // ESC " " space
        return "";
    }

    function centerString($text,$big=false)
    {
        if ($big) {
            $text = '<strong>' . $text . '</strong>';
        }

        return '<td align="center">' . $text . '</td>';
    }
    
    function TextStyle($altFont=false, $bold=false, $tall=false, $wide=false, $underline=false) {
        return "";
    }
    
    function GotoX($dots=0) {
        return "";
    } // GotoX()
    
    function InlineBitmap($data, $width, $tallDots=false, $wideDots=false) {
        return "";
    } // InlineBitmap()
    
    function Underline($dots=1) {
        return "";
    }
    
    function ResetLineSpacing() {
        return "";
    }
    
    function LineSpacing($space=64) {
        return "";
    }
    
    function Reset() {
        return "";
    }
    
    function SetTabs($tabs=null) {
        return "";
    }
    
    /**
     Enable or disable bold font
     @param $on boolean enable
     @return string printer command
    */
    function Bold($on=true) {
        return "";
    }
    
    function DoublePrint($on=true) {
        return "";
    }
    
    function PaperFeed($space) {
        return "";
    }
    
    function PaperFeedBack($space) {
        return "";
    }
    
    function PageMode() {
        return "";
    }
    
    function Font($font=0) {
        return "";
    }
    
    function CharacterSet($set=0) {
        return "";
    }
    
    function LineMode() {
        return "";
    }
    
    function PageOrient($orient=0) {
        return "";
    }
    
    // TODO: unidirectional printing;  ESC(\x1B) "U"(\x55) bit
    
    function Rotate($on=true) {
        return "";
    }
    
    function PageRegion($x=0, $y=0, $dx=65535, $dy=65535) {
        return "";
    }
    
    function MoveX($dots) {
        return "";
    }
    
    function AlignLeft() {
        return "";
    }
    
    function AlignCenter() {
        return "";
    }
    
    function AlignRight() {
        return "";
    }
    
    function PaperRoll($receipt=true, $journal=false, $endorse=false, $validation=false) {
        return "";
    } // PaperRoll()
    
    function PanelButtons($on=true) {
        return "";
    }
    
    function LineFeedBack() {
        return "";
    }
    
    function DrawerKick($pin=2, $on=100, $off=100) {
        // ESC "p" pin on off
        return ("\x1B\x70"
            .chr( ($pin < 3.5) ? 0 : 1 )
            .chr( max(0, min(255, (int)($on / 2))) ) // times are *2ms
            .chr( max(0, min(255, (int)($off / 2))) )
        );
    }
    
    function CodeTable($table=0) {
        return "";
    }
    
    function UpsideDown($on=true) {
        return "";
    }
    
    function CharacterZoom($horiz=1, $vert=1) {
        return "";
    }
    
    function GotoY($dots=0) {
        return "";
    }
    
    function Test($type=3, $paper=0) {
        return "";
    }
    
    function Density($factor=1.0) {
        return "";
    }
    
    function ColorBlack() {
        return "";
    }
    
    function ColorRed() {
        return "";
    }
    
    function Invert($on=true) {
        return "";
    }
    
    function SpeedHigh() {
        return "";
    }
    
    function SpeedMedium() {
        return "";
    }
    
    function SpeedLow() {
        return "";
    }
    
    function BarcodeHRI($below=true, $above=false) {
        return "";
    }
    
    function LeftMargin($dots=0) {
        return "";
    }
    
    function DotPitch($primary=0, $secondary=0) {
        return "";
    }
    
    function DiscardLine() {
        return "";
    }
    
    function PreCutPaper($full=false) {
        return "";
    }
    
    function CutPaper($full=false, $feed=0) {
        return "";
    }
    
    function PrintableWidth($dots=65535) {
        return "";
    }
    
    function MoveY($dots) {
        return "";
    }
    
    function Smooth($on=true) {
        return "";
    }
    
    function BarcodeHRIFont($font=0) {
        return "";
    }
    
    function BarcodeHeight($dots=162) {
        return "";
    }
    
    function BarcodeUPC($data, $upcE=false) {
        return "";
    }
    
    function BarcodeEAN($data, $ean8=false) {
        return "";
    }
    
    function BarcodeCODE39($data) {
        return "";
    }
    
    function BarcodeITF($data) {
        return "";
    }
    
    function BarcodeCODEABAR($data) {
        return "";
    }
    
    function BarcodeCODE93($data) {
        return "";
    }
    
    function BarcodeCODE128($data) {
        return "";
    }
    
    function BarcodeWidth($scale=3) {
        return "";
    }

    /**
      Write output to device
      @param the output string
    */
    function writeLine($text, $to=false)
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
            $columns = 4;
            foreach (explode("\n", $text) as $line) {
                $message .= '<tr>';
                if (strstr($line, '   ')) {
                    $parts = preg_split('/ {2,}/', $line, -1, PREG_SPLIT_NO_EMPTY); 
                    $num_col = 0;
                    for ($i=0; $i<count($parts); $i++) {
                        if (is_numeric(trim($parts[$i]))) {
                            $num_col = $i;
                            break;
                        }
                    }
                    switch ($num_col) {
                        case 0:
                            $num_col = 999;
                            $message .= '<td colspan="4">' . $line . '</td>';
                            break;
                        case 1:
                            $message .= '<td colspan="2">' . $parts[0] . '</td><td>' . $parts[$num_col] . '</td>';
                            break;
                        case 2: 
                            $message .= '<td>' . $parts[0] . '</td><td>' . $parts[1] . '</td><td>' . $parts[$num_col] . '</td>';
                            break;
                        default:
                            $num_col = 999;
                            $message .= '<td colspan="4">' . $line . '</td>';
                            break;
                    }
                    for ($i=$num_col+1; $i<count($parts); $i++) {
                        $message .= '<td>' . $parts[$i] . '</td>';
                    }
                } else {
                    if (substr($line, 0, 3) == '<td') {
                        $message .= $line;
                    } else {
                        $message .= '<td colspan="4">' . $line . '</td>';
                    }
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

    function RenderBitmapFromFile($fn, $align='C')
    {
        return '';
    }
} 

