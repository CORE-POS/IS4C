<?php
include ('../define.conf');
?>
<!-- 	INSERT JAVASCRIPT HEAD TAGS HERE -->
<script src="http://ajax.microsoft.com/ajax/jquery/jquery-1.4.2.min.js" type="text/javascript"></script>    		    

<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script> -->
<script src="<?php echo SRCROOT; ?>/js/tablesort.js" type="text/javascript"></script>
<script src="<?php echo SRCROOT; ?>/js/picnet.table.filter.min.js"  type="text/javascript"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.13/jquery-ui.min.js" type="text/javascript"></script>
<script src="<?php echo SRCROOT; ?>/js/ZeroClipboard.js" type="text/javascript"></script>
<!-- <script src="<?php //echo SRCROOT; ?>/js/jquery-ui.js" type="text/javascript"></script> -->
<script type="text/javascript" charset="utf-8">
<!-- Begin
 function putFocus(formInst, elementInst) {
  if (document.forms.length > 0) {
   document.forms[formInst].elements[elementInst].focus();
  }
 }
// The second number in the "onLoad" command in the body
// tag determines the forms focus.
//  End -->
</script>
<script type="text/javascript" charset="utf-8">
$(document).ready(function() {        
	var options = {
		clearFiltersControls: [$('#cleanfilters')],            
	};
	$('#output').tableFilter(options);
});
</script>

<script type="text/javascript" charset="utf-8">
$(function() {
    $('.opener').click(function(e) {
        e.preventDefault();
        var $this = $(this);
        var horizontalPadding = 30;
        var verticalPadding = 30;
        $('<div id="outerdiv"><iframe id="externalSite" class="externalSite" src="' + this.href + '" />').dialog({
            title: ($this.attr('title')) ? $this.attr('title') : 'Instant Item Editor',
            autoOpen: true,
            width: 560,
            height: 700,
            modal: true,
            resizable: true,
            autoResize: true,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        }).width(560 - horizontalPadding).height(700 - verticalPadding);            
    });
});

$(function() {
    $('.loginbox').click(function(e) {
        e.preventDefault();
        var $this = $(this);
        var horizontalPadding = 0;
        var verticalPadding = 0;
        $('<div id="outerdiv2"><iframe id="externalSite2" class="externalSite2" src="' + this.href + '" />').dialog({
            title: ($this.attr('title')) ? $this.attr('title') : 'Instant Item Editor',
            autoOpen: true,
            width: 500,
            height: 300,
            modal: true,
            resizable: true,
            autoResize: true,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        }).width(500 - horizontalPadding).height(300 - verticalPadding);            
    });
});
</script>
<script>
function goBack(){
	window.history.back()
}
</script>



<!-- 	INSERT CSS HEAD TAGS HERE -->
<link rel="stylesheet" href="<?php echo SRCROOT; ?>/style.css" type="text/css" />
<!-- ><link rel="stylesheet" href="<?php //echo SRCROOT; ?>/tablesort.css" type="text/css" /> -->
<link rel="stylesheet" href="<?php echo SRCROOT; ?>/js/jquery-ui.css" type="text/css" />