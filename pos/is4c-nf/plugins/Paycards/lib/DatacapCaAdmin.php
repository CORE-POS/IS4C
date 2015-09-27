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

class DatacapCaAdmin extends LibraryClass
{
    public static function caLanguage()
    {
        if (CoreLocal::get('PaycardsDatacapMode') == 2) {
            return 'English';
        } elseif (CoreLocal::get('PaycardsDatacapMode') == 3) {
            return 'French';
        } else {
            return 'English';
        }
    }

    private static function basicAdminXml()
    {
        $e2e = new MercuryE2E();
        $termID = $e2e->getTermID();
        $operatorID = CoreLocal::get("CashierNo");
        $mcTerminalID = CoreLocal::get('PaycardsTerminalID');
        $refNum = $e2e->refnum(CoreLocal::get('LastID'));
        $dc_host = CoreLocal::get('PaycardsDatacapLanHost');
        if (empty($dc_host)) {
            $dc_host = '127.0.0.1';
        }
        $msgXml = '<?xml version="1.0"?'.'>
            <TStream>
            <Transaction>
            <HostOrIP>'.$dc_host.'</HostOrIP>
            <MerchantID>'.$termID.'</MerchantID>
            <TerminalID>'.$mcTerminalID.'</TerminalID>
            <OperatorID>'.$operatorID.'</OperatorID>
            <MerchantLanguage>'.self::caLanguage().'</MerchantLanguage>
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

    public static function keyChange()
    {
        return str_replace('{{TranCode}}', 'EMVKeyChange', self::basicAdminXml());
    }

    public static function paramDownload()
    {
        return str_replace('{{TranCode}}', 'EMVParamDownload', self::basicAdminXml());
    }

    public static function keyReport()
    {
        return str_replace('{{TranCode}}', 'EMVPublicKeyReport', self::basicAdminXml());
    }

    public static function statsReport()
    {
        return str_replace('{{TranCode}}', 'EMVStatisticsReport', self::basicAdminXml());
    }

    public static function declineReport()
    {
        return str_replace('{{TranCode}}', 'EMVOfflineDeclineReport', self::basicAdminXml());
    }

    public static function paramReport()
    {
        return str_replace('{{TranCode}}', 'EMVParameterReport', self::basicAdminXml());
    }

    public static function parseResponse($xml)
    {
        $xml = new BetterXmlData($xml);

        $status = $xml->query('/RStream/CmdResponse/CmdStatus');
        $msg_text = $xml->query('/RStream/CmdResponse/TextResponse');
        $printData = $xml->query('/RStream/PrintData/*', true);

        $ret = array(
            'status' => $status,
            'msg-text' => $msg_text,
            'receipt' => $printData,
        );

        return $ret;
    }
}

