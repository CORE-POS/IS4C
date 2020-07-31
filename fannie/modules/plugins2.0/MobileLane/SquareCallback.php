<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SquareCallback extends FannieRESTfulPage
{
    private function checkAndroid()
    {
        if (isset($_REQUEST['com.squareup.pos.CLIENT_TRANSACTION_ID']) && isset($_REQUEST['com.squareup.pos.SERVER_TRANSACTION_ID'])) {
            return array(
                'clientTransID' => $_REQUEST['com.squareup.pos.CLIENT_TRANSACTION_ID'],
                'serverTransID' => $_REQUEST['com.squareup.pos.SERVER_TRANSACTION_ID'],
                'error' => false,
            );
        } elseif (isset($_REQUEST['com.squareup.pos.ERROR_CODE'])) {
            return array(
                'clientTransID' => '',
                'serverTransID' => '',
                'error' => $_REQUEST['com.squareup.pos.ERROR_CODE'],
            );
        }

        return false;
    }

    private function checkIOS()
    {
        if (isset($_REQUEST['data'])) {
            $data = json_decode($_REQUEST['data'], true);
            if (isset($data['client_transaction_id']) && isset($data['transaction_id'])) {
                return array(
                    'clientTransID' => $data['client_transaction_id'],
                    'serverTransID' => $data['transaction_id'],
                    'error' => false,
                );
            } elseif (isset($data['error_code'])) {
                return array(
                    'clientTransID' => '',
                    'serverTransID' => '',
                    'error' => $data['error_code'],
                );
            }
        }

        return false;
    }

    protected function get_handler()
    {
        $payment = $this->checkIOS();
        if ($payment === false) {
            $payment = $this->checkAndroid();
        }

        return 'index.html';
    }
}

FannieDispatch::conditionalExec();

