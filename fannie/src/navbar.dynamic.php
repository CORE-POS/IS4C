<!-- --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

      5Feb13 AT Build menu via config.php
     18Jan13 EL Add Synchronize > ProductUser. Not WEFC_Toronto-specific.
      3Jan13 EL Add Sales > General Costs. Not WEFC_Toronto-specific.
     10Oct12 EL Add memberCards to Sync flyout
.               Add Section for WEFC Toronto utilities.
      4Sep12 EL Access to the item/import utilities:
.               Add Import Products item to submenu level0
.               Add Products item to submenu level1
.               Add Import Departments item to submenu level0
.               Add Departments and Sub Departments items to submenu level1
.            EL Add "Upload a File" item to submenu level1, Prods and Depts Import.
     10Aug12 Eric Lee Add Manage Batch Types to Sales Batches submenu0
.               Add "and Tool" to "Product List"
x               Add Product List and Tool to Item Maint submenu0
.               Add submenu1 for Products to Item Maint submenu0
.               Add submenu1 for Departments to Item Maint submenu0
-->

<link rel="stylesheet" href="<?php echo $path; ?>src/style.css" type="text/css" media="screen" title="no title" charset="utf-8">

<div class="" style="width:140px;z-index:999999;position:relative;">
<ul id="css_menu_root">

<?php
if (!isset($FANNIE_MENU) || !is_array($FANNIE_MENU))
	include($path.'src/defaultmenu.php');
function render_menu($arr,$depth=0){
	global $path;
	foreach($arr as $entry){
		if(strlen($entry['url']) != 0 && substr($entry['url'],0,1) != '/'
		   && !strstr($entry['url'],'://')){
			$entry['url'] = $path.$entry['url'];
		}
		if ($depth == 0)
			printf('<li style="width:100%%;" class="menu%d">',$depth);
		else
			echo '<li>';
		printf('<a href="%s">%s</a>',$entry['url'],$entry['label']);
		if (isset($entry['submenu']) && is_array($entry['submenu']) && count($entry['submenu']) != 0){
			echo '<div class="menuwrapper">';
			printf('<div class="submenu level%d" style="width:155px;top:-28px;left:135px;">',$depth);
			echo '<ul style="">';
			render_menu($entry['submenu'],$depth+1);
			echo '</ul></div></div>';
		}
		echo '</li>';
		if (isset($entry['subheading']) && $depth == 0){
			printf('<div class="sub">%s</div>',$entry['subheading']);
		}
	}
}
render_menu($FANNIE_MENU);
?>
</ul>
<!-- 10Aug12 EL Added -->
Click the headings for more/other options.
</div>

<!--  *Note: This script is required for scripted add on support and IE 6 sub menu functionality.
      *Note: This menu will fully function in all CSS2 browsers with the script removed.-->
