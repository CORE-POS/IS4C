/**
 *  allow_group_select_checkboxes created by Ben D. 
 *  https://stackoverflow.com/users/1096794/ben-d
 *  @param {string} checkbox_wrapper_id element id 
 *  attr of wrapper element containing checkboxes.
 */
function allow_group_select_checkboxes(checkbox_wrapper_id){
    var lastChecked = null;
    var checkboxes = document.querySelectorAll('#'+checkbox_wrapper_id+' input[type="checkbox"]');

    //I'm attaching an index attribute because it's easy, but you could do this other ways...
    for (var i=0;i<checkboxes.length;i++){
        checkboxes[i].setAttribute('data-index',i);
    }

    for (var i=0;i<checkboxes.length;i++){
        checkboxes[i].addEventListener("click",function(e){

            if(lastChecked && e.shiftKey) {
                var i = parseInt(lastChecked.getAttribute('data-index'));
                var j = parseInt(this.getAttribute('data-index'));
                var check_or_uncheck = this.checked;

                var low = i; var high=j;
                if (i>j){
                    var low = j; var high=i; 
                }

                for(var c=0;c<checkboxes.length;c++){
                    if (low <= c && c <=high){
                        checkboxes[c].checked = check_or_uncheck;
                    }   
                }
            } 
            lastChecked = this;
        });
    }
}
