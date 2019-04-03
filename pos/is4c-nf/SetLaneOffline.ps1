# Run this script on Windows using the command:
#      powershell.exe -ExecutionPolicy Bypass -File ./SetLaneOffline.ps1
#
###### CHANGE lanenumber below to the actual lane number for this lane #######
$postParams = @{id='4';up='0'}

Invoke-WebRequest -Uri http://POS-Server/Our-Table/fannie/admin/LaneStatus.php -Method POST -Body $postParams -UseBasicParsing
