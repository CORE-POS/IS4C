function addNew()
{
    var cn = $('#newMem').val();
    var pid = $('#newPayID').val();
    $.ajax({
        url: 'StaffArAccountsPage.php',
        type: 'get',
        data: 'add='+cn+'&payid='+pid,
        dataType: 'json',
        success: function(resp) {
            if (resp.error) {
                alert(resp.error);
            } else {
                if ($('#row'+cn).length == 0) {
                    var newrow = '<tr class="accountrow" id="row' + cn + '">';
                    newrow += '<td>'+cn+'</td>';
                    newrow += '<td class="payidtext">'+pid+'</td>';
                    newrow += '<td class="nametext">'+resp.name+'</td>';
                    newrow += '<td class="currentbalance">'+resp.balance+'</td>';
                    newrow += '<td>0.00</td>';
                    newrow += '<td><input type="text" size="7" class="nextdeduct" value="0.00" /></td>';
                    newrow += '<td><a href="" onclick="removeAccount('+cn+'); return false;">Remove from List</a></td>';
                    newrow += '</tr>';
                    $('#accountTable tr:last').after(newrow);
                } else {
                   $('#row'+cn).find('.nametext').html(resp.name); 
                   $('#row'+cn).find('.currentbalance').html(resp.balance); 
                   $('#row'+cn).find('.payidtext').html(pid);
                }

                $('#newMem').val('');
                $('#newPayID').val('');
            }
        }
    });
}

function removeAccount(cn)
{
    $.ajax({
        url: 'StaffArAccountsPage.php',
        type: 'get',
        data: 'delete='+cn,
        success: function(resp) {
            $('#row'+cn).remove();
        }
    });
}

function useCurrent()
{
    $('.accountrow').each(function() {
        var cur = $(this).find('.currentbalance').html();
        $(this).find('.nextdeduct').val(cur);
    });
}

function saveForm()
{
    var ids = new Array();
    var amounts = new Array();
    $('.accountrow').each(function() {
        ids.push($(this).find('.cardnotext').html());
        amounts.push($(this).find('.nextdeduct').val());
    });
    var dstr = 'saveIds=' + JSON.stringify(ids) + '&saveAmounts=' + JSON.stringify(amounts);

    $.ajax({
        url: 'StaffArAccountsPage.php',
        type: 'post',
        data: dstr,
        success: function(resp) {
            alert('Saved!');
            location.reload();
        }
    });
}
