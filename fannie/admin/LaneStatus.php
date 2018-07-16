<?php

include(__DIR__ . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(__DIR__ . '/../install/util.php');
}

class LaneStatus extends FannieRESTfulPage
{
    protected $header = 'Lane Status';
    protected $title = 'Lane Status';
    public $description = '[Lane Status] shows the current up/down/offline status of all lanes';

    protected function post_id_handler()
    {
        $offline = FormLib::get('up') ? 0 : 1;
        $lanes = $this->config->get('LANES');
        $i = 1;
        $saveStr = 'array(';
        foreach ($lanes as $lane) {
            $saveStr .= "array('host'=>'" . $lane['host'] . "',"
                    . "'type'=>'" . $lane['type'] . "',"
                    . "'user'=>'" . $lane['user'] . "',"
                    . "'pw'=>'" . $lane['pw'] . "',"
                    . "'op'=>'" . $lane['op'] . "',"
                    . "'trans'=>'" . $lane['trans'] . "',";
            if ($i == $this->id) {
                $saveStr .= "'offline'=>{$offline}),";
            } else {
                $saveStr .= "'offline'=>{$lane['offline']}),";
            }
            $i++;
        }
        if ($saveStr != 'array(') {
            $saveStr = substr($saveStr, 0, strlen($saveStr)-1);
        }
        $saveStr .= ')';
        confset('FANNIE_LANES', $saveStr);

        echo json_encode(array(
            'id' => $this->id,
            'offline' => $offline,
        ));

        return false;
    }

    protected function post_handler()
    {
        $i = 1;
        $saveStr = 'array(';
        $offline = FormLib::get('offline');
        foreach ($this->config->get('LANES') as $lane) {
            $saveStr .= "array('host'=>'" . $lane['host'] . "',"
                    . "'type'=>'" . $lane['type'] . "',"
                    . "'user'=>'" . $lane['user'] . "',"
                    . "'pw'=>'" . $lane['pw'] . "',"
                    . "'op'=>'" . $lane['op'] . "',"
                    . "'trans'=>'" . $lane['trans'] . "',";
            $isOffline = in_array($i, $offline) ? 1 : 0;
            $saveStr .= "'offline'=>{$isOffline}),";
            $i++;
        }
        if ($saveStr != 'array(') {
            $saveStr = substr($saveStr, 0, strlen($saveStr)-1);
        }
        $saveStr .= ')';
        confset('FANNIE_LANES', $saveStr);

        return 'LaneStatus.php';
    }

    protected function get_view()
    {
        $status = '';
        $i = 1;
        foreach ($this->config->get('LANES') as $lane) {
            if ($lane['offline']) {
                $status .= sprintf('<tr class="warning"><td>Lane %d (%s)</td><td>Offline</td>
                    <td><input type="checkbox" name="offline[]" value="%d" checked /></td></tr>',
                    $i, $lane['host'], $i);
            } else {
                $css = 'danger';
                $port = 3306;
                $host = $lane['host'];
                if (strstr($host,":")) {
                    list($host,$port) = explode(":",$host);
                }
                $label = 'Down';
                $connected = stream_socket_client('tcp://' . $host . ':' . $port, $errno, $err, 2);
                if ($connected) {
                    fclose($connected);
                    $label = 'Up';
                    $css = 'success';
                }
                $status .= sprintf('<tr class="%s"><td>Lane %d (%s)</td><td>%s</td>
                    <td><input type="checkbox" name="offline[]" value="%d" /></td></tr>',
                    $css, $i, $lane['host'], $label, $i);
            }
            $i++;
        }

        return <<<HTML
<form method="post" action="LaneStatus.php">
    <table class="table table-bordered table-striped">
        <tr><th>Lane</th><th>Current Status</th><th>Set to Offline</th></tr>
        {$status}
    </table>
    <p>
        <button type="submit" class="btn btn-default">Update Offline Status</button>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

