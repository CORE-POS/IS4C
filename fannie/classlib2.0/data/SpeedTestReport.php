<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(dirname(__FILE__) . '/../FannieAPI.php');
}

class SpeedTestReport extends FannieRESTfulPage
{

    public $themed = true;
    protected $header = 'Archive Table Speed Test';

    public function preprocess()
    {
        $this->__routes[] = 'get<date1><date2><dept>';

        return parent::preprocess();
    }
    

    public function get_date1_date2_dept_view()
    {
        $start = FormLib::get('date1');
        $end = FormLib::get('date2');
        $dept_limit = FormLib::get('dept');
        $method = FormLib::get('sql-method');
        
        $dlog = false;
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $timing_point_1 = microtime(true);
        switch ($method) {
            case 'Large Temporary Table':
                $where = array(
                    'connection' => $dbc,
                    'clauses' => array(
                        array('sql'=>' trans_type IN (\'I\',\'D\') ', 'params'=>array()),
                        array('sql'=>' department BETWEEN 0 AND ? ', 'params'=>array($dept_limit)),
                    ),
                );
                $dlog = DTransactionsModel::selectDTrans($start, $end, $where);
                break;
            case 'Aggregate Temporary Table':
                $dlog = DTransactionsModel::selectDTransSumByDepartment($dbc, $start, $end);
                break;
            case 'Single Query':
            default:
                $dlog = DTransactionsModel::selectDTrans($start, $end);
                break;
        }
        $timing_point_2 = microtime(true);

        $ret = '<p>Using archive table(s): <em>' . $dlog . '</em></p>';

        $query = '
            SELECT d.dept_name,
                SUM(t.total) AS ttl
            FROM ' . $dlog . ' AS t
                LEFT JOIN departments AS d ON t.department=d.dept_no
            WHERE t.datetime BETWEEN ? AND ?
                AND trans_type IN (\'I\', \'D\')
                AND t.department BETWEEN 0 AND ?
            GROUP BY d.dept_name';
        $prep = $dbc->prepare($query);
        $args = array(
            $start . ' 00:00:00',
            $end . ' 23:59:59',
            $dept_limit,
        );
        $result = $dbc->execute($prep, $args);

        $timing_point_3 = microtime(true);

        $ret .= '<p>Query used:<pre>' . $query . '</pre></p>';

        $ret .= '<p>Query succeeded: ' . ($result ? 'Yes' : 'No') . '</p>';

        $ret .= '<p>Elapsed time: ' . ($timing_point_3 - $timing_point_1) . '</p>';

        return $ret;
    }

    public function get_view()
    {
        $ret = '<form method="get">
            <div class="col-sm-5">
                <div class="form-group">
                    <label>Dept# &lt=</label>
                    <input type="text" name="dept" class="form-control">
                </div>
                <div class="form-group">
                    <label>Method</label>
                    <select name="sql-method" class="form-control">
                        <option>Single Query</option>
                        <option>Large Temporary Table</option>
                        <option>Aggregate Temporary Table</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-default">Run</button>
                </div>
            </div>';
        $ret .= FormLib::standardDateFields();
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

