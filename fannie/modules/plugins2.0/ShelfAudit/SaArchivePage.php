<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SaArchivePage extends FannieRESTfulPage
{
    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Archived Counts] has old counts available to download';

    protected $title = 'ShelfAudit Counts Archive';
    protected $header = 'ShelfAudit Counts Archive';

    protected function get_id_handler()
    {
        $conf = $this->config->get('PLUGIN_SETTINGS');
        $model = new SaArchiveModel(FannieDB::get($conf['ShelfAuditDB']));
        $model->saArchiveID($this->id);
        $model->load();

        $file = 'Store ' . $model->storeID() . ' ' . $model->tdate() . '.csv';
        header('Content-Type: application/ms-excel');
        header('Content-Disposition: attachment; filename="'.$file.'.csv"');
        echo $model->data();

        return false;
    }

    protected function get_view()
    {
        $conf = $this->config->get('PLUGIN_SETTINGS');
        $model = new SaArchiveModel(FannieDB::get($conf['ShelfAuditDB']));
        $opts = '';
        foreach ($model->find('tdate', true) as $m) {
            $opts .= sprintf('<option value="%d">#%d %s</option>',
                $m->saArchiveID(), $m->storeID(), $m->tdate());
        }

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Pick snapshot</label>
        <select class="form-control" name="id">{$opts}</select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Download</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

