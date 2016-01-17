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
    }
}

