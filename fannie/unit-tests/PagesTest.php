<?php

/**
 * @backupGlobals disabled
 */
class PagesTest extends PHPUnit_Framework_TestCase
{
    public function testReports()
    {
        $reports = FannieAPI::listModules('FannieReportPage', true);

        foreach($reports as $report_class) {
            $obj = new $report_class();

            $pre = $obj->preprocess();
            $this->assertInternalType('boolean',$pre);

            $auth = $obj->checkAuth();
            $this->assertInternalType('boolean',$pre);
        }
    }

    public function testPages()
    {
        $pages = FannieAPI::listModules('FanniePage', true);

        foreach($pages as $page_class) {
            $obj = new $page_class();
            if ($page_class == 'WfcHtViewSalaryPage') continue; // header/redirect problem

            ob_start();
            $pre = $obj->preprocess();
            ob_end_clean();
            $this->assertInternalType('boolean',$pre);

            $auth = $obj->checkAuth();
            $this->assertInternalType('boolean',$pre);
        }
    }
}

