<?php

use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;

/**
 * @backupGlobals disabled
 */
class InstallTest extends PHPUnit_Framework_TestCase
{
    public function testOpdata()
    {
        $db = Database::pDataConnect();
        $errors = COREPOS\pos\install\db\Creator::createOpDBs($db, CoreLocal::get('pDatabase'));

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
        $errors = COREPOS\pos\install\db\Creator::createTransDBs($db, CoreLocal::get('tDatabase'));

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
            $dbc->query('TRUNCATE TABLE ' . $dbc->identifierEscape($sample));
            $loaded = COREPOS\pos\install\data\Loader::loadSampleData($dbc, $sample, false);
            $output = ob_get_clean();

            $this->assertEquals(true, $loaded, 'Error with sample data for ' . $sample . ' (' . $output . ')');
        }

        $dbc = Database::tDataConnect();
        $dbc->query('INSERT INTO taxrates (id, rate, description) VALUES (1, 0.05, \'SalesTax\')');
    }

    public function testMinServer()
    {
        CoreState::loadParams();
        $db = Database::mDataConnect();
        $errors = COREPOS\pos\install\db\Creator::createMinServer($db, CoreLocal::get('mDatabase'));

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
}

