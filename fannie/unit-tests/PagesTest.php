<?php

/**
 * @backupGlobals disabled
 */
class PagesTest extends PHPUnit_Framework_TestCase
{
    public function testReports()
    {
        $reports = FannieAPI::listModules('FannieReportPage', true);
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $op_db = $config->get('OP_DB');
        $dbc = FannieDB::get($op_db);
        $dbc->throwOnFailure(true);

        foreach ($reports as $report_class) {
            $obj = new $report_class();
            $obj->setConfig($config);
            $obj->setLogger($logger);
            $dbc->selectDB($op_db);
            $obj->setConnection($dbc);

            $pre = $obj->preprocess();
            $this->assertInternalType('boolean',$pre);

            $auth = $obj->checkAuth();
            $this->assertInternalType('boolean',$pre);

            $html_form = $obj->form_content();
            $this->assertNotEquals(0, strlen($html_form), 'Report form is empty for ' . $report_class);

            $form = new \COREPOS\common\mvc\ValueContainer();
            foreach ($obj->requiredFields() as $field) {
                if (strstr($field, 'date')) {
                    $form->$field = date('Y-m-d');
                } else {
                    $form->$field = 1;
                }
            }
            $obj->setForm($form);
            $preamble = $obj->report_description_content();
            $this->assertInternalType('array', $preamble, 'Report did not return description content ' . $report_class);
            $results = $obj->fetch_report_data();
            $this->assertInternalType('array', $results, 'Report did not return results ' . $report_class);
        }
    }

    public function testPages()
    {
        $pages = FannieAPI::listModules('FanniePage', true);
        $config = FannieConfig::factory();
        $logger = new FannieLogger();
        $op_db = $config->get('OP_DB');
        $dbc = FannieDB::get($op_db);
        $dbc->throwOnFailure(true);

        foreach ($pages as $page_class) {
            $obj = new $page_class();
            $obj->setConfig($config);
            $obj->setLogger($logger);
            $dbc->selectDB($op_db);
            $obj->setConnection($dbc);
            if ($page_class == 'WfcHtViewSalaryPage') continue; // header/redirect problem

            ob_start();
            $pre = $obj->preprocess();
            ob_end_clean();
            $this->assertInternalType('boolean',$pre);

            $help = $obj->helpContent();
            $this->assertInternalType('string', $help);

            $auth = $obj->checkAuth();
            $this->assertInternalType('boolean',$pre);

            $obj->unitTest($this);
        }
    }
}

