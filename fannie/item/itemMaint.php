<?php
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
/* compatibility w/ old links */
if (isset($_REQUEST['upc']))
    header('Location: ItemEditorPage.php?searchupc='.$_REQUEST['upc']);
else
    header('Location: ItemEditorPage.php');

