<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class MemberTypeEditor extends FannieRESTfulPage 
{
    protected $title = "Fannie :: Member Types";
    protected $header = "Member Types";
    public $description = '[Member Types] creates, updates, and deletes account types.';
    public $themed = true;
    protected $must_authenticate = True;
    protected $auth_classes = array('editmembers');

    public function preprocess()
    {
        $this->__routes[] = 'get<new>';
        $this->__routes[] = 'post<new>';
        $this->__routes[] = 'post<id><type>';
        $this->__routes[] = 'post<id><staff>';
        $this->__routes[] = 'post<id><ssi>';
        $this->__routes[] = 'post<id><discount>';
        $this->__routes[] = 'post<id><description>';
        $this->__routes[] = 'post<id><salesCode>';

        return parent::preprocess();
    }

    /**
      Create a new member type 
    */
    public function post_new_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $this->errors = '';

        /* do some extra sanity checks
           on a new member type
        */
        $id = $this->new;
        if (!is_numeric($id)){
            $this->errors .= 'ID '.$id.' is not a number';
            return true;
        } else {
            $mtModel = new MemtypeModel($dbc);
            $mtModel->reset();
            $mtModel->memtype($id);
            if ($mtModel->load()) {
                $this->errors .= $id . ' ID is already in use';
                return true;
            } else {
                $mtModel->memDesc('');
                $mtModel->custdataType('REG');
                $mtModel->discount(0);
                $mtModel->staff(0);
                $mtModel->ssi(0);
                $mtModel->save();

                return $_SERVER['PHP_SELF'];
            }
        }
    }

    /**
      AJAX callbacks for auto-saving fields
    */

    public function post_id_type_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $json = array('msg'=>'');

        $mtModel = new MemtypeModel($dbc);
        $mtModel->memtype($this->id);
        $mtModel->custdataType($this->type);
        $saved = $mtModel->save();
        if (!$saved) {
            $json['msg'] = 'Error saving membership status';
        }
        echo json_encode($json);

        return false;
    }

    public function post_id_description_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $json = array('msg'=>'');

        $mtModel = new MemtypeModel($dbc);
        $mtModel->memtype($this->id);
        $mtModel->memDesc($this->description);
        $saved = $mtModel->save();
        if (!$saved) {
            $json['msg'] = 'Error saving membership status';
        }
        echo json_encode($json);

        return false;
    }

    public function post_id_staff_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $json = array('msg'=>'');

        $mtModel = new MemtypeModel($dbc);
        $mtModel->memtype($this->id);
        $mtModel->staff($this->staff);
        $saved = $mtModel->save();
        if (!$saved) {
            $json['msg'] = 'Error saving staff status';
        }
        echo json_encode($json);

        return false;
    }

    public function post_id_ssi_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $json = array('msg'=>'');

        $mtModel = new MemtypeModel($dbc);
        $mtModel->memtype($this->id);
        $mtModel->ssi($this->ssi);
        $saved = $mtModel->save();
        if (!$saved) {
            $json['msg'] = 'Error saving SSI status';
        }
        echo json_encode($json);

        return false;
    }

    public function post_id_discount_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $json = array('msg'=>'');

        $mtModel = new MemtypeModel($dbc);
        $mtModel->memtype($this->id);
        $mtModel->discount($this->discount);
        $saved = $mtModel->save();
        if (!$saved) {
            $json['msg'] = 'Error saving discount';
        }
        echo json_encode($json);

        return false;
    }

    public function post_id_salesCode_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $json = array('msg'=>'');

        $mtModel = new MemtypeModel($dbc);
        $mtModel->memtype($this->id);
        $mtModel->salesCode($this->salesCode);
        $saved = $mtModel->save();
        if (!$saved) {
            $json['msg'] = 'Error saving account number';
        }
        echo json_encode($json);

        return false;
    }

    function javascript_content()
    {
        ob_start();
        ?>
        function saveMem(st,t_id){
            var cd_type = 'REG';
            if (st == true) cd_type='PC';
            var elem = $(this);
            var orig = this.defaultValue;
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 'id='+t_id+'&type='+cd_type,
                dataType: 'json'
            }).done(function(data){
                showBootstrapPopover(elem, orig, data.msg);
            });
        }

        function saveStaff(st,t_id){
            var elem = $(this);
            var orig = this.defaultValue;
            var staff = 0;
            if (st == true) staff=1;
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 'id='+t_id+'&staff='+staff,
                dataType: 'json'
            }).done(function(data){
                showBootstrapPopover(elem, orig, data.msg);
            });
        }

        function saveSSI(st,t_id){
            var elem = $(this);
            var orig = this.defaultValue;
            var ssi = 0;
            if (st == true) ssi=1;
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 't_id='+t_id+'&saveSSI='+ssi,
                dataType: 'json'
            }).done(function(data){
                showBootstrapPopover(elem, orig, data.msg);
            });
        }

        function saveDisc(disc,t_id){
            var elem = $(this);
            var orig = this.defaultValue;
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                data: 'id='+t_id+'&discount='+disc,
                dataType: 'json'
            }).done(function(data){
                showBootstrapPopover(elem, orig, data.msg);
            });
        }

        function saveType(typedesc,t_id){
            var elem = $(this);
            var orig = this.defaultValue;
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                dataType: 'json',
                data: 'id='+t_id+'&description='+typedesc
            }).done(function(data){
                showBootstrapPopover(elem, orig, data.msg);
            });
        }

        function saveAccount(account,t_id){
            var elem = $(this);
            var orig = this.defaultValue;
            $.ajax({url:'MemberTypeEditor.php',
                cache: false,
                type: 'post',
                dataType: 'json',
                data: 'id='+t_id+'&salesCode='+account
            }).done(function(data){
                showBootstrapPopover(elem, orig, data.msg);
            });
        }
        <?php
        return ob_get_clean();
    }

    /**
      Only gets to here if an error occurs creating a new
      type. Display error and re-display the new
      member type form.
    */
    public function post_new_view()
    {
        $ret = '';
        if ($this->errors) {
            $ret .= '<div class="alert alert-danger">' . $this->errors . '</div>';
        }

        return $ret . $this->get_new_view();
    }

    /**
      New member type form
    */
    public function get_new_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $q = $dbc->prepare("SELECT MAX(memtype) FROM memtype");
        $r = $dbc->execute($q);
        $sug = 0;
        if($dbc->num_rows($r)>0){
            $w = $dbc->fetch_row($r);
            if(!empty($w)) $sug = $w[0]+1;
        }
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">';
        $ret .='<div class="well">Give the new memtype an ID number. The one
            provided is only a suggestion. ID numbers
            must be unique.</div>';
        $ret .='<div class="form-inline"><p>';
        $ret .= sprintf('<label>New ID</label>: <input class="form-control" value="%d"
            name="new" id="new-mem-id" />',$sug);
        $ret .= ' <button type="submit" class="btn btn-default">
            Create New Type</button>';
        $ret .= ' <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">Cancel</a>';
        $ret .= '</p></div>
            </form>';
        
        return $ret;
    }

    /**
      List types, edit + autosave
    */
    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $ret = '<table class="table">
            <tr><th>ID#</th><th>Description</th>
            <th>Member</th><th>Discount</th>
            <th>Staff</th><th>SSI</th>
            <th>Account #</th>
            </tr>';

        $model = new MemtypeModel($dbc);
        foreach ($model->find('memtype') as $mem) {
            $ret .= sprintf('<tr><td>%d</td>
                    <td><input type="text" class="form-control" value="%s" 
                        onchange="saveType.call(this, this.value, %d);" /></td>
                    <td><input type="checkbox" %s onclick="saveMem.call(this, this.checked, %d);" /></td>
                    <td class="col-sm-1"><div class="input-group"><input type="number" value="%d" class="form-control price-field" 
                        onchange="saveDisc.call(this, this.value,%d);" /><span class="input-group-addon">%%</span>
                    </div></td>
                    <td><input type="checkbox" %s onclick="saveStaff.call(this, this.checked,%d);" /></td>
                    <td><input type="checkbox" %s onclick="saveSSI.call(this, this.checked,%d);" /></td>
                    <td><input type="text" class="form-control" value="%s"
                        onchange="saveAccount.call(this, this.value, %d);" /></td>
                    </tr>',
                    $mem->memtype(),
                    $mem->memDesc(), $mem->memtype(),
                    ($mem->custdataType() == 'PC' ? 'checked' : ''), $mem->memtype(),
                    $mem->discount(), $mem->memtype(),
                    ($mem->staff() == '1' ? 'checked' : ''), $mem->memtype(),
                    ($mem->ssi() == '1' ? 'checked' : ''), $mem->memtype(),
                    $mem->salesCode(), $mem->memtype()
                );
        }
        $ret .= "</table>";
        $ret .= '<p><a href="?new=1" class="btn btn-default">New Member Type</a></p>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>Some co-ops have more than one type of member.
            Furthermore, since CORE requires every transaction be 
            associated with a customer account it is often useful to
            have an account or accounts set aside for customers
            who are not members.</p>
            <p>When creating a new type, provide a unique numeric ID.</p>
            <ul>
                <li><em>Description</em> will show up in member editing 
                and reporting.</li>
                <li><em>Member</em> indicates whether or not customers of
                this type are members of the co-op. Checking the box means
                they are members.</li>
                <li><em>Discount</em> is a percent discount on transactions.
                For example, entering 5 will give customers of that type
                a 5% discount on each transaction.</li>
                <li><em>Staff</em> is purely for record keeping at this time.
                It does not control any POS behavior.</li>
                <li><em>SSI</em> is a flag for low-income customers who
                receive some kind of benefit.</li>
                <li><em>Account #</em> associates a chart of accounts number
                with discount given to a particular member type. The setting
                is irrelevant on member types with a 0% discount.</li>
            </ul>';
    }

    public function unitTest($phpunit)
    {
        $values = new \COREPOS\common\mvc\ValueContainer();
        $values->_method = 'get';
        $this->setForm($values);
        $this->readRoutes();

        $page = $this->get_view();

        $testID = 127;
        $values->_method = 'post';
        $values->new = $testID;
        $this->setForm($values);
        $this->readRoutes();

        $create = $this->post_new_handler();

        unset($values->new);

        $values->_method = 'get';
        $this->setForm($values);
        $this->readRoutes();

        $newpage = $this->get_view();

        $phpunit->assertNotEquals($page, $newpage);
        $phpunit->assertNotEquals(false, strpos($newpage, "$testID"));

        $this->connection->query('DELETE FROM memtype WHERE memtype=' . $testID);

        $phpunit->assertNotEquals(0, strlen($this->javascript_content()));
        $this->errors = 'an error';
        $phpunit->assertNotEquals(0, strlen($this->post_new_view()));
    }
}

FannieDispatch::conditionalExec();

