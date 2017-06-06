<?php

namespace COREPOS\pos\lib\PrintHandlers;
use COREPOS\pos\lib\Bitmap;
use COREPOS\pos\lib\ReceiptLib;

/*
ESCPOSPrintHandler
version 1 (2009-09-25)
author Alex Frase

Notes
    Epson TM-H6000II prints 504 pixels per line;
    42 columns with standard 12x24 font, 56 columns with alternate 9x17 font
*/

class ESCPOSPrintHandler extends PrintHandler {
    
    function tab() {
        // "\t"
        return "\x09";
    }
    
    function lineFeed($lines=1) {
        // one line: "\n"
        if ($lines <= 1)
            return "\x0A";
        // multiple lines: ESC "d" num
        return "\x1B\x64".chr( max(0, min(255, (int)$lines)) );
    }
    
    function pageFeed($reset=true) {
        // "(ESC) FF"
        return ($reset?"":"\x1B")."\x0C";
    }
    
    function carriageReturn() {
        // "\r"
        return "\x0D";
    }
    
    function clearPage() {
        // CAN
        return "\x18";
    }
    
    // TODO: realtime status transmission;  DLE(\x10) EOT(\x04) n
    
    // TODO: realtime request to printer;  DLE(\x10) ENQ(\x05) n
    
    // TODO: realtime pulse;  DLE(\x10) DC4(\x14) 1 m t
    
    function characterSpacing($dots=0) {
        // ESC " " space
        return "\x1B\x20".chr( max(0, min(255, $dots)) );
    }

    /**
      Add font style command
        @param $altFont use alternate font
      @param $bold use bold font
      @param $tall use tall characters
      @param $wide use wide characters
      @param $underline use undlerined font      
      @return A printer command string

      Replaces several old printLib.php functions
      TextStyle(true) is equivalent to normalFont()
      TextStyle(true,true) is equivalent to boldFont()
      TextStyle(true,false,true) is equivalent to
        biggerFont().
    */
    function textStyle($altFont=false, $bold=false, $tall=false, $wide=false, $underline=false) {
        // ESC "!" bitfield
        return ("\x1B\x21"
            .chr(
                ($underline ? 128 : 0)
                + ($wide ? 32 : 0)
                + ($tall ? 16 : 0)
                + ($bold ? 8 : 0)
                + ($altFont ? 1 : 0)
            )
        );
    }
    
    function gotoX($dots=0) {
        // ESC "$" xLO xHI
        return ("\x1B\x24"
            .chr( max(0, (int)($dots % 256)) )
            .chr( max(0, min(255, (int)($dots / 256))) )
        );
    } // GotoX()
    
    // TODO: enable downloadable character set;  ESC(\x1B) "%"(\x25) flag
    
    // TODO: define downloadable characters;  ESC(\x1B) "&"(\x26) 3(\x03) char0 char1 data
    
    /*
    Bitmaps are always drawn 24 dots tall (the height of a normal printed line).
    Tall dots are each 3x tall, so they take 1 byte per column (8 bits * 3 dots each = 24).
    Short dots require 3 bytes per column (8 bits * 3 bytes = 24).
    Data must therefore have ($width * ($tallDots ? 3 : 1)) bytes.
    Each data byte specifies dots top-to-bottom, from most- to least-significant-bit:
        \xC0 = \b11000000 = pixels in the top two rows
        \x81 = \b10000001 = pixels on the top and bottom rows
    Multiple bytes are drawn top-to-bottom (if !$tallDots), then left-to-right.
    */
    function inlineBitmap($data, $width, $tallDots=false, $wideDots=false) {
        // ESC "*" bitfield widthLO widthHI data
        $width = (int)max(0, min(1023, $width));
        $bytes = (int)($width * ($tallDots ? 1 : 3));
        return ("\x1B\x2A"
            .chr( ($tallDots ? 0 : 32) + ($wideDots ? 0 : 1) )
            .chr( (int)($width % 256) )
            .chr( (int)($width / 256) )
            .str_pad(substr($data, 0, $bytes), $bytes, "\x00")
        );
    } // InlineBitmap()
    
    function underline($dots=1) {
        // ESC "-" size
        return "\x1B\x2D".chr( max(0, min(2, $dots)) );
    }
    
    function resetLineSpacing() {
        // ESC "2"
        return "\x1B\x32";
    }
    
    function lineSpacing($space=64) {
        // in some small increment; with 12x24 font, space=64 seems to be approximately single-spaced, space=128 double
        // ESC "3" space
        return "\x1B\x33".chr( max(0, min(255, $space)) );
    }
    
    // TODO: return home;  ESC(\x1B) "<"(\x3C)
    
    // TODO: select peripheral device;  ESC(\x1B) "="(\x3D) n
    
    // TODO: delete downloadable characters;  ESC(\x1B) "?"(\x3F) char
    
    function reset() {
        // ESC "@"
        return "\x1B\x40";
    }
    
    function setTabs($tabs=null) {
        // ESC "D" tabs NUL
        if (!is_array($tabs) || !count($tabs))
            return "\x1B\x44\x00";
        $tabs = array_unique(array_map('chr', $tabs), SORT_NUMERIC);
        sort($tabs);
        return ("\x1B\x44"
            .implode('', $tabs)
            ."\x00"
        );
    }
    
    function bold($on=true) {
        // ESC "E" bit
        return "\x1B\x45".chr( $on ? 1 : 0 );
    }
    
    function doublePrint($on=true) {
        // is this like a shadow effect?
        // ESC "G" bit
        return "\x1B\x47".chr( $on ? 1 : 0 );
    }
    
    function paperFeed($space) {
        // in some small increment; with 12x24 font, space=64 seems to be approximately one printed line, space=128 two
        // ESC "J" space
        return "\x1B\x4A".chr( max(0, min(255, $space)) );
    }
    
    function paperFeedBack($space) {
        // in some small increment
        // ESC "K" num
        return "\x1B\x4B".chr( max(0, min(24, $space)) );
    }
    
    function pageMode() {
        // ESC "L"
        return "\x1B\x4C";
    }
    
    function font($font=0) {
        // ESC "M" font
        // (FS "G" font)
        return "\x1B\x4D".chr( max(0, min(2, $font)) );
    }
    
    /*
    international character sets:
        0: America
        1: France
        2: Germany
        3: UK
        4: Denmark I
        5: Sweden
        6: Italy
        7: Spain I
        8: Japan
        9: Norway
        10: Denmark II
        11: Spain II
        12: Latin America
        13: Korea
    */
    function characterSet($set=0) {
        // ESC "R" set
        return "\x1B\x52".chr( max(0, min(13, $set)) );
    }
    
    function lineMode() {
        // ESC "S"
        return "\x1B\x53";
    }
    
    function pageOrient($orient=0) {
        // ESC "T" dir
        return "\x1B\x54".chr( max(0, min(3, (int)$orient)) );
    }
    
    // TODO: unidirectional printing;  ESC(\x1B) "U"(\x55) bit
    
    function rotate($on=true) {
        // ESC "V" bit
        return "\x1B\x56".chr( $on ? 1 : 0 );
    }
    
    function pageRegion($x=0, $y=0, $dx=65535, $dy=65535) {
        // ESC "W" xLO xHI yLO yHI dxLO dxHI dyLO dyHI
        return ("\x1B\x57"
            .chr( max(0, (int)($x % 256)) )
            .chr( max(0, min(255, (int)($x / 245))) )
            .chr( max(0, (int)($y % 256)) )
            .chr( max(0, min(255, (int)($y / 245))) )
            .chr( max(0, (int)($dx % 256)) )
            .chr( max(0, min(255, (int)($dx / 245))) )
            .chr( max(0, (int)($dy % 256)) )
            .chr( max(0, min(255, (int)($dy / 245))) )
        );
    }
    
    function moveX($dots) {
        // ESC "\" dxLO dxHI
        if ($dots < 0 && $dots >= -32768)
            $dots += 65536;
        return ("\x1B\x5C"
            .chr( max(0, (int)($dots % 256)) )
            .chr( max(0, min(255, (int)($dots / 256))) )
        );
    }
    
    function alignLeft() {
        // ESC "a" align
        return "\x1B\x61\x00";
    }
    
    function alignCenter() {
        // ESC "a" align
        return "\x1B\x61\x01";
    }
    
    function alignRight() {
        // ESC "a" align
        return "\x1B\x61\x02";
    }
    
    function paperRoll($receipt=true, $journal=false, $endorse=false, $validation=false) {
        // ESC "c" "0" bitfield
        return ("\x1B\x63\x30"
            .chr(
                ($validation ? 8 : 0) // ??
                + ($endorse ? 4 : 0)
                + ($journal ? 2 : 0) // ??
                + ($receipt ? 1 : 0)
            )
        );
    } // PaperRoll()
    
    // TODO: set paper out warning sensor;  ESC(\x1B) "c"(\x63) "3"(\x33) bitfield
    
    // TODO: set paper out stop sensor;  ESC(\x1B) "c"(\x63) "4"(\x34) bitfield
    
    function panelButtons($on=true) {
        // ESC "c" "5" flag
        return "\x1B\x63\x35".chr( $on ? 0 : 1 );
    }
    
    function lineFeedBack() {
        // ESC "e" 1
        return "\x1B\x65\x01";
    }
    
    // TODO: define macro;  ESC(\x1B) "g"(\x67) NUL(\x00) num lenLO lenHI data
    
    // TODO: run macro;  ESC(\x1B) "g"(\x67) num
    
    function drawerKick($pin=2, $on=100, $off=100) {
        // ESC "p" pin on off
        return ("\x1B\x70"
            .chr( ($pin < 3.5) ? 0 : 1 )
            .chr( max(0, min(255, (int)($on / 2))) ) // times are *2ms
            .chr( max(0, min(255, (int)($off / 2))) )
        );
    }
    
    /*
    code tables:
        0: PC437 (USA: Standard Europe)
        1: Katakana
        2: PC850 (Multilingual)
        3: PC860 (Portugese)
        4: PC863 (Canadian-French)
        5: PC865 (Nordic)
        16: WPC1252
        17: PC866 (Cryllic #2)
        18: PC852 (Latin2)
        19: PC858
        255: blank
    */
    function codeTable($table=0) {
        // ESC "t" table
        return "\x1B\x74".chr( max(0, min(255, $table)) );
    }
    
    // TODO: send peripheral device status;  ESC(\x1B) "u"(\x75) ?
    
    // TODO: send paper sensor status;  ESC(\x1B) "v"(\x76) ?
    
    function upsideDown($on=true) {
        // ESC "{" flag
        return "\x1B\x7B".chr( $on ? 1 : 0 );
    }
    
    // TODO: paper forced feed;  FS(\x1C) "A"(\x41) ?
    
    // TODO: send bitmap file FS(\x1C) "B"(\x42) bitmap
    
    // TODO: pdf 417 aspect definition;  FS(\x1C) "C"(\x43) ?
    
    // TODO: pdf 417 ecc level definition FS(\x1C) "D"(\x44) ?
    
    // TODO: horz/vert bar in page mode FS(\x1C) "E"(\x45) dir lenLO lenHI width
    
    // TODO: set 2d barcode size;  FS(\x1C) "H"(\x48) zoom
    
    // TODO: paper forced return;  FS(\x1C) "R"(\x52) ?
    
    // TODO: store data;  FS(\x1C) "g"(\x67) "1"(\x31) \x00 aLO aML aMH aHI bLO bHI data; max memory address=1024
    
    // TODO: load data;  FS(\x1C) "g"(\x67) "2"(\x32) \x00 aLO aML aMH aHI bLO bHI; max memory address=1024
    
    // TODO: print 2d barcode FS(\x1C) "k"(\x6B) type lenLO lenHI data
    
    // TODO: print stored bitmap;  FS(\x1C) "p"(\x70) num bitfield
    
    // TODO: store bitmaps;  FS(\x1C) "q"(\x71) num [xLO xHI yLO yHI]{num}
    
    function characterZoom($horiz=1, $vert=1) {
        // GS "!" zoom
        return "\x1D\x21".chr(
            16 * (int)(max(1, min(8, $horiz)) - 1)
            + (int)(max(1, min(8, $vert)) - 1)
        );
    }
    
    function gotoY($dots=0) {
        // GS "$" yLO yHI
        return ("\x1D\x24"
            .chr( max(0, (int)($dots % 256)) )
            .chr( max(0, min(255, (int)($dots / 256))) )
        );
    }
    
    // TODO: define downloadable image;  GS(\x1D) "*"(\x2A) x y data
    
    function test($type=3, $paper=0) {
        // GS "(" "A"
        return ("\x1D\x28\x41\x02\x00"
            .chr( max(0, min(2, (int)$paper)) )
            .chr( max(1, min(3, (int)$type)) )
        );
    }
    
    // TODO: edit NV memory;  GS(\x1D) "("(\x28) "C"(\x43) ...
    
    // TODO: toggle realtime command;  GS(\x1D) "("(\x28) "D"(\x44) ...
    
    // TODO: user startup commands;  GS(\x1D) "("(\x28) "E"(\x45) ...
    
    function density($factor=1.0) {
        // GS "(" "K" \x02 \x00 \x31 factor
        // factor = 0.7 (\xFA) - 0.9 (\xFF) ; 1.0 (\x00) - 1.3 (\x06)
        return ("\x1D\x28\x4B\x02\x00\x31"
            .chr( (int)((256 + max(-6, min(6, (($factor - 1.0) * 20)))) % 256) )
        );
    }
    
    function colorBlack() {
        // GS "(" "N" \x02 \x00 \x30 color
        // ESC "r" color
        return "\x1D\x28\x4E\x02\x00\x30\x31";
    }
    
    function colorRed() {
        // GS "(" "N" \x02 \x00 \x30 color
        // ESC "r" color
        return "\x1D\x28\x4E\x02\x00\x30\x32";
    }
    
    // TODO: print downloadable image;  GS(\x1D) "/"(\x2F) bitfield
    
    // TODO: toggle macro recording;  GS(\x1D) ":"(\x3A)
    
    function invert($on=true) {
        // GS "B" flag
        return "\x1D\x42".chr( $on ? 1 : 0 );
    }
    
    // TODO: set counter print mode;  GS(\x1D) "C"(\x43) "0"(\x30) digits align
    
    // TODO: set counter mode;  GS(\x1D) "C"(\x43) "1"(\x31) aLO aHI bLO bHI step repeat
    
    // TODO: set counter mode value;  GS(\x1D) "C"(\x43) "2"(\x32) vLO vHI
    
    // TODO: set counter mode;  GS(\x1D) "C"(\x43) ";"(\x3B) sa ";"(\x3B) sb ";"(\x3B) sn ";"(\x3B) sr ";"(\x3B) sc ";"(\x3B)
    
    function speedHigh() {
        // GS "E" speed
        return "\x1D\x45\x00";
    }
    
    function speedMedium() {
        // GS "E" speed
        return "\x1D\x45\x10";
    }
    
    function speedLow() {
        // GS "E" speed
        return "\x1D\x45\x20";
    }
    
    function barcodeHRI($below=true, $above=false) {
        // GS "H" bitfield
        return ("\x1D\x48"
            .chr( ($below ? 2 : 0) + ($above ? 1 : 0) )
        );
    }
    
    // TODO: send printer ID;  GS(\x1D) "I"(\x49) type
    
    function leftMargin($dots=0) {
        // GS "L" marginLO marginHI
        return ("\x1D\x4C"
            .chr( max(0, (int)($dots % 256)) )
            .chr( max(0, min(255, (int)($dots / 256))) )
        );
    }
    
    function dotPitch($primary=0, $secondary=0) {
        // sets dot pitch to 1/Xth inch (25.4/Xth mm), or 0 for default
        // GS "P" pitch pitch
        return "\x1D\x50".chr($primary).chr($secondary);
    }
    
    function discardLine() {
        // GS "T" printbit
        return "\x1D\x54\x00";
    }
    
    function preCutPaper($full=false) {
        // the cutter is above the print position, so cutting without feeding will put the cut above the last few lines printed
        // GS "V" bit
        // ESC "i"        (partial)
        // ESC "m"        (partial)
        return "\x1D\x56".chr( $full ? 0 : 1 );
    }
    
    function cutPaper($full=false, $feed=0) {
        // this version feeds the paper far enough to put the cutter just below the last line printed, plus the feed distance (in pixels?)
        // GS "V" bit feed
        return ("\x1D\x56"
            .chr( $full ? 65 : 66 )
            .chr( $feed )
        );
    }
    
    function printableWidth($dots=65535) {
        // GS "W" widthLO widthHI
        return ("\x1D\x57"
            .chr( max(0, (int)($dots % 256)) )
            .chr( max(0, min(255, (int)($dots / 256))) )
        );
    }
    
    function moveY($dots) {
        // GS "\" dyLO dyHI
        if ($dots < 0 && $dots >= -32768)
            $dots += 65536;
        return ("\x1D\x5C"
            .chr( max(0, (int)($dots % 256)) )
            .chr( max(0, min(255, (int)($dots / 256))) )
        );
    }
    
    // TODO: run macro;  GS(\x1D) "^"(\x5E) repeat delay mode
    
    // TODO: enable automatic status back;  GS(\x1D) "a"(\x61) bitfield
    
    function smooth($on=true) {
        // GS "b" flag
        return "\x1D\x62".chr( $on ? 1 : 0 );
    }
    
    // TODO: print counter;  GS(\x1D) "c"(\x63)
    
    function barcodeHRIFont($font=0) {
        // GS "f" font
        return "\x1D\x66".chr( max(0, min(2, $font)) );
    }
    
    function barcodeHeight($dots=162) {
        // GS "h" height
        return "\x1D\x68".chr( max(1, min(255, $dots)) );
    }

    public function printBarcode($type, $data)
    {
        switch ($type) {
            case PrintHandler::BARCODE_UPCA:
                return $this->barcodeUPC($data, 65);
            case PrintHandler::BARCODE_UPCE:
                return $this->barcodeUPC($data, 66);
            case PrintHandler::BARCODE_EAN13:
                return $this->barcodeEAN($data, false);
            case PrintHandler::BARCODE_EAN8:
                return $this->barcodeEAN($data, true);
            case PrintHandler::BARCODE_CODE39:
                return $this->barcodeCODE39($data);
            case PrintHandler::BARCODE_ITF:
                return $this->barcodeITF($data);
            case PrintHandler::BARCODE_CODEABAR:
                return $this->barcodeCODEABAR($data);
            case PrintHandler::BARCODE_CODE93:
                return $this->barcodeCODE93($data);
            case PrintHandler::BARCODE_CODE128:
                return $this->barcodeCODE128($data);
        }

        return '';
    }
    
    private function barcodeUPC($data, $subtype) {
        $bytes = max(11, min(12, strlen($data)));
        return ("\x1D\x6B"
            .chr( $subtype )
            .chr( $bytes )
            .str_pad(preg_replace('|[^0-9]|', '0', substr($data, 0, $bytes)), $bytes, '0', STR_PAD_LEFT)
        );
    }
    
    private function barcodeEAN($data, $ean8=false) {
        $bytes = $ean8 ? max(7, min(8, strlen($data))) : max(12, min(13, strlen($data)));
        return ("\x1D\x6B"
            .chr( $ean8 ? 68 : 67 )
            .chr( $bytes )
            .str_pad(preg_replace('|[^0-9]|', '0', substr($data, 0, $bytes)), $bytes, '0', STR_PAD_LEFT)
        );
    }
    
    private function barcodeCODE39($data) {
        $bytes = max(1, min(255, strlen($data)));
        return ("\x1D\x6B"
            .chr(69)
            .chr($bytes)
            .str_pad(preg_replace('|[^0-9A-Z $%+./-]|', ' ', substr($data, 0, $bytes)), $bytes, ' ', STR_PAD_LEFT)
        );
    }
    
    private function barcodeITF($data) {
        $bytes = max(2, min(254, (int)(strlen($data) / 2) * 2));
        return ("\x1D\x6B"
            .chr(70)
            .chr($bytes)
            .str_pad(preg_replace('|[^0-9]|', '0', substr($data, 0, $bytes)), $bytes, '0', STR_PAD_LEFT)
        );
    }
    
    private function barcodeCODEABAR($data) {
        $bytes = max(1, min(255, strlen($data)));
        return ("\x1D\x6B"
            .chr(71)
            .chr($bytes)
            .str_pad(preg_replace('|[^0-9A-D$+./:-]|', '0', substr($data, 0, $bytes)), $bytes, '0', STR_PAD_LEFT)
        );
    }
    
    private function barcodeCODE93($data) {
        $bytes = max(1, min(255, strlen($data)));
        return ("\x1D\x6B"
            .chr(72)
            .chr($bytes)
            .str_pad(preg_replace('|[^\\x00-\\x7f]|', "\x00", substr($data, 0, $bytes)), $bytes, "\x00", STR_PAD_LEFT)
        );
    }
    
    private function barcodeCODE128($data) {
        $bytes = max(2, min(255, strlen($data)));
        return ("\x1D\x6B"
            .chr(73)
            .chr($bytes)
            .str_pad(preg_replace('|[^\\x00-\\x7f]|', "\x00", substr($data, 0, $bytes)), $bytes, "\x00", STR_PAD_LEFT)
        );
    }
    
    // TODO: send status;  GS(\x1D) "r"(\x72) bitfield
    
    /*
    Raster bitmaps may be up to 524280 pixels wide ((255+255*256)*8), and up to 2303 pixels tall (255+8*256).
    Tall dots are 2 pixels tall, and wide dots are 2 pixels wide.
    This function takes $width and $height as pixels, but the printer takes width as bytes (= 8 pixels).
    Data must have (($width * $height) / 8) bytes.
    The bits in each data byte specify dots from left-to-right, and bytes are left-to-right, top-to-bottom.
        \xC0 = \b11000000 = pixels in the left two columns
        \x81 = \b10000001 = pixels in the left and right columns
    */
    function rasterBitmap($data, $width, $height, $tallDots=false, $wideDots=false) {
        // GS "v" 0 bits widthLO widthHI heightLO heightHI data
        $width = (int)(($width + 7) / 8);
        $bytes = (int)($width * $height);
        return ("\x1D\x76\x00"
            .chr( ($tallDots ? 2 : 0) + ($wideDots ? 1 : 0) )
            .chr( (int)($width % 256) )
            .chr( max(0, min(255, (int)($width / 256))) )
            .chr( (int)($height % 256) )
            .chr( max(0, min(8, (int)($height / 256))) )
            .str_pad(substr($data, 0, $bytes), $bytes, "\x00")
        );
    }
    
    function barcodeWidth($scale=3) {
        // ($scale * 0.141mm) is the width per single code line, not the total code width
        // GS "w" scale
        return "\x1D\x77".chr( max(1, min(6, $scale)) );
    }

    function writeLine($text){
        ReceiptLib::writeLine($text);
    }

    function renderBitmapFromFile($fn, $align='C')
    {
        return $this->RenderBitmap($fn, $align);
    }

    /**
      Turn bitmap into receipt string
      @param $arg string filename OR Bitmap obj
      @return receipt-formatted string
    */
    function renderBitmap($arg, $align='C'){
        $slip = "";

        if (!class_exists('COREPOS\\pos\\lib\\Bitmap')) return "";

        $bmp = null;
        if (is_object($arg) && is_a($arg, 'COREPOS\\pos\\lib\\Bitmap')){
            $bmp = $arg;
        }
        else if (file_exists($arg)){
            $bmp = new Bitmap();
            $bmp->load($arg);
        }

        // argument was invalid
        if ($bmp === null)
            return "";

        $bmpData = $bmp->getRawData();
        $bmpWidth = $bmp->getWidth();
        $bmpHeight = $bmp->getHeight();
        $bmpRawBytes = (int)(($bmpWidth + 7)/8);

        $stripes = $this->TransposeBitmapData($bmpData, $bmpWidth);
        for($i=0; $i<count($stripes); $i++)
            $stripes[$i] = $this->InlineBitmap($stripes[$i], $bmpWidth);

        if ($align == 'C')
            $slip .= $this->AlignCenter();
        if (count($stripes) > 1)
            $slip .= $this->LineSpacing(0);
        $slip .= implode("\n",$stripes);
        if (count($stripes) > 1)
            $slip .= $this->ResetLineSpacing();
        if ($align == 'C') $slip .= "\n";
        $slip .= $this->AlignLeft();

        return $slip;
    }

    /**
      Show bitmap stored on the printer device itself
      @param $image_id [int|array] storage location ID
        OR storage position (start, end)
      @return [string] receipt text
    */
    public function renderBitmapFromRam($image_id)
    {
        if (is_array($image_id) && count($image_id) >= 2) {
            return chr(29) . '(L' . chr(6) . chr(0) . '0E' 
                . chr($image_id[0]) // Start position defined in utility
                . chr($image_id[1]) // End position defined in utility
                . chr(1) . chr(1);
        }

        return chr(28) . 'p' . chr($image_id) . chr(0);
    }
    
} // ESCPOSPrintHandler

