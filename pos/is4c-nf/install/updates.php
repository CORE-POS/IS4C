<?php
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\install\InstallUtilities;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\MiscLib;
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
CoreState::loadParams();
?>
<html>
<head>
<title>Database Updates</title>
<style type="text/css">
body {
    line-height: 1.5em;
}
</style>
<script type="text/javascript" src="../js/<?php echo MiscLib::jqueryFile(); ?>"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2><?php echo _('IT CORE Lane Installation: Database Updates'); ?></h2>
<?php
// apply selected update
if (FormLib::get('mupdate') !== '') {
    $updateClass = FormLib::get('mupdate');
    if (strstr($updateClass, '-')) {
        $updateClass = str_replace('-', '\\', $updateClass);
    }
    echo '<div style="border: solid 1px #999; padding:10px;">';
    echo _('Attempting to update model') . ': "'.$updateClass.'"<br />';
    if (!class_exists($updateClass)) {
        echo _('Error: class not found<br />');
    } elseif(!is_subclass_of($updateClass, 'COREPOS\\pos\\lib\\models\\BasicModel')) {
        echo _('Error: not a valid model<br />');
    } else {
        $updateModel = new $updateClass(null);
        $db_name = InstallUtilities::normalizeDbName($updateModel->preferredDB());
        if ($db_name === false) {
            echo _('Error: requested database unknown');
        } else {
            ob_start();
            $changes = $updateModel->normalize($db_name, COREPOS\pos\lib\models\BasicModel::NORMALIZE_MODE_APPLY, true);
            $details = ob_get_clean();
            if ($changes === false) {
                echo _('An error occurred.');
            } else {
                echo _('Update complete.');
            }
            printf(' <a href="" onclick="$(\'#updateDetails\').toggle();return false;"
                >' . _('Details') . '</a><pre style="display:none;" id="updateDetails">%s</pre>',
                $details);
        }
    }
    echo '</div>';
}

// list available updates
$cmd = new ReflectionClass('COREPOS\pos\lib\models\BasicModel');
$cmd = $cmd->getFileName();
$mods = AutoLoader::listModules('COREPOS\pos\lib\models\BasicModel');
$adds = 0;
$unknowns = 0;
$errors = 0;
echo '<ul>';
foreach ($mods as $class) {
    if ($class == 'ViewModel' || $class == 'COREPOS\\pos\\lib\\models\\ViewModel') {
        // just a helper subclass not an
        // actual structure
        continue;
    }

    $model = new $class(null);

    $db_name = InstallUtilities::normalizeDbName($model->preferredDB());
    if ($db_name === false) {
        echo '<li>Error: Unknown database "'.$model->preferredDB().'" for model '.$class;
        $errors++;
        continue;
    }

    ob_start();
    $changes = $model->normalize($db_name, COREPOS\pos\lib\models\BasicModel::NORMALIZE_MODE_CHECK);
    $details = ob_get_clean();

    if ($changes === false) {
        printf('<li>%s had errors.', $class);
        $errors++;
    } elseif($changes > 0) {
        printf('<li>%s has updates available.', $class);
        $adds += $changes;
    } elseif($changes < 0) {
        printf('<li>%s does not match the schema but cannot be updated.', $class);
        $unknowns += $changes;
    }

    $noslash_class = str_replace('\\', '-', $class);
    $refl = new ReflectionClass($model);
    if ($changes > 0) {
        printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
            >' . _('Details') . '</a><br /><pre style="display:none;" id="mDetails%s">%s</pre><br />'
            . _('To apply changes') . ' <a href="updates.php?mupdate=%s">' . _('Click Here') . '</a>' 
            . _('or run the following command') . ':<br />
            <pre>php %s --update %s %s</pre>
            </li>',
            $noslash_class, $noslash_class, $details, $noslash_class,
            $cmd, $db_name, $refl->getFileName()
            );
    } else if ($changes < 0 || $changes === false) {
        printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
            >' . _('Details') . '</a><br /><pre style="display:none;" id="mDetails%s">%s</pre></li>',
            $noslash_class, $noslash_class, $details
        );
    }
}
echo '</ul>';
echo '<hr />';
echo '<table cellspacing="0" cellpadding="4" border="1">';
echo '<tr><th colspan="2">' . _('Check Complete') . '</td></tr>';
printf('<tr><td>Errors</td><td align="right">%d</td></tr>', $errors);
printf('<tr><td>Updates</td><td align="right">%d</td></tr>', $adds);
printf('<tr><td>Oddities</td><td align="right">%d</td></tr>', $unknowns);
?>
</div> <!--    wrapper -->
</body>
</html>
