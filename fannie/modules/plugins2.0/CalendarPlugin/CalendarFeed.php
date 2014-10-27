<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class CalendarFeed extends FannieRESTfulPage
{
    protected $window_dressing = false;
    public $page_set = 'Plugin :: Calendar';
    public $description = '[Feed] outputs iCalendar format data for calendar events.';
    public $themed = true;

    /**
      Need to forcibly turn off menu
      since this page isn't html at all
    */
    public function getHeader()
    {
        return '';
    }
    public function getFooter()
    {
        return '';
    }

    public function preprocess()
    {
        $this->__routes[] = 'get<id><token>';

        return parent::preprocess();
    }

    public function get_id_token_handler()
    {
        $dbc = CalendarPluginDB::get();
        $cal = new CalendarsModel($dbc); 
        $cal->calendarID($this->id);
        if (!$cal->load()) {
            header("HTTP/1.0 404 Not Found");
            echo "404";
            return false;
        }

        $token = sha1($cal->calendarID() . 'FannieCalendar' . $cal->name());
        if ($token != $this->token) {
            header("HTTP/1.0 403 Forbidden");
            echo "403";
            return false;
        }

        $filename = dirname(__FILE__) . '/ics/' . $this->token . '.ics';
        if (!file_exists($filename) || $cal->modified() == 1) {
            $this->writeIcal($this->id, $filename);
        }

        return true;
    }

    private function writeICal($id, $filename)
    {
        global $FANNIE_OP_DB;
        $dbc = CalendarPluginDB::get();
        $cal = new CalendarsModel($dbc); 
        $cal->calendarID($id);
        $cal->load();
         
        $prep = $dbc->prepare('
            SELECT m.eventID,
                m.eventDate,
                m.eventText,
                m.uid,
                u.real_name,
                u.name
            FROM monthview_events AS m
                LEFT JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'Users AS u ON m.uid=u.uid
            WHERE m.calendarID = ?
            ORDER BY eventDate DESC');
        $res = $dbc->execute($prep, array($id));

        $fp = fopen($filename, 'w');
        fwrite($fp, "BEGIN:VCALENDAR\r\n");
        fwrite($fp, "VERSION:2.0\r\n");
        fwrite($fp, "PRODID:-//FannieCalendarPlugin//NONSGML v1.0//EN\r\n");
        fwrite($fp, "X-WR-CALNAME:" . $cal->name() . "\r\n");
        fwrite($fp, "CALSCALE:GREGORIAN\r\n");
        $now = gmdate('Ymd') . 'T' . gmdate('His') . 'Z';
        while ($row = $dbc->fetch_row($res)) {
            fwrite($fp, "BEGIN:VEVENT\r\n");
            fwrite($fp, "UID:" . sha1($row['eventID']) . '@' . $_SERVER['HTTP_HOST'] . "\n");
            fwrite($fp, "DTSTART;VALUE=DATE:" . date('Ymd', strtotime($row['eventDate'])) . "\n");
            fwrite($fp, "DESCRIPTION:" . $this->escapeString($row['eventText']) . "\r\n");
            $summary = explode("\n", $row['eventText'], 2);
            fwrite($fp, "SUMMARY:" . $this->escapeString($summary[0]) . "\r\n");
            fwrite($fp, "ORGANIZER;CN=" . $row['real_name'] . ":" . $row['name'] . '@' . $_SERVER['HTTP_HOST'] . "\r\n");
            fwrite($fp, "LAST-MODIFIED:" . $now . "\n");
            fwrite($fp, "END:VEVENT\n");
        }
        fwrite($fp, "END:VCALENDAR\n");
    }

    // Escapes a string of characters
    private function escapeString($string) {
        return preg_replace('/([\,;])/','\\\$1', $string);
    }

    public function get_id_token_view()
    {
        $filename = dirname(__FILE__) . '/ics/' . $this->token . '.ics';
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . time() . '-' . basename($filename));
        header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60*15)));
        header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', filemtime($filename)));
        header('ETag: ' . sha1_file($filename));
        header('Cache-Control: private');
        header_remove('Pragma');
        
        readfile($filename);

        exit; // avoid trailing close html tag
    }
}

FannieDispatch::conditionalExec();

