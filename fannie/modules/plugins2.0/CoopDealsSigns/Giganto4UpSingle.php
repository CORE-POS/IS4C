<?php

class Giganto4UpSingle extends COREPOS\Fannie\API\item\signage\Giganto4UpP
{
    protected $BIG_FONT = 130;
    protected $MED_FONT = 18;
    protected $SMALL_FONT = 14;
    protected $SMALLER_FONT = 11;
    protected $SMALLEST_FONT = 8;
    protected $BOGO_BIG_FONT = 100;
    protected $BOGO_MED_FONT = 28;

    protected $font = 'Arial';
    protected $alt_font = 'Arial';

    protected $width = 149;
    protected $height = 108;
    protected $top = 25;
    protected $left = 16;
    protected $bottomLineShift = 28;

    protected function createPDF()
    {
        $pdf = new \FPDF('L', 'mm', array(105, 148));
        $pdf->SetMargins(3.175, 3.175, 3.175);
        $pdf->SetAutoPageBreak(false);
        $pdf = $this->loadPluginFonts($pdf);
        $pdf->SetFont($this->font, '', 16);

        return $pdf;
    }

    public function drawPDF()
    {
        $pdf = $this->createPDF();

        $data = $this->loadItems();
        foreach ($data as $item) {
            $pdf->AddPage();
            $pdf = $this->drawItem($pdf, $item, 0, 0);
        }

        $pdf->Output('Giganto4UpP.pdf', 'I');
    }
}

