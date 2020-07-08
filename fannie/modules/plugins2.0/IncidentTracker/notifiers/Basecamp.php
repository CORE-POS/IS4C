<?php

namespace COREPOS\Fannie\Plugin\IncidentTracker\notifiers;

class Basecamp
{

    public function send($incident, $address)
    {
        $msg = "*Type*: {$incident['incidentSubType']}\n"
            . "*Store*: {$incident['storeName']}\n"
            . "*Location*: {$incident['incidentLocation']}\n"
            . "*Entered By*: {$incident['userName']}\n"
            . "*Called Police*: {$incident['police']}\n"
            . "*Requested Trespass*: {$incident['trespass']}\n"
            . "*Details*:\n"
            . $incident['details'];
        $curl = curl_init($address);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'content=' . urlencode($msg));
        $res = curl_exec($curl);
        curl_close($curl);
    }
}

