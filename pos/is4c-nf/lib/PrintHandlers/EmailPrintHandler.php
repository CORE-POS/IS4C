<?php

namespace COREPOS\pos\lib\PrintHandlers;
use \CoreLocal;

/**
 @class EmailPrintHandler

 Distribute receipt via email

 Most methods are not implemented
 because they have no purpose in
 a non-physical receipt
*/

class EmailPrintHandler extends PrintHandler {
    
    /**
     Get printer tab
     @return string printer command
    */
    function tab() {
        return "    ";
    }
    
    /**
      Get carriage return
      @return string printer command
    */
    function carriageReturn() {
        return "";
    }
    
    function centerString($text)
    {
        $width = 60;

        $blank = str_repeat(" ", $width);
        $text = trim($text);
        $lead = (int) (($width - strlen($text)) / 2);
        $newline = substr($blank, 0, $lead).$text;
        return $newline;
    }
    
    function drawerKick($pin=2, $on=100, $off=100) {
        // ESC "p" pin on off
        return ("\x1B\x70"
            .chr( ($pin < 3.5) ? 0 : 1 )
            .chr( max(0, min(255, (int)($on / 2))) ) // times are *2ms
            .chr( max(0, min(255, (int)($off / 2))) )
        );
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
            
            $headers = "From: ".CoreLocal::get("emailReceiptFrom");

            mail($to, $subject, $text, $headers);
        }
    }

    function renderBitmapFromFile($fn, $align='C')
    {
        return '';
    }

    function barcodeUPC($data, $upcE=false) 
    {
        return '<img src="http://store.wholefoods.coop/upc/' . $data . '" />';
    }
} 

