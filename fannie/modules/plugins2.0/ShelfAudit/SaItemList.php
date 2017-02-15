<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SaItemList extends SaHandheldPage
{
    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Build List] is an interface for scanning and entering quantities on
    hand using a handheld device.';
    protected $enable_linea = true;
    protected $must_authenticate = true;
    private $section = 1;

    private function exportList()
    {
        $table = $this->getList();
        $arr = COREPOS\Fannie\API\data\DataConvert::htmlToArray($table);
        $out = COREPOS\Fannie\API\data\DataConvert::arrayToCsv($arr);
        header('Content-Type: application/ms-excel');
        header('Content-Disposition: attachment; filename="Scan List.csv"');
        return $out;
    }

    public function preprocess()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $uid = FannieAuth::getUID($this->current_user);
        $this->section = FormLib::get('section', 1);

        if (FormLib::get('clear') === '1') {
            $table = $settings['ShelfAuditDB'] . $dbc->sep() . 'SaList';
            $prep = $dbc->prepare('
                UPDATE ' . $table . '
                SET clear=1
                WHERE uid=?
            ');
            $dbc->execute($prep, array($uid));
            return parent::preprocess();
        } elseif (FormLib::get('export') === '1') {
            echo $this->exportList();
            $this->enable_linea=false;
            return false;
        }

        return parent::preprocess();
    } 

    protected function get_id_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $uid = FannieAuth::getUID($this->current_user);
        if ($this->id !== '') {
            $upc = BarcodeLib::padUPC($this->id);
            $prep = $dbc->prepare('
                SELECT p.description,
                    p.brand,
                    p.size,
                    COALESCE(s.quantity, 0) AS qty
                FROM products AS p
                    LEFT JOIN ' . $settings['ShelfAuditDB'] . $dbc->sep() . 'SaList AS s ON p.upc=s.upc AND s.clear=0
                WHERE p.upc=?
            ');
            $row = $dbc->getRow($prep, array($upc));
            if ($row) {
                $this->saveRowToList($dbc, $upc, $row, $settings);
            }
        }
        
        return true;
    }

    protected function get_id_view()
    {
        return $this->get_view();
    }

    private function saveRowToList($dbc, $upc, $row, $settings)
    {
        $dbc->selectDB($settings['ShelfAuditDB']);
        $model = new SaListModel($dbc);
        $model->upc($upc);
        $model->section($this->section);
        $model->clear(0);
        $model->uid(FannieAuth::getUID($this->current_user));
        $entries = $model->find('date', true);
        if (count($entries) > 0) {
            $entries[0]->tdate(date('Y-m-d H:i:s'));
            $entries[0]->quantity(1);
            return $entries[0]->save();
        } else {
            $model->tdate(date('Y-m-d H:i:s'));
            $model->quantity(1);
            return $model->save();
        }
    }

    // override ajax behavior of SaHandheldPage
    protected function upcForm($section)
    {
        ?>
<form method="get" id="upcScanForm">
<a href="SaMenuPage.php">Menu</a>
<input type="hidden" name="section" id="section" value="<?php echo ((int)$section); ?>" />
<br />
<div class="form-group form-inline">
    <div class="input-group">
        <label class="input-group-addon">UPC</label>
        <input type="number" size="10" name="id" id="upc_in" 
            onfocus="handheld.paintFocus('upc_in');"
            class="focused form-control" tabindex="1"
        />
    </div>
    <button type="submit" class="btn btn-success" tabindex="-1" id="goBtn">Go</button>
</div>
</form>
        <?php
    }

    public function get_view()
    {
        $elem = '#upc_in';
        if (isset($this->current_item_data['upc']) && isset($this->current_item_data['desc'])) $elem = '#cur_qty';
        $this->addOnloadCommand('$(\'#upc_in\').focus();');
        $this->addOnloadCommand("enableLinea('#upc_in');\n");
        ob_start();
        $this->upcForm($this->section);
        if (isset($this->current_item_data['upc']) && !isset($this->current_item_data['desc'])) {
            echo '<div class="alert alert-danger">Item not found (' 
                . $this->current_item_data['upc'] . ')</div>'; 
        } 
        echo '<div class="table-responsive">';
        echo $this->getList();
        echo '</div>
            <p>
            <a href="?clear=1" class="btn btn-default btn-danger"
                onclick="return window.confirm(\'Clear list?\');">
                Clear List
            </a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            |
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="?export=1" class="btn btn-default btn-info">
                Export List
            </a>
            </p>';

        $this->addScript('js/handheld.js');
        return ob_get_clean();
    }

    private function getList()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($this->config->get('OP_DB'));
        $uid = FannieAuth::getUID($this->current_user);
        $prep = $this->connection->prepare('
            SELECT s.upc,
                p.brand,
                p.description,
                p.size,
                s.quantity as qty,
                v.sku,
                n.vendorName
            FROM ' . $settings['ShelfAuditDB'] . $this->connection->sep() . 'SaList AS s
                ' . DTrans::joinProducts('s') . '
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
            WHERE s.clear=0
                AND s.quantity <> 0
                AND s.uid=?
                AND s.section=?
            ORDER BY s.tdate DESC
        ');
        $ret = '<ul class="nav nav-tabs" role="tablist">';
        for ($i=1; $i<=3; $i++) {
            $ret .= sprintf('<li role="presentation" %s>
                <a href="#section%d" aria-controls="section%d" role="tab" data-toggle="tab"
                onclick="$(\'#section\').val(%d); return false;">Set %d</a></li>',
                ($i == $this->section ? 'class="active"' : ''),
                $i, $i, $i, $i);
        }
        $ret .= '</ul>';
        $ret .= '<div class="tab-content">';
        for ($i=1; $i<=3; $i++) {
            $res = $this->connection->execute($prep, array($uid, $i));
            $ret .= sprintf('<div role=tablepanel" class="tab-pane %s" id="section%d">',
                ($i == $this->section ? 'active' : ''), $i, $i);
            $ret .= '
                <table class="table table-bordered table-striped small">
                <tr>
                    <th>UPC</th>
                    <th>SKU</th>
                    <th>Vendor</th>
                    <th>Brand</th>
                    <th>Description</th>
                    <th>Size</th>
                    <th>Qty</th>
                </tr>';
            $upcs = '';
            while ($row = $this->connection->fetchRow($res)) {
                $ret .= sprintf('<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%d</td>
                    </tr>',
                    $row['upc'],
                    $row['sku'],
                    $row['vendorName'],
                    $row['brand'],
                    $row['description'],
                    $row['size'],
                    $row['qty']
                ); 
                $upcs .= $row['upc'] . "\n";
            }
            $ret .= '</table>';
            $ret .= '<textarea>' . $upcs . '</textarea>';
            $ret .= '</div>';
        }
        $ret .= '</div>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

