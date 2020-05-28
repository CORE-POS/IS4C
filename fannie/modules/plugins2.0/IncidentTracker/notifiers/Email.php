<?php

namespace COREPOS\Fannie\Plugin\IncidentTracker\notifiers;
use COREPOS\Fannie\API\data\pipes\OutgoingEmail;

class Email
{
    public function send($incident, $address)
    {
        if (!OutgoingEmail::available()) {
            // can't send
            return false;
        }
        $mail = OutgoingEmail::get();
        $mail->From = 'alerts@wholefoods.coop';
        $mail->FromName = 'Alerts Notification';
        foreach (explode(',', $address) as $a) {
            $mail->addAddress(trim($a));
        }
        $mail->Subject = 'WFC Alert Incident';

        $msg = "Type: {$incident['incidentSubType']}\n"
            . "Store: {$incident['storeName']}\n"
            . "Location: {$incident['incidentLocation']}\n"
            . "Entered By: {$incident['userName']}\n"
            . "Called Police: {$incident['police']}\n"
            . "Requested Trespass: {$incident['trespass']}\n"
            . "Details:\n"
            . $incident['details'];
        $mail->Body = $msg;
        $mail->send();
    }
}

