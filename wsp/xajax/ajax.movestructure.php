<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-18
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

if (isset($_REQUEST['mid'])):
	$mid = intval(str_replace("li_", "", $_REQUEST['mid']));
	if (intval($mid)>0):
		if (isset($_REQUEST['listorder'])):
			$listelements = explode("=",$_REQUEST['listorder']);
			$orderedlist = array();
			foreach ($listelements AS $lk => $lv):
				if (intval($lv)>0):
					$orderedlist[] = intval($lv);
				endif;
			endforeach;
			$checkmove_sql = "SELECT MIN(`level`) AS `minl`, MAX(`level`) AS `maxl` FROM `menu` WHERE `mid` IN (".implode(",", $orderedlist).")";
			$checkmove_res = doSQL($checkmove_sql);
			if ($checkmove_res['num']>0): 
				if (intval($checkmove_res['set'][0]['minl'])==intval($checkmove_res['set'][0]['maxl'])):
					foreach ($orderedlist AS $ok => $ov):
						doSQL("UPDATE `menu` SET `position` = ".(intval($ok)+1)." WHERE `mid` = ".intval($ov));
					endforeach;
				endif;
			endif;
		endif;
		doSQL("UPDATE `menu` SET `contentchanged` = 4 WHERE `mid` = ".intval($mid));
	endif;
endif;
endif;
// EOF ?>