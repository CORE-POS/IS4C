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

    var deptList = new Vue({
        el: '#deptselect',
        data: {
            deptID: 0
        },
        methods: {
            show: function() {
                subDept.show(this.deptID);
            }
        }
    });

    var subList = new Vue({
        el: '#subdiv',
        data: {
            subs: [],
            dept: "",
            selected: []
        },
        methods: {
            // Observer layer doesn't grok reduce()
            get: function () {
                return this.selected;
            }
        }
    });

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
            data: d,
            dataType: "json"
        }).fail(function(){
            errorMsg('Error loading sub departments');
        }).done(function(resp){
            subList.subs = resp;
            subList.dept = 'Subdepts in ' + name;
        });
    };

    mod.add = function() {
        var name = $('#newname').val();
        var did = deptList.deptID;
        var d = 'action=addSub&name='+name+'&did='+did;
        $.ajax({
            type: 'post',
            data: d,
            dataType: "json"
        }).fail(function() {
            errorMsg('Error creating sub department');
        }).done(function(resp){
            subList.subs = resp;
            $('#newname').val('');
            goodMsg('Added sub department ' + name);
        });
    };

    mod.del = function() {
        var did = deptList.deptID;
        var d = 'action=deleteSub&did='+did;
        d += subList.get().reduce((acc, x) => acc + '&sid[]=' + x, "");
        $.ajax({
            type: 'post',
            data: d,
            dataType: "json"
        }).fail(function(){
            errorMsg('Error deleting sub department(s)');
        }).done(function(resp){
            subList.subs = resp;
            goodMsg('Deleted sub department(s)');
        });
    };

    return mod;

}(jQuery));
