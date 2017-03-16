<?php

namespace COREPOS\Fannie\API;
use \FannieReportPage;
use \FannieConfig;
use \FannieDB;
use \FannieLogger;
use \PHPMailer;
use COREPOS\Fannie\API\data\DataConvert;

/**
  @class QueuedReportPage

  Queued reports are reports that are not executed immediately
  but instead added to a queue for later background processing.
  This is intended primarily for reports that may take several
  minutes to fetch all the requested data. 

  A queued report's <form> must contain an input field for an
  email address whose name matches the $email_field property
  of the class.

  A queued report's fetch_report_data function *must* read report
  parameters from the page's $form property. Calls to FormLib::get
  will not work when the report is run via a cron job.

  The runNextReport function will check the queue, run one report,
  and email the results to the requested email. The report content
  will be attached as a CSV. This method includes concurrency checking
  to make sure only one queued report is running at any given time.
*/
class QueuedReportPage extends FannieReportPage
{
    protected $email_field = 'email';

    /**
      When the report is submitted, queue it instead of
      fetching results immediately
    */
    public function preprocess()
    {
        $ret = parent::preprocess();
        if ($this->content_function = 'report_content') {
            $this->content_function = 'enqueueContent';
        }
    }

    /**
      Add report to the queue
    */
    protected function enqueueContent()
    {
        $savedForm = serialize($this->form);
        $class = get_class($this);
        $refl = new ReflectionClass($this);
        $file = $refl->getFileName();
        $email = $this->form->tryGet($this->email_field);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        if ($email === false) {
            return '<div class="alert alert-danger">Invalid email address</div>';
        }

        $chkP = $this->connection->prepare("
            SELECT queuedReportID
            FROM QueuedReports
            WHERE email=?
                AND reportClass=?
                AND formData LIKE ?");     
        $exists = $this->connection->execute($chkP, array($email, $class, $savedForm));
        if ($exists !== false) {
            return '<div class="alert alert-danger">You already queued this report</div>';
        }

        $insP = $this->connection->prepare("
            INSERT INTO QueuedReports
                (email, reportClass, reportFile, formData)
                VALUES (?, ?, ?, ?)");
        $insR = $this->connection->execute($insP, array($email, $class, $file, $savedForm));
        if ($insR === false) {
            return '<div class="alert alert-danger">Error queueing report</div>';
        }

        return '<div class="alert alert-success">Your report has been queued</div>';
    }

    /**
      Set a lock file to prevent concurrent report requests
    */
    private function takeLock()
    {
        $lockfile = sys_get_temp_dir() . '/corereport.lock';
        if (file_exists($lockfile)) {
            return false;
        }
        $file = fopen($lockfile, 'r');
        if ($file === false) {
            return false;
        }
        $locked = flock($file, LOCK_EX);
        if ($locked === false) {
            return false;
        }

        return $file;
    }

    /**
      Wrapper to release lock and return true
    */
    private function releaseLock($file)
    {
        flock($file, LOCK_UN);
        fclose($file);

        return true;
    }

    private function popQueue($reportID)
    {
        $prep = $this->connection->prepare('DELETE FROM QueuedReports WHERE queuedReportID=?');
        return $this->connection->execute($prep, array($reportID));
    }

    private function initClass($class)
    {
        $obj = new $class();
        $conf = FannieConfig::factory();
        $obj->setConfig($conf);
        $obj->setLogger(FannieLogger::factory());
        $obj->setConnection(FannieDB::get($conf->get('OP_DB')));

        return $obj;
    }

    private function sendReport($email, $data, $class)
    {
        $mail = new PHPMailer();
        $mail->FromName = 'Queued Report';
        $mail->addAddress($email);
        $mail->Body = 'Requested report attached. Do not reply to this email.';
        $mail->Subject = 'POS Report Delivery';
        
        $csv = DataConvert::arrayToCsv($data);
        $mail->addStringAttachment($csv, $class . '.csv', 'base64', 'text/csv');
        
        return $mail->send();
    }

    /**
      1. Set a lock to prevent concurrent running concurrently
      2. Get the next queued report
      3. Validate queued report
      4. Run report
      5. Email data to specified address
      6. Remove report from the queue
      7. Release lock
    */
    public function runNextReport()
    {
        $lockFile = $this->takeLock();
        // another report is running
        if ($lockFile === false) {
            return true;
        }

        $getP = $this->connection->prepare('SELECT queuedReportID,email,reportClass,reportFile,formData FROM QueuedReports ORDER BY queuedReportID');
        $report = $this->connection->getRow($getP);
        // queue is empty
        if ($report === false) {
            return true;
        }

        // bad queued file
        if (!file_exists($report['reportFile'])) {
            $this->popQueue($report['queuedReportID']);
            return $this->releaseLock($lockFile);
        }

        /**
          Symlinking may cause issues here, but attempting to verify
          the requested file is at least within the fannie directory
          structure seems like a reasonable precaution.
        */
        $ourself = realpath(__DIR__ . '/../');
        if (strpos($report['reportFile'], $ourself) !== 0) {
            $this->popQueue($report['queuedReportID']);
            return $this->releaseLock($lockFile);
        }

        include_once($report['reportFile']);

        // bad queued class
        if (!class_exists($report['reportClass'])) {
            $this->popQueue($report['queuedReportID']);
            return $this->releaseLock($lockFile);
        }

        $obj = $this->initClass($report['reportClass']);
        $form = unserialize($report['formData']);

        // bad queued form values
        if (!is_a($obj, 'COREPOS\\common\\mvc\\ValueContainer')) {
            $this->popQueue($report['queuedReportID']);
            return $this->releaseLock($lockFile);
        }

        $obj->setForm($form);
        $data = $obj->fetch_report_data();
        $this->sendReport($report['email'], $data, $report['reportClass']);
        $this->popQueue($report['queuedReportID']);

        return $this->releaseLock($lockFile);
    }
}

