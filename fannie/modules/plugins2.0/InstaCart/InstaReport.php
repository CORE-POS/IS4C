<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class InstaReport extends FannieReportPage
{
    protected $title = 'InstaCart Report';
    protected $header = 'InstaCart Report';
    public $description = '[InstaCart Report] displays data as it will be exported to InstaCart';
    protected $required_fields = array();
    protected $report_headers = array();
    protected $no_sort_but_style = true;
    protected $sortable = false;

    public function fetch_report_data()
    {
        if (!class_exists('InstaFileV3')) {
            include(__DIR__ . '/InstaFileV3.php');
        }
        $insta = new InstaFileV3($this->connection, $this->config);
        $csvfile = tempnam(sys_get_temp_dir(), 'ICT');
        $insta->getFile($csvfile);

        $data = array();
        $csv = fopen($csvfile, 'r');
        while (!feof($csv)) {
            $line = fgetcsv($csv);
            if (count($this->report_headers) == 0) {
                $this->report_headers = $line;
            } else {
                $data[] = $line;
            }
            if ($this->report_format == 'html' && count($data) > 500) {
                break;
            }
        }
        fclose($csv);
        unlink($csvfile);

        return $data;
    }
}

FannieDispatch::conditionalExec();

