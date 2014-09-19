<?php
/**
 @class PrintHandler
 Generic print module

 This is working backwards from the
 existing ESCPOS class. 
 Work-in-progress.
*/

class PrintHandler {
	
	/**
	 Get printer tab
	 @return string printer command
	*/
	function Tab() {
		return "\t";
	}
	
	/**
	  Get newlines
	  @param $lines number of lines
	  @return string printer command
	*/
	function LineFeed($lines=1) {
		$ret = "\n";
		for($i=1;$i<$lines;$i++)
			$ret .= "\n";
		return $ret;
	}
	
	function PageFeed($reset=true) {
		// "(ESC) FF"
		return ($reset?"":"\x1B")."\x0C";
	}
	
	/**
	  Get carriage return
	  @return string printer command
	*/
	function CarriageReturn() {
		return "\r";
	}
	
	function ClearPage() {
		// CAN
		return "\x18";
	}
	
	function CharacterSpacing($dots=0) {
		// ESC " " space
		return "\x1B\x20".chr( max(0, min(255, $dots)) );
	}

	/**
	 Center a string of text
	 @param $text the text
	 @param $big boolean using larger font
	 @return a printable string

	 Replaces old center(), centerString(),
	 and centerBig() functions. 
	*/
	function centerString($text,$big=false){
		$width = ($big) ? 30 : 59;

		$blank = str_repeat(" ", $width);
		$text = trim($text);
		$lead = (int) (($width - strlen($text)) / 2);
		$newline = substr($blank, 0, $lead).$text;
		return $newline;
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
	function TextStyle($altFont=false, $bold=false, $tall=false, $wide=false, $underline=false) {
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
	
	function GotoX($dots=0) {
		// ESC "$" xLO xHI
		return ("\x1B\x24"
			.chr( max(0, (int)($dots % 256)) )
			.chr( max(0, min(255, (int)($dots / 256))) )
		);
	} // GotoX()
	
	/*
	ESC/POS requires graphical bitmap data in columns, but BMP is rasterized in rows,
	so this function transposes the latter to the former.  The source BMP data must
	be 1 bpp (black/white image only), and $width is in pixels == bits (not bytes).
	The return will be an array of binary strings; if the source data fits in one
	inline stripe (8 pixels tall with $tallDots, 24 otherwise) the array will have
	only one element.
	*/
	function TransposeBitmapData($data, $width, $tallDots=false) {
		$oldRowBytes = (int)(($width + 7) / 8);
		$newColBytes = $tallDots ? 1 : 3;
		$oldStripeSize = (int)($oldRowBytes * ($tallDots ? 8 : 24));
		$stripes = array();

		// str_split function doesn't exist in PHP4
		$str_split = array();
		$val = "";
		for($i=0;$i<strlen($data);$i++){
			if ($i % $oldStripeSize==0){
				if (strlen($val) > 0)
					$str_split[] = $val;
				$val = "";
			}
			$val .= $data[$i];
		}
		if (strlen($val) > 0)
			$str_split[] = $val;
		// end manual split

		foreach ($str_split as $oldData) {
			$oldData = str_pad($oldData, $oldStripeSize, "\x00", STR_PAD_RIGHT);
			$newData = str_repeat("\x00", $width * $newColBytes);
			for ($c = 0;  $c < $width;  $c++) {
				$oldMask = 1 << (7 - ($c % 8));
				$newByte = ($tallDots ? $c : ($c + $c + $c));
				// top or only byte
				$b = 0;
				for ($r = 0;  $r < 8;  $r++) {
					$oldByte = ($r * $oldRowBytes) + ($c >> 3); // (int)($c / 8)
					if (ord($oldData[$oldByte]) & $oldMask)
						$b |= (1 << (7 - ($r % 8)));
				}
				$newData[$newByte + 0] = chr($b);
				if (!$tallDots) {
					// middle byte
					$b = 0;
					for ($r = 8;  $r < 16;  $r++) {
						$oldByte = ($r * $oldRowBytes) + ($c >> 3); // (int)($c / 8)
						if (ord($oldData[$oldByte]) & $oldMask)
							$b |= (1 << (7 - ($r % 8)));
					}
					$newData[$newByte + 1] = chr($b);
					// bottom byte
					$b = 0;
					for ($r = 16;  $r < 24;  $r++) {
						$oldByte = ($r * $oldRowBytes) + ($c >> 3); // (int)($c / 8)
						if (ord($oldData[$oldByte]) & $oldMask)
							$b |= (1 << (7 - ($r % 8)));
					}
					$newData[$newByte + 2] = chr($b);
				}
			}
			$stripes[] = $newData;
		}
		return $stripes;
	} // TransposeBitmapData()
	
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
	function InlineBitmap($data, $width, $tallDots=false, $wideDots=false) {
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
	
	function Underline($dots=1) {
		// ESC "-" size
		return "\x1B\x2D".chr( max(0, min(2, $dots)) );
	}
	
	function ResetLineSpacing() {
		// ESC "2"
		return "\x1B\x32";
	}
	
	function LineSpacing($space=64) {
		// in some small increment; with 12x24 font, space=64 seems to be approximately single-spaced, space=128 double
		// ESC "3" space
		return "\x1B\x33".chr( max(0, min(255, $space)) );
	}
	
	function Reset() {
		// ESC "@"
		return "\x1B\x40";
	}
	
	function SetTabs($tabs=null) {
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
	
	/**
	 Enable or disable bold font
	 @param $on boolean enable
	 @return string printer command
	*/
	function Bold($on=true) {
		return "";
	}
	
	function DoublePrint($on=true) {
		// is this like a shadow effect?
		// ESC "G" bit
		return "\x1B\x47".chr( $on ? 1 : 0 );
	}
	
	function PaperFeed($space) {
		// in some small increment; with 12x24 font, space=64 seems to be approximately one printed line, space=128 two
		// ESC "J" space
		return "\x1B\x4A".chr( max(0, min(255, $space)) );
	}
	
	function PaperFeedBack($space) {
		// in some small increment
		// ESC "K" num
		return "\x1B\x4B".chr( max(0, min(24, $space)) );
	}
	
	function PageMode() {
		// ESC "L"
		return "\x1B\x4C";
	}
	
	function Font($font=0) {
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
	function CharacterSet($set=0) {
		// ESC "R" set
		return "\x1B\x52".chr( max(0, min(13, $set)) );
	}
	
	function LineMode() {
		// ESC "S"
		return "\x1B\x53";
	}
	
	function PageOrient($orient=0) {
		// ESC "T" dir
		return "\x1B\x54".chr( max(0, min(3, (int)$orient)) );
	}
	
	// TODO: unidirectional printing;  ESC(\x1B) "U"(\x55) bit
	
	function Rotate($on=true) {
		// ESC "V" bit
		return "\x1B\x56".chr( $on ? 1 : 0 );
	}
	
	function PageRegion($x=0, $y=0, $dx=65535, $dy=65535) {
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
	
	function MoveX($dots) {
		// ESC "\" dxLO dxHI
		if ($dots < 0 && $dots >= -32768)
			$dots += 65536;
		return ("\x1B\x5C"
			.chr( max(0, (int)($dots % 256)) )
			.chr( max(0, min(255, (int)($dots / 256))) )
		);
	}
	
	function AlignLeft() {
		// ESC "a" align
		return "\x1B\x61\x00";
	}
	
	function AlignCenter() {
		// ESC "a" align
		return "\x1B\x61\x01";
	}
	
	function AlignRight() {
		// ESC "a" align
		return "\x1B\x61\x02";
	}
	
	function PaperRoll($receipt=true, $journal=false, $endorse=false, $validation=false) {
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
	
	function PanelButtons($on=true) {
		// ESC "c" "5" flag
		return "\x1B\x63\x35".chr( $on ? 0 : 1 );
	}
	
	function LineFeedBack() {
		// ESC "e" 1
		return "\x1B\x65\x01";
	}
	
	function DrawerKick($pin=2, $on=100, $off=100) {
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
	function CodeTable($table=0) {
		// ESC "t" table
		return "\x1B\x74".chr( max(0, min(255, $table)) );
	}
	
	function UpsideDown($on=true) {
		// ESC "{" flag
		return "\x1B\x7B".chr( $on ? 1 : 0 );
	}
	
	function CharacterZoom($horiz=1, $vert=1) {
		// GS "!" zoom
		return "\x1D\x21".chr(
			16 * (int)(max(1, min(8, $horiz)) - 1)
			+ (int)(max(1, min(8, $vert)) - 1)
		);
	}
	
	function GotoY($dots=0) {
		// GS "$" yLO yHI
		return ("\x1D\x24"
			.chr( max(0, (int)($dots % 256)) )
			.chr( max(0, min(255, (int)($dots / 256))) )
		);
	}
	
	function Test($type=3, $paper=0) {
		// GS "(" "A"
		return ("\x1D\x28\x41\x02\x00"
			.chr( max(0, min(2, (int)$paper)) )
			.chr( max(1, min(3, (int)$type)) )
		);
	}
	
	function Density($factor=1.0) {
		// GS "(" "K" \x02 \x00 \x31 factor
		// factor = 0.7 (\xFA) - 0.9 (\xFF) ; 1.0 (\x00) - 1.3 (\x06)
		return ("\x1D\x28\x4B\x02\x00\x31"
			.chr( (int)((256 + max(-6, min(6, (($factor - 1.0) * 20)))) % 256) )
		);
	}
	
	function ColorBlack() {
		// GS "(" "N" \x02 \x00 \x30 color
		// ESC "r" color
		return "\x1D\x28\x4E\x02\x00\x30\x31";
	}
	
	function ColorRed() {
		// GS "(" "N" \x02 \x00 \x30 color
		// ESC "r" color
		return "\x1D\x28\x4E\x02\x00\x30\x32";
	}
	
	function Invert($on=true) {
		// GS "B" flag
		return "\x1D\x42".chr( $on ? 1 : 0 );
	}
	
	function SpeedHigh() {
		// GS "E" speed
		return "\x1D\x45\x00";
	}
	
	function SpeedMedium() {
		// GS "E" speed
		return "\x1D\x45\x10";
	}
	
	function SpeedLow() {
		// GS "E" speed
		return "\x1D\x45\x20";
	}
	
	function BarcodeHRI($below=true, $above=false) {
		// GS "H" bitfield
		return ("\x1D\x48"
			.chr( ($below ? 2 : 0) + ($above ? 1 : 0) )
		);
	}
	
	function LeftMargin($dots=0) {
		// GS "L" marginLO marginHI
		return ("\x1D\x4C"
			.chr( max(0, (int)($dots % 256)) )
			.chr( max(0, min(255, (int)($dots / 256))) )
		);
	}
	
	function DotPitch($primary=0, $secondary=0) {
		// sets dot pitch to 1/Xth inch (25.4/Xth mm), or 0 for default
		// GS "P" pitch pitch
		return "\x1D\x50".chr($primary).chr($secondary);
	}
	
	function DiscardLine() {
		// GS "T" printbit
		return "\x1D\x54\x00";
	}
	
	function PreCutPaper($full=false) {
		// the cutter is above the print position, so cutting without feeding will put the cut above the last few lines printed
		// GS "V" bit
		// ESC "i"		(partial)
		// ESC "m"		(partial)
		return "\x1D\x56".chr( $full ? 0 : 1 );
	}
	
	function CutPaper($full=false, $feed=0) {
		// this version feeds the paper far enough to put the cutter just below the last line printed, plus the feed distance (in pixels?)
		// GS "V" bit feed
		return ("\x1D\x56"
			.chr( $full ? 65 : 66 )
			.chr( $feed )
		);
	}
	
	function PrintableWidth($dots=65535) {
		// GS "W" widthLO widthHI
		return ("\x1D\x57"
			.chr( max(0, (int)($dots % 256)) )
			.chr( max(0, min(255, (int)($dots / 256))) )
		);
	}
	
	function MoveY($dots) {
		// GS "\" dyLO dyHI
		if ($dots < 0 && $dots >= -32768)
			$dots += 65536;
		return ("\x1D\x5C"
			.chr( max(0, (int)($dots % 256)) )
			.chr( max(0, min(255, (int)($dots / 256))) )
		);
	}
	
	function Smooth($on=true) {
		// GS "b" flag
		return "\x1D\x62".chr( $on ? 1 : 0 );
	}
	
	function BarcodeHRIFont($font=0) {
		// GS "f" font
		return "\x1D\x66".chr( max(0, min(2, $font)) );
	}
	
	function BarcodeHeight($dots=162) {
		// GS "h" height
		return "\x1D\x68".chr( max(1, min(255, $dots)) );
	}
	
	function BarcodeUPC($data, $upcE=false) {
		$bytes = max(11, min(12, strlen($data)));
		return ("\x1D\x6B"
			.chr( $upcE ? 66 : 65 )
			.chr( $bytes )
			.str_pad(preg_replace('|[^0-9]|', '0', substr($data, 0, $bytes)), $bytes, '0', STR_PAD_LEFT)
		);
	}
	
	function BarcodeEAN($data, $ean8=false) {
		$bytes = $ean8 ? max(7, min(8, strlen($data))) : max(12, min(13, strlen($data)));
		return ("\x1D\x6B"
			.chr( $ean8 ? 68 : 67 )
			.chr( $bytes )
			.str_pad(preg_replace('|[^0-9]|', '0', substr($data, 0, $bytes)), $bytes, '0', STR_PAD_LEFT)
		);
	}
	
	function BarcodeCODE39($data) {
		$bytes = max(1, min(255, strlen($data)));
		return ("\x1D\x6B"
			.chr(69)
			.chr($bytes)
			.str_pad(preg_replace('|[^0-9A-Z $%+./-]|', ' ', substr($data, 0, $bytes)), $bytes, ' ', STR_PAD_LEFT)
		);
	}
	
	function BarcodeITF($data) {
		$bytes = max(2, min(254, (int)(strlen($data) / 2) * 2));
		return ("\x1D\x6B"
			.chr(70)
			.chr($bytes)
			.str_pad(preg_replace('|[^0-9]|', '0', substr($data, 0, $bytes)), $bytes, '0', STR_PAD_LEFT)
		);
	}
	
	function BarcodeCODEABAR($data) {
		$bytes = max(1, min(255, strlen($data)));
		return ("\x1D\x6B"
			.chr(71)
			.chr($bytes)
			.str_pad(preg_replace('|[^0-9A-D$+./:-]|', '0', substr($data, 0, $bytes)), $bytes, '0', STR_PAD_LEFT)
		);
	}
	
	function BarcodeCODE93($data) {
		$bytes = max(1, min(255, strlen($data)));
		return ("\x1D\x6B"
			.chr(72)
			.chr($bytes)
			.str_pad(preg_replace('|[^\\x00-\\x7f]|', "\x00", substr($data, 0, $bytes)), $bytes, "\x00", STR_PAD_LEFT)
		);
	}
	
	function BarcodeCODE128($data) {
		$bytes = max(2, min(255, strlen($data)));
		return ("\x1D\x6B"
			.chr(73)
			.chr($bytes)
			.str_pad(preg_replace('|[^\\x00-\\x7f]|', "\x00", substr($data, 0, $bytes)), $bytes, "\x00", STR_PAD_LEFT)
		);
	}
	
	/*
	Raster bitmaps may be up to 524280 pixels wide ((255+255*256)*8), and up to 2303 pixels tall (255+8*256).
	Tall dots are 2 pixels tall, and wide dots are 2 pixels wide.
	This function takes $width and $height as pixels, but the printer takes width as bytes (= 8 pixels).
	Data must have (($width * $height) / 8) bytes.
	The bits in each data byte specify dots from left-to-right, and bytes are left-to-right, top-to-bottom.
		\xC0 = \b11000000 = pixels in the left two columns
		\x81 = \b10000001 = pixels in the left and right columns
	*/
	function RasterBitmap($data, $width, $height, $tallDots=false, $wideDots=false) {
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
	
	function BarcodeWidth($scale=3) {
		// ($scale * 0.141mm) is the width per single code line, not the total code width
		// GS "w" scale
		return "\x1D\x77".chr( max(1, min(6, $scale)) );
	}

	/**
	  Write output to device
	  @param the output string
	*/
	function writeLine($text){
        ReceiptLib::writeLine($text);
	}

	/**
	  Draw bitmap from file
	  @param $fn a bitmap file
	  @return printer command string
	*/
	function RenderBitmapFromFile($fn, $align='C'){
		return $this->RenderBitmap($fn, $align);
	}

	/**
	  Turn bitmap into receipt string
	  @param $arg string filename OR Bitmap obj
	  @return receipt-formatted string
	*/
	function RenderBitmap($arg, $align='C'){
		$slip = "";

		if (!class_exists('Bitmap')) return "";

		$bmp = null;
		if (is_object($arg) && is_a($arg, 'Bitmap')){
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

		$slip .= $this->AlignCenter();
		if (count($stripes) > 1)
			$slip .= $this->LineSpacing(0);
		$slip .= implode("\n",$stripes);
		if (count($stripes) > 1)
			$slip .= $this->ResetLineSpacing()."\n";
		$slip .= $this->AlignLeft();

		return $slip;
	}
} 

?>
