<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 * 
 * 2023-01-09
 * fixed error with missing 'done' field in 'wspqueue'
 * 
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

	if (isset($_REQUEST['mid'])):
		$mid = intval($_REQUEST['mid']);
		$sql = "UPDATE `menu` SET `visibility` = 0, `editable` = 0, `trash` = 1 WHERE `mid` = ".intval($mid);
		$res = doSQL($sql);
		if ($res['res']):
			$setuptime = time();
			if ($_SESSION['wspvars']['handledelete']==1):
				// remove file from structure
				doSQL("INSERT INTO `wspqueue` SET `uid` = ".$_SESSION['wspvars']['userid'].", `set` = ".time().", `timeout` = ".(time()+3600).", `done` = 0, `action` = 'removeitem', `param` = ".intval($mid).", `priority` = 99");
			elseif ($_SESSION['wspvars']['handledelete']==2):
				// replace with forwarding to homepage
				doSQL("INSERT INTO `wspqueue` SET `uid` = ".$_SESSION['wspvars']['userid'].", `set` = ".time().", `timeout` = ".(time()+3600).", `done` = 0, `action` = 'forwardhome', `param` = ".intval($mid).", `priority` = 99");
			elseif ($_SESSION['wspvars']['handledelete']==3):
				// replace with content "deleted"
				doSQL("INSERT INTO `wspqueue` SET `uid` = ".$_SESSION['wspvars']['userid'].", `set` = ".time().", `timeout` = ".(time()+3600).", `done` = 0, `action` = 'setupdeleted', `param` = ".intval($mid).", `priority` = 99");
			endif;
			
			// reset forwarding
			$fw_sql = "SELECT `mid` FROM `menu` WHERE `forwarding_id` = ".intval($mid);
			$fw_res = doSQL($fw_sql);
			$fw_mid = array();
			if($fw_res['num']>0) {
				foreach ($fw_res['set'] AS $fwrsk => $fwrsv) {
					$fw_mid[] = intval($fwrsv['mid']);
				}
			}
			doSQL("UPDATE `menu` SET `forwarding_id` = 0 WHERE `forwarding_id` = ".intval($mid));

			// set all effected MPs to publish
			$struc_pubid = array();
			$emp_tmp = getEffectedMPs($mid);
			if(count($emp_tmp)>0):
				foreach($emp_tmp AS $emp):
					if($emp!="" && $emp!=$mid):
						array_push($struc_pubid,$emp);
					endif;
				endforeach;
			endif;
			if(count($struc_pubid)>0):
				foreach ($struc_pubid AS $strucvalue):
					if ($strucvalue>0):
						$publishlang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
						foreach ($publishlang['languages']['shortcut'] AS $sk => $sv):
							$pub_sql = "REPLACE INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = 'publishstructure', `param` = '".intval($strucvalue)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($sv)."', `output` = ''";
							$pub_res = doSQL($pub_sql); 
							if ($pub_res['res']===false):
								addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
							endif;
						endforeach;
					endif;
				endforeach;
			endif;
			if(count($fw_mid)>0):
				foreach ($fw_mid AS $fwm):
					if ($fwm>0):
						$publishlang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
						foreach ($publishlang['languages']['shortcut'] AS $sk => $sv):
							$pub_sql = "REPLACE INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".$setuptime."', `action` = 'publishitem', `param` = '".intval($fwm)."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($sv)."', `output` = ''";
							$pub_res = doSQL($pub_sql); 
							if ($pub_res['res']===false):
								addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
							endif;
						endforeach;
					endif;
				endforeach;
			endif;
			$lowerlvl = returnIDRoot(intval($mid));
			foreach ($lowerlvl AS $lk => $lv):
				doSQL("UPDATE `menu` SET `visibility` = 0, `editable` = 0, `trash` = 1 WHERE `mid` = ".intval($lv));
			endforeach;
			echo "#li_".intval($mid);
		endif;
	endif;
endif;
