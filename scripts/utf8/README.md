### Tools for debugging character encoding

The most basic CLI test is `mysql.php`. It verifies that
UTF-8 encoded data can be saved to the database and
retreived again. It also verifies that incorrectly encoded
data *cannot* be saved to the database. Keeping the web
server out of the process removes a layer of problems.

The web-based test, `mysqlweb.php`, performs the same
save & retrieve test but this time places the UTF-8
data in a form field and consumes it via HTTP POST
to check whether that layer is transcoding or 
mis-encoding values.

The `chkmysql.php` tool attempt to scan a given
table column for invalid byte sequences. Getting
invalid values into a table has proven fairly difficult
so testing on this has been running it against a VARBINARY
column. 

