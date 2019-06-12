<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpMenu extends FannieRESTfulPage
{
    protected $header = 'RP Menu';
    protected $title = 'RP Menu';

    protected function get_view()
    {
        return <<<HTML
<ul>
    <li>Ordering
    <ul>
        <li><a href="RpOrderPage.php">Regular Ordering</a></li>
        <li><a href="RpDirectPage.php">Direct Ordering</a></li>
    </ul>
    <li>Data Management</li>
    <ul>
        <li><a href="RpFarmsPage.php">Farms</a> - list of local growers for direct ordering</li>
        <li><a href="RpLocalLCsPage.php">Local Like Codes</a> - subset of like codes that are ordered direct</li>
        <li><a href="RpFarmSchedule.php">Committment Schedule</a> - set primary and secondary farm for a given like code & time period</li>
        <li><a href="RpFixedMaps.php">Fixed Maps</a> - attach a specific vendor SKU to a likecode instead of using automated name-based matching</li>
        <li><a href="RpFileManager.php">Import from RP</a> - reload data from the RP Excel file</li>
        <li><a href="RpSegmentation.php">Sales Segmentation</a> - projected day-by-day sales for a week
    </ul>
</ul>
HTML;
    }
}

FannieDispatch::conditionalExec();

