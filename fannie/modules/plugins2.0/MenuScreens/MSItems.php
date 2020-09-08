<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

if (!class_exists('MenuScreensModel')) {
    include(__DIR__ . '/models/MenuScreensModel.php');
}
if (!class_exists('MenuScreenItemsModel')) {
    include(__DIR__ . '/models/MenuScreenItemsModel.php');
}

class MSItems extends FannieRESTfulPage
{
    protected $header = 'Menu Screen Editor';
    protected $title = 'Menu Screen Editor';

    public $discoverable = false;

    protected function post_id_handler()
    {
        $itemIDs = FormLib::get('itemID', array());
        $cols = FormLib::get('col');
        $types = FormLib::get('type');
        $aligns = FormLib::get('align');
        $texts = FormLib::get('text');
        $prices = FormLib::get('price');
        $this->connection->startTransaction();
        for ($i=0; $i<count($itemIDs); $i++) {
            $itemID = $itemIDs[$i];
            $model = new MenuScreenItemsModel($this->connection);
            $model->menuScreenID($this->id);
            $model->columnNumber(isset($cols[$i]) ? $cols[$i] : 1);
            $model->rowNumber($i);
            $model->alignment(isset($aligns[$i]) ? $aligns[$i] : 'Left');
            $meta = array(
                'type' => isset($types[$i]) ? $types[$i] : 'priceditem',
                'text' => isset($texts[$i]) ? $texts[$i] : '',
                'price' => isset($prices[$i]) ? $prices[$i] : '',
            );
            $model->metadata(json_encode($meta));
            if ($itemID) {
                $model->menuScreenItemID($itemID);
            }
            $model->save();
        }
        $this->connection->commitTransaction();

        $deletes = FormLib::get('del');
        if (count($deletes) > 0) {
            list($inStr, $args) = $this->connection->safeInClause($deletes);
            $prep = $this->connection->prepare("DELETE FROM MenuScreenItems WHERE menuScreenItemID IN ({$inStr})");
            $this->connection->execute($prep, $args);
        }

        return 'MSItems.php?id=' . $this->id;
    }

    private function getItemRow($item)
    {
        $item = $item->toStdClass();
        $item->metadata = json_decode($item->metadata, true);
        $ret = <<<HTML
<tr>
    <td>
        <input type="hidden" name="col[]" value="{$item->columnNumber}" />
        <input type="hidden" name="itemID[]" value="{$item->menuScreenItemID}" />
        <input type="hidden" name="type[]" value="{$item->metadata['type']}" />
        <select name="align[]" class="form-control">;
HTML;
        foreach (array('Left', 'Center', 'Right') as $align) {
            $ret .= sprintf('<option %s>%s</option>',
                ($item->alignment == $align ? 'selected' : ''), $align);
        }
        $ret .= '</select></td><td class="form-inline">';
        switch ($item->metadata['type']) {
            case 'priceditem':
                $ret .= <<<HTML
<label>Item</label>: <input type="text" class="form-control" name="text[]" value="{$item->metadata['text']}" />
<label>Price</label>: <input type="text" class="form-control" name="price[]" value="{$item->metadata['price']}" />
HTML;
                break;
            case 'description':
                $ret .= <<<HTML
<label>Item</label>: <input type="text" class="form-control" name="text[]" value="{$item->metadata['text']}" />
<input type="hidden" class="form-control" name="price[]" value="{$item->metadata['price']}" />
HTML;
                break;
            case 'divider':
                $ret .= <<<HTML
(Divider)
<input type="hidden" class="form-control" name="text[]" value="{$item->metadata['price']}" />
<input type="hidden" class="form-control" name="price[]" value="{$item->metadata['price']}" />
HTML;
                break;
        }

        $ret .= sprintf('</td>
            <td><a href="" onclick="msi.deleteItem(this); return false;">%s</a></td>
            </tr>', COREPOS\Fannie\API\lib\FannieUI::deleteIcon());

        return $ret;
    }

    protected function get_id_view()
    {
        $model = new MenuScreensModel($this->connection);
        $model->menuScreenID($this->id);
        $model->load();

        $cols = '';
        $colOpts = '';
        for ($i=1; $i<= $model->columnCount(); $i++) {
            $cols .= '<h3>Column #' . $i . '</h3>';
            $cols .= '<table id="cols' . $i . '" class="table table-bordered table-striped cols-table">';
            $item = new MenuScreenItemsModel($this->connection);
            $item->menuScreenID($this->id);
            $item->columnNumber($i);
            foreach ($item->find() as $obj) {
                $cols .= $this->getItemRow($obj);
            }
            $cols .= '</table>';

            $colOpts .= "<option value=\"{$i}\">Column #{$i}</option>";
        }

        $this->addScript('js/msi.js');
        $this->addOnloadCommand('msi.enableSorting();');

        return <<<HTML
<form method="post" action="MSItems.php" id="menu-items-form">
    <div class="form-group">
        <button type="submit" class="btn btn-default">Save</button>
    </div>
    <input type="hidden" name="id" value="{$this->id}" />
    {$cols}
    <div class="form-group">
        <button type="submit" class="btn btn-default">Save</button>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">Add Item</div>
        <div class="panel-body">
            <div class="form-inline">
                <select id="newItemType" class="form-control">
                    <option>Priced Item</option>
                    <option>Description</option>
                    <option>Divider</option>
                </select>
                <select id="newItemCol" class="form-control">
                    {$colOpts}
                </select>
                <button type="button" class="btn btn-default" onclick="msi.addItem();">Add</button>
            </div>
        </div>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

