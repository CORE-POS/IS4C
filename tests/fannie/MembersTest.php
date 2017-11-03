<?php

use COREPOS\common\mvc\ValueContainer;

/**
 * @backupGlobals disabled
 */
class MembersTest extends PHPUnit_Framework_TestCase
{
    public function testItems()
    {
        $dbc = FannieDB::forceReconnect(FannieConfig::config('OP_DB'));
        $mems = FannieAPI::listModules('COREPOS\Fannie\API\member\MemberModule', true);

        foreach($mems as $mem_class) {
            $obj = new $mem_class();
        }
    }

    public function testAccount()
    {
        return array(
            'customerAccountID' => 1,
            'cardNo' => 1,
            'memberStatus' => 'PC',
            'activeStatus' => '',
            'customerTypeID' => 1,
            'chargeBalance' => 0,
            'chargeLimit' => 0,
            'startDate' => '2000-01-01',
            'endDate' => '2099-01-01',
            'addressFirstLine' => '123 4th St',
            'addressSecondLine' => 'Apt 678',
            'city' => 'ANYTOWN',
            'state' => 'US',
            'zip' => '12345',
            'contactAllowed' => 1,
            'contactMethod' => 'mail',
            'modified' => '2000-01-01 00:00:00',
            'customers' => array(
                array(
                    'customerID' => 1,
                    'customerAccountID' => 1,
                    'cardNo' => 1,
                    'firstName' => 'PRIMARY',
                    'lastName' => 'PERSON',
                    'chargeAllowed' => 1,
                    'checksAllowed' => 1,
                    'discount' => 0,
                    'accountHolder' => 1,
                    'staff' => 0,
                    'phone' => '867-5309',
                    'altPhone' => '',
                    'email' => 'bob@bob.com',
                    'memberPricingAllowed' => 0,
                    'memberCouponsAllowed' => 0,
                    'lowIncomeBenefits' => 0,
                    'modified' => '2000-01-01 00:00:00',
                ),
                array(
                    'customerID' => 2,
                    'customerAccountID' => 1,
                    'cardNo' => 1,
                    'firstName' => 'SECONDARY',
                    'lastName' => 'PERSON',
                    'chargeAllowed' => 1,
                    'checksAllowed' => 1,
                    'discount' => 0,
                    'accountHolder' => 0,
                    'staff' => 0,
                    'phone' => '867-5309',
                    'altPhone' => '',
                    'email' => 'jim@bob.com',
                    'memberPricingAllowed' => 0,
                    'memberCouponsAllowed' => 0,
                    'lowIncomeBenefits' => 0,
                    'modified' => '2000-01-01 00:00:00',
                ),
            ),
        );
    }

    public function testContact()
    {
        $mod = new ContactInfo();
        $json = $this->testAccount();
        $form = new ValueContainer();
        $form->ContactInfo_addr1 = '1 main st';
        $form->ContactInfo_addr2 = 'Apt 0';
        $form->ContactInfo_city = 'Home';
        $form->ContactInfo_state = 'ZZ';
        $form->ContactInfo_zip = '54321';
        $form->ContactInfo_mail = 'checked';
        $form->ContactInfo_ln = 'SMITH';
        $form->ContactInfo_fn = 'BOB';
        $form->ContactInfo_ph1 = '123-4567';
        $form->ContactInfo_ph2 = '987-6543';
        $form->ContactInfo_email = 'bob@google.com';
        $mod->setForm($form);
        $json = $mod->saveFormData(1, $json);
        $this->assertEquals('1 main st', $json['addressFirstLine']);
        $this->assertEquals('Apt 0', $json['addressSecondLine']);
        $this->assertEquals('Home', $json['city']);
        $this->assertEquals('ZZ', $json['state']);
        $this->assertEquals('54321', $json['zip']);
        $this->assertEquals(1, $json['contactAllowed']);
        $this->assertEquals('SMITH', $json['customers'][0]['lastName']);
        $this->assertEquals('BOB', $json['customers'][0]['firstName']);
        $this->assertEquals('bob@google.com', $json['customers'][0]['email']);
        $this->assertEquals('123-4567', $json['customers'][0]['phone']);
        $this->assertEquals('987-6543', $json['customers'][0]['altPhone']);
    }

    public function testREST()
    {
        $account = \COREPOS\Fannie\API\member\MemberREST::get(-999);
        $this->assertEquals($account, false);

        /** get account and verify structure **/
        $TEST_ACCOUNT = 1;
        $account = \COREPOS\Fannie\API\member\MemberREST::get($TEST_ACCOUNT);
        $this->assertInternalType('array', $account);
        $all_fields = array(
            'cardNo',
            'memberStatus',
            'activeStatus',
            'customerTypeID',
            'customerType',
            'chargeBalance',
            'chargeLimit',
            'idCardUPC',
            'startDate',
            'endDate',
            'addressFirstLine',
            'addressSecondLine',
            'city',
            'state',
            'zip',
            'contactAllowed',
            'contactMethod',
            'modified',
            'customers',
        );

        foreach ($all_fields as $field) {
            $this->assertArrayHaskey($field, $account, 'Account missing field: ' . $field);
        }
        $this->assertInternalType('array', $account['customers']);

        $customer_fields = array(
            'customerID',
            'firstName',
            'lastName',
            'chargeAllowed',
            'checksAllowed',
            'discount',
            'accountHolder',
            'staff',
            'phone',
            'altPhone',
            'email',
            'memberPricingAllowed',
            'memberCouponsAllowed',
            'lowIncomeBenefits',
            'modified',
        );
        foreach ($account['customers'] as $customer) {
            foreach ($customer_fields as $field) {
                $this->assertArrayHasKey($field, $customer);
            }
        }

        COREPOS\Fannie\API\member\MemberREST::testMode(true);
        $all = \COREPOS\Fannie\API\member\MemberREST::get();
        COREPOS\Fannie\API\member\MemberREST::testMode(false);
        foreach ($all as $a) {
            $this->assertArrayHasKey('cardNo', $a);
            if ($a['cardNo'] == $TEST_ACCOUNT) {
                $this->assertEquals($a, $account, 'get single and get all must match');
                break;
            }
        }

        $account_changed = $account;
        $account_changed['idCardUPC'] = '12345';
        $account_changed['startDate'] = date('Y-m-d 00:00:00');
        $account_changed['endDate'] = date('Y-m-d 00:00:00', strtotime('+1 year'));
        $account_changed['addressFirstLine'] = '123 4th St';
        $account_changed['addressSecondLine'] = 'Apt. 5';
        $account_changed['city'] = 'somewhere';
        $account_changed['state'] = 'NY';
        $account_changed['zip'] = '12345';
        $account_changed['customers'][0]['firstName'] = 'Test';
        $account_changed['customers'][0]['lastName'] = 'User';

        /**
          POST the original account and verify the result,
          then POST a modified account and verify, then
          restore original and verify
        */
        $post_accounts = array($account, $account_changed, $account);

        foreach ($post_accounts as $a) {
            /** post account structure back and verify it did not change **/
            $resp = \COREPOS\Fannie\API\member\MemberREST::post($TEST_ACCOUNT, $a);
            $this->assertInternalType('array', $resp);
            $this->assertArrayHasKey('errors', $resp);
            $this->assertEquals(0, $resp['errors']);
            $this->assertArrayHasKey('account', $resp);

            foreach ($all_fields as $field) {
                if ($field == 'modified' || $field == 'customers') {
                    /**
                      modified timestamp expected to change.
                      Customers will be checked field by field
                    */
                    continue;
                }
                $this->assertArrayHasKey($field, $resp['account']);
                $this->assertEquals($a[$field], $resp['account'][$field], "Mismatch for field: $field");
            }

            for ($i=0; $i<count($resp['account']['customers']); $i++) {
                $this->assertArrayHasKey($i, $a['customers']);
                foreach ($customer_fields as $field) {
                    if ($field == 'modified') {
                        $this->assertNotEquals($resp['account']['customers'][$i][$field], $a['customers'][$i][$field]);
                    } else {
                        $this->assertEquals(
                            $resp['account']['customers'][$i][$field], 
                            $a['customers'][$i][$field],
                            "Mismatch for field: $field"
                        );
                    }
                }
            }
        }
    }
}

