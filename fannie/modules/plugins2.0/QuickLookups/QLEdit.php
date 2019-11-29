<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class QLEdit extends FannieRESTfulPage
{
    protected $header = 'QL Editor';
    protected $title = 'QL Editor';

    protected function get_id_handler()
    {
        $ret = array();
        $model = new QuickLookupsModel($this->connection);
        $model->lookupSet($this->id);
        foreach ($model->find('sequence') as $m) {
            $ret[] = array(
                'id' => $m->quickLookupID(),
                'label' => $m->label(),
                'action' => $m->action(),
                'image' => strlen($m->image()) ? true : false,
                'imageURL' => 'QuickLookupsImages.php?id=' . $m->quickLookupID(),
            );
        }

        echo json_encode($ret);

        return false;
    }

    protected function post_id_handler()
    {
        $model = new QuickLookupsModel($this->connection);
        $model->lookupSet($this->id);
        $qls = FormLib::get('ql');
        $labels = FormLib::get('label');
        $actions = FormLib::get('action');
        $seq = 0;
        for ($i=0; $i<count($qls); $i++) {
            if ($qls[$i] > 0) {
                $model->quickLookupID($qls[$i]);
            } else {
                $model->quickLookupID(null);
            }
            $model->label(trim($labels[$i]));
            $model->action(trim($actions[$i]));
            if ($qls[$i] > 0 && $model->label() == "" && $model->action() == "") {
                $model->delete();
            } elseif ($model->label() != "" || $model->action() != "") {
                $model->sequence($seq);
                $model->save();
                $seq++;
            }
        }

        return false;
    }

    protected function get_view()
    {
        $this->addScript('../../../src/javascript/vue.js');
        $this->addScript('qlEdit.js');

        return <<<HTML
<div id="lookupForm" class="form-inline">
    <label>Menu #</label>
    <input type="text" v-model="menuNumber" class="form-control"
        v-on:keyup.enter="getMenu();" ref="init" />
    <button type="submit" class="btn btn-default" v-on:click="getMenu();">
        Submit</button>
</div>
<hr />
<div id="entryTable">
<table class="table table-bordered table-striped">
<tr><th>Move</th><th>ID</th><th>Label</th><th>Action</th><th>Image</th><th>New Image</th><th>Move</th></tr>
<tr v-for="(entry, index) in entries" v-bind:key="entry.id">
    <td><a href="#" v-on:click.prevent="moveUp(index);">Up</a></td>
    <td>{{entry.id}}</td>
    <td><input type="text" class="form-control" v-model="entry.label" /></td>
    <td>
        <input type="text" class="form-control" v-model="entry.action" />
        <a v-if="entry.action.substring(0,2) == 'QK'" href="3"
            v-on:click.prevent="submenu(entry.action.substring(2));">Edit submenu</a>
    </td>
    <td>
        <img v-if="entry.image" v-bind:src="entry.imageURL" />
    </td>
    <td class="form-inline">
        <input type="file" v-bind:id="'newFile' + entry.id" accept="image/*" 
            v-if="entry.id > 0" />
        <button type="button" class="btn btn-default btn-sm"
            v-if="entry.id > 0" v-on:click="upload(entry.id, index);">
            Upload
        </button>
    </td>
    <td><a href="#" v-on:click.prevent="moveDown(index);">Down</a></td>
</tr>
</table>
<button type="button" class="btn btn-default" v-on:click="save();">Save</button>
&nbsp;&nbsp;&nbsp;&nbsp;
<button type="button" class="btn btn-default" v-on:click="add();">Add Entry</button>
&nbsp;&nbsp;&nbsp;&nbsp;
<button type="button" class="btn btn-default" v-if="parentID.length" v-on:click="parentMenu();">Back</button>
</div>
<br />
HTML;
    }
}

FannieDispatch::conditionalExec();

