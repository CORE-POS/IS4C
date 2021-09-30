<?php

/**
 * Pulls warning & error lines out of fannie.log and emails them out
 * Tracks file offset to avoid repeats.
 * Requires unix-y utilities (wc, tail, grep)
 */
class LogEscalatorTask extends FannieTask
{
    public $name = 'Log Escalator';

    public $description = 'Send email notifications for logged errors & warnings.';

    public $default_schedule = array(
        'min' => '*/10',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );
    public $log_start_stop = false;

    public function run()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $offsetPath = $settings['LogEscalateOffsetFile'];

        $logfile = __DIR__ . '/../../../logs/fannie.log';
        if (file_exists($offsetPath)) {
            $offset = @file_get_contents($offsetPath);
            $offset = $offset ? $offset : 0;
        } else {
            $offset = 0;
        }
        $lines = exec('wc -l ' . escapeshellarg($logfile));
        if ($lines) {
            list($lines, $filname) = explode(' ', $lines);
        }
        if ($lines < $offset) {
            $offset = 0;
        }

        $chk_cmd = 'tail -n ' . escapeshellarg($lines - $offset)
            . ' ' . escapeshellarg($logfile)
            . ' | grep -E "fannie.(WARNING|ERROR|ALERT|CRITICAL|EMERGENCY)"';
        exec($chk_cmd, $output);
        if (count($output) > 0) {
            $saved_offset = file_put_contents($offsetPath, $lines);
            $addr = $settings['LogEscalateEmail'];
            $addr = filter_var($addr, FILTER_VALIDATE_EMAIL);
            if ($addr) {
                $msg = implode("\n", $output);
                if ($saved_offset === false) {
                    $msg = "PERMISSION ERROR SAVING OFFSET - THESE EMAILS MAY CONTINUALLY SEND UNTIL RESOLVED\n\n" . $msg;
                }
                $from = $settings['LogEscalateFrom'];
                $headers = '';
                if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
                    $headers = "From: $from";
                }
                $mailResult = mail($addr, 'CORE POS Log Escalation', $msg, $headers);
            }
        }

    }
}

