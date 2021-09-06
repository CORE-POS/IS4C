<?php

namespace Gohanman\Otto;

class Otto
{
    private $connectorURL;

    public function __construct($url)
    {
        $this->connectorURL = $url;
    }

    public function setURL($url)
    {
        $this->connectorURL = $url;
    }

    public function post($msg)
    {
        $curl = curl_init($this->connectorURL);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $msg->toJSON());
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
}

