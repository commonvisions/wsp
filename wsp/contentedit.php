<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2023-01-04
 * fixed error with missing clsInterpreter
 * 
 * 2023-01-06
 * fixed error with getview call with 4 params 
 * 
 * 2023-01-08
 * fixed error with typo in clsInterpreter
 * 
 * 2023-01-09
 * fixed deprecation errors
 * 
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
/* if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php"); */
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

// which content shall be edited
$cid = 0;
if (array_key_exists('wspvars', $_SESSION) && array_key_exists('editcontentid', $_SESSION['wspvars']) && intval($_SESSION['wspvars']['editcontentid'])>0):
	$cid = intval($_SESSION['wspvars']['editcontentid']);
endif;
if (isset($_POST['editcontentid']) && intval($_POST['editcontentid'])>0):
	$_SESSION['wspvars']['editcontentid'] = intval($_POST['editcontentid']);
	$cid = intval($_POST['editcontentid']);
endif;
if (intval($cid)==0):
	// jump back, if no content was found
	header("location: contentstructure.php");
	die();
endif;
/* define actual system position ------------- */
$_SESSION['wspvars']['menuposition'] = "contentedit";
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].";cid=".$cid;
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = true;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// page specific includes --------------------
require ("./data/include/clsinterpreter.inc.php");
// define page specific vars -----------------
$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
$isGeneric = false;
$isInterpreter = false;
$backupView = false;

if (!($_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==4 || $_SESSION['wspvars']['rights']['contents']==5 || $_SESSION['wspvars']['rights']['contents']==7)):
	$_SESSION['wspvars']['rights']['contents_array'] = array();
endif;

/* page specific funcs ----------------------- */

if (!(function_exists('subMID'))) {
    function subMID($mid) {
        $connected_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($mid);
        $connected_res = doSQL($connected_sql);
        if ($connected_res['num']>0) {
            foreach ($connected_res['set'] AS $cdrsk => $cdrsv) {
                $GLOBALS['midlist'][] = intval($cdrsv['mid']);
                subMID($cdrsv['mid']);
            }
        }
    }
}

// remove backup
if (isset($_POST) && array_key_exists('op', $_POST) && trim($_POST['op'])=='removebackup' && array_key_exists('bid', $_POST) && intval($_POST['bid'])>0) {
	doSQL("DELETE FROM `content_backup` WHERE `cbid` = ".intval($_POST['bid']));
}
// restore backup
if (isset($_POST) && array_key_exists('op', $_POST) && trim($_POST['op'])=='restorebackup') {
	$restore_sql = "SELECT `valuefields` FROM `content_backup` WHERE `cbid` = ".intval($_POST['bid']);
	$restore_res = doResultSQL($restore_sql);
	if ($restore_res!==false) {
		$backupValues = unserializeBroken($restore_res);
        if (is_array($backupValues)) {
            $backupView = true;
        } else {
            addWSPMsg('errormsg', returnIntLang('contentedit backup no found', false));
        }
	}
    else {
		addWSPMsg('errormsg', returnIntLang('contentedit backup no found', false));
	}
}
// localtoglobal
if (isset($_REQUEST['op']) && $_REQUEST['op'] == "localtoglobal") {
	$convert_sql = "SELECT * FROM `content` WHERE `cid` = ".intval($_REQUEST['cid']);
	$convert_res = doSQL($convert_sql);
	if ($convert_res['num']>0) {
		$sql = "INSERT INTO `globalcontent` SET `id`='', `content_lang` = '".$_SESSION['wspvars']['workspacelang']."', `valuefield` = '".escapeSQL($convert_res['set'][0]['valuefields'])."', `interpreter_guid` = '".escapeSQL($convert_res['set'][0]['interpreter_guid'])."'";
        $res = doSQL($sql);
		if (intval($res['inf'])>0) {
			$lastid = intval($res['inf']);
			doSQL("UPDATE `content` SET `valuefields` = '', `globalcontent_id` = ".intval($lastid)." WHERE `cid` = ".intval($_REQUEST['cid']));
			addWSPMsg('noticemsg', returnIntLang('content converted to global content'));
		} else {
			addWSPMsg('noticemsg', returnIntLang('content could not be converted to global content'));
		}
	}
}
// globaltolocal
if (isset($_REQUEST['op']) && $_REQUEST['op'] == "globaltolocal") {
	$convert_sql = "SELECT `globalcontent_id` FROM `content` WHERE `cid` = ".intval($_REQUEST['cid']);
	$convert_res = doResultSQL($convert_sql);
	if ($convert_res!==false) {
        $gc_sql = "SELECT * FROM `globalcontent` WHERE `id` = ".intval($convert_res);
        $gc_res = doSQL($gc_sql);
        if ($gc_res['num']>0) {
            $sql = "UPDATE `content` SET `globalcontent_id` = 0, `valuefields` = '".escapeSQL(trim($gc_res['set'][0]['valuefield']))."', `lastchange` = '".time()."', `interpreter_guid` = '".escapeSQL(trim($gc_res['set'][0]['interpreter_guid']))."' WHERE `cid` = ".intval($_REQUEST['cid']);
            $res = doSQL($sql);
            if ($res['aff']==1) {
                addWSPMsg('resultmsg', returnIntLang('contentedit global2local success'));
            }
        }
        else {
            $_SESSION['wspvars']['errormsg'].= returnIntLang('contentedit global2local failure2');
        }
	} 
    else {
		$_SESSION['wspvars']['errormsg'].= returnIntLang('contentedit global2local failure3');
	}
}
// text2generic
if (isset($_REQUEST) && array_key_exists('op', $_REQUEST) && trim($_REQUEST['op'])=='togeneric') {
	doSQL("UPDATE `content` SET `interpreter_guid` = 'genericwysiwyg' WHERE `cid` = ".intval($cid));
}
// save content
if (isset($_POST) && array_key_exists('op', $_POST) && trim($_POST['op'])=='save') {
	// set var to check data for used images or documents
    $checkdata = false;
    // get some data for backup and information of content
	$b_sql = "SELECT `cid`, `interpreter_guid`, `valuefields`, `lastchange`, `content_lang` FROM `content` WHERE `cid` = ".intval($cid);
	$b_res = doSQL($b_sql);
	if ($b_res['num']>0):
		// save backup from existing content
		$bb_sql = "INSERT INTO `content_backup` SET 
			`cid` = ".intval($b_res['set'][0]['cid']).", 
			`uid` = ".intval($_SESSION['wspvars']['userid']).", 
			`valuefields` = '".escapeSQL($b_res['set'][0]['valuefields'])."', 
			`lastchange` = ".intval($b_res['set'][0]['lastchange']).", 
			`lastbackup` = ".time().", 
			`content_lang` = '".trim($b_res['set'][0]['content_lang'])."'";
        $bb_res = doSQL($bb_sql);
		if ($bb_res['res']===false):
			addWSPMsg('errormsg', returnIntLang('contentedit backup not saved', false));
		endif;
	endif;
	// get savedata from interpreter
	if ($b_res['num']>0 && trim($b_res['set'][0]['interpreter_guid'])!='') {
		$i_sql = "SELECT `parsefile` FROM `interpreter` WHERE `guid` = '".trim($b_res['set'][0]['interpreter_guid'])."'";
		$i_res = doResultSQL($i_sql);
		if ($i_res!==false && trim($i_res)!='') {
			$file = trim($i_res);
            if (is_file("./data/interpreter/".$file)) {
                include_once ("./data/interpreter/".$file);
                $clsInterpreter = new $interpreterClass;
                $data = $clsInterpreter->getSave();
                if (isset($clsInterpreter) && method_exists($clsInterpreter, 'closeInterpreterDB')) {
                    $clsInterpreter->closeInterpreterDB();
                }
            }
        }
    }
	// OR get content from $_POST['field'];
	if (!(isset($data)) || trim($data)=='') {
		$data = serialize($_POST['field']);
	}
	// update global contents
    if(isset($_POST['gcid']) && intval($_POST['gcid'])>0) {
		// setup global content table
		$content_sql = "UPDATE `globalcontent` SET ";
		$content_sql.= " `valuefield` = '".escapeSQL($data)."' "; // valuefields
		$content_sql.= " WHERE `id` = ".intval($_POST['gcid']);
		// do sql statement
		doSQL($content_sql);
		// setup content table
		$content_sql = "UPDATE `content` SET ";
		// uid 2015-10-14
		$content_sql.= " `uid` = '".intval($_SESSION['wspvars']['userid'])."', "; // visibility
		//	sid
		$content_sql.= " `visibility` = '".intval($_POST['visible'])."', "; // visibility
		$content_sql.= " `content_lang` = '".$_POST['lang']."', "; // content_lang
		$content_sql.= " `showday` = '".intval(array_sum($_POST['weekday']))."', "; // showday
		$timetable = array();
		if (array_key_exists('startdate', $_POST) && is_array($_POST['startdate'])) {
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
        }
		if (array_key_exists('timetable', $_POST) && is_array($_POST['timetable'])) {
			foreach ($_POST['timetable'] AS $tk => $tv) {
				$timetable[] = explode(";", $tv);
            }
        }
		if (count($timetable)>0):
			$content_sql.= "`showtime` = '".escapeSQL(serialize($timetable))."', ";
		else:
			$content_sql.= "`showtime` = '', ";	
		endif;
		$content_sql.= "`container` = '".intval($_POST['container'])."', ";
		$content_sql.= "`containerclass` = '".escapeSQL(trim($_POST['containerclass']))."', ";
		$content_sql.= "`displayclass` = '".intval($_POST['displayclass'])."', ";
		$content_sql.= "`containeranchor` = '".escapeSQL(trim($_POST['containeranchor']))."', ";
		$content_sql.= " `lastchange` = '".time()."', "; // lastchange
		// adding special users to content control
		if (intval($_POST['visible'])>3 && isset($_POST['userrestriction']) && is_array($_POST['userrestriction']) && count($_POST['userrestriction'])>0):
			$content_sql.= " `logincontrol` = '".escapeSQL(serialize($_POST['userrestriction']))."' ";
		else:
			$content_sql.= " `logincontrol` = '' ";
		endif;
		$content_sql.= " WHERE `cid` = ".intval($cid);
		// do sql statement
		$content_res = doSQL($content_sql);
        if ($content_res['res']) {
            addWSPMsg('resultmsg', returnIntLang('global content was updated'));
            $checkdata = true;
        }
    }
    // update contens
	else {
		$content_sql = "UPDATE `content` SET ";
		// uid 2015-10-14
		$content_sql.= " `uid` = '".intval($_SESSION['wspvars']['userid'])."', "; // visibility
		//	sid
		$content_sql.= " `visibility` = '".intval($_POST['visible'])."', "; // visibility
		$content_sql.= " `content_lang` = '".$_POST['lang']."', "; // content_lang
		$content_sql.= " `valuefields` = '".escapeSQL($data)."', "; // valuefields
		$content_sql.= " `showday` = '".intval(array_sum($_POST['weekday']))."', "; // showday
		
		$timetable = array();
		if (array_key_exists('startdate', $_POST) && is_array($_POST['startdate'])) {
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
		}
		if (array_key_exists('timetable', $_POST) && is_array($_POST['timetable'])) {
			foreach ($_POST['timetable'] AS $tk => $tv) {
				$timetable[] = explode(";", $tv);
			}
		}
		if (count($timetable)>0):
			$content_sql.= "`showtime` = '".escapeSQL(serialize($timetable))."', ";
		else:
			$content_sql.= "`showtime` = '', ";	
		endif;
	
		if (array_key_exists('xajaxfunc', $_POST) && isset($_POST['xajaxfunc'])) $content_sql.= " `xajaxfunc` = '".escapeSQL(trim($_POST['xajaxfunc']))."', ";
		if (array_key_exists('xajaxfuncnames', $_POST) && isset($_POST['xajaxfuncnames'])) $content_sql.= " `xajaxfuncnames` = '".escapeSQL(trim($_POST['xajaxfuncnames']))."', ";
		
		$content_sql.= "`container` = '".intval($_POST['container'])."', ";
		$content_sql.= "`containerclass` = '".escapeSQL(trim($_POST['containerclass']))."', ";
		$content_sql.= "`displayclass` = '".intval($_POST['displayclass'])."', ";
		$content_sql.= "`containeranchor` = '".escapeSQL(trim($_POST['containeranchor']))."', ";
		$content_sql.= " `lastchange` = '".time()."', "; // lastchange
		
		// adding special users to content control
		if (intval($_POST['visible'])>3 && isset($_POST['userrestriction']) && is_array($_POST['userrestriction']) && count($_POST['userrestriction'])>0):
			$content_sql.= " `logincontrol` = '".escapeSQL(serialize($_POST['userrestriction']))."' ";
		else:
			$content_sql.= " `logincontrol` = '' ";
		endif;
		$content_sql.= " WHERE `cid` = ".intval($cid);
		// do sql statement
		$content_res = doSQL($content_sql);
        if ($content_res['res']) {
            addWSPMsg('resultmsg', returnIntLang('content was updated'));
            $checkdata = true;
        }
	}
	// check for fileusage
    if ($checkdata) {
        // check for file or document usage in $data
        $ffs = array();
        $img = '/src="\/[\/a-z0-9\.\-\_]*"/m';
        $doc = '/href="\/[\/a-z0-9\.\-\_]*"/m';
        foreach (unserializeBroken($data) AS $dk => $dv) {
            if (is_array($dv)) {
                foreach ($dv AS $dvk => $dvv) {
                    if (!(is_array($dvv))) {
                        $ffs[] = trim($dvv);
                    }
                }
            } else {
                preg_match_all($img, $dv, $matches, PREG_SET_ORDER, 0);
                if (is_array($matches) && count($matches)>0) {
                    foreach ($matches AS $mdata) {
                        $ffs[] = str_replace("'", "", str_replace("\"", "", str_replace("src='", "", str_replace("src=\"", "", $mdata[0]))));
                    }
                }
                preg_match_all($doc, $dv, $matches, PREG_SET_ORDER, 0);
                if (is_array($matches) && count($matches)>0) {
                    foreach ($matches AS $mdata) {
                        $ffs[] = str_replace("'", "", str_replace("\"", "", str_replace("href='", "", str_replace("href=\"", "", $mdata[0]))));
                    }
                }
            }
        }
        foreach ($ffs AS $value) {
            doSQL("UPDATE `wspmedia` SET `embed` = 1 WHERE `filepath` = '".$value."'");
            doSQL("UPDATE `wspmedia` SET `embed` = 1 WHERE CONCAT(`mediafolder`,`filename`) = '".$value."'");
            doSQL("UPDATE `wspmedia` SET `embed` = 1 WHERE `filename` = '".basename($value)."'");
        }
    }
	// updating user restrictions
    // yet missing ;)
	// updating menu for changed content
	$minfo_sql = "SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($_POST['editmid']);
	$minfo_res = doResultSQL($minfo_sql);
	$ccres = 0; if ($minfo_res!==false): $ccres = intval($minfo_res); endif;
	$nccres = 0; if ($ccres==0): $nccres = 2;
	elseif ($ccres==1): $nccres = 3;
	elseif ($ccres==2): $nccres = 2;
	elseif ($ccres==3): $nccres = 3;
	elseif ($ccres==4): $nccres = 5;
	elseif ($ccres==5): $nccres = 5;
	endif;
	$minfo_sql = "UPDATE `menu` SET `contentchanged` = ".intval($nccres)." WHERE `mid` = ".intval($_POST['editmid']);
	doSQL($minfo_sql);
    // do auto parse setup queue
	if (isset($_SESSION['wspvars']['autoparsecontent']) && $_SESSION['wspvars']['autoparsecontent']==1) {
		// autoparse contents 2016-03-23
		$pub_sql = "INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".time()."', `action` = 'publishcontent', `param` = '".intval($_POST['editmid'])."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($_POST['lang'])."', `output` = ''";
		$pub_res = doSQL($pub_sql);
        if ($pub_res['res']===false) {
			addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
		}
    }
	// jump back if required
	if(isset($_POST['back']) && intval($_POST['back'])==1) {
		// jump back
		header("location: contentstructure.php");
		die();
	}
}
// adding content ON page
// yet not supported since wsp 6.7
// get content info
$contentinfo_sql = "SELECT * FROM `content` WHERE `cid` = ".intval($cid);
$contentinfo_res = doSQL($contentinfo_sql);
$contentinfo_num = $contentinfo_res['num'];
$contentinfo_data = array(); if ($contentinfo_num>0) { $contentinfo_data = $contentinfo_res['set'][0]; }

/* include head */
require ("./data/include/header.inc.php");
require ("./data/include/wspmenu.inc.php");
?>
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
<?php

if ($contentinfo_num > 0):
	if (($_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==4) && in_array(intval($contentinfo_data['mid']), $_SESSION['wspvars']['rights']['contents_array'])):
		$contenteditallowed = true;
	elseif ($_SESSION['wspvars']['rights']['contents']==1 || $_SESSION['wspvars']['rights']['contents']==3):
		$contenteditallowed = true;
	elseif ($_SESSION['wspvars']['rights']['contents']==7):
		if (count($_SESSION['wspvars']['rights']['contents_array'])>0):
			$GLOBALS['midlist'] = array($_SESSION['wspvars']['rights']['contents_array'][0]);
			subMID($_SESSION['wspvars']['rights']['contents_array'][0]);
			$menuIDs = $GLOBALS['midlist'];
			$menuallowed = $GLOBALS['midlist'];
			$_SESSION['contentmidlist'] = $GLOBALS['midlist'];
		endif;
		if(in_array(intval($contentinfo_data['mid']), $menuIDs)):
			$contenteditallowed = true;
		else:
			$contenteditallowed = false;
		endif;
	elseif ($_SESSION['wspvars']['rights']['contents']==15):
		if (count($_SESSION['wspvars']['rights']['sitestructure_array'])>0):
			
			$GLOBALS['midlist'] = array($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
			subMID($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
			$menuIDs = $GLOBALS['midlist'];
			$menuallowed = $GLOBALS['midlist'];
			$_SESSION['contentmidlist'] = $GLOBALS['midlist'];
			
		endif;
		if(in_array(intval($contentinfo_data['mid']), $menuIDs)):
			$contenteditallowed = true;
		else:
			$contenteditallowed = false;
		endif;
	else:
		$contenteditallowed = false;
	endif;
	
	if ($contenteditallowed):
		?><fieldset><?php 
		// block to define workspace language
		if ((array_key_exists('workspacelang', $_SESSION['wspvars']) && $_SESSION['wspvars']['workspacelang']=="") || (!(array_key_exists('workspacelang', $_SESSION['wspvars'])))):
			$_SESSION['wspvars']['workspacelang'] = $worklang['languages']['shortcut'][0];
		endif;
		if (isset($_POST['workspacelang']) && $_POST['workspacelang']!=""):
			$_SESSION['wspvars']['workspacelang'] = $_POST['workspacelang'];
		endif;
	
		if (intval(count($worklang['languages']['shortcut']))>1) {
			?>
			<form name="changeworkspacelang" id="changeworkspacelang" method="post" style="float: right;">
			<select name="workspacelang" onchange="document.getElementById('changeworkspacelang').submit();">
				<?php
				
				foreach ($worklang['languages']['shortcut'] AS $key => $value) {
					echo "<option value=\"".$worklang['languages']['shortcut'][$key]."\" ";
					if ($_SESSION['wspvars']['workspacelang']==$worklang['languages']['shortcut'][$key]):
						echo " selected=\"selected\"";
					endif;
					echo ">".$worklang['languages']['longname'][$key]."</option>";
				}
				
				?>
			</select><input type="hidden" name="openmid" id="langopenmid" value="<?php echo isset($openpath)?$openpath:''; ?>">
			</form>
			<?php
        }
		
        ?><h1 style="float: left;"><?php echo returnIntLang('contentedit headline', true); ?></h1> <em style="float: right; font-size: 0.8em;">ID: <?php echo intval($cid); ?></em></fieldset>
    <fieldset id="basicinfo">
        <legend><?php echo returnIntLang('contentedit existing contents', true); ?> <?php echo legendOpenerCloser('contentexists'); ?></legend>
        <div id="contentexists">
		<?php 
		
		$oc_sql = "SELECT `mid`, `content_area` FROM `content` WHERE `cid` = ".intval($cid);
		$oc_res = doSQL($oc_sql);
		$fp_num = 0;
		if ($oc_res['num']>0) {
			$_SESSION['wspvars']['editmenuid'] = intval($oc_res['set'][0]['mid']);
			$activeca = intval($oc_res['set'][0]['content_area']);
			$fp_sql = "SELECT `mid`, `templates_id`, `editable` FROM `menu` WHERE `mid` = ".intval($oc_res['set'][0]['mid']);
			$fp_res = doSQL($fp_sql);
            $fp_num = $fp_res['num'];
		}
            
		if ($fp_num>0) {
			$realtemp = getTemplateID(intval($oc_res['set'][0]['mid']));
			$templatevars = getTemplateVars($realtemp);
            $siteinfo_num = 0;
			$siteinfo_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'contentvardesc'";
			$siteinfo_res = doResultSQL($siteinfo_sql);
			if ($siteinfo_res!==false && trim($siteinfo_res)!='') {
				$contentvardesc = unserializeBroken($siteinfo_res);
            }
            
			foreach ($templatevars['contentareas'] AS $tk => $tv) {
				echo "<table class='tablelist'><tr><td class='tablecell two head'>";
				if (isset($contentvardesc) && is_array($contentvardesc)):
					if (array_key_exists(($tv-1), $contentvardesc) && trim($contentvardesc[($tv-1)])!=''):
						echo $contentvardesc[($tv-1)];
					else:
						echo returnIntLang('contentstructure contentarea', false)." ".$tv;
					endif;
				else:
					echo returnIntLang('contentstructure contentarea', false)." ".$tv;
				endif;
				echo "</td><td class='tablecell two head'>".returnIntLang('str description', false)."</td><td class='tablecell three head'>".returnIntLang('str lastchange', false)."</td><td class='tablecell one head'>".returnIntLang('str action', false)."</td></tr></table>";
				
				$consel_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($oc_res['set'][0]['mid'])." AND `content_area` = ".intval($tv)." AND (`content_lang` = '".$_SESSION['wspvars']['workspacelang']."' OR `content_lang` = '') AND `trash` = 0 ORDER BY `position`";
				$consel_res = doSQL($consel_sql);
				
				echo "<div id='area-".$tk."'>";
				echo "<table class='tablelist'>";
                if ($consel_res['num']>0) {
                    foreach ($consel_res['set'] AS $csresk => $csresv) {
                        echo "<tr>";
                        $contentdesc = '';
                        $interpreter_sql = "SELECT `guid`, `name`, `classname`, `parsefile` FROM `interpreter` WHERE `guid` = '".trim($csresv['interpreter_guid'])."'";
                        $interpreter_res = doSQL($interpreter_sql);
                        if ($interpreter_res['num']>0) {
                            $contentdesc = trim($interpreter_res['set'][0]['name']);
                            $parsefile = trim($interpreter_res['set'][0]['parsefile']);
                        }
                        else if (isset($interpreter_res['set'][0]['interpreter_guid']) && trim($interpreter_res['set'][0]['interpreter_guid'])=='genericwysiwyg') {
                            $contentdesc = returnIntLang('hint generic wysiwyg', false);
                            $parsefile = 'nofile.php';
                        }
                        echo "<td class=\"tablecell two\">".$contentdesc."</td>";
                        
                        $valuedesc = '';
                        $contentvalue = unserializeBroken($csresv['valuefields']);
                        
                        if (isset($parsefile) && is_file(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$parsefile)))) {
                            include(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$parsefile)));
                            
                            $clsTmpInterpreter = new $interpreterClass;
							try {
	                            $valuedesc = $clsTmpInterpreter->getView($contentvalue, intval($oc_res['set'][0]['mid']), false);
							} finally {
								$valuedesc = '';
							}
                            
                            if (method_exists($interpreterClass, 'getPage')) {
                                $clsTmpInterpreter->getPage($contentvalue, intval($oc_res['set'][0]['mid']));
                            }
                        }
                        if (is_array($contentvalue) && array_key_exists('desc', $contentvalue) && trim($contentvalue['desc'])!='') {
                            $valuedesc = trim($contentvalue['desc']);
                        }
                        if (intval($csresv['globalcontent_id'])>0) {
                            $valuedesc.= " [GLOBAL]";
                        }
                        echo "<td class=\"tablecell two\">".$valuedesc."</td>";

                        echo "<td class=\"tablecell three\">".date('Y-m-d H:i:s', $csresv['lastchange'])."</td>";
                        echo "<td class='tablecell one'>";

                        // check if someone else is editing this content
                        $cfe_num = 0;
                        $cfe_sql = "SELECT `sid` FROM `security` WHERE `position` = '".escapeSQL("/".$_SESSION['wspvars']['wspbasedir']."/contentedit.php;cid=".intval($csresv['cid']))."'";
                        $cfe_res = doSQL($cfe_sql);
                        echo " <span class=\"bubblemessage ";
                        if ($cfe_res['num']>0): echo " orange "; else: echo " green "; endif;
                        echo "\" onclick=\"document.getElementById('editcontentid').value = '".intval($csresv['cid'])."'; document.getElementById('editcontents').submit();\">".returnIntLang('bubble edit', false)."</span>";
                        echo "&nbsp;</td>";
                        echo "</tr>";
                    }
                }
				echo "</table>";
				echo "</div>";
		
			}
		
        }
		
		?>
        </div>
	</fieldset>
	<form id="editcontents" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="hidden" id="editcontentid" name="editcontentid" value="" />
	</form>
<?php

$contentbackup_num = 0;
if(!(isset($gcid)) || intval($gcid)==0) {
	$contentbackup_sql = "SELECT `cbid` FROM `content_backup` WHERE `cid` = ".intval($cid)." ORDER BY `lastchange` DESC LIMIT 0, ".((intval($_SESSION['wspvars']['backupsteps'])>0)?intval($_SESSION['wspvars']['backupsteps']):5);
	$contentbackup_res = doSQL($contentbackup_sql);
	if ($contentbackup_res['num']>0) {
		$idlist = array();
		foreach ($contentbackup_res['set'] AS $cbk => $cbv) {
			$idlist[] = intval($cbv['cbid']);
		}
		$contentbackup_sql = "SELECT * FROM `content_backup` WHERE `cbid` IN (".implode(',', $idlist).") ORDER BY `lastchange` ASC";
		$contentbackup_res = doSQL($contentbackup_sql);
	}
	$contentbackup_num = $contentbackup_res['num'];
}

if ($contentbackup_num>0):
	?>
	<fieldset>
		<legend><?php echo returnIntLang('contentedit backup legend', true); ?> <?php echo legendOpenerCloser('returnbackup'); ?></legend>
		<div id="returnbackup">
		<script type="text/javascript" language="javascript">
		<!--

		function showBckpDetails(bckpID) {
			$('#details-' + bckpID).toggle();
			createFloatingTable();
			}

		//-->
		</script>
		<table class="tablelist">
			<tr>
				<td class="tablecell one head"><?php echo returnIntLang('str date'); ?></td>
				<td class="tablecell one head"><?php echo returnIntLang('str time'); ?></td>
				<td class="tablecell one head"><?php echo returnIntLang('str user'); ?></td>
				<td class="tablecell four head"><?php echo returnIntLang('contentedit backup changed fields'); ?></td>
				<td class="tablecell one head"><?php echo returnIntLang('str action'); ?></td>
			</tr>
			<?php
		
			for ($cbres=0; $cbres<$contentbackup_num; $cbres++):
				$lastchange = intval($contentbackup_res['set'][$cbres]["lastchange"]);
				if ($lastchange==0):
					$showdate = returnIntLang('contentedit backup clear version', true);
					$showtime = "";
				else:
					$showdate = date(returnIntLang('format date', false), $lastchange);
					$showtime = date(returnIntLang('format time', false), $lastchange);
				endif;
				// get contents from backup
				$backupvalues = unserializeBroken(trim($contentbackup_res['set'][$cbres]["valuefields"]));
				// get contents to compare with
				$nextbackup = array();
				if ($cbres<($contentbackup_num-1)):
					// get contents from next backup step
					$nextbackup = unserializeBroken(trim($contentbackup_res['set'][($cbres+1)]["valuefields"]));
				else:
					// get actual contents
					$nextbackup = unserializeBroken($contentinfo_data['valuefields']);
				endif;
				$changes = array();
				if (is_array($backupvalues) && is_array($nextbackup)):
					foreach ($backupvalues AS $bkey => $bvalue):
						if (!(isset($backupvalues[$bkey]))):
							$changes[] = "<span class='bubblemessage orange'>+ ".$bkey."</span>";
						elseif (((isset($backupvalues[$bkey]) && isset($nextbackup[$bkey])) && $backupvalues[$bkey]!=$nextbackup[$bkey]) || (isset($backupvalues[$bkey]) && !(isset($nextbackup[$bkey])))):
							$changes[] = "<span class='bubblemessage red'>".$bkey."</span>";
						endif;
					endforeach;
				endif;
				echo "<tr>";
				echo "<td class=\"tablecell one\">".$showdate."</td>";
				echo "<td class=\"tablecell one\">".$showtime."</td>";
				echo "<td class=\"tablecell one\">".returnUserData('shortcut', intval($contentbackup_res['set'][$cbres]["uid"]))."</td>";
				echo "<td class=\"tablecell four\"><a onclick='showBckpDetails(".$cbres.")'>".implode(" ", $changes)."</a></td>";
				echo "<td class=\"tablecell one actionfield\"><a style=\"cursor: pointer;\" onclick=\"document.getElementById('backupop').value = 'restorebackup'; document.getElementById('restorebid').value = '".intval($contentbackup_res['set'][$cbres]["cbid"])."'; document.getElementById('restorebackup').submit();\"><span class=\"bubblemessage green\">".returnIntLang('str review', false)."</span></a> <a style=\"cursor: pointer;\" onclick=\"document.getElementById('backupop').value = 'removebackup'; document.getElementById('restorebid').value = '".intval($contentbackup_res['set'][$cbres]["cbid"])."'; document.getElementById('restorebackup').submit();\"><span class=\"bubblemessage red\">".returnIntLang('str delete', true)."</span></a></td>";
				echo "</tr>";
				echo "<tr id='details-".$cbres."' style='display: none;'>";
				echo "<td class=\"tablecell eight\">";
				if (is_array($backupvalues) && is_array($nextbackup)):
					foreach ($backupvalues AS $bkey => $bvalue):
						if (((isset($backupvalues[$bkey]) && isset($nextbackup[$bkey])) && $backupvalues[$bkey]!=$nextbackup[$bkey]) || (isset($backupvalues[$bkey]) && !(isset($nextbackup[$bkey])))):
							if (is_array($backupvalues[$bkey])):
								echo "<strong>".$bkey."</strong>:<br />";
								foreach ($backupvalues[$bkey] AS $bkk => $bkv):
									if (isset($backupvalues[$bkey][$bkk]) && isset($nextbackup[$bkey][$bkk]) && $backupvalues[$bkey][$bkk]!=$nextbackup[$bkey][$bkk]):
										echo "<strong>".$bkk."</strong>: ";
										echo $backupvalues[$bkey][$bkk]." » ".$nextbackup[$bkey][$bkk];
										echo " - ";	
									elseif (isset($backupvalues[$bkey][$bkk]) && !(isset($nextbackup[$bkey][$bkk]))):
										echo "<strong>".$bkk."</strong>: ";
										echo $backupvalues[$bkey][$bkk]." » <em>leer</em>";
										echo " - ";	
									elseif (isset($nextbackup[$bkey][$bkk]) && !(isset($backupvalues[$bkey][$bkk]))):
										echo "<strong>".$bkk."</strong>: ";
										echo "<em>leer</em> » ".$nextbackup[$bkey][$bkk];
										echo " - ";	
									endif;
								endforeach;
								echo "<br />";
								foreach ($nextbackup[$bkey] AS $bkk => $bkv) {
									if (isset($backupvalues[$bkey][$bkk]) && isset($nextbackup[$bkey][$bkk]) && $backupvalues[$bkey][$bkk]!=$nextbackup[$bkey][$bkk]):
										echo "<strong>".$bkk."</strong>: ";
										echo $backupvalues[$bkey][$bkk]." » ".$nextbackup[$bkey][$bkk];
										echo " - ";	
									elseif (isset($backupvalues[$bkey][$bkk]) && !(isset($nextbackup[$bkey][$bkk]))):
										echo "<strong>".$bkk."</strong>: ";
										echo $backupvalues[$bkey][$bkk]." » <em>leer</em>";
										echo " - ";	
									elseif (isset($nextbackup[$bkey][$bkk]) && !(isset($backupvalues[$bkey][$bkk]))):
										echo "<strong>".$bkk."</strong>: ";
										echo "<em>leer</em> » ".$nextbackup[$bkey][$bkk];
										echo " - ";	
									endif;		
								}
								echo "<br />";
							else:
								if (isset($backupvalues[$bkey]) && isset($nextbackup[$bkey])):
									echo "<strong>".$bkey."</strong>: ".trim($backupvalues[$bkey])." <strong>»</strong> ".trim($nextbackup[$bkey])."<br/>";
								elseif (isset($backupvalues[$bkey])):
									echo "<strong>".$bkey."</strong>: ".trim($backupvalues[$bkey])." <strong>»</strong> <em>leer</em><br/>";
								elseif (isset($nextbackup[$bkey])):
									echo "<strong>".$bkey."</strong>: <em>leer</em> <strong>»</strong> ".trim($nextbackup[$bkey])."<br/>";
								endif;
							endif;
							echo "<hr />";
						endif;
					endforeach;
				endif;
				echo "</td>";
				echo "</tr>";
			endfor; ?>
		</table>
		<form id="restorebackup" name="restorebackup" method="post">
		<input type="hidden" id="backupop" name="op" value="restorebackup" /><input type="hidden" id="restorebid" name="bid" value="" /><input type="hidden" name="cid" value="<?php echo intval($cid); ?>" />
		</form>
		</div>
	</fieldset>
<?php endif; ?>
	
	<?php if($backupView): ?>
		<fieldset class='comment'>
			<p><?php echo returnIntLang('contentedit backup viewer'); ?></p>
		</fieldset>
	<?php endif; ?>
	
	<form name="editcontent" id="editcontent" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
	<script language="JavaScript1.2" type="text/javascript">
	<!--
	function submitForm(){};
	// -->
	</script>
	<?php
	
	
	// (re)load interpreter data 
	$interpreterinfo_sql = "SELECT `parsefile`, `name` FROM `interpreter` WHERE `guid` = '".trim($contentinfo_data['interpreter_guid'])."'";
	$interpreterinfo_res = doSQL($interpreterinfo_sql);
	
    $mid_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".intval($cid);
    $mid_res = doSQL($mid_sql);
    $mid = intval($mid_res['set'][0]['mid']);
	
	$gcid = intval($contentinfo_data['globalcontent_id']);
	
	if ($interpreterinfo_res['num']>0) {
		$parsefile = trim($interpreterinfo_res['set'][0]['parsefile']);
		if($gcid>0) {
			echo "<fieldset class='comment'><p>".returnIntLang('contentedit global content comment', true)."</p></fieldset>";
			$global_contentinfo_num = 0;
			$global_contentinfo_sql = "SELECT * FROM `globalcontent` WHERE `id` = ".$gcid;
			$global_contentinfo_res = doSQL($global_contentinfo_sql);
			if ($global_contentinfo_res['num']>0) {
                $fieldvalue = unserializeBroken(trim($global_contentinfo_res['set'][0]['valuefield']));
			}
        } else {
            $fieldvalue = unserializeBroken(trim($contentinfo_data['valuefields']));
		}

		// load data from backup if viewing this
		if($backupView) {
            $fieldvalue = $backupValues;
        }
		
		$fieldvaluestyle = null;

		// load interpreter-class
		if (is_file(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".trim($parsefile))))) {
            if (include(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".trim($parsefile))))) {
                try {
					$clsInterpreter = new $interpreterClass;
					$isInterpreter = true;
				} catch (Exception $e) {
					echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				}

			}
        }
    }
	else {
		$isGeneric = true;
		$isInterpreter = false;
		if($gcid>0) {
			echo "<fieldset class='comment'><p>".returnIntLang('contentedit global content comment', true)."</p></fieldset>";
			$global_contentinfo_num = 0;
			$global_contentinfo_sql = "SELECT `valuefield` FROM `globalcontent` WHERE `id` = ".intval($gcid);
			$global_contentinfo_res = doSQL($global_contentinfo_sql);
			if ($global_contentinfo_res['num'] > 0) {
				if (is_array(unserializeBroken(trim($global_contentinfo_res['set'][0]['valuefield'])))) {
					$fieldvalue = unserializeBroken(trim($global_contentinfo_res['set'][0]['valuefield']));
					$fieldvaluestyle = "array";
                }
				else {
					$fieldvalue = explode('<#>', trim($global_contentinfo_res['set'][0]['valuefield']));
					$fieldvaluestyle = "string";
                }
            }
        }
		else {
			$fieldvalue = unserializeBroken(trim($contentinfo_data['valuefields']));
		}

		// load data from backup if viewing this
		if($backupView) { 
            $fieldvalue = $backupValues; 
        }
    }
	
	if ($isInterpreter) {
		
		// call interpreter function
		$multilangcontent = false; if (property_exists($clsInterpreter, 'multilang')) $multilangcontent = $clsInterpreter->multilang;
		$flexiblecontent = false; if (property_exists($clsInterpreter, 'flexible')) $flexiblecontent = $clsInterpreter->flexible;
		$tinymcetextarea = false; if (property_exists($clsInterpreter, 'textarea')) $tinymcetextarea = $clsInterpreter->textarea;
		$wspmin = false; if (property_exists($clsInterpreter, 'wspmin')) $wspmin = $clsInterpreter->wspmin;
        
        if (is_array($tinymcetextarea)) {
			if ((is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/script/tinymce/langs/" .$_SESSION['wspvars']['locallang'] . ".js"))) {		
				$tiny_lang = $_SESSION['wspvars']['locallang'];
			}
            else {
				$tiny_lang = "en";
			}

			?><script type="text/javascript" src="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/tinymce.min.js"></script><?php			

            if (in_array('normal', $tinymcetextarea)) {
				?><script language="javascript" type="text/javascript">
				<!--
				
				tinymce.init({
					language : '<?php echo $tiny_lang; ?>',
		   			selector: ".mceNormal",
		   			license_key: 'gpl',
					height: 150,
					plugins: [
						"compat3x", "advlist", "autolink", "link", "image", "lists", "charmap", "hr", "anchor", "pagebreak", "searchreplace", "wordcount", "visualblocks", "visualchars", "code", "fullscreen", "insertdatetime", "nonbreaking", "save", "table", "directionality", "emoticons", "paste"
						],
					image_advtab: true,
					relative_urls: false,
					convert_urls: false,
					target_list: [
				        {title: '<?php echo returnIntLang('tinymce target same page', false); ?>', value: '_self'},
				        {title: '<?php echo returnIntLang('tinymce target new page', false); ?>', value: '_blank'}
					    ],
					document_base_url: "http://<?php echo $_SESSION['wspvars']['workspaceurl'] ?>/",
					image_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/image/imagelist.json.php?<?php echo time(); ?>",
					link_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/pagelist.json.php?<?php echo time(); ?>",
					document_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/medialist.json.php?<?php echo time(); ?>",
					class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/classlist.json.php?<?php echo time(); ?>",
					table_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
					table_row_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
					table_cell_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
					lang_list: [
						<?php
						if($_SESSION['wspvars']['sitelanguages']):
							$langs = unserialize($_SESSION['wspvars']['sitelanguages']);
							if(count($langs['languages']['longname'])>0):
								for($l=0;$l<count($langs['languages']['longname']);$l++):
									echo "{title: '" . $langs['languages']['longname'][$l] . "', value: '" . $langs['languages']['shortcut'][$l] . "'}";
									if($l<(count($langs['languages']['longname'])-1)):
										echo ",\n"; //'
									endif;
								endfor;
							endif;
						endif;
						?>
						],
					toolbar: "insertfile undo redo | bold italic | alignleft aligncenter alignright alignjustify | formatselect textcolor | charmap | bullist numlist outdent indent | link unlink image | table | visualblocks code", 
						contextmenu: "link image inserttable removeformat",
						autoresize_min_height: 100,
						menu: { 
				        edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall'}, 
				        insert: {title: 'Insert', items: '|'}, 
				        format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | removeformat'}, 
				        table: {title: 'Table'}, 
				        tools: {title: 'Tools'} 
					    }
						});
				
				//-->
				</script>
				<?php
			}
			if (in_array('short', $tinymcetextarea)) { ?>
<script language="javascript" type="text/javascript">

	tinymce.init({
		language : '<?php echo $tiny_lang; ?>',
				selector: ".mceShort",
				api_key: 'GPL',
		height: 150,
		plugins: [
			"compat3x", "advlist", "autolink", "link", "image", "lists", "charmap", "hr", "anchor", "pagebreak", "searchreplace", "wordcount", "visualblocks", "visualchars", "code", "fullscreen", "insertdatetime", "nonbreaking", "save", "table", "directionality", "emoticons", "paste"
			],
		image_advtab: true,
		relative_urls: false,
		convert_urls: false,
		target_list: [
			{title: '<?php echo returnIntLang('tinymce target same page', false); ?>', value: '_self'},
			{title: '<?php echo returnIntLang('tinymce target new page', false); ?>', value: '_blank'}
			],
		document_base_url: "http://<?php echo $_SESSION['wspvars']['workspaceurl'] ?>/",
		image_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/image/imagelist.json.php?<?php echo time(); ?>",
		link_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/pagelist.json.php?<?php echo time(); ?>",
		document_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/medialist.json.php?<?php echo time(); ?>",
		class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/classlist.json.php?<?php echo time(); ?>",
		table_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
		table_row_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
		table_cell_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
		lang_list: [
			<?php
			if($_SESSION['wspvars']['sitelanguages']):
				$langs = unserialize($_SESSION['wspvars']['sitelanguages']);
				if(count($langs['languages']['longname'])>0):
					for($l=0;$l<count($langs['languages']['longname']);$l++):
						echo "{title: '" . $langs['languages']['longname'][$l] . "', value: '" . $langs['languages']['shortcut'][$l] . "'}";
						if($l<(count($langs['languages']['longname'])-1)):
							echo ",\n"; //'
						endif;
					endfor;
				endif;
			endif;
			?>
			],
		toolbar: "insertfile undo redo | bold italic | alignleft aligncenter alignright alignjustify | charmap | link unlink | visualblocks code", 
			contextmenu: "link image inserttable removeformat",
			autoresize_min_height: 100,
			menu: { 
			edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall'}, 
			insert: {title: 'Insert', items: '|'}, 
			format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | removeformat'}, 
			table: {title: 'Table'}, 
			tools: {title: 'Tools'} 
			}
			});

</script><?php
			}
		}
		
		if (!(is_array($multilangcontent)) || ($fieldvaluestyle=="string" && !(@implode($fieldvalue)=="")) || !($flexiblecontent)) {
			echo "<fieldset class='comment'><p>";
			if (!(is_array($multilangcontent))) {
				echo returnIntLang('interpreter none multilang', true)." ";
            }
			if ($fieldvaluestyle=="string" && !(@implode($fieldvalue)=="")) {
				echo returnIntLang('interpreter old format', true)." ";
            }
			if (!($flexiblecontent)) {
				echo returnIntLang('interpreter non flexible', true)." ";
			}
			echo "</p></fieldset>";
		}
		if (is_array($multilangcontent)) {
			foreach($lang AS $lkey => $lvalue) {
				if (array_key_exists($lkey, $multilangcontent) && is_array($multilangcontent[$lkey])) {
					$lang[$lkey] = array_merge($lang[$lkey], $multilangcontent[$lkey]);
                }
			}
		}
        if (isset($fp_res['set'][0]['editable']) && intval($fp_res['set'][0]['editable'])==9) {
            ?><fieldset><legend>Dynamic</legend><?php
            
            $dynamicfields = $clsInterpreter -> getDynamicValues($fieldvalue, intval($mid), intval($cid), $_SESSION['wspvars']['workspacelang']);
            if (is_array($dynamicfields) && count($dynamicfields)>0) {

                echo "<input type='hidden' name='field[isdynamic]' value='1' />";
                echo "The contents of the sub entries will be filled if you edit and save this menu entry in sitestructure by the defined params.";
            
            } else {
                echo $dynamicfields;
            }
            
            ?></fieldset><?php
        }
        else {
			if ($wspmin!==false && floatval($wspmin)>=6.8) {
                echo "<fieldset>";
                echo "<legend>Inhalt</legend>";
            }
			try {
				echo $clsInterpreter->getEdit($fieldvalue, intval($mid), intval($cid));
			} catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
			} 
            if ($wspmin!==false && floatval($wspmin)>=6.8) {
                echo "</fieldset>";
            }

        }
        // close interpreter external db request, if it was open
        if (isset($clsInterpreter) && method_exists($clsInterpreter, 'closeInterpreterDB')) {
            $clsInterpreter->closeInterpreterDB();
        }
    }
	else {	
		// generic content interpreter, if no interpreter file was found
		// should be extended to handle contents with fault deleted interpreter files ..
		// 
		if ((is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/script/tinymce/langs/" .$_SESSION['wspvars']['locallang'] . ".js"))):			
			$tiny_lang = $_SESSION['wspvars']['locallang'];
		else:
			$tiny_lang = "en";
		endif;
		?>
		<?php if (isset($fieldvalue['content']) && strlen(stripslashes($fieldvalue['content']))<500): $tiny_height=150; else: $tiny_height=300; endif; ?>
		<script type="text/javascript" src="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/tinymce.min.js"></script>
		<script language="javascript" type="text/javascript">
		<!--
		
		tinymce.init({
			language : '<?php echo $tiny_lang; ?>',
   			selector: "textarea",
   			license_key: 'gpl',
			height: <?php echo $tiny_height; ?>,
			plugins: [
				"compat3x", "advlist", "autolink", "link", "image", "lists", "charmap", "hr", "anchor", "pagebreak", "searchreplace", "wordcount", "visualblocks", "visualchars", "code", "fullscreen", "insertdatetime", "nonbreaking", "save", "table", "directionality", "emoticons", "paste"
			],
			image_advtab: true,
			relative_urls: false,
			convert_urls: false,
			target_list: [
		        {title: '<?php echo returnIntLang('tinymce target same page', false); ?>', value: '_self'},
		        {title: '<?php echo returnIntLang('tinymce target new page', false); ?>', value: '_blank'}
			],
			document_base_url: "http://<?php echo $_SESSION['wspvars']['workspaceurl'] ?>/",
			image_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/image/imagelist.json.php?<?php echo time(); ?>",
			link_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/pagelist.json.php?<?php echo time(); ?>",
			document_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/medialist.json.php?<?php echo time(); ?>",
			class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/classlist.json.php?<?php echo time(); ?>",
			table_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
			table_row_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
			table_cell_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
			lang_list: [
				<?php
				if($_SESSION['wspvars']['sitelanguages']):
					$langs = unserialize($_SESSION['wspvars']['sitelanguages']);
					if(count($langs['languages']['longname'])>0):
						for($l=0;$l<count($langs['languages']['longname']);$l++):
							echo "{title: '" . $langs['languages']['longname'][$l] . "', value: '" . $langs['languages']['shortcut'][$l] . "'}";
							if($l<(count($langs['languages']['longname'])-1)):
								echo ",\n"; //'
							endif;
						endfor;
					endif;
				endif;
				?>
				],
			toolbar: "insertfile undo redo | bold italic | alignleft aligncenter alignright alignjustify | formatselect textcolor | charmap | bullist numlist outdent indent | link unlink image | table | visualblocks code", 
				contextmenu: "link image inserttable removeformat",
				autoresize_min_height: 100,
				menu: { 
		        edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall'}, 
		        insert: {title: 'Insert', items: '|'}, 
		        format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | removeformat'}, 
		        table: {title: 'Table'}, 
		        tools: {title: 'Tools'} 
			    }
				});
		
		//-->
		</script>
		
		<fieldset>
			<legend><?php echo returnIntLang('contentedit generic wysiwyg legend'); ?></legend>
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('contentedit generic wysiwyg desc'); ?></td>
					<td class="tablecell six"><input type="text" name="field[desc]" id="field_desc" value="<?php if (is_array($fieldvalue) && array_key_exists('desc', $fieldvalue)) echo prepareTextField($fieldvalue['desc']); ?>" class="six full" /></td>
				</tr>
			</table>
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('contentedit generic wysiwyg content'); ?></td>
					<td class="tablecell six"><textarea name="field[content]" id="field_content" class="medium six"><?php if (is_array($fieldvalue) && array_key_exists('content', $fieldvalue)) echo stripslashes(stripslashes(stripslashes($fieldvalue['content']))); ?></textarea></td>
				</tr>
			</table>
		</fieldset>
		<?php
	}
	?>
	<hr style="width: 100%; height: 0.1px; border: none; background: none; visibility: hidden; clear: both; float: none;" />
	<script language="JavaScript" type="text/javascript">
	<!--
	function checkUserRes() {
		if (document.getElementById('visibility').value < 4) {
			document.getElementById('userrestriction').style.display = 'none';
			}
		else {
			document.getElementById('userrestriction').style.display = 'block';
			}
		}
		
	// -->
	</script>
	<fieldset>
		<legend><?php echo returnIntLang('contentedit generell showprefs'); ?> <?php echo legendOpenerCloser('prefs_area'); ?></legend>
		<div id="prefs_area">
		<table class="tablelist">
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('contentedit showstat'); ?></td>
				<td class="tablecell six"><select name="visible" id="visibility" size="1" class="one full" onChange="checkUserRes();">
				<option value="0" <?php if(intval($contentinfo_data['visibility'])==0 || trim($contentinfo_data['visibility'])=="no"): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat inactive'); ?></option>
				<option value="1" <?php if(intval($contentinfo_data['visibility'])==1 || trim($contentinfo_data['visibility'])=="yes" || trim($contentinfo_data['visibility'])==""): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat active'); ?></option>
				<option value="2" <?php if(intval($contentinfo_data['visibility'])==2): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat active logout'); ?></option>
				<option value="3" <?php if(intval($contentinfo_data['visibility'])==3): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat active login'); ?></option>
				<option value="4" <?php if(intval($contentinfo_data['visibility'])==4): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat active user'); ?></option>
			</select></td></tr>
		</table>
		<div id="userrestriction" style="margin-top: -1px; <?php if(intval($contentinfo_data['visibility'])<4): echo "display: none;"; endif; ?>">
		<table class="tablelist follow">
			<tr>
			<td class="tablecell two"><?php echo returnIntLang('str user'); ?></td>
			<td class="tablecell six"><?php
			
			$logincontrol = unserializeBroken($contentinfo_data['logincontrol']);
			
			$usercontrol_sql = "SELECT `id`, `username` FROM `usercontrol` ORDER BY `username`";
			$usercontrol_res = doSQL($usercontrol_sql);
			if ($usercontrol_res['num']>10) {
			
                ?><select name="userrestriction[]" size="5" style="margin-right: 10px;" <?php if(intval($contentinfo_data['visibility'])<4 || trim($contentinfo_data['visibility'])=="yes" || trim($contentinfo_data['visibility'])=="no" || trim($contentinfo_data['visibility'])==""): echo "disabled=\"disabled\""; endif; ?> multiple="multiple">
                    <option value="norestrict">Keine Beschr&auml;nkung</option>
                    <optgroup label="user">
                        <?php

                        foreach ($usercontrol_res['set'] AS $uresk => $uresv) {
                            echo "<option value=\"".intval($uresv["id"])."\"";
                            if(is_array($logincontrol) && in_array(intval($uresv["id"]), $logincontrol)):
                                echo " selected=\"selected\"";
                            endif;
                            echo ">".trim($uresv["username"])."</option>";
                        }

                        ?>
                    </optgroup>
                </select><?php }
            else {

                foreach ($usercontrol_res['set'] AS $uresk => $uresv) {
                    echo "<span style=\"width: 33%; margin-bottom: 5px; float: left;\"><input type=\"checkbox\" name=\"userrestriction[]\" value=\"".intval($uresv["id"])."\"";
                    if(is_array($logincontrol) && in_array(intval($uresv["id"]), $logincontrol)):
                        echo " checked=\"checked\"";
                    endif;
                    echo " />&nbsp;".trim($uresv["username"])."</span>";
                }
			
            } ?></td></tr>
		</table>
		</div>
		</div>
	</fieldset>

	<fieldset>
		<legend><?php echo returnIntLang('contentedit special showprefs'); ?> <?php echo legendOpenerCloser('showoptions_area'); ?></legend>
		<div id="showoptions_area">
			<?php
			
			$showday = intval($contentinfo_data["showday"]);
			$weekdayvalue = array();
			for ($sd=6;$sd>=0;$sd--):
				if ($showday-pow(2,$sd)>=0):
					$weekdayvalue[($sd+1)] = ' checked="checked" ';
					$showday = $showday-(pow(2,$sd));
				else:
					$weekdayvalue[($sd+1)] = '';
				endif;
			endfor;
			
			?>
			<input type="hidden" name="weekday[0]" value="0" />
			<ul class="tablelist">
				<li class="tablecell two"><?php echo returnIntLang('contentedit special container'); ?></li>
				<li class="tablecell two"><select name="container" class="one full">
					<option value="0">SECTION</option>
					<option value="1" <?php if(intval($contentinfo_data["container"])==1) echo " selected=\"selected\" "; ?>>DIV</option>
					<option value="2" <?php if(intval($contentinfo_data["container"])==2) echo " selected=\"selected\" "; ?>>SPAN</option>
					<option value="3" <?php if(intval($contentinfo_data["container"])==3) echo " selected=\"selected\" "; ?>>LI</option>
					<option value="4" <?php if(intval($contentinfo_data["container"])==4) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('str none'); ?></option>
				</select></li>
				<li class="tablecell two"><?php echo returnIntLang('contentedit special container class'); ?></li>
				<li class="tablecell two"><input type="text" name="containerclass" value="<?php echo prepareTextField($contentinfo_data["containerclass"]); ?>" class="one full" /></li>
				<li class="tablecell two"><?php echo returnIntLang('contentedit special mobileclass'); ?></li>
				<li class="tablecell two"><select name="displayclass" class="full" >
					<option value="0" <?php if(intval($contentinfo_data["displayclass"])==0) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('contentedit special mobileclass none'); ?></option>
					<option value="1" <?php if(intval($contentinfo_data["displayclass"])==1) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('contentedit special mobileclass desktop only'); ?> (.desktop)</option>
					<option value="2" <?php if(intval($contentinfo_data["displayclass"])==2) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('contentedit special mobileclass mobile only'); ?> (.mobile)</option>
					<option value="3" <?php if(intval($contentinfo_data["displayclass"])==3) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('contentedit special mobileclass print only'); ?> (.print)</option>
				</select></li>
				<li class="tablecell two"><?php echo returnIntLang('contentedit special anchor'); ?></li>
				<li class="tablecell two"><input type="text" name="containeranchor" value="<?php if(!empty($contentinfo_data["containeranchor"])!=""): echo prepareTextField($contentinfo_data["containeranchor"]); endif; ?>" placeholder="<?php echo "ID".intval($contentinfo_data['cid']); ?>" class="one full" /></li>
				<li class="tablecell two"><?php echo returnIntLang('structure daily based view'); ?></li>
				<li class="tablecell six"><ul class="innercell block">
					<li><input type="checkbox" name="weekday[1]" id="weekday_1" value="1" <?php echo $weekdayvalue[1]; ?>  /></li>
					<li><?php echo returnIntLang('str monday'); ?></li>
				</ul>
				<ul class="innercell block">
					<li><input type="checkbox" name="weekday[2]" id="weekday_2" value="2" <?php echo $weekdayvalue[2]; ?> /></li>
					<li><?php echo returnIntLang('str tuesday'); ?></li>
				</ul>
				<ul class="innercell block">
					<li><input type="checkbox" name="weekday[3]" id="weekday_3" value="4" <?php echo $weekdayvalue[3]; ?> /></li>
					<li><?php echo returnIntLang('str wednesday'); ?></li>
				</ul>
				<ul class="innercell block">
					<li><input type="checkbox" name="weekday[4]" id="weekday_4" value="8" <?php echo $weekdayvalue[4]; ?> /></li>
					<li><?php echo returnIntLang('str thursday'); ?></li>
				</ul>
				<ul class="innercell block">
					<li><input type="checkbox" name="weekday[5]" id="weekday_5" value="16" <?php echo $weekdayvalue[5]; ?> /></li>
					<li><?php echo returnIntLang('str friday'); ?></li>
				</ul>
				<ul class="innercell block">
					<li><input type="checkbox" name="weekday[6]" id="weekday_6" value="32" <?php echo $weekdayvalue[6]; ?> /></li>
					<li><?php echo returnIntLang('str saturday'); ?></li>
				</ul>
				<ul class="innercell block">
					<li><input type="checkbox" name="weekday[7]" id="weekday_7" value="64" <?php echo $weekdayvalue[7]; ?> /></li>
					<li><?php echo returnIntLang('str sunday'); ?></li>
				</ul></li>
			</ul>
			<ul class="tablelist">
				<li class="tablecell two"><?php echo returnIntLang('structure time based view'); ?></li>
				<li class="tablecell five"><ul class="innercell block">
					<li><?php echo returnIntLang('structure time based view starts'); ?></li>
				</ul>
				<ul class="innercell block">
					<li><input id="timebasedstart" type="text"></li>
				</ul>
				<ul class="innercell block">
					<li><?php echo returnIntLang('structure time based view ends'); ?></li>
				</ul>
				<ul class="innercell block">
					<li><input id="timebasedend" type="text"></li>
				</ul></li>
				<li class="tablecell one"><a onclick="addTime();"><span class="bubblemessage green"><?php echo returnIntLang('bubble add', false); ?></span></a></li>
			</ul>
			<ul class="tablelist" id="timebasedevents"><?php
					
			$alltimes = trim($contentinfo_data["showtime"]);
			$time = array();
			if ($alltimes!=""):
				$giventimes = unserializeBroken($alltimes);
				foreach ($giventimes AS $gkey => $gvalue):
					$time[$gvalue[0]] = $gvalue[1];
				endforeach;
			endif;
			ksort($time);
			foreach ($time AS $key => $value):
				echo "<li class=\"tablecell two ".$key."\">&nbsp;</li>";
				echo "<li class=\"tablecell five ".$key."\">".date("d.m.Y", $key)." ".date("H:i", $key)." <input type=\"hidden\" name=\"timetable[".$key."]\" value=\"".$key.";".$value."\"/> - ".date("d.m.Y", $value)." ".date("H:i", $value)."</li>";
				echo "<li class=\"tablecell one ".$key."\"><a onClick=\"submitTimeRemove('".$key."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble false', false)."</span></a></li>";
			endforeach;
			$time = array_flip($time);
			
			?></ul>
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
					$('#timebasedevents').append('<li id="" class="tablecell two ' + colstamp + '">&nbsp;</li><li class="tablecell five ' + colstamp + '">' + startdate[0] + '.' + startdate[1] + '.' + startdate[2] + ' ' + starttime[0] + ':' + starttime[1] + ' - ' + enddate[0] + '.' + enddate[1] + '.' + enddate[2] + ' ' + endtime[0] + ':' + endtime[1] + '<input type="hidden" name="startdate[]" value="' + startdate[0] + '.' + startdate[1] + '.' + startdate[2] + ' ' + starttime[0] + ':' + starttime[1] + '" /><input type="hidden" name="enddate[]" value="' + enddate[0] + '.' + enddate[1] + '.' + enddate[2] + ' ' + endtime[0] + ':' + endtime[1] + '" /></li><li class="tablecell one ' + colstamp + '"><span class="bubblemessage red" onclick="removeTime(' + colstamp + ')"><?php echo returnIntLang('bubble false', false); ?></span></li>');
					document.getElementById('timebasedstart').value = '';
					document.getElementById('timebasedend').value = '';
					passLiTable('#showoptions_area ul', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'ttbl');
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
			
			function submitTimeRemove(timeid) {
				$('.'+timeid).toggle('fade',500,function(){
					$('.'+timeid).remove();
					});
				}
			
			// -->
			</script>
		</div>
	</fieldset>
        
    <?php
    
    // unset pageholder var
    if (isset($_SESSION['getpage'])) { unset($_SESSION['getpage']); }
        
    ?>
        
	<input type="hidden" name="editcontentid" value="<?php echo $_SESSION['wspvars']['editcontentid']; ?>" />
	<input type="hidden" name="editmid" value="<?php echo intval($_SESSION['wspvars']['editmenuid']) ?>" />
	<input type="hidden" name="gcid" value="<?php if (isset($gcid)) echo intval($gcid); ?>" />
	<input type="hidden" name="op" id="op" value="" />
	<input type="hidden" name="back" id="back" value="" />
	<input type="hidden" name="lang" id="editlang" value="<?php echo $_SESSION['wspvars']['workspacelang']; ?>" />
	<fieldset class="options">
		<p><a style="cursor: pointer;" onclick="document.getElementById('op').value='save'; document.getElementById('editcontent').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a>
		<a style="cursor: pointer;" onclick="submitForm(); document.getElementById('op').value = 'save'; document.getElementById('back').value = '1'; document.getElementById('editcontent').submit(); return false;" class="greenfield"><?php echo returnIntLang('btn save and back', false); ?></a>
		<a href="contentstructure.php" class="orangefield"><?php echo returnIntLang('str back', false); ?></a>
		<?php if(!(isset($gcid)) || intval($gcid)==0): ?> <a href="contentedit.php?cid=<?php echo $cid; ?>&op=localtoglobal" onclick="if (confirm(unescape('<?php echo returnIntLang('contentedit request convert lokal to global', false); ?>'))) {return true;} else {return false;}" class="orangefield"><?php echo returnIntLang('str global2local1', false); ?><span style="font-size: 80%;"><?php echo returnIntLang('str global2local2', false); ?></span><?php echo returnIntLang('str global2local3', false); ?></a> <?php if(isset($interpreterClass) && $interpreterClass=='text'): ?><a href="contentedit.php?cid=<?php echo $cid; ?>&op=togeneric" class="orangefield"><?php echo returnIntLang('str text2generic', false); ?></a><?php endif; ?> <?php else: ?> <a href="contentedit.php?cid=<?php echo $cid; ?>&op=globaltolocal" onclick="if (confirm(unescape('<?php echo returnIntLang('contentedit global2local request', false); ?>'))) {return true;} else {return false;}" class="orangefield"><?php echo returnIntLang('str global2local3', false); ?><span style="font-size: 80%;"><?php echo returnIntLang('str global2local2', false); ?></span><?php echo returnIntLang('str global2local1', false); ?></a> <?php endif; ?>
		<!-- <a onclick="showNewContent();" class="greenfield"><?php echo returnIntLang('contentedit add new content', false); ?></a> --><a href="showpreview.php?previewid=<?php echo intval($contentinfo_data['mid']); ?>&previewlang=<?php echo $_SESSION['wspvars']['workspacelang']; ?>" target="_blank" class="greenfield"><?php echo returnIntLang('str preview', false); ?></a></p>
	</fieldset>
	</form>
	<?php else: ?>
        <fieldset class="errormsg"><p>Diese Inhalte d&uuml;rfen durch Sie nicht bearbeitet werden.</p></fieldset>
	<?php endif; ?>
</div>
<?php else: ?>
<fieldset class="errormsg">
	<p>Das von Ihnen gew&auml;hlte Content-Element ist nicht vorhanden oder es kann nicht darauf zugegriffen werden.</p>
</fieldset>
<?php endif; ?>
<?php @ include("./data/include/footer.inc.php"); ?>
<!-- EOF -->