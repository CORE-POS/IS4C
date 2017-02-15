<?php

use COREPOS\Fannie\API\webservices\JsonEndPoint;

include(__DIR__ . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class MemberEndPoint extends JsonEndPoint
{
    protected function get()
    {
        $dbc = $this->dbc;
        $dbc->selectDB($this->config->get('OP_DB'));
        $id = trim(FormLib::get('term', 'SILLYNONSENSESEARCHTERM'));
        $ret = array('members' => array());

        $mems = array();
        if (is_numeric($id)) {
            $custP = $dbc->prepare('SELECT CardNo, personNum, LastName, FirstName FROM custdata WHERE CardNo=?'); 
            $custR = $dbc->execute($custP, array($id));
            while ($custW = $dbc->fetchRow($custR)) {
                $ret['members'][] = array(
                    'cardNo' => $custW['CardNo'],
                    'personNum' => $custW['personNum'],
                    'name' => $custW['LastName'] . ', ' . $custW['FirstName'],
                );
            }
            if (count($mems) == 0) {
                $custP = $dbc->prepare('SELECT 
                    CardNo, 
                    personNum, 
                    LastName, 
                    FirstName 
                    FROM memberCards AS m
                        INNER JOIN custdata AS c ON m.card_no=c.CardNo
                    WHERE m.upc=?');
                $custR = $dbc->execute($custP, array(BarcodeLib::padUPC($id)));
                while ($custW = $dbc->fetchRow($custR)) {
                    $ret['members'][] = array(
                        'cardNo' => $custW['CardNo'],
                        'personNum' => $custW['personNum'],
                        'name' => $custW['LastName'] . ', ' . $custW['FirstName'],
                    );
                }
            }
        } else {
            $custP = $dbc->prepare('
                SELECT CardNo, personNum, LastName, FirstName 
                FROM custdata 
                WHERE LastName LIKE ? 
                    AND Type IN (\'REG\', \'PC\')
                ORDER BY LastName, FirstName'); 
            $custR = $dbc->execute($custP, array('%' . $id . '%'));
            while ($custW = $dbc->fetchRow($custR)) {
                $ret['members'][] = array(
                    'cardNo' => $custW['CardNo'],
                    'personNum' => $custW['personNum'],
                    'name' => $custW['LastName'] . ', ' . $custW['FirstName'],
                );
            }
        }

        return $ret;
    }

    protected function post($json)
    {
        $ret = array('error' => false);
        $dbc = $this->dbc;
        $dbc->selectDB($this->config->get('OP_DB'));
        $cdata = new CustdataModel($dbc);
        $cdata->CardNo($json['cardNo']);
        $cdata->personNum($json['personNum']);
        $cdata->load();
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['MobileLaneDB']); 

        $setP = $dbc->prepare('
            UPDATE MobileTrans
            SET card_no=?
                percentDiscount=?,
                memType=?,
                staff=?
            WHERE emp_no=?
                AND register_no=?');
        $dbc->execute($setP, array($mem, $cdata->Discount(), $cdata->memType(), $cdata->staff(), $json['e'], $json['r']));


       return $ret;
    }
}

JsonEndPoint::dispatch('MemberEndPoint');

