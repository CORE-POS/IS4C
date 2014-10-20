<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class MembersReport extends FannieReportPage
{
    protected $header = 'Members Report';
    protected $title = 'Members Report';
    protected $report_headers = array('#', 'First Name', 'Last Name', 'Start', 'End', 'Equity', 'Inactive');
    protected $required_fields = array('type');
    protected $no_sort_but_style = true;

    public $description = '[Members Report] lists members by type with active status and equity balance';
    public $report_set = 'Membership';
    public $themed = true;

    function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $inType = '';
        $args = array();
        foreach (FormLib::get('type', array()) as $memType) {
            $inType .= '?,';
            $args[] = $memType; 
        }
        $inType = substr($inType, 0, strlen($inType)-1);

        $trans = $FANNIE_TRANS_DB;
        if ($dbc->dbms_name() == 'mssql') {
            $trans .= ".dbo";
        }
        $q = $dbc->prepare_statement("
            SELECT c.CardNo,
                CASE WHEN m.start_date IS NULL THEN n.startdate ELSE m.start_date END AS startdate,
                m.end_date AS enddate,
                c.FirstName,
                c.LastName,
                CASE WHEN s.type = 'I' THEN 1 ELSE 0 END AS isInactive,
                CASE WHEN r.textStr IS NULL THEN s.reason ELSE r.textStr END as reason,
                CASE WHEN n.payments IS NULL THEN 0 ELSE n.payments END as equity
            FROM custdata AS c 
                LEFT JOIN memDates AS m ON m.card_no = c.CardNo AND c.personNum=1
                LEFT JOIN {$trans}.equity_history_sum AS n ON c.CardNo=n.card_no AND c.personNum=1
                LEFT JOIN suspensions AS s ON c.CardNo=s.cardno AND c.personNum=1
                LEFT JOIN reasoncodes AS r ON s.reasonCode & r.mask <> 0
            WHERE c.Type <> 'TERM' 
                AND (c.memType IN ($inType) OR s.memtype1 IN ($inType))
                AND c.personNum=1
            ORDER BY c.CardNo
        ");
        $arg_count = count($args);
        for ($i=0; $i<$arg_count; $i++) {
            $args[] = $args[$i];
        }
        $r = $dbc->exec_statement($q, $args);
        $saveW = array();
        $data = array();
        while ($w = $dbc->fetch_row($r)) {
            if ($w['CardNo'] != $saveW['CardNo']){
                if (count($saveW) > 0) {
                    $data[] = $this->formatRow($saveW);
                }
                $saveW = $w;
            } else {
                $saveW['reason'] .= ", ".$w['reason'];
            }
        }
        $data[] = $this->formatRow($saveW);

        return $data;
    }

    private function formatRow($arr)
    {
        $ret = array(
            $arr['CardNo'],
            $arr['FirstName'],
            $arr['LastName'],
        );
        if (date('Y', strtotime($arr['startdate'])) < 1900) {
            $ret[] = '';
        } else {
            $ret[] = date('m/d/Y', strtotime($arr['startdate']));
        }
        if (date('Y', strtotime($arr['enddate'])) < 1900) {
            $ret[] = '';
        } else {
            $ret[] = date('m/d/Y', strtotime($arr['enddate']));
        }
        $ret[] = sprintf('%.2f', $arr['equity']);
        $ret[] = ($arr['isInactive'] == 1) ? $arr['reason'] : '';

        return $ret;
    }

    public function report_description_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $memtypes = new MemtypeModel($dbc);
        $ret = 'List of: ';
        foreach (FormLib::get('type', array()) as $type) {
            $memtypes->memtype($type);
            $memtypes->load();
            $ret .= $memtypes->memDesc() . ', ';
        }

        return array($ret);
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $memtypes = new MemtypeModel($dbc);
        ob_start();
        ?>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
        <div class="panel panel-default">
            <div class="panel-heading">Include these Types</div>
        <div class="panel panel-body">
        <?php 
        foreach ($memtypes->find('memtype') as $m) {
            printf('
                <div class="form-group">
                    <label>
                        <input type="checkbox" class="checkbox-inline"
                            name="type[]" value="%d" %s />
                        %s
                    </label>
                </div>',
                $m->memtype(),
                ($m->custdataType() == 'PC') ? 'checked' : '',
                $m->memDesc()
            );
        }
        ?>
        <p>
            <button type="submit" class="btn btn-default">List Members</button>
        </p>
        </div> <!-- panel-body -->
        </div> <!-- panel -->
        </form>
        <?php

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

?>
