var genericBilling = (function($) {
    var mod = {};

    var billForm = new Vue({
        el: '#contentArea',
        data: {
            cardNo: 0,
            lastName: "",
            balance: 0,
            hidden: "collapse",
            amount: "",
            description: ""
        },
        methods: {
            reset: function () {
                this.hidden = "collapse";
                this.amount = "";
                this.description = "";
            },
            load: function(resp) {
                this.cardNo = resp.cardNo;
                this.lastName = resp.lastName;
                this.balance = resp.balance;
                this.hidden = "";
            }
        }
    });

    var userMsg = new Vue({
        el: '#resultArea',
        data: {
            alertClass: 'collapse',
            message: ''
        },
        methods: {
            hide: function() {
                this.alertClass = "collapse";
            }
        }
    });

    mod.getMemInfo = function(){
        $.ajax({
            url: 'GenericBillingPage.php?id='+$('#memnum').val(),
            type: 'get',
            dataType: 'json'
        }).done(function(resp){
            userMsg.hide();
            billForm.load(resp);
        });
    };

    mod.postBilling = function() {
        var data = 'id='+$('#form_memnum').val();
        data += '&amount='+$('#amount').val();
        data += '&desc='+$('#desc').val();
        $.ajax({
            url: 'GenericBillingPage.php',
            type: 'post',
            data: data,
            dataType: 'json'
        }).done(function(resp){
            billForm.reset();
            userMsg.message = resp.msg;
            userMsg.alertClass = resp.billed ? "alert alert-success" : "alert alert-danger";
        });
    };

    return mod;
}(jQuery));
