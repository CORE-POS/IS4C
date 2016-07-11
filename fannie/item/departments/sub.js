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

var subDept = (function($) {
    var mod = {};

    var alertMsg = function(severity, msg) {
        showBootstrapAlert('#alertarea', severity, msg);
    };

    var errorMsg = function(msg) {
        alertMsg('danger', msg);
    };

    var goodMsg = function(msg) {
        alertMsg('success', msg);
    };

    mod.show = function(did) {
        var d= 'did='+did+'&action=showSubsForDept';
        var name = $('#deptselect option:selected').text();
        $.ajax({
            type: 'post',
            data: d
        }).fail(function(){
            errorMsg('Error loading sub departments');
        }).done(function(resp){
            $('#subselect').html(resp);
            $('#subdiv').show();
            $('#formdiv').show();
            $('#subname').html('Subdepts in '+name);
        });
    };

    mod.add = function() {
        var name = $('#newname').val();
        var did = $('#deptselect').val();
        var d = 'action=addSub&name='+name+'&did='+did;
        $.ajax({
            type: 'post',
            data: d
        }).fail(function() {
            errorMsg('Error creating sub department');
        }).done(function(resp){
            $('#subselect').html(resp);
            $('#newname').val('');
            goodMsg('Added sub department ' + name);
        });
    };

    mod.del = function() {
        var did = $('#deptselect').val();
        var d = 'action=deleteSub&did='+did;
        $('#subselect option:selected').each(function(){
            d += '&sid[]='+$(this).val();
        });
        $.ajax({
            type: 'post',
            data: d
        }).fail(function(){
            errorMsg('Error deleting sub department(s)');
        }).done(function(resp){
            $('#subselect').html(resp);
            goodMsg('Deleted sub department(s)');
        });
    };

    return mod;

}(jQuery));
