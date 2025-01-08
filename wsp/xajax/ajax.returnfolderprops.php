<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2018-09-18
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/filesystemfuncs.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

$usefolder = '';
foreach ($_SESSION['wspvars']['xajaxactmediastructure'] AS $fk => $fv):
	if (urltext(str_replace("/", "-", $fv))==$_POST['fread']):
		$usefolder = $fv;
	endif;
endforeach;

$filelist = array();
$uselist = array();
$subdir = false;
if ($usefolder!=''):
	$fsysfolder = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$usefolder));
	$folderstat = opendir($fsysfolder);
	$foldercount = readdir($folderstat);
	$countfiles = 0;
	while ($file = readdir ( $folderstat )):
		if (substr($file,0,1)!='.' && is_file($fsysfolder."/".$file)):
			$filelist[] = trim($file);
			if (fileinuse($usefolder, trim($file))):
				$uselist[] = trim($file);
			endif;
		endif;
		if (substr($file,0,1)!='.' && is_dir($fsysfolder."/".$file)):
			$subdir = true;
		endif;
	endwhile;
	closedir($folderstat);
endif;

if (count($uselist)>0 || $subdir):
	if (count($filelist)==count($uselist) || $subdir):
		// all files are in use
		echo "2";
	else:
		// some files are in use
		echo "1";
	endif;
else:
	echo "0";
endif;

endif;
// EOF ?>
