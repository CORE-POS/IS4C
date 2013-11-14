<?php
/**
 * @backupGlobals disabled
 */
class PagesTest extends PHPUnit_Framework_TestCase
{

	public function testDrawing(){
		global $CORE_LOCAL;
		$CORE_LOCAL->set('Debug_Redirects', 1, True);

		$dh = opendir(dirname(__FILE__).'/../gui-modules');
		$pages = array();
		while( ($file=readdir($dh)) !== False){
			if ($file[0] == '.') continue;
			if (substr($file,-4) != '.php') continue;
			$class = substr($file,0,strlen($file)-4);
			$pages[$class] = $file;
		}

		foreach($pages as $class => $definition){
			include_once(dirname(__FILE__).'/../gui-modules/'.$definition);

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
		}
	}
}
