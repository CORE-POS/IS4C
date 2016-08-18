<?php
include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class WfcHtEvalView extends FannieRESTfulPage
{ 
    protected $title = 'Eval List'; 
    protected $header = 'Eval List'; 
    protected $must_authenticate = true;
    protected $auth_classes = array('evals');
    public $discoverable = false;

    public function preprocess()
    {
        $this->addRoute(
            'get<addForm>',
            'get<commentForm>',
            'post<id><month><year><type><score><pos>',
            'post<id><user><comment>',
            'delete<id><eval>',
            'delete<id><comment>'
        );

        return parent::preprocess();
    }

    protected function delete_id_commnt_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');
        $q = $dbc->prepare("UPDATE evalComments SET deleted=1 WHERE id=?");
        $r = $dbc->execute($q, array($this->comment));
        echo $this->getComments($dbc, $this->id);

        return false;
    }

    protected function delete_id_eval_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');
        $q = $dbc->prepare("DELETE FROM evalScores WHERE id=? AND empID=?");
        $dbc->execute($q, array($this->eval, $this->id));

        echo $this->getHistory($dbc, $this->id);

        return false;
    }

    protected function post_id_user_comment_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');
        $q = $dbc->prepare("INSERT INTO evalComments(empID,comment,stamp,user,deleted) VALUES
            (?,?,now(),?,0)");
        $r = $dbc->execute($q, array($this->id, $this->comment, $this->user));

        echo $this->getComments($dbc, $this->id);

        return false;
    }

    protected function post_id_month_year_type_score_pos_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');

        $q = $dbc->prepare("INSERT INTO evalScores (empID,evalType,evalScore,month,year,pos)
            VALUES (?,?,?,?,?,?)");
        $r = $dbc->execute($q, array($this->id, $this->type, $this->score, $this->month, $this->year, $this->pos));

        echo $this->getHistory($dbc, $this->id);

        return false;
    }

    protected function get_addForm_handler()
    {
        $ret = "<table>";
        $ret .= "<tr class=\"form-inline\">";
        $ret .= "<td><select class=\"form-control input-sm\" id=addmonth>";
        for($i=1;$i<=12;$i++){
            $ret .= sprintf("<option value=%d %s>%s</option>",
                $i,(date('n')==$i?'selected':''),
                date('F',mktime(0,0,0,$i,1,2000)));
        }
        $ret .= "</select></td>";
        $ret .= "<td><input class=\"form-control input-sm\" type=text size=4 id=addyear value=";
        if (date('n')==12) $ret .= (date('Y')+1);    
        else $ret .= date('Y');
        $ret .= " /></td>";
        $ret .= "<td><select id=addtype class=\"form-control input-sm\">";
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');
        $q = "SELECT id,title FROM EvalTypes";
        $r = $dbc->query($q);
        while($w = $dbc->fetch_row($r)){
            $ret .= "<option value=$w[0]>$w[1]</option>";
        }
        $ret .= "</select></td>";

        $ret .= "<th>Score</th>";
        $ret .= "<td><input type=text size=3 id=addscore class=\"form-control input-sm\" /></td>";

        $ret .= "<th>Pos.</th>";
        $ret .= "<td><input type=text size=18 id=addpos class=\"form-control input-sm\" value=Primary /></td>";

        $ret .= "<td><button type=submit class=\"btn btn-default\" id=addsub>Add</button></td>";

        $ret .= "</tr></table>";
        echo $ret;

        return false;
    }

    protected function get_commentForm_handler()
    {
        $ret = "<textarea id=newcomment rows=10 cols=50 class=\"form-control\"></textarea>";
        $ret .= "<p />";
        $ret .= "<button class=\"btn btn-default\" type=submit onclick=\"saveComment();\">Save Comment</button>";
        echo $ret;

        return false;
    }

    protected function post_id_handler()
    {
        $month = isset($_REQUEST['month'])?$_REQUEST['month']:'';
        $year = isset($_REQUEST['year'])?$_REQUEST['year']:'';
        $pos = $_REQUEST['pos'];
        $date = "null";
        if (!empty($month) && !empty($year)){
            $date = $year."-".str_pad($month,2,'0',STR_PAD_LEFT)."-01";
        }
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');
        $hire = isset($_REQUEST['hire'])?$_REQUEST['hire']:'';
        if (strstr($hire,"/") !== False){
            $tmp = explode("/",$hire);
            if (count($tmp)==3)
                $hire = $tmp[2]."-".$tmp[0]."-".$tmp[1];
            else
                $hire = '';
        }
        $etype = $_REQUEST['etype'];
        $name = FormLib::get('name');
        
        $delQ = $dbc->prepare("DELETE FROM evalInfo WHERE empID=?");
        $insQ = $dbc->prepare("INSERT evalInfo VALUES (?,?,?,?,?)");
        $dbc->execute($delQ, array($this->id));
        $dbc->execute($insQ, array($this->id, $pos, $date, $hire, $etype));
        $nameP = $dbc->prepare('UPDATE employees SET name=? WHERE empID=?');
        $nameR = $dbc->execute($nameP, array($name, $this->id));
        echo "Info saved\nPositions: $pos\nNext Eval: ".trim($date,"'")."\nHire: ".trim($hire,"'");

     
        return false;
    }

    protected function empInfo($dbc, $id)
    {
        $ret = "<table class=\"table table-bordered\">";
        $q = $dbc->prepare("SELECT e.name,i.positions,i.nextEval,i.hireDate,i.nextTypeID FROM employees as e
            left join evalInfo as i on e.empID=i.empID
            WHERE e.empID=?");
        $r = $dbc->execute($q, array($id));
        $w = $dbc->fetch_row($r);
        $ret .= "<tr><th>Name</th><td colspan=2><input type=text class=\"form-control\" id=\"empName\" value=\"$w[0]\" /></td></tr>";
        $ret .= "<tr><th>Position(s)</th><td colspan=2><input type=text class=\"form-control\" id=\"empPositions\" value=\"$w[1]\" /></td></tr>";
        $tmp = explode("-",$w[3]);
        if (count($tmp) == 3)
            $w[3] = $tmp[1]."/".$tmp[2]."/".$tmp[0];
        $ret .= "<tr><th>Hire Date</th><td colspan=2><input class=\"form-control\" type=text id=\"hireDate\" value=\"$w[3]\" 
                onclick=\"\" /></td></tr>";
        $ret .= "<tr><th>Next Eval</th><td class=\"form-inline\">
            <select class=\"form-control\" id=nextMonth><option value=\"\"></option>";
        $tmp = explode("-",$w[2]);
        $month = "";
        $year = "";
        if (is_array($tmp) && count($tmp) == 3){
            $month = $tmp[1];
            $year = $tmp[0];
        }
        for($i=1;$i<=12;$i++){
            $ret .= sprintf("<option value=%d %s>%s</option>",
                $i,($i==$month?'selected':''),
                date("F",mktime(0,0,0,$i,1,2000)));
        }
        $ret .= "</select>";
        $ret .= "<input class=\"form-control\" type=text id=nextYear size=4 value=\"$year\" /></td>";
        $ret .= "<td><select class=\"form-control\" name=etype id=etype><option value=\"\"></option>";
        $et = $w[4];
        $q = "SELECT id,title FROM EvalTypes ORDER BY id";
        $r = $dbc->query($q);
        while($w = $dbc->fetch_row($r))
            $ret .= sprintf("<option %s value=\"%d\">%s</option>",($w[0]==$et?'selected':''),$w[0],$w[1]);
        $ret .= "</select></td>";
        $ret .= "</tr>";
        $ret .= "</table>";
        $ret .= "<div style=\"margin-top:5px;\">
            <button type=submit class=\"btn btn-default\" id=saveButton>Save Changes</button>
            </div>"; 
        return $ret;
    }

    protected function getHistory($dbc, $id)
    {
        $q = $dbc->prepare("SELECT e.month,e.year,t.title,e.evalScore,e.pos,e.id
            FROM evalScores AS e LEFT JOIN EvalTypes AS t
            ON e.evalType = t.id
            WHERE e.empID=?
            ORDER BY e.year DESC, e.month DESC");
        $r = $dbc->execute($q, array($id));
        $ret = "<table class=\"table table-bordered table-striped\">";
        $ret .= "<tr><th>Date</th><th>Type</th><th>Score</th><th>Position</th></tr>";
        while($w = $dbc->fetch_row($r)){
            $score = str_pad($w[3],3,'0');
            $score = substr($score,0,strlen($score)-2).".".substr($score,-2);
            $ret .= sprintf("<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%s</td>
                    <td><a href=\"\" onclick=\"return delEntry(%d);\" class=\"btn btn-danger btn-sm\">%s</a></tr>",
                date("F Y",mktime(0,0,0,$w[0],1,$w[1])),
                $w[2],
                $score,
                $w[4],$w[5],
                COREPOS\Fannie\API\lib\FannieUI::deleteIcon());
        }
        $ret .= "</table>";
        return $ret;
    }
    
    protected function getComments($dbc, $id)
    {
        $ret = "";

        $q = $dbc->prepare("SELECT stamp,user,comment,id FROM evalComments WHERE empID=? AND deleted=0 ORDER BY stamp DESC");
        $r = $dbc->execute($q, array($id));
        while($w = $dbc->fetch_row($r)){
            $ret .= sprintf('<div class="cHeader">%s - %s
                    <a href="" onclick="deleteComment(%d);return false;"
                    class="btn btn-danger btn-xs">%s</a></div>
                    <div class="cBody">%s</div>',
                    $w['stamp'],$w['user'],$w['id'],
                    COREPOS\Fannie\API\lib\FannieUI::deleteIcon(),
                    str_replace("\n","<br />",$w['comment']));
        }
        return $ret;
    }

    protected function get_id_view()
    {
        $this->addScript('view.js');
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');
        $einfo = $this->empInfo($dbc, $this->id);
        $history = $this->getHistory($dbc, $this->id);
        $comments = $this->getComments($dbc, $this->id);

        return <<<HTML
<p>
<a href="WfcHtEvalList.php" class="btn btn-default">Back to Employee List</a>
</p>

<div class="panel panel-default">
    <div class="panel panel-heading">Employee</div>
    <div class="panel-body">
        <div id="empfs">
        {$einfo}
        </div>
    </div>
</div>

<p>
    <button type=submit class="btn btn-default" id="addbutton">Add Eval</button>
    <div id="workspace"> 
    </div>
</p>

<div class="panel panel-default">
    <div class="panel panel-heading">History</div>
    <div class="panel-body">
        <div id="historyfs">
        {$history}
        </div>
    </div>
</div>

<p>
    <button class="btn btn-default" type=submit id="commentbutton">Add Comment</button>
    <div id="cform"> 
    </div>
</p>

<div class="panel panel-default">
    <div class="panel panel-heading">Comments</div>
    <div class="panel-body">
        <div id="commentfs">
        {$comments}
        </div>
    </div>
</div>

<input type=hidden id=empID value={$this->id} />
<input type=hidden id=username value="{$this->current_user}" />

<p>
    <a href="WfcHtEvalList.php" class="btn btn-default">Back to Employee List</a>
</p>
HTML;


    }
}

FannieDispatch::conditionalExec();

