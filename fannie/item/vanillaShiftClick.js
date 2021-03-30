var lastChecked = null;
var i = 0;
var indexCheckboxes = function() {
    var upcCheckBoxes = document.getElementsByClassName("upcCheckBox"); 
    for (var i = 0; i < upcCheckBoxes.length; i++) {
        upcCheckBoxes.item(i).setAttribute("data-index", i);
    }
};
document.addEventListener("click", function (event) {
    indexCheckboxes();
});
document.addEventListener("click", function (event) {
    if (event.target.classList.contains("upcCheckBox")) {
        if (lastChecked && event.shiftKey) {
            var i = parseInt(lastChecked.getAttribute("data-index"));
            var j = parseInt(event.target.getAttribute("data-index"));
            var checked = event.target.checked;

            var low = i;
            var high = j;
            if (i > j){
                var low = j;
                var high = i;
            }

            for(var c = low; c < high; c++) {
                if (c != low && c!= high) {
                    var check = checked ? true : false;
                    curbox = document.querySelectorAll('[data-index="'+c+'"]');
                    box = curbox.item(0);
                    box.checked = check;
                }
            }
        }
        lastChecked = event.target; 
    }
});
