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

use COREPOS\pos\lib\AjaxCallback;
use COREPOS\pos\plugins\Paycards\xml\BetterXmlData;

if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

/**
 * Mock processor server.
 *
 * In training mode transaction requests can be handled locally
 * instead of forwarded to the real processor. This bypasses
 * the hardware terminal entirely so there's no card data
 * involved.
 */
class AjaxPaycardTest extends AjaxCallback
{
    protected $encoding = 'plain';

    protected $RESPONSE = '
        <RStream>
            <CmdResponse>
                <CmdStatus>{STATUS}</CmdStatus>
                <DSIXReturnCode>1</DSIXReturnCode>
                <TextResponse>{TEXT}</TextResponse>
            </CmdResponse>
            <TranResponse>
                {AuthCode}
                {TranCode}
                {TranType}
                {CardType}
                <RefNo>12345</RefNo>
                <Amount>
                    <Balance>10</Balance>
                    <Authorize>{AMOUNT}</Authorize>
                </Amount>
                <RecordNo>TEST</RecordNo>
                <ProcessData>TEST</ProcessData>
                <AcqRefData>TEST</AcqRefData>
                <CardholderName>TEST/TRANS</CardholderName>
                <AcctNo>4111********1111</AcctNo>
                <EntryMethod>Swiped</EntryMethod>
            </TranResponse>
            <PrintData>
                <Line>JUST</Line>
                <Line>TESTING</Line>
            </PrintData>
        </RStream>';

    public function ajax($input=array())
    {
        $post = trim(file_get_contents('php://input'));
        $post = trim($post, '"');
        $xml = new BetterXmlData($post);
        $empNo = $xml->query('/TStream/Transaction/OperatorID');
        $amount = $xml->query('/TStream/Transaction/Amount/Purchase');
        $invoice = $xml->query('/TStream/Transaction/RefNo');
        $code = $xml->query('/TStream/Transaction/TranCode');
        $type = $xml->query('/TStream/Transaction/TranType');
        $card = $xml->query('/TStream/Transaction/TranType');

        // always decline if not the training cashier
        // approve training cashier unless special amounts
        $responseType = 'DECLINE';
        if ($empNo == '9999') {
            $responseType = 'APPROVE';
            if ($amount == 50) {
                $responseType = 'DECLINE';
            } elseif ($amount == 100) {
                $responseType = 'PARTIAL';
                $amount = 80;
            }
        }

        $code = strlen($code) > 0 ? "<TranCode>{$code}</TranCode>" : '';
        $type = strlen($type) > 0 ? "<TranType>{$type}</TranType>" : '';
        $card = strlen($card) > 0 ? "<CardType>{$card}</CardType>" : '';
        $out = $this->RESPONSE;
        $out = str_replace('{TranCode}', $code, $out);
        $out = str_replace('{TranType}', $type, $out);
        $out = str_replace('{CardType}', $card, $out);
        $out = str_replace('{REF}', $invoice, $out);
        $out = str_replace('{AMOUNT}', $amount, $out);
        switch ($responseType) {
            case 'APPROVE':
            case 'PARTIAL':
                $out = str_replace('{STATUS}', 'Approved', $out);
                $out = str_replace('{TEXT}', 'Approved', $out);
                $out = str_replace('{AuthCode}', '<AuthCode>TEST AUTH</AuthCode>', $out);
                break;
            case 'DECLINE':
            default:
                $out = str_replace('{STATUS}', 'Declined', $out);
                $out = str_replace('{TEXT}', 'Declined', $out);
                $out = str_replace('{AuthCode}', '', $out);
                break;
        }

        return '<' . '?xml version="1.0"?' . '>' . $out;
    }
}

AjaxPaycardTest::run();

