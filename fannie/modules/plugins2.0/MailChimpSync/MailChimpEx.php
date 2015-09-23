<?php

class MailChimpEx extends Mailchimp
{
    public $export_root = 'https://api.mailchimp.com/export/1.0';
    public $export_mode = false;

    public function __construct($apikey=null, $opts=array())
    {
        parent::__construct($apikey, $opts);

        $dc           = "us1";
        if (strstr($this->apikey, "-")){
            list($key, $dc) = explode("-", $this->apikey, 2);
            if (!$dc) {
                $dc = "us1";
            }
        }
        $this->export_root = str_replace('https://api', 'https://' . $dc . '.api', $this->export_root);
        $this->export_root = rtrim($this->export_root, '/') . '/';

        $this->lists = new McListEx($this);
    }

    public function call($url, $params) 
    {
        $params['apikey'] = $this->apikey;
        
        if ($this->export_mode) {
            $params = http_build_query($params);
        } else {
            $params = json_encode($params);
        }
        $ch     = $this->ch;

        curl_setopt($ch, CURLOPT_URL, ($this->export_mode ? $this->export_root : $this->root) . $url . ($this->export_mode ? '' : '.json'));
        if ($this->export_mode) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array());
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

        $start = microtime(true);
        $this->log('Call to ' . $this->root . $url . '.json: ' . $params);
        if($this->debug) {
            $curl_buffer = fopen('php://memory', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $curl_buffer);
        }

        $response_body = curl_exec($ch);

        $info = curl_getinfo($ch);
        $time = microtime(true) - $start;
        if($this->debug) {
            rewind($curl_buffer);
            $this->log(stream_get_contents($curl_buffer));
            fclose($curl_buffer);
        }
        $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->log('Got response: ' . $response_body);

        if(curl_error($ch)) {
            throw new Mailchimp_HttpError("API call to $url failed: " . curl_error($ch));
        }
        if ($this->export_mode) {
            $result = explode("\n", $response_body);
            for ($i=0; $i<count($result); $i++) {
                $result[$i] = json_decode($result[$i]);
            }
        } else {
            $result = json_decode($response_body, true);
        }
        
        if(floor($info['http_code'] / 100) >= 4) {
            throw $this->castError($result);
        }

        return $result;
    }
}

