<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class CalendarFeed extends FannieRESTfulPage
{
    protected $window_dressing = false;
    public $discoverable = false;

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
        if (!file_exists($filename) || $cal->modified() == 1 || FormLib::get('export') == 1) {
            $this->writeIcal($this->id, $filename);
            $cal->modified(0);
            $cal->save();
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
         
        $query = '
            SELECT m.eventID,
                m.eventDate,
                m.eventText,
                m.uid,
                u.real_name,
                u.name
            FROM monthview_events AS m
                LEFT JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'Users AS u ON m.uid=u.uid
            WHERE m.calendarID = ?';
        if (FormLib::get('export') != 1) {
            $query .= ' AND m.eventDate >= ' . $dbc->curdate();
        }
        $query .= ' ORDER BY eventDate DESC';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($id));

        $fp = fopen($filename, 'w');
        fwrite($fp, "BEGIN:VCALENDAR\r\n");
        fwrite($fp, "VERSION:2.0\r\n");
        fwrite($fp, "PRODID:-//FannieCalendarPlugin//NONSGML v1.0//EN\r\n");
        fwrite($fp, "X-WR-CALNAME:" . $cal->name() . "\r\n");
        fwrite($fp, "CALSCALE:GREGORIAN\r\n");
        $now = gmdate('Ymd') . 'T' . gmdate('His') . 'Z';
        while ($row = $dbc->fetch_row($res)) {
            $times = $this->getTime($row['eventText']);
            $date_stem = date('Y-m-d', strtotime($row['eventDate']));
            fwrite($fp, "BEGIN:VEVENT\r\n");
            fwrite($fp, "UID:" . sha1($row['eventID']) . '@' . $_SERVER['HTTP_HOST'] . "\r\n");
            if ($times && strtotime($date_stem . ' ' . $times['start'] . ':00') && strtotime($date_stem . ' ' . $times['end'] . ':00')) {
                $startTime = strtotime($date_stem . ' ' . $times['start'] . ':00');
                $endTime = strtotime($date_stem . ' ' . $times['end'] . ':00');
                fwrite($fp, "DTSTART:" . gmdate('Ymd\THis\Z', $startTime) . "\r\n");
                fwrite($fp, "DTEND:" . gmdate('Ymd\THis\Z', $endTime) . "\r\n");
            } else {
                fwrite($fp, "DTSTART;VALUE=DATE:" . date('Ymd', strtotime($row['eventDate'])) . "\r\n");
            }
            fwrite($fp, "DTSTAMP:" . $now . "\r\n");
            $row['eventText'] = $this->br2nl($row['eventText']);
            fwrite($fp, "DESCRIPTION:" . $this->escapeString($row['eventText']) . "\r\n");
            $summary = explode("\n", $row['eventText'], 2);
            fwrite($fp, "SUMMARY:" . $this->escapeString($summary[0]) . "\r\n");
            fwrite($fp, "ORGANIZER;CN=" . $row['real_name'] . ":" . $row['name'] . '@' . $_SERVER['HTTP_HOST'] . "\r\n");
            fwrite($fp, "LAST-MODIFIED:" . $now . "\r\n");
            fwrite($fp, "END:VEVENT\r\n");
        }
        fwrite($fp, "END:VCALENDAR\r\n");
    }

    private function br2nl($string)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', '\\n', $string);
    }

    /**
      Extract a time or time range from a string
      @param $string input value
      @return [keyed array]
        start => starting unix timestamp
    */
    private function getTime($string)
    {
        $regex = '/(\d{1,2}\s{0,2}(?:am|pm|(?::\d\d)){0,1}\s{0,2}(?:am|pm){0,1})(?:\s{0,2}(?:-|to|till|until)\s{0,2}(\d{1,2}\s{0,2}(?:am|pm|(?::\d\d)){0,1}\s{0,2}(?:am|pm){0,1}))?/i';
        $ret = false;
        if (preg_match($regex, $string, $matches)) {
            $hour1 = trim($matches[1]);
            $hour1_number = preg_replace('/^(\d+)\D?.*$/', '$1', $hour1);
            if ($hour1_number == 12) {
                $hour1_number = 0;
            }
            $hour2 = isset($matches[2]) ? $matches[2] : false;
            $hour2_number = $hour2 ? preg_replace('/^(\d+)\D?.*$/', '$1', $hour2) : false;
            if ($hour2_number == 12) {
                $hour2_number = 0;
            }
            if (!preg_match('/(am|pm)/i', $hour1)) {
                if ($hour2 !== false) {
                    if (preg_match('/am/i', $hour2) && $hour1_number < $hour2_number) {
                        $hour1 .= 'am';
                    } elseif (preg_match('/am/i', $hour2) && $hour1_number > $hour2_number) {
                        $hour1 .= 'pm';
                    } elseif (preg_match('/pm/i', $hour2) && $hour1_number < $hour2_number) {
                        $hour1 .= 'pm';
                    } elseif (preg_match('/pm/i', $hour2) && $hour1_number > $hour2_number) {
                        $hour1 .= 'am';
                    } elseif ($hour1_number < $hour2_number) {
                        $hour1 .= 'pm';
                        $hour2 .= 'am'; 
                    } elseif ($hour1_number > $hour2_number) {
                        $hour1 .= 'am';
                        $hour2 .= 'pm'; 
                    }
                } else {
                    $hour1 = false; 
                }
            }

            if ($hour2 && preg_match('/pm/i', $hour2)) {
                $hour2_number += 12;
            }
            if ($hour1 && preg_match('/pm/i', $hour1)) {
                $hour1_number += 12;
            }
            if ($hour1) {
                $hour1 = preg_replace('/^\d+(:?\d*)\D*$/', str_pad($hour1_number, 2, '0', STR_PAD_LEFT) . '$1', $hour1);
                if (!preg_match('/:/', $hour1)) {
                    $hour1 .= ':00';
                }
                if ($hour2 === false) {
                    $hour2_number = $hour1_number+1;
                    $hour2 = str_pad($hour2_number, 2, '0', STR_PAD_LEFT) . ':00';
                } else {
                    $hour2 = preg_replace('/^\d+(:?\d*)\D*$/', str_pad($hour2_number, 2, '0', STR_PAD_LEFT) . '$1', $hour2);
                    if (!preg_match('/:/', $hour2)) {
                        $hour2 .= ':00';
                    }
                }

                $ret = array('start'=>$hour1, 'end'=>$hour2);
            }
        } 

        return $ret;
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

