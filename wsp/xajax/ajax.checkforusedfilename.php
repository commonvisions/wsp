<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 */

if (!empty($_SERVER['HTTP_REFERER'] ?? null)):
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));

	require $wspdir.'/data/include/globalvars.inc.php';
	require $wspdir.'/data/include/wsplang.inc.php';
	require $wspdir."/data/include/dbaccess.inc.php";
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/filesystemfuncs.inc.php";
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";

	if (isset($_REQUEST['newname'])):
		$newname = strtolower(removeSpecialChar(trim($_REQUEST['newname'])));
		$fcheck_sql = "SELECT `filename` FROM `menu` WHERE `filename` LIKE '".escapeSQL(trim($newname))."' AND `trash` = 0";
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
		if (trim($newname)==''):
			echo returnIntLang('menuedit name cannot be empty', false)."#;#file".date("ymdHis");
		elseif ($newname!=trim($_REQUEST['newname'])):
			echo returnIntLang('menuedit name changed to unix', false)."#;#".$newname;
		elseif (in_array(trim($_REQUEST['newname']),$reservednames)):
			echo "'".trim($_REQUEST['newname'])."'".returnIntLang('menuedit reserved name', false)."#;#".$newname;
		elseif ($fcheck_res['num']>0):
			echo "'".trim($_REQUEST['newname'])."'".returnIntLang('menuedit double name', false)."#;#".$newname;
		endif;
	endif;
endif;
