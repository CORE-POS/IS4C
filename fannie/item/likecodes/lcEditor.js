
var lcEditor = (function ($) {

    var mod = {};

    function ajax(dstr) {
        $.ajax({
            url: 'LikeCodeAjax.php',
            method: 'post',
            data: dstr,
        }).done(function (resp) {
        });
    };

    mod.toggleStrict = function(lc) {
        ajax('id='+lc+'&strict=flip');
    };

    mod.toggleOrganic = function(lc) {
        ajax('id='+lc+'&organic=flip');
    };

    mod.toggleMulti = function(lc) {
        ajax('id='+lc+'&multi=flip');
    };

    mod.updateVendor = function(lc, vid) {
        ajax('id='+lc+'&vendorID='+vid);
    };

    mod.toggleUsage = function(lc, sid) {
        ajax('id='+lc+'&storeID='+sid+'&inUse=flip');
    }

    mod.toggleInternal = function(lc, sid) {
        ajax('id='+lc+'&storeID='+sid+'&internal=flip');
    }

    mod.retailCat = function(lc, cat) {
        ajax('id='+lc+'&rcat='+cat);
    };

    mod.internalCat = function(lc, cat) {
        ajax('id='+lc+'&icat='+cat);
    };

    return mod;

}(jQuery));
