<?php
$ROOT=dirname(dirname(dirname(__FILE__)));

$date = date("Y-m-d H:i:s"); 
$hostname=gethostname(); 

try {
    $db = new PDO("sqlite:$ROOT/dbf/nettemp.db");
    $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
} 
catch (Exception $e) {
    echo $date." Could not connect to the database.\n";
    exit;
}

try {
	
	$query = $db->query("SELECT * FROM sensors WHERE influxdb='on'");
	$result = $query->fetchAll();
	
	$arraycounters = array("gas", "water", "elec", "elecesp");	
	require $ROOT."/common/influx_sender.php";	
	
	foreach($result as $s) {

		$id=intval($s['id']);
		$name=$s['name'];
		$value=$s['tmp'];
		$type=$s['type'];
		$current=$s['current'];
		$rom = $s['rom'];
		$to_send = false;
		
		if(in_array($type, $arraycounters)) {
			//do nothing
		}
		else {			
			//zapytanie do bazy influx o dany sensor
			$inflquery = $db->query("SELECT * FROM influxdb WHERE id='$id'");
			$inflresult = $inflquery->fetch();
			if(! $inflresult){
				$db->exec("INSERT INTO influxdb (id,name,sent_value) VALUES ('$id','$name','$value' )") or die ("cannot insert to influxdb\n" );	
 				sendInflux($value, $current, $rom, $name,$type);
			}
			else{
				$db->exec("UPDATE influxdb SET sent_value='$value', time='$date'  WHERE id='$id'") or die ("cannot update to influxdb\n" );
				if(floatval($inflresult['sent_value'])!=$value) { 
					sendInflux($value, $current, $rom, $name,$type);
				}
			}	
		}				
	}


} catch (Exception $e) {
    echo $date." Error.\n";
    echo $e;
    exit;
}
?>

