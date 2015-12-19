<?php
/**
 * @backupGlobals disabled
 */
class PrintHandlersTest extends PHPUnit_Framework_TestCase
{
    public function testBase()
    {
        $ph = new PrintHandler();
        $this->assertEquals("\t", $ph->Tab());
        $this->assertEquals("\n", $ph->LineFeed());
        $this->assertEquals("\n\n", $ph->LineFeed(2));
        $this->assertEquals("\r", $ph->CarriageReturn());

        $this->assertEquals(str_repeat(' ', 28) . 'foo', $ph->centerString('foo'));
        $this->assertEquals(str_repeat(' ', 13) . 'foo', $ph->centerString('foo', true));

        $blank_methods = array(
            'PageFeed',
            'ClearPage',
            'CharacterSpacing',
            'GotoX',
            'Underline',
            'ResetLineSpacing',
            'LineSpacing',
            'Reset',
            'SetTabs',
            'Bold',
            'DoublePrint',
            'PageMode',
            'Font',
            'CharacterSet',
            'LineMode',
            'PageOrient',
            'Rotate',
            'AlignLeft',
            'AlignCenter',
            'AlignRight',
            'PanelButtons',
            'LineFeedBack',
            'CodeTable',
            'UpsideDown',
            'GotoY',
            'Density',
            'ColorBlack',
            'ColorRed',
            'Invert',
            'SpeedHigh',
            'SpeedMedium',
            'SpeedLow',
            'LeftMargin',
            'DiscardLine',
            'PreCutPaper',
            'PrintableWidth',
            'Smooth',
            'BarcodeHRIFont',
            'BarcodeHeight',
            'BarcodeWidth',
        );
        foreach ($blank_methods as $m) {
            $this->assertEquals('', $ph->$m());
        }
    }

    public function testPdf()
    {
        $pdf = new PdfPrintHandler();
        $pdf->TextStyle(true, true, true, true, true);

        $this->assertEquals('', $pdf->Underline());
        $this->assertEquals('', $pdf->ResetLineSpacing());
        $this->assertEquals('', $pdf->LineSpacing());
        $this->assertEquals('', $pdf->Bold());
        $this->assertEquals('', $pdf->AlignLeft());
        $this->assertEquals('', $pdf->AlignCenter());
        $this->assertEquals('', $pdf->AlignRight());
        $this->assertEquals('', $pdf->ColorBlack());
        $this->assertEquals('', $pdf->ColorRed());
        $this->assertEquals('', $pdf->RenderBitmapFromFile('fake-file'));
    }

    public function testHtml()
    {
        $html = new HtmlEmailPrintHandler();
        $this->assertEquals('<div style="text-align:center">foo</div>', $html->centerString('foo'));
        $this->assertEquals('<div style="text-align:center"><strong>foo</strong></div>', $html->centerString('foo', true));
        $this->assertEquals('<!-- foo -->', $html->addRenderingSpacer('foo'));
    }

    public function testEmail()
    {
        $mod = new EmailPrintHandler();
        $this->assertEquals('    ', $mod->Tab());
        $this->assertEquals('', $mod->CarriageReturn());
        $this->assertEquals('', $mod->RenderBitmapFromFile('fake-file'));
        $this->assertEquals(str_repeat(' ', 28) . 'foo', $mod->centerString('foo'));
    }

    public function testEsc()
    {
        $ph = new ESCPOSPrintHandler();
        $blank_methods = array(
            'PageFeed',
            'ClearPage',
            'CharacterSpacing',
            'GotoX',
            'Underline',
            'ResetLineSpacing',
            'LineSpacing',
            'Reset',
            'SetTabs',
            'Bold',
            'DoublePrint',
            'PageMode',
            'Font',
            'CharacterSet',
            'LineMode',
            'PageOrient',
            'Rotate',
            'AlignLeft',
            'AlignCenter',
            'AlignRight',
            'PanelButtons',
            'LineFeedBack',
            'CodeTable',
            'UpsideDown',
            'GotoY',
            'Density',
            'ColorBlack',
            'ColorRed',
            'Invert',
            'SpeedHigh',
            'SpeedMedium',
            'SpeedLow',
            'LeftMargin',
            'DiscardLine',
            'PreCutPaper',
            'PrintableWidth',
            'Smooth',
            'BarcodeHRIFont',
            'BarcodeHeight',
            'BarcodeWidth',
        );
        foreach ($blank_methods as $m) {
            $this->assertNotEquals('', $ph->$m());
        }

    }
}

