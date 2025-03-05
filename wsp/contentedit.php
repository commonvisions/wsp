<?php
/**
 * @description edit contents
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2020-06-30
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
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
	header("location: contents.php");
	die();
endif;
/* define actual system position ------------- */
$_SESSION['wspvars']['menuposition'] = "contentedit";
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].";cid=".$cid;
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = true;
$_SESSION['wspvars']['addpagecss'] = array(
  'jquery.nestable.css',
  'bootstrap-datepicker3.min.css'
);
$_SESSION['wspvars']['addpagejs'] = array(
  'jquery/jquery.nestable.js',
  'bootstrap/bootstrap-datepicker.js'
);
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// page specific includes --------------------
require ("./data/include/clsinterpreter.inc.php");
// define page specific vars -----------------
$isGeneric = false;
$isInterpreter = false;
$backupView = false;
$backupChange = false;

if (!($_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==4 || $_SESSION['wspvars']['rights']['contents']==5 || $_SESSION['wspvars']['rights']['contents']==7)):
	$_SESSION['wspvars']['rights']['contents_array'] = array();
endif;

// page specific funcs -----------------------

// main actions
// erase ALL backups
if (isset($_REQUEST['op']) && trim($_REQUEST['op'])=='erasebackup' && isset($_REQUEST['cid']) && intval($_REQUEST['cid'])>0 && $_SESSION['wspvars']['editcontentid']==intval($_REQUEST['cid'])) {
    $sql = "DELETE FROM `content_backup` WHERE `cid` = ".intval($_REQUEST['cid']);
    doSQL($sql);
}
// remove backup
if (isset($_REQUEST['op']) && trim($_REQUEST['op'])=='removebackup' && isset($_POST['bid']) && intval($_POST['bid'])>0) {
    $sql = "DELETE FROM `content_backup` WHERE `cbid` = ".intval($_POST['bid']);
    doSQL($sql);
    $backupChange = true;
}
// restore backup
if (isset($_REQUEST['op']) && trim($_REQUEST['op'])=='restorebackup' && isset($_POST['bid']) && intval($_POST['bid'])>0) {
	$restore_sql = "SELECT * FROM `content_backup` WHERE `cbid` = ".intval($_POST['bid']);
	$restore_res = doSQL($restore_sql);
	if ($restore_res['num']>0) {
		$backupValues = unserializeBroken($restore_res['set'][0]['valuefields']);
		$backupView = true;
	} else {
		addWSPMsg('errormsg', returnIntLang('contentedit backup no found', false));
	}
}
// localtoglobal
if (isset($_REQUEST['op']) && trim($_REQUEST['op'])=="localtoglobal" && isset($_POST['cid']) && intval($_POST['cid'])>0) {
	$convert_sql = "SELECT * FROM `content` WHERE `cid` = ".intval($_REQUEST['cid']);
	$convert_res = doSQL($convert_sql);
	if ($convert_res['num']>0) {
		$sql = "INSERT INTO `content_global` SET `id`='', `content_lang` = '".$_SESSION['wspvars']['workspacelang']."', `valuefields` = '".escapeSQL(trim($convert_res['set'][0]['valuefields']))."', `interpreter_guid` = '".escapeSQL(trim($convert_res['set'][0]['interpreter_guid']))."'";
        $res = doSQL($sql);
		if ($res['aff']>0) {
			$lastid = $res['inf'];
			$update = "UPDATE `content` SET `valuefields` = '', `globalcontent_id` = ".intval($lastid)." WHERE `cid` = ".intval($_REQUEST['cid']);
			doSQL($update);
			addWSPMsg('noticemsg', returnIntLang('content converted to global content'));
		}
        else {
			addWSPMsg('noticemsg', returnIntLang('content could not be converted to global content'));
		}
	}
}
// globaltolocal
if (isset($_REQUEST['op']) && trim($_REQUEST['op'])=="globaltolocal" && isset($_POST['cid']) && intval($_POST['cid'])>0) {
	$convert_sql = "SELECT `globalcontent_id` FROM `content` WHERE `cid` = ".intval($_REQUEST['cid']);
	$convert_res = doSQL($convert_sql);
	if ($convert_res['num']>0) {
		if (intval($convert_res['set'][0]['globalcontent_id'])!=0) {
			$gc_sql = "SELECT * FROM `content_global` WHERE `id` = ".intval($convert_res['set'][0]['globalcontent_id']);
			$gc_res = doSQL($gc_sql);
			if ($gc_res['num']>0) {
				$res = doSQL("UPDATE `content` SET `globalcontent_id` = 0, `valuefields` = '".escapeSQL(trim($gc_res['set'][0]['valuefield']))."', `lastchange` = '".time()."', `interpreter_guid` = '".escapeSQL(trim($gc_res['set'][0]['interpreter_guid']))."' WHERE `cid` = ".intval($_REQUEST['cid']));
                if ($res['res']===true) {
					addWSPMsg('resultmsg', returnIntLang('contentedit global2local success'));
                } else {
					addWSPMsg('errormsg', returnIntLang('contentedit global2local failure1'));
                }
			} else {
				addWSPMsg('errormsg', returnIntLang('contentedit global2local failure2'));
			}
		} else {
			addWSPMsg('errormsg', returnIntLang('contentedit global2local failure2'));
		}
	} else {
		addWSPMsg('errormsg', returnIntLang('contentedit global2local failure3'));
	}
}
// text2generic
if (isset($_REQUEST['op']) && trim($_REQUEST['op'])=='togeneric') {
	$sql = "UPDATE `content` SET `interpreter_guid` = 'genericwysiwyg' WHERE `cid` = ".intval($cid);
	doSQL($sql);
}
// save content
if (isset($_POST) && array_key_exists('op', $_POST) && trim($_POST['op'])=='save') {
    // get some data for backup and information of content
	$b_sql = "SELECT `cid`, `interpreter_guid`, `valuefields`, `lastchange`, `content_lang` FROM `content` WHERE `cid` = ".intval($cid);
	$b_res = doSQL($b_sql);
	// get savedata from interpreter
	if ($b_res['num']>0 && trim($b_res['set'][0]['interpreter_guid'])!='') {
		$i_sql = "SELECT `parsefile` FROM `interpreter` WHERE `guid` = '".trim($b_res['set'][0]['interpreter_guid'])."'";
		$i_res = doSQL($i_sql);
		if ($i_res['num']>0) {
			$file = trim($i_res['set'][0]["parsefile"]);
			if (is_file(DOCUMENT_ROOT.WSP_DIR.'/data/interpreter/'.$file)) {
                include_once ("./data/interpreter/".$file);
                $clsInterpreter = new $interpreterClass;
                $data = $clsInterpreter->getSave();
            }
		}
	}
	// OR get content from $_POST['field'];
	if (!(isset($data)) || trim($data)=='') {
		$data = serialize($_POST['field']);
	}
    if ($b_res['num']>0) {
		// save backup from existing content if content changed
        $be_sql = "SELECT MAX(`cbid`) AS `cbid` FROM `content_backup` WHERE `cid` = ".intval($cid)." AND `valuefields` = '".escapeSQL(trim($data))."'";
        $be_res = doSQL($be_sql);
        if (defined('WSP_DEV') && WSP_DEV) {
            addWSPMsg('noticemsg', var_export($be_res, true));
        }
        if ($be_res['num']>0 && $be_res['set'][0]['cbid']!=null) {
            $bb_sql = "UPDATE 
                `content_backup` 
            SET 
                `lastchange` = ".intval($b_res['set'][0]['lastchange']).", 
                `lastbackup` = ".time()." 
            WHERE
                `cbid` = ".intval($be_res['set'][0]['cbid']);
        } else {
            $bb_sql = "INSERT INTO 
                `content_backup` 
            SET 
                `cid` = ".intval($cid).", 
                `uid` = ".intval($_SESSION['wspvars']['userid']).", 
                `valuefields` = '".escapeSQL(trim($b_res['set'][0]['valuefields']))."', 
                `lastchange` = ".intval($b_res['set'][0]['lastchange']).", 
                `lastbackup` = ".time().", 
                `content_lang` = '".escapeSQL(trim($b_res['set'][0]['content_lang']))."'
            ";
        }
		$bb_res = doSQL($bb_sql);
        if ($bb_res['res']===true) {
			if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg('noticemsg', var_export($bb_sql, true));
                addWSPMsg('noticemsg', var_export($bb_res, true));
            }
        } else {
            if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg('errormsg', var_export($bb_res, true));
            }
			addWSPMsg('errormsg', returnIntLang('contentedit backup not saved', false));
        }
	}
	// global content
	if(isset($_POST['gcid']) && intval($_POST['gcid'])>0) {
		// update global content table
		doSQL("UPDATE `content_global` SET `valuefields` = '".escapeSQL($data)."' WHERE `id` = ".intval($_POST['gcid']));
		// setup content table
		$content_sql = "UPDATE `content` SET ";
		// uid 2015-10-14
		$content_sql.= " `uid` = '".intval($_SESSION['wspvars']['userid'])."', "; // visibility
		//	sid
		$content_sql.= " `visibility` = '".intval($_POST['visible'])."', "; // visibility
		$content_sql.= " `content_lang` = '".escapeSQL($_POST['lang'])."', "; // content_lang
        $content_sql.= " `description` = '".escapeSQL(trim($_POST['description']))."', "; // description
		$content_sql.= " `showday` = '".intval(array_sum($_POST['weekday']))."', "; // showday
		$timetable = array();
		if (array_key_exists('startdate', $_POST) && is_array($_POST['startdate'])):
			foreach ($_POST['startdate'] AS $tk => $tv):
				$startdate = datetotime($_POST['formatdate'][$tk]." H:i:s", $_POST['startdate'][$tk]." 00:00:01");
                $enddate = datetotime($_POST['formatdate'][$tk]." H:i:s", $_POST['enddate'][$tk]." 23:59:59");
                $timetable[] = array(intval($startdate),intval($enddate));	
			endforeach;
			unset($_POST['startdate']); unset($_POST['enddate']);
		endif;
		if (array_key_exists('timetable', $_POST) && is_array($_POST['timetable'])):
			foreach ($_POST['timetable'] AS $tk => $tv):
				$timetable[] = explode(";", $tv);
			endforeach;
		endif;
		if (count($timetable)>0):
			$content_sql.= "`showtime` = '".escapeSQL(serialize($timetable))."', ";
		else:
			$content_sql.= "`showtime` = '', ";	
		endif;
		$content_sql.= "`container` = '".intval($_POST['container'])."', ";
		$content_sql.= "`containerclass` = '".escapeSQL(trim($_POST['containerclass']))."', ";
		$content_sql.= "`displayclass` = '".intval($_POST['displayclass'])."', ";
		$content_sql.= "`containeranchor` = '".escapeSQL(trim($_POST['containeranchor']))."', ";
        $content_sql.= "`containerview` = '".intval($_POST['containerview'])."', ";
		$content_sql.= " `lastchange` = '".time()."', "; // lastchange
		// adding special users to content control
		if (intval($_POST['visible'])>3 && isset($_POST['userrestriction']) && is_array($_POST['userrestriction']) && count($_POST['userrestriction'])>0):
			$content_sql.= " `logincontrol` = '".escapeSQL(serialize($_POST['userrestriction']))."' ";
		else:
			$content_sql.= " `logincontrol` = '' ";
		endif;
		$content_sql.= " WHERE `cid` = ".intval($cid);
		// do sql statement
		doSQL($content_sql);
    } 
    // single based content
    else {
		$content_sql = "UPDATE `content` SET ";
		$content_sql.= " `uid` = ".intval($_SESSION['wspvars']['userid']).", "; // visibility
		$content_sql.= " `visibility` = ".intval($_POST['visible']).", "; // visibility
		$content_sql.= " `content_lang` = '".escapeSQL($_POST['lang'])."', "; // content_lang
		$content_sql.= " `description` = '".escapeSQL(trim($_POST['description']))."', "; // description
        $content_sql.= " `valuefields` = '".escapeSQL($data)."', "; // valuefields
		$content_sql.= " `showday` = '".intval(array_sum($_POST['weekday']))."', "; // showday
		$timetable = array();
		if (array_key_exists('startdate', $_POST) && is_array($_POST['startdate'])):
			foreach ($_POST['startdate'] AS $tk => $tv):
				$startdate = datetotime($_POST['formatdate'][$tk]." H:i:s", $_POST['startdate'][$tk]." 00:00:01");
				$enddate = datetotime($_POST['formatdate'][$tk]." H:i:s", $_POST['enddate'][$tk]." 23:59:59");
				$timetable[] = array(intval($startdate),intval($enddate));	
			endforeach;
			unset($_POST['startdate']); unset($_POST['enddate']);
		endif;
		if (array_key_exists('timetable', $_POST) && is_array($_POST['timetable'])):
			foreach ($_POST['timetable'] AS $tk => $tv):
				$timetable[] = explode(";", $tv);
			endforeach;
		endif;
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
//		$content_sql.= "`containerview` = '".intval($_POST['containerview'])."', ";
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
        if ($content_res['aff']==0) {
            addWSPMsg('errormsg', returnIntLang('content not saved', false));
            addWSPMsg('errormsg', var_export($content_res, true));
        }
    }
	
	// updating user restrictions
	// updating menu for changed content
	$ccres = intval(doResultSQL("SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($_POST['editmid'])));
	$nccres = 0; if ($ccres==0): $nccres = 2;
    elseif ($ccres==1): $nccres = 3;
	elseif ($ccres==2): $nccres = 2;
	elseif ($ccres==3): $nccres = 3;
	elseif ($ccres==4): $nccres = 5;
	elseif ($ccres==5): $nccres = 5;
	endif;
	doSQL("UPDATE `menu` SET `contentchanged` = ".intval($nccres)." WHERE `mid` = ".intval($_POST['editmid']));
	if (isset($_SESSION['wspvars']['autoparsecontent']) && $_SESSION['wspvars']['autoparsecontent']==1) {
		// autoparse contents 2016-03-23
		$pubsql = doSQL("INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = '".time()."', `action` = 'publishcontent', `param` = '".intval($_POST['editmid'])."', `timeout` = 0, `done` = 0, `priority` = 1, `outputuid` = ".intval($_SESSION['wspvars']['userid']).", `lang` = '".trim($_POST['lang'])."', `output` = ''");
		if ($pubsql['res']) {
            addWSPMsg('noticemsg', returnIntLang('contentedit set to autopublish'));
        }
        else {
			addWSPMsg('errormsg', returnIntLang('publisher error setup queue'));
		}
	}
	// jump back to content structure
	if(isset($_POST['back']) && intval($_POST['back'])==1) {
		// jump back
		header("location: contents.php");
		die();
    }
}
// adding content ON page
if ((isset($_POST['op']) && $_POST['op']=='add') && isset($_POST['sid']) && isset($_POST['gcid']) && isset($_POST['mid']) && intval($_POST['mid'])>0) {
	// find contents in same content area
	$newpos = intval($_POST['posvor']);
	if ($newpos>0) {
		$exc_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `content_area` = ".intval($_POST['carea'])." AND `position` >= ".$newpos." ORDER BY `position`";
		$exc_res = mysql_query($exc_sql);
		$exc_num = 0;
		if ($exc_res) { $exc_num = mysql_num_rows($exc_res); }
		if ($exc_num>0) {
			for ($ecres=0; $ecres<$exc_num; $ecres++) {
				$upd_sql = "UPDATE `content` SET `position` = ".($newpos+$ecres+1)." WHERE `cid` = ".intval(mysql_result($exc_res, $ecres, 'cid'));
				mysql_query($upd_sql);
            }
        }
    }
	else {
		$pc_sql = "SELECT MAX(`position`) FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `content_area` = ".intval($_POST['carea']);
		$pc_res = mysql_query($pc_sql);
		$pc_num = 0;
		if ($pc_res) { $pc_num = mysql_num_rows($pc_res); }
		if ($pc_num>0) { $newpos = (intval(mysql_result($pc_res, 0))+1); } else { $newpos = 1; }
    }
	// check for globalcontent
	$interpreterguid = trim($_POST['sid']);
    $globalcontentid = 0;
	if ($_POST['sid']=='0' && intval($_POST['gcid'])>0) {
		$gc_sql = "SELECT `id`, `interpreter_guid` FROM `content_global` WHERE `id` = ".intval($_POST['gcid'])." LIMIT 0,1";
		$gc_res = doSQL($gc_sql);
		if ($gc_res['num']>0) {
            $interpreterguid = trim($gc_res['set'][0]['interpreter_guid']); 
            $globalcontentid = intval($gc_res['set'][0]['id']); 
        }
	}
	$nc_sql = "INSERT INTO `content` SET 
		`mid` = ".intval($_POST['mid']).",
		`uid` = ".intval($_SESSION['wspvars']['userid']).",
        `globalcontent_id` = ".intval($globalcontentid).",
		`connected` = 0,
		`content_area` = ".intval($_POST['carea']).",
		`content_lang` = '".$_POST['lang']."',
		`position` = ".$newpos.",
		`visibility` = 1,
		`showday` = 0,
		`showtime` = '',
		`sid` = '',
		`valuefields` = '',
		`xajaxfunc` = '',
		`xajaxfuncnames` = '',
		`lastchange` = ".time().",
		`interpreter_guid` = '".escapeSQL($interpreterguid)."'";
    $nc_res = doSQL($nc_sql);
	if (intval($nc_res['inf'])>0) {
        addWSPMsg('resultmsg', returnIntLang('contentedit new content created succesfully'));
        addWSPMsg('noticemsg', var_export($nc_res, true));
		$_SESSION['wspvars']['editcontentid'] = intval($nc_res['inf']);
		header('location: contentedit.php');
    }
	else {
		addWSPMsg('errormsg', returnIntLang('contentedit failure creating new content'));
	}
}

$contentinfo_sql = "SELECT * FROM `content` WHERE `cid` = ".intval($cid);
$contentinfo_res = doSQL($contentinfo_sql);

// checking if content editing is allowed for this user
$contenteditallowed = false;
if ($contentinfo_res['num']>0){
    // get last publish from menu table
    $contentinfo_res['set'][0]['lastpublish'] = intval(doResultSQL('SELECT `lastpublish` FROM `menu` WHERE `mid` = '.intval($contentinfo_res['set'][0][ 'mid'])));

    if (($_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==4) && in_array(intval($contentinfo_res['set'][0][ 'mid']), $_SESSION['wspvars']['rights']['contents_array'])) {
        $contenteditallowed = true;
    }
    else if ($_SESSION['wspvars']['rights']['contents']==1 || $_SESSION['wspvars']['rights']['contents']==3) {
        $contenteditallowed = true;
    }
    else if ($_SESSION['wspvars']['rights']['contents']==7) {
        if (count($_SESSION['wspvars']['rights']['contents_array'])>0) {

//            $GLOBALS['midlist'] = array($_SESSION['wspvars']['rights']['contents_array'][0]);
//            subMID($_SESSION['wspvars']['rights']['contents_array'][0]);
//            $menuIDs = $GLOBALS['midlist'];
//            $menuallowed = $GLOBALS['midlist'];
//            $_SESSION['contentmidlist'] = $GLOBALS['midlist'];
//
            echo "<pre>using globals is deprecated</pre>";
            die();

        }
        if(in_array(intval($contentinfo_res['set'][0][ 'mid']), $menuIDs)) {
            $contenteditallowed = true;
        }
    }
    else if ($_SESSION['wspvars']['rights']['contents']==15) {
        if (count($_SESSION['wspvars']['rights']['sitestructure_array'])>0) {

//            $GLOBALS['midlist'] = array($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
//            subMID($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
//            $menuIDs = $GLOBALS['midlist'];
//            $menuallowed = $GLOBALS['midlist'];
//            $_SESSION['contentmidlist'] = $GLOBALS['midlist'];

            echo "<pre>using globals is deprecated</pre>";
            die();
        }
        if (in_array(intval($contentinfo_res['set'][0][ 'mid']), $menuIDs)) {
            $contenteditallowed = true;
        }
    }
}

$sitedata = getWSPProperties();

// get all data from content element if accessable
if($contenteditallowed) {
    // is it a global content?
    $gcid = intval($contentinfo_res['set'][0]['globalcontent_id']);
    if($gcid>0) {
        // select values from global content
        $gcinfo_sql = "SELECT `description`, `valuefields` FROM `content_global` WHERE `id` = ".$gcid." AND `trash` = 0";
        $gcinfo_res = doSQL($gcinfo_sql);
        if ($gcinfo_res['num']>0) {
            $description = trim($gcinfo_res['set'][0]['description']);
            $fieldvalue = unserializeBroken($gcinfo_res['set'][0]['valuefields']);
        } else {
            $description = '';
            $fieldvalue = array();
        }
    }
    else {
        // use values from normal content
        $description = trim($contentinfo_res['set'][0]['description']);
        $fieldvalue = unserializeBroken($contentinfo_res['set'][0]['valuefields']);
    }
    // overwrite values with backup values if requested
    if($backupView) {
        $fieldvalue = $backupValues;
    }
    // set new field description with older 'desc' value
    if (trim($description)=='' && isset($fieldvalue['desc']) && trim($fieldvalue['desc'])!='') {
        $description = trim($fieldvalue['description']);
    }
    // get interpreter information
    $interpreterClass = NULL;
    $isInterpreter = false;
    $isGeneric = false;
    $isModular = false;
    $editHead = returnIntLang('contentedit content');
    // (re)load interpreter data 
    $interpreterinfo_sql = "SELECT * FROM `interpreter` WHERE `guid` = '".escapeSQL($contentinfo_res['set'][0]['interpreter_guid'])."'";
    $interpreterinfo_res = doSQL($interpreterinfo_sql);
    // if some data was found
    if ($interpreterinfo_res['num']>0) {
        // content is described by an interpreter
        $parsefile = trim($interpreterinfo_res['set'][0]['parsefile']);
        // get interpreter-class
        if (is_file(cleanPath(DOCUMENT_ROOT.WSP_DIR."/data/interpreter/".trim($parsefile)))) {
            $isInterpreter = true;
            $editHead = $interpreterinfo_res['set'][0]['name'];
//            if (WSP_DEV) {
                $editHead.= " <em>(v".$interpreterinfo_res['set'][0]['version'].")</em>";
//            }
        }
        else {
            addWSPMsg('errormsg', returnIntLang('contentedit interpreterfile not found1', false).trim($parsefile).returnIntLang('contentedit interpreterfile not found2', false));
        }
    } 
    else if ($contentinfo_res['set'][0]['interpreter_guid']=='modularcontent') {
        $isModular = true;
        $editHead = returnIntLang('contentedit modular contents');
    } else {
        $isGeneric = true;
        $editHead = returnIntLang('contentedit generic contents');
    }
}
    
/* include head */
require ("./data/include/header.inc.php");
require ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");

?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('contentstructure headline'); ?> <small>ID: <?php echo intval($cid); ?></small></h1>
                <p class="page-subtitle"><?php echo returnIntLang('contentstructure info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1);
            
            if($contenteditallowed) { ?>
            <form id="editcontents" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
                <input type="hidden" id="editcontentid" name="editcontentid" value="" />
            </form>
            <form name="editcontent" id="editcontent" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                <!-- overview other contents and meta -->
                <div class="row">
                    <div class="col-md-6">
                        <?php require('./data/panels/contentedit.contentlist.inc.php'); ?>
                    </div>
                    <div class="col-md-6">
                        <?php require('./data/panels/contentedit.meta.inc.php'); ?>
                    </div>
                </div>
                <!-- content editor -->
                <div class="row">
                    <div class="col-md-12">
                        <?php
                                     
                        if ($isInterpreter) {
                                    
                            include(cleanPath(DOCUMENT_ROOT.WSP_DIR."/data/interpreter/".trim($parsefile)));
                            $clsInterpreter = new $interpreterClass;
                                    
                            // prevent showing up errors from loaded class
                            ini_set("display_errors", 0);
                            if (defined('WSP_DEV') && WSP_DEV) { ini_set("display_errors", 1); }

                            // call interpreter functions
                            $multilangcontent = false; if (property_exists($clsInterpreter, 'multilang')) $multilangcontent = $clsInterpreter -> multilang;
                            $flexiblecontent = false; if (property_exists($clsInterpreter, 'flexible')) $flexiblecontent = $clsInterpreter -> flexible;
                            $tinymcetextarea = false; if (property_exists($clsInterpreter, 'textarea')) $tinymcetextarea = $clsInterpreter -> textarea;

                            if (!(is_array($multilangcontent)) || !($flexiblecontent)) {
                                if (!(is_array($multilangcontent))) {
                                    addWSPMsg ('noticemsg', returnIntLang('interpreter none multilang', true));
                                }
                                if (!($flexiblecontent)) {
                                    addWSPMsg ('noticemsg', returnIntLang('interpreter non flexible', true));
                                }
                            }
                            if (is_array($multilangcontent)) {
                                foreach($lang AS $lkey => $lvalue) {
                                    if (array_key_exists($lkey, $multilangcontent) && is_array($multilangcontent[$lkey])) {
                                        $lang[$lkey] = array_merge($lang[$lkey], $multilangcontent[$lkey]);
                                    }
                                }
                            }
                            if ($clsInterpreter->multifields!==false && intval($clsInterpreter->multifields)>0) {
                                for ($mf=0; $mf<intval($clsInterpreter->multifields); $mf++) {
                                    echo '<div class="panel panel-tab">';
                                    echo '<div class="panel-heading">';
                                    echo '<h3 class="panel-title">';
                                    echo $editHead." [".$mf."] ";
                                    if($backupView) { echo returnIntLang('contentedit backup viewer'); }
                                    echo '</h3>';
                                    
                                    if($gcid>0) {
                                        echo '<p class="panel-subtitle">';
                                        echo returnIntLang('contentedit global content comment', true);
                                        echo '</p>';
                                    }

                                    echo '</div>';
                                    echo '<div class="panel-body">';
                                    // call real interpreter content
                                    echo $clsInterpreter -> getMultiEdit($fieldvalue, intval($contentinfo_res['set'][0]['mid']), intval($cid), $mf);
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } 
                            else {
                                echo '<div class="panel panel-tab">';
                                echo '<div class="panel-heading">';
                                echo '<h3 class="panel-title">';
                                echo $editHead;
                                if($backupView) { echo returnIntLang('contentedit backup viewer'); }
                                echo '</h3>';
                                
                                if($gcid>0) {
                                    echo '<p class="panel-subtitle">';
                                    echo returnIntLang('contentedit global content comment', true);
                                    echo '</p>';
                                }

                                echo '</div>';
                                echo '<div class="panel-body">';
                                // call real interpreter content
                                echo $clsInterpreter -> getEdit($fieldvalue, intval($contentinfo_res['set'][0]['mid']), intval($cid));
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        else if ($isModular) { ?>
                        <div class="panel panel-tab">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <?php echo $editHead; ?>
                                    <?php if($backupView) { echo returnIntLang('contentedit backup viewer'); } ?>
                                </h3>
                                <?php

                                if($gcid>0) {
                                    echo '<p class="panel-subtitle">';
                                    echo returnIntLang('contentedit global content comment', true);
                                    echo '</p>';
                                }

                                ?>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-12 input-label">
                                        <p>Modularer Content</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php }
                        else if ($isGeneric) {
                            // generic content interpreter, if no interpreter file was found
                            // should be extended to handle contents with fault deleted interpreter files ..
                            // 
                            ?>
                        <div class="panel panel-tab">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <?php echo $editHead; ?>
                                    <?php if($backupView) { echo returnIntLang('contentedit backup viewer'); } ?>
                                </h3>
                                <?php

                                if($gcid>0) {
                                    echo '<p class="panel-subtitle">';
                                    echo returnIntLang('contentedit global content comment', true);
                                    echo '</p>';
                                }

                                ?>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-3 input-label">
                                        <p><?php echo returnIntLang('contentedit generic wysiwyg desc'); ?></p>
                                    </div>
                                    <div class="col-md-9">
                                        <p><input type="text" name="field[desc]" id="field_desc" value="<?php if (isset($fieldvalue['desc'])) { echo prepareTextField($fieldvalue['desc']); } ?>" class="form-control" /></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 input-label"><?php echo returnIntLang('contentedit generic wysiwyg content'); ?></div>
                                    <div class="col-md-9"><textarea name="field[content]" id="field_content" class="form-control summernote"><?php if (isset($fieldvalue['content'])) { echo stripslashes(stripslashes(stripslashes($fieldvalue['content']))); } ?></textarea></div>
                                </div>
                            </div>
                        </div>
                        <?php }
                                     
                        echo '<input type="hidden" name="editcontentid" value="'.$_SESSION['wspvars']['editcontentid'].'" />';
                        echo '<input type="hidden" name="editmid" value="'.intval($_SESSION['wspvars']['editmenuid']).'" />';
                        echo '<input type="hidden" name="gcid" value="'.(isset($gcid)?intval($gcid):'').'" />';
                        echo '<input type="hidden" name="op" id="op" value="" />';
                        echo '<input type="hidden" name="back" id="back" value="" />';
                        echo '<input type="hidden" name="lang" id="editlang" value="'.$_SESSION['wspvars']['workspacelang'].'" />';
                
                        ?>
                    </div>
                </div>
                <!-- special view options -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-tab">
                        <div class="panel-heading">
                            <ul class="nav nav-tabs pull-left">
                                <?php
                                
                                // get information about existing backups before
                                // setting up backup tab as active 
                                     
                                $contentbackup_sql = "SELECT `cbid` FROM `content_backup` WHERE `cid` = ".intval($cid)." ORDER BY `lastchange` ASC";
                                $contentbackup_res = doSQL($contentbackup_sql);
                                if ($contentbackup_res['num']==0) {
                                    $backupView = false;
                                    $backupChange = false;
                                }
                                     
                                ?>
                                <li class="<?php echo (($backupView===false && $backupChange===false)?'active':''); ?>"><a href="#specialview" data-toggle="tab"><i class="far fa-eye "></i> <?php echo returnIntLang('content view options', true); ?></a></li>
                                <li><a href="#timeview" data-toggle="tab"><i class="far fa-clock"></i> <?php echo returnIntLang('structure special view time', true); ?></a></li>
                                <li id="showusercontrol" <?php if(intval($contentinfo_res['set'][0]['visibility'])!=4) { echo " style='display: none;' "; } ?>><a href="#userview" data-toggle="tab"><i class="fa fa-user"></i> <?php echo returnIntLang('structure special view user', true); ?></a></li>
                                <?php 
                                
                                $contentbackup_sql = "SELECT `cbid` FROM `content_backup` WHERE `cid` = ".intval($cid)." ORDER BY `lastchange` ASC";
                                $contentbackup_res = doSQL($contentbackup_sql);
                                
                                if ($contentbackup_res['num']>0) {
                                ?>
                                <li class="<?php echo (($backupView || $backupChange)?'active':''); ?>"><a href="#backupview" data-toggle="tab"><i class="fas fa-archive"></i> <?php echo returnIntLang('content backupview', true); ?></a></li>
                                <?php } ?>
                            </ul>
                            <h3 class="panel-title">&nbsp;</h3>
                        </div>
                        <div class="panel-body" >
                            <div class="tab-content no-padding">
                                <div class="tab-pane fade in <?php echo (($backupView===false && $backupChange===false)?'active':''); ?>" id="specialview">
                                    <div class="row">
                                        <div class="col-md-3 input-label">
                                            <p><?php echo returnIntLang('contentedit showstat'); ?></p>
                                        </div>
                                        <div class="col-md-3">
                                            <p><select name="visible" id="visibility" class="form-control singleselect fullwidth" onChange="checkUserRes(this.value);">
                                                <option value="0" <?php if(intval($contentinfo_res['set'][0]['visibility'])==0): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat inactive'); ?></option>
                                                <option value="1" <?php if(intval($contentinfo_res['set'][0]['visibility'])==1 || intval($contentinfo_res['set'][0]['visibility'])==""): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat active'); ?></option>
                                                <option value="2" <?php if(intval($contentinfo_res['set'][0]['visibility'])==2): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat active logout'); ?></option>
                                                <option value="3" <?php if(intval($contentinfo_res['set'][0]['visibility'])==3): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat active login'); ?></option>
                                                <option value="4" <?php if(intval($contentinfo_res['set'][0]['visibility'])==4): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('contentedit showstat active user'); ?></option>
                                            </select></p>
                                            <script>
                                            <!--
                                                
                                            function checkUserRes(tVal){if(tVal<4){$('#showusercontrol').hide();}else{$('#showusercontrol').show();}}

                                            // -->
                                            </script>
                                        </div>
                                        <div class="col-md-3 input-label">
                                            <?php echo returnIntLang('contentedit special container'); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <p><select name="container" class="form-control singleselect fullwidth" onChange="checkViewRes(this.value);">
                                                <option value="0" <?php echo ((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])==0) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==0))?' selected="selected" ':''; ?>>SECTION</option>
                                                <option value="1" <?php echo ((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])==1) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==1))?' selected="selected" ':''; ?>>DIV</option>
                                                <option value="2" <?php echo ((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])==2) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==2))?' selected="selected" ':''; ?>>SPAN</option>
                                                <option value="3" <?php echo ((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])==3) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==3))?' selected="selected" ':''; ?>>LI</option>
                                                <option value="4" <?php echo ((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])==4) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==4))?' selected="selected" ':''; ?>><?php echo returnIntLang('str none'); ?></option>
                                                <option value="5" <?php echo ((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])==5) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])==5))?' selected="selected" ':''; ?>><?php echo returnIntLang('contentedit special container combine'); ?></option>
                                            </select></p>
                                            <script>
                                            <!--
                                                
                                            function checkViewRes(tVal){
                                                
                                                if (tVal>=4) {
                                                    $('.showcontainerclass').show();
                                                    $('.showcontaineroptions').hide();}
                                                else {
                                                    $('.showcontainerclass').show();
                                                    $('.showcontaineroptions').show();
                                                }
                                                if(tVal>=2) {
                                                    $('.showcontainerview').hide();
                                                } else {
                                                    $('.showcontainerview').show();
                                                }

                                            }

                                            // -->
                                            </script>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 input-label showcontaineroptions showcontainerclass" <?php echo (((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])>=4) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])>=4))?' style="display: none;" ':''); ?>>
                                            <?php echo returnIntLang('contentedit special container class'); ?>
                                        </div>
                                        <div class="col-md-3 showcontaineroptions showcontainerclass" <?php echo (((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])>=4) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])>=4))?' style="display: none;" ':''); ?>>
                                            <p><input type="text" name="containerclass" value="<?php echo prepareTextField(trim($contentinfo_res['set'][0]['containerclass'])); ?>" class="form-control" /></p>
                                        </div>
                                        <div class="col-md-3 input-label showcontainerclass showcontainerview showcontaineroptions" <?php echo (((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])>=4) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])>=4))?' style="display: none;" ':''); ?>>
                                            <?php echo returnIntLang('contentedit special container viewfunction'); ?>
                                        </div>
                                        <div class="col-md-3 showcontainerclass showcontainerview showcontaineroptions" <?php echo (((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])>=4) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])>=4))?' style="display: none;" ':''); ?>>
                                            <p><select name="containerview" class="form-control singleselect fullwidth">
                                                <option value="0" <?php echo ((isset($contentinfo_res['set'][0]['containerview']) && intval($contentinfo_res['set'][0]['containerview'])==0))?' selected="selected" ':''; ?>><?php echo returnIntLang('str none'); ?></option>
                                                <option value="1" <?php echo (isset($contentinfo_res['set'][0]['containerview']) && intval($contentinfo_res['set'][0]['containerview'])==1)?' selected="selected" ':''; ?>><?php echo returnIntLang('contentedit special container viewfunction fade'); ?></option>
                                                <option value="2" <?php echo (isset($contentinfo_res['set'][0]['containerview']) && intval($contentinfo_res['set'][0]['containerview'])==2)?' selected="selected" ':''; ?>><?php echo returnIntLang('contentedit special container viewfunction slide from left'); ?></option>
                                                <option value="2" <?php echo (isset($contentinfo_res['set'][0]['containerview']) && intval($contentinfo_res['set'][0]['containerview'])==2)?' selected="selected" ':''; ?>><?php echo returnIntLang('contentedit special container viewfunction slide from right'); ?></option>
                                            </select></p>
                                        </div>
                                    </div>
                                    <div class="row showcontaineroptions" <?php echo (((isset($contentinfo_res['set'][0]['container']) && intval($contentinfo_res['set'][0]['container'])>=4) || (!(isset($contentinfo_res['set'][0]['container'])) && isset($sitedata['containerpref']) && intval($sitedata['containerpref'])>=4))?' style="display: none;" ':''); ?>>
                                        <div class="col-md-3 input-label">
                                            <?php echo returnIntLang('contentedit special mobileclass'); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <select name="displayclass" class="form-control singleselect fullwidth" >
                                                <option value="0" <?php if(intval($contentinfo_res['set'][0]['displayclass'])==0) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('contentedit special mobileclass none'); ?></option>
                                                <option value="1" <?php if(intval($contentinfo_res['set'][0]['displayclass'])==1) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('contentedit special mobileclass desktop only'); ?> (.desktop)</option>
                                                <option value="2" <?php if(intval($contentinfo_res['set'][0]['displayclass'])==2) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('contentedit special mobileclass mobile only'); ?> (.mobile)</option>
                                                <option value="3" <?php if(intval($contentinfo_res['set'][0]['displayclass'])==3) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('contentedit special mobileclass print only'); ?> (.print)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 input-label">
                                            <?php echo returnIntLang('contentedit special anchor'); ?>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="containeranchor" value="<?php if(trim($contentinfo_res['set'][0]['containeranchor'])!=""): echo prepareTextField(trim($contentinfo_res['set'][0]['containeranchor'])); endif; ?>" placeholder="<?php echo "ID".intval($contentinfo_res['set'][0]['cid']) ?>" class="form-control" />
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade in" id="timeview">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <?php

                                            $showday = intval($contentinfo_res['set'][0]['showday']);
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
                                            <p>
                                                <?php echo returnIntLang('structure daily based view'); ?><input type="hidden" name="weekday[0]" value="0" />
                                            </p>
                                            <p>
                                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[1]" id="weekday_1" value="1" <?php echo $weekdayvalue[1]; ?> /> <span><?php echo returnIntLang('str monday'); ?></span></label>
                                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[2]" id="weekday_2" value="2" <?php echo $weekdayvalue[2]; ?> /> <span><?php echo returnIntLang('str tuesday'); ?></span></label>
                                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[3]" id="weekday_3" value="4" <?php echo $weekdayvalue[3]; ?> /> <span><?php echo returnIntLang('str wednesday'); ?></span></label>
                                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[4]" id="weekday_4" value="8" <?php echo $weekdayvalue[4]; ?> /> <span><?php echo returnIntLang('str thursday'); ?></span></label>
                                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[5]" id="weekday_5" value="16" <?php echo $weekdayvalue[5]; ?> /> <span><?php echo returnIntLang('str friday'); ?></span></label>
                                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[6]" id="weekday_6" value="32" <?php echo $weekdayvalue[6]; ?> /> <span><?php echo returnIntLang('str saturday'); ?></span></label>
                                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[7]" id="weekday_7" value="64" <?php echo $weekdayvalue[7]; ?> /> <span><?php echo returnIntLang('str sunday'); ?></span></label>
                                            </p>
                                            <p>
                                                <?php echo returnIntLang('structure time based view'); ?>
                                            </p>
                                            <div id='timing'>
                                                <?php

                                                $alltimes = trim($contentinfo_res['set'][0]['showtime']);
                                                $time = array();
                                                if ($alltimes!="") {
                                                    $giventimes = unserializeBroken($alltimes);
                                                    foreach ($giventimes AS $gkey => $gvalue) {
                                                        $time[$gvalue[0]] = $gvalue[1];
                                                    }
                                                }
                                                ksort($time);

                                                foreach ($time AS $tstart => $tend) {
                                                    echo '<div class="row" id="time-'.$tstart.'" style="margin-bottom: 2px;">'. 
                                                        '<div class="col-sm-12">'.
                                                        '<div class="input-daterange input-group" data-provide="datepicker" data-date-format="dd.mm.yyyy">'.
                                                        '<input type="text" placeholder="'.returnIntLang('structure time based view starts', false).'" class="input-sm form-control inline-datepicker" value="'.date("d.m.Y", $tstart).'" data-date-format="dd.mm.yyyy" name="startdate[]" />'.
                                                        '<span class="input-group-addon"> – </span>'.
                                                        '<input type="text" placeholder="'.returnIntLang('structure time based view ends', false).'" class="input-sm form-control inline-datepicker" value="'.date("d.m.Y", $tend).'" data-date-format="dd.mm.yyyy" name="enddate[]" />'.
                                                        '<span class="input-group-addon"><i class="fas fa-minus-square" onclick="removeTime(\'time-'.$tstart.'\');"></i></span>'.
                                                        '<input type="hidden" value="d.m.Y" name="formatdate[]" />'.
                                                        '</div>'.
                                                        '</div>'.
                                                        '</div>';
                                                }
                                                $time = array_flip($time); 

                                                ?>
                                            </div>
                                            <div class='row' id="newtime">
                                                <div class='col-sm-12'>
                                                    <div class="input-daterange input-group" data-provide="datepicker" data-date-format="dd.mm.yyyy">
                                                        <input type="text" placeholder="<?php echo returnIntLang('structure time based view starts', false); ?>" class="input-sm form-control inline-datepicker" id="newstart" data-date-format="dd.mm.yyyy" />
                                                        <span class="input-group-addon"> – </span>
                                                        <input type="text" placeholder="<?php echo returnIntLang('structure time based view ends', false); ?>" class="input-sm form-control inline-datepicker" id="newend" data-date-format="dd.mm.yyyy" />
                                                        <span class="input-group-addon"><i class="fas fa-plus-square" onclick="addTime();"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <script>

                                                function addTime() {
                                                    if ($('#newstart').val()!='') {
                                                        var newTimeId = Math.round((new Date()).getTime() / 1000);
                                                        var newTime = '<div class="row" id="time-' + newTimeId + '" style="margin-bottom: 2px;">' + 
                                                            '<div class="col-sm-12">' +
                                                            '<div class="input-daterange input-group" data-provide="datepicker" data-date-format="dd.mm.yyyy">' +
                                                            '<input type="text" placeholder="<?php echo returnIntLang('structure time based view starts', false); ?>" class="input-sm form-control inline-datepicker" value="' + $('#newstart').val() + '" data-date-format="dd.mm.yyyy" name="startdate[]" />' +
                                                            '<span class="input-group-addon"> – </span>' +
                                                            '<input type="text" placeholder="<?php echo returnIntLang('structure time based view ends', false); ?>" class="input-sm form-control inline-datepicker" value="' + $('#newend').val() + '" data-date-format="dd.mm.yyyy" name="enddate[]" />' +
                                                            '<span class="input-group-addon"><i class="fas fa-minus-square" onclick="removeTime(\'time-' + newTimeId + '\');"></i></span>' +
                                                            '<input type="hidden" value="d.m.Y" name="formatdate[]" />' +
                                                            '</div>' +
                                                            '</div>' +
                                                            '</div>';
                                                        $('#newstart').val('');
                                                        $('#newend').val('');
                                                        $('#timing').append(newTime);
                                                    }
                                                }

                                                function removeTime(tID) {
                                                    $('#' + tID).fadeOut(500, function(){
                                                        $('#' + tID).remove();
                                                    });
                                                }

                                                $(document).ready(function() { 
                                                    $('.inline-datepicker').datepicker({
                                                        todayHighlight: true,
                                                        format: 'dd.mm.yyyy',
                                                        language: 'de',
                                                    });
                                                });
                                                
                                            </script>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade in" id="userview">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <?php

                                            $logincontrol = unserializeBroken($contentinfo_res['set'][0]['logincontrol']);

                                            $usercontrol_sql = "SELECT `rid`, `user` FROM `restrictions` WHERE `usertype` = 22 ORDER BY `user`";
                                            $usercontrol_res = doSQL($usercontrol_sql);
                                            if ($usercontrol_res['num']>0) {
                                                foreach ($usercontrol_res['set'] AS $ucrsk => $ucrsv) {
                                                    echo "<label class='fancy-checkbox custom-bgcolor-blue'><input type='checkbox' name='userrestriction[]' value='".intval($ucrsv["rid"])."'";
                                                    if(is_array($logincontrol) && in_array(intval($ucrsv["rid"]), $logincontrol)) {
                                                        echo " checked='checked' ";
                                                    }
                                                    echo " /> <span>".trim($ucrsv["user"])."</span></label>";
                                                }
                                            } ?>
                                            
                                        </div>
                                    </div>
                                </div>
                                <?php 
                                
                                $contentbackup_sql = "SELECT * FROM `content_backup` WHERE `cid` = ".intval($cid)." ORDER BY `lastchange` ASC";
                                $contentbackup_res = doSQL($contentbackup_sql);
                                
                                if ($contentbackup_res['num']>0) {
                                ?>
                                <div class="tab-pane fade in <?php echo (($backupView || $backupChange)?'active':''); ?>" id="backupview">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th><?php echo returnIntLang('str date'); ?></th>
                                                        <th><?php echo returnIntLang('str time'); ?></th>
                                                        <th><?php echo returnIntLang('str user'); ?></th>
                                                        <th><?php echo returnIntLang('contentedit backup changed fields'); ?></th>
                                                        <th><?php echo returnIntLang('str action'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php

                                                    foreach ($contentbackup_res['set'] AS $cbresk => $cbresv) {
                                                        $lastchange = intval($cbresv["lastchange"]);
                                                        if ($lastchange==0) {
                                                            $showdate = returnIntLang('contentedit backup clear version', true);
                                                            $showtime = "";
                                                        }
                                                        else {
                                                            $showdate = date(returnIntLang('format date', false), $lastchange);
                                                            $showtime = date(returnIntLang('format time', false), $lastchange);
                                                        }
                                                        // get contents from backup
                                                        $backupvalues = unserializeBroken($cbresv["valuefields"]);
                                                        // get contents to compare with
                                                        $nextbackup = array();
                                                        if ($cbresk<intval($contentbackup_res['num']-1)) {
                                                            // get contents from next backup step
                                                            $nextbackup = unserializeBroken($contentbackup_res['set'][$cbresk+1]["valuefields"]);
                                                        } else {
                                                            // get actual contents
                                                            $nextbackup = unserializeBroken($cbresv['valuefields']);
                                                        }
                                                        $changes = array();
                                                        if (is_array($backupvalues) && is_array($nextbackup)) {
                                                            foreach ($backupvalues AS $bkey => $bvalue) {
                                                                if (!(isset($backupvalues[$bkey]))) {
                                                                    $changes[] = "<span class='btn btn-xs btn-primary'>+ ".strtoupper($bkey)."</span>";
                                                                }
                                                                else if (((isset($backupvalues[$bkey]) && isset($nextbackup[$bkey])) && $backupvalues[$bkey]!=$nextbackup[$bkey]) || (isset($backupvalues[$bkey]) && !(isset($nextbackup[$bkey])))) {
                                                                    $changes[] = "<span class='btn btn-xs btn-danger'>% ".strtoupper($bkey)."</span>";
                                                                }
                                                            }
                                                        }
                                                        if (count($changes)>0) {
                                                            echo "<tr>";
                                                            echo "<td>".$showdate."</td>";
                                                            echo "<td>".$showtime."</td>";
                                                            echo "<td>".returnUserData('shortcut', intval($cbresv['uid']))."</td>";
                                                            echo "<td>";
                                                            $sc=0; foreach ($changes AS $elem) {
                                                                echo "<a onclick='showBDetails(".$cbresk.",".$sc.")'>".$elem."</a> ";
                                                                $sc++;
                                                            }
                                                            echo "</td>";
                                                            echo "<td><a style=\"cursor: pointer;\" onclick=\"document.getElementById('backupop').value = 'restorebackup'; document.getElementById('restorebid').value = '".intval($cbresv['cbid'])."'; document.getElementById('restorebackup').submit();\"><i class='far fa-eye fa-btn'></i></a> <a style=\"cursor: pointer;\" onclick=\"document.getElementById('backupop').value = 'removebackup'; document.getElementById('restorebid').value = '".intval($cbresv['cbid'])."'; document.getElementById('restorebackup').submit();\"><i class='far fa-trash fa-btn'></i></a></td>";
                                                            echo "</tr>";
                                                            
                                                            if (defined('WSP_DEV') && WSP_DEV) {

                                                                // detail view of changes
                                                                // is this really useful ?
                                                                if (is_array($changes) && count($changes)>0) {
                                                                    if (is_array($backupvalues) && is_array($nextbackup)) {
                                                                        $sc = 0; 
                                                                        foreach ($backupvalues AS $bkey => $bvalue) {
                                                                            if (((isset($backupvalues[$bkey]) && isset($nextbackup[$bkey])) && $backupvalues[$bkey]!=$nextbackup[$bkey]) || (isset($backupvalues[$bkey]) && !(isset($nextbackup[$bkey])))) {
                                                                                echo "<tr class='details-".$cbresk."-".$sc."' style='display: none;'>";
                                                                                echo "<td colspan='8'>";
                                                                                if (is_array($backupvalues[$bkey])) {
                                                                                    $bout = "<span class='btn btn-xs btn-danger'>".$bkk."</span>\n";
                                                                                    foreach ($backupvalues[$bkey] AS $bkk => $bkv):
                                                                                        if (isset($backupvalues[$bkey][$bkk]) && isset($nextbackup[$bkey][$bkk]) && $backupvalues[$bkey][$bkk]!=$nextbackup[$bkey][$bkk]):
                                                                                            $bout.= "<span class='btn btn-xs btn-danger'>".$bkk."</span> ";
                                                                                            $bout.=trim($backupvalues[$bkey][$bkk]." » ".$nextbackup[$bkey][$bkk]);
                                                                                            $bout.= " - ";	
                                                                                        elseif (isset($backupvalues[$bkey][$bkk]) && !(isset($nextbackup[$bkey][$bkk]))):
                                                                                            $bout.= "<span class='btn btn-xs btn-danger'>".$bkk."</span> ";
                                                                                            $bout.= $backupvalues[$bkey][$bkk]." » <em>leer</em>";
                                                                                            $bout.= " - ";	
                                                                                        elseif (isset($nextbackup[$bkey][$bkk]) && !(isset($backupvalues[$bkey][$bkk]))):
                                                                                            $bout.= "<span class='btn btn-xs btn-danger'>".$bkk."</span> ";
                                                                                            $bout.= "<em>leer</em> » ".$nextbackup[$bkey][$bkk];
                                                                                            $bout.= " - ";	
                                                                                        endif;
                                                                                    endforeach;
                                                                                    $bout.= "\n";
                                                                                    foreach ($nextbackup[$bkey] AS $bkk => $bkv):
                                                                                        if (isset($backupvalues[$bkey][$bkk]) && isset($nextbackup[$bkey][$bkk]) && $backupvalues[$bkey][$bkk]!=$nextbackup[$bkey][$bkk]):
                                                                                            $bout.= "<span class='btn btn-xs btn-danger'>".$bkk."</span> ";
                                                                                            $bout.= $backupvalues[$bkey][$bkk]." » ".$nextbackup[$bkey][$bkk];
                                                                                            $bout.= " - ";	
                                                                                        elseif (isset($backupvalues[$bkey][$bkk]) && !(isset($nextbackup[$bkey][$bkk]))):
                                                                                            $bout.= "<span class='btn btn-xs btn-danger'>".$bkk."</span> ";
                                                                                            $bout.= $backupvalues[$bkey][$bkk]." <span class='btn btn-xs btn-danger'>»</span> <em>".returnIntLang('str empty')."</em>";
                                                                                            $bout.= " - ";	
                                                                                        elseif (isset($nextbackup[$bkey][$bkk]) && !(isset($backupvalues[$bkey][$bkk]))):
                                                                                            $bout.= "<span class='btn btn-xs btn-danger'>".$bkk."</span> ";
                                                                                            $bout.= "<em>".returnIntLang('str empty')."</em> <span class='btn btn-xs btn-danger'>»</span> ".$nextbackup[$bkey][$bkk];
                                                                                            $bout.= " - ";	
                                                                                        endif;		
                                                                                    endforeach;
                                                                                    $bout.= "\n";
                                                                                }
                                                                                else {
                                                                                    $bout = '';
                                                                                    if (isset($backupvalues[$bkey]) && isset($nextbackup[$bkey])):
                                                                                        $bout.= "<span class='btn btn-xs btn-danger'>".$bkey."</span><br /><pre>".strip_tags(trim($backupvalues[$bkey]))."</pre><br /><span class='btn btn-xs btn-danger'>»</span><br /><pre>".strip_tags(trim($nextbackup[$bkey]))."</pre>";
                                                                                    elseif (isset($backupvalues[$bkey])):
                                                                                        $bout.= "<span class='btn btn-xs btn-danger'>".$bkey."</span> ".trim($backupvalues[$bkey])." <span class='btn btn-xs btn-danger'>»</span> <em>".returnIntLang('str empty')."</em><br/>";
                                                                                    elseif (isset($nextbackup[$bkey])):
                                                                                        $bout.= "<span class='btn btn-xs btn-danger'>".$bkey."</span> <em>".returnIntLang('str empty')."</em> <span class='btn btn-xs btn-danger'>»</span> <pre>".strip_tags(trim($nextbackup[$bkey]))."</pre>";
                                                                                    endif;
                                                                                }
                                                                                if (trim(strip_tags($bout))!='') { echo $bout; }
                                                                                echo "</td>";
                                                                                echo "</tr>";
                                                                                $sc++;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        } else {
                                                            echo "<tr>";
                                                            echo "<td>".$showdate."</td>";
                                                            echo "<td>".$showtime."</td>";
                                                            echo "<td>".returnUserData('shortcut', intval($cbresv['uid']))."</td>";
                                                            echo "<td>".returnIntLang('contentedit backup initial version')."</td>";
                                                            echo "<td><a style=\"cursor: pointer;\" onclick=\"document.getElementById('backupop').value = 'restorebackup'; document.getElementById('restorebid').value = '".intval($cbresv['cbid'])."'; document.getElementById('restorebackup').submit();\"><i class='far fa-eye fa-btn'></i></a> <a style=\"cursor: pointer;\" onclick=\"document.getElementById('backupop').value = 'removebackup'; document.getElementById('restorebid').value = '".intval($cbresv['cbid'])."'; document.getElementById('restorebackup').submit();\"><i class='far fa-trash fa-btn'></i></a></td>";
                                                            echo "</tr>";
                                                        }
                                                    }
                                                    
                                                    
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                
                                    function showBDetails(elem,change) {
                                        $('.details-' + elem + '-' + change).toggle();
                                    }
                                
                                </script>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </form>
            <form id="restorebackup" name="restorebackup" method="post">
                <input type="hidden" id="backupop" name="op" value="restorebackup" /><input type="hidden" id="restorebid" name="bid" value="" /><input type="hidden" name="cid" value="<?php echo intval($cid); ?>" />
            </form>
            <!-- buttonset -->
            <div class="row">
                <div class="col-md-12">
                    <p>
                        <a id="button-save" class="btn btn-success"><?php echo returnIntLang('str save', false); ?></a>
                        <a id="button-save-back" class="btn btn-success"><?php echo returnIntLang('btn save and back', false); ?></a>
                        <a href="contents.php" class="btn btn-warning"><?php echo returnIntLang('str back', false); ?></a>
                        <?php if(!(isset($gcid)) || intval($gcid)==0): ?> <a href="contentedit.php?cid=<?php echo $cid; ?>&op=localtoglobal" onclick="if (confirm(unescape('<?php echo returnIntLang('contentedit request convert lokal to global', false); ?>'))) {return true;} else {return false;}" class="btn btn-info"><?php echo returnIntLang('str global2local1', false); ?><tag style="font-size: 80%;"><?php echo returnIntLang('str global2local2', false); ?></tag><?php echo returnIntLang('str global2local3', false); ?></a> <?php if($interpreterClass=='text'): ?><a href="contentedit.php?cid=<?php echo $cid; ?>&op=togeneric" class="orangefield"><?php echo returnIntLang('str text2generic', false); ?></a><?php endif; ?> <?php else: ?> <a href="contentedit.php?cid=<?php echo $cid; ?>&op=globaltolocal" onclick="if (confirm(unescape('<?php echo returnIntLang('contentedit global2local request', false); ?>'))) {return true;} else {return false;}" class="btn btn-info"><?php echo returnIntLang('str global2local3', false); ?><tag style="font-size: 80%;"><?php echo returnIntLang('str global2local2', false); ?></tag><?php echo returnIntLang('str global2local1', false); ?></a> <?php endif; ?>
                        <a href="showpreview.php?previewid=<?php echo intval($contentinfo_res['set'][0]['mid']); ?>&previewlang=<?php echo $_SESSION['wspvars']['workspacelang']; ?>" target="_blank" class="btn btn-info"><?php echo returnIntLang('str preview', false); ?></a> <?php if ($contentbackup_res['num']>0) { ?> <a href="contentedit.php?cid=<?php echo $cid; ?>&op=erasebackup" class="btn btn-danger"><?php echo returnIntLang('contentedit delete backup', false); ?></a> <?php } ?> <a class="btn btn-danger"><?php echo returnIntLang('str delete', false); ?></a>
                    </p>
                </div>
            </div>
            <?php 
            }
            else {
            ?>
            <div class="row">
                <div class="col-md-12">
                    <p><?php echo returnIntLang('contentedit you are not allowed to edit this content or the desired content could not be found'); ?></p>
                </div>
            </div>  
            <?php } ?>
        </div>
    </div>
</div>
<?php require("./data/panels/summernote.inc.php") ; ?>
<!-- END MAIN -->
<script>

    $('#button-save').on('click', function() {
        $('#op').val('save');
        $('#editcontent').submit();
        return false;
    });
    
    $('#button-save-back').on('click', function() {
        $('#op').val('save');
        $('#back').val(1);
        $('#editcontent').submit();
        return false;
    });
    
    
    // sets up the editcontent-form and its value and submits the form 
    function doEdit(cid) {
        if (parseInt(cid)>0) {
            $('#editcontentid').val(parseInt(cid));
            $('#editcontents').submit();
        }
    }

    $('.section-opener').on('click', function(){
        $(this).parents('.panel-body').find('.section-opener').addClass('inactive').removeClass('active');
        $(this).parents('.panel-body').find('section').hide();
        $(this).removeClass('inactive').addClass('active');
        $(this).next('section').show();
    });

    $('.panel-heading').on('click', function(){
        if ($(this).parents('.panel-accordion').hasClass('active')) {
            $(this).parents('.panel-accordion').removeClass('active');
            $(this).next('.panel-body').hide();
        } else {
            $(this).parents('.panel-accordion').parent().find('.panel-accordion').each(function(e){
                $(this).removeClass('active');
                $(this).find('.panel-body').hide();
            });
            $(this).parents('.panel-accordion').addClass('active');
            $(this).next('.panel-body').show();
        }
    });
                                                   
</script>
<?php require ("./data/include/footer.inc.php"); ?>