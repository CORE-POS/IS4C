<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__.'/../../../classlib2.0/FannieAPI.php');
}

class BaseItemAjax extends FannieRESTfulPage
{
    public $discoverable = false;

    public function preprocess()
    {
        $this->addRoute('get<action>', 'get<dept_defaults>', 'get<vendorChanged>');

        return parent::preprocess();
    }

    protected function get_dept_defaults_handler()
    {
        $dbc = $this->dbc();
        $json = array('tax'=>0,'fs'=>false,'nodisc'=>false,'line'=>false);
        $dept = $this->dept_defaults;
        $dModel = new DepartmentsModel($dbc);
        $dModel->dept_no($dept);
        if ($dModel->load()) {
            $json['tax'] = $dModel->dept_tax();
            $json['fs'] = $dModel->dept_fs() ? true : false;
            $json['nodisc'] = $dModel->dept_discount() ? false : true;
            $json['line'] = $dModel->line_item_discount() ? true : false;
            $json['wic'] = $dModel->dept_wicable() ? true : false;
        }

        echo json_encode($json);

        return false;
    }

    protected function get_vendorChanged_handler()
    {
        $dbc = $this->dbc();
        $json = array();
        $vend = new VendorsModel($dbc);
        $vend->vendorName($this->vendorChanged);
        $matches = $vend->find();
        $json = array('error'=>false);
        if (count($matches) == 1) {
            $json['localID'] = $matches[0]->localOriginID();
            $json['vendorID'] = $matches[0]->vendorID();
        } else {
            $json['error'] = true;
        }

        echo json_encode($json);

        return false;
    }

    protected function get_action_handler()
    {
        $dbc = $this->dbc();
        $json = array();
        $name = FormLib::get('newVendorName');
        if (empty($name)) {
            $json['error'] = 'Name is required';
        } else {
            $vendor = new VendorsModel($dbc);
            $vendor->vendorName($name);
            if (count($vendor->find()) > 0) {
                $json['error'] = 'Vendor "' . $name . '" already exists';
            } else {
                $max = $dbc->query('SELECT MAX(vendorID) AS max
                                   FROM vendors');
                $newID = 1;
                if ($max && $maxW = $dbc->fetchRow($max)) {
                    $newID = ((int)$maxW['max']) + 1;
                }
                $vendor->vendorAbbreviation(substr($name, 0, 10));
                $vendor->vendorID($newID);
                $vendor->save();
                $json['vendorID'] = $newID;
                $json['vendorName'] = $name;
            }
        }

        echo json_encode($json);

        return false;
    }

    private function dbc()
    {
        $ret = $this->connection;
        $ret->selectDB($this->config->get('OP_DB'));

        return $ret;
    }
}

FannieDispatch::conditionalExec();

