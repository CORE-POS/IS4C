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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class SigCapturePage extends BasicPage 
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
		global $CORE_LOCAL;

        $this->bmp_path = $this->page_url . 'scale-drivers/drivers/NewMagellan/ss-output/tmp/';

        $terminal_msg = 'termSig';
        if (isset($_REQUEST['amt']) && isset($_REQUEST['type'])) {
            $terminal_msg .= $_REQUEST['type'] . sprintf(': $%.2f', $_REQUEST['amt']);
        } else if (isset($_REQUEST['amt'])) {
            $terminal_msg .= sprintf('Amount: $.%2f', $_REQUEST['amt']);
        }

        if (isset($_REQUEST['reginput'])) {
            if (strtoupper($_REQUEST['reginput']) == 'CL') {
                if (isset($_REQUEST['bmpfile']) && file_exists($_REQUEST['bmpfile'])) {
                    unlink($_REQUEST['bmpfile']);
                }
                $this->change_page($this->page_url.'gui-modules/pos2.php');
                UdpComm::udpSend('termReset');

                return false;
            } else if ($_REQUEST['reginput'] == '') {
                if (isset($_REQUEST['bmpfile']) && file_exists($_REQUEST['bmpfile'])) {

                    // this should have been set already, but if we have sufficient info
                    // we can make sure it's correct.
                    if (isset($_REQUEST['amt']) && !empty($_REQUEST['amt']) && isset($_REQUEST['code']) && !empty($_REQUEST['code'])) {
                        $CORE_LOCAL->set('strRemembered', (100*$_REQUEST['amt']) . $_REQUEST['code']);
                    }
                    $CORE_LOCAL->set('msgrepeat', 1);

                    $bmp = file_get_contents($_REQUEST['bmpfile']);
                    $format = 'BMP';
                    $img_content = $bmp;

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

                    $dbc = Database::tDataConnect();
                    $capQ = 'INSERT INTO CapturedSignature
                                (tdate, emp_no, register_no, trans_no,
                                 trans_id, filetype, filecontents)
                             VALUES
                                (?, ?, ?, ?,
                                 ?, ?, ?)';
                    $capP = $dbc->prepare_statement($capQ);
                    Database::getsubtotals();
                    $args = array(
                        date('Y-m-d H:i:s'),
                        $CORE_LOCAL->get('CashierNo'),
                        $CORE_LOCAL->get('laneno'),
                        $CORE_LOCAL->get('transno'),
                        $CORE_LOCAL->get('LastID') + 1,
                        $format,
                        $img_content,
                    );
                    $capR = $dbc->exec_statement($capP, $args);

                    unlink($_REQUEST['bmpfile']);

                    $this->change_page($this->page_url.'gui-modules/pos2.php');

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

	function body_content(){
		global $CORE_LOCAL;
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
		
		$CORE_LOCAL->set("boxMsg",'');
		$CORE_LOCAL->set("msgrepeat",2);
	} // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new SigCapturePage();

?>
