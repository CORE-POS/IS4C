<?php
require('prodAllLanes.php');
require('barcode.php');

include('../db.php');

function itemParse($upc,$dupe='no',$description='',$prefix=false)
{
    global $sql;
    /* why am I only checking for being logged in here
     * instead of a specific permission?
     * 
     * The enter and update item pages do that check
     * For new items, permission could be dependent on
     * which subdepartment is selected. For changing an item,
     * changing subdepartment introduces odd scenarios,
     * switching in and out of valid permission. Easier to just
     * check the final changes.
     */
    $logged_in = checkLogin();

    $p_columns = '
        p.upc,
        p.description,
        p.normal_price,
        p.pricemethod,
        p.quantity,
        p.groupprice,
        p.special_price,
        p.end_date,
        p.department,
        p.tax,
        p.foodstamp,
        p.scale,
        p.qttyEnforced,
        p.discount,
        p.modified,
        p.local
    '; // last comma omitted on purpose

    $savedUPC="";
    $queryItem = "";
    $args = array();
    $numType = (isset($_REQUEST['ntype'])?$_REQUEST['ntype']:'UPC');
    if (is_numeric($upc)){
    switch($numType){
    case 'UPC':
        $upc = str_pad($upc,13,0,STR_PAD_LEFT);
        $savedUPC = $upc;
        $queryItem = "SELECT {$p_columns},x.distributor,x.manufacturer,
                u.brand,u.description as udesc,u.sizing,u.photo,
                u.long_text,u.enableOnline,e.expires
                FROM products as p left join 
                prodExtra as x on p.upc=x.upc 
                LEFT JOIN productUser as u
                ON p.upc=u.upc LEFT JOIN productExpires
                AS e ON p.upc=e.upc
                WHERE p.upc = ? or x.upc = ?";
                $args = array($upc, $upc);
        break;
    case 'SKU':
        $queryItem = "SELECT {$p_columns},x.distributor,x.manufacturer,
                u.brand,u.description as udesc,u.sizing,u.photo,
                u.long_text,u.enableOnline,e.expires
                FROM products as p inner join 
                vendorItems as v ON p.upc=v.upc 
                left join prodExtra as x on p.upc=x.upc 
                LEFT JOIN productUser as u
                ON p.upc=u.upc LEFT JOIN productExpires
                AS e ON p.upc=e.upc
                WHERE v.sku=?";
                $args = array($upc);
        break;
    case 'Brand Prefix':
        $queryItem = "SELECT {$p_columns},x.distributor,x.manufacturer,
                u.brand,u.description as udesc,u.sizing,u.photo,
                u.long_text,u.enableOnline,e.expires
            FROM products as p 
            left join prodExtra as x on p.upc=x.upc 
            LEFT JOIN productUser as u
            ON p.upc=u.upc LEFT JOIN productExpires
            AS e ON p.upc=e.upc
            WHERE p.upc like ? order by p.upc";
            $args = array('%'.$upc.'%');
        break;
    }
    }
    else{
        $queryItem = "SELECT {$p_columns},x.distributor,x.manufacturer,
            u.brand,u.description as udesc,u.sizing,u.photo,
                u.long_text,u.enableOnline,e.expires
            FROM products as p left join 
            prodExtra as x on p.upc=x.upc 
            LEFT JOIN productUser as u
            ON p.upc=u.upc LEFT JOIN productExpires
            AS e ON p.upc=e.upc
            WHERE p.description LIKE ? OR 
            u.description LIKE ? ORDER BY p.description";
        $args = array('%'.$upc.'%', '%'.$upc.'%');
    }
    //echo $queryItem;

    echo "<script type=\"text/javascript\">";
    echo "function shelftag(u){";
    echo "testwindow= window.open (\"../../item/addShelfTag.php?upc=\"+u, \"New Shelftag\",\"location=0,status=1,scrollbars=1,width=300,height=220\");";
    echo "testwindow.moveTo(50,50);";
    echo "}";
    echo "</script>";

    $barcodeUPC = ltrim($upc,"0");
    echo "<script language=JavaScript>";
    echo "function popup(){";
    echo "testwindow= window.open (\"addLikeCode.php\", \"Add Like Code\",\"location=0,status=1,scrollbars=1,width=200,height=200\");";
    echo "testwindow.moveTo(50,50);";
    echo "}";
    echo "</script>";
    echo "<script language=JavaScript>";
    echo "function newTagCheck(){";
    echo "if (document.getElementById('newTag').checked)";
    //echo "shelftag();";
    echo "}";
    echo "</script>";

    $num = 0;
    $prepItem = $sql->prepare($queryItem);
    $resultItem = $sql->execute($prepItem, $args);
    $num = $sql->num_rows($resultItem);
   
    $likeCodeQ = $sql->prepare("SELECT u.*,l.likeCodeDesc FROM upcLike as u, likeCodes as l 
        WHERE u.likeCode = l.likeCode and u.upc = ?");
    $likeCodeR = $sql->execute($likeCodeQ, array($upc));
    $likeCodeRow= $sql->fetch_row($likeCodeR);
    $likeCodeNum = $sql->num_rows($likeCodeR);

    $listCodeQ = "SELECT * from likeCodes";
    $listCodeR = $sql->query($listCodeQ);
    $listCodeRow = $sql->fetch_row($likeCodeR);

    if($num == 0)
    {
        noItem();
        echo "<head><title>Enter New Item</title></head>";
        echo "<BODY onLoad='putFocus(0,1);'>";
    printMods($savedUPC);
        echo "Item not found, would you like to enter it?";
                        echo "<form action=enterTestItem.php method=post onsubmit=\"newTagCheck();\">";
        echo "<table>";
           echo "<tr><td align=right><b>UPC</b></td><td><font color='red'></font>
            <input type=text value=$upc name=upc maxlength=13></td>";
           echo "</tr><tr><td><b>Description</b></td><td>
            <input type=text size=35 name=descript maxlength=30></td>";
                echo "<td><b>Price</b></td><td>$<input type=text name=price></td></tr>";
                echo "<tr><td><b>Manufacturer</b></td><td><input type=text size=35 value=\"\" name=manufacturer /></td>";
                   echo "<td><b>Distributor</b></td><td>&nbsp;<input type=text value=\"\" name=distributor /></td></tr>";
                echo "</table>";
        echo "<table border=0><tr>";
                echo "<th>Dept<th>Tax<th>FS<th>Scale<th>QtyFrc<th>NoDisc<th>Local<th>Like Code<th>Shelf Tag</b>";
                echo "</tr>";
                echo "<tr>";
                        $query2 = "SELECT * FROM departments as d,
                MasterSuperDepts AS s WHERE s.dept_ID=d.dept_no AND dept_no NOT IN (60,225)
                ORDER BY superID, dept_no";
                echo "<td>";
            $upc_split = substr($upc,0,7);
            $guessQ = $sql->prepare("select department from products where upc like ?
                   group by department
                   order by count(*) desc");
            $guessR = $sql->execute($guessQ, array($upc_split.'%'));
            $guess = 60;
            if ($sql->num_rows($guessR) > 0)
                $guessW = $sql->fetchRow($guessR);
                $guess = $guessW['department'];
                echo '<select name="dept">';
                $result2 = $sql->query($query2);
                while($row2 = $sql->fetch_row($result2)) {
                    printf('<option %s value="%d">%d %s</option>',
                            ($row2['dept_no'] == $guess ? 'selected' : ''),
                            $row2['dept_no'], $row2['dept_no'], $row2['dept_name']);
                }
                echo '</select>';
                echo " </td>";
                echo "<td align=right>Reg";
                echo "<input type=radio name=tax value=1><br>";
                echo "Deli<input type=radio name=tax value=2><br>";
                echo "No Tax<input type=radio name=tax value=0";
                echo "></td><td align=center><input type=checkbox value=1 name=FS";
                echo "></td><td align=center><input type=checkbox value=1 name=Scale";
                echo "></td><td align=center><input type=checkbox value=1 name=ForcQty";
                echo "></td><td align=center><input type=checkbox value=1 name=NoDisc";
                echo "></td><td align=center><select name=local><option value=0>No</option>
        <option value=1>SC</option><option value=2>300mi</option></select>";
                echo "<input type=hidden value=1 name=inUse";
                echo "><td align=center>";
                //echo "<input type=text align=right size=4 name=likeCode>";
        echo "<select name=likeCode style=\"{width: 175px;}\">";
                echo "<option value=\"\">(none)</option>";
                $likelistQ = "select * from likeCodes order by likecode";
                $likelistR = $sql->query($likelistQ);
                while ($llRow = $sql->fetchRow($likelistR)){
                  echo "<option value={$llRow[0]}";
                  if (isset($likecode) && $llRow[0] == $likecode){
                    echo " selected";
                  }
                  echo ">{$llRow[0]} {$llRow[1]}</option>";
                }
                echo "</select>";
                echo "</td><td align=center><input type=checkbox value=1 id=newTag name=shelftag></td></tr>";
               if ($logged_in){     
                    echo "<tr><td><input type='submit' name='submit' value='submit'>";
               }
               else {
                   echo "<tr><td>Please <a href=/auth/ui/loginform.php?redirect=/queries/productTest.php?upc=$upc>";
                    echo "login</a> to add items";
               }
               
        echo "</td></tr> ";
                echo "</tr></table>";

    }elseif($num > 1){
        moreItems($upc);
    $upcs = array();
    $descriptions = array();
        for($i=0;$i < $num;$i++){
            $rowItem= $sql->fetchRow($resultItem);
        $upcs[$i] = $rowItem['upc'];
        $descriptions[$i] = $rowItem['description'];
        $modified[$i] = $rowItem['modified'];
        //echo "<a href='productTest.php?upc=$upc'>" . $upc . " </a>- " . $rowItem['description'] . "<br>";
        }
    for ($i=0;$i<$num;$i++){
      $dupe = false;
      for ($j=0; $j<$num; $j++){
        if ($i != $j and $upcs[$i] == $upcs[$j]){
          $enc = base64_encode($descriptions[$i]);
          echo "<a href=productTest.php?upc=$upcs[$i]&duplicate=yes&description={$enc}>{$upcs[$i]}</a>-{$descriptions[$i]} - <b>DUPLICATE</b> - ";
          echo "<a href=javascript:delete_popup(\"$upcs[$i]\",\"$enc\")><img src=trash.png border=0/></a><br />";
          $j = $num;
          $dupe = true;
        }
      }
      if (!$dupe){
        echo "<a href=productTest.php?upc={$upcs[$i]}>{$upcs[$i]}</a>-{$descriptions[$i]}<br />";
      }
    }
    }else{
        oneItem($upc);
    $rowItem = $sql->fetchRow($resultItem);
    $upc = $rowItem['upc'];

    
    $currentDepartment = $rowItem['department'];
    $prev = $next = 0;
    $modified = $rowItem['modified'];
    deptPrevNext($currentDepartment,$upc,$prev,$next);
    $modDate = $modified;    

    $likecode = '';
    if(!empty($likeCodeRow[1]))
        $likecode = $likeCodeRow[1];

            echo "<head><title>Update Item</title></head>";
        echo "<BODY onLoad='putFocus(0,2);'>";
    printMods($savedUPC);
        echo "<form action=updateItemTest.php method=post>";
        echo "<table>";
        echo "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$rowItem['upc']."</font><input type=hidden value='{$rowItem['upc']}' name=upc>";
        if ($prev != -1)
                echo    "&nbsp;&nbsp;<a href=productTest.php?upc=$prev>Previous</a>";
            if ($next != -1)
                echo "&nbsp;&nbsp;<a href=productTest.php?upc=$next>Next</a>";
        echo "</td>";
        echo "</tr><tr><td><b>Description</b></td><td><input type=text size=35 maxlength=30 value=\"{$rowItem['description']}\" name=descript></td>";
        echo "<td><b>Price</b></td><td>$<input type=text value='{$rowItem['normal_price']}' name=price></td></tr>";
    echo "<tr><td colspan=2 align=right><b>Enable Volume Price</b> <input type=checkbox name=doVolume ";
    echo ($rowItem['pricemethod'] != 0 ? "checked" : "")." /></td><td colspan=2>";
    echo "<input type=hidden name=pricemethod value=\"{$rowItem['pricemethod']}\">";
    echo "<input type=text size=4 name=vol_qtty value=".($rowItem['quantity'] != 0 ? $rowItem['quantity'] : "\"\"")." />";
    echo " for $<input type=text size=4 name=vol_price value=".($rowItem['groupprice'] != 0 ? $rowItem['groupprice'] : "\"\"")." /></td></tr>";
        echo "<tr><td><b>Manufacturer</b></td><td><input type=text size=35 value=\"{$rowItem['manufacturer']}\" name=manufacturer /></td>";
        echo "<td><b>Distributor</b></td><td>&nbsp;<input type=text value=\"{$rowItem['distributor']}\" name=distributor /></td></tr>";
        echo "<a href=/git/fannie/reports/PriceHistory/?upc={$rowItem['upc']} target=blank>Click for Price History</a>";
        if($rowItem['special_price'] <> 0){
           echo "<tr><td><font size=+1 color=green><b>Sale Price:</b></font></td><td><font size=+1 color=green>{$rowItem['special_price']}</font>";
           echo "<td colspan=2><font size=+1 color=green>End Date: {$rowItem['end_date']}</font></td><tr>";
       $findBatchQ = $sql->prepare("select batchName from batches as b, batchList as l
              where b.batchID = l.batchID and l.upc like ?
              and ".$sql->curdate()." BETWEEN b.startDate AND b.endDate");
       $findBatchR = $sql->execute($findBatchQ, array($upc));
       $batchName = ($sql->num_rows($findBatchR) == 0) ? "Unknown" :array_pop($sql->fetchRow($findBatchR));
       if ($batchName == "Unknown" && $likecode != ""){
        $findBatchQ = $sql->prepare("select batchName from batches as b, batchList as l
                where b.batchID=l.batchID and l.upc = ?
                  and ".$sql->curdate()." BETWEEN b.startDate AND b.endDate");
        $findBatchR = $sql->execute($findBatchQ, array('LC'.$likecode));
        $batchName = ($sql->num_rows($findBatchR) == 0) ? "Unknown" :array_pop($sql->fetchRow($findBatchR));
       }
       echo "<tr><td colspan=4><b>Batch: $batchName</b> ";
       if (validateUserQuiet('pricechange') || substr($upc,0,3) == "002" ){
           echo "(<a href=unsale.php?upc=$upc><font color=red>Take this item off sale now</font></a>)</td>";
       }
        }
    echo "</table>";
        echo "<table border=0><tr>";
                echo "<th>Dept<th>Tax<th>FS<th>Scale<th>QtyFrc<th>NoDisc<th>Local<th>Like Code<th>&nbsp;</b>";
                echo "</tr>";
                echo "<tr align=top>";
                        //$dept=$row1[3];
                        $query2 = "SELECT * FROM departments as d,
                MasterSuperDepts AS s WHERE s.dept_ID=d.dept_no AND dept_no NOT IN (60,225)
                ORDER BY superID, dept_no";
                echo "<td>";
                        $query3 = $sql->prepare("SELECT dept_no,superID FROM departments as d
                LEFT JOIN MasterSuperDepts AS s ON d.dept_no=s.dept_ID
                WHERE dept_no = ?");
                        $result3 = $sql->execute($query3, $rowItem['department']);
                        $row3 = $sql->fetchRow($result3);
                echo '<select name="dept">';
                $result2 = $sql->query($query2);
                while($row2 = $sql->fetch_row($result2)) {
                    printf('<option %s value="%d">%d %s</option>',
                            ($row2['dept_no'] == $rowItem['department'] ? 'selected' : ''),
                            $row2['dept_no'], $row2['dept_no'], $row2['dept_name']);
                }
                echo '</select>';
                echo " </td>";
                echo "<td align=right>Reg ";
                echo "<input type=radio name=tax value=1";
                        if($rowItem['tax']==1){
                                echo " checked";
                        }
                echo "><br>Deli <input type=radio name=tax value=2";
                        if($rowItem['tax']==2){
                                echo " checked";
                        }
                echo "><br>NoTax <input type=radio name=tax value=0";
                        if($rowItem['tax']==0){
                                echo " checked";
                        }
                echo "></td><td align=center><input type=checkbox value=1 name=FS";
                        if($rowItem['foodstamp']==1){
                                echo " checked";
                        }
                echo "></td><td align=center><input type=checkbox value=1 name=Scale";
                        if($rowItem['scale']==1){
                                echo " checked";
                        }
                echo "></td><td align=center><input type=checkbox value=1 name=QtyFrc";
                        if($rowItem['qttyEnforced']==1){
                                echo " checked";
                        }
                echo "></td><td align=center><input type=checkbox value=0 name=NoDisc";
                        if($rowItem['discount']==0){
                                echo " checked";
                        }
                echo ">" . "</td><input type=hidden value=1 name=inUse";
                echo "></td><td align=center><select name=local>";
            printf("<option value=0 %s>No</option>",($rowItem['local']==0?'selected':''));
            printf("<option value=1 %s>SC</option>",($rowItem['local']==1?'selected':''));
            printf("<option value=2 %s>300mi</option>",($rowItem['local']==2?'selected':''));
            echo "</select><td align=center>";
            //echo "<input type=text align=right size=4 value='$likecode' name=likeCode>";
        echo "<select name=likeCode style=\"{width: 175px;}\">";
        echo "<option value=-1>(none)</option>";
        $likelistQ = "select * from likeCodes order by likecode";
        $likelistR = $sql->query($likelistQ);
        while ($llRow = $sql->fetchRow($likelistR)){
          echo "<option value={$llRow[0]}";
          if ($llRow[0] == $likecode){
            echo " selected";
          }
          echo ">{$llRow[0]} {$llRow[1]}</option>";
        }
        echo "</select>";
                echo "</td><td>";
        echo "<a href=javascript:popup()>Add like code</a>";
                echo "</td>";
            echo "<a href=javascript:shelftag('$upc')>New Shelf Tag</a>";
                echo "</tr>";
                echo "</tr>";
            echo "<tr><td align=right><font size=-1 color=purple><i><b>Last Modified: </b></i></font></td>";
            echo "<td colspan=3><font size=-1 color=purple><i>$modDate</i></td>";
        echo"<td colspan=5><a href=/git/fannie/reports/RecentSales/index.php?upc=$upc target=blank>Click for History</a></td></tr>";
        echo "<tr><td colspan=4>";
        // kick out a scale input for upcs starting with 002
        // pass variables with prefix s_
        if (preg_match("/^002/",$rowItem['upc']) && $row3[1] == 3){
           $scaleQuery = $sql->prepare("select * from scaleItems where plu=?");
           $scaleRes = $sql->execute($scaleQuery, array($upc));
           $scaleRow = $sql->fetch_row($scaleRes);
           echo "<table border=1 cellspacing=0 cellpadding=7><tr>";
           echo "<td bgcolor=\"#FFFFCC\">";
           echo "Scale Information:<br />";
           echo "UPC: <font color=red>{$rowItem['upc']}</font><p />";
           echo "<input type=hidden name=s_plu value={$rowItem['upc']}>";
           // update:  some items might need a longer description
           echo "Longer description:";
           // only show a longer description if the description differs
           echo "&nbsp;&nbsp;<input type=text name=s_longdesc size=40 maxlength=100";
           if ($rowItem['description'] != $scaleRow[2]){
             echo " value='{$scaleRow[2]}'";
           }
           echo ">";
           echo "<p />";
           // EXCEPTION PRICE?
           //   Unnecessary to users but expected by csv package
           echo "<input type=hidden name=s_exception value=0.00>";
           echo "<table border=1 cellspacing=0 cellpadding=7><tr>";
           echo "<th>Weight</th><th>By Count</th><th>Tare</th><th>Shelf Life</th><th>Net Wt (oz)</th><th>Label</th><th>Safehandling</th>";
           echo "</tr><tr><td>";
           echo "<input type=radio name=s_type value=\"Random Weight\"";
           if ($scaleRow[4] == 0){
              echo " checked> Random<br />";
           }
           else {
              echo "> Random<br />";    
           }
           echo "<input type=radio name=s_type value=\"Fixed Weight\"";
           if ($scaleRow[4] == 1){
              echo " checked> Fixed<br />";
           }
           else {
              echo "> Fixed<br />";
           }
           echo "</td><td align=center>";
           echo "<input type=checkbox name=s_bycount";
           if ($scaleRow['bycount'] == 1){
              echo " checked>";
           }
           else {
              echo ">";
           }
           echo "</td><td>";
           echo "<input type=text name=s_tare size=5 value={$scaleRow['tare']}>";
           echo "</td><td>";
           echo "<input type=text name=s_shelflife size=5 value={$scaleRow['shelflife']}>";
           echo "</td><td>";
           echo "<input type=text name=s_netwt size=5 value={$scaleRow['netWeight']}>";
           echo "</td><td>";
           echo "<select name=s_label size=2>";
           if ($scaleRow['label'] == 133 || $scaleRow['label'] == 63)
                   echo "<option value=horizontal selected>Horizontal</option>";
           else
                   echo "<option value=horizontal>Horizontal</option>";
           if ($scaleRow['label'] == 103 || $scaleRow['label'] == 53 || $scaleRow['label'] == 23)
                   echo "<option value=vertical selected>Vertical</option>";
           else
                   echo "<option value=vertical>Vertical</option>";
           echo "</select>";
           echo "</td><td align=center>";
           if ($scaleRow['graphics'] == 0)
             echo "<input type=checkbox name=s_graphics />";
           else
             echo "<input type=checkbox name=s_graphics checked />";
           echo "</td>";
           echo "</td></tr></table><br />";
           echo "<p />Expanded text:<br /><textarea name=s_text rows=4
           cols=40>{$scaleRow['text']}</textarea>";

           echo "<p /></td></tr></table>";
        }
        echo "<br /></td></tr><tr><td>";
                echo "Like Code Linked Items</td><td>&nbsp;</td><td><input type=checkbox name=update value='no'></td><td colspan=4>Check to not update like code items</td></tr><tr><td>";
                if($likeCodeNum > 0){
                        $selLikeQ = "SELECT p.upc,p.description,p.normal_price FROM products as p, upcLike as u WHERE u.upc = p.upc and u.likeCode = ?";
                        likedtotable($selLikeQ,$likeCodeRow[0],0,'FFFFCC');
            echo"<td valign=top colspan=3><a href=/git/fannie/reports/PriceHistory/?likecode=$likeCodeRow[1] target=lc_hist>Click for Like Code History</a></td>";
                }
       if ($logged_in || (preg_match("/^002/",$rowItem['upc']) && $row3[1] == 3)){
               echo "</td></tr><tr><td colspan=4><input type='submit' name='submit' value='submit'> ";
        echo " <a href=../../item/DeleteItemPage.php?id=$upc>Delete this item</a> | ";
           }
           else {
               echo "</td></tr><tr><td colspan=4>Please <a href=/auth/ui/loginform.php?redirect=/queries/productTest.php?upc=$upc>";
                echo "login</a> to change prices";
           }
           echo " <a href=javascript:back()>Back</a></td></tr> "; 
        echo "<tr><td colspan=5>";
        echo '<a href="" onclick="$(\'#topSecret\').toggle();return false;">Extra</a>';
        echo "<div id=\"topSecret\" style=\"display:none;\">";
        echo "<div style=\"float:left;width:500px;\">";
        echo "<b>Sane Brand &amp; Description</b>: ";
        printf("<input name=\"u_brand\" value=\"%s\" /> 
            <input name=\"u_desc\" value=\"%s\" /><br />",
            $rowItem["brand"],$rowItem["udesc"]);
        printf("<b>Size</b>: <input name=\"u_size\" value=\"%s\" />",
            $rowItem["sizing"]);
        printf('&nbsp;&nbsp;<input type="checkbox" name="u_enableOnline" %s />Sell online<br />',
            ($rowItem['enableOnline']==1 ? 'checked' : ''));
        printf("<b>Expires</b>: <input name=\"u_expires\" value=\"%s\" />",
            $rowItem["expires"]);
        printf('<p><b>Page Text</b><br /><textarea rows=15 cols=80 name=u_long_text>%s</textarea></p>',str_replace("<br />","\n",$rowItem['long_text']));
        echo "</div>";
        if (!empty($rowItem["photo"])){
            echo "<div style=\"float:left;\">";
            printf("<a href=\"/git/fannie/item/images/done/%s\"><img src=\"/git/fannie/item/images/done/%s\" /></a>",
                $rowItem["photo"],str_replace("png","thumb.png",$rowItem["photo"]));
            echo "</div>";
        }
        echo "<div style=\"clear:left;\"></div>";
        echo "</div>";
        echo "</tr>";
           echo "<tr><td height=5>&nbsp;</td></tr><tr><td bgcolor=#ddffdd colspan=3>";
           allLanes($upc);
           echo "</td></tr>";
    }
    return $num;
}

function likedtotable($query,$args,$border,$bgcolor)
{
    global $sql;
        $prep = $sql->prepare($query);
        $results = $sql->execute($prep, $args); 
        $number_cols = $sql->numFields($results);
        //display query
        //echo "<b>query: $query</b>";
        //layout table header
        echo "<table border = $border bgcolor=$bgcolor>\n";
        echo "<tr align left>\n";
        /*for($i=0; $i<5; $i++)
        {
                echo "<th>" . $sql->fieldName($results,$i). "</th>\n";
        }
        echo "</tr>\n"; *///end table header
        //layout table body
        while($row = $sql->fetch_row($results))
        {
                echo "<tr align=left>\n";
                echo "<td >";
                        if(!isset($row[0]))
                        {
                                echo "NULL";
                        }else{
                                 ?>
                                 <a href="productTest.php?upc=<?php echo $row[0]; ?>">
                                 <?php echo $row[0]; ?></a>
                        <?php echo "</td>";
                        }
                for ($i=1;$i<$number_cols-1; $i++)
                {
                echo "<td>";
                        if(!isset($row[$i])) //test for null value
                        {
                                echo "NULL";
                        }else{
                                echo $row[$i];
                        }
                        echo "</td>\n";
                } echo "</tr>\n";
        } echo "</table>\n";
}

function noItem()
{
   echo "No Items Found <br>";
}

function moreItems($upc)
{
    echo "More than 1 item found for: " . $upc . "<br>";
}

function oneItem($upc)
{
    //echo "One item found for: " . $upc . "<br>";
}

function upcCheck($upc){
  $sum=0;
  for($i=1;$i<=11;$i+=2)
   $sum+=3*$barcode{$i};
  for($i=0;$i<=10;$i+=2)
    $sum+=$barcode{$i};
  
  $check = 10 - ($sum)%10;
  return $check; 
}


function upcCheckOld($upc)
{
   $dig1 = substr($upc,0,1);
   $dig2 = substr($upc,1,1);
   $dig3 = substr($upc,2,1);
   $dig4 = substr($upc,3,1);
   $dig5 = substr($upc,4,1);
   $dig6 = substr($upc,5,1);
   $dig7 = substr($upc,6,1);
   $dig8 = substr($upc,7,1);
   $dig9 = substr($upc,8,1);
   $dig10 = substr($upc,9,1);
   $dig11 = substr($upc,10,1);
   $dig12 = substr($upc,11,1);
   $dig13 = substr($upc,12,1);
   //echo $upc . ": ". $dig1 . " ". $dig2 . " ". $dig3. " ". $dig13 . "<br>";
   $mult1 = 3*dig1;
   $mult2 = 3*dig2;
   $mult3 = 3*dig3;
   $mult5 = 3*dig5;
   $mult7 = 3*dig7;
   $mult9 = 3*dig9;
   $mult11 = 3*dig11;
   $mult13 = 3*dig13;
   
   $mod = 10;
 
   //$preCheck = $mult1+$dig2+$multi3+$dig4+$multi5+$dig6+$multi7+$dig8+$multi9+$dig10+$multi11+$dig12+$mult13;
   //echo $upc . " ";
   $odd = $dig13+$dig11+$dig9+$dig7+$dig5+$dig3+$dig1;
   //echo $odd . " ";
   $odd3 = $odd * 3;
   //echo $odd3 . " ";
   $even = $dig12+$dig10+$dig8+$dig6+$dig4+$dig2;
   //echo $even . " ";
   $precheck = $odd3+$even;
   //echo $precheck . " ";
   $modTen = $precheck % $mod;
   //echo $modTen . " ";
   $checkDigit = 10-$modTen;
   //echo $checkDigit . "<BR>"; 
   return $checkDigit;
}

/* for a given upc in a given department, find
 * the previous and next upcs in that department
 * -1 for a upc value indicates no previous or
 * next item.  Returns true
 */
function deptPrevNext($dept,$upc,&$prev,&$next){
    global $sql;
    $deptQ = $sql->prepare("select upc from products where department = ? order by upc");
    $deptR = $sql->execute($deptQ, array($dept));    
    $p = -1;
    while ($row = $sql->fetchRow($deptR)){
        if ($upc == $row[0]){
            $prev = $p;
            break;    
        }
        $p = $row[0];
    }
    $row = $sql->fetchRow($deptR);
    if ($row)
        $next = $row[0];
    else
        $next = -1;
    
    return true;
}

