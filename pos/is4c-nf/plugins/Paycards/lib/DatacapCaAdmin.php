<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

use COREPOS\pos\plugins\Paycards\xml\BetterXmlData;

class DatacapCaAdmin 
{
    public function __construct()
    {
        $this->conf = new PaycardConf();
    }

    public function caLanguage()
    {
        if ($this->conf->get('PaycardsDatacapMode') == 2) {
            return 'English';
        } elseif ($this->conf->get('PaycardsDatacapMode') == 3) {
            return 'French';
        }

        return 'English';
    }

    private function basicAdminXml()
    {
        $e2e = new MercuryE2E();
        $termID = $e2e->getTermID();
        $operatorID = $this->conf->get("CashierNo");
        $mcTerminalID = $this->conf->get('PaycardsTerminalID');
        $refNum = $e2e->refnum($this->conf->get('LastID'));
        $dcHost = $this->conf->get('PaycardsDatacapLanHost');
        if (empty($dcHost)) {
            $dcHost = '127.0.0.1';
        }
        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <HostOrIP>'.$dcHost.'</HostOrIP>
            <MerchantID>'.$termID.'</MerchantID>
            <TerminalID>'.$mcTerminalID.'</TerminalID>
            <OperatorID>'.$operatorID.'</OperatorID>
            <MerchantLanguage>'.$this->caLanguage().'</MerchantLanguage>
            <TranCode>{{TranCode}}</TranCode>
            <SecureDevice>{{SecureDevice}}</SecureDevice>
            <ComPort>{{ComPort}}</ComPort>
            <SequenceNo>{{SequenceNo}}</SequenceNo>
            <InvoiceNo>'.$refNum.'</InvoiceNo>
            <RefNo>'.$refNum.'</RefNo>
            </Transaction>
            </TStream>';

        return $msgXml;
    }

    public function keyChange()
    {
        return str_replace('{{TranCode}}', 'EMVKeyChange', $this->basicAdminXml());
    }

    public function paramDownload()
    {
        return str_replace('{{TranCode}}', 'EMVParamDownload', $this->basicAdminXml());
    }

    public function keyReport()
    {
        return str_replace('{{TranCode}}', 'EMVPublicKeyReport', $this->basicAdminXml());
    }

    public function statsReport()
    {
        return str_replace('{{TranCode}}', 'EMVStatisticsReport', $this->basicAdminXml());
    }

    public function declineReport()
    {
        return str_replace('{{TranCode}}', 'EMVOfflineDeclineReport', $this->basicAdminXml());
    }

    public function paramReport()
    {
        return str_replace('{{TranCode}}', 'EMVParameterReport', $this->basicAdminXml());
    }

    public function parseResponse($xml)
    {
        $xml = new BetterXmlData($xml);

        $status = $xml->query('/RStream/CmdResponse/CmdStatus');
        $msgText = $xml->query('/RStream/CmdResponse/TextResponse');
        $printData = $xml->query('/RStream/PrintData/*', true);

        $ret = array(
            'status' => $status,
            'msg-text' => $msgText,
            'receipt' => $printData,
        );

        return $ret;
    }
}

