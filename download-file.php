<?php
	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="export.csv"');
	
	echo $_GET('data');
?>