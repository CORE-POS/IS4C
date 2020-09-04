<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class UndeliverableUpload extends COREPOS\Fannie\API\FannieUploadPage
{
    protected $header = 'Upload Undeliverable Addresses';
    protected $title = 'Upload Undeliverable Addresses';
    public $description = '[Upload Undeliverable Addresses] for owner accounts';
    
    protected $preview_opts = array(
        'cardno' => array(
            'name' => 'cardno',
            'display_name' => 'Account #',
            'default' => 0,
            'required' => true,
        ),
        'email' => array(
            'name' => 'email',
            'display_name' => 'Email',
            'default' => 9,
            'required' => true,
        ),
    );

    private $updateCount = 0;

    public function process_file($linedata, $indexes)
    {
        $queue = new COREPOS\Fannie\API\jobs\QueueManager();
        $chkP = $this->connection->prepare("SELECT Type FROM custdata WHERE CardNo=?");
        foreach ($linedata as $line) {
            $id = trim($line[$indexes['cardno']]);
            if (!is_numeric($id)) {
                continue;
            }
            $type = $this->connection->getValue($chkP, array($id));
            if ($type != 'PC') {
                continue; // already inactive
            }

            $queue->add(array(
                'class' => 'MarkBadAddress',
                'data' => array(
                    'id' => $id,
                    'code' => 16,
                    'email' => $line[$indexes['email']],
                ),
            ));
            $this->updateCount++;
        }

        return true;
    }

    public function results_content()
    {
        return <<<HTML
<p>
Updated {$this->updateCount} accounts.
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

