<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class PaycardAdminPage extends FannieRESTfulPage
{
    protected $header = 'Paycard Admin Functions';
    protected $title = 'Paycard Admin Functions';
    public $discoverable = true;
    protected $must_authenticate = true;
    protected $auth_classes = array('admin');

    public function preprocess()
    {
        $this->addRoute(
            'get<summary>',
            'get<query>',
            'post<query><date1><date2>',
            'get<close>',
            'get<stats>',
            'get<forward>'
        );

        return parent::preprocess();
    }

    private function initXML()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $termID = '';
        if ($settings['PcAdminTerminal']) {
            $termID = '<TerminalID>' . $settings['PcAdminTerminal'] . '</TerminalID>';
        }
        return <<<XML
<?xml verison="1.0"?>
<TStream>
    <Admin>
        <HostOrIP>127.0.0.1</HostOrIP>
        <Port>9000</Port>
        <MerchantID>{$settings['PcAdminMerchant']}</MerchantID>
        {$termID}
        <POSPackageID>COREPOS:1.0.0</POSPackageID>
        <SecureDevice>{{SecureDevice}}</SecureDevice>
        <ComPort>{{ComPort}}</ComPort>
        <SequenceNo>{{SequenceNo}}</SequenceNo>
        <TranType>Administrative</TranType>
XML;
    }

    private function sendRequest($xml)
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $curl = curl_init('http://' . $settings['PcAdminHost'] . ':8999');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        $respXML = curl_exec($curl);

        return $respXML;
    }

    protected function get_summary_view()
    {
        $xml = $this->getXML() . <<<XML
        <TranCode>BatchSummary</TranCode>
    </Admin>
</TStream>
XML;
        $result = $this->sendRequest($xml);
        return <<<HTML
<b>Batch Summary Result</b>:<br />
<pre>
    {$result}
</pre>
<p>
<a href="PaycardAdminPage">Run Another Command</a>
</p>
HTML;
    }

    protected function get_query_view()
    {
        return <<<HTML
<form method="post" action="PaycardAdminPage.php">
<div class="form-group">
    <label>Query Start</label>
    <input type="text" name="date1" class="form-control date-field" />
</div>
<div class="form-group">
    <label>Query End</label>
    <input type="text" name="date2" class="form-control date-field" />
</div>
<div class="form-group">
    <input type="hidden" name="query" value="1" />
    <button type="submit" class="btn btn-default">Submit Query</button>
</div>
</form>
HTML;
    }

    protected function post_query_date1_date2_view()
    {
        $xml = $this->getXML() . <<<XML
        <TranCode>BatchReportQuery</TranCode>
        <TransDateTimeBegin>{$this->date1}</TransDateTimeBegin>
        <TransDateTimeEnd>{$this->date2}</TransDateTimeEnd>
    </Admin>
</TStream>
XML;
        $result = $this->sendRequest($xml);
        return <<<HTML
<b>Batch Report Query Result</b>:<br />
<pre>
    {$result}
</pre>
<p>
<a href="PaycardAdminPage">Run Another Command</a>
</p>
HTML;
    }

    protected function get_close_view()
    {
        $xml = $this->getXML() . <<<XML
        <TranCode>BatchSummary</TranCode>
    </Admin>
</TStream>
XML;
        $result = $this->sendRequest($xml);

        $summary = simplexml_load_string($result);

        $xml = $this->getXML() . <<<XML
        <TranCode>BatchClose</TranCode>
        <BatchNo>{$summary->BatchSummary->BatchNo[0]}</BatchNo>
        <BatchItemCount>{$summary->BatchSummary->BatchItemCount[0]}</BatchItemCount>
        <NetBatchTotal>{$summary->BatchSummary->NetBatchTotal[0]}</NetBatchTotal>
    </Admin>
</TStream>
XML;
        $result = $this->sendRequest($xml);
        return <<<HTML
<b>Batch Close Result</b>:<br />
<pre>
    {$result}
</pre>
<p>
<a href="PaycardAdminPage">Run Another Command</a>
</p>
HTML;
    }

    protected function get_stats_view()
    {
        $xml = $this->getXML() . <<<XML
        <TranCode>SAF_Statistics</TranCode>
    </Admin>
</TStream>
XML;
        $result = $this->sendRequest($xml);
        return <<<HTML
<b>Store & Forward Statistics Result</b>:<br />
<pre>
    {$result}
</pre>
<p>
<a href="PaycardAdminPage">Run Another Command</a>
</p>
HTML;
    }

    protected function get_forward_view()
    {
        $xml = $this->getXML() . <<<XML
        <TranCode>SAF_ForwardAll</TranCode>
    </Admin>
</TStream>
XML;
        $result = $this->sendRequest($xml);
        return <<<HTML
<b>Store & Forward Forward-All Result</b>:<br />
<pre>
    {$result}
</pre>
<p>
<a href="PaycardAdminPage">Run Another Command</a>
</p>
HTML;
    }

    protected function get_view()
    {
        return <<<HTML
<p>
Choose an operation:
<hr />
<b>Batches</b>:<br />
<ul>
    <li><a href="PaycardAdminPage.php?summary=1">Summary</a></li>
    <li><a href="PaycardAdminPage.php?query=1">Report Query</a></li>
    <li><a href="PaycardAdminPage.php?close=1">Close</a></li>
</ul>
<b>Store & Forward</b>:<br />
<ul>
    <li><a href="PaycardAdminPage.php?stats=1">Statistics</a></li>
    <li><a href="PaycardAdminPage.php?forward=1">Forward All</a></li>
</ul>
</p>
HTML;
    }

}

FannieDispatch::conditionalExec();
