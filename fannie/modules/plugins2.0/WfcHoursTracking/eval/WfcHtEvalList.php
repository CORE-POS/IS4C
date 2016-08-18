<?php
include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class WfcHtEvalList extends FannieRESTfulPage
{ 
    protected $title = 'Eval List'; 
    protected $header = 'Eval List'; 
    protected $must_authenticate = true;
    protected $auth_classes = array('evals');
    public $discoverable = false;

    protected function put_handler()
    {

        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');

        $idP = $dbc->prepare("SELECT MAX(empID) FROM employees WHERE empID > 10000");
        $newID = $dbc->getValue($idP);
        $newID = $newID ? 10000 : $newID+1;

        $newP = $dbc->prepare('INSERT INTO employees (empID, name, deleted) VALUES (?, ?, ?)');
        $dbc->execute($newP, array($newID, '0 NEW EMPLOYEE', 1));

        return 'WfcHtEvalList.php';
    }

    protected function get_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = $this->connection;
        $dbc->selectDB('HoursTracking');
        $order = 'name';
        if (isset($_REQUEST['o'])) {
            switch($_REQUEST['o']) {
                case 'adpID':
                    $order = 'adpID';
                    break;
                case 'hireDate':
                    $order = 'hireDate';
                    break;
                case 'evalDate':
                    $order = 'evalDate';
                    break;
                default:
                    $order = 'name';
                    break;
            }
        }
        if ($order != 'name') $order .= ", name";

        $clause = "";
        $args = array();
        if (isset($_REQUEST['eM']) && is_numeric($_REQUEST['eM']) && is_numeric($_REQUEST['eY'])){
            $clause = ' AND YEAR(nextEval) = ? AND MONTH(nextEval) = ? ';
            $args[] = $_REQUEST['eY'];
            $args[] = $_REQUEST['eM'];
        }

        $q = $dbc->prepare("SELECT e.empID,name,adpID,i.nextEval,
            DATE_FORMAT(i.hireDate,'%m/%d/%Y') as hireDate,
            t.title FROM employees as e
            left join evalInfo as i on e.empID=i.empID 
            left join EvalTypes as t ON i.nextTypeID=t.id
            WHERE deleted=0 $clause order by $order");
        $r = $dbc->execute($q, $args);
        echo '<style type="text/css">a{color:blue;}</style>';
        echo '<form method=get>';
        echo '<div class="form-inline">';
        echo 'Filter by next eval: <select class="form-control" name=eM>';
        echo '<option value="">Month...</option>';
        for($i=1;$i<=12;$i++){
            printf('<option value=%d>%s</option>',$i,
                date("F",mktime(0,0,0,$i,1,2000)));
        }
        echo '</select>';
        echo ' <input type=text class="form-control" size=4 name=eY value="'.date("Y").'" />';
        echo ' <button type=submit class="btn btn-default">Filter</button>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="?_method=put" class="btn btn-default">Add Employee</a>
            </div></form>';
        echo '<b>Reports</b>: ';
        echo '<a href="WfcHtEvalReport.php">Most Recent Eval</a>';
        echo '<p />';
        echo "<table class=\"table table-bordered table-striped\">";
        echo '<tr>
            <th><a href="?o=name">Name</a></th>
            <th><a href="?o=adpID">ADP ID</a></th>
            <th><a href="?o=hireDate">Hire Date</a></th>
            <th colspan="2"><a href="?o=nextEval">Next Eval</a></th>
        </tr>';
        while($w = $dbc->fetch_row($r)){
            $next = "&nbsp;";
            $tmp = explode("-",$w[3]);
            if (is_array($tmp) && count($tmp) == 3){
                $next = $tmp[1]."/".$tmp[0];
            }
            printf("<tr><td><a href=WfcHtEvalView.php?id=%d>%s</a></td>
                <td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>",
                $w[0],$w[1],$w[2],
                (!empty($w[4])?$w[4]:'&nbsp;'),$next,
                (!empty($w[5])?$w[5]:'&nbsp;'));
        }
        echo "</table>";
    }

}

FannieDispatch::conditionalExec();


