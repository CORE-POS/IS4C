<?php

namespace COREPOS\Fannie\Plugin\IncidentTracker\notifiers;
use Maknz\Slack\Client;

class Slack
{
    public function send($incident, $address)
    {
        if (!class_exists('Maknz\\Slack\\Client')) {
            // can't send
            return false;
        }

        $client = new Client($address);
        $msg = "*Type*: {$incident['incidentSubType']}\n"
            . "*Store*: {$incident['storeName']}\n"
            . "*Location*: {$incident['incidentLocation']}\n"
            . "*Entered By*: {$incident['userName']}\n"
            . "*Called Police*: {$incident['police']}\n"
            . "*Requested Trespass*: {$incident['trespass']}\n"
            . "*Details*:\n"
            . $incident['details'];
        $client->send($msg);
    }
}

