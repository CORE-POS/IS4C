<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class ForwardingAddressUpload extends COREPOS\Fannie\API\FannieUploadPage
{
    protected $header = 'Upload Forwarding Addresses';
    protected $title = 'Upload Forwarding Addresses';
    public $description = '[Upload Forwarding Addresses] for owner accounts';
    
    protected $preview_opts = array(
        'cardno' => array(
            'name' => 'cardno',
            'display_name' => 'Account #',
            'default' => 0,
            'required' => true,
        ),
        'addr1' => array(
            'name' => 'addr1',
            'display_name' => 'Address 1',
            'default' => 3,
            'required' => true,
        ),
        'addr2' => array(
            'name' => 'addr2',
            'display_name' => 'Address 2',
            'default' => 4,
            'required' => true,
        ),
        'city' => array(
            'name' => 'city',
            'display_name' => 'City',
            'default' => 5,
            'required' => true,
        ),
        'state' => array(
            'name' => 'state',
            'display_name' => 'State',
            'default' => 6,
            'required' => true,
        ),
        'zip' => array(
            'name' => 'zip',
            'display_name' => 'Zip Code',
            'default' => 7,
            'required' => true,
        ),
    );

    private $updateCount = 0;

    public function process_file($linedata, $indexes)
    {
        $queue = new COREPOS\Fannie\API\jobs\QueueManager();
        foreach ($linedata as $line) {
            $id = trim($line[$indexes['cardno']]);
            if (!is_numeric($id)) {
                continue;
            }

            $zip = $line[$indexes['zip']];
            if (strpos($zip, '-')) {
                list($zip,) = explode('-', $zip, 2);
            }
            $data = array(
                'cardNo' => $id,
                'addressFirstLine' => strtoupper($line[$indexes['addr1']]),
                'addressSecondLine' => strtoupper($line[$indexes['addr2']]),
                'city' => strtoupper($line[$indexes['city']]),
                'state' => strtoupper($line[$indexes['state']]),
                'zip' => $zip,
            );

            $queue->add(array(
                'class' => 'UpdateAddress',
                'data' => $data,
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

