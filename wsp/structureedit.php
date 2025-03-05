<?php
/**
 * edit menupoints details
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-03-10
 */

/* start session ----------------------------- */
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
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
$_SESSION['wspvars']['pagedesc'] = array('fas fa-sitemap',returnIntLang('menu content'),returnIntLang('menu content structure edit'));
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].";mid=".$mid;
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = true;
$_SESSION['wspvars']['addpagecss'] = array(
    'bootstrap-multiselect.css',
    'bootstrap-datepicker3.min.css'
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'jquery/jquery.autogrowtextarea.js',
    'bootstrap/bootstrap-multiselect.js',
    'bootstrap/bootstrap-datepicker.js'
    );

/* second includes --------------------------- */
require ("data/include/checkuser.inc.php");
require ("data/include/siteinfo.inc.php");

/* define page specific vars ----------------- */
/* page specific funcs and actions */
if ($op=='save'):
    // update contentchanged info
	$nccres = contentChangeStat(intval($_POST['mid']), "structure");
	// get level info based on $_POST['subpointfrom']
	$isindex = 0;
	$level = 0;
	$level_sql = "SELECT `level` FROM `menu` WHERE `mid` = ".intval($_POST['subpointfrom']);
	$level_res = doResultSQL($level_sql);
	$level = intval($level_res)+1;
	// check for changed filename
	$oname_sql = "SELECT `isindex`, `filename`, `forwarding_id` FROM `menu` WHERE `mid` = ".intval($_POST['mid']);
	$oname_res = doSQL($oname_sql);
	if ($oname_res['num']>0) { 
		$isindex = intval($oname_res['set'][0]['isindex']);
		if(trim($oname_res['set'][0]['filename'])!=trim($_POST['filename'])) { 
			// if filename was changed
			$ename_sql = "SELECT `mid` FROM `menu` WHERE `mid` != ".intval($_POST['mid'])." AND `connected` = ".intval($_POST['subpointfrom'])." AND `filename` = '".escapeSQL(trim($oname_res['set'][0]['filename']))."'";
			$ename_res = doSQL($ename_sql);
			if ($ename_res['num']==0) {
				// no other menupoint is named like THIS one, so create menu entry with header location 
                // to new file and write this to publisher with long time delay
				$oldfile_sql = "INSERT INTO `menu` SET ";
				$oldfile_sql.= "`level` = ".intval($level).", ";
				$oldfile_sql.= "`connected` = ".intval($_POST['subpointfrom']).", ";
				$oldfile_sql.= "`editable` = 2, ";
				$oldfile_sql.= "`position` = 0, ";
				$oldfile_sql.= "`visibility` = 0, ";
				$oldfile_sql.= "`description` = '".escapeSQL("autofile-".date('Y-m-d-H-i-s')."-".trim($oname_res['set'][0]['filename']))."', ";
				$oldfile_sql.= "`filename` = '".escapeSQL(trim($oname_res['set'][0]['filename']))."', ";
				$oldfile_sql.= "`forwarding_id` = ".intval($_POST['mid']).", ";
				$oldfile_sql.= "`contentchanged` = 4, ";
				$oldfile_sql.= "`changetime` = ".time().", ";
				$oldfile_sql.= "`isindex` = 0, ";
				$oldfile_sql.= "`trash` = 1";
                $oldfile_res = doSQL($oldfile_sql);
				if ($oldfile_res['aff']) {
					// setup publisher
                }
            }
			$nccres = 7; // status 7 = structure changed, but only file was renamed
		}
		if(intval($oname_res['set'][0]['forwarding_id'])!=intval($_POST['forwarding_id'])) {
			$nccres = contentChangeStat(intval($_POST['mid']), "complete");
		}
	}
	// update query		
	$menuupdate_sql = "UPDATE `menu` SET ";
	// basefacts
    $menuupdate_sql.= "`level` = ".intval($level);
	$menuupdate_sql.= ", `connected` = ".intval($_POST['subpointfrom']);
	$menuupdate_sql.= ", `editable` = ".intval($_POST['editable']); unset($_POST['editable']);
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
	$menuupdate_sql.= ", `filename` = '".escapeSQL(trim($_POST['filename']))."'"; unset($_POST['filename']);
	$menuupdate_sql.= ", `templates_id` = ".intval($_POST['template']); unset($_POST['template']);
    // menuimage
	$menuupdate_sql.= ", `imageon` = '".escapeSQL(trim($_POST['imageon']))."'"; unset($_POST['imageon']);
	$menuupdate_sql.= ", `imageoff` = '".escapeSQL(trim($_POST['imageoff']))."'"; unset($_POST['imageoff']);
	$menuupdate_sql.= ", `imageakt` = '".escapeSQL(trim($_POST['imageakt']))."'"; unset($_POST['imageakt']);
	$menuupdate_sql.= ", `imageclick` = '".escapeSQL(trim($_POST['imageclick']))."'"; unset($_POST['imageclick']);
	// handling different targets
    if ($_POST['targetfile']=='anchor' && trim($_POST['anchortarget'])!='') {
        $menuupdate_sql.= ", `filetarget` = '".escapeSQL(trim(str_replace("#", "", $_POST['anchortarget'])))."'";
        $menuupdate_sql.= ", `offlink` = NULL"; 
        $menuupdate_sql.= ", `docintern` = ''";
        $menuupdate_sql.= ", `internlink_id` = 0";
    } else if ($_POST['targetfile']=='external' && trim($_POST['offlink'])!='') {
        $menuupdate_sql.= ", `filetarget` = ''";
        $menuupdate_sql.= ", `offlink` = '".escapeSQL(trim($_POST['offlink']))."'"; 
        $menuupdate_sql.= ", `docintern` = ''";
        $menuupdate_sql.= ", `internlink_id` = 0";
    } else if ($_POST['targetfile']=='document' && trim($_POST['docintern'])!='') {
        $menuupdate_sql.= ", `filetarget` = ''";
        $menuupdate_sql.= ", `offlink` = NULL"; 
        $menuupdate_sql.= ", `docintern` = '".escapeSQL(trim($_POST['docintern']))."'";
        $menuupdate_sql.= ", `internlink_id` = 0";
    } else if ($_POST['targetfile']=='internal' && intval($_POST['urlintern'])!=intval($_POST['mid']) && intval($_POST['urlintern'])>0) {
        // page works as internal link
        $menuupdate_sql.= ", `filetarget` = ''";
        $menuupdate_sql.= ", `offlink` = NULL"; 
        $menuupdate_sql.= ", `docintern` = ''";
        $menuupdate_sql.= ", `internlink_id` = ".intval($_POST['urlintern']);
    } else {
        // page works as normal link
        $menuupdate_sql.= ", `filetarget` = ''";
        $menuupdate_sql.= ", `offlink` = NULL"; 
        $menuupdate_sql.= ", `docintern` = ''";
        $menuupdate_sql.= ", `internlink_id` = 0";
    }
    unset($_POST['anchortarget']);
    unset($_POST['offlink']);
    unset($_POST['docintern']);
    unset($_POST['urlintern']);
	// header based forwarding (nearly the same as urlintern, but only subpages)
	$menuupdate_sql.= ", `forwarding_id` = ".intval($_POST['forwarding_id']); unset($_POST['forwarding_id']);
	$menuupdate_sql.= ", `contentchanged` = ".intval($nccres);
	$menuupdate_sql.= ", `changetime` = ".time();
	// create a tmp addjs var to hold it for dynamic menu
    $tmpaddscript = ''; if (array_key_exists('usejs', $_POST) && is_array($_POST['usejs'])) { $tmpaddscript = escapeSQL(serialize($_POST['usejs'])); unset($_POST['usejs']); }
    $menuupdate_sql.= ", `addscript` = '".$tmpaddscript."'";	
	// create a tmp addcss var to hold it for dynamic menu
    $tmpaddcss = ''; if (array_key_exists('usecss', $_POST) && is_array($_POST['usecss'])) { $tmpaddcss = escapeSQL(serialize($_POST['usecss'])); unset($_POST['usecss']); }
	$menuupdate_sql.= ", `addclass` = '".escapeSQL(trim($_POST['useclass']))."'"; unset($_POST['useclass']);
	$menuupdate_sql.= ", `linktoshortcut` = '".escapeSQL(trim($_POST['shortcut']))."'"; unset($_POST['shortcut']);
	//	denylang
	if (intval($_POST['visibility'])==0):
		$menuupdate_sql.= ", `visibility` = 0";  unset($_POST['visibility']);
		$menuupdate_sql.= ", `denylang` = NULL";
	elseif (array_key_exists('denylang', $_POST) && is_array($_POST['denylang']) && count($_POST['denylang'])==count($_SESSION['wspvars']['sitelanguages'])):
		$menuupdate_sql.= ", `visibility` = 0";  unset($_POST['visibility']);
		$menuupdate_sql.= ", `denylang` = NULL";	
	elseif (array_key_exists('denylang', $_POST) && is_array($_POST['denylang'])):
		$menuupdate_sql.= ", `visibility` = 1";  unset($_POST['visibility']);
		$menuupdate_sql.= ", `denylang` = '".escapeSQL(serialize($_POST['denylang']))."'"; unset($_POST['denylang']);
	else:
		$menuupdate_sql.= ", `visibility` = ".intval($_POST['visibility']);  unset($_POST['visibility']);
		$menuupdate_sql.= ", `denylang` = NULL";
	endif;
	// update timing -> use var $timetable with contents later again !!
	$timetable = array();
	if (array_key_exists('startdate', $_POST) && is_array($_POST['startdate'])) {
		foreach ($_POST['startdate'] AS $tk => $tv) {
            if (trim($_POST['startdate'][$tk])!='') {
                // get returned timestamp for start and end
                $format = $_POST['formatdate'][0];
                $startdate = datetotime($format, trim($_POST['startdate'][$tk]));
                $enddate = datetotime($format, trim($_POST['enddate'][$tk]));
                $timetable[] = array(intval($startdate),intval($enddate));
            }
        }
		unset($_POST['startdate']); unset($_POST['enddate']);
    }
	if (array_key_exists('timetable', $_POST) && is_array($_POST['timetable'])):
		foreach ($_POST['timetable'] AS $tk => $tv):
			$timetable[] = explode(";", $tv);
		endforeach;
	endif;
	if (count($timetable)>0) { $menuupdate_sql.= ", `showtime` = '".escapeSQL(serialize($timetable))."'"; } else { $menuupdate_sql.= ", `showtime` = ''"; }
    // javascript functions
	$menuupdate_sql.= ", `jsmouseover` = '".escapeSQL(trim($_POST['jscallmouseover']))."'"; unset($_POST['jscallmouseover']);
	$menuupdate_sql.= ", `jsclick` = '".escapeSQL(trim($_POST['jscallclick']))."'"; unset($_POST['jscallclick']);
	$menuupdate_sql.= ", `jsmouseout` = '".escapeSQL(trim($_POST['jscallmouseout']))."'"; unset($_POST['jscallmouseout']);
	if ($_POST['targetopens']=='_popup') {
        if (intval($_POST['filewidth'])>0 && intval($_POST['fileheight'])>0) {
            $menuupdate_sql.= ", `filepopup` = '".serialize(array('top' => intval($_POST['filetop']), 'left' => intval($_POST['fileleft']), 'height' => intval($_POST['fileheight']), 'width' => intval($_POST['filewidth'])))."'"; unset($_POST['filetop']); unset($_POST['fileleft']); unset($_POST['fileheight']); unset($_POST['filewidth']);
        } else {
            $menuupdate_sql.= ", `filepopup` = ''";
        }
        $menuupdate_sql.= ", `interntarget` = ''"; unset($_POST['targetopens']);
    } else {
        $menuupdate_sql.= ", `filepopup` = ''";
        $menuupdate_sql.= ", `interntarget` = '".escapeSQL(trim($_POST['targetopens']))."'"; unset($_POST['targetopens']);
    }
	// `internpopup` deprecated
    // `externpopup` deprecated
	$menuupdate_sql.= ", `isindex` = ".intval($_POST['isindex']);
	if (intval($_POST['isindex'])==1):
		// set all other items isindex on same level to 0
		$isindex_sql = "UPDATE `menu` SET `isindex` = 0 WHERE `connected` = ".intval($_POST['subpointfrom']);
		doSQL($isindex_sql);
	endif; unset($_POST['isindex']); unset($_POST['subpointfrom']);
	
	$menuupdate_sql.= ", `weekday` = ".intval(array_sum($_POST['weekday'])); unset($_POST['weekday']);
	$menuupdate_sql.= ", `mobileexclude` = ".intval($_POST['mobileexclude']); unset($_POST['mobileexclude']);
	if (intval($_POST['logincontrol'])==1 && array_key_exists('loginuser', $_POST) && is_array($_POST['loginuser'])):
		$menuupdate_sql.= ", `logincontrol` = '".escapeSQL(serialize($_POST['loginuser']))."'"; unset($_POST['loginuser']);
	else:
		$menuupdate_sql.= ", `logincontrol` = ''";
	endif;
	$menuupdate_sql.= ", `login` = ".intval($_POST['logincontrol']); unset($_POST['logincontrol']);
	$menuupdate_sql.= ", `lockpage` = ".intval($_POST['lockpage']); unset($_POST['lockpage']);
    //	pluginmenu concept
    if (array_key_exists('pluginconfig', $_POST) && is_array($_POST['pluginconfig']) && trim($_POST['pluginconfig']['fromtable'])!='') {
        foreach ($_POST['pluginconfig']['where'] AS $wk => $wv) {
            if (trim($wv)=='') { 
                unset($_POST['pluginconfig']['where'][$wk]);
                unset($_POST['pluginconfig']['whereopt'][$wk]);
                unset($_POST['pluginconfig']['whereval'][$wk]);
                unset($_POST['pluginconfig']['wherecombine'][$wk]);
            }
        }
        
        
        $menuupdate_sql.= ", `pluginconfig` = '".escapeSQL(serialize($_POST['pluginconfig']))."' ";
        // create dynamic menu entries with post data
        $dynmid = createDynamicMenu(intval($_POST['mid']), $_POST['pluginconfig'], true, true);
        // unset var to prevent displaying
        unset($_POST['pluginconfig']);
    } else {
        $menuupdate_sql.= ", `pluginconfig` = '' ";
    } 

	$menuupdate_sql.= " WHERE `mid` = ".intval($_POST['mid']);
	$menuupdate_res = doSQL($menuupdate_sql);
    if ($menuupdate_res['res']) {
		addWSPMsg('resultmsg', returnIntLang('menuedit menupoint successfully updated', false));
    } else {
		addWSPMsg('errormsg', 'menuedit menupoint update error');
        addWSPMsg('errormsg', $menuupdate_sql);
        addWSPMsg('errormsg', var_export($menuupdate_res['err'], true));
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
	
	// update user rights using $_POST[rights_update_user] 
	// disabled 14-07-15 because of new properties and resulting errors
	/*
	$userrightoptions = array();
	foreach ($_SESSION['wspvars']['rightpossibilities'] AS $key => $possibilities):
		if ($possibilities==2):
			$userrightoptions[] = $_SESSION['wspvars']['rightabilities'][$key];
		endif;
	endforeach;
	if (array_key_exists('rights_update_user', $_POST) && is_array($_POST['rights_update_user'])):
		foreach ($_POST['rights_update_user'] AS $key => $value):
			$userrights_sql = "SELECT `rid`, `rights`, `idrights` FROM `restrictions` WHERE `rid` = ".intval($value);
			$userrights_res = mysql_query($userrights_sql);
			$userrights_num = 0;
			if ($userrights_res): $userrights_num = mysql_num_rows($userrights_res); endif;
			if ($userrights_num>0):
				$temprights = unserializeBroken(mysql_result($userrights_res, 0, "rights"));
				$tempidrights = unserializeBroken(mysql_result($userrights_res, 0, "idrights"));
				foreach($userrightoptions AS $uokey => $uovalue):
					if (array_key_exists(("user_".$uovalue), $_POST) && array_key_exists(intval($value), $_POST[("user_".$uovalue)])): $setoptionvalue = intval($_POST[("user_".$uovalue)][intval($value)]); else: $setoptionvalue = 0; endif;
					if ($setoptionvalue==1):
						$tempidrights[$uovalue][] = strval($mid);
						$tempidrights[$uovalue] = array_unique($tempidrights[$uovalue]);
					elseif ($setoptionvalue==0):
						if (array_key_exists($uovalue, $tempidrights) && is_array($tempidrights[$uovalue])):
							$tempidrights[$uovalue] = array_unique($tempidrights[$uovalue]);
							unset($tempidrights[$uovalue][array_search($mid, $tempidrights[$uovalue])]);
							$tempidrights[$uovalue] = array_unique($tempidrights[$uovalue]);
						endif;
					endif;
					if ((!(array_key_exists($uovalue, $tempidrights)) || (array_key_exists($uovalue, $tempidrights) && count($tempidrights[$uovalue])==0)) && $setoptionvalue!=2):
						$temprights[$uovalue] = 0;
						unset($tempidrights[$uovalue]);
					elseif (count($tempidrights[$uovalue])>0 && $setoptionvalue!=2):
						$temprights[$uovalue] = 2;
					endif;
				endforeach;
//				$sql = "UPDATE `restrictions` SET `rights` = '".escapeSQL(serialize($temprights))."', `idrights` = '".escapeSQL(serialize($tempidrights))."' WHERE `rid` = ".intval($value);
				mysql_query($sql);
			endif;
		endforeach;
	endif;
	*/
	
	// update contentchanged var to all related pages
	$relmid = array_merge(returnIDRoot($_POST['mid']),returnIDTree($_POST['mid']));
	if (is_array($relmid) && count($relmid)>0):
		foreach($relmid AS $rk => $rv):
			$ccres_sql = "SELECT `contentchanged` FROM `menu` WHERE `contentchanged` != 1 && `contentchanged` != 3 && `mid` = ".intval($rv);
			$ccres = intval(doResultSQL($ccres_sql));
			$nccres = 0; if ($ccres==0): $nccres = 4; elseif ($ccres==2): $nccres = 5; elseif ($ccres==4): $nccres = 4; elseif ($ccres==5): $nccres = 5; elseif ($ccres==7): $nccres = 7; endif;
			$minfo_sql = "UPDATE `menu` SET `contentchanged` = ".intval($nccres)." WHERE `mid` = ".intval($rv);
			doSQL($minfo_sql);
		endforeach;
	endif;
	// maybe lookup template for used menuvars
	// maybe lookup menuvars for displayed levels and mids
	// maybe update mids with related contentchanged var
endif;

if (isset($_POST) && array_key_exists('backjump', $_POST) && $_POST['backjump']=="back"):
	$_SESSION['wspvars']['editmenuid'] = $mid;
	header('location: structure.php');
endif;

$menudetails_sql = "SELECT * FROM `menu` WHERE `mid` = ".$mid;
$menudetails_res = doSQL($menudetails_sql);

if ($menudetails_res['num']==0): 
    header ('location: structure.php');
else:
    //
    $menueditdata = $menudetails_res['set'][0];
    // converting some values
    $menueditdata['langdescription'] = unserializeBroken($menueditdata['langdescription']);
    $menueditdata['pluginconfig'] = unserializeBroken($menueditdata['pluginconfig']);
    $menueditdata['filepopup'] = unserializeBroken($menueditdata['filepopup']);
    $menueditdata['targetopens'] = trim($menueditdata['interntarget']);
    $menueditdata['denylang'] = unserializeBroken($menueditdata['denylang']);
    // adding information not stored in resultset
    // define real template if 'use upper template' is defined
    $menueditdata['real_templates_id'] = getTemplateID(intval($mid)); 
    // getting meta information
    $pagemeta_sql = "SELECT * FROM `pageproperties` WHERE `mid` = ".intval($mid);
    $pagemeta_res = doSQL($pagemeta_sql);
    if ($pagemeta_res['num']>0):
        $menueditdata['pagetitle'] = $pagemeta_res['set'][0]['pagetitle'];
        $menueditdata['pagekeys'] = $pagemeta_res['set'][0]['pagekeys'];
        $menueditdata['pagedesc'] = $pagemeta_res['set'][0]['pagedesc'];
    else:
        $menueditdata['pagetitle'] = ''; $menueditdata['pagekeys'] = ''; $menueditdata['pagedesc'] = '';
    endif;
    if (trim($menueditdata['filetarget'])!='') {
        $menueditdata['anchortarget'] = $menueditdata['filetarget'];
        $menueditdata['filetarget'] = 'anchor';  
    }
    else if ($menueditdata['internlink_id']!=intval($mid) && $menueditdata['internlink_id']>0) {
        $menueditdata['filetarget'] = 'internal';
    }
    else if (trim($menueditdata['docintern'])!='') {
        $menueditdata['filetarget'] = 'document';
    }
    else if (trim($menueditdata['offlink'])!='') {
        $menueditdata['filetarget'] = 'external';
    } 
    else {
        $menueditdata['filetarget'] = 'none';
    }
        
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
                <h1 class="page-title">
                    <?php echo returnIntLang('structure edit headline'); ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo returnIntLang('structure edit info'); ?>
                </p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i>
                    <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?>
                </li>
                <li>
                    <?php echo $_SESSION['wspvars']['pagedesc'][2]; ?>
                </li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <form id="frmmenudetail" name="frmmenudetail" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <?php echo returnIntLang('structure edit generell', true); ?>
                                </h3>
                            </div>
                            <div class="panel-body">
                                <?php 
                                if (intval($menueditdata['editable'])==7) {
                                ?>
                                <input type="hidden" name="subpointfrom" value="<?php echo intval($menueditdata['connected']); ?>" />
                                <input type="hidden" name="isindex" value="0" />
                                <?php
                                }
                                else {
                                ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?php 
                                            $forward_sql = "SELECT `mid`, `description` FROM `menu` WHERE trash != 1 AND `connected` = ".intval($mid)." ORDER BY `position`";
                                            $forward_res = doSQL($forward_sql);
                                            ?>
                                        <p>
                                            <input name="forwarding_id" type="hidden" value="0" />
                                            <?php echo returnIntLang('structure is subpoint to', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p>
                                            <select id="subpointfrom" name="subpointfrom" class="form-control searchselect">
                                                <?php 

                                                if ($_SESSION['wspvars']['rights']['sitestructure']==1) { 
                                                    echo "<option value='0'>".returnIntLang('structure menuedit mainmenu')."</option>";
                                                    echo returnStructureItem('menu', 0, true, 9999, array(intval($menueditdata['connected'])), 'option', array('disable' => array(intval($mid)))); 
                                                }
                                                else if ($_SESSION['wspvars']['rights']['sitestructure']==7 && intval($_SESSION['wspvars']['rights']['sitestructure_array'][0])>0) { 
                                                    if ($mid==$_SESSION['wspvars']['rights']['sitestructure_array'][0]) {
                                                        $topm_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($_SESSION['structuremidlist'][0]);
                                                        $topm_res = doResultSQL($topm_sql);
                                                        echo "<option value=\"".intval($topm_res)."\">".returnIntLang('structure edit property can not be changed', false)."</option>";
                                                    }
                                                    else {
                                                        $mpname_sql = "SELECT `description`, `level` FROM `menu` WHERE `mid` = ".intval($_SESSION['wspvars']['rights']['sitestructure_array'][0])." ORDER BY `level`, `position`";
                                                        $mpname_res = doSQL($mpname_sql);
                                                        if ($mpname_res['num']>0) {
                                                            echo "<option value='".intval($_SESSION['wspvars']['rights']['sitestructure_array'][0])."'>";
                                                            echo $mpname_res['set'][0]['description'];
                                                            echo "</option>";
                                                            echo returnStructureItem('menu', 0, true, 9999, array(intval($menueditdata['connected'])), 'option'); 
                                                        };
                                                    };
                                                }; ?>
                                            </select>
                                        </p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <?php echo returnIntLang('structure edit index def', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                            <input type="hidden" name="isindex" value="0" /><input name="isindex" type="checkbox" value="1" <?php if (intval($menueditdata['isindex'])==1) echo "checked" ; ?> onchange="document.getElementById('cfc').value = 1;" /><span>&nbsp;</span>
                                        </label>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <?php echo returnIntLang('structure edit generell show', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                            <input type="hidden" name="visibility" value="0" /><input name="visibility" type="checkbox" value="1" <?php if (intval($menueditdata['visibility'])==1) echo "checked" ; ?> onchange="document.getElementById('cfc').value = 1;" /><span>&nbsp;</span>
                                        </label>
                                    </div>
                                </div>
                                <?php 
                                if (intval($menueditdata['editable'])==7) {
                                ?>
                                <input type="hidden" name="lockpage" value="0" />
                                <?php
                                }
                                else {
                                ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <?php if (isset($_SESSION['wspvars']['bindcontentview']) && intval($_SESSION['wspvars']['bindcontentview'])==1):
                                                echo returnIntLang('structure show content even menu inactive');
                                            else:
                                                echo returnIntLang('structure hide content when menu inactive');
                                            endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                            <?php if (isset($_SESSION['wspvars']['bindcontentview']) && intval($_SESSION['wspvars']['bindcontentview'])>0):
                                            ?><input type="hidden" name="lockpage" value="0" /><input type="checkbox" name="lockpage" value="1" <?php if(isset($menueditdata['lockpage']) && intval($menueditdata['lockpage'])==1): echo " checked='checked' " ; endif; ?> />
                                            <?php
                                        else:
                                            ?><input type="hidden" name="lockpage" value="0" /><input type="checkbox" name="lockpage" value="1" <?php if(isset($menueditdata['lockpage']) && intval($menueditdata['lockpage'])==1): echo " checked='checked' " ; endif; ?> />
                                            <?php 
                                        endif; ?><span>&nbsp;</span>
                                        </label>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <?php echo returnIntLang('structure exclude mobile'); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                            <input type="hidden" name="mobileexclude" value="0" /><input type="checkbox" name="mobileexclude" id="mobileexclude" value="1" <?php if(intval($menueditdata['mobileexclude'])==1) echo "checked=\" checked\""; ?> onchange="document.getElementById('cfc').value = 1;"><span>&nbsp;</span>
                                        </label>
                                    </div>
                                </div>
                                <?php 
                                if (intval($menueditdata['editable'])==7) {
                                ?>
                                <input type="hidden" name="editable" value="7" />
                                <input type="hidden" name="template" value="0" />
                                <?php
                                }
                                else {
                                ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <?php echo returnIntLang('structure edit generell block', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p>
                                            <select id="editable" name="editable" class="form-control singleselect fullwidth" onchange="checkEditable(this.value);">
                                                <option value="0">
                                                    <?php echo returnIntLang('structure edit generell block not editable', true); ?>
                                                </option>
                                                <option value="1" <?php if (intval($menueditdata['editable'])==1) echo " selected='selected' " ; ?>>
                                                    <?php echo returnIntLang('structure edit generell block editable', true); ?>
                                                </option>
                                                <option value="9" <?php if (intval($menueditdata['editable'])==9) echo " selected='selected' " ; ?>>
                                                    <?php echo returnIntLang('structure edit generell block dynamic content', true); ?>
                                                </option>
                                            </select>
                                        </p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <?php echo returnIntLang('structure templatename', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><select name="template" id="template" class="form-control singleselect fullwidth">
                                                <option value="-1">
                                                    <?php echo returnIntLang('structure pleasechoosetemplate', true); ?>
                                                </option>
                                                <option value="0" <?php if (intval($menueditdata['templates_id'])==0): echo ' selected="selected"' ; endif; ?>>
                                                    <?php echo returnIntLang('structure chooseuppertemplate', true); ?>
                                                </option>
                                                <?php

                                        $templates_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
                                        $templates_res = doSQL($templates_sql);
                                        if ($templates_res['num']>0) {
                                            foreach ($templates_res['set'] AS $trsk => $trsv) {
                                                echo "<option value=\"".intval($trsv['id'])."\" ";
                                                if (intval($menueditdata['templates_id'])==intval($trsv['id'])): echo ' selected="selected"'; endif;
                                                echo ">";
                                                echo ((trim($trsv['name'])!='')?trim($trsv['name']):returnIntLang('str template', false));
                                                echo "</option>";
                                            }
                                        }
                                        
                                        ?>
                                            </select></p>
                                    </div>
                                </div>
                                <?php if ($forward_res['num']>0) { ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <?php echo returnIntLang('structure edit generell forwarding', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><select name="forwarding_id" id="forwarding_id" class="form-control singleselect fullwidth">
                                                <option value="0">
                                                    <?php echo returnIntLang('structure forward first active', false); ?>
                                                </option>
                                                <?php foreach ($forward_res['set'] AS $frsk => $frsv):
                                                echo "<option value=\"".intval($frsv['mid'])."\" ";
                                                if (intval($menueditdata['forwarding_id'])==intval($frsv['mid'])): echo ' selected="selected"'; endif;
                                                echo ">";
                                                echo trim($frsv['description']);
                                                echo "</option>";
                                            endforeach; ?>
                                            </select></p>
                                    </div>
                                </div>
                                <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <?php echo returnIntLang('structure edit names', true); ?>
                                </h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-horizontal">
                                    <?php if (intval($menueditdata['editable'])==7) { ?>
                                    <input type="hidden" name="filename" value="<?php echo $menueditdata['filename']; ?>" />
                                    <?php } else { ?>
                                    <div class="form-group">
                                        <div class="col-md-3 input-label">
                                            <p>
                                                <?php echo returnIntLang('str filename', true); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-9">
                                            <input name="filename" id="filename" type="text" size="22" maxlength="32" value="<?php echo $menueditdata['filename']; ?>" class="form-control" onchange="document.getElementById('cfc').value = 1; checkForUsedFilename(this.value);" />
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="form-group">
                                        <div class="col-md-3 input-label">
                                            <p><span data-toggle="tooltip" class="help" data-placement="bottom" title="" data-original-title="<?php echo returnIntLang('structure shortcut help', false); ?>">
                                                    <?php echo returnIntLang('structure shortcut', true); ?></span></p>
                                        </div>
                                        <div class="col-md-9">
                                            <input name="shortcut" id="shortcut" type="text" size="22" maxlength="32" value="<?php echo $menueditdata['linktoshortcut']; ?>" class="form-control" onchange="document.getElementById('cfc').value = 1; checkForUsedShortcut(this.value);" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-md-3 input-label">
                                            <p>
                                                <?php echo returnIntLang('structure avaiable shortcuts', true); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-9 input-label">
                                            <p>
                                                <?php 
                                            
                                            echo "<span data-toggle='tooltip' class='help' data-placement='bottom' title='' data-original-title='".returnIntLang('structure shortcut link info', false)."'>[%MID:".$mid."%]</span>";
                                            
                                            if (isset($menueditdata['linktoshortcut']) && trim($menueditdata['linktoshortcut'])!=''): 
                                            
                                                echo "<br /><span data-toggle='tooltip' class='help' data-placement='bottom' title='' data-original-title='".returnIntLang('structure shortcut page info', false)."'>[%PAGE:<span class='showshortcut'>".strtoupper(trim($menueditdata['linktoshortcut']))."</span>%]</span>";
                                                echo "<br /><span data-toggle='tooltip' class='help' data-placement='bottom' title='' data-original-title='".returnIntLang('structure shortcut link info', false)."'>[%LINK:<span class='showshortcut'>".strtoupper(trim($menueditdata['linktoshortcut']))."</span>%]</span>";
                                            
                                            endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if (intval(count($_SESSION['wspvars']['sitelanguages']['shortcut']))>1) {
                                    $langcell = array();
                                    foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value) {
                                        if (!(is_array($menueditdata['langdescription'])) || !(array_key_exists($value, $menueditdata['langdescription'])) || $menueditdata['langdescription'][$value]==""): $menueditdata['langdescription'][$value] = $menueditdata['description']; endif; 
                                        echo '<div class="form-group"><div class="col-md-3 input-label">'.returnIntLang('structure edit generell title', true).' "'.$_SESSION['wspvars']['sitelanguages']['longname'][$key].'"</div><div class="col-md-9"><input name="langdesc['.$value.']" type="text" maxlength="150" value="'.prepareTextField(stripslashes($menueditdata['langdescription'][$value])).'" class="form-control" onchange="document.getElementById(\'cfc\').value = 1;" /></div></div>';
                                    }
                                } else {
                                    ?>
                                    <div class="form-group">
                                        <div class="col-md-3 input-label">
                                            <p>
                                                <?php echo returnIntLang('structure edit generell title', true); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-9">
                                            <input name="description" type="text" size="22" value="<?php echo prepareTextField($menueditdata['description']); ?>" class="six full" onchange="document.getElementById('cfc').value = 1;" />
                                        </div>
                                    </div>
                                    <?php
                                } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-tab">
                            <div class="panel-heading">
                                <ul class="nav nav-tabs pull-left">
                                    <li <?php echo (isset($menueditdata['editable']) && intval($menueditdata['editable'])==9)?'style="display: block;" class="active"':' style="display: none;"'; ?> id="dynamiccontent_tab">
                                        <a href="#dynamiccontent" data-toggle="tab"><i class="far fa-cloud"></i>
                                        <?php echo returnIntLang('structure edit dynamiccontent', true); ?></a></li>
                                    <li <?php echo (isset($menueditdata['editable']) && intval($menueditdata['editable'])==9)?'':'class="active"'; ?> id="behaviour_tab"><a href="#behaviour" data-toggle="tab"><i class="far fa-share-alt"></i>
                                        <?php echo returnIntLang('structure edit behaviour', true); ?></a></li>
                                    <li><a href="#specialviewtime" data-toggle="tab"><i class="far fa-clock"></i>
                                            <?php echo returnIntLang('structure special view time', true); ?></a></li>
                                    <li><a href="#specialviewuser" data-toggle="tab"><i class="far fa-user"></i>
                                            <?php echo returnIntLang('structure special view user', true); ?></a></li>
                                    <li><a href="#meta" data-toggle="tab"><i class="fas fa-search"></i>
                                            <?php echo returnIntLang('structure edit meta', true); ?></a></li>
                                    <li><a href="#menuimage" data-toggle="tab"><i class="fas fa-image"></i>
                                            <?php echo returnIntLang('structure edit image', true); ?></a></li>
                                    <li><a href="#addons" data-toggle="tab"><i class="fas fa-cogs"></i>
                                            <?php echo returnIntLang('structure edit addon', true); ?></a></li>
                                </ul>
                                <h3 class="panel-title">&nbsp;</h3>
                            </div>
                            <div class="panel-body">
                                <div class="tab-content no-padding">
                                    <div class="tab-pane fade in <?php echo (isset($menueditdata['editable']) && intval($menueditdata['editable'])==9)?'active':''; ?>" id="dynamiccontent">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p>
                                                    <?php echo returnIntLang('structure edit dynamiccontent description', true); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu output rule'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-9 input-label">
                                                <p><select name="pluginconfig[outputrule]" class="form-control singleselect">
                                                    <option value="0" <?php echo ((isset($menueditdata['pluginconfig']['outputrule']) && intval($menueditdata['pluginconfig']['outputrule'])==0)?'selected="selected"':''); ?>><?php echo returnIntLang('structure dynmenu output rule delete and overwrite'); ?></option>
                                                    <option value="1" <?php echo ((isset($menueditdata['pluginconfig']['outputrule']) && intval($menueditdata['pluginconfig']['outputrule'])==1)?'selected="selected"':''); ?>><?php echo returnIntLang('structure dynmenu output rule hold and overwrite'); ?></option>
                                                </select></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu TABLE SELECT'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-3">
                                                <p><select name="pluginconfig[fromtable]" class="form-control singleselect" onchange="$('#cfc').val(1); selectDynTable(this.value);">
                                                    <?php
                                                    
                                                    $tables = array();
                                                    $ts_sql = "SELECT `id`, `name`, `dynamiccontent` FROM `modules` WHERE `dynamiccontent` != ''";
                                                    $ts_res = doSQL($ts_sql);
                                                    if ($ts_res['num']>0) {
                                                        foreach ($ts_res['set'] AS $tsk => $tsv) {
                                                            $dynamic = unserializeBroken($tsv['dynamiccontent']);
                                                            foreach ($dynamic AS $dck => $dcv) {
                                                                $tc_sql = "SELECT `table_comment` FROM INFORMATION_SCHEMA.TABLES WHERE `table_name` = '".escapeSQL($dck)."'";
                                                                $tc_res = doResultSQL($tc_sql);
                                                                if ($tc_res && trim($tc_res)!='') {
                                                                    $tables[$dck] = trim($tc_res)." ✓";
                                                                } else {
                                                                    $tables[$dck] = trim($tsv['name']." » ".$dck);
                                                                }
                                                            }
                                                        }
                                                    }
                                                    if (count($tables)>0) {
                                                        echo '<option value="">'.returnIntLang('hint choose', false).'</option>';
                                                        foreach ($tables AS $tk => $tv) {
                                                            echo '<option value="'.$tk.'" '.((isset($menueditdata['pluginconfig']['fromtable']) && $menueditdata['pluginconfig']['fromtable']==$tk)?' selected="selected" ':'').'>'.returnIntLang('str db table').' '.$tv.'</option>';
                                                        }
                                                    } else {
                                                        echo '<option value="">'.returnIntLang('structure dynmenu no data avaiable').'</option>';
                                                    }
                                                    ?>
                                                    </select></p>
                                            </div>
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu filename SELECT'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-3">
                                                <p>
                                                    <select name="pluginconfig[filename]" id="choose_dyntable_filename" class="form-control" onchange="$('#cfc').val(1);">
                                                        <?php 
                                                        
                                                    // if a table was already saved 
                                                    if (isset($menueditdata['pluginconfig']['fromtable']) && trim($menueditdata['pluginconfig']['fromtable'])!='') {
                                                        $tablefields = array();
                                                        $ts_sql = "SELECT `id`, `name`, `dynamiccontent` FROM `modules` WHERE `dynamiccontent` LIKE '%".escapeSQL(trim($menueditdata['pluginconfig']['fromtable']))."%'";
                                                        $ts_res = doSQL($ts_sql);
                                                        if ($ts_res['num']>0) {
                                                            foreach ($ts_res['set'] AS $tsk => $tsv) {
                                                                $dynamic = unserializeBroken($tsv['dynamiccontent']);
                                                                foreach ($dynamic AS $dck => $dcv) {
                                                                    if ($dck==trim($menueditdata['pluginconfig']['fromtable'])) {
                                                                        $tablefields = $dcv;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                       
                                                    if (isset($menueditdata['pluginconfig']['fromtable']) && trim($menueditdata['pluginconfig']['fromtable'])!='') {
                                                        if (count($tablefields)>0) {
                                                            echo '<option value="">'.returnIntLang('hint choose').'</option>';
                                                            foreach ($tablefields AS $tfk => $tfv) {
                                                                echo '<option value="'.$tfv.'" '.((isset($menueditdata['pluginconfig']['filename']) && $menueditdata['pluginconfig']['filename']==$tfv)?' selected="selected" ':'').'>'.returnIntLang('str db field').' '.$tfv.'</option>';
                                                            }
                                                        } else {
                                                            echo '<option value="">'.returnIntLang('structure dynmenu no tablefields defined').'</option>'; 
                                                        }
                                                    }
                                                    else {
                                                        echo '<option value="">'.returnIntLang('structure dynmenu choose table').'</option>'; 
                                                    }
                                                        
                                                    ?>
                                                    </select>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu description SELECT'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-3">
                                                <p>
                                                    <select name="pluginconfig[description]" id="choose_dyntable_description" class="form-control" onchange="$('#cfc').val(1);">
                                                        <?php 
                                                        
                                                        if (isset($menueditdata['pluginconfig']['fromtable']) && trim($menueditdata['pluginconfig']['fromtable'])!='') {
                                                            if (count($tablefields)>0) {
                                                                echo '<option value="">'.returnIntLang('hint choose').'</option>';
                                                                foreach ($tablefields AS $tfk => $tfv) {
                                                                    echo '<option value="'.$tfv.'" '.((isset($menueditdata['pluginconfig']['description']) && $menueditdata['pluginconfig']['description']==$tfv)?' selected="selected" ':'').'>'.returnIntLang('str db field').' '.$tfv.'</option>';
                                                                }
                                                            } else {
                                                                echo '<option value="">'.returnIntLang('structure dynmenu no tablefields defined').'</option>'; 
                                                            }
                                                        }
                                                        else {
                                                            echo '<option value="">'.returnIntLang('structure dynmenu choose table').'</option>'; 
                                                        }
                                                        
                                                    ?>
                                                    </select>
                                                </p>
                                            </div>
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu ORDER BY'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-2">
                                                <p>
                                                    <select name="pluginconfig[order]" id="choose_dyntable_order" class="form-control" onchange="$('#cfc').val(1);">
                                                        <?php 
                                                        
                                                        if (isset($menueditdata['pluginconfig']['fromtable']) && trim($menueditdata['pluginconfig']['fromtable'])!='') {
                                                            if (count($tablefields)>0) {
                                                                echo '<option value="">'.returnIntLang('hint choose').'</option>';
                                                                foreach ($tablefields AS $tfk => $tfv) {
                                                                    echo '<option value="'.$tfv.'" '.((isset($menueditdata['pluginconfig']['order']) && $menueditdata['pluginconfig']['order']==$tfv)?' selected="selected" ':'').'>'.returnIntLang('str db field').' '.$tfv.'</option>';
                                                                }
                                                            } else {
                                                                echo '<option value="">'.returnIntLang('structure dynmenu no tablefields defined').'</option>'; 
                                                            }
                                                        }
                                                        else {
                                                            echo '<option value="">'.returnIntLang('structure dynmenu choose table').'</option>'; 
                                                        }
                                                        
                                                        
                                                    ?>
                                                    </select>
                                                </p>
                                            </div>
                                            <div class="col-md-1">
                                                <select name="pluginconfig[orderdir]" class="form-control" onchange="$('#cfc').val(1);">
                                                    <option value="ASC" <?php if (isset($menueditdata['pluginconfig']['orderdir']) && trim($menueditdata['pluginconfig']['orderdir'])!='DESC' ) { echo " selected='selected' " ; } ?>>ASC</option>
                                                    <option value="DESC" <?php if (isset($menueditdata['pluginconfig']['orderdir']) && trim($menueditdata['pluginconfig']['orderdir'])=='DESC' ) { echo " selected='selected' " ; } ?>>DESC</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu WHERE OPTION'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-9" id="choose_dyntable_where_clause">
                                                <div class="row" id="choose_dyntable_where_template" style="display: none; background: #eee;">
                                                    <div class="col-md-4 col-sm-12">
                                                        <p><select name="pluginconfig[where][]" class="form-control choose_dyntable_where" onchange="$('#cfc').val(1);">
                                                                <?php 

                                                            // if a table was already saved 
                                                            if (isset($menueditdata['pluginconfig']['fromtable']) && trim($menueditdata['pluginconfig']['fromtable'])!='') {
                                                                if (count($tablefields)>0) {
                                                                    echo '<option value="">'.returnIntLang('hint choose').'</option>';
                                                                    foreach ($tablefields AS $tfk => $tfv) {
                                                                        echo '<option value="'.$tfv.'">'.returnIntLang('str db field').' '.$tfv.'</option>';
                                                                    }
                                                                } else {
                                                                    echo '<option value="">'.returnIntLang('structure dynmenu no tablefields defined').'</option>'; 
                                                                }
                                                            }
                                                            else {
                                                                echo '<option value="">'.returnIntLang('structure dynmenu choose table').'</option>'; 
                                                            }

                                                            ?>
                                                            </select></p>
                                                    </div>
                                                    <div class="col-md-2 col-sm-6">
                                                        <p><select name="pluginconfig[whereopt][]" class="form-control choose_dyntable_whereopt" onchange="$('#cfc').val(1);">
                                                                <option value="=">=</option>
                                                                <option value="!=">!=</option>
                                                                <option value=">">&gt;</option>
                                                                <option value="<">&lt;</option>
                                                                <option value=">=">&gt;=</option>
                                                                <option value="<=">&lt;=</option>
                                                                <option value="LIKE">LIKE</option>
                                                                <option value="NOT LIKE">NOT LIKE</option>
                                                            </select></p>
                                                    </div>
                                                    <div class="col-md-2 col-sm-6">
                                                        <p><input type="text" name="pluginconfig[whereval][]" value="" class="form-control choose_dyntable_whereval" /></p>
                                                    </div>
                                                    <div class="col-md-4 col-sm-12">
                                                        <p>
                                                            <select name="pluginconfig[wherecombine][]" class="form-control choose_dyntable_wherecombine" onchange="$('#cfc').val(1); addDynWhere(this.value, $(this).attr('rel'));">
                                                                <option value="">&nbsp;</option>
                                                                <option value="AND">AND</option>
                                                                <option value="ANDCOMBO">AND combine with next</option>
                                                                <option value="OR">OR</option>
                                                                <option value="ORCOMBO">OR combine with next</option>
                                                            </select>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu show in menu'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="fancy-checkbox custom-bgcolor-blue">
                                                    <input type="hidden" name="pluginconfig[visibility]" value="0"><input name="pluginconfig[visibility]" type="checkbox" value="1" onchange="document.getElementById('cfc').value = 1;"><span>&nbsp;</span>
                                                </label>
                                            </div>
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu lockpage'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="fancy-checkbox custom-bgcolor-blue">
                                                    <input type="hidden" name="pluginconfig[lockpage]" value="0"><input name="pluginconfig[lockpage]" type="checkbox" value="1" onchange="document.getElementById('cfc').value = 1;"><span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu hide in mobile'); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="fancy-checkbox custom-bgcolor-blue">
                                                    <input type="hidden" name="pluginconfig[mobileexclude]" value="0"><input name="pluginconfig[mobileexclude]" type="checkbox" value="1" onchange="document.getElementById('cfc').value = 1;"><span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                        <?php $dynnum = createDynamicMenu(intval($mid), $menueditdata['pluginconfig'], false, true); 
                                        if ($dynnum['posvalues']>0) { ?>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p>
                                                    <?php echo returnIntLang('structure dynmenu possible values1', true)." <strong>".$dynnum['posvalues']."</strong> ".returnIntLang('structure dynmenu possible values2', true); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php } ?>
                                    </div>
                                    <div class="tab-pane fade in  <?php echo (isset($menueditdata['editable']) && intval($menueditdata['editable'])==9)?'':'active'; ?>" id="behaviour">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p>
                                                    <?php echo returnIntLang('structure edit behaviour description', true); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 input-label">
                                                <p>
                                                    <?php echo returnIntLang('structure edit generell target', true); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-3">
                                                <p><select name="targetfile" id="target_file" onchange="changeTarget('target', this.value); changeTarget('opener', $('#target_opener').val());" class="form-control">
                                                        <option value="none" <?php if ($menueditdata['filetarget']=="none" ) echo "selected=\" selected\""; ?>>
                                                            <?php echo returnIntLang('structure edit generell target notarget', false); ?>
                                                        </option>
                                                        <option value="anchor" <?php if ($menueditdata['filetarget']=="anchor" ) echo "selected=\" selected\""; ?>>
                                                            <?php echo returnIntLang('structure edit generell target anchor', false); ?>
                                                        </option>
                                                        <option value="internal" <?php if ($menueditdata['filetarget']=="internal" ) echo "selected=\" selected\""; ?>>
                                                            <?php echo returnIntLang('structure edit generell target internal', false); ?>
                                                        </option>
                                                        <option value="document" <?php if ($menueditdata['filetarget']=="document" ) echo "selected=\" selected\""; ?>>
                                                            <?php echo returnIntLang('structure edit generell target document', false); ?>
                                                        </option>
                                                        <option value="external" <?php if ($menueditdata['filetarget']=="external" ) echo "selected=\" selected\""; ?>>
                                                            <?php echo returnIntLang('structure edit generell target external', false); ?>
                                                        </option>
                                                    </select></p>
                                            </div>
                                            <div class="col-md-3 input-label target-option target-none target-internal target-document target-external" <?php echo (isset($menueditdata['filetarget']) && $menueditdata['filetarget']=="anchor" )?' style="display: none;" ':''; ?>>
                                                <p>
                                                    <?php echo returnIntLang('structure edit generell target opens in', true); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-3 target-option target-none target-internal target-document target-external" <?php echo (isset($menueditdata['filetarget']) && $menueditdata['filetarget']=="anchor" )?' style="display: none;" ':''; ?>>
                                                <p><select name="targetopens" id="target_opener" onchange="changeTarget(' opener', this.value);" class="form-control">
                                                <option value="_self" <?php if (isset($menueditdata['targetopens']) && $menueditdata['targetopens']=="_self" ) echo "selected=\" selected\""; ?>>
                                                    <?php echo returnIntLang('structure edit generell target self', false); ?>
                                                </option>
                                                <option value="_top" <?php if (isset($menueditdata['targetopens']) && $menueditdata['targetopens']=="_top" ) echo "selected=\" selected\""; ?>>
                                                    <?php echo returnIntLang('structure edit generell target top', false); ?>
                                                </option>
                                                <option value="_parent" <?php if (isset($menueditdata['targetopens']) && $menueditdata['targetopens']=="_parent" ) echo "selected=\" selected\""; ?>>
                                                    <?php echo returnIntLang('structure edit generell target parent', false); ?>
                                                </option>
                                                <option value="_blank" <?php if (isset($menueditdata['targetopens']) && $menueditdata['targetopens']=="_blank" ) echo "selected=\" selected\""; ?>>
                                                    <?php echo returnIntLang('structure edit generell target new', false); ?>
                                                </option>
                                                <option value="_popup" <?php if (isset($menueditdata['targetopens']) && $menueditdata['targetopens']=="popup" ) echo "selected=\" selected\""; ?>>
                                                    <?php echo returnIntLang('structure edit generell target popup', false); ?>
                                                </option>
                                                </select></p>
                                            </div>
                                            <div class="col-md-3 input-label target-option target-anchor" <?php echo (isset($menueditdata['filetarget']) && $menueditdata['filetarget']=="anchor" )?'':' style="display: none;" '; ?>>
                                                <p><?php echo returnIntLang('structure edit anchor target', true); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-3 target-option target-file target-anchor" <?php echo (isset($menueditdata['filetarget']) && $menueditdata['filetarget']=="anchor" )?'':' style="display: none;" '; ?>>
                                                <p><input name="anchortarget" type="text" value="<?php echo (isset($menueditdata[' anchortarget']))?$menueditdata['anchortarget']:''; ?>" class="form-control" /></p>
                                            </div>
                                        </div>
                                        <div class="row opener-option opener-popup target-option" <?php echo (isset($menueditdata['targetopens']) && $menueditdata['targetopens']=="_popup" )?'':' style="display: none;" '; ?>>
                                            <div class="col-md-1 input-label">
                                                <p><?php echo returnIntLang('structure edit popup top', true); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-2">
                                            <p><input type="text" name="filetop" id="filetop" maxlength="4" class="form-control" value="<?php echo intval($menueditdata['filetop']); ?>" placeholder="<?php echo returnIntLang('structure edit popup top', false); ?>" /></p>
                                        </div>
                                        <div class="col-md-1 input-label">
                                            <p>
                                                <?php echo returnIntLang('structure edit popup left', true); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-2">
                                            <p><input type="text" name="fileleft" id="fileleft" maxlength="4" class="form-control" value="<?php echo intval($menueditdata['fileleft']); ?>" placeholder="<?php echo returnIntLang('structure edit popup left', false); ?>" /></p>
                                        </div>
                                        <div class="col-md-1 input-label">
                                            <p>
                                                <?php echo returnIntLang('structure edit popup width', true); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-2">
                                            <p><input type="text" name="filewidth" id="filewidth" maxlength="4" class="form-control" value="<?php echo intval($menueditdata['filewidth']); ?>" placeholder="<?php echo returnIntLang('structure edit popup width', false); ?>" /></p>
                                        </div>
                                        <div class="col-md-1 input-label">
                                            <p>
                                                <?php echo returnIntLang('structure edit popup height', true); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-2">
                                            <p><input type="text" name="fileheight" id="fileheight" maxlength="4" class="form-control" value="<?php echo intval($menueditdata['fileheight']); ?>" placeholder="<?php echo returnIntLang('structure edit popup height', false); ?>" /></p>
                                        </div>
                                    </div>
                                    <div class="row target-option target-none" <?php echo (isset($menueditdata['filetarget']) && $menueditdata['filetarget']!="none" )?' style="display: none;" ':''; ?>>
                                            <div class="col-md-3 input-label">
                                                <p><?php echo returnIntLang('structure edit jscall mouseover', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><input name="jscallmouseover" type="text" value="<?php echo $menueditdata['jsmouseover']; ?>" class="form-control" /></p>
                                    </div>
                                    <div class="col-md-3 input-label opener-option opener-self opener-top opener-parent opener-blank">
                                        <p>
                                            <?php echo returnIntLang('structure edit jscall click', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-3 opener-option opener-self opener-top opener-parent opener-blank">
                                        <p><input name="jscallclick" type="text" value="<?php echo $menueditdata['jsclick']; ?>" class="form-control" /></p>
                                    </div>
                                    <div class="col-md-3 input-label">
                                        <p>
                                            <?php echo returnIntLang('structure edit jscall mouseout', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><input name="jscallmouseout" type="text" value="<?php echo $menueditdata['jsmouseout']; ?>" class="form-control" /></p>
                                    </div>
                                </div>
                                <div class="row target-option target-internal" <?php echo (isset($menueditdata['filetarget']) && $menueditdata['filetarget']=="internal" )?'':' style="display: none;" '; ?>>
                                            <div class="col-md-3 input-label">
                                                <p><span data-toggle="tooltip" class="help" data-placement="bottom" title="" data-original-title="<?php echo returnIntLang('structure edit linkintern help', false); ?>">
                                    <?php echo returnIntLang('structure edit linkintern', true); ?></span></p>
                                </div>
                                <div class="col-md-9">
                                    <p>
                                        <select name="urlintern" id="urlintern" class="form-control">
                                            <option value="0">
                                                <?php echo returnIntLang('structure edit internlink settarget', true); ?>
                                            </option>
                                            <?php echo returnStructureItem('menu', 0, true, 9999, array(intval($menueditdata['internlink_id'])), 'option'); ?>
                                        </select>
                                    </p>
                                </div>
                            </div>
                            <div class="row target-option target-document" <?php echo (isset($menueditdata['filetarget']) && $menueditdata['filetarget']=="document" )?'':' style="display: none;" '; ?>>
                                            <div class="col-md-3 input-label">
                                                <p><span data-toggle="tooltip" class="help" data-placement="bottom" title="" data-original-title="<?php echo returnIntLang(' structure edit linkdocument help', false); ?>">
                                <?php echo returnIntLang('structure edit linkdocument', true); ?></span></p>
                            </div>
                            <div class="col-md-9">
                                <p>
                                    <select name="docintern" id="docintern" class="form-control">
                                        <option value="">
                                            <?php echo returnIntLang('structure edit documentlink settarget', true); ?>
                                        </option>
                                        <?php echo mediaSelect('/media/download/', '/media/download/', array($menueditdata['docintern'])); ?>
                                    </select>
                                </p>
                            </div>
                        </div>
                        <div class="row target-option target-external" <?php echo (isset($menueditdata['filetarget']) && $menueditdata['filetarget']=="external" )?'':' style="display: none;" '; ?>>
                                            <div class="col-md-3 input-label">
                                                <p><span data-toggle="tooltip" class="help" data-placement="bottom" title="" data-original-title="<?php echo returnIntLang('structure edit linkextern help', false); ?>">
                            <?php echo returnIntLang('structure edit linkextern', true); ?></span></p>
                        </div>
                        <div class="col-md-9">
                            <input name="offlink" type="text" value="<?php echo trim($menueditdata['offlink']); ?>" class="form-control" />
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade in" id="specialviewtime">
                    <div class="row">
                        <div class="col-md-12">
                            <p>
                                <?php echo returnIntLang('structure special view description', true); ?>
                            </p>
                            <?php

                                                $showday = intval($menueditdata['weekday']);
                                                for ($sd=6;$sd>=0;$sd--) {
                                                    if ($showday-pow(2,$sd)>=0) {
                                                        $weekdayvalue[($sd+1)] = " checked=\"checked\" ";
                                                        $showday = $showday-(pow(2,$sd));
                                                    }
                                                    else {
                                                        $weekdayvalue[($sd+1)] = "";
                                                    }
                                                };

                                                ?>
                            <p>
                                <?php echo returnIntLang('structure daily based view'); ?><input type="hidden" name="weekday[0]" value="0" />
                            </p>
                            <p>
                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[1]" id="weekday_1" value="1" <?php echo $weekdayvalue[1]; ?> /> <span>
                                        <?php echo returnIntLang('str monday'); ?></span></label>
                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[2]" id="weekday_2" value="2" <?php echo $weekdayvalue[2]; ?> /> <span>
                                        <?php echo returnIntLang('str tuesday'); ?></span></label>
                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[3]" id="weekday_3" value="4" <?php echo $weekdayvalue[3]; ?> /> <span>
                                        <?php echo returnIntLang('str wednesday'); ?></span></label>
                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[4]" id="weekday_4" value="8" <?php echo $weekdayvalue[4]; ?> /> <span>
                                        <?php echo returnIntLang('str thursday'); ?></span></label>
                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[5]" id="weekday_5" value="16" <?php echo $weekdayvalue[5]; ?> /> <span>
                                        <?php echo returnIntLang('str friday'); ?></span></label>
                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[6]" id="weekday_6" value="32" <?php echo $weekdayvalue[6]; ?> /> <span>
                                        <?php echo returnIntLang('str saturday'); ?></span></label>
                                <label class='fancy-checkbox custom-bgcolor-blue'><input type="checkbox" name="weekday[7]" id="weekday_7" value="64" <?php echo $weekdayvalue[7]; ?> /> <span>
                                        <?php echo returnIntLang('str sunday'); ?></span></label>
                            </p>
                            <p>
                                <?php echo returnIntLang('structure time based view'); ?>
                            </p>
                            <div id='timing'>
                                <?php

                                                    $alltimes = trim($menueditdata['showtime']);
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
                                                    
                                                </script>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade in" id="specialviewuser">
                    <div class="row">
                        <div class="col-md-12">
                            <p>
                                <?php echo returnIntLang('structure special view description', true); ?>
                            </p>
                        </div>
                    </div>
                    <?php

                                        if (intval(count($_SESSION['wspvars']['sitelanguages']['shortcut']))>1) {
                                            echo '<div class="row">';
                                            echo '<div class="col-md-3">';
                                            echo '<p>'.returnIntLang('structure denylang').'</p>';
                                            echo '</div>';
                                            echo '<div class="col-md-9">';
                                            echo '<p>';
                                            foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value) {
                                                if (!(is_array($menueditdata['langdescription'])) || !(array_key_exists($value, $menueditdata['langdescription'])) || $menueditdata['langdescription'][$value]==""): $menueditdata['langdescription'][$value] = $menueditdata['description']; endif; 
                                                $checked = '';
                                                if(is_array(unserializeBroken($menueditdata['denylang'])) && in_array($_SESSION['wspvars']['sitelanguages']['shortcut'][$key], unserializeBroken($menueditdata['denylang']))): $checked = " checked='checked' "; endif;
                                                echo '<label class="fancy-checkbox custom-bgcolor-blue"><input type="checkbox" name="denylang[]" value="'.$_SESSION['wspvars']['sitelanguages']['shortcut'][$key].'" '.$checked.' /> <span>'.$_SESSION['wspvars']['sitelanguages']['longname'][$key].'</span></label>';
                                            }
                                            echo '</p>';
                                            echo '</div>';
                                            echo '</div>';
                                        }

                                        ?>
                    <div class="row">
                        <div class="col-md-3 input-label">
                            <p>
                                <?php echo returnIntLang('structure login control'); ?>
                            </p>
                        </div>
                        <div class="col-md-9">
                            <label class="fancy-checkbox custom-bgcolor-blue">
                                <input type="hidden" name="logincontrol" value="0" /><input type="checkbox" name="logincontrol" value="1" <?php if(intval($menueditdata['login'])==1): echo "checked=\" checked\""; endif; ?> /><span>&nbsp;</span>
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 input-label">
                            <p>
                                <?php echo returnIntLang('structure login control user'); ?> <em>beta</em>
                            </p>
                        </div>
                        <div class="col-md-9">
                            <?php

                                                $loginuser_sql = "SELECT `id`, `username` FROM `usercontrol` ORDER BY `username`";
                                                $loginuser_res = doSQL($loginuser_sql);
                                                $logincontrol = unserializeBroken($menueditdata['logincontrol']);
                                                if ($loginuser_res['num']>9) {
                                                    echo '<select name="loginuser[]" size="5" multiple="multiple" class="form-control">';
                                                    foreach ($loginuser_res['set'] AS $lursk => $lursv) {
                                                        echo "<option value=\"".intval($lursv['id'])."\" ";
                                                        if (is_array($logincontrol) && in_array(intval($lursv['id']), $logincontrol)) { echo " selected=\"selected\" "; }
                                                        echo ">".setUTF8(trim($lursv['username']))."</option>";
                                                    }
                                                    echo '</select>';
                                                }
                                                else if ($loginuser_res['num']>0) {
                                                    echo '<input type="hidden" name="loginuser" value="" />';
                                                    foreach ($loginuser_res['set'] AS $lursk => $lursv) {
                                                        
                                                        echo '<label class="fancy-checkbox custom-bgcolor-blue"><input type="checkbox" name="loginuser[]" value="'.intval($lursv['id']).'" '.((is_array($logincontrol) && in_array(intval($lursv['id']), $logincontrol))?' checked="checked" ':'').' /><span>'.setUTF8(trim($lursv['username'])).'</span></label>';

                                                    }
                                                }
                                                
                                                ?>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade in" id="meta">
                    <div class="row">
                        <div class="col-md-12">
                            <?php

                                                $sitedata['sitedesc'] = '';
                                                $sitedata['sitekeys'] = '';
                                                $sitemeta_sql = "SELECT * FROM `wspproperties` WHERE `varname` LIKE 'site%'";
                                                $sitemeta_res = doSQL($sitemeta_sql);
                                                if ($sitemeta_res['num']>0):
                                                    foreach ($sitemeta_res['set'] AS $smk => $smv):
                                                        $sitedata[$smv['varname']] = trim($smv['varvalue']);
                                                    endforeach;
                                                endif;

                                                if (array_key_exists('pagetitle', $menueditdata) && trim($menueditdata['pagetitle'])!=''): $sitedata['sitetitle'] = $menueditdata['pagetitle']; endif;
                                                if (array_key_exists('pagedesc', $menueditdata) && trim($menueditdata['pagedesc'])!=''): $sitedata['sitedesc'] = $menueditdata['pagedesc']; endif;
                                                if (array_key_exists('pagekeys', $menueditdata) && trim($menueditdata['pagekeys'])!=''): $sitedata['sitekeys'] = $menueditdata['pagekeys'].", ".$sitedata['sitekeys']; endif;

                                                ?>
                            <div class="row">
                                <div class="col-md-2">
                                    <?php echo returnIntLang('seo title'); ?>
                                </div>
                                <div class="col-md-10">
                                    <div class="input-group form-group">
                                        <input class="form-control" name="pagetitle" id="pagetitle" type="text" placeholder="<?php echo prepareTextField($sitedata['sitetitle']); ?>" value="<?php echo prepareTextField($menueditdata['pagetitle']); ?>" onkeyup="showPageQuality('pagetitle',80,200);" />
                                        <span id="show_pagetitle_length" class="input-group-addon">
                                            <?php echo strlen(prepareTextField($sitedata['sitetitle'])); ?>/200</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <?php echo returnIntLang('structure edit meta desc', true); ?>
                                </div>
                                <div class="col-md-10">
                                    <div class="input-group form-group">
                                        <textarea name="pagedesc" id="pagedesc" class="form-control noresize autogrow" placeholder="<?php echo prepareTextField(stripslashes($sitedata['sitedesc'])); ?>" onchange="showPageQuality('pagedesc',150,300);"><?php echo prepareTextField(stripslashes($menueditdata['pagedesc'])); ?></textarea>
                                        <span id="show_pagedesc_length" class="input-group-addon">
                                            <?php echo strlen(prepareTextField($sitedata['sitedesc'])); ?>/300</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <?php echo returnIntLang('structure edit meta keywords', true); ?>
                                </div>
                                <div class="col-md-10">
                                    <div class="input-group form-group">
                                        <textarea name="pagekeys" id="pagekeys" cols="20" rows="7" class="form-control noresize autogrow" placeholder="<?php echo prepareTextField($sitedata['sitekeys']); ?>" onchange="showPageQuality('pagekeys',300,1000);"><?php echo prepareTextField(stripslashes($menueditdata['pagekeys'])); ?></textarea>
                                        <span id="show_pagekeys_length" class="input-group-addon">
                                            <?php echo strlen(prepareTextField($sitedata['sitekeys'])); ?>/1000</span>
                                    </div>
                                </div>
                            </div>
                            <script language="JavaScript" type="text/javascript">
                                showPageQuality('pagetitle', 80, 200, <?php echo strlen(prepareTextField(stripslashes($sitedata['sitetitle']))) ?>);
                                showPageQuality('pagedesc', 150, 300, <?php echo strlen(prepareTextField(stripslashes($sitedata['sitedesc']))) ?>);
                                showPageQuality('pagekeys', 200, 1000, <?php echo strlen(prepareTextField(stripslashes($sitedata['sitekeys']))) ?>);

                            </script>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade in" id="menuimage">
                    <div class="row">
                        <div class="col-md-12">
                            <p style="clear: both;">
                                <?php echo returnIntLang('structure edit image desc', true); ?>
                            </p>
                            <div class="row">
                                <div class="col-md-3 input-label">
                                    <p>
                                        <?php echo returnIntLang('structure edit image inactive', true); ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p>
                                        <?php visibleMediaSelect('imageoff', 'menuimg_imageoff', '/media/screen/', '/media/screen/', false, $menueditdata['imageoff'], 150, 0); ?>
                                    </p>
                                </div>
                                <div class="col-md-3 input-label">
                                    <p>
                                        <?php echo returnIntLang('structure edit image active', true); ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p>
                                        <?php visibleMediaSelect('imageakt', 'menuimg_imageakt', '/media/screen/', '/media/screen/', false, $menueditdata['imageakt'], 150, 0); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 input-label">
                                    <p>
                                        <?php echo returnIntLang('structure edit image mouseover', true); ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p>
                                        <?php visibleMediaSelect('imageon', 'menuimg_imageon', '/media/screen/', '/media/screen/', false, $menueditdata['imageon'], 150, 0); ?>
                                    </p>
                                </div>
                                <div class="col-md-3 input-label">
                                    <p>
                                        <?php echo returnIntLang('structure edit image clicked', true); ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p>
                                        <?php visibleMediaSelect('imageclick', 'menuimg_imageclick', '/media/screen/', '/media/screen/', false, $menueditdata['imageclick'], 150, 0); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade in" id="addons">
                    <div class="row">
                        <div class="col-md-12">
                            <p>
                                <?php echo returnIntLang('structure edit addon jsfiles', true); ?>
                            </p>
                            <p>
                                <?php

                                $extrajs = unserializeBroken($menueditdata['addscript']);

                                $jsuse_sql = "SELECT `javascript_id` FROM `r_temp_jscript` WHERE `templates_id` = ".intval($menueditdata['real_templates_id']);
                                $jsuse_res = getResultSQL($jsuse_sql);
                                if (is_array($jsuse_res) && count($jsuse_res)>0) {
                                    $js_sql = "SELECT `id`, `describ` FROM `javascript` WHERE `id` NOT IN (".implode(",", $jsuse_res).")";
                                }
                                else {
                                    $js_sql = "SELECT `id`, `describ` FROM `javascript`";
                                }
                                // find all not-template-used javascript files and show them
                                $js_res = doSQL($js_sql);
                                if ($js_res['num']>0) {
                                    foreach ($js_res['set'] AS $jrsk => $jrsv) {
                                        echo "<label class='fancy-checkbox custom-bgcolor-blue'><input type=\"checkbox\" name=\"usejs[]\" value=\"".intval($jrsv['id'])."\" ";
                                        if (is_array($extrajs) && in_array(intval($jrsv['id']), $extrajs)):
                                            echo " checked=\"checked\" ";
                                        endif;
                                        echo " /> <span>".trim($jrsv['describ'])."</span></label>";
                                    }
                                }
                                else {
                                    echo returnIntLang('structure edit all js-files used in templage');
                                }

                                ?>
                            </p>
                            <p>
                                <?php echo returnIntLang('structure edit addon cssfiles', true); ?>
                            </p>
                            <p>
                                <?php

                                $extracss = unserializeBroken($menueditdata['addcss']);

                                $cssuse_sql = "SELECT `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = ".intval($menueditdata['real_templates_id']);
                                $cssuse_res = getResultSQL($cssuse_sql);
                                if (is_array($cssuse_res) && count($cssuse_res)>0):
                                    $css_sql = "SELECT `id`, `describ` FROM `stylesheets` WHERE `id` NOT IN (".implode(",", $cssuse_res).")";
                                else:
                                    $css_sql = "SELECT `id`, `describ` FROM `stylesheets`";
                                endif;    
                                // selecting 
                                $css_res = doSQL($css_sql);
                                if ($css_res['num']>0) {
                                    foreach ($css_res['set'] AS $crsk => $crsv) {
                                        echo "<label class='fancy-checkbox custom-bgcolor-blue'><input type=\"checkbox\" name=\"usecss[]\" value=\"".intval($crsv['id'])."\" ";
                                        if (is_array($extracss) && in_array(intval($crsv['id']), $extracss)) {
                                            echo " checked='checked' ";
                                        }
                                        echo " /> <span>".trim($crsv['describ'])."</span></label> ";
                                    }
                                }
                                else {
                                    echo returnIntLang('structure edit all css-files used in template');
                                }

                                ?>
                            </p>
                            <p>
                                <?php echo returnIntLang('structure edit addon cssclass', false); ?>
                            </p>
                            <p><input type="text" name="useclass" value="<?php echo $menueditdata['addclass']; ?>" class="form-control" placeholder="<?php echo returnIntLang('structure edit addon cssclass', false); ?>" /></p>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>
</div>
</div>
<div class="row">
    <div class="col-md-12">
        <input name="backjump" id="backjump" type="hidden" value="" />
        <input name="op" id="ophidden" type="hidden" value="" />
        <input name="mid" type="hidden" value="<?php echo intval($mid); ?>" />
        <p>
            <a onclick="saveMenuEdit(false);" class="btn btn-success"><?php echo returnIntLang('str save', true); ?></a> 
            <a onclick="saveMenuEdit(true);" class="btn btn-primary"><?php echo returnIntLang('btn save and back', true); ?></a> 
            <a href="structure.php" class="btn btn-warning"><?php echo returnIntLang('str back', true); ?></a>
            <a href="removeMenu();" class="btn btn-danger"><?php echo returnIntLang('str delete', true); ?></a>
        </p>
    </div>
</div>
</form>
</div>
</div>
<script type="text/javascript">
    
    function checkForUsedFilename(newName) {
        $.post("xajax/ajax.checkforusedfilename.php", {
                'newname': newName
            })
            .done(function(data) {
                if (data != '') {
                    var splitData = data.split("#;#");
                    if (splitData.length == 2) {
                        document.getElementById('filename').value = splitData[1];
                    } else {
                        alert(data);
                        document.getElementById('filename').focus();
                    }
                }
            });
    }

    function checkForUsedShortcut(newShortcut) {
        $.post("xajax/ajax.checkforusedshortcut.php", {
                'newshortcut': newShortcut
            })
            .done(function(data) {
                if (data != '') {
                    var splitData = data.split("#;#");
                    if (splitData.length == 2) {
                        console.log(splitData[0]);
                        document.getElementById('shortcut').value = splitData[1];
                    } else {
                        alert(data);
                        document.getElementById('shortcut').focus();
                    }
                } else {
                    $('.showshortcut').text(newShortcut.toUpperCase());
                }
            });
    }

    function saveMenuEdit(backjump) {
        if (document.getElementById('filename').value != '') {
            if (backjump) {
                document.getElementById('backjump').value = 'back';
            }
            document.getElementById('cfc').value = 0;
            document.getElementById('ophidden').value = 'save';
            document.getElementById('frmmenudetail').submit();
        } else {
            alert('<?php echo returnIntLang('menuedit set filename', false); ?>');
            document.getElementById('filename').value = '';
        }
    }

    function submitTimeRemove(timeid) {
        $('#time-' + timeid).toggle('fade', 500, function() {
            $('#time-' + timeid).remove();
        });
    }

    function changeTarget(tDest, tTarget) {
        tTarget = tTarget.replace("_", "");
        tDest = tDest.replace(" ", "");
        $('.' + tDest + '-option').hide();
        $('.' + tDest + '-' + tTarget).show();
    }

    function selectDynTable(tVal) {
        var htmltext = '';
        var htmldata = '';
        if (tVal.trim() != '') {
            $.post("xajax/ajax.checkfordyntable.php", {
                'table': tVal
            }).done(function(data) {
                if (data != '') {

                    console.log(data);

                    var splitData = JSON.parse(data);
                    if (splitData.length > 0) {
                        htmltext = '<option value=""><?php echo returnIntLang('hint choose', false); ?></option>';
                    }
                    while (splitData.length > 0) {
                        htmldata = splitData.shift();
                        htmltext += '<option value="' + htmldata + '"><?php echo returnIntLang('str db field', false); ?> ' + htmldata + '</option>';
                    }
                } else {
                    htmltext = '<option value=""><?php echo returnIntLang('structure dynmenu table error', false); ?></option>';
                }
                $('#choose_dyntable_filename').html(htmltext);
                $('#choose_dyntable_description').html(htmltext);
                $('.choose_dyntable_where').html(htmltext);
                $('#choose_dyntable_order').html(htmltext);
            });
        } else {
            htmltext = '<option value=""><?php echo returnIntLang('structure dynmenu choose table', false); ?></option>';
            $('#choose_dyntable_filename').html(htmltext);
            $('#choose_dyntable_description').html(htmltext);
            $('.choose_dyntable_where').html(htmltext);
            $('#choose_dyntable_order').html(htmltext);
        }
    }

    function checkEditable(cVal) {
        if (cVal == 9) {
            $('ul.nav.nav-tabs.pull-left').find('li').removeClass('active');
            $('#dynamiccontent_tab').show().addClass('active');
            $('div.tab-pane.fade.in').removeClass('active');
            $('#dynamiccontent').addClass('active');
        } else {
            $('ul.nav.nav-tabs.pull-left').find('li').removeClass('active');
            $('#dynamiccontent_tab').hide();
            $('#behaviour_tab').addClass('active');
            $('div.tab-pane.fade.in').removeClass('active');
            $('#behaviour').addClass('active');
        }
    }

    function addDynWhere(comboVal, whereData, whereoptData, wherevalData, wherecombineData) {
        var temp = document.getElementById('choose_dyntable_where_template'); 
        if (comboVal!='') {
            var doVal = true;
            if (whereData!='') {
                $('#' + whereData).nextUntil(temp, '.row').each(function(e){
                    if ($(this).find('.choose_dyntable_wherecombine').val()=='') {
                        doVal = false;
                    }
                });
            }
            if (doVal) {
                var dynID = Math.floor(Math.random() * 10000) + 1;
                var htmldata = $('#choose_dyntable_where_template').html();
                $('#choose_dyntable_where_template').before('<div class="row" id="dynwhere-' + dynID + '">' + htmldata + '</div>');
                $('#dynwhere-' + dynID).find('.choose_dyntable_where').val(whereData);
                $('#dynwhere-' + dynID).find('.choose_dyntable_whereopt').val(whereoptData);
                $('#dynwhere-' + dynID).find('.choose_dyntable_whereval').val(wherevalData);
                $('#dynwhere-' + dynID).find('.choose_dyntable_wherecombine').val(wherecombineData);
                $('#dynwhere-' + dynID).find('.choose_dyntable_wherecombine').attr('rel','dynwhere-' + dynID);
            }
        } else {
            if (confirm('<?php echo returnIntLang('structure dynmenu remove following WHERE options', false); ?>')) {
                $('#' + whereData).nextUntil(temp, '.row').remove();
            }
        }
    }

<?php
        
$addnew = '';
if (isset($menueditdata['pluginconfig']['where']) && is_array($menueditdata['pluginconfig']['where'])) {
    foreach ($menueditdata['pluginconfig']['where'] AS $wk => $wv) {
        if (trim($wv)!='') { ?>
        addDynWhere('new',
            '<?php echo $menueditdata['pluginconfig']['where'][$wk]; ?>',
            '<?php echo (isset($menueditdata['pluginconfig']['whereopt'][$wk])?$menueditdata['pluginconfig']['whereopt'][$wk]:'='); ?>',
            '<?php echo addSlashes(prepareTextField($menueditdata['pluginconfig']['whereval'][$wk])); ?>',
            '<?php echo ($wk<(count($menueditdata['pluginconfig']['where'])-1))?$menueditdata['pluginconfig']['wherecombine'][$wk]:''; ?>'
        );
        <?php
            $addnew = trim($menueditdata['pluginconfig']['wherecombine'][$wk]);
        }
    }
} else {
    echo "\n\t\t\taddDynWhere('new','','=','','');\n";
}
if ($addnew!='') {
    echo "\n\t\t\taddDynWhere('new','','=','','');\n";
}
            
?>

</script>
</div>

<?php endif; ?>

<script language="JavaScript" type="text/javascript">
    $(document).ready(function() {
        $('.singleselect').multiselect();
        $('.inline-datepicker').datepicker({
            todayHighlight: true,
            format: 'dd.mm.yyyy',
            language: 'de',
        });

    });

</script>

<?php require ("./data/include/footer.inc.php"); ?>
