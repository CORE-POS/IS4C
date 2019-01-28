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
        $this->addRoute('get<new>','post<save>','post<custdata>','post<complete>',
            'get<old>','get<id><old>');
        return parent::preprocess();
    }

    public function css_content()
    {
        return <<<HTML
.comment {
    background-color: rgba(255,255,255,0.3);
    border-radius: 3px;
}
.sub-comment {
    color: rgba(0,0,0,0.3);
}
.old-table {
    background-color: #e2e2e2;
}
.sm-text {
    font-size: 14px;
    font-weight: normal;
    font-decoration: italic;
}
.btn-complete {
    float: right;
}
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
}
label {
    color: #636E7A;
}
HTML;
    }

    protected function post_complete_handler()
    {
        $id = FormLib::get('id');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $args = array($id);
        $prep = $dbc->prepare("UPDATE CustomerServiceTracker.Tracker
            SET complete = NOW() WHERE id = ?");
        $res = $dbc->execute($prep,$args);

        return header('location: CustomerServiceLog.php');
    }

    protected function post_custdata_handler()
    {
        $id = FormLib::get('ownerid');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $json = array();
        $args = array($id);
        $prep = $dbc->prepare("
            SELECT
                m.city, m.zip, m.phone, m.email_1, m.email_2,
                c.FirstName as first_name, c.LastName as last_name, m.card_no
            FROM custdata AS c
                LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
            WHERE c.CardNo = ?
                AND personNum = 1
            LIMIT 1
        ");
        $res = $dbc->execute($prep,$args);
        $fields = array('card_no','first_name','last_name','street','city',
            'state','zip','email_1','email_2','phone');
        while ($row = $dbc->fetchRow($res)) {
            $address = $row['city'].', '.$row['zip'];
            $json['address'] = $address;
            foreach($fields as $field) {
                $json[$field] = $row[$field];
            }
        }

        echo json_encode($json);
        return false;
    }

    protected function get_id_view()
    {
        $id = FormLib::get('id');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $args = array($id);
        $formFields = array('owner','firstName','lastName','phone','address','uid','date','subject','content');
        $prep = $dbc->prepare("SELECT * FROM CustomerServiceTracker.Tracker WHERE id = ?;");
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

    protected function get_id_old_view()
    {
        $id = FormLib::get('id');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $args = array($id);
        $formFields = array('owner','firstName','lastName','phone','address','uid','date','subject','content','complete');
        $prep = $dbc->prepare("SELECT * FROM CustomerServiceTracker.Tracker WHERE id = ?;");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            foreach($formFields as $field) {
                ${$field} = $row[$field];
            }
        }
        $thisForm = $this->inner_form_content($owner,$firstName,$lastName,$phone,$address,$uid,$date,$subject,$content,$complete);

        return <<<HTML
{$this->form_content()}
<div id="innerForm">{$thisForm}</div>
{$this->get_comments('old')}
HTML;
    }

    protected function post_save_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $id = FormLib::get('id', false);
        $formValues = array('store','owner','firstName','lastName','phone','address',
            'date','subject','content','complete');
        foreach ($formValues as $name) {
            ${$name} = FormLib::get($name);
        }
        $uid = FannieAuth::getUID($this->current_user);

        $argsSave = array($store,$uid,$complete,$subject,
        $firstName,$lastName,$owner,$phone,$address);
        $argsSave = array($store,$uid,$complete,$subject,$content,
        $firstName,$lastName,$owner,$phone,$address);
        $prepSave = $dbc->prepare("INSERT INTO CustomerServiceTracker.Tracker (
                storeID,uid,date,complete,subject,content,firstName,lastName,
                            owner,phone,address) VALUES (?,?,NOW(),?,?,?,?,?,?,?,?);");
        $argsUpdate= array($complete,$subject,
            $firstName,$lastName,$owner,$phone,$address,$store,$id);
        $prepUpdate = $dbc->prepare("UPDATE CustomerServiceTracker.Tracker
            set complete = ?, subject = ?,
            firstName = ?, lastName = ?, owner = ?, phone = ?,
            address = ?, storeID = ? WHERE id = ?");
        if ($id) {
            //update Tracker 
            $res = $dbc->execute($prepUpdate,$argsUpdate);
            //save comment
            $args = array($id);
            $prep = $dbc->prepare("SELECT max(commentID) AS maxid FROM CustomerServiceTracker.TrackerComments WHERE trackerID = ?");
            $res = $dbc->execute($prep,$args);
            $row = $dbc->fetchArray($res);
            $commentID = (is_null($row['maxid'])) ? 1 : $row['maxid']+1;
            $saveCommentA = array($id,$commentID,$content,$uid);
            $saveCommentP = $dbc->prepare("INSERT INTO CustomerServiceTracker.TrackerComments (trackerID,commentID,date,comment,uid) VALUES (?, ?, NOW(), ?, ?)");
            $saveCommentR = $dbc->execute($saveCommentP,$saveCommentA);
        } else {
            //insert Tracker 
            $res = $dbc->execute($prepSave,$argsSave);
            //save comment
            $prep = $dbc->prepare("SELECT MAX(id) AS maxid FROM CustomerServiceTracker.Tracker");
            $res = $dbc->execute($prep);
            $row = $dbc->fetchArray($res);
            $id = $row['maxid'];
            $prep = $dbc->prepare("SELECT max(commentID) AS maxid FROM CustomerServiceTracker.TrackerComments WHERE trackerID = ?");
            $res = $dbc->execute($prep,$args);
            $row = $dbc->fetchArray($res);
            $commentID = (is_null($row['maxid'])) ? 1 : $row['maxid']+1;
            $saveCommentA = array($id,$commentID,$content,$uid);
            $saveCommentP = $dbc->prepare("INSERT INTO CustomerServiceTracker.TrackerComments (trackerID,commentID,date,comment,uid) VALUES (?, ?, NOW(), ?, ?)");
            $saveCommentR = $dbc->execute($saveCommentP,$saveCommentA);
        }


        $er = $dbc->error();

        return header('location: CustomerServiceLog.php#'.$er);

    }

    protected function get_new_view()
    {

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

    protected function get_old_view()
    {
        return <<<HTML
{$this->form_content()}
{$this->get_comments('old')}
HTML;
}
    
    protected function get_comments($mode='new')
    {
        $id = FormLib::get('id');
        $trx = ($id) ? 'highlight' : '';
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $stores = array();
        $fields = array('id','storeID','uid','date','subject','owner','firstName','lastName');
        $hiddenFields = array('firstName','lastName','owner','phone','address');
        $storeNames = array(1=>'Hillside',2=>'Denfeld');
        $qOpr = ($mode == 'new') ? '=' : '!=';
        $query = "SELECT * FROM CustomerServiceTracker.Tracker WHERE complete $qOpr'0000-00-00 00:00:00' ORDER BY id DESC;";
        $prep = $dbc->prepare($query);
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

        ///get comments
        $comments = array();
        $prep = $dbc->prepare("SELECT comment, trackerID FROM CustomerServiceTracker.TrackerComments");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $id = $row['trackerID'];
            $comment = $row['comment'];
            if (!isset($comments[$id])) {
                $comments[$id] = $comment;
            } else {
                $comments[$id] .= ". ".$comment;
            }
        }


        if ($er = $dbc->error()) echo '<div class="alert alert-danger">'.$er.'</div>';
        $identify = ($mode == 'new') ? ': Pending/Current | <i><a href="CustomerServiceLog.php?old=1">See Old</i></a>'
            : ': Completed/Old | <i><a href="CustomerServiceLog.php">See Current</i></a>';
        $tclass = ($mode == 'new') ? '' : 'old-table';

        $table = '<div class="table-responsive"><table class="table table-striped table-condensed table-bordered">
            <thead class="'.$tclass.'"><th class="text-center" colspan="7">Customer Service Tracker <span class="sm-text">'.$identify.'</span></th></thead><tbody>';
        foreach ($data as $id => $row) {
            $owner = '<a href="../PIKiller/PIMemberPage.php?id='.$row['owner'].'" target="_blank">'.$row['owner'].'</a>';
            $firstName = $row['firstName'];
            $lastName = $row['lastName'];
            $table .= '<tr><span style="display: none" class="id">'.$row['id'].'</span>';
            $idLink = ($mode == 'new') ? $row['id'] : $row['id'].'&old=1';
            $table .= '<td><span></span><a href="CustomerServiceLog.php?id='.$idLink.'"> #'.$row['id'].'</a></td>';
            $table .= '<td class="store">'.$storeNames[$row['storeID']].'</td>';
            foreach (array('date','subject','content') as $field) {
                if ($field === 'content') {
                   $table .= '<td><b>'.$owner.'</b> <b>'.$firstName.'</b> <b>'.$lastName.'</b> '.$comments[$row['id']].'</td>' ;
                } elseif ($field == 'date') {
                   $table .= '<td>'.substr($row[$field],0,10).'</td>';
                } else {
                   $table .= '<td>'.$row[$field].'</td>';
                }
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
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $stores = array();
        $prep = $dbc->prepare("SELECT * FROM Stores WHERE storeID < 50 ORDER BY storeID;");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $stores[$row['storeID']] = $row['description'];
        }
        $filterBtns = '';
        foreach ($stores as $id => $store) {
            $filterBtns .= '<p class="btn btn-primary filter" value="'.$id.'">'.$store.'</p> ';
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
            <button href="CustomerServiceLog.php" class="btn-warning">HOME</button>
        </div>
    </form>
</p>
HTML;
    }

    protected function inner_form_content($owner='',$firstName='',$lastName='',
        $phone='',$address='',$uid='',$date='',$subject='',$content='',$complete='')
    {

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        if ($complete != '') {
            $complete = substr($complete,0,10);
            $complete = "<label>Completed On</label>
                <div class='form-control input-xs'>$complete</div>";
        }
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $store = 1;
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
            $stores = array();
            $prep = $dbc->prepare("SELECT * FROM Stores WHERE storeID < 50 ORDER BY storeID;");
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

        $ids = $this->get_active_comment_ids($dbc);
        $id = FormLib::get('id');
        $new = FormLib::get('new');
        if ($new) {
            $date = date('Y-m-d h:i:s');
            $uid = FannieAuth::getUID($this->current_user);
        }
        $new = FormLib::get('new');
        $idHeading = ($new) ? 'NEW ENTRY' : 'ID#';
        $newFormIn = ($new) ? '' : '<input type="hidden" name="id" value="'.$id.'">';

        $last = end($ids);
        $first = reset($ids);
        $p = array_search($id,$ids)-1;
        $n = array_search($id,$ids)+1;
        $prev = ($p > array_search($first,$ids)) ? $ids[$p] : $first;
        $next = ($n < array_search($last,$ids)) ? $ids[$n] : $last;

        if ($new) {
            $left = '';
            $right = '';
            $btnComplete = '';
        } else {
            $left = '<a class="btn btn-default btn-xs" href="CustomerServiceLog.php?id='.$prev.'">
                <span class="glyphicon glyphicon-chevron-left"></span>
            </a>';
            $right = '<a class="btn btn-default btn-xs" href="CustomerServiceLog.php?id='.$next.'">
                <span class="glyphicon glyphicon-chevron-right" ></span>
            </a>';
            $btnComplete = '<button class="btn btn-default btn-complete" name="complete" value="1">
                <span class="glyphicon glyphicon-ok"></span>
            </button>';
        }
        $uid = FannieAuth::getName($uid);

        $comments = '';
        $getCommentsA = array($id);
        $getCommentsP = $dbc->prepare("SELECT comment, date, uid FROM CustomerServiceTracker.TrackerComments WHERE trackerID = ? 
            ORDER BY commentID ASC");
        $getCommentsR = $dbc->execute($getCommentsP,$getCommentsA);
        while ($row = $dbc->fetchRow($getCommentsR)) {
            if (!is_null($row['comment'])) {
                $temp = $row['date'];
                $date = substr($temp,0,10);
                $time = substr($temp,10);
                $cuid = FannieAuth::getName($row['uid']);
                if ($cuid == false) $cuid = '<i>unknown</i>';
                $comments .= "<div class='comment'>
                    <span class='sub-comment'>
                        $date | $time | $cuid: 
                    </span>{$row['comment']}
                </div>"; 
            }
        }
        $commentsLabel = (FormLib::get('new') == 1) ? '' : '<label>Comments</label>';

        return <<<HTML
<p class="form-inline">
    <div id="#jax-resp"></div>
    <form method="post" class="myform">
        <div>
            {$left}
            &nbsp; {$idHeading} {$id} &nbsp;
            {$right}
        </div>
        <div class="row">
            <div class="col-md-3">
                <label>Owner</label>
                <input type="text" class="form-control input-xs" id="owner" name="owner" value="{$owner}" autofocus>
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
                <input type="text" class="form-control input-xs" name="firstName" id="first_name" value="{$firstName}">
            </div>
            <div class="col-md-3">
                <label>Last Name</label>
                <input type="text" class="form-control input-xs" name="lastName" id="last_name" value="{$lastName}">
            </div>
            <div class="col-md-3">
            </div>
            <div class="col-md-3">
                {$storeForm}
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <label>Phone</label>
                <input type="text" class="form-control input-xs" name="phone" id="phone" value="{$phone}">
            </div>
            <div class="col-md-3">
                <label>Address</label>
                <input type="text" class="form-control input-xs" name="address" id="address" value="{$address}">
            </div>
            <div class="col-md-3">
            </div>
            <div class="col-md-3">
                $complete
            </div>

        </div>
        <div class="spacer"></div>
        <div class="form-group">
            <label>Subject</label>
            <input type="text" class="form-control input-sm" name="subject" value="{$subject}">
        </div>
            $commentsLabel
            $comments
        <div class="form-group">
            <label>Comment</label>
            <textarea class="form-control" name="content"></textarea>
        </div>
        <div class="form-group form-inline">
            {$newFormIn}
            <button class="btn btn-default btn-save" name="save" value="1">Save</button> |
            <a class="btn btn-default" href="CustomerServiceLog.php">Cancel</a>
            {$btnComplete}
        </div>
    </form>
</p>
HTML;
    }

    private function get_active_comment_ids($dbc)
    {
        $prep = $dbc->prepare("SELECT id from CustomerServiceTracker.Tracker
            WHERE complete = '0000-00-00 00:00:00' ORDER BY id");
        $res = $dbc->execute($prep);
        $ids = array();
        while ($row = $dbc->fetchRow($res)) {
            $ids[] = $row['id'];
        }
        return $ids;
    }

    public function javascriptContent()
    {
        $id = FormLib::get('id');
        return <<<HTML
var id = {$id}

$('.btn-complete').click(function(){
    var c = confirm('Mark Task as Completed?');
    if (c == true) {
        return true;
    } else {
        return false;
    }
});

$('#owner').change(function(){
    var ownerid = $('#owner').val();
    if (ownerid != 11) {
        $.ajax ({
            type: 'post',
            data: 'ownerid='+ownerid+'&custdata=1',
            dataType: 'json',
            success: function(resp)
            {
                var card_no = resp['card_no'];
                var data = ['first_name','last_name','street','city',
                    'state','zip','email_1','email_2','phone','address'];
                $.each(data, function(k,v) {
                    var value = resp[v];
                    $('#'+v).val(value);
                });
            }
        });
    }
});

$(document).ready(function() {
    btnClick();
});

function btnClick()
{
    $('.btn').click(function() {
        if ($(this).hasClass('btn-primary')) {
           $(this).removeClass('btn-primary').addClass('btn-default');
        } else {
           $(this).removeClass('btn-default').addClass('btn-primary');
        }
    });
}

$('p.filter').click(function() {
    var off = $(this).hasClass('btn-primary');
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
HTML;

    }

}
FannieDispatch::conditionalExec();

