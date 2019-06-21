<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}
if (!function_exists('validateUserQuiet')) {
    require(dirname(__FILE__) . '/../login.php');
}
if (!function_exists('doLogin')) {
    require(dirname(__FILE__) . '/../utilities.php');
}

class FannieAuthLoginPage extends FannieRESTfulPage
{
    protected $title = 'Fannie : Auth';
    protected $header = 'Fannie : Auth';

    protected $enable_linea = true;

    /**
     * Force authenticate requirement off
     * to avoid a redirect loop
     */
    public function __construct()
    {
        parent::__construct();
        $this->must_authenticate = false;
    }

    public function preprocess()
    {
        if (isset($_GET["redirect"]) && init_check()){
            header("Location:".$_GET['redirect']);
            return false;
        }
        $this->__routes[] = 'get<logout>';
        $this->__routes[] = 'post<name><password>';
        $this->__routes[] = 'get<name><factor>';
        $this->__routes[] = 'post<name><factor>';

        return parent::preprocess();
    }

    /**
      Logout the current user as requested 
    */
    public function get_logout_handler()
    {
        logout();

        return true;
    }

    /**
     * Check name & password against enabled
     * authentication source(s)
     */
    private function validateLogin($name, $password)
    {
        $login = login($name,$password);

        if (!$login && FannieConfig::config('AUTH_LDAP', false)) {
            $login = ldap_login($name,$password);
        }

        if (!$login && FannieConfig::config('AUTH_SHADOW', false)) {
            $login = shadow_login($name,$password);
        }

        return $login;
    }

    /**
      Check submitted credentials. Redirect to destination
      on success, proceed to error message on failure

      If two-factor authentication is required, this redirects
      to an additional form to enter the code. So people can't
      [easily] skip the name & password requirement, a short-lived
      entry is created in userSessions to flag that correct first
      credentials have been entered for the account.
    */
    public function post_name_password_handler()
    {
        $redirect = FormLib::get('redirect', 'menu.php');

        $login = $this->validateLogin($this->name, $this->password);
        if ($login) {
            $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
            $otpP = $dbc->prepare('SELECT * FROM Users WHERE name=?');
            $user = $dbc->getRow($otpP, array($this->name));
            if (isset($user['totpURL']) && $user['totpURL']) {
                $redirect = 'loginform.php?name=' . urlencode($this->name) . '&factor=1&redirect=' . urlencode($redirect);
                $uid = getUID($this->name);
                $expires = date('Y-m-d H:i:s', time() + (3*60));
                $sessionP = $dbc->prepare('INSERT INTO userSessions 
                            (uid,session_id,ip,expires)
                            VALUES (?,?,?,?)');
                $dbc->execute($sessionP, array($uid, 'temp-login', '127.0.0.1', $expires));
            } else {
                doLogin($this->name);
            }

            return $redirect;
        }

        return true;
    }

    /**
     * Validate two-factor token provided
     * If correct, the user is logged in. If not it's
     * treated identical to an incorrect password
     */
    protected function post_name_factor_handler()
    {
        $redirect = FormLib::get('redirect', 'menu.php');
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $otpP = $dbc->prepare('SELECT totpURL FROM Users WHERE name=?');
        $uri = $dbc->getValue($otpP, array($this->name));
        $otp = OTPHP\Factory::loadFromProvisioningUri($uri);
        if ($otp->verify($this->factor)) {
            $delP = $dbc->prepare("DELETE FROM userSessions WHERE name=? AND session_id='temp-login'");
            $dbc->execute($delP, array($this->name));
            doLogin($this->name);
            return $redirect;
        }

        return true;
    }

    /**
     * Check for a temporary userSession indicating a recent
     * correct name & password before presenting the two factor form
     */
    protected function get_name_factor_handler()
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $chkP = $dbc->prepare("SELECT session_id FROM userSessions
            WHERE uid=? AND session_id='temp-login' AND expires < " . $dbc->now());
        $chk = $dbc->getValue($chkP, array(getUID($this->name)));
        if ($chk === false) {
            return 'loginform.php';
        }

        return true;
    }

    protected function get_name_factor_view()
    {
        $redirect = FormLib::get('redirect');
        $this->addOnloadCommand("\$('#factor').focus()");
        return <<<HTML
<form method="post" action="loginform.php">
    <input type="hidden" name="name" value="{$this->name}" />
    <input type="hidden" name="redirect" value="{$redirect}" />
    <div class="form-group">
        <label>Enter Code</label>
        <input type="text" class="form-control" name="factor" id="factor" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Log In</button>
    </div>
</form>
HTML;
    }

    protected function post_name_factor_view()
    {
        return $this->post_name_password_view();
    }

    /**
      Error message for failed login
    */
    public function post_name_password_view()
    {
        $redirect = FormLib::get('redirect', 'menu.php');
        return "<p>Login failed. <a href=loginform.php?redirect=$redirect>Try again</a>?</p>";
    }

    /**
      After logout, just display the regular login form
      with a line noting logout was successful
    */
    public function get_logout_view()
    {
        return "<blockquote><i>You've logged out</i></blockquote>"
            . $this->get_view();
    }

    /**
      Show the login form unless the user is already logged in
      Logged in users get links to potentially intended destinations
    */
    public function get_view()
    {
        $current_user = checkLogin();

        if ($current_user) {
            $rdWarn = isset($_GET['redirect'])
                ? "<b style=\"font-size:1.5em;\">It looks like you don't have permission to access this page</b><p />"
                : '';
            return <<<HTML
You are logged in as {$current_user}<p />
{$rdWarn}
<a href=menu.php>Main menu</a>  |  <a href=loginform.php?logout=yes>Logout</a>
HTML;
        }

        $this->addScript('auth.js');
        $this->addOnloadCommand('$(\'#authUserName\').focus();');
        $this->addOnloadCommand("enableLinea('#linea-in', CoreAuth.linea);\n");
        $redirect = FormLib::get('redirect', 'menu.php');
        $httpsLink = '';
        $domain = $this->config->get('TLS_DOMAIN');
        if ($domain && !isset($_SERVER['HTTPS']) || empty($_SERVER['HTTPS'])) {
            $url = $this->config->get('URL');
            $httpsLink = "<p><a href=\"https://{$domain}{$url}auth/ui/loginform.php?redirect=$redirect\">Switch to HTTPS</a></p>";
        }

        return <<<HTML
<form action=loginform.php method=post>
<p>
    <div class="form-group">
        <label for="authUserName">Name</label>
        <input class="form-control" id="authUserName" name="name" type="text" />
    </div>
    <div class="form-group">
        <label for="authPassword">Password</label>
        <input class="form-control" id="authPassword" name="password" type="password" />
    </div>
</p>
<p><button type="submit" class="btn btn-default">Login</button></p>
<input type=hidden value="{$redirect}" name=redirect />
<input type=hidden id=\"linea-in\" />
</form>
$httpsLink
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_logout_view()));
        $phpunit->assertNotEquals(0, strlen($this->post_name_password_view()));

        $phpunit->assertEquals(true, $this->preprocess());
        $phpunit->assertEquals(true, $this->get_logout_handler());
        $this->name = 'notrealuser';
        $this->password = 'notrealpassword';
        $phpunit->assertEquals(true, $this->post_name_password_handler());
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new FannieAuthLoginPage();
    $obj->drawPage();
}

