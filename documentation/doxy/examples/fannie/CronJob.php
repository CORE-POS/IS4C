<?php

class CronJob extends FannieCron {

	public $advertised = True;

	public $description = "Reload speedup tables for batch merge views";

	function task(){
		$sql = op_connect();

		$chk = $sql->query("TRUNCATE TABLE batchMergeTable");
		if ($chk === False)
			echo $this->cron_msg("Could not truncate batchMergeTable");

		$chk = $sql->query("INSERT INTO batchMergeTable SELECT * FROM batchMergeProd");
		if ($chk === False)
			echo $this->cron_msg("Could not load data from batchMergeProd");

		$chk = $sql->query("INSERT INTO batchMergeTable SELECT * FROM batchMergeLC");
		if ($chk === False)
			echo $this->cron_msg("Could not load data from batchMergeLC");

		echo $this->cron_msg("Task complete");

		$sql->close();
	}
}

?>
