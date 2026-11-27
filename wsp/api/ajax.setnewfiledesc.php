<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version 6.8
 */

if (!empty($_SERVER['HTTP_REFERER'] ?? null)):
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));

	require $wspdir.'/data/include/globalvars.inc.php';
	require $wspdir.'/data/include/wsplang.inc.php';
	require $wspdir.'/data/include/funcs.inc.php';
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";
	require $wspdir."/data/include/dbaccess.inc.php";

	$medialist = $_SESSION['xajaxmedialist'];
	$fileid = (!empty($_POST['fileid'] ?? null)) ? $_POST['fileid'] : '';
	$newdesc = (!empty($_POST['newdesc'] ?? '')) ? $_POST['newdesc'] : '';

	if($fileid!=""):
		$dir = $_SESSION['xajaxmedialist'][$fileid]['directory'];
		$file = $_SESSION['xajaxmedialist'][$fileid]['file'];
		$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` = '".escapeSQL($dir.$file)."'";
		$desc_res = doSQL($desc_sql);

		if (trim($newdesc)==trim($file)): $newdesc = ''; endif;

		if ($desc_res['num']>0):
			$sql = "UPDATE `mediadesc` SET `filedesc` = '".escapeSQL(trim($newdesc))."' WHERE `mediafile` ='".escapeSQL($dir.$file)."'";
		else:
			$sql = "INSERT INTO `mediadesc` SET `filedesc` = '".escapeSQL(trim($newdesc))."', `mediafile` = '".escapeSQL($dir.$file)."'";
		endif;
		$res = doSQL($sql);
		if ($res['res']):
			if(trim($newdesc)!="" && trim($newdesc)!=$file):
				echo "<em>".trim($newdesc)."</em>";
			else:
				echo $file;
			endif;
		else:
			addWSPMsg('errormsg', 'error setting mediadesc for '.$file.'');
		endif;
	endif;
endif;
