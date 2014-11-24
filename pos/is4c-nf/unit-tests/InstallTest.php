<?php

/**
 * @backupGlobals disabled
 */
class InstallTest extends PHPUnit_Framework_TestCase
{
	public function testOpdata()
    {
        global $CORE_LOCAL;
        $db = Database::pDataConnect();
        $errors = InstallUtilities::createOpDBs($db, $CORE_LOCAL->get('pDatabase'));

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
        global $CORE_LOCAL;
        $db = Database::tDataConnect();
        $errors = InstallUtilities::createTransDBs($db, $CORE_LOCAL->get('tDatabase'));

        $sets = $db->query("SHOW VARIABLES LIKE '%character_set%'");
        while ($row = $db->fetch_row($sets)) {
            print_r($row);
        }

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

