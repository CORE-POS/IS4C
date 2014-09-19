<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

/* configuration

    $tos is an array of email addresses indexed on subdepartment
    these are the people notified when an item in that subdept
    are changed

    $hostname is used in the email link
*/

function audit($dept_sub,$uid,$upc,$desc,$price,$tax,$fs,$scale,$discount,$likecode=False){
    global $FANNIE_URL;
    $hostname = "key";
    $tos = array(   0=>"andy",
            1=>"jim, lisa, meales",
            2=>"jesse, lisa, meales",
            3=>"fkoenig, aelliott, justin",
            4=>"joeu, lisa, meales",
            5=>"jillhall, lisa, meales",
            6=>"michael",
            7=>"shannon",
            8=>"jesse, lisa, meales",
            9=>"meales, lisa"
    );  
    $taxes = array("NoTax","Reg","Deli");

    $subject = "Item Update notification: ".$upc;
    $message = "Item $upc ($desc) has been changed\n";  
    $message .= "Price: $price\n";
    $message .= "Tax: ".$taxes[$tax]."\n";
    $message .= "Foodstampable: ".($fs==1?"Yes":"No")."\n";
    $message .= "Scale: ".($scale==1?"Yes":"No")."\n";
    $message .= "Discountable: ".($discount==1?"Yes":"No")."\n";
    if ($likecode != False){
        if ($likecode == -1)
            $message .= "This item is not in a like code\n";
        else
            $message .= "All items in this likecode ($likecode) were changed\n";
    }
    $message .= "\n";
    $message .= "Adjust this item?\n";
    $message .= "http://{$hostname}/{$FANNIE_URL}item/itemMaint.php?searchupc=$upc\n";
    $message .= "\n";
    $message .= "This change was made by user $uid\n";

    $from = "From: automail\r\n";

    mail($tos[$dept_sub],$subject,$message,$from);
}

?>
