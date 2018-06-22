<?php

class InstaCompareModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    public function width()
    {
        return self::META_WIDTH_FULL;
    }

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);
        $settings = FannieConfig::config('PLUGIN_SETTINGS');

        $dbc = $this->db();
        $prep = $dbc->prepare('SELECT * FROM ' . FannieDB::fqn('InstaCompares', 'plugin:InstaCartDB') . ' WHERE upc=?');
        $row = $dbc->getRow($prep, array($upc));
        if ($row === false) {
            $row = array('url'=>'', 'price'=>'', 'salePrice'=>'', 'modified'=>'');
        }
        $prep = $dbc->prepare('SELECT normal_price FROM products WHERE upc=?');
        $ours = $dbc->getValue($prep, array($upc));

        $ret = '';
        $ret = '<div id="InstaCompDiv" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#InstaCompContents').toggle();return false;\">
                InstaCompare
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="InstaCompContents" class="panel-body' . $css . '">';
        $ret .= '<table class="table table-bordered">';
        $urlLabel = 'URL';
        if ($row['url']) {
            $urlLabel = "<a href=\"{$row['url']}\">URL</a>";
        }
        $ret .= sprintf('<tr><th>Our Regular Price</th><td>%.2f</td></tr>
            <tr><th>Their Regular Price</th><td>%.2f</td></tr>
            <tr><th>Their Sale Price</th><td>%.2f</td></tr>
            <tr><th>Last Checked</th><td>%s</td></tr>
            <tr><th>%s</th><td><input type="text" class="form-control" name="ic_url" value="%s" /></td></tr>',
            $ours, $row['price'], $row['salePrice'], $row['modified'], $urlLabel, $row['url']);
        $ret .= '</table>';
        if ($row['url']) {
            $url = FannieConfig::config('URL') . 'modules/plugins2.0/InstaCart/noauto/images/' . md5($row['url']) . '.png';
            $ret .= '<div class="ic-img">
                <p><a href="" onclick="$(\'#ic-img\').toggle(); return false;">View Listing</a></p>
                <img id="ic-img" class="collapse" src="' . $url . '" />
                </div>';
        }

        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    public function saveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $settings = FannieConfig::config('PLUGIN_SETTINGS');
        $dbc = $this->db();
        $model = new InstaComparesModel($dbc);
        $model->whichDB($settings['InstaCartDB']);
        $model->upc($upc);

        $url = FormLib::get('ic_url');
        if (trim($url) === '') {
            $model->delete();
        } else {
            $model->url($url);
            $model->save();
        }

        return true;
    }
}

