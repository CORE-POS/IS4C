<?php

include(__DIR__ . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class IllnessLogReport extends FannieReportPage
{
    protected $header = 'Illness Logs';
    protected $title = 'Illness Logs';
    public $default_db = 'wfc_hr';
    protected $must_authenticate = true;
    protected $auth_classes = array('hr_editor', 'illness_editor', 'illness_viewer');
    protected $new_tablesorter = true;
    protected $report_headers = array('Illness Date', 'Employee', 'Type(s)', 'Excl.', 'MDH', 'Return Date', 'Final Form', 'Comments');

    function report_description_content()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);
        $ret = array();
        $year = date('Y');
        $cutoff = ($year - 1) . '-01-01';
        if ($this->report_format == 'html') {
            $ret[] = '<p><form action="IllnessLogReport.php" id="fForm" method="get" class="form-inline">';
            $ret[] = '<a href="../HrMenu.php" class="btn btn-default btn-small">Main Menu</a> | ';
            $store = FormLib::get('fStore', false);
            $model = new COREPOS\Fannie\Plugin\HrWeb\sql\HrStoresModel($dbc);
            $ret[] = '<label>Store</label> <select name="fStore" class="form-control input-sm" onchange="$(\'#fForm\').submit();">
                <option value=""></option>'
                . $model->toOptions($store) . '</select> | ';

            $emp = FormLib::get('fEmp', false);
            $prep = $dbc->prepare('SELECT i.employeeID AS id, e.firstName, e.lastName
                FROM IllnessLogs AS i
                    INNER JOIN Employees AS e ON i.employeeID=e.employeeID
                WHERE i.illnessDate >= ?
                GROUP BY i.employeeID, e.firstName, e.lastName
                ORDER BY e.lastName, e.firstName');
            $res = $dbc->execute($prep, array($cutoff));
            $eOpts = '';
            while ($row = $dbc->fetchRow($res)) {
                $eOpts .= sprintf('<option %s value="%d">%s, %s</option>',
                        $row['id'] == $emp ? 'selected' : '',
                        $row['id'], $row['lastName'], $row['firstName']);
            }
            $ret[] = '<label>Employee</label> <select name="fEmp" class="form-control input-sm" onchange="$(\'#fForm\').submit();">
                <option value=""></option>'
                . $eOpts . '</select> | ';

            $month = FormLib::get('fMonth', false);
            $res = $dbc->query('SELECT MONTH(illnessDate) AS m, YEAR(illnessDate) AS y FROM IllnessLogs AS i 
                GROUP BY MONTH(illnessDate), YEAR(illnessDate)
                ORDER BY YEAR(illnessDate) DESC, MONTH(illnessDate) DESC');
            $mOpts = '';
            while ($row = $dbc->fetchRow($res)) {
                $mOpts .= sprintf('<option %s value="%s">%s</option>',
                        $row['m'].':' .$row['y'] == $month ? 'selected' : '',
                        $row['m'].':'.$row['y'],
                        date('F Y', mktime(0,0,0, $row['m'], 1, $row['y']))
                );
            }
            $ret[] = '<label>Month</label> <select name="fMonth" class="form-control input-sm" onchange="$(\'#fForm\').submit();">
                <option value=""></option>'
                . $mOpts . '</select> | ';

            $year = FormLib::get('fYear', false);
            $res = $dbc->query('SELECT YEAR(illnessDate) AS y FROM IllnessLogs AS i 
                GROUP BY YEAR(illnessDate)
                ORDER BY YEAR(illnessDate) DESC');
            $yOpts = '';
            while ($row = $dbc->fetchRow($res)) {
                $yOpts .= sprintf('<option %s value="%s">%s</option>',
                        $row['y'] == $year ? 'selected' : '',
                        $row['y'], $row['y']
                );
            }
            $ret[] = '<label>Year</label> <select name="fYear" class="form-control input-sm" onchange="$(\'#fForm\').submit();">
                <option value=""></option>'
                . $yOpts . '</select> | ';

            $final = FormLib::get('fFinal', false);
            $fOpts = '';
            foreach (array(1=>'Yes', 0=>'No') as $k => $v) {
                $fOpts .= sprintf('<option %s value="%d">%s</option>',
                    ($final !== false && $final !== '' && $final == $k) ? 'selected' : '',
                    $k, $v);
            }
            $ret[] = '<label>Final Form</label> <select name="fFinal" class="form-control input-sm" onchange="$(\'#fForm\').submit();">
                <option value=""></option>'
                . $fOpts . '</select> | ';

            $ret[] = '<button type="button" class="btn btn-default btn-small" onclick="$(\'select\').val(\'\');$(\'#fForm\').submit();">Clear All</button>';

            $ret[] = '</form></p>';
        }

        return $ret;
    }

    public function fetch_report_data()
    {
        $editCSS = FannieAuth::validateUserQuiet('hr_editor') ? '' : 'collapse';
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['HrWebDB']);

        $year = date('Y');
        $cutoff = ($year - 1) . '-01-01';
        $args = array($cutoff);
        $openQ = '
            SELECT i.illnessLogID,
                i.illnessDate,
                e.firstName,
                e.lastName,
                i.exclusionary,
                i.MDHContacted,
                i.returnToWorkDate,
                i.finalFormSubmitted,
                MAX(i.comments) AS comments,
                GROUP_CONCAT(t.illnessType, \', \') AS types
            FROM IllnessLogs AS i
                LEFT JOIN IllnessLogsIllnessTypes AS m ON i.illnessLogID=m.illnessLogID
                LEFT JOIN IllnessTypes AS t ON m.illnessTypeID=t.illnessTypeID
                LEFT JOIN Employees AS e ON i.employeeID = e.employeeID 
            WHERE i.illnessDate >= ? ';
        if (FormLib::get('fEmp')) {
            $openQ .= ' AND i.employeeID=? ';
            $args[] = FormLib::get('fEmp');
        }
        if (FormLib::get('fMonth')) {
            list($m, $y) = explode(':', FormLib::get('fMonth'), 2);
            $ts = mktime(0, 0, 0, $m, 1, $y);
            $openQ .= ' AND i.illnessDate BETWEEN ? AND ? ';
            $args[] = date('Y-m-d 00:00:00', $ts);
            $args[] = date('Y-m-t 23:59:59', $ts);
        }
        if (FormLib::get('fYear')) {
            $openQ .= ' AND i.illnessDate BETWEEN ? AND ? ';
            $args[] = FormLib::get('fYear') . '-01-01 00:00:00';
            $args[] = FormLib::get('fYear') . '-12-31 23:59:59';

        }
        if (FormLib::get('fFinal') !== '') {
            $openQ .= ' AND i.finalFormSubmitted=? ';
            $args[] = FormLib::get('fFinal') ? 1 : 0;
        }
        if (FormLib::get('fStore')) {
            $openQ .= ' AND i.employeeID IN (SELECT DISTINCT employeeID FROM EmployeeStores WHERE storeID=?) ';
            $args[] = FormLib::get('fStore');
        }
        $openQ .= ' GROUP BY i.illnessLogID,
                i.illnessDate,
                e.firstName,
                e.lastName,
                i.exclusionary,
                i.MDHContacted,
                i.returnToWorkDate,
                i.finalFormSubmitted
            ORDER BY i.illnessDate DESC';
        $openP = $dbc->prepare($openQ);
        $openR = $dbc->execute($openP, $args);
        $data = array();
        while ($row = $dbc->fetchRow($openR)) {
            $data[] = array(
                $row['illnessDate'],
                $row['lastName'] . ', ' . $row['firstName'],
                $row['types'],
                ($row['exclusionary'] ? 'Yes' : 'No'),
                ($row['MDHContacted'] ? 'Yes' : 'n/a'),
                ($row['returnToWorkDate'] ? $row['returnToWorkDate'] : ''),
                ($row['finalFormSubmitted'] ? 'Yes' : 'No'),
                htmlentities($row['comments']),
            );
        }
        
        return $data;
    }
}

FannieDispatch::conditionalExec();

