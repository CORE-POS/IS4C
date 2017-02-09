<?php

class MSoapClient
{
    private $client;
    private $id;
    private $password;

    public function __construct($id, $passwd)
    {
        $uri = "https://w1.mercurypay.com/ws/ws.asmx?WSDL";
        $this->client = new SoapClient($uri, array('trace'=>true,'exceptions'=>true));
        $this->id = $id;
        $this->password = $passwd;
    }

    private function stubTransaction($invoice)
    {
        return array(
            'Transaction' => array(
                'MerchantID' => $this->id,
                'OperatorID' => 0,
                'TranType' => 'Credit',
                'InvoiceNo' => $invoice,
                'RefNo' => $invoice,
                'Memo' => 'CORE POS 1.0.0',
                'Frequency' => 'OneTime',
                'Amount' => array(
                    'Purchase' => "0.00",
                )
            ),
        );
    }

    public function saleByRecordNo($amt, $token, $refno, $invoice)
    {
        $xml = $this->stubTransaction($invoice);
        $xml['Transaction']['TranCode'] = 'SaleByRecordNo';
        $xml['Transaction']['RecordNo'] = $token;
        $xml['Transaction']['RefNo'] = $refno;
        $xml['Transaction']['Frequency'] = 'Recurring';
        $xml['Transaction']['Amount']['Purchase'] = sprintf('%.2f', $amt);

        return $this->send($xml);
    }

    private function arrToXml($arr, $xml)
    {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $subnode = $xml->addChild($k);
                $this->arrToXml($v, $subnode);
            } else {
                $xml->addChild($k, $v);
            }
        }

        return $xml;
    }

    private function send(array $xml)
    {
        $obj = new SimpleXmlElement('<TStream/>');
        $raw = $this->arrToXml($xml, $obj);
        $request = array('tran' => $raw->asXML(), 'pw' => $this->password);
        $resp = $this->client->CreditTransaction($request)->CreditTransactionResult;
        $resp = simplexml_load_string($resp);

        return $resp;
    }
}

