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
if (!function_exists('checkLogin')) {
    require(dirname(__FILE__) . '/../login.php');
}

class AuthEmailAddress extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $title = 'Fannie : Auth : Email Address';
    protected $header = 'Fannie : Auth : Email Address';

    protected function post_view()
    {
        $email = trim(FormLib::get('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '<div class="alert alert-danger">Not a valid email address</div>' . $this->get_view();
        }

        $upP = $this->connection->prepare('UPDATE Users SET email=? WHERE name=?');
        $upR = $this->connection->execute($upP, array($email, $this->current_user));

        return ($upR ? '<div class="alert alert-success">Email updated</div>' : '<div class="alert alert-danger">Update failed</div>')
            . $this->get_view();
    }

    protected function get_view()
    {
        $emailP = $this->connection->prepare('SELECT email FROM Users WHERE name=?');
        $email = $this->connection->getValue($emailP, array($this->current_user));
        $this->addOnloadCommand("\$('#email-in').focus();\n");

        return <<<HTML
<form method="post" action="AuthEmailAddress.php">
    <div class="form-group">
        <label>Email</label>
        <input type="email" class="form-control" name="email" value="{$email}" id="email-in" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Update Email</button>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <a href="AuthIndexPage.php" class="btn btn-default">Main Menu</a>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

