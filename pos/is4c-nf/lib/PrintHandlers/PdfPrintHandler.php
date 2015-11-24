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

    function PageFeed($reset=true) {
        $instance->AddPage();
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
            $style .= 'I';
        }
        $size = 10;
        if ($tall || $wide) {
            $size = 16;
        }
        if (is_object($this->instance)) {
            $this->instance->SetFont($family, $style, $size);
        }
    }
    
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
} 

