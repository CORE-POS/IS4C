<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op
    Modifications copyright 2010 Whole Foods Co-op

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

use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\plugins\Paycards\xml\BetterXmlData;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardEmvBatch extends PaycardProcessPage 
{
    private $prompt = false;
    private $runTransaction = false;
    private $mode = 'gettingSummary';

    function preprocess()
    {
        if (FormLib::get('reginput', false) !== false) { // User input
            $input = FormLib::get('reginput');
            if (trim(strtoupper($input)) == 'CL') {
                $this->clearBatchData();
                $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php');
                return false;
            }

            if (trim($input) == '' && $this->conf->get('PaycardBatchItemCount')) {
                $this->requestClose();
                return true;
            }

        } elseif (FormLib::get('xml-resp', false) !== false) { // XML response
            $xml = FormLib::get('xml-resp');
            if (strstr($xml, '<BatchSummary>')) {
                return $this->processSummaryResponse($xml);
            } elseif (strstr($xml, '<BatchClose>')) {
                return $this->processCloseResponse($xml);
            }
            return $this->genericError();
        }

        $this->requestSummary();
        return true;
    }

    private function genericError()
    {
        $this->conf->set(_('An error occurred at the gateway'));
        $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
        return false;
    }

    private function clearBatchData()
    {
        $this->conf->set('PaycardBatchNo', '');
        $this->conf->set('PaycardBatchItemCount', '');
        $this->conf->set('PaycardNetBatchTotal', '');
    }

    private function requestSummary()
    {
        $e2e = new MercuryDC();
        $this->xml = $e2e->batchXmlInit('BatchSummary') . '</Admin></TStream>';
        $this->runTransaction = true;
    }

    private function processSummaryResponse($xml)
    {
        $xml = new BetterXmlData($xml);
        $batchNo = $xml->query('/RStream/BatchSummary/BatchNo');
        $batchItemCount = $xml->query('/RStream/BatchSummary/BatchItemCount');
        $netBatchTotal = $xml->query('/RStream/BatchSummary/NetBatchTotal');
        if ($batchItemCount == 0) {
            $this->conf->set('boxMsg', 'Batch is empty');
            $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
            return false;
        }

        $this->conf->set('PaycardBatchNo', $batchNo);
        $this->conf->set('PaycardBatchItemCount', $batchItemCount);
        $this->conf->set('PaycardNetBatchTotal', $netBatchTotal);
        $this->runTransaction = false;
        $this->mode = 'haveSummary';

        return true;
    }

    private function requestClose()
    {
        $e2e = new MercuryDC();
        $this->xml = $e2e->batchXmlInit('BatchClose') 
            . '<BatchNo>' . $this->conf->get('PaycardBatchNo') . '</BatchNo>'
            . '<BatchItemCount>' . $this->conf->get('PaycardBatchItemCount') . '</BatchItemCount>'
            . '<NetBatchTotal>' . $this->conf->get('PaycardNetBatchTotal') . '</NetBatchTotal>'
            . '</Admin></TStream>';
        $this->runTransaction = true;
        $this->mode = 'closingBatch';
    }

    private function processCloseResponse($xml)
    {
        $this->clearBatchData();
        $xml = new BetterXmlData($xml);
        $status = $xml->query('/RStream/CmdResponse/CmdStatus');
        if (strtoupper($status) == 'SUCCESS') {
            $this->conf->set('boxMsg', _('Batch closed successfully'));
        } else {
            $err = _('Batch close failed');
            $err .= ' (' . $xml->query('/RStream/CmdResponse/TextResponse') . ')';
            $this->conf->set('boxMsg', $err);
        }
        $this->conf->set('boxMsg', 'Batch is empty');
        $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
        return false;
    }

    function head_content()
    {
        $url = MiscLib::baseURL();
        echo '<script type="text/javascript" src="' . $url . '/js/singleSubmit.js"></script>';
        echo '<script type="text/javascript" src="../js/emv.js?date=20180308"></script>';
        if (!$this->runTransaction) {
            return '';
        }
        $e2e = new MercuryDC();
        ?>
<script type="text/javascript">
function emvSubmit() {
    $('div.baseHeight').html('Processing transaction');
    // POST XML request to driver using AJAX
    var xmlData = '<?php echo json_encode($this->xml); ?>';
    if (xmlData == '"Error"') { // failed to save request info in database
        location = '<?php echo MiscLib::baseURL(); ?>gui-modules/boxMsg2.php';
        return false;
    }
    emv.submit(xmlData);
}
</script>
        <?php
    }

    function body_content()
    {
        echo '<div class="baseHeight">';
        switch ($this->mode) {
            case 'gettingSummary':
            case 'closingBatch':
            default:
                echo _('Processing...');
                break;
            case 'haveSummary':
                echo PaycardLib::paycardMsgBox(
                    sprintf('Close current batch? ($%.2f)', $this->conf->get('PaycardNetBatchTotal')),
                    "",
                    _("[enter] to continue<br />[clear] to cancel")
                );
                break;
        }
        echo '</div>';
    }
}

AutoLoader::dispatch();

