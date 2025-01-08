<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
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

if (isset($_REQUEST['newshortcut']) && trim($_REQUEST['newshortcut'])!=''):
	$newshortcut = strtolower(removeSpecialChar(trim($_REQUEST['newshortcut'])));
	$fcheck_sql = "SELECT `filename` FROM `menu` WHERE `linktoshortcut` LIKE '".escapeSQL(trim($newshortcut))."' AND `trash` = 0";
	$fcheck_res = doSQL($fcheck_sql);
	// list of reserved names
	$reservednames = array('index','wsp','data','media');
	if (is_array($_SESSION['wspvars']) && array_key_exists('publisherdata',$_SESSION['wspvars']) && array_key_exists('nonames',$_SESSION['wspvars']['publisherdata']) && trim($_SESSION['wspvars']['publisherdata']['nonames'])!=''):
		$nonames = explode(";",str_replace(";;", ";", str_replace(";;", ";", str_replace("\r", ";", str_replace("\n", ";", $_SESSION['wspvars']['publisherdata']['nonames'])))));
		if (is_array($nonames)):
			$reservednames = array_merge($reservednames,$nonames);
		endif;
	endif;
	$reservednames = array_unique($reservednames);
	if (trim($newshortcut)==''):
		echo returnIntLang('menuedit name cannot be empty', false)."#;#file".date("ymdHis");
	elseif ($newshortcut!=trim($_REQUEST['newshortcut'])):
		echo returnIntLang('menuedit shortcut changed to unix', false)."#;#".$newshortcut;
	elseif (in_array(trim($_REQUEST['newshortcut']),$reservednames)):
		echo "'".trim($_REQUEST['newshortcut'])."'".returnIntLang('menuedit reserved shortcut', false)."#;#";
	elseif ($fcheck_res['num']>0):
		echo "'".trim($_REQUEST['newshortcut'])."'".returnIntLang('menuedit double shortcut', false)."#;#";
	endif;
endif;
endif;

// EOF ?>