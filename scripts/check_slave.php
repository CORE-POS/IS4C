<?php
/**
 * Description: This script checks the slave status on the configured host
 *              printing "BAD <description>" or "OK <description>" for failure
 *              or success respectively.
 *              The possible failures could include:
 *              1) connection failure
 *              2) query failure (permissions, network, etc.)
 *              3) fetch failure (???)
 *              4) slave or io thread is not running
 *              5) Unknown master state (seconds_behind_master is null)
 *              6) seconds_behind_master has exceeded the configured threshold
 *
 *              If none of these condition occur, we asssume success and return
 *              an "OK" response, otherwise we include the error we can find
 *              (mysqli_connect_error() or $mysqli->error, or problem
 *               description).  A monitoring system need only check for:
 *              /^BAD/ (alert) or /^OK/ (everybody happy)
 */
 
/* **************************
 * Change related value below
 * **************************
 */
    $host = array(
        // "cr-1" => "192.168.0.81",
        // "cr-2" => "192.168.0.82",
        // "cr-3" => "192.168.0.83",
        // "cr-4" => "192.168.0.84",
        // "cr-5" => "192.168.0.85",
        // "jp-1" => "192.168.100.81",
        // "jp-2" => "192.168.100.82",
        // "jp-3" => "192.168.100.83",
        // "jp-4" => "192.168.100.84",
        // "jp-5" => "192.168.100.85",
        "cr-test" => "192.168.0.87"
        );
    $user = "";
    $pass = "";
    $mailto = "";
    $mailfrom = "";
 
/* ******************************************
 * No need to change anything below this line
 * ******************************************
*/

error_reporting(E_ALL);
header("Content-Type: text/plain"); # Not HTML
foreach ($host as $key => $value) {
    
    $mailsubject = "[".$key."] SLAVE REPLICATION ALERT";
    $mailheaders = "From:" . $mailfrom;
    $sql = "SHOW SLAVE STATUS";
    $skip_file = 'skip_alerts';
    $link = mysql_connect($value, $user, $pass, null);
 
    if($link)
        $result = mysql_query($sql, $link);
    else {
        printf("[".$key."] BAD: Connection Failed %s", mysql_error());
        mysql_close($link);
        return;
    }
 
    if($result)
        $status = mysql_fetch_assoc($result);
    else {
        printf("[".$key."] BAD: Query failed - %s\n", mysql_error($link));
        mysql_close($link);
        return;
    }
 
    mysql_close($link);
 
    $slave_lag_threshold = 120;
 
    $tests = array(
        'test_slave_io_thread' => array('Slave_IO_Running', "\$var === 'Yes'",
                                        'Slave IO Thread is not running'),
        'test_slave_sql_thread' => array('Slave_SQL_Running', "\$var === 'Yes'",
                                        'Slave SQL Thread is not running')
//        'test_last_err' => array('Last_Errno', "\$var == 0",
//                                 "Error encountered during replication - "
//                                 .$status['Last_Error']),
//        'test_master_status' => array('Seconds_Behind_Master', "isset(\$var)",
//                                        'Unknown master status (Seconds_Behind_Master IS NULL)'),
//        'test_slave_lag' => array('Seconds_Behind_Master',
//                                  "\$var < \$slave_lag_threshold",
//                                  "Slave is ${status['Seconds_Behind_Master']}s behind master (threshold=$slave_lag_threshold)")
    );
 
    $epic_fail = false;
    if(is_file($skip_file))
        $epic_fail = false;
    else {
        $mailmessage = "";
        $val1 = 0;
        foreach($tests as $test_name => $data) {
            list($field, $expr, $err_msg) = $data;
            $var = $status[$field];
            $val = eval("return $expr;");
            $val1 = (!$val) ? $val1 + 1 : $val1 - 1;
            // $mailmessage .= "BAD: " . $key . " replication failed. Reason: " . $err_msg . "\n";
        //print $val1."\n";
        }
        if ($val1 > 0) {
            print "[".$key."] BAD: Slave failure detected.  Attempting RESET.\n"; 
            $reset = "mysql -u".$user." -p".$pass." -h ".$value." -e \"slave stop;reset slave;slave start\"";
            exec($reset, $output, $result);
        if ($result <> 0) {
        print "[".$key."] BAD: RESET FAILED :-( \n";
        } else {
        print "[".$key."] RESET Successful.\n";
                sleep(3);
            $val2 = 0;
                foreach($tests as $test_name => $data) {
                    list($field, $expr, $err_msg) = $data;
                    $var = $status[$field];
                    $val = eval("return $expr;");
                    $val1 = (!$val) ? $val1 + 1 : $val1 - 1;
                    $mailmessage .= "[".$key."] BAD: Replication failed. Reason: " . $err_msg . "\n";
                }
        }
            if ($val2 > 0) {
                mail($mailto,$mailsubject,$mailmessage,$mailheaders);
                print $mailmessage . "\n";
                $epic_fail = true;
            }
        }
    }
 
    if(!$epic_fail) {
        print "[".$key."] OK: Checks all completed successfully.\n";
    }
}

function reset_slave($host_ip) {
    $reset = "mysql -u".$user." -p".$pass." -h ".$host_ip." -e \"slave stop;reset slave;slave start\"";
    exec($reset, $output, $result);
    $ret = ($result > 0) ? true : false;
}

