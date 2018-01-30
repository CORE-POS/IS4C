<?php

namespace COREPOS\Fannie\Plugin\AWS;
use \Exception;

/**
 * Wrapper for Simple Notification Service
 *
 * Primary purpose of the wrapper is using CORE
 * for configuration management
 */
class SNS
{
    private $client;

    public function __construct($config)
    {
        $settings = $config->get('PLUGIN_SETTINGS');
        foreach (array('AwsApiKey', 'AwsApiSecret', 'AwsRegion') as $key) {
            if (!isset($settings[$key])) {
                throw new Exception("Missing setting for {$key}");
            }
        }
        if (!class_exists('Aws\\Sns\\SnsClient')) {
            throw new Exception("Install aws/aws-sdk-php");
        }

        $this->client = new \Aws\Sns\SnsClient(array(
            'version' => 'latest',
            'region' => $settings['AwsRegion'],
            'credentials' => array(
                'key' => $settings['AwsApiKey'],
                'secret' => $settings['AwsApiSecret'],
            ),
        ));
    }

    /**
     * Get the underlying Aws\Sns\SnsClient object
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Send a simple SMS message. Biased toward North America and
     * uses country code +1 if none is provided
     * @param $phone [string] phone number. Non-digits automatically removed
     * @param $msg [string] the SMS message
     * @return [boolean] success
     */
    public function sendSMS($phone, $msg)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = strlen($phone) === 10 ? ('+1' . $phone) : ('+' . $phone);

        try {
            $result = $this->client->publish(array(
                'Message' => $msg,
                'PhoneNumber' => $phone,
            ));

            return true;
        } catch (Exception $ex) { }

        return false;
    }
}

