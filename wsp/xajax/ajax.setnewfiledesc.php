<?php
/**
 * ...
 * @author stefan@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-01-22
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php';
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";

$medialist = $_SESSION['xajaxmedialist'];

if (isset($_POST) && isset($_POST['fileid'])):
	$fileid = $_POST['fileid'];
endif;
if (isset($_POST) && isset($_POST['newdesc'])):
	$newdesc = $_POST['newdesc'];
endif;

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
// EOF ?>