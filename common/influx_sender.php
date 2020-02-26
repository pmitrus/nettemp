<?php

<<<<<<< HEAD

  
=======
>>>>>>> betamm-upstream
function sendInflux($s_value, $s_current, $rom, $name, $type){ 
 
	$root= "/var/www/nettemp";

	$date = date("Y-m-d H:i:s");  
  
  	try {
    	$db = new PDO("sqlite:$root/dbf/nettemp.db");
    	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
  	} 
  		catch (Exception $e) {
    	echo $date."Could not connect to the database.\n";
		exit;
	}

	try {
   	$sth = $db->query("SELECT * FROM nt_settings");
   	$sth->execute();
   	$result = $sth->fetchAll();
   	foreach ($result as $a) {
      	if($a['option']=='inflip') {
         	$influxdb_ip=$a['value'];
      	}
      	if($a['option']=='inflport') {
         	$influxdb_port=$a['value'];
      	}
      	if($a['option']=='inflon') {
         	$influxdb_on=$a['value'];
      	}
      	if($a['option']=='inflbase') {
         	$influxdb_base=$a['value'];
      	}
<<<<<<< HEAD
   	}
    
    	if(!empty($influxdb_ip)&&!empty($influxdb_port)&&!empty($influxdb_base)&&$influxdb_on=='on'){
		
			require $root."/other/composer/vendor/autoload.php";
   		$client = new InfluxDB\Client($influxdb_ip, $influxdb_port);
   		$database = $client->selectDB($influxdb_base);

         $value=floatval($s_value);
         
         
			if (isset($current) && is_numeric($current)) {
				$current=floatval($s_current);
				$points = [
	                 new InfluxDB\Point(
	                     'nt_'.$type, // the name of the measurement
	                     $value, // measurement value
	                     ['name' => $name, 'rom' => $rom], // measurement tags
	                     ['current' => $current], // additional measurement fields
	                     exec('date +%s%N') // timestamp in nanoseconds on Linux ONLY
	                 )
	             ];						
			}               
         else {
	         $points = [
	                 new InfluxDB\Point(
	                     'nt_'.$type, // the name of the measurement
	                     $value, // measurement value
	                     ['name' => $name, 'rom' => $rom], // measurement tags
	                     [], // additional measurement fields
	                     exec('date +%s%N') // timestamp in nanoseconds on Linux ONLY
	                 )
	             ];
	      }
	      $toinflux = $database->writePoints($points);
	      $to_send = false;
	      echo $name." to influx\n";
	      echo $toinflux;	
      }
=======
		if($a['option']=='inflbaseuser') {
			$influxdb_log=$a['value'];
		}
		if($a['option']=='inflbasepassword') {
			$influxdb_pass=$a['value'];
		}
   	}
    
    	if(!empty($influxdb_ip) && !empty($influxdb_port) && !empty($influxdb_base) && $influxdb_on == 'on'){
			
			$url = "http://$influxdb_ip:$influxdb_port/write?db=$influxdb_base";

         $value=floatval($s_value);
	    
			if (isset($s_current) && is_numeric($s_current)) {
				
				$points = "nt_$type,name=$name,rom=$rom current=$s_current,value=$value";	
			}               
			else {
				$points = "nt_$type,name=$name,rom=$rom value=$value";     
			}
		  
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,5);
			curl_setopt($ch, CURLOPT_TIMEOUT,10);
			
				if(!empty($influxdb_log) && !empty($influxdb_pass)){
					$auths = $influxdb_log . ':' . $influxdb_pass;
					curl_setopt($ch, CURLOPT_USERPWD,$auths);
				}
			
			curl_setopt($ch, CURLOPT_POSTFIELDS, $points);
			$server_output = curl_exec ($ch);
			$status   = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close ($ch);
			
			if ($status == 204) {
				logs(date("Y-m-d H:i:s"),'Info',$rom." - InfluxDB - Value sent - ".$value);
			} elseif ($status == 400) {
				logs(date("Y-m-d H:i:s"),'Error',$rom." - InfluxDB - Bad Request - ".$points);
			} elseif ($status == 401) {
				logs(date("Y-m-d H:i:s"),'Error',$rom." - Influxdb - Unauthorized.");
			} elseif ($status == 413) {
				logs(date("Y-m-d H:i:s"),'Error',$rom." - Influxdb - Request Entity Too Large.");
			} else {
				logs(date("Y-m-d H:i:s"),'Error',$rom." - InfluxDB -  Error sending data - ".$points);
			}
      } else {
		  logs(date("Y-m-d H:i:s"),'Error',"InfluxDB - Check  configuration. ");
	  }
>>>>>>> betamm-upstream
    } 
    catch (Exception $e) {
    	echo $date." Error.\n";
    	echo $e;
    	exit;
	 }   
}
  
  
?>