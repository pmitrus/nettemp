<?php

session_start();
	   include('modules/login/login_check.php');
		if ($numRows1 == 1 && ($perms == "adm" )) {?> 



<?php include("diag_db_show.php"); ?>
<?php include("diag_file_check.php"); ?>




<?php
 }
else { 
  	  header("Location: diened");
    }; 

