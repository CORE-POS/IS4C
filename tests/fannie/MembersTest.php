<?php

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

