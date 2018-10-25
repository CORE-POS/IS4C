<?php

use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class MemNotifications extends FannieRESTfulPage
{
    protected $header = "Customer Notifications";
    protected $title = "Fannie :: Customer Notifications";
    public $description = '[Member Notifications] manages a set of per-member front-end notifications.';

    protected $must_authenticate = true;

    private $sources = array(
        'blueline' => 'Member Title',
        'memlist' => 'Member List',
        'receipt' => 'Receipt',
        'callback' => 'Callback',
    );

    protected function post_handler()
    {
        $memID = FormLib::get('currentID');
        $model = new CustomerNotificationsModel($this->connection);
        $model->cardNo($memID);
        $model->type(FormLib::get('type'));
        $model->source(FormLib::get('source'));
        $model->message(FormLib::get('msg'));
        $model->modifierModule(FormLib::get('mod'));
        $model->save();

        return 'MemNotifications.php?id=' . $memID;
    }

    protected function post_id_handler()
    {
        $ids = $this->id;
        $types = FormLib::get('type');
        $sources = FormLib::get('source');
        $msgs = FormLib::get('msg');
        $mods = FormLib::get('mod');
        $model = new CustomerNotificationsModel($this->connection);
        for ($i=0; $i<count($ids); $i++) {
            $model->customerNotificationID($ids[$i]);
            $model->type($types[$i]);
            $model->source($sources[$i]);
            $model->message($msgs[$i]);
            $model->modifierModule($mods[$i]);
            $model->save();
        }

        $memID = FormLib::get('currentID');

        return 'MemNotifications.php?id=' . $memID;
    }

    protected function delete_id_handler()
    {
        $model = new CustomerNotificationsModel($this->connection);
        $model->customerNotificationID($this->id);
        $model->delete();

        $memID = FormLib::get('currentID');

        return 'MemNotifications.php?id=' . $memID;
    }

    protected function get_id_view()
    {
        $model = new CustomerNotificationsModel($this->connection);
        $model->cardNo($this->id);
        $body = '';
        foreach ($model->find() as $cn) {
            $opts = '';
            foreach ($this->sources as $key => $val) {
                $opts .= sprintf('<option %s value="%s">%s</option>',
                    ($cn->type() == $key ? 'selected' : ''),
                    $key, $val);
            }
            $body .= sprintf('<tr>
                <td><input type="hidden" name="id[]" value="%d" />
                    <select name="type[]" class="form-control">%s</select></td>
                <td><input type="text" class="form-control" name="source[]" value="%s" /></td>
                <td><input type="text" class="form-control" name="msg[]" value="%s" /></td>
                <td><input type="text" class="form-control" name="mod[]" value="%s" /></td>
                <td><a class="btn btn-xs btn-danger" href="MemNotifications.php?_method=delete&id=%d&currentID=%d">%s</a></td>
                </tr>',
                $cn->customerNotificationID(),
                $opts,
                $cn->source(),
                $cn->message(),
                $cn->modifierModule(),
                $cn->customerNotificationID(), $this->id, FannieUI::deleteIcon()
            );
        }
        $opts = '';
        foreach ($this->sources as $key => $val) {
            $opts .= sprintf('<option value="%s">%s</option>',
                $key, $val);
        }

        return <<<HTML
<form method="post" action="MemNotifications.php">
    <input type="hidden" name="currentID" value="{$this->id}" />
    <table class="table table-bordered table-striped">
        <tr><th>Type</th><th>Source</th><th>Message</th><th>Modifier</th><th>&nbsp;</th></tr>
        {$body}
    </table>
    <p>
        <button type="submit" class="btn btn-default">Save</button>
    </p>
</form>
<form method="post" action="MemNotifications.php">
    <div class="panel panel-default">
        <div class="panel panel-heading">New Notification</div>
        <div class="panel panel-body">
            <input type="hidden" name="currentID" value="{$this->id}" />
            <div class="form-group">
                <label>Type</label>
                <select name="type" class="form-control">{$opts}</select>
            </div>
            <div class="form-group">
                <label>Source</label>
                <input type="text" class="form-control" name="source" required />
            </div>
            <div class="form-group">
                <label>Message</label>
                <input type="text" class="form-control" name="msg" required />
            </div>
            <div class="form-group">
                <label>Modifier</label>
                <input type="text" class="form-control" name="mod" />
            </div>
            <p>
                <button type="submit" class="btn btn-default">Save</button>
            </p>
        </div>
    </div>
</form>
HTML;
    }

    protected function get_view()
    {
        return <<<HTML
<form method="get" action="MemNotifications.php">
    <div class="form-group">
        <label>Member #</label>
        <input type="text" name="id" class="form-control">
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

