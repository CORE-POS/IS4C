<?php

namespace COREPOS\Fannie\Plugin\IncidentTracker\notifiers;

class Email
{
    public function send($incident, $address)
    {
        if (!class_exists('PHPMailer')) {
            // can't send
            return false;
        }
        $mail = new \PHPMailer();
        $mail->From = 'alerts@wholefoods.coop';
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

