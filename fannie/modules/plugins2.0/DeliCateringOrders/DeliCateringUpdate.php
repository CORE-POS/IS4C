<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of CORE-POS.

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

class DeliCateringUpdate {} // compat

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {

    include(dirname(__FILE__).'/../../../config.php');
    if (!class_exists('FannieAPI')) {
        include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
    }
    if (!class_exists('DeliCateringPage.php')) {
        include_once($FANNIE_ROOT.'item/DeliCateringOrdersPage.php');
    }
    
    $ret = '';
    if ($_POST['category'] == 'appetizers') 
    $ret .= '
        <h2>Appetizers</h2>
                <div class=\'descbox\'>
                    <div class=\'title\'>
                        Gourmet Cheese Platter
                    </div>
                    <div class=\'desc\'>
                        An assortment of artisan cheeses for every palate. Creamy 
                        imported Brie, aged Gouda, sharp Irish cheddar, gorgonzola, 
                        fresh chevre, and applewood smoked cheddar. Served with 
                        seasonal fruit and sliced baguette.
                    </div>
                    <div class=\'quantbox\'>
                      <strong>$54.99</strong><br><i>Serves 8 - 10</i><br>
                      <input type=\'text\' name=\'id101\' id=\'qty101\' class=\'form-qty\'><br>
                      <input type=\'button\' name=\'add\' class=\'form-up-btn\' onclick=\'javascript: document.getElementById("qty101").value++;\' value=\'+\'>
                      <input type=\'button\' name=\'subtract\' class=\'form-down-btn\' onclick=\'javascript: document.getElementById("qty101").value--;\' value=\'-\'>      
                    </div>
                    
                </div>

                <div class=\'descbox\'>
                    <div class=\'title\'>
                        <br>Gourmet Cheese & Charcuterie Platter
                    </div>
                    <div class=\'desc\'>
                        The same variety of delicious cheeses, but with the 
                        addition of Lake Superior smoked trout, duck liver 
                        pate with cognac, and thinly sliced Olli salami.
                    </div>
                    
                    <div class=\'quantbox\'>
                      <strong>$99.99</strong><br><i>Serves 16 - 20</i><br>
                      <input type=\'text\' name=\'id102\' id=\'qty102\' class=\'form-qty\'><br>
                      <input type=\'button\' name=\'add\' class=\'form-up-btn\' onclick=\'javascript: document.getElementById("qty102").value++;\' value=\'+\'>
                      <input type=\'button\' name=\'subtract\' class=\'form-down-btn\' onclick=\'javascript: document.getElementById("qty102").value--;\' value=\'-\'>
                    </div>
                    
                </div>
                
                    <div class=\'longdescbox\'>
                    <div class=\'title\'>
                        Spanikopita Platter
                    </div>
                    <div class=\'desc\'>
                        Flaky phyllo dough wrapped around a savory 
                        combination of spinach, onions, feta and herbs. 
                        Served with a tangy tzatziki sauce for dipping.
                    </div>
                    <div class=\'quantbox\'>
                      <strong>$34.99</strong><br>Small<br><i>Serves 8 - 10</i><br>
                      <input type=\'text\' name=\'id103\' id=\'qty103\' class=\'form-qty\'><br>
                      <input type=\'button\' name=\'add\' class=\'form-up-btn\' onclick=\'javascript: document.getElementById("qty103").value++;\' value=\'+\'>
                      <input type=\'button\' name=\'subtract\' class=\'form-down-btn\' onclick=\'javascript: document.getElementById("qty103").value--;\' value=\'-\'>
                    </div>
                    <div class=\'quantbox\'>
                    <strong>$64.99</strong><br>Large<br><i>Serves 16 - 20</i><br>
                      <input type=\'text\' name=\'id104\' id=\'qty104\' class=\'form-qty\'><br>
                      <input type=\'button\' name=\'add\' class=\'form-up-btn\' onclick=\'javascript: document.getElementById("qty104").value++;\' value=\'+\'>
                      <input type=\'button\' name=\'subtract\' class=\'form-down-btn\' onclick=\'javascript: document.getElementById("qty104").value--;\' value=\'-\'>
                    </div>
                    
                </div>
    ';
    
    if ($_POST['category'] == 'fruit') 
    $ret .= '<h2>Fruit And Veggie Trays</h2>
                <div class=\'descbox\'>
                    <div class=\'title\'>
                        Small Vegetable Tray
                    </div>
                    <div class=\'desc\'>
                        Each tray includes a variety of seasonal fruit 
                        and/or vegetables that are hand-selected for 
                        maximum freshness and flavor and beautifully
                        arranged by the experts in our Produce department.
                    </div>
                    <div class=\'quantbox\'>
                      <strong>$39.95</strong><br><i>Serves 8 - 12</i><br>
                      <input type=\'text\' name=\'id201\' id=\'qty201\' class=\'form-qty\'><br>
                      <input type=\'button\' name=\'add\' class=\'form-up-btn\' onclick=\'javascript: document.getElementById("qty201").value++;\' value=\'+\'>
                      <input type=\'button\' name=\'subtract\' class=\'form-down-btn\' onclick=\'javascript: document.getElementById("qty201").value--;\' value=\'-\'>      
                    </div>
                    <textarea rows="1" cols="50"></textarea>
                </div>
    ';

    echo json_encode($ret);

}