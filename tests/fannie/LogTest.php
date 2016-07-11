<?php

/**
 * @backupGlobals disabled
 */
class LogTest extends PHPUnit_Framework_TestCase
{
    public function testLogger()
    {
        $dbc = FannieDB::forceReconnect(FannieConfig::config('OP_DB'));
        $logger = new FannieLogger();
        $tempfile = tempnam(sys_get_temp_dir(), 'FLT');
        $context = array('logfile' => $tempfile);
        $message = 'test logging';
        $levels = array(
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
        );

        // test non-debug levels first
        foreach ($levels as $id => $level) {

            $pattern = '/^[A-Za-z]{3} \d+ \d\d:\d\d:\d\d .+ fannie\[\d+\]: \(' . $level . '\) test logging$/';

            // call emergency(), alert(), etc directly
            unlink($tempfile);
            $logger->$level($message, $context);
            $output = file_get_contents($tempfile);
            $this->assertRegExp($pattern, $output);

            // call log() with string level name
            unlink($tempfile);
            $logger->log($level, $message, $context);
            $output = file_get_contents($tempfile);
            $this->assertRegExp($pattern, $output);

            // call log() with int level ID
            unlink($tempfile);
            $logger->log($id, $message, $context);
            $output = file_get_contents($tempfile);
            $this->assertRegExp($pattern, $output);
        }

        $pattern = '/^[A-Za-z]{3} \d+ \d\d:\d\d:\d\d .+ fannie\[\d+\]: \(debug\) test logging$/';
        $frame = '/^[A-Za-z]{3} \d+ \d\d:\d\d:\d\d .+ fannie\[\d+\]: \(debug\) Frame \#\d+ .*, Line \d+, function [\w\\\\]+(::)?\w+$/';

        // test debug w/ stack trace
        unlink($tempfile);
        $context['verbose'] = true;
        $logger->debug($message, $context);
        $output = file_get_contents($tempfile);
        $lines = explode("\n", $output);
        for ($i=0; $i<count($lines); $i++) {
            if ($lines[$i] === '') {
                continue;
            }
            if ($i == 0) {
                $this->assertRegExp($pattern, $lines[$i]);
            } else {
                $this->assertRegExp($frame, $lines[$i]);
            }
        }

        // test debug again with an exception included in the context
        $e = new Exception('test exception');
        $context['exception'] = $e;
        unlink($tempfile);
        $logger->debug($message, $context);
        $output = file_get_contents($tempfile);
        $lines = explode("\n", $output);
        for ($i=0; $i<count($lines); $i++) {
            if ($lines[$i] === '') {
                continue;
            }
            if ($i == 0) {
                $this->assertRegExp($pattern, $lines[$i]);
            } else {
                $this->assertRegExp($frame, $lines[$i]);
            }
        }

        unlink($tempfile);
    }
}

