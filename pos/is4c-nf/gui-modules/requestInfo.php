<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* this module is intended for re-use. Just set 
 * Pass the name of a class with the
 * static properties: 
 *  - requestInfoHeader (upper message to display)
 *  - requestInfoMsg (lower message to display)
 * and static method:
 *  - requestInfoCallback(string $info)
 *
 * The callback receives the info entered by the 
 * cashier. To reject the entry as invalid, return
 * False. Otherwise return a URL to redirect to that
 * page or True to go to pos2.php.
 */

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class requestInfo extends NoInputPage {

	private $request_header = '';
	private $request_msg = '';

	function preprocess(){
		// get calling class (required)
		$class = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
		$pos_home = MiscLib::base_url().'gui-modules/pos2.php';
		if ($class === '' || !class_exists($class)){
			$this->change_page($pos_home);
			return False;
		}
		// make sure calling class implements required
		// method and properties
		try {
			$method = new ReflectionMethod($class, 'requestInfoCallback');
			if (!$method->isStatic() || !$method->isPublic())
				throw new Exception('bad method requestInfoCallback');
			$property = new ReflectionProperty($class, 'requestInfoMsg');
			if (!$property->isStatic() || !$property->isPublic())
				throw new Exception('bad property requestInfoMsg');
			$property = new ReflectionProperty($class, 'requestInfoHeader');
			if (!$property->isStatic() || !$property->isPublic())
				throw new Exception('bad property requestInfoHeader');
		}
		catch (Exception $e){
			$this->change_page($pos_home);
			return False;
		}

		$this->request_header = $class::$requestInfoHeader;
		$this->request_msg = $class::$requestInfoMsg;

		// info was submitted
		if (isset($_REQUEST['input'])){
			$reginput = strtoupper($_REQUEST['input']);
			if ($reginput == 'CL'){
				// clear; go home
				$this->change_page($pos_home);
				return False;
			}
			elseif ($reginput === ''){
				// blank. stay at prompt
				return True;
			}
			else {
				// give info to callback function
				$result = $class::requestInfoCallback($reginput);
				if ($result === True){
					// accepted. go home
					$this->change_page($pos_home);
					return False;
				}
				elseif ($result === False){
					// input rejected. try again
					$this->result_header = 'invalid entry';
					return True;
				}
				else {
					// callback wants to navigate to
					// another page
					$this->change_page($result);
					return False;
				}
			}
		}
		return True;
	}

	function body_content(){
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay">
		<span class="larger">
		<?php echo $this->request_header; ?>
		</span>
		<form name="form" method="post" autocomplete="off" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="text" id="reginput" name='input' tabindex="0" onblur="$('#input').focus()" />
		<input type="hidden" name="class" value="<?php echo $_REQUEST['class']; ?>" />
		</form>
		<p>
		<?php echo $this->request_msg; ?>
		</p>
		</div>
		</div>

		<?php
		$this->add_onload_command("\$('#reginput').focus();");
	} // END true_body() FUNCTION

}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new requestInfo();

?>
