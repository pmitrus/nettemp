<?php 
if (isset($_GET['ch_g'])) { 
    $ch_g = $_GET['ch_g'];
} 

$root=$_SERVER["DOCUMENT_ROOT"];
$db = new PDO("sqlite:$root/dbf/nettemp.db") or die ("cannot open database");
//check if display normalized in jg
$normalized=-1;
$sth_norma = $db->prepare("select * from meteo");
$sth_norma->execute();
$result_norma = $sth_norma->fetchAll();	
if ($result_norma[0]['jg']=='on')
{
	$normalized=$result_norma[0]['pressure'];
}

if(($_SESSION["perms"] == 'adm') || (isset($_SESSION["user"]))) {

	$sth = $db->prepare("select *,'off' as 'normalized' from sensors where jg='on' AND hide='off' AND ch_group=='$ch_g' UNION ALL select *,'on' as 'normalized' from sensors WHERE id=$normalized AND ch_group=='$ch_g' ORDER BY position ASC,id");

} else { 

	$sth = $db->prepare("select *,'off' as 'normalized' from sensors where jg='on' AND hide='off' AND logon =='on' AND ch_group=='$ch_g' UNION ALL select *,'on' as 'normalized' from sensors WHERE id=$normalized AND ch_group=='$ch_g' ORDER BY position ASC,id");
}

	$sth->execute();
$result = $sth->fetchAll();
$numRows = count($result);
if ( $numRows > '0' ) { ?>

<div class="grid-item sgjg<?php echo $ch_g ?>">
<div class="panel panel-default">
<div class="panel-heading">




<?php

	if(isset($_SESSION['user'])){
			$ch_g2 = '<a href="index.php?id=device&type=device&device_group='.$ch_g.'&device_menu=settings" title="Go to group settings" class="group-name" >'.str_replace("_", " ", $ch_g).'</a>';
			echo $ch_g2;
			} else {
					echo str_replace("_", " ", $ch_g);
			}
	?>
</div>
<div class="panel-body">


<?php
$query = $db->query("SELECT * FROM types");
$result_t = $query->fetchAll();

$KtoryWidget = 1;
foreach ($result as $a) { 
//$type='';
$name='';
$time='';
$valfoncol='';
$titfoncol='';
$err='';

// value colours - alarm colours

$type=$a['type'];
		
		if ($type == 'elec' || $type == 'water' || $type == 'gas' ) {
			$tmpval=$a['current'];
		}else {
			$tmpval=$a['tmp'];
		}

if($tmpval >= $a['tmp_max'] && !empty($tmpval) && !empty($a['tmp_max'])) { 
		    $valfoncol='#d9534f'; 
		} elseif($tmpval <= $a['tmp_min'] && !empty($tmpval) && !empty($a['tmp_min'])) { 
		    $valfoncol='#5bc0de'; 
		} elseif ($nts_theme == 'Dark') {$valfoncol='#c8c8c8'; 
		} else {$valfoncol='black'; }
		
// title colours - read colours
					
				    if (($a['tmp'] == 'error') || ($a['status'] == 'error')){
					$titfoncol='#d9534f'; 
					$err='1';
				    } elseif (!empty($a['readerrsend']) || (strtotime($a['time'])<(time()-($a['readerrtime']*60)) && $a['readerrtime'] != '0')){
					$titfoncol='#f0ad4e'; 
					$err='1';
				    }else{
					$titfoncol='#8c8c8c'; 
				    }		

if ($a['normalized']=='on')
{
	$a['name']=$a['name'].' npm';
	$a['type']='normalized';
	require_once('Meteo.class.php');
	$meteo=new Meteo();
	$a['tmp']=number_format($meteo->getCisnienieZnormalizowane(),2,'.','');
}
?>

<a href="index.php?id=view&type=<?php echo $a['type']?>&max=<?php echo $nts_charts_max ?>&single=<?php echo $a['name']?>" title="Go to charts, last update: <?php echo $a['time']?>" class="btn btn-link">
<div id="<?php echo $ch_g.$a['name']?>" style="margin:-12px; width:95px; height:95px; display:inline-block;"></div>
</a>

<script>
<?php

foreach($result_t as $ty){
       	if($ty['type']==$a['type']) {
       		if(($nts_temp_scale != 'C')&&($a['type']=='temp')){
       			echo "var n_units = '". $ty['unit2'] ."';\n"; 
       		} 
       		else {
				echo "var n_units = '". $ty['unit'] ."';\n"; 
       		}
        	}   
		}

?>

var g<?php echo $ch_g?><?=$KtoryWidget++?> = new JustGage({
        id: "<?php echo $ch_g.$a['name']?>",
        valueFontColor: "<?php echo $valfoncol ?>",
		value: <?php 
        				if($a['type']=='elec') {
							echo $a['current'];
        				}
        				else if($a['tmp']=='error') { 
        						echo '0'; 
        					} 
        						else { 
        								echo $a['tmp'];
        								}
        			?>,

		<?php if(isset($a['jg_min']) && ($a['jg_min'] !='') ) {echo "min:".$a['jg_min'].",";} else {echo "min:0,";} ?>
		<?php if(isset($a['jg_max']) && ($a['jg_max'] !='') ) {echo "max:".$a['jg_max'].",";} else {echo "max:100,";}?>
        titleFontColor: "<?php echo $titfoncol ?>",
		title: "<?php if ($err=='1') {echo "!! ".str_replace("_", " ", $a['name'])." !!";} else {echo str_replace("_", " ", $a['name']);}?>",
        label: n_units
	
      });
	  
</script>


<?php
 }
?>
</div>
</div>
</div>
<?php }  ?>
