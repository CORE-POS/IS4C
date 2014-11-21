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
        $this->assertEquals(0, count($errors), print_r($errors, true));
    }
}

