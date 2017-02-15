
$(document).ready(function(){
    $('#addbutton').click(showAddForm);
    $('#saveButton').click(saveEmpInfo);
    $('#commentbutton').click(showCommentForm);
});

function saveEmpInfo(){
    var dstr = "id="+$('#empID').val();
    dstr += "&pos="+$('#empPositions').val();
    dstr += "&month="+$('#nextMonth').val();
    dstr += "&year="+$('#nextYear').val();
    dstr += "&hire="+$('#hireDate').val();
    dstr += "&etype="+$('#etype').val();
    dstr += "&name="+encodeURIComponent($('#empName').val());
    $.ajax({
        type: 'post',
        data: dstr,
        success: function(data){
            alert(data);
        }
    });
}

function showAddForm(){
    $.ajax({
        type: 'get',
        data: 'addForm=yes',
        success: function(data){
            $('#workspace').html(data);
            $('#addsub').click(addEntry);    
            $('#addpos').val($('#empPositions').val());
            $('#addmonth').focus();
        }
    });    
}

function showCommentForm(){
    $.ajax({
        type: 'get',
        data: 'commentForm=yes',
        success: function(data){
            $('#cform').html(data);
            $('#newcomment').focus();
        }
    });
}

function saveComment(){
    var dstr = "id="+$('#empID').val();
    dstr += "&user="+$('#username').val();
    dstr += "&comment="+encodeURIComponent($('#newcomment').val());
    
    $.ajax({
        type: 'post',
        data: dstr,
        success: function(data){
            $('#cform').html('');
            $('#commentfs').html(data);
        }
    });
}

function addEntry(){
    var dstr = "id="+$('#empID').val();
    dstr += "&month="+$('#addmonth').val();    
    dstr += "&year="+$('#addyear').val();    
    dstr += "&type="+$('#addtype').val();    
    dstr += "&score="+$('#addscore').val();    
    dstr += "&pos="+$('#addpos').val();    

    $.ajax({
        type: 'post',
        data: dstr,
        success: function(data){
            $('#historyfs').html(data);
            $('#workspace').html('');
        }
    });
}

function delEntry(id){
    if (!confirm("Delete this eval score")) return false;

    $.ajax({
        type: 'delete',
        data: 'eval='+id+'&id='+$('#empID').val(),
        success: function(data){
            $('#historyfs').html(data);
        }
    });

    return false;
}
function deleteComment(id){
    if (!confirm("Delete this comment")) return false;

    $.ajax({
        type: 'delete',
        data: 'comment='+id+'&id='+$('#empID').val(),
        success: function(data){
            $('#commentfs').html(data);
        }
    });
    return false;
}
