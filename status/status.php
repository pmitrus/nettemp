<style type="text/css">

* {
  -webkit-box-sizing: border-box;
          box-sizing: border-box;
}


/* ---- grid ---- */

.grid {
	<?php if($id=='screen') { ?>
   	width: 800px;
   <?php } ?>
}

/* clearfix */
.grid:after {
  content: '';
  display: block;
  clear: both;
}

/* ---- grid-item ---- */

.grid-item {
    width: 340px;
    float: left;
    border-radius: 5px;
}

</style>

<script src="html/justgage/raphael-2.1.4.min.js"></script>
<script src="html/justgage/justgage.js"></script>

<div class="grid">
    <div class="grid-sizer"></div>
    <?php
		$rows = $db->query("SELECT * FROM sensors");
$row = $rows->fetchAll();
$numRows = count($row);
if ($numRows == 0 ) { ?>
<div class="grid-item sg<?php echo $ch_g ?>">
<div class="panel panel-default">
<div class="panel-body">
Go to device scan!
<a href="index.php?id=device&type=scan" class="btn btn-success">GO!</a>
</div>
</div>
<?php
}

//Modules ORDER

 $morder = $db->query("SELECT * FROM statusorder ORDER BY position ASC") or header("Location: html/errors/db_error.php");
	$order = $morder->fetchAll();
	foreach($order as $or) {
		$module = $or['modulename'];
		
		if ($module == "Sensors") {
			 //GROUPS
			$rows = $db->query("SELECT ch_group FROM sensors ORDER BY position_group ASC") or header("Location: html/errors/db_error.php");
			$result_ch_g = $rows->fetchAll();
			$unique=array();
			$uniquea=array();
	
			foreach($result_ch_g as $uniq) {
				if(!empty($uniq['ch_group'])&&$uniq['ch_group']!='none'&&!in_array($uniq['ch_group'], $unique)) {
					$unique[]=$uniq['ch_group'];
					$ch_g=$uniq['ch_group'];
					include('status/sensor_groups.php');
				}
			}//END GROUPS
			
		}else if ($module == "Just Gage") {
			
			//JG GROUPS
			$rows = $db->query("SELECT ch_group FROM sensors ORDER BY position_group ASC") or header("Location: html/errors/db_error.php");
			$result_ch_g = $rows->fetchAll();
			//$unique=array();
			$uniquea=array();
			foreach($result_ch_g as $uniqa) {
				if(!empty($uniqa['ch_group'])&&$uniqa['ch_group']!='none'&&!in_array($uniqa['ch_group'], $uniquea)) {
					$uniquea[]=$uniqa['ch_group'];
					$ch_g=$uniqa['ch_group'];
					include('status/justgage_status.php');
				}
			}//END JG GROUPS

		}else if ($module == "MinMax") {
			include('status/minmax_status.php');
		}else  if ($module == "Counters") {
			include('status/counters_status.php');
		}else  if ($module == "Controls/GPIO") {
			include('status/controls.php');
		}else  if ($module == "Meteo") {
			include('status/meteo_status.php');
		}else  if ($module == "IP Cam") {
			include('status/ipcam_status.php');
		}else  if ($module == "UPS") {
			include('status/ups_status.php');
		}else  if ($module == "Widget") {
			//OW
			$rowsow = $db->query("SELECT * FROM ownwidget WHERE onoff='on' ") or header("Location: html/errors/db_error.php");
			$owresult = $rowsow->fetchAll();
			$uniquec=array();
				foreach($owresult as $owg) {
					$owb = $owg['body'];
					$own = $owg['name'];
					$owh = $owg['hide'];
					//$ref = $owg['refresh'];
					include('status/ownwidget.php');
				}
		}
	}
?>
</div>

<script type="text/javascript">
    setInterval( function() {
	
	<?php	
	
	$refr = $db->query("SELECT value FROM nt_settings WHERE option = 'refreshcount'") or header("Location: html/errors/db_error.php");
	$ref = $refr->fetchAll();
	foreach($ref as $ref2) {
	
	$reff = $ref2['value'];
	}
	
	if ($reff != 0 ) {
	
		foreach ($unique as $key => $ch_g) { 
	?>
		$('.sg<?php echo $ch_g?>').load("status/sensor_groups.php?ch_g=<?php echo $ch_g?>");
	<?php
		}
	?>
	
	<?php
		foreach ($uniquea as $key => $ch_g) { 
	?>
		$('#justgage_refresh').load("status/justgage_refresh.php?ch_g=<?php echo $ch_g?>");
	<?php
		}
	?>
	
	<?php
		foreach ($owresult as $owg) { 
		if ($owg['refresh'] == 'on') {
	?>
		$('.ow<?php echo $owg['body']?>').load("status/ownwidget.php?owb=<?php echo $owg['body'];?>&own=<?php echo $owg['name'];?>&hide=<?php echo $owg['hide'];?>");
	<?php
		}
		}
	?>
	
    $('.co').load("status/counters_status.php");
    $('.ms').load("status/meteo_status.php");
    $('.mm').load("status/minmax_status.php");
    $('.ups').load("status/ups_status.php");
    $('.swcon').load("status/controls.php", function() {		
	$('[id="onoffstatus"]').bootstrapToggle({size : 'mini', off : 'Off', on : 'On',});
	$('[id="lockstatus"]').bootstrapToggle({size : 'mini', off : 'lock', on : 'lock',});	
	});	

	
	<?php
	$db->exec("UPDATE nt_settings SET value = '0'  WHERE option='refreshcount'") or die (date("Y-m-d H:i:s")." ERROR: Cannot insert count to table\n" );
	}
	?>
}, 10000);

$(document).ready( function() {

  $('.grid').masonry({
    itemSelector: '.grid-item',
    columnWidth: 350,
  });

  
});
</script>
<script src="html/masonry/masonry.pkgd.min.js"></script>
<div id="justgage_refresh"></div>



