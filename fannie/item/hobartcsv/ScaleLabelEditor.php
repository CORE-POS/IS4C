<?php

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ScaleLabelEditor extends FannieRESTfulPage
{
    protected $header = 'Manage Service Scale Labels';
    protected $title = 'Manage Service Scale Labels';

    protected $TYPES = array(
        23 => 'Fixed Weight, Vertical',
        63 => 'Fixed Weight, Horizontal',
        103 => 'Random Weight, Vertical',
        113 => 'Random Weight, Horizontal',
        53 => 'Safe Handling Instructions',
    );

    protected function post_id_handler()
    {
        try {
            $labels = $this->form->label;
            $scales = $this->form->scale;
            $maps = $this->form->mapped;
            $model = new ScaleLabelsModel($this->connection);
            $this->connection->startTransaction();
            for ($i=0; $i<count($this->id); $i++) {
                $model->scaleLabelID($this->id[$i]);
                $model->labelType($labels[$i]);
                $model->scaleType($scales[$i]);
                $model->mappedType($maps[$i]);
                $model->save(); 
            }
            $this->connection->commitTransaction();
        } catch (Exception $ex) {}
        $this->addNew();

        return filter_input(INPUT_SERVER, 'PHP_SELF');
    }

    protected function post_handler()
    {
        $this->addNew();

        return filter_input(INPUT_SERVER, 'PHP_SELF');
    }

    private function addNew()
    {
        try {
            if ($this->form->newLabel == '' || $this->form->newScale == '' || $this->form->newMapping == '') {
                return false;
            }
            $model = new ScaleLabelsModel($this->connection);
            $model->labelType($this->form->newLabel);
            $model->scaleType($this->form->newScale);
            $model->mappedType($this->form->newMapping);
            return $model->save() ? true : false;
        } catch (Exception $ex) {
            return false;
        }
    }

    protected function get_view()
    {
        $scaleTypes = array();
        $res = $this->connection->query('SELECT scaleType FROM ServiceScales GROUP BY scaleType ORDER BY scaleType');
        while ($row = $this->connection->fetchRow($res)) {
            $scaleTypes[] = $row['scaleType'];
        }

        $model = new ScaleLabelsModel($this->connection);
        $table = '';
        foreach ($model->find(array('scaleType', 'labelType')) as $sl) {
            $opts1 = '';
            foreach ($this->TYPES as $k => $v) {
                $opts1 .= sprintf('<option %s value="%d">%s</option>',
                    ($sl->labelType() == $k ? 'selected' : ''), $k, $v);
            }
            $opts2 = '';
            foreach ($scaleTypes as $st) {
                $opts2 .= sprintf('<option %s>%s</option>',
                    ($sl->scaleType() == $st ? 'selected' : ''), $st);
            }
            $table .= sprintf('<tr>
                <td><input type="hidden" name="id[]" value="%d" />
                    <select name="label[]" class="form-control">%s</select></td>
                <td><select name="scale[]" class="form-control">%s</select></td>
                <td><input type="text" class="form-control" name="mapped[]" value="%s" /></td>
                </tr>',
                $sl->scaleLabelID(),
                $opts1,
                $opts2,
                $sl->mappedType()
            );
        }

        $scaleOpts = array_reduce($scaleTypes, function ($c, $i) { return $c . "<option>{$i}</option>"; });
        $labelOpts = array_reduce(array_keys($this->TYPES), function ($c, $i) {
            return $c . "<option value=\"{$i}\">{$this->TYPES[$i]}</option>";
        });

        return <<<HTML
<form method="post">
    <table class="table table-striped table-bordered">
        <tr><th>Label Type</th><th>Scale Type</th><th>Mapping</th></tr>
        {$table}
    </table>
<div class="panel panel-default">
    <div class="panel panel-heading">New entry</div>
    <div class="panel panel-body">
        <div class="form-group">
            <label>Label Type</label>
            <select name="newLabel" class="form-control">
                <option value="">Select one...</option>
                {$labelOpts}
            </select>
        </div>
        <div class="form-group">
            <label>Scale Type</label>
            <select name="newScale" class="form-control">
                <option value="">Select one...</option>
                {$scaleOpts}
            </select>
        </div>
        <div class="form-group">
            <label>Mapping</label>
            <input type="text" name="newMapping" class="form-control" />
        </div>
    </div>
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default btn-core">Save</button>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

