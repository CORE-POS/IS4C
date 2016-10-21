<?php

use COREPOS\Fannie\API\webservices\JsonEndPoint;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class ItemEndPoint extends JsonEndPoint
{
    protected function get()
    {
        $emp = FormLib::get('e', false);
        $reg = FormLib::get('r', false);
        $ret = array('error' => false, 'items'=>array());
        if ($emp === false || $reg === false) {
            $ret['error'] = 'Invalid request';
        } else {
            $dbc = $this->dbc;
            $settings = $this->config->get('PLUGIN_SETTINGS');
            $prep = $dbc->prepare('
                SELECT upc,
                    description,
                    total,
                    store_row_id
                FROM ' . $settings['MobileLaneDB'] . $dbc->sep() . 'MobileTrans
                WHERE emp_no=?
                    AND register_no=?
                ORDER BY store_row_id');
            $res = $dbc->execute($prep, array($emp, $reg));
            while ($row = $dbc->fetchRow($res)) {
                $ret['items'][]= array(
                    'upc' => $row['upc'],
                    'description' => $row['description'],
                    'total' => $row['total'],
                    'id' => $row['store_row_id'],
                );
            }
        }

        return $ret;
    }

    protected function post($json)
    {
        $dbc = $this->dbc;
        $ret = array('error' => false);
        $dbc->selectDB($this->config->get('OP_DB'));
        $upc = BarcodeLib::padUPC($json['upc']);
        $itemP = $dbc->prepare('
            SELECT description,
                normal_price,
                special_price,
                discount,
                discounttype,
                tax,
                foodstamp,
                cost,
                department,
                mixmatchcode
            FROM products 
            WHERE upc=?
                AND scale=0');
        $item = $dbc->getRow($itemP, array($upc));
        if ($item === false) {
            $ret['error']  = 'Item not found';
        } else {
            $settings = $this->config->get('PLUGIN_SETTINGS');
            $dbc->selectDB($settings['MobileLaneDB']);
            $mgr = new MobileTransManager($dbc, $this->config);
            $model = new MobileTransModel($dbc);
            $model->datetime(date('Y-m-d H:i:s'));
            $model->emp_no($this->emp);
            $model->register_no($this->reg);
            $model->trans_no($mgr->getTransNo($this->emp, $this->reg));
            $model->trans_type('I');
            $model->department($item['department']);
            $model->quantity(1);
            $model->cost($item['cost']);
            $model->regPrice($item['normal_price']);
            $model->unitPrice($item['normal_price']);
            $model->total($item['normal_price']);
            $model->tax($item['tax']);
            $model->foodstamp($item['foodstamp']);
            $model->discountable($item['discount']);
            $model->discounttype($item['discounttype']);
            $model->ItemQtty(1);
            $model->mixMatch($item['mixmatchcode']);
            if ($item['discounttype'] == 1) {
                $model->unitPrice($item['special_price']);
                $model->total($item['special_price']);
                $model->discount($item['normal_price'] - $item['special_price']);
            }
            $saved = $model->save();
            if ($saved === false) {
                $ret['error'] = 'Error adding item';
            } else {
                $ret['item'] = array(
                    'id' => $saved,
                    'upc' => $model->upc(),
                    'description' => $model->description(),
                    'total' => $model->total(),
                );
            }
        }

        return $ret;
    }
}

JsonEndPoint::dispatch('ItemEndPoint');

