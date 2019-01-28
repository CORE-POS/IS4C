<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class DBAjax extends FannieRESTfulPage
{
    public $discoverable = false;

    protected function get_id_handler()
    {
        $ret = array('error'=> false);
        $prep = $this->connection->prepare('SELECT reportQuery FROM customReports WHERE reportID=?');
        $query = $this->connection->getValue($prep, $this->id);
        if ($query) {
            $ret['query'] = base64_decode($query);
        } else {
            $ret['error'] = true;
            $ret['errorMsg'] = 'Report not found';
        }
        echo json_encode($ret);

        return false;
    }
}

FannieDispatch::conditionalExec();

