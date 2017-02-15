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

var deptEdit = (function($) {

    var mod = {};

    mod.deptSave = function() {
        var fields = $('.deptFields :input').serialize();

        $.ajax({
            url: 'DepartmentEditor.php',
            type: 'post',
            timeout: 5000,
            data: fields,
            dataType: 'json'
        }).fail(function(){
            showBootstrapAlert('#deptdiv', 'danger', 'Error saving department');
        }).done(function(resp){
            if (resp.did && resp.msg) {
                showBootstrapAlert('#deptdiv', 'success', resp.msg);
            } else {
                showBootstrapAlert('#deptdiv', 'danger', 'Error saving department');
            }
        });
    };

    mod.deptchange = function() {
        var dID = $('#deptselect').val();
        if (dID == 0){ 
            $('#infodiv').html("");
            return;
        }

        $.ajax({
            url: 'DepartmentEditor.php',
            type: 'get',
            timeout: 5000,
            data: 'id='+dID
        }).fail(function(){
            showBootstrapAlert('#deptdiv', 'danger', 'Error loading department');
        }).done(function(resp){
            $('#infodiv').html(resp);
            $('#infodiv input[type=text]').keyup(function (e){
                if (e.which == 13) {
                    mod.deptSave();
                }
            });
            $('#infodiv input[type=text]:first').focus();
        });
    };

    return mod;

}(jQuery)); 

