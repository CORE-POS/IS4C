<?php
use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\UdpComm;

include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class InterceptPage extends BasicCorePage 
{
    protected $boxColor;
    protected $msg;

    function preprocess()
    {
        $this->boxColor="coloredArea";
        $this->msg = _("number of bags");

        $qtty = strtoupper(trim($this->form->tryGet('reginput')));
        if ($qtty == "CL") {
            /**
              Clear cancels
            */
            $this->session->set('InterceptedCommand', '');
            $this->session->set('Intercepted', 1);
            $this->change_page($this->page_url."gui-modules/pos2.php");
            return false;
        } elseif (is_numeric($qtty) && $qtty < 9999 && $qtty >= 0) {
            // TODO: add bags to the transaction
            $this->session->set('ttlflag', 1);
            $inp = $this->session->get('InterceptedCommand');
            $this->session->set('InterceptedCommand', '');
            $this->session->set('Intercepted', 1);
            $this->change_page(
                $this->page_url
                . "gui-modules/pos2.php"
                . '?reginput=' . $inp
                . '&repeat=1');

            return false;
        } elseif ($qtty !== '') {
            $this->boxColor="errorColoredArea";
            $this->msg = _("invalid quantity");
        }

        return true;
    }

    function head_content()
    {
        $this->noscan_parsewrapper_js();
    }

    function body_content()
    {
        $this->input_header();
        echo DisplayLib::printheaderb();

        ?>
        <div class="baseHeight">
        <div class="<?php echo $this->boxColor; ?> centeredDisplay">
        <span class="larger">
        <?php echo $this->msg ?>
        </span><br />
        <p>
        <?php echo _("enter quantity or clear to cancel"); ?>
        </p> 
        </div>
        </div>

        <?php
        UdpComm::udpSend('errorBeep');
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";
    } // END true_body() FUNCTION
}

AutoLoader::dispatch();

