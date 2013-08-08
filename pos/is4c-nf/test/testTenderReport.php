<?php
include('test_env.php');
$mods = AutoLoader::ListModules('TenderReport');
?>
<form action="testTenderReport.php">
<select name='t_mod'>
<?php foreach ($mods as $m){ ?>
<option><?php echo $m; ?></option>
<?php } ?>
</select>

Emp# <input type="text" size="3" name="emp_no" />

<input type="submit" value="Get Output" />
</form>
<hr />
<?php
if (isset($_REQUEST['t_mod']) && isset($_REQUEST['emp_no'])){
	$CORE_LOCAL->set('CashierNo',$_REQUEST['emp_no']);
	$tmod = $_REQUEST['t_mod'];
	echo "Output for $tmod:<br />";
	echo '<pre>';
	var_dump($tmod::get());
	echo '</pre>';
}
?>
