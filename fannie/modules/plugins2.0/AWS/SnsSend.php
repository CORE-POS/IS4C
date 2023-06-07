<?php

use COREPOS\Fannie\Plugin\AWS\SNS;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SnsSend extends FannieRESTfulPage
{

    protected function post_view()
    {
        $sns = new SNS($this->config);
        $msg = FormLib::get('msg');
        $attempt = 1;
        $sent = 0;
        foreach (explode("\n", FormLib::get('addr')) as $addr) {
            if ($sns->sendSMS($addr, $msg)) {
                $sent++;
            }
            $attempt++;
            if ($attempt % 20 == 0) {
                sleep(1);
            }
        } 

        return <<<HTML
Sent {$sent} messages out of {$attempt} attempts.
HTML;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="post" action="SnsSend.php">
    <div class="form-group">
        <label>Message</label>
        <textarea rows="5" class="form-control" name="msg"></textarea>
    </div>
    <div class="form-group">
        <label>Recipient(s)</label>
        <textarea rows="15" class="form-control" name="addr"></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Send</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

