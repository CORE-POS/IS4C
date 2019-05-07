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

    protected $WIC_BALANCE = '
        <RStream>
            <CmdResponse>
                <ResponseOrigin>Processor</ResponseOrigin>
                <DSIXReturnCode>000000</DSIXReturnCode>
                <CmdStatus>{STATUS}</CmdStatus>
                <TextResponse>{TEXT}</TextResponse>
                <UserTraceData></UserTraceData>
            </CmdResponse>
            <TranResponse>
                <MerchantID>2108927001</MerchantID>
                <AcctNo>610349XXXXXX5777</AcctNo>
                <ExpDate>XXXX</ExpDate>
                <CardType>EWIC</CardType>
                <TranCode>BalancePreVal</TranCode>
                <AuthCode>000194</AuthCode>
                <CaptureStatus>Captured</CaptureStatus>
                <RefNo>0803005699015000</RefNo>
                <InvoiceNo>0803005699015000</InvoiceNo>
                <OperatorID>56</OperatorID>
                <Memo>CORE POS 1.0.0 PDCX</Memo>
                <Amount></Amount>
                <AcqRefData>K</AcqRefData>
                <EarliestBenefitExpDate>12312025</EarliestBenefitExpDate>
                <ProductData>
                    <ProductCat1>02</ProductCat1>
                    <ProductSubCat1>000</ProductSubCat1>
                    <ProductQty1>32.00</ProductQty1>
                    <ProductCat2>03</ProductCat2>
                    <ProductSubCat2>000</ProductSubCat2>
                    <ProductQty2>72.00</ProductQty2>
                    <ProductCat3>05</ProductCat3>
                    <ProductSubCat3>000</ProductSubCat3>
                    <ProductQty3>108.00</ProductQty3>
                    <ProductCat4>06</ProductCat4>
                    <ProductSubCat4>000</ProductSubCat4>
                    <ProductQty4>36.00</ProductQty4>
                    <ProductCat5>09</ProductCat5>
                    <ProductSubCat5>000</ProductSubCat5>
                    <ProductQty5>24.00</ProductQty5>
                    <ProductCat6>12</ProductCat6>
                    <ProductSubCat6>000</ProductSubCat6>
                    <ProductQty6>32.00</ProductQty6>
                    <ProductCat7>16</ProductCat7>
                    <ProductSubCat7>000</ProductSubCat7>
                    <ProductQty7>30.00</ProductQty7>
                    <ProductCat8>19</ProductCat8>
                    <ProductSubCat8>000</ProductSubCat8>
                    <ProductQty8>19.75</ProductQty8>
                    <ProductCat9>21</ProductCat9>
                    <ProductSubCat9>018</ProductSubCat9>
                    <ProductQty9>24.00</ProductQty9>
                    <ProductCat10>50</ProductCat10>
                    <ProductSubCat10>000</ProductSubCat10>
                    <ProductQty10>18.00</ProductQty10>
                    <ProductCat11>51</ProductCat11>
                    <ProductSubCat11>000</ProductSubCat11>
                    <ProductQty11>192.00</ProductQty11>
                    <ProductCat12>52</ProductCat12>
                    <ProductSubCat12>000</ProductSubCat12>
                    <ProductQty12>192.00</ProductQty12>
                    <ProductCat13>52</ProductCat13>
                    <ProductSubCat13>004</ProductSubCat13>
                    <ProductQty13>32.00</ProductQty13>
                    <ProductCat14>52</ProductCat14>
                    <ProductSubCat14>006</ProductSubCat14>
                    <ProductQty14>16.00</ProductQty14>
                    <ProductCat15>53</ProductCat15>
                    <ProductSubCat15>000</ProductSubCat15>
                    <ProductQty15>96.00</ProductQty15>
                    <ProductCat16>54</ProductCat16>
                    <ProductSubCat16>000</ProductSubCat16>
                    <ProductQty16>72.00</ProductQty16>
                </ProductData>
            </TranResponse>
        </RStream>
';

    protected $WIC_AUTH = '
        <RStream>
            <CmdResponse>
                <ResponseOrigin>Processor</ResponseOrigin>
                <DSIXReturnCode>000000</DSIXReturnCode>
                <CmdStatus>{STATUS}</CmdStatus>
                <TextResponse>{TEXT}</TextResponse>
                <UserTraceData></UserTraceData>
            </CmdResponse>
            <TranResponse>
                <MerchantID>2108927001</MerchantID>
                <AcctNo>610349XXXXXX5777</AcctNo>
                <ExpDate>XXXX</ExpDate>
                <CardType>EWIC</CardType>
                <TranCode>Sale</TranCode>
                <AuthCode>000195</AuthCode>
                <CaptureStatus>Captured</CaptureStatus>
                <RefNo>1003</RefNo>
                <InvoiceNo>0803005699015004</InvoiceNo>
                <OperatorID>56</OperatorID>
                <Memo>CORE POS 1.0.0</Memo>
                <Amount>
                    <Purchase>1.19</Purchase>
                    <Authorize>1.19</Authorize>
                </Amount>
                <AcqRefData>K</AcqRefData>
                <ItemData>
                    <UPCItem1>10000000000040112</UPCItem1>
                    <ItemPrice1>1.19</ItemPrice1>
                    <ItemQty1>1.00</ItemQty1>
                    <ItemStatus1>Approved</ItemStatus1>
                </ItemData>
                <EarliestBenefitExpDate>12312016</EarliestBenefitExpDate>
                <ProductData>
                    <ProductCat1>02</ProductCat1>
                    <ProductSubCat1>000</ProductSubCat1>
                    <ProductQty1>32.00</ProductQty1>
                    <ProductCat2>03</ProductCat2>
                    <ProductSubCat2>000</ProductSubCat2>
                    <ProductQty2>72.00</ProductQty2>
                    <ProductCat3>05</ProductCat3>
                    <ProductSubCat3>000</ProductSubCat3>
                    <ProductQty3>108.00</ProductQty3>
                    <ProductCat4>06</ProductCat4>
                    <ProductSubCat4>000</ProductSubCat4>
                    <ProductQty4>36.00</ProductQty4>
                    <ProductCat5>09</ProductCat5>
                    <ProductSubCat5>000</ProductSubCat5>
                    <ProductQty5>24.00</ProductQty5>
                    <ProductCat6>12</ProductCat6>
                    <ProductSubCat6>000</ProductSubCat6>
                    <ProductQty6>32.00</ProductQty6>
                    <ProductCat7>16</ProductCat7>
                    <ProductSubCat7>000</ProductSubCat7>
                    <ProductQty7>30.00</ProductQty7>
                    <ProductCat8>19</ProductCat8>
                    <ProductSubCat8>000</ProductSubCat8>
                    <ProductQty8>0.75</ProductQty8>
                    <ProductCat9>21</ProductCat9>
                    <ProductSubCat9>018</ProductSubCat9>
                    <ProductQty9>24.00</ProductQty9>
                    <ProductCat10>50</ProductCat10>
                    <ProductSubCat10>000</ProductSubCat10>
                    <ProductQty10>18.00</ProductQty10>
                    <ProductCat11>51</ProductCat11>
                    <ProductSubCat11>000</ProductSubCat11>
                    <ProductQty11>192.00</ProductQty11>
                    <ProductCat12>52</ProductCat12>
                    <ProductSubCat12>000</ProductSubCat12>
                    <ProductQty12>192.00</ProductQty12>
                    <ProductCat13>52</ProductCat13>
                    <ProductSubCat13>004</ProductSubCat13>
                    <ProductQty13>32.00</ProductQty13>
                    <ProductCat14>52</ProductCat14>
                    <ProductSubCat14>006</ProductSubCat14>
                    <ProductQty14>16.00</ProductQty14>
                    <ProductCat15>53</ProductCat15>
                    <ProductSubCat15>000</ProductSubCat15>
                    <ProductQty15>96.00</ProductQty15>
                </ProductData>
            </TranResponse>
        </RStream>
';

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
        $card = $xml->query('/TStream/Transaction/CardType');

        // always decline if not the training cashier
        // approve training cashier unless special amounts
        $responseType = 'DECLINE';
        if ($empNo == '9999' || $empNo === 'test') {
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
        if ($code === '<TranCode>BalancePreVal</TranCode>') {
            $out = $this->WIC_BALANCE;
        } elseif ($code === '<TranCode>Balance</TranCode>' && $card === '<CardType>EWIC</CardType>') {
            $out = $this->WIC_BALANCE;
        } elseif ($code === '<TranCode>Sale</TranCode>' && $card === '<CardType>EWIC</CardType>') {
            $out = $this->WIC_AUTH;
        }
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

