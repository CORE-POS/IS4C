<?php
require($FANNIE_ROOT.'src/Credentials/GoE.wfc.php');
include('xmlData.php');

function getProcessorInfo($dateStr){
    global $FANNIE_TRANS_DB;
    $dbc = FannieDB::get($FANNIE_TRANS_DB);

    $trans_stack = array();
    $query = $dbc->prepare_statement("SELECT q.refNum,r.httpCode,q.PAN,q.issuer FROM efsnetRequest as q
        LEFT JOIN efsnetResponse as r ON q.date=r.date
        and q.cashierNo=r.cashierNo and q.laneNo=r.laneNo
        and q.transNo=r.transNo and q.transID=r.transID
        WHERE q.datetime BETWEEN ? AND ?");
    $result = $dbc->exec_statement($query,array($dateStr.' 00:00:00',$dateStr.' 23:59:59'));
    while($row = $dbc->fetch_row($result)){
        $trans_stack[$row['refNum']] = array(
            "http"=>$row['httpCode'],
            "card"=>$row['PAN'],
            "ctype"=>$row['issuer'] 
        );
    }

    list($year,$month,$day) = explode("-",$dateStr);
    $today = date('mdy',mktime(0,0,0,$month,$day,$year));
    $tomorrow = date("mdy",mktime(0,0,0,$month,$day+1,$year));
    $auths = queryAuth($today);
    loadAuthInfo($auths,$trans_stack);
    $settles = querySettle($tomorrow);
    loadSettleInfo($settles,$trans_stack);

    return $trans_stack;
}

function loadSettleInfo($fn,&$trans_stack){
    if (!file_exists($fn)) return;

    $parser = new xmlData(file_get_contents($fn));  
    $num_records = $parser->get_first('records_found');
    for($i=1;$i<=$num_records;$i++){
        if (strtolower($parser->get_first("trans_type$i")) != 'settle')
            continue; // skip non-auth records
        $orderID = $parser->get_first("order_id$i");
        if (!isset($trans_stack[$orderID]))
            continue; // order settled on unexpected date...
        
        $ts = $parser->get_first("trans_time$i");
        $trans_stack[$orderID]['settle_dt'] = $ts;
    }
}

function loadAuthInfo($fn,&$trans_stack){
    if (!file_exists($fn)) return;

    $parser = new xmlData(file_get_contents($fn));  
    $num_records = $parser->get_first('records_found');
    for($i=1;$i<=$num_records;$i++){
        if (strtolower($parser->get_first("trans_type$i")) != 'auth' &&
            strtolower($parser->get_first("trans_type$i")) != 'credit') 
            continue; // skip weird records

        $orderID = $parser->get_first("order_id$i");
        if (!isset($trans_stack[$orderID])){
            // backend correction via GoE web portal
            $trans_stack[$orderID] = array();
            $trans_stack[$orderID]['ctype'] = $parser->get_first("card_type$i");
            $trans_stack[$orderID]['card'] = 'N/A';
        }

        $amt = $parser->get_first("amount$i");
        $settledAmt = $parser->get_first("amount_settled$i");
        $reversal = $parser->get_first("credit_void$i");
        $status = $parser->get_first("trans_status$i");

        $trans_stack[$orderID]['auth_amt'] = $amt;
        $trans_stack[$orderID]['settle_amt'] = $settledAmt;
        if (strtolower($parser->get_first("trans_type$i")) == 'credit'){
            $creditAmt = $parser->get_first("amount_credited$i");
            $trans_stack[$orderID]['settle_amt'] = -1*$creditAmt;
            $trans_stack[$orderID]['auth_amt'] *= -1;
        }
        $trans_stack[$orderID]['reversal'] = $reversal;
        $trans_stack[$orderID]['success'] = $status;
    }
}

function queryAuth($dateStr){
    return doDuery('SALE',$dateStr);
}

function querySettle($dateStr){
    return doDuery('SETTLE',$dateStr);
}

/* query all transactions for a given date and type
   return cached results, if any, otherwise query
   processor and cache results
*/
function doDuery($type,$dateStr){
    global $GOEMERCH_ID,$GOEMERCH_GATEWAY_ID;

    $cache_fn = 'xmlcache/'.$type.'.'.$dateStr.'.xml';
    if (file_exists($cache_fn)) return $cache_fn;
    
    echo 'issuing query...';
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    $xml .= "<TRANSACTION>";
    $xml .= "<FIELDS>";
    $xml .= "<FIELD KEY=\"merchant\">$GOEMERCH_ID</FIELD>";
    $xml .= "<FIELD KEY=\"gateway_id\">$GOEMERCH_GATEWAY_ID</FIELD>";
    $xml .= "<FIELD KEY=\"operation_type\">query</FIELD>";
    $xml .= "<FIELD KEY=\"trans_type\">$type</FIELD>";
    $xml .= "<FIELD KEY=\"begin_date\">$dateStr</FIELD>";
    $xml .= "<FIELD KEY=\"begin_time\">0001AM</FIELD>";
    $xml .= "<FIELD KEY=\"end_date\">$dateStr</FIELD>";
    $xml .= "<FIELD KEY=\"end_time\">1159PM</FIELD>";
    $xml .= "</FIELDS>";
    $xml .= "</TRANSACTION>";

    $result = docurl($xml);
    if (!empty($result['response'])){
        $fp = fopen($cache_fn,'w');
        fwrite($fp,$result['response']);
        fclose($fp);
    }

    return $cache_fn;
}

function docurl($xml){
    $curl_handle = curl_init("https://secure.goemerchant.com/secure/gateway/xmlgateway.aspx");

    curl_setopt($curl_handle, CURLOPT_HEADER, 0);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT,15);
    curl_setopt($curl_handle, CURLOPT_FAILONERROR,false);
    curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION,false);
    curl_setopt($curl_handle, CURLOPT_TIMEOUT,60);
    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array("Content-type: text/xml"));
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $xml);

    set_time_limit(300);

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

?>
