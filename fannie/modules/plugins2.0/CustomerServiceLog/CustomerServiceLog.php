<?php
include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
/*
if (!class_exists('CSCLogModel')) {
    include(__DIR__ . '/models/CSCLogModel.php');
}*/

class CustomerServiceLog extends FannieRESTfulPage
{
    protected $header = 'Customer Service Tracker';
    protected $title = 'Customer Service Tracker';
    protected $must_authenticate = true;

    public function preprocess()
    {
        $this->addRoute('get<new>','post<save>');
        return parent::preprocess();
    }

    public function css_content()
    {
        return <<<HTML
.btn-new {
    border: 1px solid grey;
}
.btn-save{
    border: 1px solid brown;
}
.spacer {
    border-top-style: solid;
    border-color: lightgrey;
    border-width: 1px;
    margin: 25px;
    margin-top: 35px;
}
.myform {
    background-color: #e5e5e5;
    padding: 15px;
}
#innerForm {
    display: none;
}
label {
    color: #636E7A;
}
HTML;
    }

    protected function get_id_view()
    {
        $id = FormLib::get('id');
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $args = array($id);
        $formFields = array('owner','firstName','lastName','phone','address','uid','date','subject','content');
        $prep = $dbc->prepare("SELECT * FROM CustomerServiceTracker.Pending WHERE id = ?;");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            foreach($formFields as $field) {
                ${$field} = $row[$field];   
            }
        }
        $thisForm = $this->inner_form_content($owner,$firstName,$lastName,$phone,$address,$uid,$date,$subject,$content);


        return <<<HTML
{$this->form_content()}
<div id="innerForm">{$thisForm}</div>
{$this->get_comments()}
HTML;
    }

    protected function post_save_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $id = FormLib::get('id');
        $formValues = array('owner','firstName','lastName','phone','address','uid',
            'date','subject','content','complete');
        foreach ($formValues as $name) {
            ${$name} = FormLib::get($name);
        }

        $args = array($storeID,$uid,$date,$complete,$subject,$content,
            $firstName,$lastName,$owner,$phone,$address);
        $prepSave = $dbc->prepare("INSERT INTO CustomerServiceTracker.Pending (
            storeID,uid,date,complete,subject,content,firstName,lastName,
            owner,phone,address) VALUES (?,?,?,?,?,?,?,?,?,?,?);");
        $prepUpdate = $dbc->prepare("UPDATE CustomerServiceTracker.Pending 
            set storeID = ?, uid = ?, date = ?, complete = ?, subject = ?,
            content = ?, firstName = ?, lastName = ?, owner = ?, phone = ?,
            address = ? WHERE id = ?");
        if ($id) {
            $args[] = $id;
            $res = $dbc->execute($prepUpdate,$args);
        } else {
            $res = $dbc->execute($prepSave,$args);
        }

        return header('location: CustomerServiceLog.php');
        
    }

    protected function get_new_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $store = 1;
            //$store = Store::getIdByIp();
            $stores = array();
            $prep = $dbc->prepare("SELECT * FROM Stores ORDER BY storeID;");
            $res = $dbc->execute($prep);
            $options = '';
            while ($row = $dbc->fetchRow($res)) {
                $sel = ($row['storeID'] == $store) ? ' selected' : '';
                $options .= '<option value="'.$row['storeID'].'" '.$sel.'>'.$row['description'].'</option>';
            }
            $storeForm = '
                <label>Store</label>
                <select class="form-control" name="store">
                    '.$options.' 
                </select>';
        } else {
            $storeForm = '<input type="hidden" name="store" value="1">';
        }

        return <<<HTML
{$this->form_content()}
{$this->inner_form_content()}
{$this->get_comments()}
HTML;
    }

    protected function get_view()
    {
        return <<<HTML
{$this->form_content()}
{$this->get_comments()}
HTML;
    }

    protected function get_comments()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $stores = array();
        $fields = array('id','storeID','uid','date','complete','subject','content');
        $hiddenFields = array('firstName','lastName','owner','phone','address');
        $storeNames = array(1=>'Hillside',2=>'Denfeld');
        $prep = $dbc->prepare("SELECT *
            FROM CustomerServiceTracker.Pending ORDER BY id;");
        $res = $dbc->execute($prep);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            foreach ($fields as $field) {
                $data[$row['id']][$field] = $row[$field];
            }
            foreach ($hiddenFields as $hf) {
                $hiddenData[$row['id']][$hf] = $row[$hf];
            }
        }
        if ($er = $dbc->error) echo '<div class="alert alert-danger">'.$er.'</div>';
        
        $table = '<div class="table-responsive"><table class="table table-striped table-condensed table-bordered">
            <thead><th class="text-center" colspan="7">Customer Service Tracker</th></thead><tbody>';
        foreach ($data as $id => $row) {
            $table .= '<tr><span style="display: none" class="id">'.$row['id'].'</span>';
            $table .= '<td><span>#</span><a href="CustomerServiceLog.php?id='.$row['id'].'">'.$row['id'].'</a></td>';
            $table .= '<td class="store">'.$storeNames[$row['storeID']].'</td>';
            foreach (array('uid','date','complete','subject','content') as $field) {
                $table .= '<td>'.$row[$field].'</td>';
            }
            foreach ($hiddenFields as $field) {
                $table .= '<td class="hidden">'.$hiddenData[$row['id']][$field].'</td>';
            }
            $table .= '</tr>';
        }
        $table .= '</tbody></table></div>';

        return <<<HTML
{$table}
HTML;
    }

    protected function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $stores = array();
        $prep = $dbc->prepare("SELECT * FROM Stores ORDER BY storeID;");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $stores[$row['storeID']] = $row['description'];
        }
        $filterBtns = '';
        foreach ($stores as $id => $store) {
            $filterBtns .= '<p class="btn btn-default active filter" value="'.$id.'">'.$store.'</p> ';
        }
        return <<<HTML
<p class="form-inline">
    <form method="get" class="form-inline">
        <div class="form-group">
            <button type="submit" class="btn btn-default btn-new" name="new" value="1">New Entry</button>
        </div>
        <div class="form-group" style="float: right;">
            <label>Filter</label>:
            {$filterBtns}
            <input class="form-control input-sm" name="search" id="search" placeholder="containing">
            <button href="CustomerServiceLog.php" class="btn-warning">Reset</button>
        </div>
    </form>
</p>
HTML;
    }

    protected function inner_form_content($owner='',$firstName='',$lastName='',
        $phone='',$address='',$uid='',$date='',$subject='',$content='')
    {
        $id = FormLib::get('id');
        $new = FormLib::get('new');
        if ($new) {
            $date = date('Y-m-d h:i:s');
            $uid = FannieAuth::getUID($this->current_user); 
        }
        
        return <<<HTML
<p class="form-inline">
    <form method="post" class="myform"> 
        <div>TrackerID# {$id}</div>
        <div class="row">
            <div class="col-md-3">
                <label>Owner</label>
                <input type="text" class="form-control input-xs" name="owner" value="{$owner}">
            </div>
            <div class="col-md-3"></div>
            <div class="col-md-3">
                <label>Employee</label>
                <input type="text" class="form-control input-xs" name="uid" value="{$uid}" disabled>
            </div>
            <div class="col-md-3">
                <label>Date</label>
                <input type="text" class="form-control input-xs" name="date" value="{$date}" disabled>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <label>First Name</label>
                <input type="text" class="form-control input-xs" name="firstName" value="{$firstName}">
            </div>
            <div class="col-md-3">
                <label>Last Name</label>
                <input type="text" class="form-control input-xs" name="lastName" value="{$lastName}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <label>Phone</label>
                <input type="text" class="form-control input-xs" name="phone" value="{$phone}">
            </div>
            <div class="col-md-3">
                <label>Address</label>
                <input type="text" class="form-control input-xs" name="address" value="{$address}">
            </div>
            <div class="col-md-3">
                {$storeForm}
            </div>
        </div>
        <div class="spacer"></div>
        <div class="form-group">    
            <label>Subject</label>
            <input type="text" class="form-control input-sm" name="subject" value="{$subject}">
        </div>
        <div class="form-group">    
            <label>Comment</label>
            <textarea class="form-control" name="content">{$content}</textarea>
        </div>
        <div class="form-group form-inline">    
            <button class="btn btn-default btn-save" name="save" value="1">Save</button> | 
            <a class="btn btn-default" href="CustomerServiceLog.php">Cancel</a>
        </div>
    </form>
</p>
HTML;
    }

    public function javascriptContent()
    {   
        $id = FormLib::get('id');
        return <<<HTML
var id = {$id}

$(document).ready(function() {
    $('#hillside').addClass('inactive');
    btnClick();
});

function btnClick()
{
    $('.btn').click(function() {
        if ($(this).hasClass('active')) {
           $(this).removeClass('active').addClass('inactive'); 
        } else {
           $(this).removeClass('inactive').addClass('active'); 
        }
    });
}

$('p.filter').click(function() {
    var off = $(this).hasClass('active');
    var btn = $(this).text();
    if (off) {
        $('tr').each(function() {
            var storeName = $(this).find('td.store').text();
            storeName = storeName.toUpperCase();
            if (storeName == btn) {
                $(this).closest('tr').hide();
            }
        });
    } else {
        $('tr').each(function() {
            var storeName = $(this).find('td.store').text();
            storeName = storeName.toUpperCase();
            if (storeName == btn) {
                $(this).closest('tr').show();
            }
        });
    }
});

$('#search').keyup( function () {
    //alert('hi');
    var text = $("#search").val();
    if ( $('#search').val() == '') {
        $('tr').each(function() {
            $(this).closest('tr').show();
        });
    } else {
        hideAll();
        $('td').each(function() {
            var needle = $(this).text();
            if (needle == text) {
                $(this).closest('tr').show();
            }
        });
    }
    //right now, once there is a str in #search, only rows containing td.text() 
    //that matches #search.text verbatim will .show(). 
    //next - need to break each string by whitespace and check if ANY str match, 
    //show rows if true;
    //also - as soon as there is a str in #search, all buttons should be enabled. 
    //also - all needle & text need to be upperCased.
});

function hideAll()
{
    $('tr').each(function() {
        $(this).closest('tr').hide();
    });
}

HTML;

    }

}
FannieDispatch::conditionalExec();

