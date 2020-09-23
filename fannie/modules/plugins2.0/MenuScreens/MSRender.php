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
    private $step_number = 0;

    private function renderColumn($id, $num)
    {
        $item = new MenuScreenItemsModel($this->connection);
        $item->menuScreenID($this->id);
        $item->columnNumber($num);
        $ret = '';
        $line = 0;
        foreach ($item->find('rowNumber') as $obj) {
            $obj = $obj->toStdClass();
            $meta = json_decode($obj->metadata, true);
            $ret .= '<div class="mstype-' . $meta['type'] . ' msalign-' . strtolower($obj->alignment) . ' '
                . ($line % 2 == 0 ? 'even-line': 'odd-line') . ' msline msline-' . $line . '">';
            $ret .= $this->renderMeta($meta);
            $ret .= '</div>';
            $line++;
            if ($meta['type'] == 'header') {
                $line = 0;
            }
        }

        return $ret;
    }

    private function renderMeta($meta)
    {
        switch ($meta['type']) {
        case 'priceditem':
            if (preg_match('/(.+)(\(.+?\))(.*)/', $meta['text'], $matches)) {
                $meta['text'] = $matches[1] . '<span class="parens">' . $matches[2] . '</span>' . $matches[3];
            }
            return sprintf('<span class="priced-text">%s</span><span class="priced-price">%s</span>', $meta['text'], $meta['price']);
        case 'dualpriceditem':
            if (preg_match('/(.+)(\(.+?\))(.*)/', $meta['text'], $matches)) {
                $meta['text'] = $matches[1] . '<span class="parens">' . $matches[2] . '</span>' . $matches[3];
            }
            if (preg_match('/(.+)(\(.+?\))(.*)/', $meta['price'], $matches)) {
                $meta['price'] = $matches[1] . '<span class="parens">' . $matches[2] . '</span>' . $matches[3];
            }
            if (preg_match('/(.+)(\(.+?\))(.*)/', $meta['price2'], $matches)) {
                $meta['price2'] = $matches[1] . '<span class="parens">' . $matches[2] . '</span>' . $matches[3];
            }
            return sprintf('<span class="dual-priced-text">%s</span><span class="dual-price1">%s</span><span class="dual-price2">%s</span>', $meta['text'], $meta['price'], $meta['price2']);
        case 'header':
            return sprintf('<span class="msheader">%s</span><span class="dual-price1">12oz</span><span class="dual-price2">16oz</span>', $meta['text']);
        case 'description':
            if (preg_match('/(.+)(\(.+?\))(.*)/', $meta['text'], $matches)) {
                $meta['text'] = $matches[1] . '<span class="parens">' . $matches[2] . '</span>' . $matches[3];
            }
            $meta['text'] = str_replace('OG', '<span class="organic">OG</span>', $meta['text']);
            return sprintf('<span class="msdescription">%s</span>', $meta['text']);
        case 'divider':
            return '<span class="msdivider"></span>';
        case 'sandwichstep':
            $this->step_number++;
            $ret = '<div class="step-header"><span class="circled">' . $this->step_number . '</span> ' . $meta['text'] . '</div>';
            $opts = explode(',', $meta['option']);
            $opts = array_map('trim', $opts);
            $opts = array_map(function ($i) { return str_replace(' ', '&nbsp;', $i); }, $opts);
            $opts = array_map(function ($i) { return str_replace('-', '&#8209;', $i); }, $opts);
            $opts = implode(' &#x2027; ', $opts);
            $opts = str_replace('OG', '<span class="organic">OG</span>', $opts);
            $opts = str_replace('HM', '<span class="organic">HM</span>', $opts);
            $ret .= '<div class="step-options">' . $opts . '</div>';
            $ret .= '<div class="step-extra">' . $meta['extra'] . '</div>';
            return $ret;
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

