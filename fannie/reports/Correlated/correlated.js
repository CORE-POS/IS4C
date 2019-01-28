
function flipover(opt){
    if (opt == 'UPC'){
        document.getElementById('inputset1').style.display='none';
        document.getElementById('inputset2').style.display='block';
        document.forms[0].dept1.value='';
        document.forms[0].dept2.value='';
    }
    else {
        document.getElementById('inputset2').style.display='none';
        document.getElementById('inputset1').style.display='block';
        document.forms[0].upc.value='';
    }
}

