<?php

include("common/functions.php");

// name:
// type: temp, humid, relay, lux, press, humid, gas, water, elec, volt, amps, watt, trigger
// device: ip, wireless, remote, gpio, i2c, usb

// definied source (middle part): tty, ip, gpio number

// curl --connect-timeout 3 -G "http://172.18.10.10/receiver.php" -d "value=1&key=123456&device=wireless&type=gas&ip=172.18.10.9"
// curl --connect-timeout 3 -G "http://172.18.10.10/receiver.php" -d "value=20&key=123456&device=wireless&type=elec&ip=172.18.10.9"
// php-cgi -f receiver.php key=123456 rom=new_12_temp value=23

if (isset($_GET['key'])) { 
    $key = $_GET['key'];
} else { 
    $key='';
}

if (isset($_GET['token'])) { 
    $token = $_GET['token'];
} else { 
    $token='';
}

if (isset($_GET['value'])) {
    $val = $_GET['value'];
} else { 
    $val='';
}

if (isset($_GET['rom'])) {
    $rom = $_GET['rom'];
}

if (isset($_GET['ip'])) {
    $ip = $_GET['ip'];
} else {
    $ip='';
}

if (isset($_GET['type'])) {
    $type = $_GET['type'];

} 
    
if (isset($_GET['gpio'])) {
    $gpio = $_GET['gpio'];
} else {
    $gpio='';
}

if (isset($_GET['device'])) {
    $device = $_GET['device'];
} else {
    $device='';
}

if (isset($_GET['i2c'])) { 
    $i2c = $_GET['i2c'];
} else {
    $i2c='';
}

if (isset($_GET['usb'])) { 
    $usb = $_GET['usb'];
} else {
    $usb='';
}

if (isset($_GET['current'])){
    $current = $_GET['current'];
} else {
    $current='';
}

if (isset($_GET['id'])){
    $id = $_GET['id'];
} else {
    $id='';
}

if (isset($_GET['name'])){
    $name = $_GET['name'];
} else {
    $name='';
}

$local_rom='';
$local_type='';
$local_val='';
$local_device='';
$local_i2c='';
$local_current='';
$local_name='';
$local_ip='';
$local_gpio='';
$local_usb='';


$dbr = new PDO("sqlite:".__DIR__."/dbf/nettemp.db") or die ("cannot open database");

$sth = $dbr->query("SELECT * FROM nt_settings");
$sth->execute();
$result = $sth->fetchAll();
foreach ($result as $a) {
	if($a['option']=='temp_scale') {
		$scale=$a['value'];
	}
	if($a['option']=='server_key') {
		$skey=$a['value'];
	}
	if($a['option']=='charts_min') {
		$chmin=$a['value'];
	}
	if($a['option']=='mail_topic') {
		$mail_topic=$a['value'];
	}
	if($a['option']=='inflon') {
		$influxon=$a['value'];
	}
}
if ($influxon == 'on') {
	include("common/influx_sender.php");
}


function scale($val,$type) {
	global $scale;
	// scale F->C
	if($scale=='F' && $type=='temp') {
		$val=$val*1.8+32;
		return "$val";
	} else {
		return "$val";
	}
	//$sthr=null;
    //$dbr=null;
}

function trigger($rom, $val) {
	$dbr = new PDO("sqlite:".__DIR__."/dbf/nettemp.db") or die ("cannot open database");
    $sthr = $dbr->query("SELECT mail, tel FROM users WHERE maila='yes' OR smsa='yes'");
    $row = $sthr->fetchAll();
    foreach($row as $row) {
		$mailto[]=$row['mail'];   
		$smsto[]=$row['tel'];
    }
    $sthr = $dbr->query("SELECT name, tmp, ssms, smail, script, script1 FROM sensors WHERE rom='$rom'");
    $row = $sthr->fetchAll();
    foreach($row as $row) {
		$name = $row['name'];   
		$oldval = $row['tmp'];
		$sms = $row['ssms'];
		$mail = $row['smail'];
		$pscript = $row['script'];
		$pscript1 = $row['script1'];
    }
	$mailto = implode(', ', $mailto);
	
	// from 0 to 1
	if ($val > $oldval) {
				
		if ($sms == 'on') {
			
			for ($x = 0, $cnt = count($smsto); $x < $cnt; $x++){
			$random=substr(rand(), 0, 4);
			$date = date('H:i:s');
			$msg = $date." - ".$name." - ALARM";
			$sms = "To: ".$smsto[$x]."\n\n".$msg;
			$filepath = "tmp/sms/message_".$date."_".$random.".sms";
			$fsms = fopen($filepath, 'a+');
			fwrite($fsms, $sms);
			fclose($fsms);
			$ftosend = "/var/spool/sms/outgoing/message_".$date."_".$random.".sms";
			
			if (!copy($filepath, $ftosend)) {
			echo "Send failed.\n";
			} else {
				echo "Send OK.\n";
			}
			unlink($filepath);
			logs(date("Y-m-d H:i:s"),'Info',$name." - !!! ALARM !!!");
			}
		}
		if ($mail == 'on') {
			$topic = "Trigger ALARM info from nettemp";
			mail($mailto, $topic, "Trigger: $name *** ALARM ***" );
			
			}
		if (!empty($pscript)) {
			shell_exec(__DIR__."/scripts/$pscript");
		}	
	// from 1 to 0	
	}elseif ($val < $oldval) {
		
		if ($sms == 'on') {
			
			for ($x = 0, $cnt = count($smsto); $x < $cnt; $x++){
			$random=substr(rand(), 0, 4);
			$date = date('H:i:s');
			$msg = $date." - ".$name." - RECOVERY";
			$sms = "To: ".$smsto[$x]."\n\n".$msg;
			$filepath = "tmp/sms/message_".$date."_".$random.".sms";
			$fsms = fopen($filepath, 'a+');
			fwrite($fsms, $sms);
			fclose($fsms);
			$ftosend = "/var/spool/sms/outgoing/message_".$date."_".$random.".sms";
	
			if (!copy($filepath, $ftosend)) {
			echo "Send failed.\n";
			} else {
				echo "Send OK.\n";
			}
			unlink($filepath);
			logs(date("Y-m-d H:i:s"),'Info',$name." - *** RECOVERY ***");
			}
		}
		if ($mail == 'on') {
			$topic = "Trigger RECOVERY info from nettemp";
			mail($mailto, $topic, "Trigger: $name *** Recovery ***" );
			
			}
		if (!empty($pscript1)) {
			shell_exec(__DIR__."/scripts/$pscript1");
		}
	}
}

function check($val,$type) {
	$dbr = new PDO("sqlite:".__DIR__."/dbf/nettemp.db") or die ("cannot open database");
	$sthr = $dbr->query("SELECT * FROM types WHERE type='$type'");
    $row = $sthr->fetchAll();
    foreach($row as $range) 
    {
		if (($range['min'] <= $val) && ($val <= $range['max']) && ($val != $range['value1']) && ($val != $range['value2']) && ($val != $range['value3'])) 
		{
			return "$val";
		}
		else 
		{
			return 'range';
		}
	}
}



function db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name){
	$file = "$rom.sql";
	global $chmin;
	global $influxon;
	$dbr = new PDO("sqlite:".__DIR__."/dbf/nettemp.db") or die ("cannot open database");
	if(file_exists(__DIR__."/db/".$file)&&filesize(__DIR__."/db/".$file)!=0){
		$dbfr = new PDO("sqlite:".__DIR__."/db/$file");
		$sthr = $dbr->query("SELECT stat_min,stat_max,rom,adj,tobase,influxdb,name  FROM sensors WHERE rom='$rom'");
		$row = $sthr->fetchAll();
		foreach($row as $row) {
			$adj=$row['adj']; 
			$stat_min=$row['stat_min'];
			$stat_max=$row['stat_max'];
			$to_base=$row['tobase'];
			$to_influx=$row['influxdb'];
			$iname=$row['name'];
			
			if ($type != 'host'){
				$val=$val+$adj;  
			}
		}	
		
		$c = count($row);
		if ( $c >= "1") {
			if (is_numeric($val)) {
				$val=scale($val,$type);
				$val=check($val,$type);
				if ($val != 'range'){
					//// base
					// counters and other dwvices in array can always put to base

					$arrayt = array("gas", "water", "elec", "elecesp", "trigger" );
					$arraycounters = array("gas", "water", "elec", "elecesp");
					
					$arrayd = array("wireless", "gpio", "usb", "ip", "ip_mqtt", "system");
					if (in_array($type, $arrayt) &&  in_array($device, $arrayd)) {
					
													if  ($type == 'elecesp') {
															$query = $dbr->query("SELECT sum FROM sensors WHERE rom='$rom'");
															$result = $query->fetchAll();
															foreach ($result as $esp) {
															$last=trim($esp['sum']);
															echo $rom." - Last ".$last." \n";
															}
															$espval = trim(round($val-$last,3));
															echo $rom." - ESPVAL ".$espval." \n";
															$val = $espval;
															
															echo $rom." - VAL_po ".$val." \n";
															
														}
						
							if (isset($current) && is_numeric($current)) {
								
								if ($to_base == 'on'){
								
									$dbfr->exec("INSERT OR IGNORE INTO def (value,current) VALUES ('$val','$current')") or die ("cannot insert to rom sql current\n" );	
								}
								
								if ($to_influx == 'on' && $influxon == 'on'){				
									sendInflux($val, $current, $rom, $iname, $type);
									
								}
								
								$dbr->exec("UPDATE sensors SET current='$current' WHERE rom='$rom'") or die ("cannot insert to current\n" );
								
								echo $rom." - Current value for counter updated ".$current." \n";
								logs(date("Y-m-d H:i:s"),'Info',$rom." - Current value for counter updated - ".$current);
								
							} else {
								
								if ($to_base == 'on'){
									
									$dbfr->exec("INSERT OR IGNORE INTO def (value) VALUES ('$val')") or die ("cannot insert to rom sql\n" );
									logs(date("Y-m-d H:i:s"),'Info',$rom." - Value in base updated - ".$val);
								}
								
								if ($to_influx == 'on' && $influxon == 'on'){				
									
									sendInflux($val, $current, $rom, $iname, $type);
								}
							}
							
<<<<<<< HEAD
							if ($to_influx == 'on'){				
									require "common/influx_sender.php";
									sendInflux($val, $current, $rom, $iname, $type);
									logs(date("Y-m-d H:i:s"),'Info',$rom." - Value sent to influx - ".$val);
								}							
=======
														
>>>>>>> betamm-upstream
							
							//sum,current for counters
							if (in_array($type, $arraycounters)){
								$dbr->exec("UPDATE sensors SET sum='$val'+sum WHERE rom='$rom'") or die ("cannot insert to status\n" );
								logs(date("Y-m-d H:i:s"),'Info',$rom." - Summary value for counter updated");
							}
						
					}// tutaj koniec in array*********************************************************
					
					
					// time when you can put into base
					elseif ((date('i', time())%$chmin==0) || (date('i', time())==00))  {
						
						if ($to_base == 'on'){
						
							$dbfr->exec("INSERT OR IGNORE INTO def (value) VALUES ('$val')") or die (date("Y-m-d H:i:s")." ERROR: Cannot insert to rom sql, time\n");
							echo date("Y-m-d H:i:s")." ".$rom." ".$val." Value in base updated \n";
							logs(date("Y-m-d H:i:s"),'Info',$rom." - Value in base updated - ".$val);
						}
						
						if ($to_influx == 'on' && $influxon == 'on'){				
							
							sendInflux($val, $current, $rom, $iname, $type);
						}
					}
					else {
						echo "Not writed to base, interval is ".$chmin." min\n";
						logs(date("Y-m-d H:i:s"),'Info',$rom." - Not writed to base, interval is ".$chmin." min");
					}
		    
					// 5ago arrow
					$min=intval(date('i'));
					if ((strpos($min,'0') !== false) || (strpos($min,'5') !== false)) {
						$dbr->exec("UPDATE sensors SET tmp_5ago='$val' WHERE rom='$rom'") or die ("cannot insert to 5ago\n" );
					}
		
					if ($type == 'trigger') {
						
						trigger($rom,$val);
						$dbr->exec("UPDATE sensors SET tmp='$val', ip='$ip' WHERE rom='$rom'") or die ("cannot insert to trigger status2\n");
						logs(date("Y-m-d H:i:s"),'Info',$rom." - Value updated trigger - ".$val);
						
					}
					//sensors status and GPIO status
					else {
						$dbr->exec("UPDATE sensors SET tmp='$val', status='ok', ip='$ip' WHERE rom='$rom'") or die (date("Y-m-d H:i:s")." ERROR: Cannot insert value to status\n" );
						echo $rom." Value updated to sensors\n";
						logs(date("Y-m-d H:i:s"),'Info',$rom." - Value in sensors updated - ".$val);
						
						//minmax light
						if ($val<$stat_min || empty($stat_min)) {$dbr->exec("UPDATE sensors SET stat_min='$val' WHERE rom='$rom'");
						} elseif ($val>$stat_max || empty($stat_max)) {$dbr->exec("UPDATE sensors SET stat_max='$val' WHERE rom='$rom'");}
						
						if($type == 'gpio') {
							
							if(!is_null($ip)) {
								$dbr->exec("UPDATE gpio SET ip='$ip' WHERE rom='$rom'") or die (date("Y-m-d H:i:s")." ERROR: Cannot insert IP to gpio\n" );
							}
							
							if($val == '1.0' || $val == '1' ) {
								$dbr->exec("UPDATE gpio SET status='ON' WHERE rom='$rom'") or die (date("Y-m-d H:i:s")." ERROR: Cannot insert status to gpio\n" );
							} elseif($val == '0.0' || $val == '0') {
								$dbr->exec("UPDATE gpio SET status='OFF' WHERE rom='$rom'") or die (date("Y-m-d H:i:s")." ERROR: Cannot insert status to gpio\n" );
							}
						}
						
						}
					
				}		
				else {
					echo $rom." ".$val." - Value not in range \n";
					logs(date("Y-m-d H:i:s"),'Error',$rom." - Value not in range");
				}
		
			}
			// if not numeric
			else {
				if($device!='gpio' && $type!='gpio'){
					$dbr->exec("UPDATE sensors SET status='error', tmp = 0 WHERE rom='$rom'") or die (date("Y-m-d H:i:s")." ERROR: Cannot insert status to sensors ".$rom.", not numeric\n");
					
					if ($to_base == 'on'){
						
						$dbfr->exec("INSERT OR IGNORE INTO def (value) VALUES ('0')") or die (date("Y-m-d H:i:s")." ERROR: Cannot insert to rom DB ".$rom.", not numeric\n");
						echo date("Y-m-d H:i:s")." Puting value \"".$val."\" to ".$rom.", but value is not numieric!, inserting 0 to db\n";
						logs(date("Y-m-d H:i:s"),'Info',$rom." - Value is not numeric - inserting 0 to database ");
					}
				}
			}
		}
		//if not in sensors table
		else {
			if  ($type == 'elecesp') {
				$type = 'elec';
				}
			$name=substr(rand(), 0, 4);
			$dbr->exec("INSERT OR IGNORE INTO newdev (rom,type,device,ip,gpio,i2c,usb,name) VALUES ('$rom','$type','$device','$ip','$gpio','$i2c','$usb','$name')");
			echo "Database exist. Added ".$rom." to new sensors \n";
			//logs(date("Y-m-d H:i:s"),'Info',$rom." - Database exist - Added to new sensors ");
		}
	}
	//if base not exist
	else {
		if  ($type == 'elecesp') {
			$type = 'elec';
			}
		$name=substr(rand(), 0, 4);
		$dbr->exec("INSERT OR IGNORE INTO newdev (rom,type,device,ip,gpio,i2c,usb,name) VALUES ('$rom','$type','$device','$ip','$gpio','$i2c','$usb','$name')");
		echo "Database not exist. Added ".$rom." to new sensors \n";
		//logs(date("Y-m-d H:i:s"),'Info',$rom." - Database not exist - Added to new sensors ");
	}
	
	$dbr->exec("UPDATE nt_settings SET value = value + 1  WHERE option='refreshcount'") or die (date("Y-m-d H:i:s")." ERROR: Cannot insert count to table\n" );
	//logs(date("Y-m-d H:i:s"),'Receiver','test');
	//$sthr=null;
	//$dbr=null;
	//$dbfr=null;
} 



if (("$key" != "$skey") && (!defined('LOCAL')))
{
    echo "wrong key\n";
	logs(date("Y-m-d H:i:s"),'Error',"Receiver - wrong key - ".$key.$rom);
} 
else {

//MAIN
//Local devices have always rom
if(isset($val) && isset($rom) && isset($type))
{
	db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
}
elseif (isset($val) && isset($type)) 
{
	//MULTI ID
	// receiver.php?device=ip&ip=172.18.10.102&key=q1w2e3r4&id=5;6;7&type=temp;humid;press&value=0.00;0.00;0.00
	if (strpos($type, ';') !== false && strpos($id, ';') !== false) 
	{
		$aid = array_filter(explode(';', $id),'strlen');
		$atype = array_filter(explode(';', $type),'strlen');
		$aval = array_filter(explode(';', $val),'strlen');
		foreach($aid as $index => $id) {
			$type=$atype[$index];
			$val=$aval[$index];
			if(empty($id)){
				echo "One id is not definied in multi id mode, name ".$name.", type ".$type.", val ".$val."\n";
				continue;
			}
			if(empty($type)){
				echo "One type is not definied in multi id mode, name ".$name.", id ".$id.", val ".$val."\n";
				continue;
			}
			if(!is_numeric($val)){
				echo "No val definied in multi id mode, name ".$name.", type ".$type." id ".$id.", type ".$type."\n";
				continue;
			}			
			$rom=$device."_".$name."id".$id."_".$type; 
			db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
		}
		
	}
	//MULTI TYPE
	// receiver.php?name=unit1&key=q1w2e3r4&type=temp;humid;press&value=0.00;0.00;0.00
	elseif (strpos($type, ';') !== false && empty($id)) 
	{
		$atype = array_filter(explode(';', $type),'strlen');
		$aval = array_filter(explode(';', $val),'strlen');
		
		if(empty($atype)) {
			echo "No type definied in one id mode, name ".$name.", id ".$id."\n";
			exit;
		}
		foreach($atype as $index => $typel) {
			$type=$typel;
			$val=$aval[$index];
			if(empty($type)){
				echo "One type is not definied in multi id mode, name ".$name.", id ".$id.", val ".$val."\n";
				continue;
			}
			if(!is_numeric($val)){
				echo "No val definied in multi id mode, name ".$name.", id ".$id.", type ".$type."\n";
				continue;
			}

			$rom=$device."_".$name."_".$type; 
			db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
		} 
	}
	// ONE ID 
	// type is more important than id, type equal value
	// receiver.php?name=unit1&key=q1w2e3r4&id=5&type=temp;humid;press&value=0.00;0.00;0.00
	elseif (!empty($id)&&!empty($name)) 
	{
		$atype = array_filter(explode(';', $type),'strlen');
		$aval = array_filter(explode(';', $val),'strlen');
		if(empty($atype)) {
			echo "No type definied in one id mode, name ".$name.", id ".$id."\n";
			exit;
		}
		foreach($atype as $index => $typel) {
			$type=$typel;
			$val=$aval[$index];
			if(empty($type)){
				echo "One type is not definied in one id mode, name ".$name.", id ".$id.", val $val\n";
				continue;
			}
			if(!is_numeric($val)){
				echo "No val definied in one id mode, name ".$name.", id ".$id.", type ".$type."\n";
				continue;
			}
			$rom=$device.'_'.$name.'id'.$id.'_'.$type; 
			db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
		} 
	}
	// ONE TYPE	
	// receiver.php?device=ip&ip=172.18.10.102&key=q1w2e3r4&type=temp&value=0.00
	elseif ( $device == "i2c" ) 
	{ 
	    if (!empty($type) && !empty($i2c)) 
	    {
			$rom=$device.'_'.$i2c.'_'.$type;
			db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
	    } else 
	    {
			echo "Missing type or i2c number";
			exit();
	    }	
	}
	elseif ( $device == "gpio" ) 
	{ 
	    if (!empty($type) && !empty($gpio)) {
			$rom=$device.'_'.$gpio; 
			db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
	    } else {
			echo "Missing type or gpio number";
			exit();
	    }
	}
	elseif ( $device == "usb" ) 
	{
	    if (!empty($type) && !empty($usb)) 
	    {
			$rom=$device.'_'.$usb.'_'.$type; 
			db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
	    } else {
			echo "Missing type or USB";
			exit();
	    }
	}
	elseif ( $device == "wireless" ) 
	{
	    if (!empty($type) && !empty($ip)) {
			$rom=$device.'_'.$ip.'_'.$type; 
			db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
	    } else 
	    {
			echo "Missing type or IP";
			exit();
	    }
	}
	elseif ( $device == "ip" ) 
	{
	    if (empty($type)){ echo "Missing type"; exit();}
	    if (empty($device)){ echo "Missing device"; exit();}
	    if (empty($name)){ echo "Missing name"; exit();}
	    $rom=$device.'_'.$name.'_'.$type;
	    db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
	}
	else 
	{
		 db($rom,$val,$type,$device,$current,$ip,$gpio,$i2c,$usb,$name);
	} 
	

}
elseif (!defined('LOCAL')) {
    echo "no data\n";
    } 

} //end main
?>

