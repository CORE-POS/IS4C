
var qlEdit = (function ($) {

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
                $.ajax({
                    url: 'QLEdit.php?id=' + this.menuNumber,
                    dataType: 'json'
                }).done(function (resp) {
                    entryTable.entries = resp;
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
            newID: 0
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
                var d = 'id='+lookupForm.menuNumber;
                d += this.entries.reduce((acc, x) => acc + '&ql[]=' + x.id, "");
                d += this.entries.reduce((acc, x) => acc + '&label[]=' + x.label, "");
                d += this.entries.reduce((acc, x) => acc + '&action[]=' + x.action, "");
                $.ajax({
                    type: 'post',
                    data: d
                }).success(function (resp) {
                    lookupForm.getMenu();
                });
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
                var files = $('#newFile' + id).prop('files');
                if (files.length == 0) return;
                data.append('newImage', files[0]);
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
            }
        }
    });

    return mod;

})(jQuery);

