<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

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

include_once(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class StatementsPluginIndex extends FannieRESTfulPage 
{
    public $page_set = 'Plugin :: StatementsPlugin';

    protected $header = 'Statements';
    protected $title = 'Statements';

    public function preprocess()
    {
        $this->__routes[] = 'get<equityTab>';
        $this->__routes[] = 'get<arTab>';
        $this->__routes[] = 'get<termTab>';
        $this->__routes[] = 'post<csv>';

        return parent::preprocess();
    }

    public function get_arTab_handler()
    {
        echo $this->arForm();

        return false;
    }

    public function get_equityTab_handler()
    {
        echo $this->reminderForm();

        return false;
    }

    public function get_termTab_handler()
    {
        echo $this->termForm();

        return false;
    }

    public function post_csv_handler()
    {
        $json = json_decode($this->csv);

        header('Content-Type: application/ms-excel');
        header('Content-Disposition: attachment; filename="'.$json->name.'.csv"');
        
        foreach ($json->records as $record) {
            for ($i=0; $i<count($record); $i++) {
                echo '"' . $record[$i] . '"';
                echo ($i < count($record)-1) ? ',' : "\r\n"; 
            }
        }

        return false;
    }

    public function get_view()
    {
        $ret = '<div id="tabs">';
        $ret .= '<ul>';
        $ret .= '<li><a href="#welcomeTab">Paid In Full</a></li>';
        $ret .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?equityTab=1">Equity Reminders</a></li>';
        $ret .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?arTab=1">AR Notices</a></li>';
        $ret .= '<li><a href="' . $_SERVER['PHP_SELF'] . '?termTab=1">Term Letters</a></li>';
        $ret .= '</ul>';
        $ret .= '<div id="welcomeTab">';
        $ret .= $this->welcomeForm();
        $ret .= '</div>';
        $ret .= '</div>';

        $this->add_onload_command('$(\'#tabs\').tabs();');

        return $ret;
    }

    private function welcomeForm()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $cutoff = mktime(0, 0, 0, date('n')-2, 1, date('Y'));

        $query = 'SELECT m.card_no,
                    c.LastName,
                    ' . $dbc->monthdiff($dbc->now(), 'h.mostRecent') . ' AS monthdiff
                  FROM memDates AS m
                    INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
                    INNER JOIN ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_live_balance AS e ON m.card_no=e.memnum
                    INNER JOIN ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_history_sum AS h ON m.card_no=h.card_no
                  WHERE h.mostRecent >= ?
                    AND e.payments >= 100';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array(date('Y-m-d', $cutoff)));
        $opt_sets = array('', '', '');
        while ($row = $dbc->fetch_row($result)) {
            $option = sprintf('<option value="%d">%d %s</option>',
                        $row['card_no'], $row['card_no'], $row['LastName']);
            $opt_sets[$row['monthdiff']] .= $option;
        }

        $ret = '<form id="welcomeForm" action="StatementsPluginPostCards.php" method="post">';
        $ret .= '<select onchange="$(\'#welcomeAccounts\').html($(this.value).html());">';
        $ret .= '<option value="#welcomeSet0">This Month</option>';
        $ret .= '<option value="#welcomeSet1">Last Month</option>';
        $ret .= '<option value="#welcomeSet2">Two Months Ago</option>';
        $ret .= '</select>';

        $ret .= '<button type="button" onclick="$(\'#welcomeAccounts option\').each(function(){$(this).attr(\'selected\', \'selected\');});
                    return false;">Select All</button>';
        $ret .= '<button type="submit">Print</button>';
        $ret .= '<button type="button" onclick="exportCSV(\'welcome\', \'#welcomeAccounts\');">Export List</button>';

        $ret .= '<br />';

        $ret .= '<select id="welcomeAccounts" name="id[]" multiple size="20">';
        $ret .= $opt_sets[0];
        $ret .= '</select>';
        $ret .= '</form>';

        for ($i=0; $i<3; $i++) {
            $ret .= '<div id="welcomeSet' . $i . '" style="display:none;">';
            $ret .= $opt_sets[$i];
            $ret .= '</div>';
        }

        return $ret;
    }

    private function reminderForm()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $cutoff = mktime(0, 0, 0, date('n')+2, 1, date('Y'));

        $query = 'SELECT m.card_no,
                    c.LastName,
                    ' . $dbc->monthdiff($dbc->now(), 'm.end_date') . ' AS monthdiff
                  FROM memDates AS m
                    INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
                    INNER JOIN ' . $FANNIE_TRANS_DB . $dbc->sep() . 'equity_live_balance AS e ON m.card_no=e.memnum
                  WHERE m.end_date BETWEEN ? AND ?
                    AND c.Type NOT IN (\'REG\', \'TERM\', \'INACT2\')
                    AND e.payments < 100';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array(date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59', $cutoff)));
        $opt_sets = array('', '', '');
        while ($row = $dbc->fetch_row($result)) {
            $option = sprintf('<option value="%d">%d %s</option>',
                        $row['card_no'], $row['card_no'], $row['LastName']);
            $opt_sets[abs($row['monthdiff'])] .= $option;
        }

        $ret = '<form id="reminderForm" action="StatementsPluginPostCards.php" method="post">';
        $ret .= '<select onchange="$(\'#reminderAccounts\').html($(this.value).html());">';
        $ret .= '<option value="#reminderSet0">This Month</option>';
        $ret .= '<option value="#reminderSet1">Next Month</option>';
        $ret .= '<option value="#reminderSet2">Two Months</option>';
        $ret .= '</select>';

        $ret .= '<button type="button" onclick="$(\'#reminderAccounts option\').each(function(){$(this).attr(\'selected\', \'selected\');});
                    return false;">Select All</button>';
        $ret .= '<button type="submit">Print</button>';
        $ret .= '<button type="button" onclick="exportCSV(\'reminder\', \'#reminderAccounts\');">Export List</button>';

        $ret .= '<br />';

        $ret .= '<select id="reminderAccounts" name="id[]" multiple size="20">';
        $ret .= $opt_sets[0];
        $ret .= '</select>';
        $ret .= '</form>';

        for ($i=0; $i<3; $i++) {
            $ret .= '<div id="reminderSet' . $i . '" style="display:none;">';
            $ret .= $opt_sets[$i];
            $ret .= '</div>';
        }

        return $ret;
    }

    private function arForm()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $opt_sets = array('', '', '');
        $q1 = 'SELECT a.card_no,
                c.LastName,
                m.email_1
               FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'ar_live_balance AS a
                INNER JOIN custdata AS c ON a.card_no=c.CardNo AND c.personNum=1
                LEFT JOIN suspensions AS s ON a.card_no=s.cardno
                INNER JOIN meminfo AS m ON a.card_no=m.card_no
               WHERE c.Type <> \'TERM\'
                AND (c.memType=2 OR s.memtype1=2)
                AND a.balance > 0
               GROUP BY a.card_no,
                c.LastName';
        $r1 = $dbc->query($q1);
        while ($row = $dbc->fetch_row($r1)) {
            if (filter_var($row['email_1'], FILTER_VALIDATE_EMAIL)) {
                $row['LastName'] .= ' &#x2709;';
            }
            $option = sprintf('<option value="%d">%d %s</option>',
                        $row['card_no'], $row['card_no'], $row['LastName']);
            $opt_sets[0] .= $option;
        }

        $q2 = 'SELECT a.cardno AS card_no,
                c.LastName,
                m.email_1
               FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'AR_EOM_Summary AS a
                INNER JOIN custdata AS c ON a.cardno=c.CardNo AND c.personNum=1
                LEFT JOIN suspensions AS s ON a.cardno=s.cardno
                INNER JOIN meminfo AS m ON a.cardno=m.card_no
               WHERE c.Type <> \'TERM\'
                AND (c.memType=2 OR s.memtype1=2)
                AND (a.lastMonthBalance <> 0 OR a.lastMonthCharges <> 0 OR a.lastMonthPayments <> 0)';
        $r2 = $dbc->query($q2);
        while ($row = $dbc->fetch_row($r2)) {
            if (filter_var($row['email_1'], FILTER_VALIDATE_EMAIL)) {
                $row['LastName'] .= ' &#x2709;';
            }
            $option = sprintf('<option value="%d">%d %s</option>',
                        $row['card_no'], $row['card_no'], $row['LastName']);
            $opt_sets[1] .= $option;
        }

        $q2 = 'SELECT a.b2bInvoiceID AS card_no,
                c.LastName,
                m.email_1
               FROM ' . $FANNIE_TRANS_DB . $dbc->sep() . 'B2BInvoices AS a
                INNER JOIN custdata AS c ON a.cardNo=c.CardNo AND c.personNum=1
                LEFT JOIN suspensions AS s ON a.cardNo=s.cardno
                INNER JOIN meminfo AS m ON a.cardNo=m.card_no
               WHERE c.Type <> \'TERM\'
                AND (c.memType=2 OR s.memtype1=2)
                AND (a.isPaid=0)';
        $r2 = $dbc->query($q2);
        while ($row = $dbc->fetch_row($r2)) {
            if (filter_var($row['email_1'], FILTER_VALIDATE_EMAIL)) {
                $row['LastName'] .= ' &#x2709;';
            }
            $option = sprintf('<option value="b2b%d">%d %s</option>',
                        $row['card_no'], $row['card_no'], $row['LastName']);
            $opt_sets[2] .= $option;
        }

        $ret = '<form id="arForm" action="StatementsPluginBusiness.php" method="post">';
        $ret .= '<select onchange="$(\'#arAccounts\').html($(this.value).html());">';
        $ret .= '<option value="#arSet0">Business (Any Balance)</option>';
        $ret .= '<option value="#arSet1">Business (EOM)</option>';
        $ret .= '<option value="#arSet2">B2B Invoices</option>';
        $ret .= '</select>';

        $ret .= '<button type="button" onclick="$(\'#arAccounts option\').each(function(){$(this).prop(\'selected\', true);});
                    return false;">Select All</button>';
        $ret .= '<button type="button" onclick="$(\'#arAccounts option\').each(function(){
                    if (/\u2709/.test($(this).html())) {
                        $(this).prop(\'selected\', true);
                    } else {
                        $(this).prop(\'selected\', false);
                    }
                    }); return false;">Select Email</button>';
        $ret .= '<button type="button" onclick="$(\'#arAccounts option\').each(function(){
                    if (/\u2709/.test($(this).html()) == false) {
                        $(this).prop(\'selected\', true);
                    } else {
                        $(this).prop(\'selected\', false);
                    } 
                    }); return false;">Select Paper</button>';
        $ret .= '<button onclick="$(\'#arForm\').attr(\'action\', \'StatementsPluginBusiness.php\');" type="submit">Print</button>';
        $ret .= '<button onclick="$(\'#arForm\').attr(\'action\', \'StatementsPluginEmail.php\');" type="submit">Email</button>';
        $ret .= '<button type="button" onclick="exportCSV(\'ar_statements\', \'#arAccounts\');">Export List</button>';

        $ret .= '<br />';

        $ret .= '<select id="arAccounts" name="id[]" multiple size="20">';
        $ret .= $opt_sets[0];
        $ret .= '</select>';
        $ret .= '</form>';

        for ($i=0; $i<3; $i++) {
            $ret .= '<div id="arSet' . $i . '" style="display:none;">';
            $ret .= $opt_sets[$i];
            $ret .= '</div>';
        }

        return $ret;
    }

    private function termForm()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $query = "SELECT c.CardNo AS card_no,
                    c.LastName 
                  FROM custdata AS c 
                    LEFT JOIN suspensions AS s ON c.CardNo=s.cardno
                  WHERE c.personNum=1
                    AND c.Type IN ('INACT','INACT2')
                    AND s.reasoncode & 64 <> 0
                  ORDER BY c.CardNo";
        $r1 = $dbc->query($query);
        $options = '';
        while ($row = $dbc->fetch_row($r1)) {
            $option = sprintf('<option value="%d">%d %s</option>',
                        $row['card_no'], $row['card_no'], $row['LastName']);
            $options .= $option;
        }

        $ret = '<form id="termForm" action="StatementsPluginTerm.php" method="post">';

        $ret .= '<button type="button" onclick="$(\'#termAccounts option\').each(function(){$(this).attr(\'selected\', \'selected\');});
                    return false;">Select All</button>';
        $ret .= '<button type="submit">Print</button>';
        $ret .= '<button type="button" onclick="exportCSV(\'term_letters\', \'#termAccounts\');">Export List</button>';

        $ret .= '<br />';

        $ret .= '<select id="termAccounts" name="id[]" multiple size="20">';
        $ret .= $options;
        $ret .= '</select>';
        $ret .= '</form>';

        return $ret;
    }

    public function javascript_content()
    {
        ob_start();
        ?>
        function exportCSV(name, select_elem)
        {
            var data = []
            $(select_elem+' option').each(function(){
                var record = [
                    $(this).val(),
                    $(this).html()
                ];
                data[data.length] = record;
            });
            var obj = {
                name: name+".csv",
                records: data
            };
            var form = $('<form method="post"/>');
            var field = $('<input name="csv" type="hidden"/>').val(JSON.stringify(obj));
            form.append(field);
            form.appendTo('body').submit();
        }
        <?php
        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();

