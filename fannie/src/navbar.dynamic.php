<!-- --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

      5Feb13 AT Build menu via config.php

-->

<?php
global $FANNIE_MENU, $FANNIE_NAV_POSITION;
if ( isset($FANNIE_NAV_POSITION) && $FANNIE_NAV_POSITION == "top" ) {
    echo "<div class='' style='width:900px;z-index:999999;position:relative;'>";
    echo "<ul id='css_menu_root' style='list-style-type:none;'>";
}
else {
    echo "<div class='' style='width:140px;z-index:999999;position:relative;'>";
    echo "<ul id='css_menu_root'>";
}
?>

<?php

function newMenuToOldMenu($menu)
{
    /**
    OLD:
    array(
        label,
        url,
        subheading,
        submenu => array(
                        (label, url)
                        (label, url)
                        (label, url)
                        ...
                   )
    )

    NEW:
    array(
        heading => array(
                    (label, url)
                    (label, url)
                    (label, url)
                    ...
                   )
    )
    */

    if (isset($menu['url'])) {
        return $menu;
    }

    $newmenu = array();
    foreach ($menu as $heading => $menu_items) {
        if ($heading == '__store__' && count($menu_items) == 0) {
            continue;
        }

        $entry = array(
            'label' => $heading,
            'url' => '',
            'subheading' => '',
            'submenu' => array(),
        );
        /**
          Add subheadings & URLs back
        */
        switch ($heading) {
            case 'Item Maintenance':
                $entry['url'] = 'item/ItemEditorPage.php';
                $entry['subheading'] = 'Manage our product DB';
                break;
            case 'Sales Batches':
                $entry['url'] = 'batches/';
                $entry['subheading'] = 'Create automated sales & price changes';
                break;
            case 'Reports':
                $entry['url'] = 'reports/';
                $entry['subheading'] = 'Custom reporting tools';
                break;
            case 'Membership':
                $entry['url'] = 'mem/';
                $entry['subheading'] = 'Manage our member DB';
                break;
            case 'Synchronize':
                $entry['url'] = 'sync/';
                $entry['subheading'] = 'Update cash register';
                break;
            case 'Admin':
                $entry['url'] = 'admin/';
                $entry['subheading'] = 'Administratrive functions, etc.';
                break;
            case '__store__':
                $entry['label'] = 'Custom';
                $entry['subheading'] = 'Store-specific menu';
                break;
        }
        foreach ($menu_items as $label => $url) {
            // non-link in new style menu
            if (preg_match('/^__.+__$/', $url)) {
                continue;
            }
            $entry['submenu'][] = array(
                'label' => $label,
                'url' => $url,
            );
        }
        $newmenu[] = $entry;
    }

    return $newmenu;
}

function render_menu($arr,$depth=0)
{
    global $FANNIE_URL, $FANNIE_NAV_POSITION;
//    $FANNIE_NAV_POSITION = "top";
    foreach($arr as $entry){
        if(strlen($entry['url']) != 0 && substr($entry['url'],0,1) != '/'
           && !strstr($entry['url'],'://')){
            $entry['url'] = $FANNIE_URL.$entry['url'];
        }
        if ($depth == 0) {
            if (isset($FANNIE_NAV_POSITION) && $FANNIE_NAV_POSITION == "top") {
            /* 6Oct13 Woodshed comments, will remove when finished.
            height does not give a uniform height. Does if subhead not below.
            The li contains all the submenus.
            The reachability of the submenu is dependent on the offset from the list item,
             so fixed width important unless the offset can be actual-width-aware.
            */
                echo "<li style='width:140px; height:20px; margin-right:0.8em; float:left; 
                    border-bottom:1px solid #ccc;' class='menu0' title='{$entry["subheading"]}'>";
            //printf('<li style="height:40px; margin-right:0.8em; float:left;" class="menu%d">',$depth);
            //printf('<li style="width:15%%; height:40px; margin-right:0.8em; float:left;" class="menu%d">',$depth);
            } else {
                printf('<li style="width:100%%;" class="menu%d">',$depth);
            }
        } else {
            echo '<li>';
        }
        printf('<a href="%s">%s</a>',$entry['url'],$entry['label']);
        if (isset($entry['submenu']) && is_array($entry['submenu']) && count($entry['submenu']) != 0){
            echo '<div class="menuwrapper">';
            printf('<div class="submenu level%d" style="width:155px;top:-38px;left:135px;">',$depth);
            echo '<ul style="">';
            render_menu($entry['submenu'],$depth+1);
            echo '</ul></div></div>';
        }
        if (isset($FANNIE_NAV_POSITION) && $FANNIE_NAV_POSITION == "top") {
            // Subhead inside list element.
            if (False && isset($entry['subheading']) && $depth == 0){
                printf('<div class="sub">%s</div>',$entry['subheading']);
            }
            echo '</li>';
        } else {
            // Subhead outside list element.
            echo '</li>';
            if (isset($entry['subheading']) && $depth == 0){
                printf('<div class="sub">%s</div>',$entry['subheading']);
            }
        }
    }
}

if (!isset($FANNIE_MENU) || !is_array($FANNIE_MENU))
    include(dirname(__FILE__) . '/defaultmenu.php');
else
    render_menu(newMenuToOldMenu($FANNIE_MENU));
echo "</ul>";
if ( isset($FANNIE_NAV_POSITION) && $FANNIE_NAV_POSITION == "top" )
    echo "<br style='clear:both;'><!-- br / -->";
echo "Click the headings for more/other options.";
?>
</div>

<!--  *Note: This script is required for scripted add on support and IE 6 sub menu functionality.
      *Note: This menu will fully function in all CSS2 browsers with the script removed.-->
