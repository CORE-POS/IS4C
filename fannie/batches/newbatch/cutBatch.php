<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class cutBatch extends FannieRESTfulPage {

    public $discoverable = false;

    protected function post_id_handler()
    {
        $ret = '';
        $timeStamp = date('Y-m-d h:i:s');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $id = $this->id;
        $uid = FormLib::get('uid', 0);
        
        $blModel = new BatchListModel($dbc);
        $blModel->batchID($id);
        $blModel->load();
        $cut = new BatchCutPasteModel($dbc);
        $cut->batchID($id);
        $cut->uid($uid);
        
        $ret = array('error' => 0);
        foreach ($blModel->find() as $item) {
            $cut->upc($item->upc());
            $cut->batchID($id);
            $cut->uid($uid);
            $cut->tdate($timeStamp);
            $saved = $cut->save();
            if (!$saved) {
                $ret['error'] = 1;
                $ret['error_msg'] = 'Save failed';
            } else {
                $bu = new BatchUpdateModel($dbc);
                $bu->batchID($id);
                $bu->upc($item->upc());
                $bu->logUpdate($bu::UPDATE_REMOVED);
            }
        }

        echo json_encode($ret);

        return false;
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        ob_start();
        $phpunit->assertEquals(false, $this->post_id_handler());
        ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

