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

class MSRender extends FannieRESTfulPage
{
    public $discoverable = false;
    protected $window_dressing = false;

    private function renderColumn($id, $num)
    {
        $item = new MenuScreenItemsModel($this->connection);
        $item->menuScreenID($this->id);
        $item->columnNumber($num);
        $ret = '';
        foreach ($item->find('rowNumber') as $obj) {
            $obj = $obj->toStdClass();
            $meta = json_decode($obj->metadata, true);
            $ret .= '<div class="mstype-' . $meta['type'] . ' msalign-' . strtolower($obj->alignment) . '">';
            $ret .= $this->renderMeta($meta);
            $ret .= '</div>';
        }

        return $ret;
    }

    private function renderMeta($meta)
    {
        switch ($meta['type']) {
        case 'priceditem':
            return sprintf('<span class="priced-text">%s</span><span class="priced-price">%s</span>', $meta['text'], $meta['price']);
        case 'description':
            return sprintf('<span class="msdescription">%s</span>', $meta['text']);
        case 'divider':
            return '<span class="msdivider"></span>';
        }

        return '';
    }

    protected function get_id_view()
    {
        $menu = new MenuScreensModel($this->connection);
        $menu->menuScreenID($this->id);
        $menu->load();
        $ret = $menu->layout();

        for ($i=1; $i<=$menu->columnCount(); $i++) {
            $col = $this->renderColumn($this->id, $i);
            $ret = str_replace('{{col' . $i . '}}', $col, $ret);
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec();

