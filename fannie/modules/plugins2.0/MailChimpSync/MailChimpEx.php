<?php

if (class_exists('Mailchimp')) {

class MailChimpEx extends Mailchimp
{
    public $export_root = 'https://api.mailchimp.com/export/1.0';
    public $export_mode = false;

    public function __construct($apikey=null, $opts=array())
    {
        parent::__construct($apikey, $opts);

        $domain_code           = "us1";
        if (strstr($this->apikey, "-")){
            list($key, $domain_code) = explode("-", $this->apikey, 2);
            if (!$domain_code) {
                $domain_code = "us1";
            }
        }
        $this->export_root = str_replace('https://api', 'https://' . $domain_code . '.api', $this->export_root);
        $this->export_root = rtrim($this->export_root, '/') . '/';

        $this->lists = new McListEx($this);
    }

    public function call($url, $params) 
    {
        $params['apikey'] = $this->apikey;
        
        $params = $this->encodeParameters($params, $this->export_mode);
        $curl     = $this->ch;

        curl_setopt($curl, CURLOPT_URL, ($this->export_mode ? $this->export_root : $this->root) . $url . ($this->export_mode ? '' : '.json'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeaders($this->export_mode));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_VERBOSE, $this->debug);

        $start = microtime(true);
        $this->log('Call to ' . $this->root . $url . '.json: ' . $params);
        if($this->debug) {
            $curl_buffer = fopen('php://memory', 'w+');
            curl_setopt($curl, CURLOPT_STDERR, $curl_buffer);
        }

        $response_body = curl_exec($curl);

        $info = curl_getinfo($curl);
        $time = microtime(true) - $start;
        if($this->debug) {
            rewind($curl_buffer);
            $this->log(stream_get_contents($curl_buffer));
            fclose($curl_buffer);
        }
        $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->log('Got response: ' . $response_body);

        if(curl_error($curl)) {
            throw new Mailchimp_HttpError("API call to $url failed: " . curl_error($curl));
        }
        $result = $this->getResult($response_body, $this->export_mode);
        
        if(floor($info['http_code'] / 100) >= 4) {
            throw $this->castError($result);
        }

        return $result;
    }

    private function getHeaders($export_mode)
    {
        if ($export_mode) {
            return array();
        } else {
            return array('Content-Type: application/json');
        }
    }

    private function encodeParameters($params, $export_mode)
    {
        if ($export_mode) {
            return http_build_query($params);
        } else {
            return json_encode($params);
        }
    }

    private function getResult($response_body, $export_mode)
    {
        if ($export_mode) {
            $result = explode("\n", $response_body);
            for ($i=0; $i<count($result); $i++) {
                $result[$i] = json_decode($result[$i]);
            }
            return $result;
        } else {
            return json_decode($response_body, true);
        }
    }
}

} else {

class MailChimpEx {} // useless stub if MC is not installed via composer

}
