<?php
/**
 * manage sitestructure
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-11-27
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'sitestructure';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-sitemap',returnIntLang('menu content'),returnIntLang('menu content structure'));
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['addpagecss'] = array(
    'jquery.nestable.css',
    'bootstrap-multiselect.css'
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'jquery/jquery.nestable.js',
    'bootstrap/bootstrap-multiselect.js'
    );
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$datatable = 'menu'; // setting up the menu datatable
/* page specific funcs and actions */

// init information display mode
if (!(isset($_SESSION['wspvars']['sdm']))): $_SESSION['wspvars']['sdm'] = 0; endif;
if (isset($_REQUEST['sdm'])): $_SESSION['wspvars']['sdm'] = intval($_REQUEST['sdm']); endif;
// init information show visibility mode
if (!(isset($_SESSION['wspvars']['ssh']))): $_SESSION['wspvars']['ssh'] = 2; endif;
if (isset($_REQUEST['ssh'])): $_SESSION['wspvars']['ssh'] = intval($_REQUEST['ssh']); endif;

if (!(function_exists('createNewMP'))) {
    function createNewMP($dataTable='menu', $newName='', $newFileName='', $topMID=0, $copyMID=0, $templateID=0, $multiCreate=false, $copyContent=false) {
        // set some basics
        $editable = 1;
        $breaktree = 0;
        $visibility = 1;
        if (intval($copyMID)>0) {
            $data_sql = "SELECT * FROM `".$dataTable."` WHERE `mid` = ".intval($copyMID);
            $data_res = doSQL($data_sql);
            if ($data_res['num']>0) {
                $newName = $data_res['set'][0]['description']." COPY ".time();
                $newFileName = $data_res['set'][0]['filename']."-".time();
                $templateID = intval($data_res['set'][0]['templates_id']);
                $editable = intval($data_res['set'][0]['editable']);
                $breaktree = intval($data_res['set'][0]['breaktree']);
                $visibility = intval($data_res['set'][0]['visibility']);
            }
        }
        if (trim($newName)=='') { $newName = md5(time().rand()); }
        if (trim($newFileName)=='') { $newFileName = urlText(trim($newName)); }
        $namecheck_sql = "SELECT `filename` FROM `".$dataTable."` WHERE `connected` = ".intval($topMID)." AND `filename` = '".escapeSQL(trim($newFileName))."' AND `trash` = 0";
        $namecheck_res = doSQL($namecheck_sql);
        if ($namecheck_res['num']>0): $newFileName = $newFileName."-".time(); endif;
        // check for existing index-file - else, set THIS new file to index 
        $indexcheck_sql = "SELECT `isindex` FROM `".$dataTable."` WHERE `connected` = ".intval($topMID)." AND `isindex` = 1";
        $indexcheck_res = doResultSQL($indexcheck_sql);
        $newindex = false; if ($indexcheck_res===false): $newindex = true; endif;
        // BUT unset index page if defined in editor prefs
        if ($_SESSION['wspvars']['noautoindex']==1): $newindex = false; endif;
        // check for new level
        $levelcheck_sql = "SELECT `level` FROM `".$dataTable."` WHERE `mid` = ".intval($topMID);
        $levelcheck_res = doResultSQL($levelcheck_sql);
        $newlevel = 1; if ($levelcheck_res) { $newlevel = intval($levelcheck_res)+1; } 
        // check for new position
        $poscheck_sql = "SELECT MAX(`position`) AS `position` FROM `".$dataTable."` WHERE `connected` = ".intval($topMID)." ORDER BY `position` DESC" ;
        $poscheck_res = doResultSQL($poscheck_sql);
        $newpos = 1; if (intval($poscheck_res)>0) { $newpos = intval($poscheck_res)+1; }
        $sql = "INSERT INTO `".$dataTable."` SET 
            `level` = ".intval($newlevel).", 
            `connected` = ".intval($topMID).",
            `editable` = ".$editable.", 
            `breaktree` = ".$breaktree.",
            `position` = ".$newpos.",
            `visibility` = ".$visibility.",
            `description` = '".escapeSQL(trim($newName))."',
            `filename` = '".escapeSQL($newFileName)."',
            `templates_id` = ".intval($templateID).",
            `contentchanged` = 4,
            `changetime` = ".time().",
            `isindex` = ".intval($newindex);
        $res = doSQL($sql);
        if ($res['aff']==1) {
            $_SESSION['wspvars']['editmenuid'] = intval($res['inf']);
            if ($_SESSION['wspvars']['editmenuid']>0 && $multiCreate!==true) {
                addWSPMsg('resultmsg', returnIntLang('structure new menupoint created', true));
                header('location: structureedit.php');
                die();
            }
            else {
                return intval($res['inf']);
            }
        }
        else {
            addWSPMsg('errormsg', returnIntLang('structure error creating new menupoint'));
            if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg('errormsg', var_export($res, true));
            }
            return false;
        }
    }
}

if (!(function_exists('cloneMP'))) {
    function cloneMP($menuTable='menu', $cloneMID = 0, $newParent = 0) {
        $newMID = createNewMP('menu', '', '', $newParent, $cloneMID, 0, true, true);
        $cloneSUB = getResultSQL("SELECT `mid` FROM `".$menuTable."` WHERE `connected` = ".$cloneMID." AND `trash` = 0");
        $_SESSION['wspvars']['opentree'][] = $newMID;
        if (is_array($cloneSUB) && count($cloneSUB)>0) {
            foreach ($cloneSUB AS $csubv) {
                cloneMP($menuTable, $csubv, $newMID);
            }
        }
    }
}

if (!(function_exists('removeMP'))) {
    function removeMP($dataTable='menu', $removeMID = 0) {
        if (intval($removeMID)>0) {
            // find all subs down to last 
            $sub_sql = "SELECT `mid` FROM `".$dataTable."` WHERE `connected` = ".intval($removeMID);
            $sub_res = getResultSQL($sub_sql);
            if (is_array($sub_res) && count($sub_res)>0) {
                foreach ($sub_res AS $srk => $srv) {
                    removeMP($dataTable, $srv);
                }
            }
            // find all connected contents and set them to trash = 1
            $content_sql = "UPDATE `content` SET `trash` = 1, `uid` = ".intval($_SESSION['wspvars']['userid'])." WHERE `mid` = ".intval($removeMID);
            doSQL($content_sql);
            // find all connected contents and set them to trash = 1
            $menu_sql = "UPDATE `".$dataTable."` SET `trash` = 1, `isindex` = 0 WHERE `mid` = ".intval($removeMID);
            doSQL($menu_sql);
        }
    }
}

if (isset($_POST['structurefilter'])) {
    if (trim($_POST['structurefilter'])=='') {
        $_SESSION['wspvars']['structurefilter'] = '';
    }
    else if (strlen(trim($_POST['structurefilter']))>2) {
        $_SESSION['wspvars']['structurefilter'] = trim($_POST['structurefilter']);
        $_SESSION['wspvars']['opentree'] = array();
    }
    else {
        $_SESSION['wspvars']['structurefilter'] = '';
        addWSPMsg('noticemsg', returnIntLang('structure search to short'));
    }
}

if (isset($_POST['op']) && $_POST['op']=='new' && isset($_POST['newmenuitem']) && trim($_POST['newmenuitem'])!="") {
    createNewMP('menu', $_POST['newmenuitem'], '', $_POST['subpointfrom'], 0, $_POST['template'], false, false);
    }
else if (isset($_POST['op']) && $_POST['op']=='new' && trim($_POST['newmenuitemlist'])!="") {
	// create new list of menupoints
	$newmplist = array();
	$newmplisttmp = explode("<br />", nl2br($_POST['newmenuitemlist']));
	if (is_array($newmplisttmp)):
		foreach ($newmplisttmp AS $nk => $nv):
			if (trim($nv)!='') $newmplist[] = trim($nv);
		endforeach;
	endif;
	if (count($newmplist)>0):
        foreach ($newmplist AS $npk => $npv) {
            createNewMP('menu', $npv, '', $_POST['subpointfrom'], 0, $_POST['template'], true, false);
        }
        $_SESSION['wspvars']['resultmsg'] = "<p>".returnIntLang('structure success creating new menupointlist', true)."</p>";
        $_SESSION['wspvars']['opentree'][] = intval($_POST['subpointfrom']);
	else:	
		$_SESSION['wspvars']['errormsg'] = "<p>".returnIntLang('structure error creating new menupointlist', true)."</p>";
	endif;
}
else if (isset($_POST['op']) && $_POST['op']=='clone' && intval($_POST['mid'])>0) {
    
    // setup selected MID 
    $mid = intval($_POST['mid']);
    // get MAIN parent MID
    $parentMID = intval(returnIDTree($mid)[0]);
    // init cloning with first level
    cloneMP('menu', $mid, $parentMID);
    /*
    if ($_SESSION['wspvars']['usertype']!="admin"):
		$rights_sql = "SELECT `rights`, `idrights` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
		$rights_res = mysql_query($rights_sql);
		if ($rights_res):
			$rights_num = mysql_num_rows($rights_res);
		endif;
		if ($rights_num>0):
			$rights = unserialize(mysql_result($rights_res, 0, 0));
			$idrights = unserialize(mysql_result($rights_res, 0, 1));
			if ($rights['contents']>1):
				if (is_array($idrights['contents'])):
//					$clonarray = explode(",",$_SESSION['clone']['clonemids']);
					array_merge($idrights['contents'],explode(",",$_SESSION['clone']['clonemids']));
//					$idrights['contents'][]
				else:
					$idrights['contents'] =explode(",",$_SESSION['clone']['clonemids']);
				endif;
			endif;
			if ($rights['publisher']>1):
				if (is_array($idrights['publisher'])):
//					$idrights['publisher'][] = explode(",",$_SESSION['clone']['clonemids']);
					array_merge($idrights['publisher'],explode(",",$_SESSION['clone']['clonemids']));
				else:
					$idrights['publisher'] = explode(",",$_SESSION['clone']['clonemids']);
				endif;
			endif;
			$sql = "UPDATE `restrictions` SET `idrights` = '".serialize($idrights)."' WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
			mysql_query($sql);
		endif;
	endif;
    */
}
else if (isset($_POST['op']) && $_POST['op']=='multiedit' && is_array($_POST['multiedit']) && count($_POST['multiedit'])>1) {
    $_SESSION['wspvars']['editmenuid'] = array();
    foreach ($_POST['multiedit'] AS $pmek => $pmev) {
        $_SESSION['wspvars']['editmenuid'][] = intval($pmek);
    }
    header('location: structuremultiedit.php');
    die();
}
else if (isset($_POST['op']) && $_POST['op']=='multiedit' && is_array($_POST['multiedit']) && count($_POST['multiedit'])==1) {
    foreach ($_POST['multiedit'] AS $pmek => $pmev) {
        $_SESSION['wspvars']['editmenuid'] = intval($pmek);
    }
    header('location: structureedit.php');
    die();
}
else if (isset($_POST['op']) && $_POST['op']=='multidelete' && is_array($_POST['multiedit']) && count($_POST['multiedit'])>0) {
    foreach ($_POST['multiedit'] AS $pmek => $pmev) {
        removeMP('menu', $pmek);
    }
    header('location: structure.php');
    die();
}

// cleaning up structure before output
$cleanstructure_res = doSQL("SELECT * FROM `".$datatable."` WHERE `trash` = 0 ORDER BY `level`, `position` ASC");
if ($cleanstructure_res['num']>0) {
    foreach ($cleanstructure_res['set'] AS $csk => $csv) {
        if (intval($csv['connected'])>0) {
            $con = intval($csv['connected']);
            $nlv = 1;
            while($con>0) {
                $nlv++;
                $con = doResultSQL("SELECT `connected` FROM `".$datatable."` WHERE `mid` = ".intval($con)." AND `trash` = 0");
                if ($nlv>10) { $con = 0; }
            }
            doSQL("UPDATE `".$datatable."` SET `level` = ".$nlv." WHERE `mid` = ".intval($csv['mid']));
        }
        else {
            doSQL("UPDATE `".$datatable."` SET `level` = '1' WHERE `mid` = ".intval($csv['mid']));
        }
    }
}

// get information about opened structure
if (isset($_POST['openmid']) && intval($_POST['openmid']) > 0):
	returnReverseStructure(intval($_POST['openmid']));
	$openpath = $midpath[0];
elseif (isset($_SESSION['pathmid']) && intval($_SESSION['pathmid']) > 0):
	returnReverseStructure(intval($_SESSION['pathmid']));
	$openpath = $midpath[1];
elseif (isset($_SESSION['opencontent']) && intval($_SESSION['opencontent']) > 0):
	$oc_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".$_SESSION['opencontent'];
	$oc_res = mysql_query($oc_sql);
	$oc_num = mysql_num_rows($oc_res);
	if ($oc_num>0):
		returnReverseStructure(mysql_db_name($oc_res, 0, 'mid'));
		$openpath = $midpath[1];
	endif;
else:
	$openpath = array();
endif;

// head der datei
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
                <h1 class="page-title"><?php echo returnIntLang('structure headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('structure info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <div class="row">
                <div class="col-sm-<?php echo ($_SESSION['wspvars']['rights']['sitestructure']==1 || $_SESSION['wspvars']['rights']['sitestructure']==7)?'8':'12'; ?> col-md-<?php echo ($_SESSION['wspvars']['rights']['sitestructure']==1 || $_SESSION['wspvars']['rights']['sitestructure']==7)?'8':'12'; ?> col-lg-<?php echo ($_SESSION['wspvars']['rights']['sitestructure']==1 || $_SESSION['wspvars']['rights']['sitestructure']==7)?'8':'12'; ?>">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php if ($_SESSION['wspvars']['rights']['sitestructure']==1): 
                                echo returnIntLang('structure actualstructure', true); 
                            else: 
                                echo returnIntLang('structure restrictedstructure', true); 
                            endif; ?></h3>
                            <div class="right">
                                <div class="dropdown">
                                    <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-bars"></i> </a>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a href='?sdm=0'><i class="fa fa-info-circle"></i><?php echo returnIntLang('structure show menu names', true); ?></a></li>
                                        <li><a href='?sdm=1'><i class="fa fa-code"></i><?php echo returnIntLang('structure show menu file names', true); ?></a></li>
                                        <li><a href='?sdm=2'><i class="fa fa-hashtag"></i><?php echo returnIntLang('structure show menu ids', true); ?></a></li>
                                        <li><a href='?sdm=3'><i class="fa fa-database"></i><?php echo returnIntLang('structure show everything', true); ?></a></li>
                                        <li class="divider"></li>
                                        <li><a id="btn-expand"><i class="fa fa-plus-square"></i><?php echo returnIntLang('structure expand structure', true); ?></a></li>
                                        <li><a id="btn-collapse"><i class="fa fa-minus-square"></i><?php echo returnIntLang('structure collapse structure', true); ?></a></li>
                                        <li class="divider"></li>
                                        <?php if ($_SESSION['wspvars']['ssh']==2) { ?>
                                            <li><a href='?ssh=0'><i class="fas fa-bookmark"></i><?php echo returnIntLang('structure show all', true); ?></a></li>
                                            <li><a href='?ssh=1'><i class="fas fa-bookmark fa-disabled"></i><?php echo returnIntLang('structure hide invisible', true); ?></a></li>
                                        <?php }
                                        else if ($_SESSION['wspvars']['ssh']==1) { ?>
                                            <li><a href='?ssh=0'><i class="fas fa-bookmark"></i><?php echo returnIntLang('structure show all', true); ?></a></li>
                                            <li><a href='?ssh=2'><i class="fas fa-bookmark fa-disabled"></i><?php echo returnIntLang('structure show only invisible', true); ?></a></li>
                                        <?php }
                                        else { ?>
                                            <li><a href='?ssh=2'><i class="fas fa-sign-in-alt"></i><?php echo returnIntLang('structure hide forwarding', true); ?></a></li>
                                            <li><a href='?ssh=1'><i class="fas fa-bookmark"></i><?php echo returnIntLang('structure hide invisible', true); ?></a></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                            <?php 
                            // block to define workspace language
                            
                            if ((array_key_exists('workspacelang', $_SESSION['wspvars']) && $_SESSION['wspvars']['workspacelang']=="") || (!(array_key_exists('workspacelang', $_SESSION['wspvars'])))) {
                                $_SESSION['wspvars']['workspacelang'] = $_SESSION['wspvars']['sitelanguages']['shortcut'][0];
                            }
                            if (isset($_REQUEST['wsl']) && trim($_REQUEST['wsl'])!="") {
                                $_SESSION['wspvars']['workspacelang'] = trim($_REQUEST['wsl']);
                            }
                            
                            if (intval(count($_SESSION['wspvars']['sitelanguages']['shortcut']))>1) {
                                ?>
                                <div class="right">
                                    <div class="dropdown">
                                        <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-globe"></i> <?php echo strtoupper($_SESSION['wspvars']['workspacelang']); ?> </a>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <?php
                                            
                                            foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value) {
                                                echo "<li><a href='?wsl=".$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]."'>";
                                                echo "<i class=\"fa ";
                                                echo ($_SESSION['wspvars']['workspacelang']==$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]) ? 'fa-check-circle' : 'fa-globe';
                                                echo "\"></i>".$_SESSION['wspvars']['sitelanguages']['longname'][$key]."</a></li>";
                                            }
                                            
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <form method="post">
                            <div class="panel-option">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" placeholder="<?php echo returnIntLang('structure filter', false); ?>" name="structurefilter" id="structurefilter" value="<?php if(isset($_SESSION['wspvars']['structurefilter']) && trim($_SESSION['wspvars']['structurefilter'])!='') { echo prepareTextField($_SESSION['wspvars']['structurefilter']); } ?>" />
                                </div>
                            </div>
                        </form>
                        <div class="panel-body" >
                            <?php
                            
                            $menuids = array();
                            $menuallowed = array();
                            $allowedstructure = array();
                            $topmid = 0;
                            
                            if ($_SESSION['wspvars']['rights']['sitestructure']==4) {
                                if (count($_SESSION['wspvars']['rights']['sitestructure_array'])>0) {
                                    $menuids = $_SESSION['wspvars']['rights']['sitestructure_array'];
                                    $menuallowed = $_SESSION['wspvars']['rights']['sitestructure_array'];
                                }
                                else {
                                    $menuids = @explode(",", $_SESSION['wspvars']['rights']['sitestructure_id']);
                                }
                            }
                            else if ($_SESSION['wspvars']['rights']['sitestructure']==7) {
                                if (count($_SESSION['wspvars']['rights']['sitestructure_array'])>0) {
                                    // Aenderungen, damit User mit Rechten auf einen MP mit UnterMP die richtigen MP angezeigt bekommen			
                                    $_SESSION['clone']['midlist'] = array($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
                                    $_SESSION['mylist'] = returnIDRoot($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
                                    $_SESSION['mylistid'] = $_SESSION['wspvars']['rights']['sitestructure_array'][0];
                                    $menuids = $_SESSION['clone']['midlist'];
                                    $menuallowed = $_SESSION['mylist'];
                                    $_SESSION['structuremidlist'] = $_SESSION['mylist'];
                                    array_unshift($_SESSION['structuremidlist'], $_SESSION['mylistid']);
                                }
                                else {
                                    $menuids = @explode(",", $_SESSION['wspvars']['rights']['sitestructure_id']);
                                }
                            }
                            else {
                                $menuids = array();
                                $_SESSION['structuremidlist'] = array();
                            }
                            
                            // built array with structure based on user rights
                            if (isset($menuallowed) && is_array($menuallowed)) {
                                $allowedstructure = array();
                                foreach ($menuallowed AS $makey => $mavalue):
                                    $allowedstructure = array_merge($allowedstructure, returnIDTree($mavalue));
                                endforeach;
                                $allowedstructure = array_unique($allowedstructure);
                                $menuallowed = $allowedstructure;
                            }
                            else {
                                $menuallowed = array();
                            }
                                                        
                            if ($_SESSION['wspvars']['rights']['sitestructure']==7 && is_array($_SESSION['structuremidlist'])) {
                                if (!(in_array($openpath, $_SESSION['structuremidlist']))):
                                    $openpath = array();
                                endif;
                                $topm_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($_SESSION['structuremidlist'][0]);
                                $topm_res = mysql_query($topm_sql);
                                $topm_num = 0; if ($topm_res): $topm_num = mysql_num_rows($topm_res); endif;
                                if ($topm_num>0): $topmid = intval(mysql_result($topm_res,0,'connected')); endif;
                            }

                            if ($_SESSION['wspvars']['rights']['sitestructure']==4 && is_array($menuallowed)) {
                                if (!(in_array($openpath, $menuallowed))) {
                                    $openpath = array();
                                }
                            }
                            
                            ?>
                            <form id="menuedit_form" method="post" action="structureedit.php">
                                <input type="hidden" id="menuedit_mid" name="mid" value="0" />
                                <input type="hidden" id="menuedit_op" name="op" value="edit" />
                            </form>
                            <form id="menuclone_form" method="post">
                                <input type="hidden" id="menuclone_mid" name="mid" value="0" />
                                <input type="hidden" name="op" value="clone" />
                            </form>
                            <form id="multiedit_form" method="post">
                                <input type="hidden" name="op" id="multiedit_action" value="multiedit" />
                                <div class="dd structure" id="structure">
                                    <ol class="dd-list">
                                        <?php

                                        $mid_res = doSQL("SELECT `mid` FROM `".$datatable."` WHERE `connected` = 0 ORDER BY `position` ASC");
                                        foreach ($mid_res['set'] AS $mk => $mv):
                                            echo returnStructureItem($datatable, $mv['mid'], true, 9999, $openpath, 'list', array('visible'=>$_SESSION['wspvars']['ssh']));
                                        endforeach;

                                        ?>
                                    </ol>
                                </div>
                            </form>
                            <p>&nbsp;</p>
                            <p>
                                <a onclick="doMultiEdit();" class="btn btn-primary"><?php echo returnIntLang('structure edit marked', false); ?></a>
                                <a onclick="confirmMultiDelete();" class="btn btn-danger"><?php echo returnIntLang('structure delete marked', false); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
                <?php if ($_SESSION['wspvars']['rights']['sitestructure']==1 || $_SESSION['wspvars']['rights']['sitestructure']==7): ?>
                    <div class="col-sm-4 col-md-4 col-lg-4">
                        <?php require ("./data/panels/structure.addpage.inc.php"); ?>
                    </div>
                    <?php if (isset($_SESSION['wspvars']['disablehelp']) && intval($_SESSION['wspvars']['disablehelp'])==1) {} else { ?>
                        <div class="col-sm-4 col-md-4 col-lg-4">
                            <?php require ("./data/panels/structure.icondesc.inc.php"); ?>
                        </div>
                    <?php } ?>
                <?php endif; ?>
            </div>
            <?php if ($_SESSION['wspvars']['rights']['sitestructure']!=1 && $_SESSION['wspvars']['rights']['sitestructure']!=7) { ?>
                
                <div class="row">
                    <div class="col-md-12">
                        <?php require ("./data/panels/structure.icondesc.inc.php"); ?>
                    </div>
                </div>
            
            <?php } ?>
        </div>
    </div>
    <!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->

<script language="JavaScript" type="text/javascript">

    function doMultiEdit() {
        var ccheck = $('.dd-action.custom-marker input:checkbox[type=checkbox]:checked').length;
        if (ccheck>0) {
            $('#multiedit_action').val('multiedit');
            $('#multiedit_form').submit();
        }
    } // 2019-03-14
    function confirmMultiDelete() { 
        var ccheck = $('.dd-action.custom-marker input:checkbox[type=checkbox]:checked').length;
        if (ccheck>0) {
            if (confirm('<?php echo returnIntLang('structure really multidelete menupoint1', false); ?> ' + ccheck + ' <?php echo returnIntLang('structure really multidelete menupoint2', false); ?>')) {
                $('#multiedit_action').val('multidelete');
                $('#multiedit_form').submit();
            }
        }
    } // 2019-03-14
    function doShowHide(mid, vis) {
        $.post("xajax/ajax.showhidevisibility.php", { 'mid': mid, 'vis': vis }).done(function(data) {
            if (data==1) {
                console.log(mid + ':' + vis + ':' + data);
                if (vis==1) {
                    $('#structure-' + mid).children('.dd-action.custom-action').children('.dropdown').children('a').children('i').removeClass('fa-disabled');
                    $('#structure-' + mid).children('.dd-action.custom-action').children('.dropdown').children('ul').find('.showhide').hide();
                    $('#structure-' + mid).children('.dd-action.custom-action').children('.dropdown').children('ul').find('.hideshow').show();
                } else {
                    $('#structure-' + mid).children('.dd-action.custom-action').children('.dropdown').children('a').children('i').addClass('fa-disabled');
                    $('#structure-' + mid).children('.dd-action.custom-action').children('.dropdown').children('ul').find('.hideshow').hide();
                    $('#structure-' + mid).children('.dd-action.custom-action').children('.dropdown').children('ul').find('.showhide').show();
                }
            }
        });
    } // 2019-03-14
    function doEdit(mid) {
        $('#menuedit_mid').val(mid);
        $('#menuedit_op').val('edit');
        $('#menuedit_form').submit();
    }  // 2019-03-14
    function doDelete(mid) {
        if (confirm('<?php echo returnIntLang('structure really delete menupoint1', false); ?> <?php echo returnIntLang('structure really delete menupoint2', false); ?>')) {
            $('#multiedit_' + mid).prop('checked', true);
            $('#multiedit_action').val('multidelete');
            $('#multiedit_form').submit();
        }
    } // 2019-03-14
    function doClone(mid) {
        if (parseInt(mid)>0) {
            $('#menuclone_mid').val(mid);
            $('#menuclone_form').submit();
        }
    } // 2019-03-14
    function doSub(mid) {
        $('#subpointfrom').val(mid);
        $('#newmenuitem').focus();
    } // 2019-03-14
    
    $(document).ready(function() { 
    
        $('#structure').nestable({
            maxDepth: 99,
        }).on('change', function() {
            $.post("xajax/ajax.setstructure.php", { 'structure': window.JSON.stringify($('#structure').nestable('serialize'))})
                .done(function(data) {
                if ($.trim(data)!='') {
                    console.log(data);
                }
            });
        }).on('expand', function() {
            $.post("xajax/ajax.opentree.php", { 'mid': $('#structure').nestable('affected'), 'action': 'open' });
        }).on('collapse', function() {
            $.post("xajax/ajax.opentree.php", { 'mid': $('#structure').nestable('affected'), 'action': 'close' });
        });
        $('#structure').nestable('collapseAll');
<?php
    
    if (isset($_SESSION['wspvars']['opentree']) && is_array($_SESSION['wspvars']['opentree']) && count($_SESSION['wspvars']['opentree'])>0) {
        foreach ($_SESSION['wspvars']['opentree'] AS $ok => $ov) {
            echo "\t\t\$('#structure-".$ov."').removeClass('dd-collapsed');\n";
        }
    }
    
?>
        // button actions
        $('#btn-expand').on('click', function() { 
            $('#structure').nestable('expandAll'); 
            $.post("xajax/ajax.opentree.php", { 'mid': 0, 'action': 'openall' });
        });
        $('#btn-collapse').on('click', function() { 
            $('#structure').nestable('collapseAll'); 
            $.post("xajax/ajax.opentree.php", { 'mid': 0, 'action': 'closeall' });
        });
    
        $('.singleselect').multiselect();
        $('.searchselect').multiselect({
            maxHeight: 300,
            enableFiltering: true,
        });
    });
    
</script>

<?php include ("./data/include/footer.inc.php"); ?>