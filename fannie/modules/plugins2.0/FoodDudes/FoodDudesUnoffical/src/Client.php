<?php

namespace FoodDudesUnofficial;

class Client
{
    private $LOGIN_PAGE_URL = 'https://fooddudesdelivery.com/app.php';
    private $LOGIN_URL = 'https://fooddudesdelivery.com/cordova/www/ajax.php';
    private $ORDER_URL = 'https://fooddudesdelivery.com/cordova/www/receive.php';

    private $http;
    private $cat_id;
    private $admin_id;

    public function __construct($username, $password)
    {
        $this->http = new \GuzzleHttp\Client();
        $page = $this->http->request('GET', $this->LOGIN_PAGE_URL, ['verify'=>false]);
        $logged_in = $this->http->request('POST', $this->LOGIN_URL, [
            'form_params' => [
                'login' => json_encode(['username'=>$username, 'password'=>$password, 'device_id'=>0]),
            ],
            'verify' => false,
        ]);
        $json = json_decode($logged_in->getBody()->getContents(), true);
        if (!is_array($json)) {
            throw new \Exception('Login failed');
        }
        $this->cat_id = $json['categories_id'];
        $this->admin_id = $json['admin_id'];
    }

    public function getOrders($startDate, $endDate)
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        if ($start === false || $end === false) {
            throw new \Exception('Invalid date(s) given');
        }
        $json = [
            'key' => 'past_order',
            'params' => [
                'categories_id' => $this->cat_id,
                'start' => date('Y-m-d', $start),
                'end' => date('Y-m-d', $end),
                'search_id' => 0,
            ],
        ];
        $resp = $this->http->request('POST', $this->ORDER_URL, [
            'form_params'=> [ 'restaurant' => json_encode($json) ],
            'verify' => false,
        ]);

        $body = $resp->getBody()->getContents();
        $valid = json_decode($body, true);
        if ($valid === null) {
            throw new \Exception('Invalid response from server');
        }

        return $valid;
    }
}

