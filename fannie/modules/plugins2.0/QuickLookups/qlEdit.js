
var qlEdit = (function () {

    var mod = {};

    /** Vue instance
     *  Form to load a given menu
     */
    var lookupForm = new Vue({
        el: '#lookupForm',
        data: {
            menuNumber: ''
        },
        methods: {
            getMenu: function() {
                fetch('QLEdit.php?id=' + this.menuNumber)
                .then(function(resp) {
                    if (resp.ok) {
                        resp.json().then(i=> entryTable.entries = i);
                    }
                });
            }
        },
        mounted: function() {
            this.$refs.init.focus();
        }
    });

    /** Vue instance
     *  Table of entries for the given menu
     */
    var entryTable = new Vue({
        el: '#entryTable',
        data: {
            entries: [],
            newID: 0,
            parentID: []
        },
        methods: {
            // move entry row up
            moveUp: function(i) {
                if (i >= 1) {
                    var tmp = this.entries[i - 1];
                    this.$set(this.entries, i - 1, this.entries[i]);
                    this.$set(this.entries, i, tmp);
                }
            },
            // move entry row down
            moveDown: function(i) {
                if (i < this.entries.length - 1) {
                    var tmp = this.entries[i + 1];
                    this.$set(this.entries, i + 1, this.entries[i]);
                    this.$set(this.entries, i, tmp);
                }
            },
            // save current entries
            save: function() {
                var fd = new FormData;
                fd.append('id', lookupForm.menuNumber);
                this.entries.forEach(function (i) {
                    fd.append('ql[]', i.id);
                    fd.append('label[]', i.label);
                    fd.append('action[]', i.action);
                });
                fetch('QLEdit.php', {
                    method: 'post',
                    body: fd
                }).then(resp => resp.text())
                .then(resp => lookupForm.getMenu());
            },
            // add more entries to the table
            // ID decrements so there won't be duplicate
            // Real IDs get generated on save()
            add: function() {
                this.entries.push({ id: this.newID, label: "", action: "" });
                this.newID = this.newID - 1;
            },
            // upload an image for the entry
            upload: function(id, index) {
                var data = new FormData();
                var vm = this;
                data.append('id', id);
                var files = document.getElementById('newFile').files;
                if (files.length == 0) return;
                data.append('newImage', files[0]);
                fetch('QuickLookupsImages.php', {
                    method: 'post',
                    body: data
                }).then(resp => resp.text())
                .then(function (resp) {
                    var cur = vm.entries[index];
                    cur['image'] = true;
                    cur['imageURL'] = 'QuickLookupsImages.php?id=' + id;
                    cur['imageURL'] += '&ms=' + (new Date().getMilliseconds());
                    vm.$set(vm.entries, index, cur);
                });
                /*
                $.ajax({
                    url: 'QuickLookupsImages.php',
                    type: 'post',
                    data: data,
                    processData: false,
                    contentType: false
                }).done(function (resp) {
                    var cur = vm.entries[index];
                    cur['image'] = true;
                    cur['imageURL'] = 'QuickLookupsImages.php?id=' + id;
                    cur['imageURL'] += '&ms=' + (new Date().getMilliseconds());
                    vm.$set(vm.entries, index, cur);
                });
                */
            },
            // drilldown to submenu
            submenu: function(id) {
                this.parentID.push(lookupForm.menuNumber);
                lookupForm.menuNumber = id;
                lookupForm.getMenu();
            },
            parentMenu: function() {
                if (this.parentID) {
                    lookupForm.menuNumber = this.parentID.pop();
                    lookupForm.getMenu();
                }
            }
        }
    });

    return mod;

})();

