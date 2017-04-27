<?php

use COREPOS\pos\lib\PrintHandlers\PrintHandler;

/**
 * @backupGlobals disabled
 */
class PrintHandlersTest extends PHPUnit_Framework_TestCase
{
    public function testBase()
    {
        $ph = new COREPOS\pos\lib\PrintHandlers\PrintHandler();
        $this->assertEquals("\t", $ph->Tab());
        $this->assertEquals("\n", $ph->LineFeed());
        $this->assertEquals("\n\n", $ph->LineFeed(2));
        $this->assertEquals("\r", $ph->CarriageReturn());

        $this->assertEquals(str_repeat(' ', 28) . 'foo', $ph->centerString('foo'));
        $this->assertEquals(str_repeat(' ', 28) . 'foo', $ph->centerString('foo', true));

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
        $pdf = new COREPOS\pos\lib\PrintHandlers\PdfPrintHandler();
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
        $html = new COREPOS\pos\lib\PrintHandlers\HtmlEmailPrintHandler();
        $this->assertEquals('<div style="text-align:center">foo</div>', $html->centerString('foo'));
        $this->assertEquals('<!-- foo -->', $html->addRenderingSpacer('foo'));
    }

    public function testEmail()
    {
        $mod = new COREPOS\pos\lib\PrintHandlers\EmailPrintHandler();
        $this->assertEquals('    ', $mod->Tab());
        $this->assertEquals('', $mod->CarriageReturn());
        $this->assertEquals('', $mod->RenderBitmapFromFile('fake-file'));
        $this->assertEquals(str_repeat(' ', 28) . 'foo', $mod->centerString('foo'));
    }

    public function testEsc()
    {
        $ph = new COREPOS\pos\lib\PrintHandlers\ESCPOSPrintHandler();
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

        $ph->Tab();
        $ph->LineFeed(1);
        $ph->LineFeed(2);
        $ph->CarriageReturn();
        $ph->InlineBitmap('12345', 2);
        $ph->SetTabs(array(1,2));
        $ph->PaperFeed(1);
        $ph->PaperFeedBack(1);
        $ph->PageRegion();
        $ph->MoveX(-1);
        $ph->PaperRoll();
        $ph->DrawerKick();
        $ph->CharacterZoom();
        $ph->Test();
        $ph->BarcodeHRI();
        $ph->DotPitch();
        $ph->CutPaper();
        $ph->MoveY(-1);
        $ph->printBarcode(PrintHandler::BARCODE_UPCA, '123456789012');
        $ph->printBarcode(PrintHandler::BARCODE_UPCE, '123456789012');
        $ph->printBarcode(PrintHandler::BARCODE_EAN13, '1234567890123');
        $ph->printBarcode(PrintHandler::BARCODE_EAN8, '1234567890123');
        $ph->printBarcode(PrintHandler::BARCODE_ITF, '1234567890123');
        $ph->printBarcode(PrintHandler::BARCODE_CODEABAR, '1234567890123');
        $ph->printBarcode(PrintHandler::BARCODE_CODE39, '1234567890123');
        $ph->printBarcode(PrintHandler::BARCODE_CODE93, '1234567890123');
        $ph->printBarcode(PrintHandler::BARCODE_CODE128, '1234567890123');
        $ph->RasterBitmap('123456', 1, 1);
        $fn = dirname(__FILE__) . '/../../pos/is4c-nf/graphics/WfcLogo2014';
        $ph->RenderBitmapFromFile($fn);
        $ph->RenderBitmapFromRam(123);
    }
}

