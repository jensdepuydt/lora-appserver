<?php
	$dbhost = 'localhost';
	$dbusername = 'lora';
	$dbpasswd = 'password';
	$database_name = 'loradb';

	$connection = mysqli_connect("$dbhost","$dbusername","$dbpasswd","$database_name") or die ("Can't connect to MySQL!");
	mysqli_query($connection,"SET NAMES 'UTF8'");
?>
