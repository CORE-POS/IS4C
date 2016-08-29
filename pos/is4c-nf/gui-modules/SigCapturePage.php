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
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\UdpComm;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class SigCapturePage extends BasicCorePage 
{

    private $bmp_path;

    function head_content()
    {
        ?>
        <script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
        <script type="text/javascript">
        function parseWrapper(str) {
            if (str.substring(0, 7) == 'TERMBMP') {
                var fn = '<?php echo $this->bmp_path; ?>' + str.substring(7);
                $('<input>').attr({
                    type: 'hidden',
                    name: 'bmpfile',
                    value: fn
                }).appendTo('#formlocal');

                var img = $('<img>').attr({
                    src: fn,
                    width: 250 
                });
                $('#imgArea').append(img);
                $('.boxMsgAlert').html('Approve Signature');
                $('#sigInstructions').html('[enter] to approve, [clear] to cancel');
            } 
        }
        function addToForm(n, v) {
            $('<input>').attr({
                name: n,
                value: v,
                type: 'hidden'
            }).appendTo('#formlocal');
        }
        </script>
        <style type="text/css">
        #imgArea img { border: solid 1px; black; margin:5px; }
        </style>
        <?php
    }

    function preprocess()
    {
        $this->bmp_path = $this->page_url . 'scale-drivers/drivers/NewMagellan/ss-output/tmp/';

        $terminal_msg = 'termSig';
        $amt = FormLib::get('amt');
        if ($amt !== '') {
            if (FormLib::get('type') !== '') {
               $terminal_msg .= sprintf('%s: $.%2f', FormLib::get('type'), $amt);
            } else {
                $terminal_msg .= sprintf('Amount: $.%2f', $amt);
            }
        }

        if (FormLib::get('reginput', false) !== false) {
            $bmpfile = FormLib::get('bmpfile');
            if (strtoupper(FormLib::get('reginput')) === 'CL') {
                if ($bmpfile !== '' && file_exists($bmpfile)) {
                    unlink($bmpfile);
                }
                $this->change_page($this->page_url.'gui-modules/pos2.php');
                UdpComm::udpSend('termReset');

                return false;
            } elseif (FormLib::get('reginput', false) === '') {
                if ($bmpfile !== '' && file_exists($bmpfile)) {

                    // this should have been set already, but if we have sufficient info
                    // we can make sure it's correct.
                    $qstr = '';
                    if (FormLib::get('code') !== '') {
                        $qstr = '?reginput=' . urlencode((100*$amt) . FormLib::get('code'))
                            . '&repeat=1';
                    }

                    $bmp = file_get_contents($bmpfile);
                    $this->saveImage('BMP', $bmp);
                    unlink($bmpfile);

                    $this->change_page($this->page_url.'gui-modules/pos2.php' . $qstr);

                    return false;

                } else {
                    UdpComm::udpSend($terminal_msg);
                }
            }
        } else {
            UdpComm::udpSend($terminal_msg);
        }

        return true;
    }

    private function saveImage($format, $img_content)
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
            CoreLocal::get('CashierNo'),
            CoreLocal::get('laneno'),
            CoreLocal::get('transno'),
            CoreLocal::get('LastID') + 1,
            $format,
            $img_content,
        );
        $capR = $dbc->execute($capP, $args);

        return $capR ? true : false;
    }

    function body_content()
    {
        $this->input_header();
        echo DisplayLib::printheaderb();
        ?>
        <div class="baseHeight">
        <?php
        echo "<div id=\"boxMsg\" class=\"centeredDisplay\">";

        echo "<div class=\"boxMsgAlert coloredArea\">";
        echo "Waiting for signature";
        echo "</div>";

        echo "<div class=\"\">";

        echo "<div id=\"imgArea\"></div>";
        echo '<div class="textArea">';
        if (!isset($_REQUEST['amt'])) {
            $_REQUEST['amt'] = 0.00;
        }
        if (!isset($_REQUEST['type'])) {
            $_REQUEST['type'] = 'Unknown';
        }
        if (!isset($_REQUEST['code'])) {
            $_REQUEST['code'] = '??';
        }
        echo '$' . sprintf('%.2f', $_REQUEST['amt']) . ' as ' . $_REQUEST['type'];
        echo '<br />';
        echo '<span id="sigInstructions" style="font-size:90%;">';
        echo '[enter] to get re-request signature, [clear] to cancel';
        echo '</span>';
        echo "</div>";

        echo "</div>"; // empty class
        echo "</div>"; // #boxMsg
        echo "</div>"; // .baseHeight
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";

        $this->add_onload_command("addToForm('amt', '{$_REQUEST['amt']}');\n");
        $this->add_onload_command("addToForm('type', '{$_REQUEST['type']}');\n");
        $this->add_onload_command("addToForm('code', '{$_REQUEST['code']}');\n");
        
        CoreLocal::set("boxMsg",'');
    } // END body_content() FUNCTION
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
        $png_content = ob_get_clean();
        if ($success) {
            $format = 'PNG';
            $img_content = $png_content;
        }
    }
}
*/

