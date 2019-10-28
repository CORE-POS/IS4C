<?php

namespace COREPOS\Fannie\API\webservices; 
use COREPOS\Fannie\API\member\MemberREST;

/**
    Web service for getting and/or updating 
    member info.
    
    Sample get request:

    {
        "jsonrpc": "2.0",
        "method": "\\COREPOS\\Fannie\\API\\webservices\\FannieMember",
        "id": "9382839393292",
        "params": {
            "cardNo": "1",
            "method": "get"
        }
    }

    Sample set request:

    {
        "jsonrpc": "2.0",
        "method": "\\COREPOS\\Fannie\\API\\webservices\\FannieMember",
        "id": "9382839393292",
        "params": {
            "cardNo": "1",
            "method": "set",
            "member": {
                "cardNo": "1",
                "memberStatus": "PC",
                "activeStatus": "",
                "customerTypeID": "1",
                "customerType": "Member",
                "chargeLimit": "0.00",
                "chargeBalance": "0.00",
                "idCardUPC": "0040000000000",
                "startDate": "2010-01-01 00:00:00",
                "endDate": "0000-00-00 00:00:00",
                "city": "DULUTH",
                "state": "MN",
                "zip": "55805",
                "contactAllowed": "0",
                "contactMethod": "mail",
                "addressFirstLine": "123 4TH STREET",
                "addressSecondLine": "",
                "customers": [
                    {
                        "customerID": "40171",
                        "firstName": "SOME",
                        "lastName": "PERSON",
                        "chargeAllowed": "0",
                        "checksAllowed": "1",
                        "discount": "0",
                        "staff": "0",
                        "lowIncomeBenefits": "0",
                        "accountHolder": 1,
                        "phone": "800-867-5309",
                        "email": "name@domain",
                        "altPhone": "",
                        "memberPricingAllowed": 1,
                        "memberCouponsAllowed": 1,
                        "customerAccountID": "1"
                    }
                ],
                "customerAccountID": "1"
            }
        }
    }
 */
class FannieMember extends FannieWebService
{
    public $type = 'json'; // json/plain by default

    public function run($args=array())
    {
        if (!property_exists($args, 'cardNo') || !property_exists($args, 'method')) {
            // missing required arguments
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters',
            );
            return $ret;
        }
        $method = strtolower($args->method);
        if ($method != 'get' && $method != 'set') {
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Method must be "get" or "set"',
            );
            return $ret;
        }
        if ($method == 'set' && !property_exists($args, 'member')) {
            $ret['error'] = array(
                'code' => -32602,
                'message' => 'Invalid parameters',
            );
            return $ret;
        }

        switch ($method) {
        case 'get':
            return MemberREST::get($args->cardNo);
        case 'post':
            return MemberREST::post($args->cardNo, $args->member);
        }
    }
}

