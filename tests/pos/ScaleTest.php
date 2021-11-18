<?php

use COREPOS\pos\lib\DriverWrappers\NewMagellan;
use COREPOS\pos\lib\DriverWrappers\ssd;

/**
 * @backupGlobals disabled
 */
class ScaleTest extends PHPUnit_Framework_TestCase
{

    public function testWrappers()
    {
        $nm = new NewMagellan();
        $ss_output = dirname(__FILE__) . '/../../pos/is4c-nf/scale-drivers/drivers/NewMagellan/ss-output/test.output';

        file_put_contents($ss_output, 'S110123');
        ob_start();
        $nm->ReadFromScale();
        $read = json_decode(ob_get_clean(), true);
        $this->assertEquals(false, file_exists($ss_output));
        $this->assertInternalType('array', $read);
        $this->assertArrayHasKey('scale', $read);
        $this->assertNotEquals(0, strlen($read['scale']));

        file_put_contents($ss_output, '12345');
        ob_start();
        $nm->ReadFromScale();
        $read = json_decode(ob_get_clean(), true);
        $this->assertEquals(false, file_exists($ss_output));
        $this->assertInternalType('array', $read);
        $this->assertArrayHasKey('scans', $read);
        $this->assertEquals('12345', $read['scans']);

        file_put_contents($ss_output, 'foo');
        $nm->ReadReset();
        $this->assertEquals(false, file_exists($ss_output));

        ob_start();
        $nm->ReadFromScale();
        $this->assertEquals('{}', ob_get_clean());

        $ssd = new ssd();
        $dir = dirname(__FILE__) . '/../../pos/is4c-nf/scale-drivers/drivers/rs232/';
        file_put_contents($dir . 'scale', 'S110123');
        ob_start();
        $ssd->ReadFromScale();
        $read = json_decode(ob_get_clean(), true);
        $this->assertInternalType('array', $read);
        $this->assertArrayHasKey('scale', $read);
        $this->assertNotEquals(0, strlen($read['scale']));
        file_put_contents($dir . 'scanner', '12345');
        ob_start();
        $ssd->ReadFromScale();
        $read = json_decode(ob_get_clean(), true);
        $this->assertInternalType('array', $read);
        $this->assertArrayHasKey('scans', $read);
        $this->assertEquals('12345', $read['scans']);

        foreach (array('goodBeep', 'errorBeep', 'twoPairs') as $cmd) {
            $ssd->WriteToScale($cmd);
            $nm->WriteToScale($cmd);
        }
        foreach (array('rePoll', 'wakeup') as $cmd) {
            $ssd->WriteToScale($cmd);
            $nm->WriteToScale($cmd);
        }
    }
}

