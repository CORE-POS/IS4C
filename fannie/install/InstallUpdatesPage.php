<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

//ini_set('display_errors','1');
include('../config.php'); 
include('updates/Update.php');
include('util.php');
include('db.php');
include_once('../classlib2.0/FannieAPI.php');

/**
	@class InstallUpdatesPage
	Class for the Updates install and config options
*/
class InstallUpdatesPage extends InstallPage {

	protected $title = 'Fannie: Database Updates';
	protected $header = 'Fannie: Database Updates';

	public $description = "
	Class for the Database Updates install and config options page.
	";

	// This replaces the __construct() in the parent.
	public function __construct() {

		// To set authentication.
		FanniePage::__construct();

		// Link to a file of CSS by using a function.
		$this->add_css_file("../src/style.css");
		$this->add_css_file("../src/jquery/css/smoothness/jquery-ui-1.8.1.custom.css");
		$this->add_css_file("../src/css/install.css");

		// Link to a file of JS by using a function.
		$this->add_script("../src/jquery/js/jquery.js");
		$this->add_script("../src/jquery/js/jquery-ui-1.8.1.custom.min.js");

	// __construct()
	}

	// If chunks of CSS are going to be added the function has to be
	//  redefined to return them.
	// If this is to override x.css draw_page() needs to load it after the add_css_file
	/**
	  Define any CSS needed
	  @return A CSS string
	function css_content(){
		$css ="";
		return $css;
	//css_content()
	}
	*/

	// If chunks of JS are going to be added the function has to be
	//  redefined to return them.
	/**
	  Define any javascript needed
	  @return A javascript string
	function javascript_content(){
		$js ="";
		return $js;

	}
	*/

	private function normalize_db_name($name){
		global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB;
		if ($name == 'op') return $FANNIE_OP_DB;
		elseif($name == 'trans') return $FANNIE_TRANS_DB;
		elseif($name == 'archive') return $FANNIE_ARCHIVE_DB;
		else return False;
	}

	function body_content(){
		ob_start();
		echo showInstallTabs('Updates');
?>

<h1 class="install"><?php echo $this->header; ?></h1>
<p class="ichunk">Model-based Updates.</p>
<?php
		if (FormLib::get_form_value('mupdate') !== ''){
			$updateClass = FormLib::get_form_value('mupdate');
			echo '<div style="border: solid 1px #999; padding:10px;">';
			echo 'Attempting to update model: "'.$updateClass.'"<br />';
			if (!class_exists($updateClass))
				echo 'Error: class not found<br />';
			elseif(!is_subclass_of($updateClass, 'BasicModel'))
				echo 'Error: not a valid model<br />';	
			else {
				$updateModel = new $updateClass(null);
				$db_name = $this->normalize_db_name($updateModel->preferredDB());
				if ($db_name === False)
					echo 'Error: requested database unknown';
				else {
					ob_start();
					$changes = $updateModel->normalize($db_name, BasicModel::NORMALIZE_MODE_APPLY);
					$details = ob_get_clean();
					if ($changes === False)
						echo 'An error occurred.';
					else
						echo 'Update complete.';
					printf(' <a href="" onclick="$(\'#updateDetails\').toggle();return false;"
						>Details</a><pre style="display:none;" id="updateDetails">%s</pre>',
						$details);
				}
			}
			echo '</div>';
		}

		$obj = new BasicModel(null);
		$models = $obj->getModels();
		$cmd = new ReflectionClass('BasicModel');
		$cmd = $cmd->getFileName();
		echo '<ul>';
		foreach($models as $class){
			$model = new $class(null);
			$db_name = $this->normalize_db_name($model->preferredDB());
			if ($db_name === False) continue;
		
			ob_start();
			$changes = $model->normalize($db_name, BasicModel::NORMALIZE_MODE_CHECK);
			$details = ob_get_clean();

			if ($changes === False){
				printf('<li>%s had errors.', $class);
			}
			elseif($changes > 0){
				printf('<li>%s has updates available.', $class);
			}
			elseif($changes < 0){
				printf('<li>%s does not match the schema but cannot be updated.', $class);
			}

			if ($changes > 0){
				printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
					>Details</a><br /><pre style="display:none;" id="mDetails%s">%s</pre><br />
					To apply changes <a href="InstallUpdatesPage.php?mupdate=%s">Click Here</a>
					or run the following command:<br />
					<pre>php %s --update %s %s</pre>
					</li>',
					$class, $class, $details, $class,
					$cmd, $db_name, $class
					);
			}
			else if ($changes < 0 || $changes === False){
				printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
					>Details</a><br /><pre style="display:none;" id="mDetails%s">%s</pre></li>',
					$class, $class, $details
				);
			}
		}
		echo '</ul>';
?>
<hr />
<p class="ichunk">Click a link for details on the simple Update.</p>
<?php
		if (is_writable('../config.php')){
			echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
		}
		else {
			echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
		}

		echo "<hr />";

		$action = isset($_REQUEST['action']) ? $_REQUEST['action']: 'list';
		$updateID = isset($_REQUEST['u']) ? $_REQUEST['u'] : '';
		switch($action){
			case 'view':
			case 'mark':
			case 'unmark':
				if (empty($updateID)){
					echo 'No update specified!';
					echo '<a href="InstallUpdatesPage.php">Back</a>';
					exit;
				}
				$file_name = "updates/$updateID.php";
				$class_name = "update_$updateID";
				if (!file_exists($file_name)){
					echo 'Update not found!';
					echo '<a href="InstallUpdatesPage.php">Back</a>';
					exit;
				}
				include($file_name);
				if (!class_exists($class_name)){
					echo 'Update is malformed!';
					echo '<a href="InstallUpdatesPage.php">Back</a>';
					exit;
				}
				$obj = new $class_name();
				echo $obj->HtmlInfo();
				if ($action=='mark')
					$obj->SetStatus(True);
				if ($action=='unmark')
					$obj->SetStatus(False);
				if (!$obj->CheckStatus()){
					printf('<a href="InstallUpdatesPage.php?action=apply&u=%s">Apply Update</a><br />',$updateID);
					printf('<a href="InstallUpdatesPage.php?action=mark&u=%s">Mark Update Complete</a><br />',$updateID);
				} else {
					printf('<a href="InstallUpdatesPage.php?action=unmark&u=%s" title="This does not un-do the Update. Not all Updates can be re-run.">Un-mark Update (so it can be run again)</a><br />',$updateID);
				}
				echo '<a href="InstallUpdatesPage.php">Back to List of Updates</a>';
				echo "<hr />";
				echo "<b>Query details</b>:<br />";
				echo $obj->HtmlQueries();
					
				break;
			case 'apply':
				if (empty($updateID)){
					echo 'No update specified!';
					echo '<a href="InstallUpdatesPage.php">Back</a>';
					exit;
				}
				$file_name = "updates/$updateID.php";
				$class_name = "update_$updateID";
				if (!file_exists($file_name)){
					echo 'Update not found!';
					echo '<a href="InstallUpdatesPage.php">Back</a>';
					exit;
				}
				include($file_name);
				if (!class_exists($class_name)){
					echo 'Update is malformed!';
					echo '<a href="InstallUpdatesPage.php">Back</a>';
					exit;
				}
				$obj = new $class_name();
				echo $obj->ApplyUpdates();
				echo '<hr />';
				echo "If the queries all succeeded, this update is automatically marked complete.
		If not, you can make corrections in your database and refresh this page to try again or just make
		alterations directly";
				echo '<br /><br />';	
				if ( !$obj->CheckStatus() ) {
					printf('<a href="InstallUpdatesPage.php?action=mark&u=%s">Manually Mark Update $updateID Complete</a><br />',$updateID);
				} else {
					echo "Update $updateID has been Marked Complete.<br />";
					printf('<a href="InstallUpdatesPage.php?action=unmark&u=%s" title="This does not un-do the Update. Not all Updates can be re-run.">Un-mark Update (so it can be run again)</a><br />',$updateID);
				}
				echo '<a href="InstallUpdatesPage.php">Back to List of Updates</a>';
				break;
			case 'list':
				// find update files
				$dh = opendir('updates');
				$updates = array();
				while ( ($file=readdir($dh)) !== False ){
					if ($file[0] == ".") continue;
					if ($file == "Update.php") continue;
					if (!is_file('updates/'.$file)) continue;
					if (substr($file,-4) != ".php") continue;
					$updates[] = $file;
				}
				sort($updates);

				// check for new vs. finished and put in separate arrays.
				$new = array();
				$done = array();
				foreach($updates as $u){
					$key = substr($u,0,strlen($u)-4);
					include('updates/'.$u);
					if (!class_exists('update_'.$key)) continue;
					$class = "update_$key";
					$obj = new $class();
					if ($obj->CheckStatus())
						$done[] = $key;
					else
						$new[] = $key;
				}

				// display
				echo '<h4 class="install">Available Updates</h4>';
				echo '<ul>';
				foreach($new as $key){
					printf('<li><a href="InstallUpdatesPage.php?action=view&u=%s">%s</a></li>',
						$key,$key);
				}
				echo '</ul>';
				echo '<h4 class="install">Applied Updates</h4>';
				echo '<ul>';
				foreach($done as $key){
					printf('<li><a href="InstallUpdatesPage.php?action=view&u=%s">%s</a></li>',
						$key,$key);
				}
				echo '</ul>';
				break;
			default:
				echo 'Action unknown!';
				echo '<a href="InstallUpdatesPage.php">Back</a>';
				break;
		}


		echo "<hr />";

		return ob_get_clean();

	// body_content
	}

// InstallUpdatesPage
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])){
	$obj = new InstallUpdatesPage();
	$obj->draw_page();
}
?>
