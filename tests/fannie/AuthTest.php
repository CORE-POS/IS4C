<?php

/**
 * @backupGlobals disabled
 */
class AuthTest extends PHPUnit_Framework_TestCase
{
    public function testPages()
    {
        $pages = array(
            'AuthChangePassword',
            'AuthClassesPage',
            'AuthGroupsPage',
            'AuthIndexPage',
            'AuthPagePermissions',
            'AuthPosePage',
            'AuthUsersPage',
        );
        $conf = FannieConfig::factory();
        foreach ($pages as $page) {
            if (!class_exists($page)) {
                include(dirname(__FILE__) . '/../../fannie/auth/ui/' . $page . '.php');
            }
            $obj = new $page();
            $obj->setConfig($conf);
            $dbc = FannieDB::forceReconnect($conf->get('OP_DB'));
            $obj->setConnection($dbc);
            $obj->unitTest($this);
        }

        if (!class_exists('FannieAuthLoginPage')) {
            include(dirname(__FILE__) . '/../../fannie/auth/ui/loginform.php');
        }
        $obj = new FannieAuthLoginPage();
        $obj->setConfig($conf);
        $dbc = FannieDB::forceReconnect($conf->get('OP_DB'));
        $obj->setConnection($dbc);
        $obj->unitTest($this);
    }

    public function testGroups()
    {
        if (!function_exists('addGroup')) {
            include(dirname(__FILE__) . '/../../fannie/auth/groups.php');
        }

        $this->assertEquals(false, addGroup('invalid name', 'user'));
        $this->assertEquals(true, addGroup('testgroup', 'testuser'));

        $this->assertEquals(false, addUserToGroup('invalid name', 'testuser'))
        $this->assertEquals(false, addUserToGroup('nonexistant', 'testuser'))
        $this->assertEquals(false, addUserToGroup('testgroup', 'testuser'))
        $this->assertEquals(true, addUserToGroup('testgroup', 'testuser2'))

        $this->assertEquals(false, deleteUserFromGroup('invalid name', 'testuser'))
        $this->assertEquals(false, deleteUserFromGroup('nonexistant', 'testuser'))
        $this->assertEquals(true, deleteUserFromGroup('testgroup', 'testuser2'))

        $this->assertEquals(false, addAuthToGroup('invalid name', 'testauth'))
        $this->assertEquals(false, addAuthToGroup('nonexistant', 'testauth'))
        $this->assertEquals(true, addAuthToGroup('testgroup', 'testauth'))

        $this->assertEquals(false, checkGroupAuth('invalid name', 'testauth'))
        $this->assertEquals(false, checkGroupAuth('nonexistant', 'testauth'))
        $this->assertEquals(true, checkGroupAuth('testuser', 'testauth'))

        $this->assertEquals(false, deleteAuthFromGroup('invalid name', 'testauth'))
        $this->assertEquals(false, deleteAuthFromGroup('nonexistant', 'testauth'))
        $this->assertEquals(true, deleteAuthFromGroup('testgroup', 'testauth'))

        $this->assertEquals(false, deleteGroup('invalid name'));
        $this->assertEquals(false, deleteGroup('nonexistant'));
        $this->assertEquals(true, deleteGroup('testgroup'));
    }

    public function testPrivs()
    {
        if (!function_exists('addGroup')) {
            include(dirname(__FILE__) . '/../../fannie/auth/privileges.php');
        }

        
    }
}

