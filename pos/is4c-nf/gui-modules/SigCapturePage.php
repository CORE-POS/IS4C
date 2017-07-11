<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\UdpComm;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class SigCapturePage extends BasicCorePage 
{
    private $bmpPath;

    function head_content()
    {
        $receipt = ReceiptLib::receiptNumber();
        $this->addOnloadCommand("sigCapture.init('{$this->page_url}', '{$receipt}', '{$this->bmpPath}');");
        $this->addOnloadCommand("sigCapture.addOption('[Clear] to cancel');");
        ?>
        <script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
        <script type="text/javascript" src="<?php echo $this->page_url; ?>js/sigCapture.js"></script>
        <script type="text/javascript">
        function parseWrapper(str) {
            return sigCapture.parseWrapper(str);
        }
        </script>
        <style type="text/css">
        #imgArea img { border: solid 1px; black; margin:5px; }
        </style>
        <?php
    }

    function preprocess()
    {
        $this->bmpPath = $this->page_url . 'scale-drivers/drivers/NewMagellan/ss-output/tmp/';

        $terminalMsg = 'termSig';
        $amt = $this->form->tryGet('amt');
        if ($amt !== '') {
            $type = $this->form->tryGet('type');
            $terminalMsg .= $type !== '' ? sprintf('%s: $.%2f', $type, $amt) : sprintf('Amount: $.%2f', $amt);
        }

        $input = $this->form->tryGet('reginput', false);
        $bmpfile = $this->form->tryGet('bmpfile');
        if (strtoupper($input) === 'CL') {
            /**
             * Cancel request. Delete signature if necessary, reset terminal, go home
             */
            if ($bmpfile !== '' && file_exists($bmpfile)) {
                unlink($bmpfile);
            }
            $this->change_page($this->page_url.'gui-modules/pos2.php');
            UdpComm::udpSend('termReset');

            return false;
        } elseif ($input === '') {
            if ($bmpfile !== '' && file_exists($bmpfile)) {

                // this should have been set already, but if we have sufficient info
                // we can make sure it's correct.
                $qstr = '';
                if ($this->form->tryGet('code') !== '') {
                    $qstr = '?reginput=' . urlencode((100*$amt) . $this->form->code)
                        . '&repeat=1';
                }

                $bmp = file_get_contents($bmpfile);
                $this->saveImage('BMP', $bmp);
                unlink($bmpfile);

                $this->change_page($this->page_url.'gui-modules/pos2.php' . $qstr);

                return false;
            }
        } elseif ($input === false || strtoupper($input) === 'TL') {
            if ($bmpfile !== '' && file_exists($bmpfile)) {
                unlink($bmpfile);
            }
            UdpComm::udpSend($terminalMsg);
        }

        return true;
    }

    private function saveImage($format, $imgContent)
    {
        $dbc = Database::tDataConnect();
        $capQ = 'INSERT INTO CapturedSignature
                    (tdate, emp_no, register_no, trans_no,
                     trans_id, filetype, filecontents)
                 VALUES
                    (?, ?, ?, ?,
                     ?, ?, ?)';
        $capP = $dbc->prepare($capQ);
        Database::getsubtotals();
        $args = array(
            date('Y-m-d H:i:s'),
            $this->session->get('CashierNo'),
            $this->session->get('laneno'),
            $this->session->get('transno'),
            $this->session->get('LastID') + 1,
            $format,
            $imgContent,
        );
        $capR = $dbc->execute($capP, $args);

        return $capR ? true : false;
    }

    function body_content()
    {
        $this->input_header("onsubmit=\"return sigCapture.submitWrapper();\" action=\"".filter_input(INPUT_SERVER, 'PHP_SELF')."\"");
        echo DisplayLib::printheaderb();
        ?>
        <div class="baseHeight">
        <?php
        echo "<div id=\"boxMsg\" class=\"centeredDisplay\">";

        echo "<div class=\"boxMsgAlert coloredArea\">";
        echo _("Waiting for signature");
        echo "</div>";

        echo "<div class=\"\">";

        echo "<div id=\"imgArea\"></div>";
        echo '<div class="textArea">';
        $amt = $this->form->tryGet('amt', 0.00);
        $type = $this->form->tryGet('type', 'Unknown');
        $code = $this->form->tryGet('code', '??');
        echo '$' . sprintf('%.2f', $amt) . ' as ' . $type;
        echo '<br />';
        echo '<span id="sigInstructions" style="font-size:90%;">';
        echo _('[subtotal] to get re-request signature, [clear] to cancel');
        echo '</span>';
        echo "</div>";

        echo "</div>"; // empty class
        echo "</div>"; // #boxMsg
        echo "</div>"; // .baseHeight
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";

        $this->add_onload_command("sigCapture.addToForm('amt', '{$amt}');\n");
        $this->add_onload_command("sigCapture.addToForm('type', '{$type}');\n");
        $this->add_onload_command("sigCapture.addToForm('code', '{$code}');\n");
        $this->add_onload_command("sigCapture.addToForm('doCapture', 1);\n");
        
        $this->session->set("boxMsg",'');
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        $phpunit->assertEquals(true, $this->saveImage('bmp', 'fakeContent'));
    }
}

AutoLoader::dispatch();

/**
  Idea: convert image to PNG if GD functions
  are available. It would reduce storage size
  but also make printing the image more complicated
  since it would need to be converted *back* to
  a bitmap. Undecided whether to use this.
  Maybe reformatting happens server-side for
  long term storage.

  Update: does not work with GD. That extension
  does not understand bitmaps. Same idea may
  work with a different library like ImageMagick.
if (function_exists('imagecreatefromstring')) {
    $image = imagecreatefromstring($bmp);
    if ($image !== false) {
        ob_start();
        $success = imagepng($image);
        $pngContent = ob_get_clean();
        if ($success) {
            $format = 'PNG';
            $imgContent = $pngContent;
        }
    }
}
*/

