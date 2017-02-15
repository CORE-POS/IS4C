var restrictCoupon = (function($) {
    var mod = {};

    mod.load = function(upc){
        $.ajax({
            url: 'RestrictCouponPage.php?id='+upc,
            type: 'get',
            dataType: 'json',
        }).done(function(data){
            $('#upc').val(upc);
            if (data.limit)
                $('#limit').val(data.limit);
            if (data.reason)
                $('#reason').val(data.reason);
        });
    };

    mod.save = function(){
        var dstr = 'id='+$('#upc').val();
        dstr += '&limit='+$('#limit').val();
        dstr += '&reason='+$('#reason').val();
        $.ajax({
            url: 'RestrictCouponPage.php',
            type: 'post',
            data: dstr
        }).done(function(){
            window.location='RestrictCouponPage.php';
        });
    };

    mod.remove = function(upc){
        if (window.confirm('Remove restrictions for '+upc+'?')){
            $.ajax({
                url: 'RestrictCouponPage.php?id='+upc,
                type: 'delete'
            }).done(function(){
                window.location='RestrictCouponPage.php';
            });
        }
    };

    return mod;

}(jQuery));
