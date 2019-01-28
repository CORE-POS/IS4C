<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeAjax extends FannieRESTfulPage
{
    public $discoverable = false;

    public function preprocess()
    {
        $this->addRoute(
            'post<id><strict>',
            'post<id><organic>',
            'post<id><multi>',
            'post<id><vendorID>',
            'post<id><rcat>',
            'post<id><icat>',
            'post<id><storeID><inUse>',
            'post<id><storeID><internal>'
        );

        return parent::preprocess();
    }

    private function getOthersInSort($dbc, $sort, $lc)
    {
        if (empty(trim($sort))) {
            return '';
        }
        $ret = "<p class=\"small\"><strong>{$sort}</strong><br />";
        $prep = $dbc->prepare('SELECT likeCode, likeCodeDesc FROM likeCodes 
            WHERE sortRetail=? AND likeCode <> ? ORDER BY likeCodeDesc');
        $res = $dbc->execute($prep, array($sort, $lc));
        while ($row = $dbc->fetchRow($res)) {
            $ret .= sprintf('<a href="LikeCodeEditor.php?start=%d">%d %s</a><br />',
                $row['likeCode'], $row['likeCode'], $row['likeCodeDesc']);
        }
        $ret .= '</p>';

        return $ret;
    }

    private function getSorts($dbc, $type)
    {
        $col = $type == 'retail' ? 'sortRetail' : 'sortInternal';
        $res = $dbc->query("SELECT {$col} FROM likeCodes WHERE {$col} IS NOT NULL AND {$col} <> '' GROUP BY {$col}");
        $ret = array();
        while ($row = $dbc->fetchRow($res)) {
            $ret[] = $row[$col];
        }

        return $ret;
    }

    protected function get_id_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $likeCode = new LikeCodesModel($dbc);
        $likeCode->likeCode($this->id);
        $likeCode->load();

        $vendors = new VendorsModel($dbc);
        $vOpts = $vendors->toOptions($likeCode->preferredVendorID());

        $activeP = $dbc->prepare('
            SELECT s.storeID AS sID, s.description, l.*
            FROM Stores AS s
                LEFT JOIN LikeCodeActiveMap AS l ON s.storeID=l.storeID AND l.likeCode=?
            WHERE s.hasOwnItems=1
            ORDER BY s.storeID');
        $activeR = $dbc->execute($activeP, array($this->id));
        $table = '';
        while ($activeW = $dbc->fetchRow($activeR)) {
            $table .= sprintf('<tr><td>%s</td>
                <td><input type="checkbox" onchange="lcEditor.toggleUsage(%d,%d);" %s /></td>
                <td><input type="checkbox" onchange="lcEditor.toggleInternal(%d,%d);" %s /></td>
                <td>%s</td></tr>',
                $activeW['description'],
                $this->id, $activeW['sID'], $activeW['inUse'] ? 'checked' : '',
                $this->id, $activeW['sID'], $activeW['internalUse'] ? 'checked' : '',
                $activeW['lastSold']
            );
        }
        if ($table !== '') {
            $table = '<table class="table small table-bordered table-striped">
                <tr><th>Store</th><th>Active</th><th>Internal</th><th>Last Sold</th></tr>'
                . $table . '</table>';
        }

        $prep = $dbc->prepare("SELECT u.upc,p.description FROM
                upcLike AS u 
                    " . DTrans::joinProducts('u', 'p', 'INNER') . "
                WHERE u.likeCode=?
                ORDER BY p.description");
        $res = $dbc->execute($prep,array($this->id));
        $ret = "";
        while ($row = $dbc->fetch_row($res)) {
            $ret .= "<a style=\"font-size:90%;\" href=\"../ItemEditorPage.php?searchupc=$row[0]\">";
            $ret .= $row[0]."</a> ".substr($row[1],0,25)."<br />";
        }
        if ($ret === '') {
            $ret = '<div class="alert alert-danger">Empty like code</div>';
        }

        $preamble = sprintf('<div class="panel panel-default">
            <div class="panel panel-heading">' . $likeCode->likeCode() . ' ' . $likeCode->likeCodeDesc() . '</div>
            <div class="panel panel-body">
                <label><input type="checkbox" %s onchange="lcEditor.toggleStrict(%d);" /> Strict</label>
                &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
                <label><input type="checkbox" %s onchange="lcEditor.toggleOrganic(%d);" /> Organic</label>
                &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
                <label><input type="checkbox" %s onchange="lcEditor.toggleMulti(%d);" /> Multi-Vendor</label>
                <p>
                    <label>Preferred Vendor</label>
                    <select onchange="lcEditor.updateVendor(%d, this.value);" class="form-control v-chosen">
                        <option value="0">Select...</option>%s
                    </select>
                </p>
                <p>
                    <label>Retail Category</label>
                    <input type="text" onchange="lcEditor.retailCat(%d, this.value);"
                        class="form-control retailCat" value="%s" />
                </p>
                <p>
                    <label>Internal Category</label>
                    <input type="text" onchange="lcEditor.internalCat(%d, this.value);" 
                        class="form-control internalCat" value="%s" />
                </p>
                %s
                <p>%s</p>
            </div>
            </div>',
            $likeCode->strict() ? 'checked' : '',
            $likeCode->likeCode(),
            $likeCode->organic() ? 'checked' : '',
            $likeCode->likeCode(),
            $likeCode->multiVendor() ? 'checked' : '',
            $likeCode->likeCode(),
            $likeCode->likeCode(),
            $vOpts,
            $likeCode->likeCode(), $likeCode->sortRetail(),
            $likeCode->likeCode(), $likeCode->sortInternal(),
            $table,
            $ret
        );

        $retail = $this->getSorts($dbc, 'retail');
        $internal = $this->getSorts($dbc, 'internal');
        $others = $this->getOthersInSort($dbc, $likeCode->sortRetail(), $this->id);
        $json = array('form'=>$preamble, 'retail'=>$retail, 'internal'=>$internal, 'similar'=>$others);
        echo json_encode($json);

        return false;
    }

    protected function post_id_strict_handler()
    {
        return $this->toggleField($this->id, 'strict');
    }

    protected function post_id_organic_handler()
    {
        return $this->toggleField($this->id, 'organic');
    }

    protected function post_id_multi_handler()
    {
        return $this->toggleField($this->id, 'multiVendor');
    }

    protected function getLcModel($likeCode)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new LikeCodesModel($dbc);
        $model->likeCode($likeCode);
        if (!$model->load()) {
            echo 'No such likecode';
            return false;
        }

        return $model;
    }

    protected function post_id_vendorID_handler()
    {
        $model = $this->getLcModel($this->id);
        if ($model === false) {
            return false;
        }

        $model->preferredVendorID($this->vendorID);
        $model->save();

        echo 'Done';
        return false;
    }

    protected function post_id_rcat_handler()
    {
        $model = $this->getLcModel($this->id);
        if ($model === false) {
            return false;
        }

        $model->sortRetail($this->rcat);
        $model->save();

        echo 'Done';
        return false;
    }
    protected function post_id_icat_handler()
    {
        $model = $this->getLcModel($this->id);
        if ($model === false) {
            return false;
        }

        $model->sortInternal($this->icat);
        $model->save();

        echo 'Done';
        return false;
    }

    protected function post_id_storeID_inUse_handler()
    {
        return $this->toggleStoreField($this->storeID, $this->id, 'inUse');
    }

    protected function post_id_storeID_internal_handler()
    {
        return $this->toggleStoreField($this->storeID, $this->id, 'internalUse');
    }

    private function toggleStoreField($store, $likeCode, $field)
    {
        $model = new LikeCodeActiveMapModel($this->connection);
        $model->likeCode($likeCode);
        $model->storeID($store);
        $model->load();
        $model->$field($model->$field() ? 0 : 1);
        $model->save();

        echo 'Done';
        return false;

    }

    private function toggleField($likeCode, $field)
    {
        $model = $this->getLcModel($likeCode);
        if ($model === false) {
            return false;
        }

        $model->$field($model->$field() ? 0 : 1);
        $model->save();

        echo 'Done';
        return false;
    }

    public function unitTest($phpunit)
    {
        ob_start();
        $this->id = 1;
        $phpunit->assertEquals(false, $this->get_id_handler());
        $this->strict = 1;
        $phpunit->assertEquals(false, $this->post_id_strict_handler());
        $this->organic = 1;
        $phpunit->assertEquals(false, $this->post_id_organic_handler());
        $this->multi = 1;
        $phpunit->assertEquals(false, $this->post_id_multi_handler());
        $this->vendorID = 1;
        $phpunit->assertEquals(false, $this->post_id_vendorID_handler());
        $this->storeID = 1;
        $this->inUse = 1;
        $phpunit->assertEquals(false, $this->post_id_storeID_inUse_handler());
        $this->internal = 1;
        $phpunit->assertEquals(false, $this->post_id_storeID_internal_handler());
        ob_end_clean();
    }
}

FannieDispatch::conditionalExec();

