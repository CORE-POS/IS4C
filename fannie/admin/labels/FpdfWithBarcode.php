<?php

/****Credit for the majority of what is below for barcode generation
 has to go to Olivier for posting the script on the FPDF.org scripts
 webpage.****/
if (!class_exists('FpdfWithBarcode', false)) {

class FpdfWithBarcode extends FPDF
{
   function EAN13($x,$y,$barcode,$h=16,$w=.35)
   {
      $numDigits = strlen(ltrim($barcode, '0')) <= 11 ? 12 : 13;
      $this->Barcode($x,$y,$barcode,$h,$w,$numDigits);
   }

   function UPC_A($x,$y,$barcode,$h=16,$w=.35)
   {
      $numDigits = strlen(ltrim($barcode, '0')) <= 11 ? 12 : 13;
      $this->Barcode($x,$y,$barcode,$h,$w,$numDigits);
    }

    function GetCheckDigit($barcode)
    {
      //Compute the check digit
      $sum=0;
      for($i=1;$i<=11;$i+=2)
        $sum+=3*(isset($barcode[$i])?$barcode[$i]:0);
      for($i=0;$i<=10;$i+=2)
        $sum+=(isset($barcode[$i])?$barcode[$i]:0);
      $rem=$sum%10;
      if($rem>0)
        $rem=10-$rem;
      return $rem;
    }

   function TestCheckDigit($barcode)
   {
      //Test validity of check digit
      $sum=0;
      for($i=1;$i<=11;$i+=2)
        $sum+=3*$barcode[$i];
      for($i=0;$i<=10;$i+=2)
        $sum+=$barcode[$i];
      return ($sum+$barcode[12])%10==0;
   }

   function Barcode($x,$y,$barcode,$h,$w,$len)
   {
      //Padding
      $barcode=str_pad($barcode,$len-1,'0',STR_PAD_LEFT);
      if($len==12)
        $barcode='0'.$barcode;
      //Add or control the check digit
      if (strlen($barcode)==12) {
        $barcode.=$this->GetCheckDigit($barcode);
      } elseif (!$this->TestCheckDigit($barcode)) {
        $barcode = substr($barcode, 0, strlen($barcode)-1);
        $barcode .= $this->GetCheckDigit($barcode);
      }
      //Convert digits to bars
      $codes = BarcodeLib::$CODES;
      $parities = BarcodeLib::$PARITIES;
      $code='101';
      $pty=$parities[$barcode[0]];
      for($i=1;$i<=6;$i++)
        $code.=$codes[$pty[$i-1]][$barcode[$i]];
      $code.='01010';
      for($i=7;$i<=12;$i++)
        $code.=$codes['C'][$barcode[$i]];
      $code.='101';
      //Draw bars
      for($i=0;$i<strlen($code);$i++)
      {
        if($code[$i]=='1')
            $this->Rect($x+$i*$w,$y,$w,$h,'F');
      }
      //Print text under barcode
      $this->barcodeText($x, $y, $h, $barcode, $len);
    }

    function barcodeText($x, $y, $h, $barcode, $len)
    {
      $this->SetFont('Arial','',9);
      $this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
    }

    function normalize($text)
    {
        $subs = array("@" => "%40", "`" => "%60", "¢" => "%A2", "£" => "%A3", "¥" => "%A5", "|" => "%A6", "«" => "%AB", "¬" => "%AC", "¯" => "%AD", "º" => "%B0", "±" => "%B1", "ª" => "%B2", "µ" => "%B5", "»" => "%BB", "¼" => "%BC", "½" => "%BD", "¿" => "%BF", "À" => "%C0", "Á" => "%C1", "Â" => "%C2", "Ã" => "%C3", "Ä" => "%C4", "Å" => "%C5", "Æ" => "%C6", "Ç" => "%C7", "È" => "%C8", "É" => "%C9", "Ê" => "%CA", "Ë" => "%CB", "Ì" => "%CC", "Í" => "%CD", "Î" => "%CE", "Ï" => "%CF", "Ð" => "%D0", "Ñ" => "%D1", "Ò" => "%D2", "Ó" => "%D3", "Ô" => "%D4", "Õ" => "%D5", "Ö" => "%D6", "Ø" => "%D8", "Ù" => "%D9", "Ú" => "%DA", "Û" => "%DB", "Ü" => "%DC", "Ý" => "%DD", "Þ" => "%DE", "ß" => "%DF", "à" => "%E0", "á" => "%E1", "â" => "%E2", "ã" => "%E3", "ä" => "%E4", "å" => "%E5", "æ" => "%E6", "ç" => "%E7", "è" => "%E8", "é" => "%E9", "ê" => "%EA", "ë" => "%EB", "ì" => "%EC", "í" => "%ED", "î" => "%EE", "ï" => "%EF", "ð" => "%F0", "ñ" => "%F1", "ò" => "%F2", "ó" => "%F3", "ô" => "%F4", "õ" => "%F5", "ö" => "%F6", "÷" => "%F7", "ø" => "%F8", "ù" => "%F9", "ú" => "%FA", "û" => "%FB", "ü" => "%FC", "ý" => "%FD", "þ" => "%FE", "ÿ" => "%FF");

        foreach($subs as $chr => $sub) {
            $newText = str_replace($chr, $sub, $text);
        }

        return urldecode($newText);
    }

}

}

