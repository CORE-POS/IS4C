<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
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
include_once('../cron/tasks/GiterateTask.php');

/**
    @class InstallUpdatesPage
    Class for the Updates install and config options
*/
class InstallUpdatesPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie: Updates';
    protected $header = 'Fannie: Updates';

    public $description = "
    Class for the Updates install and config options page.
    ";
    public $themed = true;

    // This replaces the __construct() in the parent.
    public function __construct() {

        // To set authentication.
        FanniePage::__construct();

        // Link to a file of CSS by using a function.
        $this->add_css_file("../src/style.css");
        $this->add_css_file("../src/javascript/jquery-ui.css");
        $this->add_css_file("../src/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("../src/javascript/jquery.js");
        $this->add_script("../src/javascript/jquery-ui.js");

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

<h1 class="install">
    <?php 
    if (!$this->themed) {
        echo "<h1 class='install'>{$this->header}</h1>";
    }
    ?>
</h1>
<p class="ichunk">Database Updates.</p>
<?php
        if (FormLib::get_form_value('mupdate') !== ''){
            $updateClass = FormLib::get_form_value('mupdate');
            echo '<div class="well">';
            echo 'Attempting to update model: "'.$updateClass.'"<br />';
            if (!class_exists($updateClass))
                echo '<div class="alert alert-danger">Error: class not found</div>';
            elseif(!is_subclass_of($updateClass, 'BasicModel'))
                echo '<div class="alert alert-danger">Error: not a valid model</div>';
            else {
                $updateModel = new $updateClass(null);
                $db_name = $this->normalize_db_name($updateModel->preferredDB());
                if ($db_name === False)
                    echo '<div class="alert alert-danger">Error: requested database unknown</div>';
                else {
                    ob_start();
                    $changes = $updateModel->normalize($db_name, BasicModel::NORMALIZE_MODE_APPLY);
                    $details = ob_get_clean();
                    if ($changes === False)
                        echo '<div class="alert alert-danger">An error occured applying the update</div>';
                    else
                        echo '<div class="alert alert-success">Update complete</div>';
                    printf(' <a href="" onclick="$(\'#updateDetails\').toggle();return false;"
                        >Details</a><pre class="collapse" id="updateDetails">%s</pre>',
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
                $reflector = new ReflectionClass($class);
                $model_file = $reflector->getFileName();
                printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
                    >Details</a><br /><pre class="collapse" id="mDetails%s">%s</pre><br />
                    To apply changes <a href="InstallUpdatesPage.php?mupdate=%s">Click Here</a>
                    or run the following command:<br />
                    <pre>php %s --update %s %s</pre>
                    </li>',
                    $class, $class, $details, $class,
                    $cmd, $db_name, $model_file
                    );
            }
            else if ($changes < 0 || $changes === False){
                printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
                    >Details</a><br /><pre class="collapse" id="mDetails%s">%s</pre></li>',
                    $class, $class, $details
                );
            }
        }
        echo '</ul>';
?>
<hr />
<p class="ichunk">CORE Updates.</p>
<em>This is new; consider it alpha-y. Commit any changes before running an update.</em><br />
<?php
        $version_info = \COREPOS\Fannie\API\data\DataCache::check('CoreReleases');
        if ($version_info === false) {
            ini_set('user_agent', 'CORE-POS');
            $json = file_get_contents('https://api.github.com/repos/CORE-POS/IS4C/tags');
            if ($json === false && function_exists('curl_init')) {
                $ch = curl_init('https://api.github.com/repos/CORE-POS/IS4C/tags');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURL_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_USERAGENT, 'CORE-POS');
                $json = curl_exec($ch);
                curl_close($ch);
            }

            if ($json === false) {
                echo '<div class="alert alert-danger">Error downloading release information</div>';
            } else {
                $decoded = json_decode($json, true);
                if ($decoded === null) {
                    echo '<div class="alert alert-danger">Downloaded release information is invalid</div>';
                    var_dump($json);
                } else {
                    $version_info = $json;
                    \COREPOS\Fannie\API\data\DataCache::freshen($version_info, 'day', 'CoreReleases');
                }
            }
        }
        $version_info = json_decode($version_info, true);
        $tags = array();
        foreach ($version_info as $release) {
            $tags[] = $release['name'];
        }
        usort($tags, array('InstallUpdatesPage', 'versionSort'));
        $my_version = trim(file_get_contents(dirname(__FILE__) . '/../../VERSION'));
        if ($tags[count($tags)-1] == $my_version) {
            echo '<div class="alert alert-success">Up to date</div>';
        } elseif (!in_array($my_version, $tags)) {
            echo '<div class="alert alert-warning">Current version <strong>' . $my_version . '</strong> not recognized</div>';
        } else {
            echo '<div class="alert alert-info">
                Current version: <strong>' . $my_version . '</strong><br />
                Newest version available: <strong>' . $tags[count($tags)-1] . '</strong>
                </div>';
            echo '<h3>To get the latest version</h3>';
            echo '<i>Make note of the big string of letters and numbers produced
                by the "git log" command. If you want to undo the update, that will be handy</i><br />';
            echo '<p><code>';
            $dir = realpath(dirname(__FILE__) . '/../../');
            echo 'cd "' . $dir . '"<br />';
            echo 'git log -n1 --pretty=oneline<br />';
            echo 'git fetch upstream<br />';
            echo 'git merge ' . $tags[count($tags)-1] . '<br />';
            echo '</code></p>';
            echo '<h3>Troubleshooting</h3>';
            echo '<p>Error message: <i>fatal: \'upstream\' does not appear to be a repository</i><br />';
            echo 'Solution: add the repository and re-run the update commands above<br />';
            echo '<code>git remote add upstream https://github.com/CORE-POS/IS4C</code>';
            echo '</p>';
            echo '<p>Error message: <i>Automatic merge failed; fix conflicts and then commit the result.</i><br />';
            echo 'Unfortunately this means the update cannot be applied automatically. If you are a developer
                you can of course fix the conflicts. If you just need to undo the update attempt and get back
                to a working state, first try this:<br />
                <code>git reset --merge</code><br />
                If problems persist (or you have an old version of git that doesn\'t support that command) use:<br />
                <code>git reset --hard</code>
                </p>';
            echo '<p>Undoing the update<br />
                If you noted the big string of letters and numbers from "git log", you can go back to that
                exact point. Replace PREVIOUS with the big string.<br />
                <code>git reset --merge PREVIOUS</code><br />
                If not, this should get back to the version you were running before but may not be quite
                identical.<br />
                <code>git reset --merge ' . $my_version . '</code>
                </p>';
        }

        return ob_get_clean();

    // body_content
    }

    private static function versionSort($a, $b) 
    {
        $a_valid = preg_match('/^(.*?)(\d+)\D(\d+)\D(\d+)\D/', $a, $a_parts);
        $b_valid = preg_match('/^(.*?)(\d+)\D(\d+)\D(\d+)\D/', $b, $b_parts);
        if (!$a_valid && !$b_valid) {
            return 0;
        } elseif ($a_valid && !$b_valid) {
            return 1;
        } elseif (!$a_valid && $b_valid) {
            return -1;
        }

        if ($a_parts[2] > $b_parts[2]) { // major 
            return 1;
        } elseif ($a_parts[2] < $b_parts[2]) { // major 
            return -1;
        } elseif ($a_parts[3] > $b_parts[3]) { // minor 
            return 1;
        } elseif ($a_parts[3] < $b_parts[3]) { // minor
            return -1;
        } elseif ($a_parts[4] > $b_parts[4]) { // revision
            return 1;
        } elseif ($a_parts[4] < $b_parts[4]) { // revision
            return -1;
        } elseif (empty($a_parts[1]) && !empty($b_parts[1])) { // has prefix
            return 1;
        } elseif (!empty($a_parts[1]) && empty($b_parts[1])) { // has prefix
            return -1;
        } else {
            return 0;
        }
    }

// InstallUpdatesPage
}

FannieDispatch::conditionalExec(false);

?>
