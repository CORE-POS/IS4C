<?php
if (!file_exists(dirname(__FILE__) . '/../../../vendor/autoload.php')) {
    echo "Error: missing composer installed files!";
    exit;
}
include(dirname(__FILE__) . '/../../../vendor/autoload.php');
if (!class_exists('PhpAmqpLib\Connection\AMQPConnection')) {
    echo "Error: missing RabbitMQ library for PHP. Run composer to install it.";
    exit;
}
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
ini_set('display_errors', 1);

try {
    $connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();
    $channel->queue_declare('core-pos', false, false, false, false);
} catch (Exception $ex) {
    echo 'Error: cannot connect to RabbitMQ<br />';
    echo 'Details: ' . print_r($ex);
    exit;
}

if (isset($_REQUEST['publish'])) {
    $msg = new AMQPMessage($_REQUEST['publish']);
    $channel->basic_publish($msg, '', 'core-pos');
    exit;
}
?>
<!doctype html>
<html>
<head>
    <title>RabbitMQ testing</title>
</head>
<script type="text/javascript" src="../js/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="../js/sockjs.min.js"></script>
<script type="text/javascript" src="../js/stomp.min.js"></script>
<script type="text/javascript">
function backgroundSubmit()
{
    $.ajax({
        data: $('#send-form').serialize()
    });
    $('input:first').val('');
}
function subscribeToQueue()
{
    // Stomp.js boilerplate
    var ws = new SockJS('http://127.0.0.1:15674/stomp');
    var client = Stomp.over(ws);
    // SockJS does not support heart-beat: disable heart-beats
    client.heartbeat.outgoing = 0;
    client.heartbeat.incoming = 0;

    var echo_function = function(x) {
        $('#message-log').append('Received: ' + x.body + "\n");
    };

    var connect_callback = function(x) {
        $('#message-log').append("Connected!\n");
        client.subscribe("/amq/queue/core-pos", echo_function);
    };

    var error_callback = function(x) {
        $('#message-log').append("Connection Error!\n");
        console.log(x);
    };
    client.connect('guest', 'guest', connect_callback, error_callback, '/');
}
$(document).ready(function(){
    subscribeToQueue();
});
</script>
<body>

<div style="width:300px; float: left;">
    <h3>Put Messages in Queue</h3>
    <form onsubmit="backgroundSubmit(); return false;" id="send-form">
    <label>Message</label>
    <input type="text" name="publish" />
    <button type="submit">Send</button>
    </form>
</div>
<div style="width:300px; float: left;">
    <h3>Subscribe to Queue</h3>
    <pre id="message-log">
    </pre>
</div>
</body>
</html>
