<?php
if (file_exists(dirname(__FILE__) . '/Credentials/GoE.wfc.php')) {
    require_once(dirname(__FILE__) .'/Credentials/GoE.wfc.php');
}

function getFailedTrans($dateStr,$hour){
    global $sql;

    $trans_stack = array();
    $query = $sql->prepare("
        SELECT refNum
        FROM PaycardTransactions
        WHERE 
        dateID=?
        AND ".$sql->hour('requestDatetime')."=?
        AND httpCode <> 200
        AND (refNum like '%-%' OR refNum='')");
    $dateStr = date('Ymd', strtotime($dateStr));
    $response = $sql->execute($query,array($dateStr,$hour));
    while($row = $sql->fetch_row($response))
        $trans_stack[] = $row['refNum'];

    return $trans_stack;
}

/* query all transactions for a given date and type
   return cached results, if any, otherwise query
   processor and cache results
*/
function doquery($dateStr,$refNum){
    global $GOEMERCH_ID,$GOEMERCH_GATEWAY_ID;

    echo 'issuing query...'."\n";
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    $xml .= "<TRANSACTION>";
    $xml .= "<FIELDS>";
    $xml .= "<FIELD KEY=\"merchant\">$GOEMERCH_ID</FIELD>";
    $xml .= "<FIELD KEY=\"gateway_id\">$GOEMERCH_GATEWAY_ID</FIELD>";
    $xml .= "<FIELD KEY=\"operation_type\">query</FIELD>";
    $xml .= "<FIELD KEY=\"trans_type\">SALE</FIELD>";
    $xml .= "<FIELD KEY=\"begin_date\">$dateStr</FIELD>";
    $xml .= "<FIELD KEY=\"begin_time\">0001AM</FIELD>";
    $xml .= "<FIELD KEY=\"end_date\">$dateStr</FIELD>";
    $xml .= "<FIELD KEY=\"end_time\">1159PM</FIELD>";
    $xml .= "<FIELD KEY=\"order_id\">$refNum</FIELD>";
    $xml .= "</FIELDS>";
    $xml .= "</TRANSACTION>";

    $result = docurl($xml);
    fwrite(STDERR,$result['response']."\n");
    $p = new xmlData($result['response']);
    if ($p->get_first("RECORDS_FOUND") != 0)
        return $p->get_first("REFERENCE_NUMBER1");
    else
        return False;
}

function dovoid($refs){
    global $GOEMERCH_ID,$GOEMERCH_GATEWAY_ID;

    echo 'performing void...'."\n";
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    $xml .= "<TRANSACTION>";
    $xml .= "<FIELDS>";
    $xml .= "<FIELD KEY=\"merchant\">$GOEMERCH_ID</FIELD>";
    $xml .= "<FIELD KEY=\"gateway_id\">$GOEMERCH_GATEWAY_ID</FIELD>";
    $xml .= "<FIELD KEY=\"operation_type\">void</FIELD>";
    $xml .= "<FIELD KEY=\"total_number_transactions\">".count($refs)."</FIELD>";
    for($i=0;$i<count($refs);$i++){
        $xml .= "<FIELD KEY=\"reference_number".($i+1)."\">".$refs[$i]."</FIELD>";
    }
    $xml .= "</FIELDS>";
    $xml .= "</TRANSACTION>";

    $result = docurl($xml);
    fwrite(STDERR,$result['response']."\n");
}

function docurl($xml){
    $curl_handle = curl_init("https://secure.goemerchant.com/secure/gateway/xmlgateway.aspx");

    curl_setopt($curl_handle, CURLOPT_HEADER, 0);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT,15);
    curl_setopt($curl_handle, CURLOPT_FAILONERROR,false);
    curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION,false);
    curl_setopt($curl_handle, CURLOPT_TIMEOUT,30);
    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array("Content-type: text/xml"));
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $xml);

    $response = curl_exec($curl_handle);

    $result = array(
        'curlErr' => curl_errno($curl_handle),
        'curlErrText' => curl_error($curl_handle),
        'curlTime' => curl_getinfo($curl_handle,
                CURLINFO_TOTAL_TIME),
        'curlHTTP' => curl_getinfo($curl_handle,
                CURLINFO_HTTP_CODE),
        'response' => $response
    );

    return $result;
}

