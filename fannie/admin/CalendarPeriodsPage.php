<?php

include(__DIR__ . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class CalendarPeriodsPage extends FannieRESTfulPage
{
    protected $header = 'Calendar Periods';
    protected $title = 'Calendar Periods';

    protected function post_id_handler()
    {
        $model = new PeriodsModel($this->connection);
        $model->year(FormLib::get('year'));
        $model->num($this->id);
        $model->startDate(FormLib::get('starts'));
        $model->endDate(FormLib::get('ends'));
        $model->save();

        return 'CalendarPeriodsPage.php';
    }

    protected function get_view()
    {
        $res = $this->connection->query("SELECT year, num, startDate, endDate FROM Periods ORDER BY year, num");
        $ret = '<table class="table table-bordered table-striped">';
        $ret .= '<tr><th>Name</th><th>Starts</th><th>Ends</th></tr>';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%d Period %d</td><td>%s</td><td>%s</td></tr>',
                $row['year'], $row['num'], $row['startDate'], $row['endDate']);
        }
        $ret .= '</table>';
        $year = date('Y');

        return <<<HTML
{$ret}
<hr />
<form method="post">
    <div class="form-group">
        <label>Year</label>
        <input type="text" name="year" class="form-control" value="{$year}" />
    </div>
    <div class="form-group">
        <label>Number</label>
        <input type="text" name="id" class="form-control" value="" />
    </div>
    <div class="form-group">
        <label>Starts</label>
        <input type="text" name="starts" class="form-control date-field" value="" />
    </div>
    <div class="form-group">
        <label>Ends</label>
        <input type="text" name="ends" class="form-control date-field" value="" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Create</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

