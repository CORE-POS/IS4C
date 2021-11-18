<?php

use COREPOS\pos\lib\gui\InputCorePage;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
use COREPOS\common\mvc\ValueContainer;

/**
 * @backupGlobals disabled
 */
class PagesTest extends PHPUnit_Framework_TestCase
{

    public function testLib()
    {
        $classes = array('COREPOS\\pos\\lib\\gui\\BasicCorePage', 'COREPOS\\pos\\lib\\gui\\InputCorePage', 'COREPOS\\pos\\lib\\gui\\NoInputCorePage');
        $session = new WrappedStorage();
        $form = new ValueContainer();
        foreach ($classes as $class) {
            ob_start();
            $obj = new $class($session, $form);
            $no_draw = ob_get_clean();
            $this->assertNotEquals(0, strlen($obj->getHeader()));
            $this->assertNotEquals(0, strlen($obj->getFooter()));
        }

        ob_start();
        $obj = new InputCorePage($session, $form);
        $no_draw = ob_get_clean();
        $obj->hide_input(true);
        $this->assertEquals(true, (false !== strpos($obj->getHeader(), 'type="password"')));
        $obj->hide_input(false);
    }

    public function testDrawing()
    {
        $dh = opendir(dirname(__FILE__).'/../../pos/is4c-nf/gui-modules');
        $session = new WrappedStorage();
        $form = new ValueContainer();
        $pages = array();
        while( ($file=readdir($dh)) !== False){
            if ($file[0] == '.') continue;
            if (substr($file,-4) != '.php') continue;
            if ($file == 'ddd.php') continue;
            $class = substr($file,0,strlen($file)-4);
            $pages[$class] = $file;
        }

        foreach($pages as $class => $definition){
            include_once(dirname(__FILE__).'/../../pos/is4c-nf/gui-modules/'.$definition);
            CoreLocal::set('Debug_Redirects', 1, True);

            // get the default output
            ob_start();
            $obj = new $class($session, $form);
            $output = ob_get_clean();
            $output = trim($output);

            // make sure preprocess returns correctly
            ob_start();
            $pre = $obj->preprocess();
            ob_end_clean();
            $this->assertInternalType('boolean',$pre);

            $this->assertInternalType('string',$output);
            if ($pre === True){
                // output is a complete page
                $this->assertEquals('</html>',substr($output,-7));
                $this->assertEquals('<!DOCTYPE html>',substr($output,0,15));
            } else {
                // output is a proper redirect message
                $this->assertEquals('</ul>',substr($output,-5), "Page $class not redirecting correctly");
                $this->assertEquals('Follow redirect', substr($output,0,15), "Page $class not redirecting corretly");
            }

            $obj->unitTest($this);
        }
    }
}
