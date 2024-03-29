
var batchList = (function($) {

    var mod = {};

    /**
      State data controlling how list
      is currently displayed
    */
    var filters = {
        owner: "",
        store: "",
        name: "",
        date: "",
    };
    var mode = "all";
    var pageStart = "";

    /**
      Refetch list from server based on current
      display criteria
    */
    var redrawList = function() {
        var data = 'mode=' + encodeURIComponent(mode);
        data += '&filter=' + encodeURIComponent(JSON.stringify(filters));
        data += '&max=' + encodeURIComponent(pageStart);
        $.ajax({
            url: 'BatchListPage.php',
            type: 'get',
            data: data
        }).done(function(resp) {
            $('#displayarea').html(resp);
        });
    };

    /**
      Update mode state & redraw
      Reset page start since new time slice might have
      fewer pages
    */
    mod.changeTimeSlice = function(m) {
        mode = m;
        pageStart = '';
        redrawList();
    };

    /**
      Update paging state & redraw
    */
    mod.movePage = function(p) {
        pageStart = p;
        redrawList();
    };

    /**
      Update filters state & redraw
      Reset page start since new filtered set might have
      fewer pages
    */
    mod.refilter = function() {
        filters.owner = $('#filterOwner').val();
        filters.store = $('#filterStore').val();
        filters.name = $('#filterName').val();
        filters.date = $('#filterDate').val();
        pageStart = '';
        redrawList();
    };

    return mod;

}(jQuery));

function newBatch()
{
    var dataStr = $('#newBatchForm').serialize();
    if ($('#newBatchName').val() === '') {
        showBootstrapAlert('#inputarea', 'danger', 'Name cannot be blank');
        return;
    }

    $.ajax({
        url: 'BatchListPage.php',
        type: 'post',
        data: dataStr,
        dataType: 'json'
    }).done(function(resp) {
        if (resp.error) {
            showBootstrapAlert('#inputarea', 'danger', resp.msg);
        } else {
            showBootstrapAlert('#inputarea', 'success', resp.msg);
            $('#displayarea').html(resp.new_list);
            $('#newBatchForm')[0].reset();
        }
    });
}

function editBatchLine(id)
{
    var name = $('tr#batchRow' + id + ' a:first').html();
    var type = $('#type'+id).html();
    var startdate = $('#startdate'+id).html();
    var enddate = $('#enddate'+id).html();
    var owner = $('#owner'+id).html();

    var batchTypes = JSON.parse($('#typeJSON').val());
    var owners = JSON.parse($('#ownerJSON').val());
    
    $('#name'+id).html($('<input class="form-control" name="batchName"/>').val(name));
    $('#startdate'+id).html($('<input class="form-control date-input" name="startDate"/>').val(startdate));
    $('#enddate'+id).html($('<input class="form-control date-input" name="endDate"/>').val(enddate));

    var typesElem = $('<select class="form-control" name="batchType"/>');
    for (var typeID in batchTypes) {
        var opt = $('<option/>').val(typeID).html(batchTypes[typeID]);
        if (type === batchTypes[typeID]) {
            opt.attr('selected','selected');
        }
        typesElem.append(opt);
    }
    $('#type'+id).html(typesElem);

    var ownerElem = $('<select class="form-control" name="owner"/>');
    ownerElem.append('<option/>');
    for (var o in owners) {
        opt = $('<option/>').html(owners[o]);
        if (owner === owners[o]) {
            opt.attr('selected','selected');
        }
        ownerElem.append(opt);
    }
    $('#owner'+id).html(ownerElem);

    $('tr#batchRow' + id).find('a.batchEditLink').hide();
    $('tr#batchRow' + id).find('a.batchSaveLink').show();

    $('tr#batchRow' + id + ' input.date-input').datepicker();

    $('tr#batchRow'+id+' input:first').focus();
}

function saveBatchLine(id)
{
    var dataStr = $('tr#batchRow' + id + ' :input').serialize();
    dataStr += '&id='+id;

    $.ajax({
        url: 'BatchListPage.php',
        type: 'post',
        data: dataStr,
        dataType: 'json'
    }).done(function(resp) {
        if (resp.error) {
            showBootstrapAlert('#inputarea', 'danger', resp.msg);
        } else {
            showBootstrapAlert('#inputarea', 'success', resp.msg);
        }
    });

    $('tr#batchRow' + id + ' td').each(function() {
        var newVal = '';
        if ($(this).find('select').length > 0) {
            newVal = $(this).find('option:selected').html();
            $(this).html(newVal);
        } else if ($(this).find('input').length > 0) {
            newVal = $(this).find('input').val();
            $(this).html(newVal);
        }
    });

    $('tr#batchRow' + id).find('a.batchEditLink').show();
    $('tr#batchRow' + id).find('a.batchSaveLink').hide();

    var link = $('<a href="EditBatchPage.php?id=' + id + '"/>');

    var name = $('tr#batchRow'+id+' td:first').html();
    link.html(name);
    $('tr#batchRow'+id+' td:first').html(link);
}

function deleteBatch(id, name, endDate)
{
    
	var audited = $('#isAudited').val();
	if (audited === "1") {
            window.alert("You're not allowed to delete batches");
            return;
	}

        var date1 = new Date(endDate+' 23:59:59');
        var date2 = new Date();

        if (date1 >= date2) {
            window.alert("Cannot delete a batch with valid end date. To delete batch, change end date and reload this page.");
            return false;
        }

    var dataStr = 'delete=1&id='+id;

	if (window.confirm('Delete this batch ('+name+')?')) {
        console.log('anything');
        $.ajax({
            url: 'BatchListPage.php',
            data: dataStr,
            type: 'post',
            dataType: 'json'
        }).done(function(resp) {
            if (resp.error) {
                showBootstrapAlert('#inputarea', 'danger', resp.msg);
            } else {
                showBootstrapAlert('#inputarea', 'success', resp.msg);
                $('tr#batchRow'+id).hide();
            }
        });
    }
}

