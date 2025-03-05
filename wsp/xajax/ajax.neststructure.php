<?php
/**
 * ...
 * @author stefan@covi.de
 * @copyright (c) 2021, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 7.0
 * @lastchange 2021-03-02
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

	function returnUpperLevel($mid) {
		// returns the level of the menupoint, that THIS mid is connected to
		$con_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($mid);
		$con_res = intval(doResultSQL($con_sql));
		$lvl_sql = "SELECT `level` FROM `menu` WHERE `mid` = ".intval($con_res);
		$lvl_res = intval(doResultSQL($lvl_sql));
		return $lvl_res;
		}

if (isset($_REQUEST['mid'])):
	$mid = str_replace("li_", "", $_REQUEST['mid']);
	$out = str_replace("ul_", "", $_REQUEST['selector']);
	$tgt = str_replace("ul_", "", $_REQUEST['target']);
	if (intval($mid)>0):
		// getting level of new connection
		$lvl_sql = "SELECT `level` FROM `menu` WHERE `mid` = ".intval($mid);
		$lvl_res = intval(doResultSQL($lvl_sql));
		// update old structure for redirecting
		changeMenuEntry(intval($mid), intval($tgt));
		// updating new connection
		doSQL("UPDATE `menu` SET `connected` = ".intval($tgt).", `level` = ".($lvl_res+1).", `changetime` = ".time().", `contentchanged` = 1 WHERE `mid` = ".intval($mid));
		$lowerlvl = returnIDRoot($mid);
		foreach ($lowerlvl AS $rk => $rv):
			doSQL("UPDATE `menu` SET `level` = ".(returnUpperLevel($rv)+1).", `changetime` = ".time().", `contentchanged` = 1 WHERE `mid` = ".intval($rv));
		endforeach;
		// updating change info to new connected list level
		doSQL("UPDATE `menu` SET `changetime` = ".time().", `contentchanged` = 1 WHERE `connected` = ".intval($tgt));
		// updating change info to old connected list level
		doSQL("UPDATE `menu` SET `changetime` = ".time().", `contentchanged` = 1 WHERE `connected` = ".intval($out));
        if (isset($_REQUEST['listorder'])):
			// ordering the new list
			$listelements = explode("=",$_REQUEST['listorder']);
			$orderedlist = array();
			foreach ($listelements AS $lk => $lv):
				if (intval($lv)>0):
					$orderedlist[] = intval($lv);
				endif;
			endforeach;
			$checkmove_sql = "SELECT MIN(`level`) AS minl, MAX(`level`) AS maxl, COUNT(`isindex`) AS index FROM `menu` WHERE `mid` IN (".implode(",", $orderedlist).")";
			$checkmove_res = doSQL($checkmove_sql);
			if ($checkmove_res['num']>0): 
				if (intval($checkmove_res['set'][0]['minl'])==intval($checkmove_res['set'][0]['maxl'])):
					foreach ($orderedlist AS $ok => $ov):
						if (intval($checkmove_res['set'][0]['index'])==0 && $ok==0):
							doSQL("UPDATE `menu` SET `isindex` = 1, `position` = ".(intval($ok)+1)." WHERE `mid` = ".intval($ov));
						else:
							doSQL("UPDATE `menu` SET `position` = ".(intval($ok)+1)." WHERE `mid` = ".intval($ov));
						endif;
					endforeach;
				endif;
			endif;
		endif;
	endif;
endif;

endif;
// EOF ?>