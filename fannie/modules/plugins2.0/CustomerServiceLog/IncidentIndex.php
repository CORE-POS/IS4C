<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class IncidentIndex extends FannieRESTfulPage
{
    protected $header = 'Incident Tracking';
    protected $title = 'Incident Tracking';
    protected $must_authenticate = true;

    protected function get_view()
    {
        return <<<HTML
<p>
    <a href="AlertIncident.php" class="btn btn-default">Alert</a>
    <hr />
    <a href="MaintenanceIncident.php" class="btn btn-default">Maintenance</a>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

