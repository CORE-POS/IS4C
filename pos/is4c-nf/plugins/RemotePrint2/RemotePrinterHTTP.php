<?php

use COREPOS\pos\lib\PrintHandlers\PrintHandler;

/**
 * POST a JSON message to a remote server via HTTP
 *
 * The JSON object contains two fields:
 *   - receipt [string] the content to be printed
 *   - encoding [string] encoding used on the receipt field, if any
 *
 * The remote web server has to be able to parse this request
 * and write the appropriate string to the printer device file.
 */
class RemotePrinterHTTP extends PrintHandler
{
    public function writeLine($text)
    {
        $url = CoreLocal::get('RemotePrintDevice');
        if (substr(strtolower($url), 0, 7) == 'http://' && substr(strtolower($url), 0, 8) != 'https://') {
            // no URL configured
            return false;
        }

        $json = array(
            'receipt' => base64_encode($text),
            'encoding' => 'base64',
        );
        $json = json_encode($json);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );
        $result = curl_exec($curl);

        return $result ? true : false;
    }
}

