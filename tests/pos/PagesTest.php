<?php
/**
 * @backupGlobals disabled
 */
class PagesTest extends PHPUnit_Framework_TestCase
{

    public function testLib()
    {
        $classes = array('BasicCorePage', 'InputCorePage', 'NoInputCorePage');
        foreach ($classes as $class) {
            ob_start();
            $obj = new $class();
            $no_draw = ob_get_clean();
            $this->assertNotEquals(0, strlen($obj->getHeader()));
            $this->assertNotEquals(0, strlen($obj->getFooter()));
        }

        ob_start();
        $obj = new InputCorePage();
        $no_draw = ob_get_clean();
        $obj->hide_input(true);
        $this->assertEquals(true, (false !== strpos($obj->getHeader(), 'type="password"')));
        $obj->hide_input(false);
    }

    public function testDrawing()
    {
        CoreLocal::set('Debug_Redirects', 1, True);

        $dh = opendir(dirname(__FILE__).'/../../pos/is4c-nf/gui-modules');
        $pages = array();
        while( ($file=readdir($dh)) !== False){
            if ($file[0] == '.') continue;
            if (substr($file,-4) != '.php') continue;
            $class = substr($file,0,strlen($file)-4);
            $pages[$class] = $file;
        }

        foreach($pages as $class => $definition){
            include_once(dirname(__FILE__).'/../../pos/is4c-nf/gui-modules/'.$definition);

            // get the default output
            ob_start();
            $obj = new $class();
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
            }
            else {
                // output is a proper redirect message
                $this->assertEquals('</ul>',substr($output,-5));
                $this->assertEquals('Follow redirect', substr($output,0,15));
            }

            $obj->unitTest($this);
        }
    }
}
