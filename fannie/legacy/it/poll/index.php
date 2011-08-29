<?php

?>

<html><head><title>Poll Maker</title>
<script type="text/javascript" src="index.js"></script>
<link rel="stylesheet" type="text/css" href="index.css">
</head>
<body>
<div id="pollstart">
<b>Create a new Poll</b> <input type=text id=newPollName />
<input type=submit value=Create onclick="newPoll();" />
</div>
<input type=hidden id=pollNumQuestions value=0 />
<div id="polldisplay">

</div>
</body>
