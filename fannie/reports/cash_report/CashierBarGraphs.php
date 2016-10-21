<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('FPDF')) {
    include(dirname(__FILE__) . '/../../src/fpdf/fpdf.php');
}

class CashierBarGraphs extends FannieRESTfulPage
{
    protected $title = "Fannie :: Cashier Report";
    protected $header = "Cashier Report";
    public $description = '[Cashier Performance Graphs] for a single cashier on select metrics.';
    public $themed = true;
    private $session_key = '';

    protected function readinessCheck()
    {
        $path = realpath(dirname(__FILE__));
        $path = rtrim($path, '/') . '/image_area';
        if (!is_dir($path)) {
            $this->error_text = 'Missing required directory ' . $path 
                . '; create it to use this report';
            return false;
        } elseif (!is_writable($path)) {
            $this->error_text = 'Directory ' . $path . ' must be writable by web server';
            return false;
        } else {
            return true;
        }
    }

    private function avg($array)
    {
        $count = 0;
        $sum = 0;
        foreach ($array as $a){
            $sum += $a;
            $count++;
        }
        return (float)$sum / $count;
    }

    public function get_view()
    {
        ob_start();
        ?>
        <form method=get action=<?php echo $_SERVER['PHP_SELF'] ?>
            class="form-inline">
        <p>
        <label>Enter Employee Number</label>
        <input type=text id=emp_no name="id" class="form-control" />
        <button type="submit" class="btn btn-default">Get Report</button>
        <label><input type=checkbox name=pdf /> PDF</label>
        </p>
        </form>
        <?php
        $this->add_onload_command('$(\'#emp_no\').focus();');

        return ob_get_clean();
    }

    public function get_id_handler()
    {
        $emp_no = $this->id;
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $query = "";
        $args = array();
        if ($emp_no=="") {
            $query = "select
                      emp_no,
                      ".$dbc->weekdiff($dbc->now(),'proc_date')." as week,
                      year(proc_date) as year,
                  SUM(Rings) / count(emp_no) as rings,
                  ".$dbc->convert('SUM(items)','int')." / count(emp_no) as items,
                  COUNT(Rings) / count(emp_no) as Trans,
                  SUM(CASE WHEN transinterval = 0 then 1 when transinterval > 600 then 600 else transinterval END) / count(emp_no)  / 60 as minutes,
                  SUM(Cancels) / count(emp_no) as cancels,
                  MIN(proc_date)
                  from CashPerformDay
                  GROUP BY emp_no,".$dbc->weekdiff($dbc->now(),'proc_date').",year(proc_date)
                  ORDER BY year(proc_date) desc,".$dbc->weekdiff($dbc->now(),'proc_date')." asc";
        } else {
            $query = "select
                      emp_no,
                      ".$dbc->weekdiff($dbc->now(),'proc_date')." as week,
                      year(proc_date) as year,
                      SUM(Rings) as rings,
                  ".$dbc->convert('SUM(items)','int')." as items,
                      COUNT(*) as TRANS,
                      SUM(CASE WHEN transInterval = 0 THEN 1 when transInterval > 600 then 600 ELSE transInterval END)/60 as minutes,
                      SUM(cancels)as cancels,
                      MIN(proc_date)
                      FROM CashPerformDay
                      WHERE emp_no = ?
                  GROUP BY emp_no,".$dbc->weekdiff($dbc->now(),'proc_date').",year(proc_date)
                  ORDER BY year(proc_date) desc,".$dbc->weekdiff($dbc->now(),'proc_date')." asc";
            $args = array($emp_no);
        }
        if ($dbc->isView('CashPerformDay') && $dbc->tableExists('CashPerformDay_cache')) {
            $query = str_replace('CashPerformDay', 'CashPerformDay_cache', $query);
        }
        $result = $dbc->execute($query,$args);

        $this->rpm = array(); // rings per minute
        $this->ipm = array(); // items per minute
        $this->tpm = array(); // transactions per minute
        $this->cpr = array(); // cancels per rings
        $this->cpi = array(); // cancels per items
        $week = array(); // first day of the week
        $i = 0;
        /* 
        calculate rates
        remove the time from the week
        */
        while ($row = $dbc->fetchRow($result)){
            $temp = explode(" ",$row[8]);
            $temp = explode("-",$temp[0]);
            $week[$i] = $temp[0]." ".$temp[1]." ".$temp[2];
            $minutes = $row[6];
            // zeroes values where minutes = 0
            if ($minutes == 0) {
                $minutes = 999999999;
            }
            $this->rpm[$i] = $row[3] / $minutes;
            $this->ipm[$i] = $row[4] / $minutes;
            $this->tpm[$i] = $row[5] / $minutes;
            if ($row[3] == 0) {
                $this->cpr[$i] = 0;
            } else {
                $this->cpr[$i] = ($row[7] / $row[3]) * 100;
            }
            if ($row[4] == 0) {
                $this->cpi[$i] = 0;
            } else {
                $this->cpi[$i] = ($row[7] / $row[4]) * 100;
            }
            $i++;
        }

        include_once('graph.php');

        /* clear out ony ld images */
        $dh = opendir('image_area');
        while ( ($file = readdir($dh)) !== false ) {
            if ($file[0] == '.') continue;
            if (substr($file, -4) == '.png' && strstr($file, 'cash_report')) {
                unlink('image_area/' . $file);
            }
        }
        closedir($dh);

        /* generate a reasonably unique session key */
        $session_key = '';
        for ($i = 0; $i < 20; $i++) {
            $num = rand(97,122);
            $session_key = $session_key . chr($num);
        }

        /* write graphs in the image_area directory */
        $session_key = "image_area/".$session_key;

        $width = graph($this->rpm,$week,$session_key."cash_report_0.png");
        graph($this->ipm,$week,$session_key."cash_report_1.png",10,0,0,255);
        graph($this->tpm,$week,$session_key."cash_report_2.png",60,0,255,0);
        graph($this->cpr,$week,$session_key."cash_report_3.png",90,0,0,255);
        graph($this->cpi,$week,$session_key."cash_report_4.png",90);


        if (isset($_GET['pdf'])) {
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial','B',16);
            $str = "Rings per minute\n";
            $str .= "(average: ".round($this->avg($this->rpm),2).")";
            $pdf->MultiCell(0,11,$str,0,'C');
            $pdf->Image($session_key."cash_report_0.png",65,35,$width/2);
            $pdf->AddPage();
            $str = "Items per minute\n";
            $str .= "(average: ".round($this->avg($this->ipm),2).")";
            $pdf->MultiCell(0,11,$str,0,'C');
            $pdf->Image($session_key."cash_report_1.png",65,35,$width/2);
            $pdf->AddPage();
            $str = "Transactions per minute\n";
            $str .= "(average: ".round($this->avg($this->tpm),2).")";
            $pdf->MultiCell(0,11,$str,0,'C');
            $pdf->Image($session_key."cash_report_2.png",65,35,$width/2);
            $pdf->AddPage();
            $str = "% rings cancelled\n";
            $str .= "(average: ".round($this->avg($this->cpr),2).")";
            $pdf->MultiCell(0,11,$str,0,'C');
            $pdf->Image($session_key."cash_report_3.png",65,35,$width/2);
            $pdf->AddPage();
            $str = "% items cancelled\n";
            $str .= "(average: ".round($this->avg($this->cpi),2).")";
            $pdf->MultiCell(0,11,$str,0,'C');
            $pdf->Image($session_key."cash_report_4.png",65,35,$width/2);
            $pdf->Output("Cashier_" . $emp_no . "_Report.pdf","D");

            return false;
        } else {
            $this->session_key = $session_key;

            return true;
        }
    }

    public function get_id_view()
    {
        $session_key = $this->session_key;
        ob_start();
        ?>
        <div align=center><h2>Rings per minute</h2>
        (<i>average:</i> <?php echo round($this->avg($this->rpm),2) ?>)<br />
        <img src=<?php echo $session_key."cash_report_0.png"?> />
        </div>
        <hr />
        <div align=center><h2>Items per minute</h2>
        (<i>average:</i> <?php echo round($this->avg($this->ipm),2) ?>)<br />
        <img src=<?php echo $session_key."cash_report_1.png"?> />
        </div>
        <hr />
        <div align=center><h2>Transactions per minute</h2>
        (<i>average:</i> <?php echo round($this->avg($this->tpm),2) ?>)<br />
        <img src=<?php echo $session_key."cash_report_2.png"?> />
        </div>
        <hr />
        <div align=center><h2>% Rings canceled</h2>
        (<i>average:</i> <?php echo round($this->avg($this->cpr),2) ?>)<br />
        <img src=<?php echo $session_key."cash_report_3.png"?> />
        </div>
        <hr />
        <div align=center><h2>% Items canceled</h2>
        (<i>average:</i> <?php echo round($this->avg($this->cpi),2) ?>)<br />
        <img src=<?php echo $session_key."cash_report_4.png"?> />
        </div>
        <?php

        return ob_get_clean();
    }
    public function helpContent()
    {
        return '<p>Show bar graphs of cashier performance. Terminology:
            <ul>
                <li><em>Rings</em> are line-items added to a transaction.</li>
                <li><em>Items</em> are the number of items in a transction.
                    For example, if a cashier enters "2*" then scans a UPC,
                    that counts as one ring but two items.</li>
                <li><em>Canceled</em> in this context means voiding a line
                    in a transaction.</li>
            </ul>';
    }
}

FannieDispatch::conditionalExec();

