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

class AuthPosePage extends FannieRESTfulPage {

    protected $must_authenticate = true;
    protected $auth_classes = array('admin');
    protected $title = 'Fannie : Auth : Pose';
    protected $header = 'Fannie : Auth : Pose';

    public function post_id_handler()
    {
        pose($this->id);
        header("Location: AuthIndexPage.php");
        
        return false;
    }

    public function get_view()
    {
        ob_start();
        ?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<label>Username</label>
<select name="id" class="form-control">
<?php
foreach (getUserList() as $uid => $name) {
    echo "<option>".$name."</option>";
}
?>
</select>
<p>
    <button class="btn btn-defaut" type="submit">Pose as User</button>
</p>
</form>
        <?php
        $this->add_onload_command("\$('select.form-control').focus();\n");

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

