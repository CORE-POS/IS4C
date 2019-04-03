###### CHANGE lanenumber below to the actual lane number for this lane #######
$postParams = @{id='lanenumber';up='0'}

Invoke-WebRequest -Uri http://POS-Server/Our-Table/fannie/admin/LaneStatus.php -Method POST -Body $postParams -UseBasicParsing
