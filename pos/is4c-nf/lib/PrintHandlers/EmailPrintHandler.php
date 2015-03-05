<?php
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
	function Tab() {
		return "    ";
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

	function centerString($text,$big=false){
		$width = 60;

		$blank = str_repeat(" ", $width);
		$text = trim($text);
		$lead = (int) (($width - strlen($text)) / 2);
		$newline = substr($blank, 0, $lead).$text;
		return $newline;
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
			
			$headers = "From: ".CoreLocal::get("emailReceiptFrom");

			mail($to, $subject, $text, $headers);
		}
	}

	function RenderBitmapFromFile($fn, $align='C')
    {
		return '';
	}
} 

