<?php

class TaxHolidaysPage extends FannieRESTfulPage
{
    protected $header = 'Tax Holidays Schedule';
    protected $title = 'Tax Holidays Schedule';
    public $description = '[Tax Holidays] are pre-scheduled days where sales tax does not apply';

    protected function post_id_view()
    {
        $date = strtotime($this->id);
        if ($date !== false) {
            $this->connection->selectDB($this->config->get('OP_DB'));
            $model = new TaxHolidaysModel($this->connection);
            $model->tdate(date('Y-m-d 00:00:00', $date)); 
            if (count($model->find()) == 0) {
                $model->save();
            }
        }

        return 'TaxHolidaysPage.php';
    }

    protected function delete_id_view()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $model = new TaxHolidaysModel($this->connection);
        $model->taxHolidayID($this->id);
        $model->delete();

        return 'TaxHolidaysPage.php';
    }

    protected function get_view()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $model = new TaxHolidaysModel($this->connection);
        $ret = '<form method="post">
            <div class="form-group form-inline">
                <label>Date</label>
                <input type="text" class="form-control date-field" name="id" />
                <button class="btn btn-default">Add</button>
            </div>
            </form>
            <table class="table table-bordered">
            <tr>
                <th>Date</th>
                <th>&nbsp;</th>
            </tr>';
        foreach ($model->find('tdate', true) as $obj) {
            $ret .= sprintf('<tr><td>%s</td><td><a href="?_method=delete&id=%d">%s</a></td></tr>',
                $obj->tdate(),
                $obj->taxHolidayID(),
                COREPOS\Fannie\API\lib\deleteIcon()
            );
        }
        $ret .= '</table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

