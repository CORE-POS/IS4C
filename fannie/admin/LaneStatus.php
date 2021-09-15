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

        // nb. we must access the array element directly, via its
        // "true" key ($i), in order to change its data.  but that is
        // a 0-based index and we must use a 1-based index when
        // checking the form data
        foreach ($lanes as $i => $lane) {
            $number = $i + 1;
            if ($number == $this->id) {
                $lanes[$i]['offline'] = $offline;
            }
        }

        update_lanes($lanes);

        echo json_encode(array(
            'id' => $this->id,
            'offline' => $offline,
        ));

        return false;
    }

    protected function post_handler()
    {
        // nb. if *no* lanes were checked as being offline, then we do
        // not get an array back from the form.  in which case, pretend.
        $offline = FormLib::get('offline');
        if (!$offline) {
            $offline = array();
        }

        // nb. we must access the array element directly, via its
        // "true" key ($i), in order to change its data.  but that is
        // a 0-based index and we must use a 1-based index when
        // checking the form data
        $lanes = $this->config->get('LANES');
        foreach ($lanes as $i => $lane) {
            $number = $i + 1;
            $lanes[$i]['offline'] = in_array($number, $offline) ? 1 : 0;
        }

        update_lanes($lanes);

        return 'LaneStatus.php';
    }

    protected function get_view()
    {
        $status = '';
        $timeout = 2;
        $i = 1;
        foreach ($this->config->get('LANES') as $lane) {
            if ($lane['offline']) {
                $status .= sprintf('<tr class="warning"><td>Lane %d (%s)</td><td>Offline</td>
                    <td><input type="checkbox" name="offline[]" value="%d" checked /></td></tr>',
                    $i, $lane['host'], $i);
            } else {
                $css = 'danger';
                $label = 'Down';
                if (check_db_host($lane['host'], $lane['type'], $timeout)) {
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

