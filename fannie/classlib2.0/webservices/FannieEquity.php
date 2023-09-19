<?php

namespace COREPOS\Fannie\API\webservices;
use \FannieDB;
use \FannieConfig;
use \DTrans;

class FannieEquity extends FannieWebService
{
    public function run($args=[])
    {
        $config = FannieConfig::factory();
        $dbc = FannieDB::get($config->get('TRANS_DB'));
        $OP = $config->get('OP_DB') . $dbc->sep();
        $submethod = strtolower($args->submethod);
        $ret = array();

        /*******************************
         * validate all inputs
         *******************************/

        if ($submethod != 'add_equity') {
            $ret['error'] = [
                'code' => 1,
                'message' => 'only add_equity submethod is supported',
            ];
            return $ret;
        }

        // assume default employee from config unless specified
        $empNo = $args->columns->emp_no;
        if (!isset($empNo)) {
            $empNo = $config->get('EMP_NO');
        }

        // assume default register from config unless specified
        $regNo = $args->columns->register_no;
        if (!isset($regNo)) {
            $regNo = $config->get('REGISTER_NO');
        }

        // caller must specify card number
        $cardNo = $args->columns->card_no;
        if (!isset($cardNo)) {
            $ret['error'] = [
                'code' => 1,
                'message' => 'card_no column is required',
            ];
            return $ret;
        }

        // caller must specify equity total
        $total = $args->columns->total;
        if (!isset($total)) {
            $ret['error'] = [
                'code' => 1,
                'message' => 'total column is required',
            ];
            return $ret;
        }

        // caller must specify department
        $deptNo = $args->columns->department;
        if (!isset($deptNo)) {
            $ret['error'] = [
                'code' => 1,
                'message' => 'department column is required',
            ];
            return $ret;
        }

        // fetch list of equity departments
        $result = preg_match_all("/[0-9]+/", $config->get('EQUITY_DEPARTMENTS'), $equityDepartments);
        if ($result == 0){
            $ret['error'] = [
                'code' => 1,
                'message' => 'cannot read list of equity departments from config',
            ];
            return $ret;
        }
        $equityDepartments = $equityDepartments[0];

        // validate requested department
        if (!in_array($deptNo, $equityDepartments)) {
            $ret['error'] = [
                'code' => 1,
                'message' => "department $deptNo cannot be used for equity",
            ];
            return $ret;
        }

        // make sure caller specified the tender
        $tenderCode = $args->columns->tender;
        if (!isset($tenderCode)) {
            $ret['error'] = [
                'code' => 1,
                'message' => 'tender column is required',
            ];
            return $ret;
        }

        // fetch list of tenders
        $tendersP = $dbc->prepare("SELECT TenderCode, TenderName FROM {$OP}tenders " .
                                  "WHERE TenderModule != 'DisabledTender' ORDER BY TenderName");
        $tendersR = $dbc->execute($tendersP, array());
        $tenders = [];
        while ($tender = $dbc->fetchArray($tendersR)) {
             $tenders[$tender['TenderCode']] = $tender["TenderName"];
        }

        // validate requested tender
        if (!isset($tenders[$tenderCode])) {
            $ret['error'] = [
                'code' => 1,
                'message' => 'speficied tender is not valid',
            ];
            return $ret;
        }
        $tenderName = $tenders[$tenderCode];

        // caller may specify transaction number, or we auto-generate
        $transNo = $args->columns->trans_no;
        if (!isset($transNo)) {
            $transNo = DTrans::getTransNo($dbc, $empNo, $regNo);
        }

        // caller may specify extra comment
        $comment = $args->columns->comment;
        if (!isset($comment)) {
            $comment = null;
        }

        /*******************************
         * insert to dtransactions
         *******************************/

        $common = [
            'card_no' => $cardNo,
            'register_no' => $regNo,
            'emp_no' => $empNo,
        ];

        $dbc->startTransaction();

        // open ring for equity
        $params = (array)$common;
        if (!DTrans::addOpenRing($dbc, $deptNo, $total, $transNo, $params)) {
            $dbc->rollbackTransaction();
            $ret['error'] = [
                'code' => 2,
                'message' => 'Failed to insert open ring item',
            ];
            return $ret;
        }

        // tender to balance books
        $params = (array)$common;
        $params['description'] = $tenderName;
        $params['trans_type'] = 'T';
        $params['trans_subtype'] = $tenderCode;
        $params['total'] = $total * -1;
        if (!DTrans::addItem($dbc, $transNo, $params)) {
            $dbc->rollbackTransaction();
            $ret['error'] = [
                'code' => 2,
                'message' => 'Failed to insert tender item',
            ];
            return $ret;
        }

        // optionally record extra comment line
        if ($comment) {
            $params = (array)$common;
            $params['description'] = $comment;
            $params['trans_type'] = 'C';
            $params['trans_subtype'] = 'CM';
            if (!DTrans::addItem($dbc, $transNo, $params)) {
                $dbc->rollbackTransaction();
                $ret['error'] = [
                    'code' => 2,
                    'message' => 'Failed to insert comment item',
                ];
                return $ret;
            }
        }

        $dbc->commitTransaction();

        $result = (array)$common;
        $result['trans_no'] = $transNo;
        $result['department'] = $deptNo;
        $result['total'] = $total;
        $result['fullTransactionNumber'] = "$empNo-$regNo-$transNo";
        $ret['result'] = $result;
        return $ret;
    }
}
