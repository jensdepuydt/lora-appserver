<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<?php
		//connect to DB
		include 'database_connect.php';
		//read different devices present in DB
                $sql="select distinct(device) from temp";
                $mysql_result=mysqli_query($connection,$sql) or die(mysqli_error());
		$lora_devices=array();
                while ($row=mysqli_fetch_array($mysql_result))
                {
                	$lora_devices[]=$row["device"];
		}	
		//first get device from post
		$device = $_POST['device'];
		if ($device=="")
		{
			//if the value is empty, use the querystring:
			$device=$_GET["device"];
			//if still nothing, take the first device in the list
			if ($device=="") $device=$lora_devices[0];
		}

		//fetch data and store in arrays
               	$sql="select temperature,pressure,light,event,battery,gps,devaddr,timestamp,UNIX_TIMESTAMP(CONVERT_TZ(timestamp, '+01:00', 'SYSTEM')) AS epoch, DAYNAME(timestamp) as weekday from temp WHERE device='".$device."' order by timestamp DESC LIMIT 30";
	        $mysql_result=mysqli_query($connection,$sql) or die(mysqli_error());
		$data_temp=array();
		$data_press=array();
		$data_ligtht=array();
		$data_event=array();
		$data_battery=array();
		$data_gps=array();
		$data_devaddr=array();
		$data_ts=array();
		$data_epoch=array();
		$data_weekday=array();
               	while ($row=mysqli_fetch_array($mysql_result))
	        {
        		$data_ts[]=$row["timestamp"];
			$data_temp[]=$row["temperature"];
			$data_press[]=$row["pressure"];
			$data_light[]=$row["light"];
			$data_event[]=$row["event"];
			$data_battery[]=$row["battery"];
			$data_gps[]=$row["gps"];
			$data_devaddr[]=$row["devaddr"];
			$data_epoch[]=$row["epoch"];
			$data_weekday[]=$row["weekday"];
		}
	?>
		<title>LoRa logserver - data for <?php echo $device ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="imagetoolbar" content="no" />
		<meta http-equiv="imagetoolbar" content="false" />
		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		<script type="text/javascript">
			google.charts.load('current', {packages: ['corechart','line','timeline']});
			google.charts.setOnLoadCallback(drawChart);

			function drawChart()
			{
				var chartDiv = document.getElementById('chart_div');
				var data = new google.visualization.DataTable();
				data.addColumn('date', 'Day');
				data.addColumn('number', 'Temperature');
				data.addColumn("number", "Data");
				<?php
				$i=0;
				foreach ($data_temp as $temp)
				{
					//generate data for graph
					echo "\ndata.addRow([new Date(".$data_epoch[$i]."*1000),".$data_temp[$i].",".$data_light[$i]."]);";
					$i++;
				}
			        ?>
			
				var materialOptions = 
				{
					
					width: 1000,
					height: 400,
					series:{
						// Gives each series an axis name that matches the Y-axis below.
						0: {axis: 'temp'},
						1: {axis: 'data'},
					},
					axes: {
						// Adds labels to each axis; they don't have to match the axis names.
						y: {
							temp: {label: 'Temperature (°C)'},
							data: {label: 'Data'},
						}
					}
				};

				function drawMaterialChart()
				{
					var materialChart = new google.charts.Line(chartDiv);
					materialChart.draw(data, materialOptions);
				}
				drawMaterialChart();
			}
		</script>
	</head>
	<body style='font-family:"trebuchet MS", "Century Gothic", Arial;'>
		<h2>LoRaWAN logserver - data for <?php echo $device ?></h2>
		<div style="width:400px; padding:10px; border:1px solid black;">
			<div>select sensor to display</div>
			<form name="device_select" action="index.php" method="POST">
			<table>
			<tr>
				<td>Device:</td>
				<td>
					<select input width="300px" type="text" name="device">
                                        <?php
						foreach($lora_devices as $lora_device)
						{
							$selected="";
							if ($lora_device==$device) $selected="selected";
							echo "<option ".$selected." value='".$lora_device."'>".$lora_device."</option>";
						}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2"><input type="submit" name="submit" /></td>
			</tr>
			</table>
			</form>
		</div>
		<div id="chart_div"></div>
		<table border="1">
			<tr>
				<th style="width:250px;">Timestamp</th>
				<th>Devaddr</th>
				<th>Temperature (C)</th>
				<th>Pressure (hPa)</th>
				<th>Light (lux)</th>
				<th>Battery (mV)</th>
				<th>GPS</th>
				<th>Event</th>
			</tr>
		<?php
			$i=0;
			foreach ($data_temp as $temp)
			{
				$timestamp=$data_ts[$i];
				$press=$data_press[$i];
				$light=$data_light[$i];
				$event=$data_event[$i];
				$battery=$data_battery[$i];
				$gps=$data_gps[$i];
				$devaddr=$data_devaddr[$i];
				$weekday=$data_weekday[$i];
					echo "<tr>";
					echo "<td>".$weekday." ".$timestamp."</td>";
					echo "<td>".$devaddr."</td>";
					echo "<td>".$temp."</td>";
					echo "<td>".$press."</td>";
					echo "<td>".$light."</td>";
					echo "<td>".$battery."</td>";
					echo "<td>".$gps."</td>";
					echo "<td>".$event."</td>";
					echo "</tr>";
				$i++;
			}
		 ?>
		</table>
	</body>
</html>
