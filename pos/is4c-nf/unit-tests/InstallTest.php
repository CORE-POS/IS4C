<?php

/**
 * @backupGlobals disabled
 */
class InstallTest extends PHPUnit_Framework_TestCase
{
	public function testOpdata()
    {
        $db = Database::pDataConnect();
        $errors = InstallUtilities::createOpDBs($db, CoreLocal::get('pDatabase'));

        $this->assertInternalType('array', $errors);
        foreach ($errors as $error) {
            $this->assertInternalType('array', $error, 'Invalid status entry');
            $this->assertArrayHasKey('error', $error, 'Status entry missing key: error');
            $this->assertEquals(0, $error['error'], 'Error creating ' . $error['struct'] 
                . ', ' . (isset($error['details']) ? $error['details'] : ''));
            $exists = $db->table_exists($error['struct']);
            $this->assertEquals(true, $exists, 'Failed to create ' . $error['struct']);
        }
    }

	public function testTranslog()
    {
        $db = Database::tDataConnect();
        $errors = InstallUtilities::createTransDBs($db, CoreLocal::get('tDatabase'));

        $this->assertInternalType('array', $errors);

        $this->assertInternalType('array', $errors);
        foreach ($errors as $error) {
            $this->assertInternalType('array', $error, 'Invalid status entry');
            $this->assertArrayHasKey('error', $error, 'Status entry missing key: error');
            $this->assertEquals(0, $error['error'], 'Error creating ' . $error['struct']
                . ', ' . print_r($error, true));
            if (isset($error['query']) && stristr($error['query'], 'DROP VIEW')) {
                // don't check for existence on DROP VIEW queries
                continue;
            }
            $exists = $db->table_exists($error['struct']);
            $this->assertEquals(true, $exists, 'Failed to create ' . $error['struct']
                . ', ' . print_r($error, true));
        }
    }

	public function testSampleData()
    {
        $samples = array(
            'couponcodes',
            'custdata',
            'departments',
            'employees',
            'globalvalues',
            'houseCoupons',
            'houseCouponItems',
            'MasterSuperDepts',
            'parameters',
            'products',
            'subdepts',
            'tenders',
        );
        $dbc = Database::pDataConnect();

        foreach ($samples as $sample) {
            ob_start();
            $dbc->query('TRUNCATE TABLE ' . $dbc->identifier_escape($sample));
            $loaded = InstallUtilities::loadSampleData($dbc, $sample, false);
            $output = ob_get_clean();

            $this->assertEquals(true, $loaded, 'Error with sample data for ' . $sample . ' (' . $output . ')');
        }
    }
}

