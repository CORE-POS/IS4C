<?php

class SpinsDate
{
    private $iso_week;
    private $year;
    private $spins_week;
    private $start;
    private $end;
    public function __construct($offset=0, $iso_week=-1)
    {
        $this->year = date('Y');
        $this->iso_week = $iso_week;
        // if week is not specified default to previous week
        $specific_week = true;
        if ($this->iso_week < 0) {
            $specific_week = false;
            $this->iso_week = date('W');
            $this->iso_week--;
            if ($this->iso_week <= 0) {
                $this->iso_week = 52;
                $this->year--;
            }
        }
        $this->spins_week = $this->iso_week;
        $this->iso_week += $offset;

        // First day of ISO week is a Monday
        $this->start = strtotime($this->year . 'W' . str_pad($this->iso_week, 2, '0', STR_PAD_LEFT));
        // if the SpinsOffset results in non-existant week 0, 
        // use ISO week 1 and go back seven days
        if ($this->iso_week == 0) {
            $this->start = strtotime($this->year . 'W01');
            $this->start = mktime(0, 0, 0, date('n', $this->start), date('j',$this->start)-7, date('Y', $this->start));
        }
        // walk forward to Sunday
        $this->end = $this->start;
        while (date('w', $this->end) != 0) {
            $this->end = mktime(0,0,0,date('n',$this->end),date('j',$this->end)+1,date('Y',$this->end));
        }

        /**
          When the offset to SPINS' week numbering is negative, the default week
          can end up being multiple weeks ago. All data will still get submitted
          eventually but period deadlines get missed. Unless a specific week number
          has been requested, this section walks forward in 7 day increments so the
          default week is also the most recent completed week.
        */
        $endDT = new DateTime(date('Y-m-d', $this->end));
        $today = new DateTime(date('Y-m-d'));
        $endDT->add(new DateInterval('P7D'));
        while (!$specific_week && $endDT < $today) {
            $this->spins_week++;
            $this->iso_week++;
            $this->start = mktime(0,0,0, date('n',$this->start), date('j',$this->start)+7, date('Y',$this->start));
            $this->end = mktime(0,0,0, date('n',$this->end), date('j',$this->end)+7, date('Y',$this->end));
            $endDT->add(new DateInterval('P7D'));
        }
    }

    public function spinsWeek()
    {
        return $this->spins_week;
    }

    public function startDate()
    {
        return date('Y-m-d', $this->start);
    }
    
    public function endDate()
    {
        return date('Y-m-d', $this->end);
    }

    public function endTimeStamp()
    {
        return $this->end;
    }
}

if (php_sapi_name() == 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $offset = isset($argv[1]) ? $argv[1] : 0;
    $iso = isset($argv[2]) ? $argv[2] : -1;
    $test = new SpinsDate($offset, $iso);
    echo "Usage: SpinsDate.php [offset] [week number]\n";
    echo "SPINS week " . $test->spinsWeek() . " from " . $test->startDate() . " to " . $test->endDate() . "\n";
}

