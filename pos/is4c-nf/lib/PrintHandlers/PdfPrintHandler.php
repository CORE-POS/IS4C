<?php
/**
 @class PdfPrintHandler
*/

class PdfPrintHandler extends PrintHandler 
{
    private $instance = null;
    private $line_height = 5;
    private $align = 'L';

    public function __construct()
    {
        if (class_exists('fpdf\FPDF')) {
            $this->instance = new fpdf\FPDF('P', 'mm', 'Letter');
            $this->instance->AddPage();
            $this->TextStyle();
        }
    }

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
        $instance->AddPage();
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
        return '';
    }
    
    function CharacterSpacing($dots=0) {
        // ESC " " space
        return '';
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
    function TextStyle($altFont=false, $bold=false, $tall=false, $wide=false, $underline=false) 
    {
        $family = 'Courier';
        $style = '';
        if ($bold) {
            $style .= 'B';
        }
        if ($underline) {
            $style .= 'U';
        }
        if ($altFont) {
            $style .- 'I';
        }
        $size = 10;
        if ($tall || $wide) {
            $size = 16;
        }
        if (is_object($this->instance)) {
            $this->instance->SetFont($family, $style, $size);
        }
    }
    
    function GotoX($dots=0) 
    {
        return '';
    } // GotoX()
    
    function Underline($dots=1) 
    {
        $this->TextStyle(false, false, false, false, true);
        return '';
    }
    
    function ResetLineSpacing() 
    {
        $this->line_height = 5;
        return '';
    }
    
    function LineSpacing($space=64) 
    {
        $this->line_height = $space;
        return '';
    }
    
    function Reset() 
    {
        return '';
    }
    
    function SetTabs($tabs=null) 
    {
        return '';
    }
    
    /**
     Enable or disable bold font
     @param $on boolean enable
     @return string printer command
    */
    function Bold($on=true) 
    {
        $this->TextStyle(false, true);
        return '';
    }
    
    function DoublePrint($on=true) 
    {
        return '';
    }
    
    function PaperFeed($space) 
    {
        return '';
    }
    
    function PaperFeedBack($space) 
    {
        return '';
    }
    
    function PageMode() 
    {
        return '';
    }
    
    function Font($font=0) 
    {
        return '';
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
    function CharacterSet($set=0) 
    {
        return '';
    }
    
    function LineMode() 
    {
        return '';
    }
    
    function PageOrient($orient=0) 
    {
        return '';
    }
    
    // TODO: unidirectional printing;  ESC(\x1B) "U"(\x55) bit
    
    function Rotate($on=true) 
    {
        return '';
    }
    
    function PageRegion($x=0, $y=0, $dx=65535, $dy=65535) 
    {
        return '';
    }
    
    function MoveX($dots) 
    {
        return '';
    }
    
    function AlignLeft() 
    {
        $this->align = 'L';
        return '';
    }
    
    function AlignCenter() 
    {
        $this->align = 'C';
        return '';
    }
    
    function AlignRight() 
    {
        $this->align = 'R';
        return '';
    }
    
    function PaperRoll($receipt=true, $journal=false, $endorse=false, $validation=false) 
    {
        return '';
    } // PaperRoll()
    
    function PanelButtons($on=true) 
    {
        return '';
    }
    
    function LineFeedBack() 
    {
        return '';
    }
    
    function DrawerKick($pin=2, $on=100, $off=100) 
    {
        return '';
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
    function CodeTable($table=0) 
    {
        return '';
    }
    
    function UpsideDown($on=true) 
    {
        return '';
    }
    
    function CharacterZoom($horiz=1, $vert=1) 
    {
        return '';
    }
    
    function GotoY($dots=0) 
    {
        return '';
    }
    
    function Test($type=3, $paper=0) 
    {
        return '';
    }
    
    function Density($factor=1.0) 
    {
        return '';
    }
    
    function ColorBlack() 
    {
        if (is_object($this->instance)) {
            $this->instance->SetTextColor(0, 0, 0);
        }
        return '';
    }
    
    function ColorRed() 
    {
        if (is_object($this->instance)) {
            $this->instance->SetTextColor(0xff, 0, 0);
        }
        return '';
    }
    
    function Invert($on=true) 
    {
        return '';
    }
    
    function SpeedHigh() 
    {
        return '';
    }
    
    function SpeedMedium() 
    {
        return '';
    }
    
    function SpeedLow() {
        return '';
    }
    
    function BarcodeHRI($below=true, $above=false) 
    {
        return '';
    }
    
    function LeftMargin($dots=0) 
    {
        return '';
    }
    
    function DotPitch($primary=0, $secondary=0) 
    {
        return '';
    }
    
    function DiscardLine() 
    {
        return '';
    }
    
    function PreCutPaper($full=false) 
    {
        return '';
    }
    
    function CutPaper($full=false, $feed=0) 
    {
        return '';
    }
    
    function PrintableWidth($dots=65535) 
    {
        return '';
    }
    
    function MoveY($dots) 
    {
        return '';
    }
    
    function Smooth($on=true) 
    {
        return '';
    }
    
    function BarcodeHRIFont($font=0) 
    {
        return '';
    }
    
    function BarcodeHeight($dots=162) 
    {
        return '';
    }
    
    function BarcodeUPC($data, $upcE=false) 
    {
        return '';
    }
    
    function BarcodeEAN($data, $ean8=false) 
    {
        return '';
    }
    
    function BarcodeCODE39($data) 
    {
        return '';
    }
    
    function BarcodeITF($data) 
    {
        return '';
    }
    
    function BarcodeCODEABAR($data) 
    {
        return '';
    }
    
    function BarcodeCODE93($data) 
    {
        return '';
    }
    
    function BarcodeCODE128($data) 
    {
        return '';
    }
    
    function BarcodeWidth($scale=3) 
    {
        return '';
    }

    /**
      Write output to device
      @param the output string
    */
    function writeLine($text)
    {
        if (is_object($this->instance)) {
            $this->instance->MultiCell(0, $this->line_height, $text, '', $this->align);
            $this->instance->Output('receipt.pdf', 'F');
        }
    }

    /**
      Draw bitmap from file
      @param $fn a bitmap file
      @return printer command string
    */
    function RenderBitmapFromFile($fn, $align='C')
    {
        return $this->RenderBitmap($fn, $align);
    }

    /**
      Turn bitmap into receipt string
      @param $arg string filename OR Bitmap obj
      @return receipt-formatted string
    */
    function RenderBitmap($arg, $align='C')
    {
        return '';
    }

    /**
      Wrapper for raw ESC byte strings so 
      subclass handlers can decide whether they're
      compatible
      @param $command [string] command bytes
      @return [string] receipt text
    */
    public function rawEscCommand($command)
    {
        return '';
    }

    /**
      Show bitmap stored on the printer device itself
      @param $image_id [int] storage location ID
      @return [string] receipt text
    */
    public function renderBitmapFromRam($image_id)
    {
        return '';
    }

    /**
      Insert a chunk of information into the
      receipt that writeLine() will later use
      during rendering. By default adds nothing.
    */
    public function addRenderingSpacer($str)
    {
        return '';
    }
} 

