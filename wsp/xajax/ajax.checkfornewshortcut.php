<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2019-01-03
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

if (isset($_REQUEST['newshortcut']) && trim($_REQUEST['newshortcut'])!=''):
	$shortcut_sql = "SELECT `id` FROM `menu` WHERE `linktoshortcut` LIKE '".escapeSQL(trim($_REQUEST['newshortcut']))."'";
	$shortcut_res = mysql_query($shortcut_sql);
	$shortcut_num = 0;
	if ($shortcut_res) $shortcut_num = mysql_num_rows($shortcut_res);
	if ($shortcut_num>0) echo returnIntLang('message shortcut already exists', false);
endif;
endif;
?>