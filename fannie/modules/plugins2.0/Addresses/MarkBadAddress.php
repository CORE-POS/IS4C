<?php

use COREPOS\Fannie\API\jobs\Job;
use COREPOS\Fannie\API\member\MemberREST;

class MarkBadAddress extends Job
{
    public function run()
    {
        $status = 'INACT';
        $id = $this->data['id'];
        $code = $this->data['code'];

        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));

        $cas_model = new CustomerAccountSuspensionsModel($dbc);
        $cas_model->card_no($id);
        $current_id = 0;
        $account = \COREPOS\Fannie\API\member\MemberREST::get($id);

        $discount = 0;
        foreach ($account['customers'] as $c) {
            if ($c['accountHolder']) {
                $discount = $c['discount'];
                break;
            }
        }
        
        $susp = new SuspensionsModel($dbc);
        $susp->cardno($id);
        $susp->type( $status == 'TERM' ? 'T' : 'I' );           
        $susp->memtype1($account['customerTypeID']);
        $susp->memtype2($account['memberStatus']);
        $susp->suspDate(date('Y-m-d H:i:s'));
        $susp->reason('');
        $susp->mailflag($account['contactAllowed']);
        $susp->discount($discount);
        $susp->chargelimit($account['chargeLimit']);
        $susp->reasoncode($code);
        $susp->save();

        $cas_model->savedType($account['memberStatus']);
        $cas_model->savedMemType($account['customerTypeID']);
        $cas_model->savedDiscount($discount);
        $cas_model->savedChargeLimit($account['chargeLimit']);
        $cas_model->savedMailFlag($account['contactAllowed']);
        $cas_model->suspensionTypeID( $status == 'TERM' ? 2 : 1 );
        $cas_model->tdate(date('Y-m-d H:i:s'));
        $cas_model->username('undeliverable');
        $cas_model->reasonCode($code);
        $cas_model->active(1);
        $current_id = $cas_model->save();

        $history = new SuspensionHistoryModel($dbc);
        $history->username('undeliverable');
        $history->cardno($id);
        $history->reasoncode($code);
        $history->postdate(date('Y-m-d H:i:s'));
        $history->save();

        $json = array(
            'cardNo' => $id,
            'chargeLimit' => 0,
            'activeStatus' => $status,
            'customerTypeID' => 0,
            'contactAllowed' => 0,
            'customers' => array(),
        );
        foreach ($account['customers'] as $c) {
            $c['discount'] = 0;
            $json['customers'][] = $c;
        }
        COREPOS\Fannie\API\member\MemberREST::post($id, $json);

        if ($current_id != 0) {
            $cas_model->reset();
            $cas_model->card_no($id);
            $cas_model->active(1);
            foreach($cas_model->find() as $obj) {
                if ($obj->customerAccountSuspensionID() != $current_id) {
                    $obj->active(0);
                    $obj->save();
                }
            }
        }

        if (filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
            // usually lots of these gets queued
            // delay to avoid seeming spam-y
            $wait = rand(30, 60);
            sleep($wait);
            $to = $this->data['email'];
            $subject = 'Whole Foods Co-op Address';
            $body = "Hello,\n\nWhole Foods Co-op does not currently have a valid mailing address for your account."
                . " Please fill out this form to update your contact information:\n\n"
                . "https://wholefoods.coop/ownership-1/owner-solutions/\n\n"
                . "Your Owner Number is {$id}\n";
            $headers = "From: Whole Foods Co-op <info@wholefoods.coop>\r\n";
            mail($to, $subject, $body, $headers);
        }
    }
}


