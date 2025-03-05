<?php
/**
 * edit menupoints details
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2021-03-03
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
/* checkParamVar ----------------------------- */
$mid = 0;
$op = '';
if (array_key_exists('wspvars', $_SESSION) && array_key_exists('editmenuid', $_SESSION['wspvars']) && intval($_SESSION['wspvars']['editmenuid'])>0):
	$mid = intval($_SESSION['wspvars']['editmenuid']);
endif;
if (isset($_POST['mid']) && intval($_POST['mid'])>0):
	$mid = intval($_POST['mid']);
endif;
// setting editmenuid to use with sitestructure or contentstructure page
$_SESSION['wspvars']['editmenuid'] = intval($mid);
// checking for operation
if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="edit"):
	$op = "edit";
endif;
if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="save"):
	$op = "save";
endif;
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'sitestructure';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].";mid=".$mid;
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = true;
/* second includes --------------------------- */
require ("data/include/checkuser.inc.php");
require ("data/include/errorhandler.inc.php");
require ("data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);

/* page specific funcs and actions */
if ($op=='save'):
    // update contentchanged info
	$nccres = contentChangeStat(intval($_POST['mid']), "structure");
	// get level info based on $_POST['subpointfrom']
	$isindex = 0;
	$level = 0;
	$minfo_sql = "SELECT `level` FROM `menu` WHERE `mid` = ".intval($_POST['subpointfrom']);
	$minfo_res = doResultSQL($minfo_sql);
    if ($minfo_res!==false): $level = intval($minfo_res)+1; else: $level = 1; endif;
	// check for changed filename
	$oname_sql = "SELECT `isindex`, `filename`, `forwarding_id` FROM `menu` WHERE `mid` = ".intval($_POST['mid']);
	$oname_res = doSQL($oname_sql);
	if ($oname_res['num']>0) {
        // menupoint was found by mid
		// is index file
        $isindex = intval($oname_res['set'][0]['isindex']);
        // get old filename
        $setfilename = strtolower(removeSpecialChar(trim($oname_res['set'][0]['filename'])));
        // get/set new filename
        $newfilename = strtolower(removeSpecialChar(trim($_POST['filename'])));
        // if filenames differ
		if($setfilename!=trim($newfilename)) { 
            // if filename was changed
            // lookup for same filename in same structure
			$ename_sql = "SELECT `mid` FROM `menu` WHERE `mid` != ".intval($_POST['mid'])." AND `connected` = ".intval($_POST['subpointfrom'])." AND `filename` = '".escapeSQL($setfilename)."' AND `trash` = 0";
			$ename_res = doSQL($ename_sql);
            if ($ename_res['num']>0) {
                // same name was found so we try to count up until filename doesnt exist
                $run = 1;
                while ($ename_res['num']>0) {
                    $ename_sql = "SELECT `mid` FROM `menu` WHERE `mid` != ".intval($_POST['mid'])." AND `connected` = ".intval($_POST['subpointfrom'])." AND `filename` = '".escapeSQL($newfilename."-".$run)."'";
	        		$ename_res = doSQL($ename_sql);
                    $run++;
                }
                $newfilename.= '-'.($run-1);
            }
            changeMenuEntry(intval($_POST['mid']), intval($_POST['subpointfrom']), $setfilename, $newfilename);
        }
		if(intval($oname_res['set'][0]['forwarding_id'])!=intval($_POST['forwarding_id'])) {
			$nccres = contentChangeStat(intval($_POST['mid']), "complete");
        }
	}
	// update query
	$menuupdate_sql = "UPDATE `menu` SET ";
	// $menuupdate_sql.= "`level` = ".intval($level);
	// $menuupdate_sql.= ", `connected` = ".intval($_POST['subpointfrom']);
	$menuupdate_sql.= " `editable` = ".intval($_POST['editable']); unset($_POST['editable']);
	$description = "";
	if (array_key_exists('langdesc', $_POST) && is_array($_POST['langdesc'])):
		foreach ($_POST['langdesc'] AS $lk => $lv):
			if ($description==""): $description = trim($lv); endif;
		endforeach;
		$menuupdate_sql.= ", `langdescription` = '".escapeSQL(serialize($_POST['langdesc']))."'"; unset($_POST['langdesc']);
	elseif (array_key_exists('description', $_POST) && trim($_POST['description'])!=""):
		$description = trim($_POST['description']);
	else:
		$description = "not set";
	endif;
	$menuupdate_sql.= ", `description` = '".escapeSQL(trim($description))."'";
	// $menuupdate_sql.= ", `filename` = '".escapeSQL(trim($_POST['filename']))."'"; unset($_POST['filename']);
	$menuupdate_sql.= ", `offlink` = '".escapeSQL(trim($_POST['offlink']))."'"; unset($_POST['offlink']);
	$menuupdate_sql.= ", `imageon` = '".escapeSQL(trim($_POST['imageon']))."'"; unset($_POST['imageon']);
	$menuupdate_sql.= ", `imageoff` = '".escapeSQL(trim($_POST['imageoff']))."'"; unset($_POST['imageoff']);
	$menuupdate_sql.= ", `imageakt` = '".escapeSQL(trim($_POST['imageakt']))."'"; unset($_POST['imageakt']);
	$menuupdate_sql.= ", `imageclick` = '".escapeSQL(trim($_POST['imageclick']))."'"; unset($_POST['imageclick']);
	$menuupdate_sql.= ", `templates_id` = ".intval($_POST['template']); unset($_POST['template']);
	$menuupdate_sql.= ", `internlink_id` = ".intval($_POST['urlintern']); unset($_POST['urlintern']);
	$menuupdate_sql.= ", `filetarget` = '".escapeSQL(trim($_POST['targetfile']))."'"; unset($_POST['targetfile']);
	$menuupdate_sql.= ", `interntarget` = '".escapeSQL(trim($_POST['targetintern']))."'"; unset($_POST['targetintern']);
	$menuupdate_sql.= ", `externtarget` = '".escapeSQL(trim($_POST['targetextern']))."'"; unset($_POST['targetextern']);
	$menuupdate_sql.= ", `forwarding_id` = ".intval($_POST['forwarding_id']); unset($_POST['forwarding_id']);
	$menuupdate_sql.= ", `contentchanged` = ".intval($nccres);
	$menuupdate_sql.= ", `changetime` = '".time()."'";
	$tmpaddscript = ''; if (array_key_exists('usejs', $_POST) && is_array($_POST['usejs'])) {
		$tmpaddscript = escapeSQL(serialize($_POST['usejs'])); unset($_POST['usejs']);
    }
    $menuupdate_sql.= ", `addscript` = '".$tmpaddscript."'";	
	$tmpaddcss = ''; if (array_key_exists('usecss', $_POST) && is_array($_POST['usecss'])) {
		$tmpaddcss = escapeSQL(serialize($_POST['usecss']));
        unset($_POST['usecss']);
    }
    $menuupdate_sql.= ", `addcss` = '".$tmpaddcss."'";
    $tmpuseclass = escapeSQL(trim($_POST['useclass'])); unset($_POST['useclass']);
	$menuupdate_sql.= ", `addclass` = '".$tmpuseclass."'";
	$menuupdate_sql.= ", `linktoshortcut` = '".escapeSQL(trim($_POST['shortcut']))."'"; unset($_POST['shortcut']);
    //	pluginguid - in development
    //	dynamicmenu - in development
    //	dynamicguid - in development
    //	forwardmenu
	//	denylang
	if (intval($_POST['visibility'])==0):
		$menuupdate_sql.= ", `visibility` = 0";  unset($_POST['visibility']);
		$menuupdate_sql.= ", `denylang` = ''";
	elseif (array_key_exists('denylang', $_POST) && is_array($_POST['denylang']) && count($_POST['denylang'])==count($worklang['languages'])):
		$menuupdate_sql.= ", `visibility` = 0";  unset($_POST['visibility']);
		$menuupdate_sql.= ", `denylang` = ''";	
	elseif (array_key_exists('denylang', $_POST) && is_array($_POST['denylang'])):
		$menuupdate_sql.= ", `visibility` = 1";  unset($_POST['visibility']);
		$menuupdate_sql.= ", `denylang` = '".escapeSQL(serialize($_POST['denylang']))."'"; unset($_POST['denylang']);
	else:
		$menuupdate_sql.= ", `visibility` = ".intval($_POST['visibility']);  unset($_POST['visibility']);
		$menuupdate_sql.= ", `denylang` = ''";
	endif;
	// update timing -> use var $timetable with contents later again !!
	$timetable = array();
	if (array_key_exists('startdate', $_POST) && is_array($_POST['startdate'])):
		foreach ($_POST['startdate'] AS $tk => $tv):
			$startdatetmp = explode(" ",$_POST['startdate'][$tk]);
			$startdaytmp = explode(".", $startdatetmp[0]);
			$starttimetmp = explode(":", $startdatetmp[1]);
			$startdate = mktime(intval($starttimetmp[0]),intval($starttimetmp[1]),0,intval($startdaytmp[1]),intval($startdaytmp[0]),intval($startdaytmp[2]));
			$enddatetmp = explode(" ",$_POST['enddate'][$tk]);
			$enddaytmp = explode(".", $enddatetmp[0]);
			$endtimetmp = explode(":", $enddatetmp[1]);
			$enddate = mktime(intval($endtimetmp[0]),intval($endtimetmp[1]),0,intval($enddaytmp[1]),intval($enddaytmp[0]),intval($enddaytmp[2]));
			$timetable[] = array(intval($startdate),intval($enddate));	
		endforeach;
		unset($_POST['startdate']); unset($_POST['enddate']);
	endif;
	if (array_key_exists('timetable', $_POST) && is_array($_POST['timetable'])):
		foreach ($_POST['timetable'] AS $tk => $tv):
			$timetable[] = explode(";", $tv);
		endforeach;
	endif;
    $tmpshowtime = '';
	if (count($timetable)>0):
		$tmpshowtime = escapeSQL(serialize($timetable));
	endif;
    $menuupdate_sql.= ", `showtime` = '".$tmpshowtime."'";	
	$menuupdate_sql.= ", `jsmouseover` = '".escapeSQL(trim($_POST['jscallmouseover']))."'"; unset($_POST['jscallmouseover']);
	$menuupdate_sql.= ", `jsclick` = '".escapeSQL(trim($_POST['jscallclick']))."'"; unset($_POST['jscallclick']);
	$menuupdate_sql.= ", `jsmouseout` = '".escapeSQL(trim($_POST['jscallmouseout']))."'"; unset($_POST['jscallmouseout']);
	$menuupdate_sql.= ", `filepopup` = '".serialize(array('top' => intval($_POST['filetop']), 'left' => intval($_POST['fileleft']), 'height' => intval($_POST['fileheight']), 'width' => intval($_POST['filewidth'])))."'"; unset($_POST['filetop']); unset($_POST['fileleft']); unset($_POST['fileheight']); unset($_POST['filewidth']);
	$menuupdate_sql.= ", `internpopup` = '".serialize(array('top' => intval($_POST['interntop']), 'left' => intval($_POST['internleft']), 'height' => intval($_POST['internheight']), 'width' => intval($_POST['internwidth'])))."'"; unset($_POST['interntop']); unset($_POST['internleft']); unset($_POST['internheight']); unset($_POST['internwidth']);
	$menuupdate_sql.= ", `externpopup` = '".serialize(array('top' => intval($_POST['externtop']), 'left' => intval($_POST['externleft']), 'height' => intval($_POST['externheight']), 'width' => intval($_POST['externwidth'])))."'"; unset($_POST['externtop']); unset($_POST['externleft']); unset($_POST['externheight']); unset($_POST['externwidth']);
	$menuupdate_sql.= ", `isindex` = ".intval($_POST['isindex']);
	if (intval($_POST['isindex'])==1) {
		// set all other items isindex on same level to 0
		doSQL("UPDATE `menu` SET `isindex` = 0 WHERE `connected` = ".intval($_POST['subpointfrom']));
    }
    unset($_POST['isindex']); unset($_POST['subpointfrom']);
	$menuupdate_sql.= ", `docintern` = '".escapeSQL(trim($_POST['docintern']))."'"; unset($_POST['docintern']);
	$tmpweekday = intval(array_sum($_POST['weekday'])); unset($_POST['weekday']);
    $menuupdate_sql.= ", `weekday` = ".$tmpweekday."";
	$menuupdate_sql.= ", `mobileexclude` = ".intval($_POST['mobileexclude']); unset($_POST['mobileexclude']);
	$tmplogincontrol = ''; if (array_key_exists('loginuser', $_POST) && is_array($_POST['loginuser'])) {
		$tmplogincontrol = escapeSQL(serialize($_POST['loginuser']));
        unset($_POST['loginuser']);
    }
    $menuupdate_sql.= ", `logincontrol` = '".$tmplogincontrol."'";	
    $tmplogin = intval($_POST['logincontrol']); unset($_POST['logincontrol']);
    $menuupdate_sql.= ", `login` = ".$tmplogin; 
	$menuupdate_sql.= ", `lockpage` = ".intval($_POST['lockpage']); unset($_POST['lockpage']);
    //	pluginmenu concept
    if (array_key_exists('pluginconfig', $_POST) && is_array($_POST['pluginconfig'])) {
        $menuupdate_sql.= ", `pluginconfig` = '".escapeSQL(serialize($_POST['pluginconfig']))."'";
        // do plugin menu action
        if (isset($_POST['pluginconfig']['filename']) && isset($_POST['pluginconfig']['description']) && isset($_POST['pluginconfig']['fromtable']) && isset($_POST['pluginconfig']['where'])) {
            // create a new temporary database connect if connection data is set
            if ((isset($_POST['pluginconfig']['dbhost']) && trim($_POST['pluginconfig']['dbhost'])!='') && (isset($_POST['pluginconfig']['dbuser']) && trim($_POST['pluginconfig']['dbuser'])!='') && (isset($_POST['pluginconfig']['dbpass']) && trim($_POST['pluginconfig']['dbpass'])!='') && (isset($_POST['pluginconfig']['dbname']) && trim($_POST['pluginconfig']['dbname'])!='')) {
                mysqli_close($_SESSION['wspvars']['db']);
                $_SESSION['wspvars']['db'] = new mysqli(trim($_POST['pluginconfig']['dbhost']), trim($_POST['pluginconfig']['dbuser']), trim($_POST['pluginconfig']['dbpass']), trim($_POST['pluginconfig']['dbname']));
                $tmpdb = true;
            }
            $plugincontent_sql = "SELECT `".trim($_POST['pluginconfig']['filename'])."` AS filename, `".trim($_POST['pluginconfig']['description'])."` AS description FROM `".trim($_POST['pluginconfig']['fromtable'])."`";
            if (trim($_POST['pluginconfig']['where'])!='') {
                $plugincontent_sql.= str_replace(" ORDER BY ", "", " WHERE ".trim($_POST['pluginconfig']['where']));
            }
            if (trim($_POST['pluginconfig']['order'])!='') {
                $plugincontent_sql.= str_replace(" WHERE ", "", " ORDER BY `".trim($_POST['pluginconfig']['order']))."`";
            }
            $plugincontent_res = doSQL(escapeSQL($plugincontent_sql));
            if (isset($tmpdb) && $tmpdb===true) {
                mysqli_close($_SESSION['wspvars']['db']);
                $_SESSION['wspvars']['db'] = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                unset($tmpdb);
            }
            if ($plugincontent_res['num']>0) {
                // setup empty array to update contents
                $updatecontent = array();
                // setup empty array to delete menupoints
                $deletemid = array();
                // get ALL connected menupoints
                $existmid_sql = "SELECT `mid` FROM `menu` WHERE `editable` = 7 AND `connected` = ".intval(intval($_POST['mid']));
                $existmid = getResultSQL($existmid_sql);
                // prepare an array with all mid that just have to be updated
                $updatemid = array();
                foreach ($plugincontent_res['set'] AS $pcrk => $pcrv) {
                    // get THE connected menupoint
                    $conmid_sql = "SELECT `mid` FROM `menu` WHERE `editable` = 7 AND `description` = '".escapeSQL(setUTF8(trim($pcrv['description'])))."' AND `filename` = '".escapeSQL(urlText(trim($pcrv['filename'])))."' AND `connected` = ".intval($_POST['mid']);
                    if (doResultSQL($conmid_sql)) {
                        $updatemid[] = intval(doResultSQL($conmid_sql));
                        $updatecontent[] = array(
                            'mid' => intval(doResultSQL($conmid_sql)),
                            'filename' => urlText(trim($pcrv['filename'])),
                            'description' => setUTF8(trim($pcrv['description'])),
                        );
                        // remove key-value-pair from $plugincontent_res['set'] to prevent new INSERT
                        // BUT update the entry later with new param
                        unset($plugincontent_res['set'][$pcrk]);
                        // everything what stays in $plugincontent_res['set'] is NEW content
                    }
                }
                // compare all connected menupoints with updatetable menupoints
                if (is_array($existmid)) {
                    // and put all not updateable menupoints to deletion queue 
                    $deletemid = array_diff($existmid, $updatemid);
                }
                // remove all unrelated menupoints
                foreach ($deletemid AS $dmk => $dmv) {
                    $dynamiccleanup_sql = "DELETE FROM `menu` WHERE `editable` = 7 AND `connected` = ".intval($_POST['mid'])." AND `mid` = ".intval($dmv);
                    doSQL($dynamiccleanup_sql);
                    $dynamiccleanup_sql = "DELETE FROM `content` WHERE `mid` = ".intval($dmv);
                    doSQL($dynamiccleanup_sql);
                }
                // update all menupoints that are STILL connected
                foreach ($updatemid AS $umk => $umv) {
                    $dynamicudpate_sql = "UPDATE `menu` SET `visibility` = ".(isset($_POST['pluginconfig']['visibility'])?intval($_POST['pluginconfig']['visibility']):'').", `level` = ".(intval($level)+1).", `connected` = ".intval($_POST['mid']).", `contentchanged` = 1, `changetime` = ".time().", `addscript` = '".$tmpaddscript."', `addcss` = '".$tmpaddcss."', `addclass` = '".$tmpuseclass."', `mobileexclude` = ".(isset($_POST['pluginconfig']['mobileexclude'])?intval($_POST['pluginconfig']['mobileexclude']):'').", `weekday` = ".$tmpweekday.", `showtime` = '".$tmpshowtime."', `login` = ".$tmplogin.", `logincontrol` = '".$tmplogincontrol."', `lockpage` = ".(isset($_POST['pluginconfig']['lockpage'])?intval($_POST['pluginconfig']['lockpage']):'').", `structurechanged` = ".time().", `menuchangetime` = ".time().", `lastchange` = ".time()." WHERE `mid` = ".intval($umv);
                    $dynamicudpate_res = doSQL($dynamicudpate_sql);
                }
                // insert dynamic menupoints to menu
                foreach ($plugincontent_res['set'] AS $pcrk => $pcrv) {
                    // insert dynamic menupoints to menu
                    $dynamicinsert_sql = "INSERT INTO `menu` SET `editable` = 7, `position` = ".intval($pcrk).", `visibility` = ".(isset($_POST['pluginconfig']['visibility'])?intval($_POST['pluginconfig']['visibility']):'').", `description` = '".escapeSQL(setUTF8(trim($pcrv['description'])))."', `templates_id` = 0, `level` = ".(intval($level)+1).", `connected` = ".intval($_POST['mid']).", `filename` = '".escapeSQL(urlText(trim($pcrv['filename'])))."', `contentchanged` = 1, `changetime` = ".time().", `addscript` = '".$tmpaddscript."', `addcss` = '".$tmpaddcss."', `addclass` = '".$tmpuseclass."', `isindex` = 0, `trash` = 0, `mobileexclude` = ".(isset($_POST['pluginconfig']['mobileexclude'])?intval($_POST['pluginconfig']['mobileexclude']):'').", `weekday` = ".$tmpweekday.", `showtime` = '".$tmpshowtime."', `login` = ".$tmplogin.", `logincontrol` = '".$tmplogincontrol."', 
                    `lockpage` = ".(isset($_POST['pluginconfig']['lockpage'])?intval($_POST['pluginconfig']['lockpage']):'').", `structurechanged` = ".time().", `menuchangetime` = ".time().", `lastchange` = ".time();
                    $dynamicinsert_res = doSQL($dynamicinsert_sql);
                    if ($dynamicinsert_res['inf']>0) {
                        $updatecontent[] = array(
                            'mid' => intval($dynamicinsert_res['inf']),
                            'filename' => urlText(trim($pcrv['filename'])),
                            'description' => setUTF8(trim($pcrv['description'])),
                        );
                    }
                }
                foreach ($updatecontent AS $uck => $ucv) {
                    // find contents of dynamic menupoint
                    $dynamiccontent_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `trash` = 0 ORDER BY `content_area`, `position`";
                    $dynamiccontent_res = doSQL($dynamiccontent_sql);
                    if ($dynamiccontent_res['num']>0) {
                        // remove all older contents
                        $removecontent_sql = "DELETE FROM `content` WHERE `mid` = ".intval($ucv['mid']); 
                        doSQL($removecontent_sql);
                        foreach ($dynamiccontent_res['set'] AS $dcrk => $dcrv) {
                            $dcrv['mid'] = $ucv['inf'];
                            // get dynamic valuefields and fill it with contents
                            $dyncontent = unserializeBroken($dcrv['valuefields']);
                            $valuefields = array();
                            if (is_array($dyncontent) && array_key_exists('isdynamic', $dyncontent)) {
                                foreach ($dyncontent AS $dck => $dcv) {
                                    if ($dck!='isdynamic') {
                                        $valuefields_sql = "SELECT `".str_replace("`", "", $dcv['selectfield'])."` AS value FROM `".str_replace("`", "", $dcv['selecttable'])."` WHERE `".trim($_POST['pluginconfig']['filename'])."` = '".trim($ucv['filename'])."' AND `".trim($_POST['pluginconfig']['description'])."` = '".trim($ucv['description'])."'";
                                        if (trim(str_replace("`", "", $dcv['where']))!='') {
                                            $valuefields_sql.= " AND (".trim($dcv['where']).")";
                                        }
                                        $valuefields[$dck] = doResultSQL($valuefields_sql);
                                    }
                                }
                            }
                            $dcrv['valuefields'] = serialize($valuefields);
                            $insertdata_sql = "INSERT INTO `content` SET `mid` = ".intval($ucv['mid']).", `globalcontent_id` = ".intval($dcrv['globalcontent_id']).", `connected` = 0, `position` = ".intval($dcrv['position']).", `visibility` = ".intval($dcrv['visibility']).", `sid` = ".intval($dcrv['sid']).", `valuefields` = '".escapeSQL($dcrv['valuefields'])."', `lastchange` = ".time().", `interpreter_guid` = '".escapeSQL($dcrv['interpreter_guid'])."', `content_area` = ".intval($dcrv['content_area']).", `content_lang` = '".escapeSQL($dcrv['content_lang'])."', `showday` = ".intval($dcrv['showday']).", `showtime` = '".escapeSQL($dcrv['showtime'])."', `container` = ".intval($dcrv['container']).", `containerclass` = '".escapeSQL($dcrv['containerclass'])."', `trash` = 0, `containeranchor` = '".escapeSQL($dcrv['containeranchor'])."', `displayclass` = ".intval($dcrv['displayclass']).", `login` = ".intval($dcrv['login']).", `logincontrol` = '".escapeSQL($dcrv['logincontrol'])."', `uid` = ".intval(intval($_SESSION['wspvars']['userid'])).", `description` = 'dynamiccontent'";
                            $insertdata_res = doSQL($insertdata_sql);
                        }
                    }
                }
            }
        }
        unset($_POST['pluginconfig']);
    }
    $menuupdate_sql.= " WHERE `mid` = ".intval($_POST['mid']);
    if (doSQL($menuupdate_sql)['aff']>0) {
		addWSPMsg('resultmsg', returnIntLang('menuedit menupoint successfully updated', false));
    } else {
		addWSPMsg('errormsg', 'error updating menupoint');
        addWSPMsg('errormsg', var_export(doSQL($menuupdate_sql), true));
    }

    // update meta page info
	$pageinfo_sql = "DELETE FROM `pageproperties` WHERE `mid` = ".intval($_POST['mid']);
	doSQL($pageinfo_sql);
	$pageinfo_sql = "INSERT INTO `pageproperties` SET ";
	$pageinfo_sql.= "`mid` = ".intval($_POST['mid']).", ";
	$pageinfo_sql.= "`pagetitle` = '".escapeSQL(trim($_POST['pagetitle']))."', "; unset($_POST['pagetitle']);
	$pageinfo_sql.= "`pagedesc` = '".escapeSQL(trim($_POST['pagedesc']))."', "; unset($_POST['pagedesc']);
	$pageinfo_sql.= "`pagekeys` = '".escapeSQL(trim($_POST['pagekeys']))."'"; unset($_POST['pagekeys']);
	doSQL($pageinfo_sql);
	
	// update contentchanged var to all related pages
	$relmid = array_merge(returnIDRoot($_POST['mid']),returnIDTree($_POST['mid']));
	if (is_array($relmid) && count($relmid)>0):
		foreach($relmid AS $rk => $rv):
			$minfo_sql = "SELECT `contentchanged` FROM `menu` WHERE `contentchanged` != 1 && `contentchanged` != 3 && `mid` = ".intval($rv);
			$minfo_res = doResultSQL($minfo_sql);
			$ccres = 0; if ($minfo_res!==false): $ccres = intval($minfo_res); endif;
			$nccres = 0; if ($ccres==0): $nccres = 4;
			elseif ($ccres==2): $nccres = 5;
			elseif ($ccres==4): $nccres = 4;
			elseif ($ccres==5): $nccres = 5;
			elseif ($ccres==7): $nccres = 7;
			endif;
			doSQL("UPDATE `menu` SET `contentchanged` = ".intval($nccres)." WHERE `mid` = ".intval($rv));
		endforeach;
	endif;
	// maybe lookup template for used menuvars
	// maybe lookup menuvars for displayed levels and mids
	// maybe update mids with related contentchanged var
	
endif;

if (isset($_POST) && array_key_exists('backjump', $_POST) && $_POST['backjump']=="back") {
	$_SESSION['wspvars']['editmenuid'] = $mid;
	header('location: menuedit.php');
}

$menudetails_sql = "SELECT * FROM `menu` WHERE `mid` = ".$mid;
$menudetails_res = doSQL($menudetails_sql);

require ("./data/include/header.inc.php");
require ("./data/include/wspmenu.inc.php");

//    echo "<pre>";
//    var_export($_SESSION);
//    echo "</pre>";

if ($menudetails_res['num']==0): ?>
    <div id="contentholder">	
        <fieldset><h1><?php echo returnIntLang('structure edit headline', true); ?></h1></fieldset>
        <fieldset><?php echo returnIntLang('structure error retrieving mid', true); ?></fieldset>
        <fieldset class="options">
            <p><a href="menuedit.php" class="orangefield"><?php echo returnIntLang('str back', true); ?></a></p>
        </fieldset>
    </div>
<?php else: ?>
<script src="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/jquery/jquery.timepicker.js"></script>
<style type="text/css"> 
.ui-timepicker-div .ui-widget-header { margin-bottom: 8px; }
.ui-timepicker-div dl { text-align: left; }
.ui-timepicker-div dl dt { float: left; clear:left; padding: 0 0 0 5px; }
.ui-timepicker-div dl dd { margin: 0 10px 10px 45%; }
.ui-timepicker-div td { font-size: 90%; }
.ui-tpicker-grid-label { background: none; border: none; margin: 0; padding: 0; }
.ui-timepicker-rtl{ direction: rtl; }
.ui-timepicker-rtl dl { text-align: right; padding: 0 5px 0 0; }
.ui-timepicker-rtl dl dt{ float: right; clear: right; }
.ui-timepicker-rtl dl dd { margin: 0 45% 10px 10px; }
</style>
<div id="contentholder">	
	<fieldset><h1><?php echo returnIntLang('structure edit headline', true); ?></h1></fieldset>
	<?php 
	
	$menueditdata = $menudetails_res['set'][0];
	$langdescription = unserializeBroken($menueditdata['langdescription']);
	if (trim($menueditdata['offlink'])!=''):
		$menueditdata['offlinkdata'] = explode('<#>', trim($menueditdata['offlink']));
		$menueditdata['offlink'] = trim($menueditdata['offlinkdata'][0]);
	endif;
    $pluginconfig = unserializeBroken($menueditdata['pluginconfig']);
	
	$forward_sql = "SELECT `mid`, `description` FROM `menu` WHERE trash != 1 AND `connected` = ".intval($mid)." ORDER BY `position`"; //'
	$forward_res = doSQL($forward_sql);
	?>
	<form id="frmmenudetail" name="frmmenudetail" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
        <script language="JavaScript" type="text/javascript">
        <!--

        function checkForUsedFilename(newName) {
            $.post("xajax/ajax.checkforusedfilename.php", { 'newname': newName})
            .done (function(data) {
                if (data!='') {
                    var splitData = data.split("#;#");
                    if (splitData.length==2) {
                        alert (splitData[0]);
                        document.getElementById('filename').value = splitData[1];
                        }
                    else {
                        alert (data);
                        }
                    document.getElementById('filename').focus();
                    }
                });
            }

        function checkForUsedShortcut(newShortcut) {
            $.post("xajax/ajax.checkforusedshortcut.php", { 'newshortcut': newShortcut})
            .done (function(data) {
                if (data!='') {
                    var splitData = data.split("#;#");
                    if (splitData.length==2) {
                        alert (splitData[0]);
                        document.getElementById('shortcut').value = splitData[1];
                        }
                    else {
                        alert (data);
                        }
                    document.getElementById('shortcut').focus();
                    }
                });
            }

        function saveMenuEdit(backjump) {
            if (document.getElementById('filename').value!='') {
                if (backjump) {
                    document.getElementById('backjump').value = 'back'; 
                    }
                document.getElementById('cfc').value = 0;
                document.getElementById('ophidden').value = 'save';
                document.getElementById('frmmenudetail').submit();
                }
            else {
                alert ('<?php echo returnIntLang('menuedit set filename', false); ?>');
                document.getElementById('filename').value = '';
                }
            }

        function submitTimeRemove(timeid) {
            $('#time-'+timeid).toggle('fade',500,function(){
                $('#time-'+timeid).remove();
                });
            }

        // -->
        </script>
        <fieldset>
            <legend><?php echo returnIntLang('structure edit generell', true); ?> <?php echo legendOpenerCloser('structure_basics'); ?></legend>
            <div id="structure_basics">
            <input name="forwarding_id" type="hidden" value="0" />
            <?php 
                
                if ($_SESSION['wspvars']['rights']['sitestructure']==3) {
                    ?>
                <input type="hidden" name="subpointfrom" value="<?php echo intval($menueditdata['connected']); ?>" />
                <input type="hidden" name="mobileexclude" value="<?php echo intval($menueditdata['mobileexclude']); ?>" />
                <input type="hidden" name="isindex" value="<?php echo intval($menueditdata['isindex']); ?>" />
                <input type="hidden" name="editable" value="<?php echo intval($menueditdata['editable']); ?>" />
                <input type="hidden" name="lockpage" value="<?php echo intval($menueditdata['lockpage']); ?>" />
                
                <table class="tablelist">
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit generell show', true); ?> <?php if(intval(count($worklang['languages']['shortcut']))>1): helpText(returnIntLang('structure edit generell show help', false)); endif; ?></td>
                        <td class="tablecell two"><input type="hidden" name="visibility" value="0" /><input name="visibility" type="checkbox" value="1" <?php if (intval($menueditdata['visibility'])==1) echo "checked"; ?> onchange="document.getElementById('cfc').value = 1;" /></td>
                        <td class="tablecell two"><?php echo returnIntLang('structure templatename', true); ?></td>
                        <td class="tablecell two"><select name="template" id="template" style="width: 99%;">
                            <option value="-1"><?php echo returnIntLang('structure pleasechoosetemplate', true); ?></option>
                            <option value="0" <?php if (intval($menueditdata['templates_id'])==0): echo ' selected="selected"'; endif; ?>><?php echo returnIntLang('structure chooseuppertemplate', true); ?></option>
                            <?php

                            $templates_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
                            $templates_res = doSQL($templates_sql);
                            if ($templates_res['num']>0):
                                foreach ($templates_res['set'] AS $tresk => $tresv):
                                    echo "<option value=\"".intval($tresv['id'])."\" ";
                                    if (intval($menueditdata['templates_id'])==intval($tresv['id'])): echo ' selected="selected"'; endif;
                                    echo ">";
                                    echo setUTF8(trim($tresv['name']));
                                    echo "</option>";
                                endforeach;
                            endif;
                            $menueditdata['real_templates_id'] = $realtemp = getTemplateID(intval(intval($menueditdata['templates_id']))); 

                            ?>
                        </select></td>

                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('str filename', true); ?> <?php helpText(returnIntLang('structure filename help', false)); ?></td>
                        <td class="tablecell two"><input name="filename" id="filename" type="text" size="22" value="<?php echo $menueditdata['filename']; ?>" class="two" onchange="document.getElementById('cfc').value = 1; checkForUsedFilename(this.value);" /></td>
                        <td class="tablecell two"><?php echo returnIntLang('str shortcut', true); ?> <?php helpText(returnIntLang('structure shortcut help', false)); ?></td>
                        <td class="tablecell two"><input name="shortcut" id="shortcut" type="text" size="22"  value="<?php echo $menueditdata['linktoshortcut']; ?>" class="two" onchange="document.getElementById('cfc').value = 1; checkForUsedShortcut(this.value);" /></td>
                    </tr>
                    <?php if (intval(count($worklang['languages']['shortcut']))>1):
                        $langcell = array();
                        foreach ($worklang['languages']['shortcut'] AS $key => $value):
                            if (!(is_array($langdescription)) || !(array_key_exists($value, $langdescription)) || $langdescription[$value]==""): $langdescription[$value] = $menueditdata['description']; endif; 
                            $langcell[] = '<td class="tablecell two">'.returnIntLang('structure edit generell title', true).' "'.$worklang['languages']['longname'][$key].'"</td><td class="tablecell two"><input name="langdesc['.$value.']" type="text" value="'.prepareTextField(stripslashes($langdescription[$value])).'" class="full" onchange="document.getElementById(\'cfc\').value = 1;" /></td>';
                        endforeach;
                        if ((count($langcell)/2)!=(ceil((count($langcell)/2)))):
                            $langcell[] = '<td class="tablecell four"></td>';
                        endif;
                        for ($lc=0; $lc<(count($langcell)/2); $lc++):
                            echo "<tr>";
                            for ($lcc=0; $lcc<2; $lcc++):
                                echo $langcell[(($lc*2)+$lcc)];
                            endfor;
                            echo "</tr>";
                        endfor;
                    else:
                        ?><tr><td class="tablecell two"><?php echo returnIntLang('structure edit generell title', true); ?></td>
                        <td class="tablecell six"><input name="description" type="text" size="22" value="<?php echo prepareTextField($menueditdata['description']); ?>" class="six full" onchange="document.getElementById('cfc').value = 1;" /></td></tr><?php
                    endif; ?>
                </table>
                <?php
                }
                else { ?>
                <table class="tablelist">
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure is subpoint to', true); ?></td>
                        <td class="tablecell two"><select id="subpointfrom" name="subpointfrom" size="1" class="three full"><?php if ($_SESSION['wspvars']['rights']['sitestructure']==1): ?>
                            <option value="0"><?php echo returnIntLang('structure menuedit mainmenu'); ?></option>
                            <?php getMenuLevel(0, 0, 1, array(intval($menueditdata['connected'])), '', 1, false);
                        elseif ($_SESSION['wspvars']['rights']['sitestructure']==7 && intval($_SESSION['wspvars']['rights']['sitestructure_array'][0])>0): 
                            if ($mid==$_SESSION['wspvars']['rights']['sitestructure_array'][0]):
                                $topm_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($_SESSION['structuremidlist'][0]);
                                $topm_res = doResultSQL($topm_sql);
                                $topmid = intval($topm_res);
                                echo "<option value=\"".$topmid."\">".returnIntLang('structure edit property can not be changed', false)."</option>";
                            else:
                                ?>
                                <option value="<?php echo intval($_SESSION['wspvars']['rights']['sitestructure_array'][0]); ?>"><?php 
                                $mpname_sql = "SELECT `description`, `level` FROM `menu` WHERE `mid` = ".intval($_SESSION['wspvars']['rights']['sitestructure_array'][0])." ORDER BY `level`, `position`";
                                $mpname_res = doSQL($mpname_sql);
                                if ($mpname_res['num']>0):
                                    echo setUTF8(trim($mpname_res['set'][0]['description'])); 
                                    ?></option>
                                    <?php getMenuLevel($_SESSION['wspvars']['rights']['sitestructure_array'][0], (intval($mpname_res['set'][0]['level'])*3), 1, array(intval($menueditdata['connected'])), '', 1, false);
                                endif;
                            endif;
                        endif;?></select></td>
                        <td class="tablecell two"><?php echo returnIntLang('structure exclude mobile'); ?> <?php helpText(returnIntLang('structure exclude mobile help', false)); ?></td>
                        <td class="tablecell two"><input type="hidden" name="mobileexclude" value="0" /><input type="checkbox" name="mobileexclude" id="mobileexclude" value="1" <?php if(intval($menueditdata['mobileexclude'])==1) echo "checked=\"checked\""; ?> onchange="document.getElementById('cfc').value = 1;"></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit index def', true); ?>  <?php helpText(returnIntLang('structure edit index def help', false)); ?></td>
                        <td class="tablecell two"><input type="hidden" name="isindex" value="0" /><input name="isindex" type="checkbox" value="1" <?php if (intval($menueditdata['isindex'])==1) echo "checked"; ?> onchange="document.getElementById('cfc').value = 1;" /></td>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit generell block', true); ?>  <?php helpText(returnIntLang('structure edit generell block help', false)); ?></td>
                        <td class="tablecell two"><select id="editable" name="editable" size="1" class="" onchange="document.getElementById('cfc').value = 1;" >
                            <option value="0"><?php echo returnIntLang('structure edit generell block not editable', true); ?></option>
                            <option value="1" <?php if (intval($menueditdata['editable'])==1) echo " selected='selected' " ; ?>><?php echo returnIntLang('structure edit generell block editable', true); ?></option>
                            <option value="9" <?php if (intval($menueditdata['editable'])==9) echo " selected='selected' " ; ?>><?php echo returnIntLang('structure edit generell block dynamic content', true); ?></option>
                        </select></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit generell show', true); ?> <?php if(intval(count($worklang['languages']['shortcut']))>1): helpText(returnIntLang('structure edit generell show help', false)); endif; ?></td>
                        <td class="tablecell two"><input type="hidden" name="visibility" value="0" /><input name="visibility" type="checkbox" value="1" <?php if (intval($menueditdata['visibility'])==1) echo "checked"; ?> onchange="document.getElementById('cfc').value = 1;" /></td>
                        <td class="tablecell two"><?php if (isset($_SESSION['wspvars']['bindcontentview']) && intval($_SESSION['wspvars']['bindcontentview'])==1):
                            echo returnIntLang('structure show content even menu inactive');
                        else:
                            echo returnIntLang('structure hide content when menu inactive');
                        endif; ?></td>
                        <td class="tablecell two"><?php

                        if (isset($_SESSION['wspvars']['bindcontentview']) && intval($_SESSION['wspvars']['bindcontentview'])>0):
                            ?><input type="hidden" name="lockpage" value="0" /><input type="checkbox" name="lockpage" value="1" <?php if(isset($menueditdata['lockpage']) && intval($menueditdata['lockpage'])==1): echo " checked='checked' "; endif; ?> /><?php
                        else:
                            ?><input type="hidden" name="lockpage" value="0" /><input type="checkbox" name="lockpage" value="1" <?php if(isset($menueditdata['lockpage']) && intval($menueditdata['lockpage'])==1): echo " checked='checked' "; endif; ?> /><?php 
                        endif; ?></td>

                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure templatename', true); ?></td>
                        <td class="tablecell six"><select name="template" id="template" style="width: 99%;">
                            <option value="-1"><?php echo returnIntLang('structure pleasechoosetemplate', true); ?></option>
                            <option value="0" <?php if (intval($menueditdata['templates_id'])==0): echo ' selected="selected"'; endif; ?>><?php echo returnIntLang('structure chooseuppertemplate', true); ?></option>
                            <?php

                            $templates_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
                            $templates_res = doSQL($templates_sql);
                            if ($templates_res['num']>0):
                                foreach ($templates_res['set'] AS $tresk => $tresv):
                                    echo "<option value=\"".intval($tresv['id'])."\" ";
                                    if (intval($menueditdata['templates_id'])==intval($tresv['id'])): echo ' selected="selected"'; endif;
                                    echo ">";
                                    echo setUTF8(trim($tresv['name']));
                                    echo "</option>";
                                endforeach;
                            endif;
                            $menueditdata['real_templates_id'] = $realtemp = getTemplateID(intval(intval($menueditdata['templates_id']))); 

                            ?>
                        </select></td>
                    </tr>
                    <?php if ($$forward_res['num']>0): ?>
                        <tr>
                            <td class="tablecell two"><?php echo returnIntLang('structure edit generell forwarding', true); ?> <?php helpText(returnIntLang('structure forwarder help', false)); ?></td>
                            <td class="tablecell six"><select name="forwarding_id" id="forwarding_id" style="width: 99%;">
                                <option value="0"><?php echo returnIntLang('structure forward first active', false); ?></option>
                                <?php foreach ($forward_res['set'] AS $fresk => $fresv):
                                    echo "<option value=\"".intval($fresv['mid'])."\" ";
                                    if ($menueditdata['forwarding_id']==intval($fresv['mid'])): echo ' selected="selected"'; endif;
                                    echo ">";
                                    echo setUTF8(trim($fresv['description']));
                                    echo "</option>";
                                endforeach; ?>
                            </select></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('str filename', true); ?> <?php helpText(returnIntLang('structure filename help', false)); ?></td>
                        <td class="tablecell two"><input name="filename" id="filename" type="text" size="22" value="<?php echo $menueditdata['filename']; ?>" class="two" onchange="document.getElementById('cfc').value = 1; checkForUsedFilename(this.value);" /></td>
                        <td class="tablecell two"><?php echo returnIntLang('str shortcut', true); ?> <?php helpText(returnIntLang('structure shortcut help', false)); ?></td>
                        <td class="tablecell two"><input name="shortcut" id="shortcut" type="text" size="22"  value="<?php echo $menueditdata['linktoshortcut']; ?>" class="two" onchange="document.getElementById('cfc').value = 1; checkForUsedShortcut(this.value);" /></td>
                    </tr>
                    <?php if (intval(count($worklang['languages']['shortcut']))>1):
                        $langcell = array();
                        foreach ($worklang['languages']['shortcut'] AS $key => $value):
                            if (!(is_array($langdescription)) || !(array_key_exists($value, $langdescription)) || $langdescription[$value]==""): $langdescription[$value] = $menueditdata['description']; endif; 
                            $langcell[] = '<td class="tablecell two">'.returnIntLang('structure edit generell title', true).' "'.$worklang['languages']['longname'][$key].'"</td><td class="tablecell two"><input name="langdesc['.$value.']" type="text" value="'.prepareTextField(stripslashes($langdescription[$value])).'" class="full" onchange="document.getElementById(\'cfc\').value = 1;" /></td>';
                        endforeach;
                        if ((count($langcell)/2)!=(ceil((count($langcell)/2)))):
                            $langcell[] = '<td class="tablecell four"></td>';
                        endif;
                        for ($lc=0; $lc<(count($langcell)/2); $lc++):
                            echo "<tr>";
                            for ($lcc=0; $lcc<2; $lcc++):
                                echo $langcell[(($lc*2)+$lcc)];
                            endfor;
                            echo "</tr>";
                        endfor;
                    else:
                        ?><tr><td class="tablecell two"><?php echo returnIntLang('structure edit generell title', true); ?></td>
                        <td class="tablecell six"><input name="description" type="text" size="22" value="<?php echo prepareTextField($menueditdata['description']); ?>" class="six full" onchange="document.getElementById('cfc').value = 1;" /></td></tr><?php
                    endif; ?>
                </table>
                <?php } ?>
            </div>
        </fieldset>
        <?php 
        if (intval($menueditdata['editable'])==9) {
            // properties for plugin contents
            ?>
            <fieldset>
                <legend><?php echo returnIntLang('structure plugin content properties', true); ?> <?php echo legendOpenerCloser('structure_plugincontent'); ?></legend>
                <div id="structure_plugincontent">
                    <table class="tablelist">
                        <tr>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content use database host', true); ?></td>
                            <td class="tablecell two"><input name="pluginconfig[dbhost]" type="text" value="<?php echo prepareTextField($pluginconfig['dbhost']); ?>" class="full" onchange="document.getElementById('cfc').value = 1;" /></td>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content use database name', true); ?></td>
                            <td class="tablecell two"><input name="pluginconfig[dbname]" type="text" value="<?php echo prepareTextField($pluginconfig['dbname']); ?>" class="full" onchange="document.getElementById('cfc').value = 1;" /></td>
                        </tr>
                        <tr>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content use database user', true); ?></td>
                            <td class="tablecell two"><input name="pluginconfig[dbuser]" type="text" value="<?php echo prepareTextField($pluginconfig['dbuser']); ?>" class="full" onchange="document.getElementById('cfc').value = 1;" /></td>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content use database pass', true); ?></td>
                            <td class="tablecell two"><input name="pluginconfig[dbpass]" type="text" value="<?php echo prepareTextField($pluginconfig['dbpass']); ?>" class="full" onchange="document.getElementById('cfc').value = 1;" /></td>
                        </tr>
                        <tr>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content filename select from FIELD', true); ?></td>
                            <td class="tablecell two"><input name="pluginconfig[filename]" type="text" value="<?php echo prepareTextField($pluginconfig['filename']); ?>" class="full" onchange="document.getElementById('cfc').value = 1;" /></td>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content description select from FIELD', true); ?></td>
                            <td class="tablecell two"><input name="pluginconfig[description]" type="text" value="<?php echo prepareTextField($pluginconfig['description']); ?>" class="full" onchange="document.getElementById('cfc').value = 1;" /></td>
                        </tr>
                        <tr>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content select from TABLE', true); ?></td>
                            <td class="tablecell two"><input name="pluginconfig[fromtable]" type="text" value="<?php echo prepareTextField($pluginconfig['fromtable']); ?>" class="full" onchange="document.getElementById('cfc').value = 1;" /></td>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content WHERE condition', true); ?></td>
                            <td class="tablecell two"><input name="pluginconfig[where]" type="text" value="<?php echo prepareTextField($pluginconfig['where']); ?>" class="full" onchange="document.getElementById('cfc').value = 1;" /></td>
                        </tr>
                        <tr>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content ORDER BY condition', true); ?></td>
                            <td class="tablecell two"><input name="pluginconfig[order]" type="text" value="<?php echo prepareTextField($pluginconfig['order']); ?>" class="full" onchange="document.getElementById('cfc').value = 1;" /></td>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content menu visibility', true); ?></td>
                            <td class="tablecell two"><input type="hidden" name="pluginconfig[visibility]" value="0" /><input name="pluginconfig[visibility]" type="checkbox" value="1" <?php if (intval($pluginconfig['visibility'])==1) echo "checked"; ?> onchange="document.getElementById('cfc').value = 1;" /></td>
                        </tr>
                        <tr>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content menu hide on mobile', true); ?></td>
                            <td class="tablecell two"><input type="hidden" name="pluginconfig[mobileexclude]" value="0" /><input name="pluginconfig[mobileexclude]" type="checkbox" value="1" <?php if (intval($pluginconfig['mobileexclude'])==1) echo "checked"; ?> onchange="document.getElementById('cfc').value = 1;" /></td>
                            <td class="tablecell two"><?php echo returnIntLang('structure plugin content menu editing locked', true); ?></td>
                            <td class="tablecell six"><input type="hidden" name="pluginconfig[lockpage]" value="0" /><input name="pluginconfig[lockpage]" type="checkbox" value="1" <?php if (intval($pluginconfig['lockpage'])==1) echo "checked"; ?> onchange="document.getElementById('cfc').value = 1;" /></td>
                        </tr>
                    </table>
                </div>
            </fieldset>
            <?php
        } ?>
        <fieldset>
            <legend><?php echo returnIntLang('structure special view', true); ?> <?php echo legendOpenerCloser('structure_specialview'); ?></legend>
            <div id="structure_specialview">
                <p><?php echo returnIntLang('structure special view description', true); ?></p>
                <?php

                $showday = intval($menueditdata['weekday']);
                for ($sd=6;$sd>=0;$sd--):
                    if ($showday-pow(2,$sd)>=0):
                        $weekdayvalue[($sd+1)] = "checked=\"checked\"";
                        $showday = $showday-(pow(2,$sd));
                    else:
                        $weekdayvalue[($sd+1)] = "";
                    endif;
                endfor;

                ?>
                <table class="tablelist">
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure daily based view'); ?><input type="hidden" name="weekday[0]" value="0" /></td>
                        <td class="tablecell six"><span class="nowrap"><input type="checkbox" name="weekday[1]" id="weekday_1" value="1" <?php echo $weekdayvalue[1]; ?>  /> <?php echo returnIntLang('str monday'); ?></span> <span class="nowrap"><input type="checkbox" name="weekday[2]" id="weekday_2" value="2" <?php echo $weekdayvalue[2]; ?> /> <?php echo returnIntLang('str tuesday'); ?></span> <span class="nowrap"><input type="checkbox" name="weekday[3]" id="weekday_3" value="4" <?php echo $weekdayvalue[3]; ?> /> <?php echo returnIntLang('str wednesday'); ?></span> <span class="nowrap"><input type="checkbox" name="weekday[4]" id="weekday_4" value="8" <?php echo $weekdayvalue[4]; ?> /> <?php echo returnIntLang('str thursday'); ?></span> <span class="nowrap"><input type="checkbox" name="weekday[5]" id="weekday_5" value="16" <?php echo $weekdayvalue[5]; ?> /> <?php echo returnIntLang('str friday'); ?></span> <span class="nowrap"><input type="checkbox" name="weekday[6]" id="weekday_6" value="32" <?php echo $weekdayvalue[6]; ?> /> <?php echo returnIntLang('str saturday'); ?></span> <span class="nowrap"><input type="checkbox" name="weekday[7]" id="weekday_7" value="64" <?php echo $weekdayvalue[7]; ?> /> <?php echo returnIntLang('str sunday'); ?></span></td>
                    </tr>
                    <tr id="add_timebased">
                        <td class="tablecell two head"><?php echo returnIntLang('structure time based view'); ?></td>
                        <td class="tablecell two head"><input id="timebasedstart" type="text" placeholder="<?php echo returnIntLang('structure time based view starts', false); ?>" class="full" /></td>
                        <td class="tablecell two head"><input id="timebasedend" type="text" placeholder="<?php echo returnIntLang('structure time based view ends', false); ?>" class="full" /></td>
                        <td class="tablecell one head"></td>
                        <td class="tablecell one head"><a onclick="addTime();"><span class="bubblemessage green"><?php echo returnIntLang('bubble add', false); ?></span></a></td>
                    </tr>
                    <?php

                    $alltimes = trim($menueditdata['showtime']);
                    $time = array();
                    if ($alltimes!=""):
                        $giventimes = unserializeBroken($alltimes);
                        foreach ($giventimes AS $gkey => $gvalue):
                            $time[$gvalue[0]] = $gvalue[1];
                        endforeach;
                    endif;
                    ksort($time);

                    foreach ($time AS $key => $value):
                        echo "<tr id='time-".$key."'>";
                        echo "<td class=\"tablecell two\">&nbsp;</td>";
                        echo "<td class=\"tablecell five\">".date("d.m.Y", $key)." ".date("H:i", $key)." <input type=\"hidden\" name=\"timetable[".$key."]\" value=\"".$key.";".$value."\"/> - ".date("d.m.Y", $value)." ".date("H:i", $value)."</td>";
                        echo "<td class=\"tablecell one\"><a onClick=\"submitTimeRemove('".$key."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble false', false)."</span></a></td>";
                        echo "</tr>";
                    endforeach;
                    $time = array_flip($time);

                    if (intval(count($worklang['languages']['shortcut']))>1): 
                        echo '<tr>';
                        echo '<td class="tablecell two">'.returnIntLang('structure denylang').'</td>';
                        echo '<td class="tablecell six">';
                        foreach ($worklang['languages']['shortcut'] AS $key => $value):
                            if (!(is_array($langdescription)) || !(array_key_exists($value, $langdescription)) || $langdescription[$value]==""): $langdescription[$value] = $menueditdata['description']; endif; 
                            $checked = '';
                            if(is_array(unserializeBroken($menueditdata['denylang'])) && in_array($worklang['languages']['shortcut'][$key], unserializeBroken($menueditdata['denylang']))): $checked = " checked='checked' "; endif;
                            echo '<span class="nowrap"><input type="checkbox" name="denylang[]" value="'.$worklang['languages']['shortcut'][$key].'" '.$checked.' /> '.$worklang['languages']['longname'][$key].'</span> ';
                        endforeach;
                        echo '</td>';
                        echo '</tr>';
                    endif; ?>
                </table>
                <script language="JavaScript" type="text/javascript">
                <!--
                function addTime() {

                    var timebasedstart = document.getElementById('timebasedstart').value;
                    var timebasedend = document.getElementById('timebasedend').value;
                    var colstamp = new Date();
                    colstamp = colstamp.getTime();

                    if (timebasedstart!='' && timebasedend!='') {
                        // start time
                        if (timebasedstart.indexOf(' ') != -1) {
                            var startvalue = timebasedstart.split(" ");
                            // de
                            var startdate = startvalue[0].split(".");
                            if (startvalue[1]!='') {
                                var starttime = startvalue[1].split(":");
                                }
                            else {
                                var starttime = new Array(0, 0);
                                }
                            }
                        else if (timebasedstart.indexOf('.') != -1) {
                            // only date found
                            // de
                            var startdate = timebasedstart.split(".");
                            }
                        else {
                            var startdate = new Array();
                            var starttime = new Array(0, 0);
                            var calcstart = new Date();
                            startdate[0] = calcstart.getDate();
                            startdate[1] = calcstart.getMonth();
                            startdate[2] = calcstart.getYear();
                            }
                        // end time
                        if (timebasedend.indexOf(' ') != -1) {
                            var endvalue = timebasedend.split(" ");
                            // de
                            var enddate = endvalue[0].split(".");
                            if (endvalue[1]!='') {
                                var endtime = endvalue[1].split(":");
                                }
                            else {
                                var endtime = new Array(0, 0);
                                }
                            }
                        else if (timebasedend.indexOf('.') != -1) {
                            // only date found
                            // de
                            var enddate = timebasedend.split(".");
                            }
                        else {
                            var enddate = new Array();
                            var endtime = new Array(0, 0);
                            var calcend = new Date();
                            enddate[0] = calcend.getDate();
                            enddate[1] = calcend.getMonth();
                            enddate[2] = calcend.getYear();
                            }	

                        var newtimecol = '<tr id="time-' + colstamp + '"><td class="tablecell two">&nbsp;</td>';
                        newtimecol = newtimecol + '<td class="tablecell two">' + startdate[0] + '.' + startdate[1] + '.' + startdate[2] + ' ' + starttime[0] + ':' + starttime[1] + '</td>';
                        newtimecol = newtimecol + '<td class="tablecell three">' + enddate[0] + '.' + enddate[1] + '.' + enddate[2] + ' ' + endtime[0] + ':' + endtime[1] + '<input type="hidden" name="startdate[]" value="' + startdate[0] + '.' + startdate[1] + '.' + startdate[2] + ' ' + starttime[0] + ':' + starttime[1] + '" /><input type="hidden" name="enddate[]" value="' + enddate[0] + '.' + enddate[1] + '.' + enddate[2] + ' ' + endtime[0] + ':' + endtime[1] + '" /></td>';
                        newtimecol = newtimecol + '<td class="tablecell one"><span class="bubblemessage red" onclick="removeTime(' + colstamp + ')"><?php echo returnIntLang('bubble false', false); ?></span></td>';

                        $(newtimecol).insertAfter("#add_timebased");

                        document.getElementById('timebasedstart').value = '';
                        document.getElementById('timebasedend').value = '';
                        createFloatingTable();

                        }
                    else {
                        alert ('<?php echo returnIntLang('timebasedstart and/or timebasedend false', false); ?>');
                        }
                    }

                var startDateTextBox = $('#timebasedstart');
                var endDateTextBox = $('#timebasedend');

                startDateTextBox.datetimepicker({ 
                    timeFormat: 'HH:mm',
                    dateFormat: "dd.mm.yy",
                    minDate: "<?php echo date('d.m.Y'); ?>",
                    onClose: function(dateText, inst) {
            if (endDateTextBox.val() != '') {
                var testStartDate = startDateTextBox.datetimepicker('getDate');
                var testEndDate = endDateTextBox.datetimepicker('getDate');
                if (testStartDate > testEndDate)
                    endDateTextBox.datetimepicker('setDate', testStartDate);
            }
            else {
                endDateTextBox.val(dateText);
            }
        },
        onSelect: function (selectedDateTime){
            endDateTextBox.datetimepicker('option', 'minDate', startDateTextBox.datetimepicker('getDate') );
        }
    });
    endDateTextBox.datetimepicker({ 
        timeFormat: 'HH:mm',
        dateFormat: "dd.mm.yy",
        onClose: function(dateText, inst) {
            if (startDateTextBox.val() != '') {
                var testStartDate = startDateTextBox.datetimepicker('getDate');
                var testEndDate = endDateTextBox.datetimepicker('getDate');
                if (testStartDate > testEndDate)
                    startDateTextBox.datetimepicker('setDate', testEndDate);
            }
            else {
                startDateTextBox.val(dateText);
            }
        },
        onSelect: function (selectedDateTime){
            startDateTextBox.datetimepicker('option', 'maxDate', endDateTextBox.datetimepicker('getDate') );
        }
    });	

                // -->
                </script>
            </div>
        </fieldset>
        <script language="JavaScript" type="text/javascript">
        <!--

        function changeTarget(tDest) {
            document.getElementById(tDest + '_popup').style.display = 'none';
            if (document.getElementById('target' + tDest).value=='_popup') {
                document.getElementById(tDest + '_popup').style.display = 'block';
                }
            }

        // -->
        </script>
        <fieldset>
            <legend><?php echo returnIntLang('structure edit behaviour', true); ?> <?php echo legendOpenerCloser('structure_behaviour'); ?></legend>
            <div id="structure_behaviour">
                <p><?php echo returnIntLang('structure edit behaviour description', true); ?></p>
                <table class="tablelist">
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit generell target', true); ?></td>
                        <td class="tablecell two"><select name="targetfile" id="targetfile" class="one full" onchange="changeTarget('file');">
                            <option value="_none" <?php if ($menueditdata['filetarget']=="_none") echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target notarget', false); ?></option>
                            <option value="_parent" <?php if ($menueditdata['filetarget']=="_parent") echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target self', false); ?></option>
                            <option value="_blank" <?php if ($menueditdata['filetarget']=="_blank") echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target new', false); ?></option>
                            <option value="_popup" <?php if ($menueditdata['filetarget']=="_popup") echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target popup', false); ?></option>
                        </select></td>
                        <td class="tablecell four"><div id="file_popup" <?php if($menueditdata['filetarget']!="_popup") echo "style=\"display: none;\""; ?>><?php echo returnIntLang('str top', true); ?> <input type="text" name="filetop" id="filetop" size="4" value="<?php echo intval($menueditdata['filetop']); ?>" /> <?php echo returnIntLang('str left', true); ?> <input type="text" name="fileleft" id="fileleft" size="4" value="<?php echo intval($menueditdata['fileleft']); ?>" /> <?php echo returnIntLang('str height', true); ?> <input type="text" name="fileheight" id="fileheight" size="4" value="<?php echo intval($menueditdata['fileheight']); ?>" /> <?php echo returnIntLang('str width', true); ?> <input type="text" name="filewidth" id="filewidth" size="4" value="<?php echo intval($menueditdata['filewidth']); ?>" /></div></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit jscall mouseover', true); ?></td>
                        <td class="tablecell two"><input name="jscallmouseover" type="text" value="<?php echo $menueditdata['jsmouseover']; ?>" class="full" /></td>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit jscall click', true); ?></td>
                        <td class="tablecell two"><input name="jscallclick" type="text" value="<?php echo $menueditdata['jsclick']; ?>" class="full" /></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit jscall mouseout', true); ?></td>
                        <td class="tablecell six"><input name="jscallmouseout" type="text" value="<?php echo $menueditdata['jsmouseout']; ?>" class="full" /></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit linkintern', true); ?> <?php helpText(returnIntLang('structure edit linkintern help', false)); ?></td>
                        <td class="tablecell six"><select name="urlintern" id="urlintern" class="full">
                            <option value="0"><?php echo returnIntLang('structure edit internlink settarget', true); ?></option>
                            <?php getMenuLevel(0, 0, gmlSelect, array($menueditdata['internlink_id'])); ?>
                        </select></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit linkdocument', true); ?> <?php helpText(returnIntLang('structure edit linkdocument help', false)); ?></td>
                        <td class="tablecell six"><select name="docintern" id="docintern" class="three full">
                            <option value=""><?php echo returnIntLang('structure edit documentlink settarget', true); ?></option>
                            <?php echo getDownloadFiles($path='/', array($menueditdata['docintern']), ''); ?>
                        </select></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit linkintern target', true); ?> <?php helpText(returnIntLang('structure edit linkintern target help', false)); ?></td>
                        <td class="tablecell two"><select name="targetintern" id="targetintern" onchange="changeTarget('intern');" class="full">
                            <option value="_parent" <?php if ($menueditdata['interntarget'] == '_parent' || intval($menueditdata['internlink_id']) == "0") echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target self', true); ?></option>
                            <option value="_blank" <?php if ($menueditdata['interntarget'] == '_blank') echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target new', true); ?></option>
                            <option value="_popup" <?php if ($menueditdata['interntarget'] == '_popup') echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target popup', true); ?></option>
                        </select></td>
                        <td class="tablecell four"><div id="intern_popup" <?php if($menueditdata['interntarget']!="_popup") echo "style=\"display: none;\""; ?>><?php echo returnIntLang('str top', true); ?> <input type="text" name="interntop" id="interntop" size="4" value="<?php echo intval($menueditdata['interntop']); ?>" /> <?php echo returnIntLang('str left', true); ?> <input type="text" name="internleft" id="internleft" size="4" value="<?php echo intval($menueditdata['internleft']); ?>" /> <?php echo returnIntLang('str height', true); ?> <input type="text" name="internheight" id="internheight" size="4" value="<?php echo intval($menueditdata['internheight']); ?>" /> <?php echo returnIntLang('str width', true); ?> <input type="text" name="internwidth" id="internwidth" size="4" value="<?php echo intval($menueditdata['internwidth']); ?>" /></div></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit linkextern', true); ?> <?php helpText(returnIntLang('structure edit linkextern help', false)); ?></td>
                        <td class="tablecell six"><input name="offlink" type="text" value="<?php echo trim($menueditdata['offlink']); ?>" class="full" /></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit linkextern target', true); ?> <?php helpText(returnIntLang('structure edit linkextern target help', false)); ?></td>
                        <td class="tablecell two"><select name="targetextern" id="targetextern" onchange="changeTarget('extern');" class="full">
                            <option value="_parent" <?php if ($menueditdata['externtarget'] == '_parent') echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target self', true); ?></option>
                            <option value="_blank" <?php if ($menueditdata['externtarget'] == '_blank' || $menueditdata['externtarget']=="") echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target new', true); ?></option>
                            <option value="_popup" <?php if ($menueditdata['externtarget'] == '_popup') echo "selected=\"selected\""; ?>><?php echo returnIntLang('structure edit generell target popup', true); ?></option>
                        </select></td>
                        <td class="tablecell four"><div id="extern_popup" <?php if($menueditdata['externtarget']!="_popup") echo "style=\"display: none;\""; ?>><ul class="innercell block">
                            <li><?php echo returnIntLang('str top', true); ?></li>
                            <li><input type="text" name="externtop" id="externtop" size="4" value="<?php echo intval($menueditdata['externtop']); ?>" /></li>
                        </ul><ul class="innercell block">
                            <li><?php echo returnIntLang('str left', true); ?></li>
                            <li><input type="text" name="externleft" id="externleft" size="4" value="<?php echo intval($menueditdata['externleft']); ?>" /></li>
                        </ul><ul class="innercell block">
                            <li><?php echo returnIntLang('str height', true); ?></li>
                            <li><input type="text" name="externheight" id="externheight" size="4" value="<?php echo intval($menueditdata['externheight']); ?>" /></li>
                        </ul><ul class="innercell block">
                            <li><?php echo returnIntLang('str width', true); ?></li>
                            <li><input type="text" name="externwidth" id="externwidth" size="4" value="<?php echo intval($menueditdata['externwidth']); ?>" /></li>
                        </ul></div></td>
                    </tr>
                    <?php

                    $subtest_sql = "SELECT * FROM `menu` WHERE `connected` = ".intval($mid);
                    $subtest_res = doSQL($subtest_sql);

                    if ($subtest_res['num']==0):
                        // abfrage nach plugins
                        // muss konzeptionell noch mal ueberdacht werden
                        /*
                        ?>
                        <li class="tablecell two"><?php echo returnIntLang('structure edit pluginmenu', true); ?></li>
                        <li class="tablecell six"><pre>data</pre></li>
                        <?php
                        */

                        $dmenu_sql = "SELECT `guid`, `title` FROM `templates_menu` WHERE `module_guid` != '' ORDER BY `title`";
                        $dmenu_res = doSQL($dmenu_sql);
                        if ($dmenu_res['num']>0):
                            $edittemp_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($menueditdata['real_templates_id']);
                            $edittemp_res = doSQL($edittemp_sql);
                            $noinc = array();
                            if ($edittemp_res['num']>0):
                                $edittemp_temp = stripslashes(trim($edittemp_res['set'][0]["template"]));
                                foreach ($dmenu_res['set'] AS $dmresk => $dmresv):
                                    if (str_replace("<% MENUVAR:".strtoupper(trim($dmresv["guid"]))." %>", "-", $edittemp_temp)!=$edittemp_temp):
                                        $noinc[] = trim($dmresv["guid"]);
                                    endif;
                                endforeach;	
                            endif;

                            ?>
                            <tr>
                                <td class="tablecell two">DynamicMenu: <abbr title="Dynamische Men&uuml;s sind zusammengesetzte Men&uuml;s aus mehreren Men&uuml;parsern.">[?]</abbr><select style="visibility: hidden;"></select></td>
                                <td class="tablecell six"><select name="dynamicguid" id="dynamicguid" class="full">
                                    <option value="">Kein Men&uuml; ausgew&auml;hlt</option>
                                    <?php
                                    foreach ($dmenu_res['set'] AS $dmresk => $dmresv):
                                        if (!(in_array(trim($dmresv["guid"]), $noinc))):
                                            echo "<option value=\"".trim($dmresv["guid"])."\" ";
                                            if ($dynamicguid==trim($dmresv["guid"])):
                                                echo "selected=\"selected\"";
                                            endif;
                                            echo ">".((trim($dmresv["title"])!='')?trim($dmresv["title"]):trim($dmresv["guid"]))."</option>";
                                        endif;
                                    endforeach;
                                    ?>
                                </select></td>
                            </tr>
                            <?php
                        endif;
                    endif; ?>
                </table>
            </div>
        </fieldset>
        <fieldset>
            <legend><?php echo returnIntLang('structure edit image', true); ?> <?php echo legendOpenerCloser('structure_menupic'); ?></legend>
            <div id="structure_menupic">
                <p style="clear: both;"><?php echo returnIntLang('structure edit image desc', true); ?></p>
                <table class="tablelist">
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit image inactive', true); ?></td>
                        <td class="tablecell four"><select id="menuimg_imageoff" name="imageoff" class="autocombo">
                            <option value=""></option>
                            <?php  echo imageSelect('/screen/', '', false, $menueditdata['imageoff'], 150, true); ?>
                        </select> <!-- choose from multiselect imageoff --></td>
                        <td class="tablecell two"><?php if(trim($menueditdata['imageoff'])!=""): echo "<img src='".$menueditdata['imageoff']."' border='0' id='menuimg_imageoff_preview' class='autocombo-previewimg' />"; else: echo "<img src='/".$_SESSION['wspvars']['wspbasedir']."/media/screen/no.gif' border='0' id='menuimg_imageoff_preview' class='autocombo-previewimg' />"; endif; ?></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit image active', true); ?></td>
                        <td class="tablecell four"><select id="menuimg_imageakt" name="imageakt" class="autocombo">
                            <option value=""></option>
                            <?php  echo imageSelect('/screen/', '', false, array($menueditdata['imageakt']), 150, true); ?>
                        </select> <!-- choose from multiselect imageakt --></td>
                        <td class="tablecell two"><?php if(trim($menueditdata['imageakt'])!=""): echo "<img src=\"".$menueditdata['imageakt']."\" border=\"0\" id=\"menuimg_imageakt_preview\" class=\"autocombo-previewimg\" />"; else: echo "<img src='/".$_SESSION['wspvars']['wspbasedir']."/media/screen/no.gif' border='0' id='menuimg_imageakt_preview' class='autocombo-previewimg' />"; endif; ?></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit image mouseover', true); ?></td>
                        <td class="tablecell four"><select id="menuimg_imageon" name="imageon" class="autocombo">
                            <option value=""></option>
                            <?php  echo imageSelect('/screen/', '', false, $menueditdata['imageon'], 150, true); ?>
                        </select> <!-- choose from multiselect imageon --></td>
                        <td class="tablecell two"><?php if(trim($menueditdata['imageon'])!=""): echo "<img src=\"".$menueditdata['imageon']."\" border=\"0\" id=\"menuimg_imageon_preview\" class=\"autocombo-previewimg\" />"; else: echo "<img src='/".$_SESSION['wspvars']['wspbasedir']."/media/screen/no.gif' border='0' id='menuimg_imageon_preview' class='autocombo-previewimg' />"; endif; ?></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit image clicked', true); ?></td>
                        <td class="tablecell four"><select id="menuimg_imageclick" name="imageclick" class="autocombo">
                            <option value=""></option>
                            <?php  echo imageSelect('/screen/', '', false, $menueditdata['imageclick'], 150, true); ?>
                        </select> <!-- choose from multiselect imageclick --></td>
                        <td class="tablecell two"><?php if(trim($menueditdata['imageclick'])!=""): echo "<img src=\"".$menueditdata['imageclick']."\" border=\"0\" id=\"menuimg_imageclick_preview\" class=\"autocombo-previewimg\" />"; else: echo "<img src='/".$_SESSION['wspvars']['wspbasedir']."/media/screen/no.gif' border='0' id='menuimg_imageclick_preview' class='autocombo-previewimg' />"; endif; ?></td>
                    </tr>
                </table>
            </div>
        </fieldset>
        <fieldset>
            <legend><?php echo returnIntLang('structure edit meta', true); ?> <?php echo legendOpenerCloser('structure_meta'); ?></legend>
            <div id="structure_meta">
                <?php

                $sitemeta_sql = "SELECT * FROM `wspproperties` WHERE `varname` LIKE 'site%'";
                $sitemeta_res = doSQL($sitemeta_sql);
                if ($sitemeta_res['num']>0):
                    foreach ($sitemeta_res['set'] AS $sresk => $sresv):
                        $sitedata[trim($sresv['varname'])] = $sresv['varvalue'];
                    endforeach;
                endif;

                $pagemeta_sql = "SELECT * FROM `pageproperties` WHERE `mid` = ".intval($mid);
                $pagemeta_res = doSQL($pagemeta_sql);
                if ($pagemeta_res['num']>0):
                    $menueditdata['pagetitle'] = trim($pagemeta_res['set'][0]['pagetitle']);
                    $menueditdata['pagekeys'] = trim($pagemeta_res['set'][0]['pagekeys']);
                    $menueditdata['pagedesc'] = trim($pagemeta_res['set'][0]['pagedesc']);
                endif;

                if (array_key_exists('pagetitle', $menueditdata) && trim($menueditdata['pagetitle'])!=''): $sitedata['sitetitle'] = $menueditdata['pagetitle']; endif;
                if (array_key_exists('pagedesc', $menueditdata) && trim($menueditdata['pagedesc'])!=''): $sitedata['sitedesc'] = $menueditdata['pagedesc']; endif;
                if (array_key_exists('pagekeys', $menueditdata) && trim($menueditdata['pagekeys'])!=''): $sitedata['sitekeys'] = $menueditdata['pagekeys'].",".$sitedata['sitekeys']; endif;

                ?>
                <table class="tablelist">
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit meta title', true); ?> <?php helpText(returnIntLang('structure edit meta title help', false)); ?></td>
                        <td class="tablecell four"><input name="pagetitle" id="pagetitle" type="text" value="<?php if(array_key_exists('pagetitle', $menueditdata)) echo prepareTextField(stripslashes($menueditdata['pagetitle'])); ?>" maxlength="250" class="four full" onkeyup="showPageQuality('pagetitle',80,200,<?php echo strlen(prepareTextField(stripslashes($sitedata['sitetitle']))) ?>);" /></td>
                        <td class="tablecell two"><span id="show_pagetitle_length"><?php if(array_key_exists('pagetitle', $menueditdata)): echo strlen(prepareTextField(stripslashes($menueditdata['pagetitle']))); else: echo 0; endif; ?></span> (<?php echo returnIntLang('seo str max'); ?> 200) <?php echo returnIntLang('str chars'); ?></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit meta desc', true); ?> <?php helpText(returnIntLang('structure edit meta desc help', false)); ?></td>
                        <td class="tablecell four"><textarea name="pagedesc" id="pagedesc" cols="20" rows="5" class="four full small noresize" onkeyup="showPageQuality('pagedesc',150,300,<?php echo strlen(prepareTextField(stripslashes($sitedata['sitedesc']))) ?>);"><?php if(array_key_exists('pagedesc', $menueditdata)) echo prepareTextField(stripslashes($menueditdata['pagedesc'])); ?></textarea></td>
                        <td class="tablecell two"><span id="show_pagedesc_length"><?php echo strlen(prepareTextField(stripslashes($menueditdata['pagedesc']))); ?></span> (<?php echo returnIntLang('seo str max'); ?> 300) <?php echo returnIntLang('str chars'); ?></td>
                    </tr>
                    <tr>
                        <td class="tablecell two"><?php echo returnIntLang('structure edit meta keywords', true); ?> <?php helpText(returnIntLang('structure edit meta keywords help', false)); ?></td>
                        <td class="tablecell four"><textarea name="pagekeys" id="pagekeys" cols="20" rows="7" class="four full medium noresize" onkeyup="showPageQuality('pagekeys',200,1000,<?php echo strlen(prepareTextField(stripslashes($sitedata['sitekeys']))) ?>);"><?php if(array_key_exists('pagekeys', $menueditdata)) echo prepareTextField(stripslashes($menueditdata['pagekeys'])); ?></textarea></td>
                        <td class="tablecell two"><span id="show_pagekeys_length"><?php echo strlen(prepareTextField(stripslashes($menueditdata['pagekeys']))); ?></span> (<?php echo returnIntLang('seo str max'); ?> 1000) <?php echo returnIntLang('str chars'); ?></td>
                    </tr>
                </table>
            </div>
            <script language="JavaScript" type="text/javascript">
            <!--

            showPageQuality('pagetitle',80,200,<?php echo strlen(prepareTextField(stripslashes($sitedata['sitetitle']))) ?>);
            showPageQuality('pagedesc',150,300,<?php echo strlen(prepareTextField(stripslashes($sitedata['sitedesc']))) ?>);
            showPageQuality('pagekeys',200,1000,<?php echo strlen(prepareTextField(stripslashes($sitedata['sitekeys']))) ?>);

            // -->
            </script>
        </fieldset>
				
        <fieldset>
            <legend><?php echo returnIntLang('structure edit addon', true); ?> <?php echo legendOpenerCloser('structure_pageaddons'); ?></legend>
            <div id="structure_pageaddons">
                <ul class="tablelist">
                    <li class="tablecell two"><?php echo returnIntLang('structure edit addon jsfiles', true); ?> <?php helpText(returnIntLang('structure edit addon jsfiles help', false)); ?></li>
                    <li class="tablecell six">
                        <?php

                        $extrajs = unserializeBroken($menueditdata['addscript']);

                        $jsuse_sql = "SELECT `javascript_id` FROM `r_temp_jscript` WHERE `templates_id` = '".intval($menueditdata['real_templates_id'])."'";
                        $jsuse_res = doSQL($jsuse_sql);
                        if ($jsuse_res['num']>0):
                            $usejs = array();
                            foreach ($jsuse_res['set'] AS $jresk => $jresv):
                                $usejs[] = " `id` != ".intval($jresv['javascript_id'])." ";
                            endforeach;
                            $js_sql = "SELECT * FROM `javascript` WHERE ".implode(" AND ", $usejs);
                        else:
                            $js_sql = "SELECT * FROM `javascript`";
                        endif;
                        $js_res = doSQL($js_sql);
                        if ($js_res['num']>0):
                            foreach ($js_res['set'] AS $jresk => $jresv):
                                echo "<ul class=\"innercell block\"><li><input type=\"checkbox\" id=\"addjs_check_".$jresk."\" name=\"usejs[]\" value=\"".intval($jresv['id'])."\" ";
                                if (is_array($extrajs) && in_array(intval($jresv['id']), $extrajs)):
                                    echo " checked=\"checked\"";
                                endif;
                                echo " /></li><li><label for=\"addjs_check_".$jresk."\">".setUTF8(trim($jresv['describ']))."</label></li></ul>";
                            endforeach;
                        else:
                            echo returnIntLang('structure edit all js-files used in templage');
                        endif;
                        ?>
                    </li>
                    <li class="tablecell two"><?php echo returnIntLang('structure edit addon cssfiles', true); ?> <?php helpText(returnIntLang('structure edit addon cssfiles help', false)); ?></li>
                    <li class="tablecell six"><table border="0" cellspacing="0" cellpadding="0">
                        <?php

                        $extracss = unserializeBroken($menueditdata['addcss']);

                        $cssuse_sql = "SELECT `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = ".intval($menueditdata['real_templates_id']);
                        $cssuse_res = doSQL($cssuse_sql);
                        if ($cssuse_res['num']>0):
                            $usecss = array();
                            foreach ($cssuse_res['set'] AS $cresk => $cresv):
                                $usecss[] = " `id` != ".intval($cresv['stylesheets_id'])." ";
                            endforeach;
                            $css_sql = "SELECT * FROM `stylesheets` WHERE ".implode(" AND ", $usecss);
                        else:
                            $css_sql = "SELECT * FROM `stylesheets`";
                        endif;
                        $css_res = doSQL($css_sql);
                        if ($css_res['num']>0):
                            foreach ($css_res['set'] AS $cresk => $cresv):
                                echo "<ul class=\"innercell block\"><li><input type=\"checkbox\" id=\"addcss_check_".$cresk."\" name=\"usecss[]\" value=\"".intval($cresv['id'])."\" ";
                                if (is_array($extracss) && in_array(intval($cresv['id']), $extracss)):
                                    echo " checked=\"checked\"";
                                endif;
                                echo " /></li><li><label for=\"addcss_check_".$cresk."\">".setUTF8(trim($cresv['describ']))."</label></li></ul>";
                            endforeach;	
                        else:
                            echo returnIntLang('structure edit all css-files used in templage');
                        endif;

                        ?>
                    </table></li>
                    <li class="tablecell two"><?php echo returnIntLang('structure edit addon cssclass', true); ?> <?php helpText(returnIntLang('structure edit addon cssclass help', false)); ?></li>
                    <li class="tablecell six"><input type="text" name="useclass" value="<?php echo $menueditdata['addclass']; ?>" /></li> 
                </ul>
            </div>
        </fieldset>
        <input name="backjump" id="backjump" type="hidden" value="" />
        <input name="op" id="ophidden" type="hidden" value="" />
        <input name="mid" type="hidden" value="<?php echo intval($mid); ?>" />
	</form>
	<fieldset class="options">
		<p><a onclick="saveMenuEdit(false);" class="greenfield"><?php echo returnIntLang('str save', true); ?></a> <a onclick="saveMenuEdit(true);" class="greenfield"><?php echo returnIntLang('btn save and back', true); ?></a> <a href="menuedit.php" class="orangefield"><?php echo returnIntLang('str back', true); ?></a></p>
	</fieldset>
</div>
<?php endif; ?>
<?php require ("./data/include/footer.inc.php"); ?>
<!-- EOF -->