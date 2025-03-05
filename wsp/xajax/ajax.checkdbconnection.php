<?php
/**
 * ...
 * @author stefan@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-01-22
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

if (isset($_POST['conhost']) && trim($_POST['conhost'])!='' && isset($_POST['conlocation']) && trim($_POST['conlocation'])!='' && isset($_POST['conuser']) && trim($_POST['conuser'])!='' && isset($_POST['conpass']) && trim($_POST['conpass'])!=''):
	/*
	if (mysql_ping()==1): mysql_close(); endif;
	
	$dbtestcon = @mysql_connect(trim($_POST['conhost']),trim($_POST['conuser']),trim($_POST['conpass']));
	$dbtestacc = @mysql_select_db(trim($_POST['conlocation']), $dbtestcon);
	if ($dbtestacc==1):
		$test_sql = "SELECT `rid` FROM `restrictions` LIMIT 0,1";
		$test_res = mysql_query($test_sql);
		$test_num = 0; if ($test_res): $test_num = mysql_num_rows($test_res); endif;
		if ($test_num>0):
			echo 99;
		else:
			echo 2;
		endif;
	else:
		echo 1;
	endif;
    */
    
    // deprecated below 7.0
    echo 1;

endif;

endif;

// EOF ?>