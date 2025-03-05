<?php
/**
 * MenüTemplates bearbeiten
 * @author stefan@covi.de
 * @since 3.2.4
 * @version 7.0
 * @lastchange 2021-09-15
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-paint-brush',returnIntLang('menu design'),returnIntLang('menu design menutmp'));
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].';id='.checkparamvar('id');
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['addpagejs'] = array('jquery/jquery.autogrowtextarea.js',);
$_SESSION['wspvars']['addpagecss'] = array('jquery.nestable.css');
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");

/* define page specific vars ----------------- */

/* define page specific functions ------------ */
if (checkparamvar('op')=="new") {
	
	$new_guid = md5(date("Y-m-d H:i:s"));
	$new_guid = substr($new_guid,0,8)."-".substr($new_guid,8,4)."-".substr($new_guid,12,4)."-".substr($new_guid,16,4)."-".substr($new_guid,20);
    // check for existing menu
	$menu_sql = "SELECT * FROM `templates_menu` WHERE `title` LIKE '".escapeSQL($_POST['menu_title'])."'";
	$menu_res = doSQL($menu_sql);
	if ($menu_res['num']>0) {
        $_POST['menu_title'] = $_POST['menu_title']." #".(($menu_res['num'])+1);
	}

    $sql = "INSERT INTO `templates_menu` SET `title` = '".escapeSQL($_POST['menu_title'])."', `describ` = '".escapeSQL($_POST['menu_describ'])."', `startlevel` = '".intval($_POST['menu_slevel'])."'";
    if (isset($_POST['item'])) {
        $itemcode = '';
        foreach ($_POST['item'] AS $pik => $piv) {
            $itemcode.= "LEVEL {\n";
            foreach ($piv AS $pivck => $pivcv) {
                if (trim($pivcv)!='') {
                    $itemcode.= "\t".$pivck." = '".trim($pivcv)."'\n";
                }
            }
            $itemcode.= "}"; 
        }
        $sql.= ", `code` = '".escapeSQL($itemcode)."'";
    }
    else if (isset($_POST['code'])) {
		$sql.= ", `code` = '".escapeSQL($_POST['code'])."'";
	} else {
        $sql.= ", `code` = ''";
    }
    $sql.= ", `guid` = '".escapeSQL($new_guid)."'";
    $res = doSQL($sql);
    if ($res['aff']) {
        addWSPMsg('noticemsg', returnIntLang('menutmp template created'));
    } else {
        if (defined('WSP_DEV') && WSP_DEV) {
            addWSPMsg('errormsg', var_export($res, true));
        }
    }
}
else if (checkparamvar('op')=="save") {
	$sql = "UPDATE `templates_menu` SET `title` = '".escapeSQL($_POST['menu_title'])."', `describ` = '".escapeSQL($_POST['menu_describ'])."', `startlevel` = '".intval($_POST['menu_slevel'])."'";
	if (isset($_POST['item'])) {
        $itemcode = '';
        foreach ($_POST['item'] AS $pik => $piv) {
            $itemcode.= "LEVEL {\n";
            foreach ($piv AS $pivck => $pivcv) {
                if (trim($pivcv)!='') {
                    $itemcode.= "\t".$pivck." = '".trim($pivcv)."'\n";
                }
            }
            $itemcode.= "}"; 
        }
        $sql.= ", `code` = '".escapeSQL($itemcode)."'";
    }
    else if (isset($_POST['code'])) {
		$sql.= ", `code` = '".escapeSQL($_POST['code'])."'";
	} else {
        $sql.= ", `code` = ''";
    }
	$sql.= "WHERE `id` = ".intval(checkparamvar('id'));
	$res = doSQL($sql);
	if ($res['aff']) {
        addWSPMsg('noticemsg', returnIntLang('menutmp template properties saved'));
    }
}
else if (checkparamvar('op')=="delete" && intval($_POST['id'])>0) {
    $menu_sql = "SELECT `guid` FROM `templates_menu` WHERE `id` = ".intval($_POST['id']);
	$menu_res = doSQL($menu_sql);
    if ($menu_res['num']>0) {
		// remove menutemplate
        doSQL("DELETE FROM `templates_menu` WHERE `id` = ".intval($_POST['id']));
        addWSPMsg('noticemsg', returnIntLang('menutmp template removed'));
        // find affected templates
        $template_sql = "SELECT `id` FROM `templates` WHERE `template` LIKE '%[\%MENUVAR:".$menu_res['set'][0]['guid']."\%]%'";
        $template_res = doSQL($template_sql);
        if ($template_res['num']>0) {
            // do replacing in templates
            doSQL("UPDATE `templates` SET `template` = REPLACE( `template` , '[%MENUVAR:".$menu_res['set'][0]['guid']."%]', '' ) WHERE `template` LIKE '%[\%MENUVAR:".$menu_res['set'][0]['guid']."\%]%'");
            doSQL("UPDATE `templates` SET `template` = REPLACE( `template` , '[%MENUVAR:".strtoupper($menu_res['set'][0]['guid'])."%]', '' ) WHERE `template` LIKE '%[\%MENUVAR:".$menu_res['set'][0]['guid']."\%]%'");
            // set contentedit for pages that are affected 
            foreach ($template_res['set'] AS $trk => $trv) {
                $affMID = getTemplateTree($trv['id']);
                foreach ($affMID AS $midv) {
                    setContentChangeStat($midv, 1);
                }
            }
            addWSPMsg('noticemsg', returnIntLang('menutmp template removed from templates'));
        }
	}
}

// head der datei

include ("data/include/header.inc.php");
include ("data/include/navbar.inc.php");
include ("data/include/sidebar.inc.php");
?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title">
                    <?php echo returnIntLang('menutmp headline'); ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo returnIntLang('menutmp info'); ?>
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
            <div class="row">
                <?php
                
                $menutpl_sql = "SELECT * FROM `templates_menu` ORDER BY `title`";
                $menutpl_data = doSQL($menutpl_sql);
                
                if ($menutpl_data['num']>0 && trim(checkParamVar('op'))=='') {
                ?>
                <div class="col-md-12">
                    <div class="panel" id="existingmenutemplates">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <?php echo returnIntLang('menutmp existingtemplates'); ?>
                            </h3>
                            <?php panelOpener(false, array('op',array('edit','editsource','savecode','preview'),array(false,false,false,false)), true, 'existingmenutemplates'); ?>
                        </div>
                        <div class="panel-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="col-md-3">
                                            <?php echo returnIntLang('menutmp templatename'); ?>
                                        </th>
                                        <th class="col-md-3">
                                            <?php echo returnIntLang('menutmp templatetype'); ?>
                                        </th>
                                        <th class="col-md-3">
                                            <?php echo returnIntLang('str usage'); ?>
                                        </th>
                                        <th class="col-md-2">
                                            <?php echo returnIntLang('str startlevel'); ?>
                                        </th>
                                        <th class="col-md-1 text-right">
                                            <?php echo returnIntLang('str action'); ?>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $i = 0;
                                    foreach ($menutpl_data['set'] as $mtdk => $mtdv):
                                        echo "<tr>";
                                        echo "<td><a style=\"cursor: pointer;\" onclick=\"document.getElementById('editform".intval($mtdv['id'])."').submit();\">".setUTF8($mtdv['title'])."</a></td>";
                                        echo "<td>";
                                        if ($mtdv['parser']!=""):
                                            echo returnIntLang('menutmp parserfile');
                                        elseif (trim($mtdv['code'])!=""):
                                            echo returnIntLang('menutmp cmcode');
                                        else:
                                            echo returnIntLang('menutmp emptycode');
                                        endif;
                                        echo "</td>";
                                        echo "<td>";

                                        $tplusage_sql = "SELECT `id`, `name` FROM `templates` WHERE `template` LIKE '%[\%MENUVAR:".$mtdv['guid']."\%]%'";
                                        $tplusage_data = doSQL($tplusage_sql);
                                        if ($tplusage_data['num']>0):
                                            foreach ($tplusage_data['set'] AS $tudk => $tudv):
                                                echo returnIntLang('menutmp template')." ".$tudv['name']."<br />";
                                            endforeach;
                                        else:
                                            echo returnIntLang('str no usage');
                                        endif;

                                        echo "</td>";
                                        echo "<td>".$mtdv['startlevel']." <form id=\"editform".intval($mtdv['id'])."\" method=\"post\"><input type=\"hidden\" name=\"op\" value=\"edit\"><input type=\"hidden\" name=\"id\" value=\"".intval($mtdv['id'])."\"></form><form id=\"editsourceform".intval($mtdv['id'])."\" method=\"post\"><input type=\"hidden\" name=\"op\" value=\"editsource\"><input type=\"hidden\" name=\"id\" value=\"".intval($mtdv['id'])."\"></form></td>";
                                        
                                        echo "<td class='text-right' nowrap='nowrap'><a style='cursor: pointer;' onclick=\"document.getElementById('editform".intval($mtdv['id'])."').submit();\"><i class='fa fa-pencil-alt fa-btn'></i></a> ";
                                        if ($mtdv['parser']=="") {
                                            echo "<a style=\"cursor: pointer;\" onclick=\"$('#editsourceform".intval($mtdv['id'])."').submit();\"><i class='fas fa-code fa-btn'></i></a> ";
                                        }
                                        echo "<a style=\"cursor: pointer;\" onclick=\"removeMenu(".intval($mtdv['id']).",'".prepareTextField(setUTF8($mtdv['title']))."');\"><i class='fa fa-trash fa-btn'></i></a></td>";
                                        
                                        echo "</tr>";
                                    endforeach;

                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php } else if (trim(checkParamVar('op'))=='') {
                    ?>
                    <div class="row">
                        <div class="col-md-12">
                            <h3 style="text-align: center; font-weight: 300"><?php echo returnIntLang('menutmp just create your first menutemplate', false); ?></h3>
                            <h1 style="text-align: center; font-size: 8vw; padding-top: 1vw">
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?op=edit&id=0"><i class="fas fa-edit"></i></a>
                            </h1>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <?php
            
            if (checkParamVar('op')=='edit' || checkParamVar('op')=='editsource' || checkParamVar('op')=='savecode' || checkParamVar('op')=='preview') {
            
                $editmenu_sql = "SELECT * FROM `templates_menu` WHERE `id` = ".intval(checkParamVar('id'));
                $editmenu_res = doSQL($editmenu_sql);

                if ($editmenu_res['num']>0) {
                    $id = intval($editmenu_res['set'][0]['id']);
                    $title = prepareTextField($editmenu_res['set'][0]['title']);
                    $desc = prepareTextField($editmenu_res['set'][0]['describ']);
                    $slevel = intval($editmenu_res['set'][0]['startlevel']);
                    $buf = "[%MENUVAR:".strtoupper(trim($editmenu_res['set'][0]['guid']))."%]";
                    if (trim($editmenu_res['set'][0]['parser'])!="") {
                        $type = "parser";
                        $code = trim($editmenu_res['set'][0]['parser']);
                        $jobkind = "save";
                    }
                    else {
                        $code = trim($editmenu_res['set'][0]['code']);
                        $type = "code";
                        $jobkind = "save";
                    }
                }
                else {
                    $id = 0;
                    $title = "";
                    $desc = "";
                    $slevel = 1;
                    $code = "";
                    $type = "code";
                    $jobkind = "new";
                    $buf = "";
                }
                
                if (checkParamVar('op')=='savecode') {
                    $title = trim($_POST['menu_title']);
                    $desc = trim($_POST['menu_describ']);
                    $slevel = intval($_POST['menu_slevel']);
                    $code = trim($_POST['code']);
                }
            
                if (checkParamVar('op')=='editsource') {
                    $title = isset($_POST['menu_title'])?trim($_POST['menu_title']):$title;
                    $desc = isset($_POST['menu_describ'])?trim($_POST['menu_describ']):$desc;
                    $slevel = isset($_POST['menu_slevel'])?intval($_POST['menu_slevel']):$slevel;
                    if (isset($_POST['item'])) {
                        $code = '';
                        foreach ($_POST['item'] AS $pik => $piv) {
                            $code.= "LEVEL {\n";
                            ksort($piv);
                            foreach ($piv AS $pivck => $pivcv) {
                                $code.= "\t".$pivck." = '".trim($pivcv)."'\n";
                            }
                            $code.= "}\n\n"; 
                        }
                    }
                }
            
                if (checkParamVar('op')=='preview') {
                    ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <?php echo returnIntLang('menutmp previewmenu'); ?>
                            </h3>
                            <p class="panel-subtitle"><?php echo returnIntLang('menutmp previewmenu desc'); ?></p>
                        </div>
                        <div class="panel-body">
                            <form id="menutemplateform" name="menutemplateform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                <style>
                                
                                    #menupreview {
                                        width: 100%; 
                                        background: rgba(255,255,255,0.05);
                                        height: 200px;
                                        max-height: 200px;
                                        overflow: hidden;
                                        margin-bottom: 20px; 
                                    }
                                    
                                </style>
                                <div id="menupreview">
                                    <?php 
                                    
                                    $mncd = showMenuDesign(trim($code));
                                    $prvmid = doResultSQL('SELECT `mid` FROM `menu` WHERE `level` = '.$slevel);
                                    include_once('./data/include/menuparser.inc.php');
                                    $prvmenu = buildMenu($mncd, $slevel, 0, $prvmid, 'de', 0, 0, true);

                                    var_Export($prvmenu);

                                    echo $prvmenu['menucode'];
                    
                                    ?>
                                </div>
                                <p><input name="op" id="op" type="hidden" value="edit" /><input name="id" id="id" type="hidden" value="<?php echo $id; ?>" /><a href="#" class="btn btn-info" onClick="$('#menutemplateform').submit();"><?php echo returnIntLang('str back', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-warning"><?php echo returnIntLang('str cancel', false); ?></a> <?php if ($id>0) { ?><a onclick="removeMenu(<?php echo $id; ?>, '<?php echo prepareTextField($title); ?>');" class="btn btn-danger"><?php echo returnIntLang('str delete', false); ?></a><?php } ?></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
                <?php
                }
                else {
                ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <?php echo returnIntLang('menutmp editmenu'); ?>
                            </h3>
                        </div>
                        <div class="panel-body">
                            <form id="menutemplateform" name="menutemplateform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                <div class="row">
                                    <div class="col-md-3">
                                        <p><input name="menu_title" placeholder="<?php echo returnIntLang('menutmp menuname', false); ?>" id="menu_title" type="text" value="<?php echo $title; ?>" class="form-control" /></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><input name="menu_describ" placeholder="<?php echo returnIntLang('menutmp menudesc', false); ?>" id="menu_describ" type="text" value="<?php echo $desc; ?>" class="form-control" /></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><input name="menu_slevel" placeholder="<?php echo returnIntLang('menutmp startlevel', false); ?>" id="menu_slevel" type="number" min="0" value="<?php echo $slevel; ?>" maxlength="2" class="form-control" /></p>
                                    </div>
                                </div>
                                <?php 
                                if ($type=="parser") { 
                                    ?>
                                    <!-- menutemplate is parser/interpreter file -->
                                    <div class="row">
                                        <div class="col-md-3">
                                            <p>
                                                <?php echo returnIntLang('menutmp sourcefile'); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-9">
                                            <p><input name="parser" id="parser" type="text" value="<?php echo $code; ?>" readonly="readonly" disabled="disabled" style="width: 98%;" /></p>
                                        </div>
                                    </div>
                                    <?php }
                                else { 
                                    if (checkParamVar('op')=='editsource') {
                                    ?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <textarea class="form-control autogrow allowTabChar" name="code"><?php echo $code; ?></textarea>
                                            <p style="font-size: 1px;">&nbsp;</p>
                                            <p><a onclick="$('#op').val('savecode'); $('#menutemplateform').submit();" class="btn btn-warning"><?php echo returnIntLang('menutmp quit sourcecode editor', false); ?></a></p>
                                        </div>
                                    </div>
                                    <?php } else { ?>
                                    <div class="row">
                                        <div class="col-md-6" id="showcase_panel">
                                            <div class="panel">
                                                <div class="panel-heading">
                                                    <h3 class="panel-title"><?php echo returnIntLang('menutmp manage level entries'); ?></h3>
                                                    <div class="right">
                                                        <div class="dropdown">
                                                            <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-bars"></i> </a>
                                                            <ul class="dropdown-menu dropdown-menu-right">
                                                                <li><a id="btn-append" onclick="appendLevel();"><i class="fas fa-plus-square"></i><?php echo returnIntLang('menutmp append level'); ?></a></li>
                                                                <?php if (checkParamVar('op')!='editsource') { ?><li><a onclick="$('#op').val('editsource'); $('#menutemplateform').submit();" id="btn-source"><i class="fas fa-code"></i><?php echo returnIntLang('menutmp edit sourcecode'); ?></a></li><?php } ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="panel-body">
                                                    <?php

                                                    // create array/object from string menu
                                                    $menucode = showMenuDesign($code);
                                                    $menuproperties = array(
                                                        'TYPE' => false,
                                                        'CONTAINER' => false, // style
                                                        'CONTAINER.CLASS' => false,
                                                        'CONTAINER.DISPLAY' => false,
                                                        'SPACER' => false,
                                                        'DELIMITER' => false,
                                                        'DELIMITER.CLASS' => false,
                                                        'DELIMITER.ACTIVE' => false,
                                                        'DELIMITER.INACTIVE' => false,
                                                        'LINK' => false,
                                                        'LINK.CLASS' => false,
                                                        'MENU.SHOW' => false,
                                                        'MENU.HIDE' => false,
                                                        'MENU.LIST' => false,
                                                    );
                                                    
                                                    foreach ($menucode AS $mck => $mcv) {
                                                        $mcp = $menuproperties;
                                                        echo "<div class='row' id='level-".$mck."'>";
                                                        echo "<div class='col-md-12 text-right'><p>";
                                                        echo "<span class='btn btn-xs btn-danger' onclick=\"removeLevel('level-".$mck."')\"><i class='fas fa-minus-square'></i> ".returnIntLang('menutmp remove level', false)."</span> ";
                                                        echo "</p></div>";
                                                        echo "<div class='col-md-12 used-items' style='margin-top: 10px; margin-bottom: 10px;'>";
                                                        foreach ($mcv AS $dk => $dv) {
                                                            if (trim($dv)!='') {

                                                                echo "<input type='hidden' class='".str_replace(".", "_", $dk)."-level-".$mck."-value' name='item[".$mck."][".$dk."]' value='".$dv."' />";
                                                                echo "<span class='btn btn-xs btn-default ".str_replace(".", "_", $dk)."-desc' style='margin-right: 3px; margin-bottom: 3px; max-width: 200px; overflow: hidden; text-overflow: ellipsis;' onclick=\"editItem('".$dk."', 'level-".$mck."')\">".$dk." : ".$dv."</span> ";
                                                                
                                                                unset($mcp[$dk]);

                                                            }
                                                        }
                                                        foreach ($mcp AS $dk => $dv) {
                                                            echo "<input type='hidden' class='".str_replace(".", "_", $dk)."-level-".$mck."-value' name='item[".$mck."][".$dk."]' value='' />";
                                                        }
                                                        echo "</div>";
                                                        echo "<div class='col-md-12 unused-items'><p>";
                                                        foreach ($mcp AS $dk => $dv) {
                                                            echo "<span class='btn btn-xs btn-primary ".str_replace(".", "_", $dk)."-add' style='margin-right: 3px; margin-bottom: 3px;' onclick=\"editItem('".$dk."', 'level-".$mck."')\"><i class='fas fa-plus-square'></i> ".$dk."</span> ";
                                                        }
                                                        echo "</p></div>";
                                                        echo "<div class='col-md-12'>";
                                                        echo "<hr />";
                                                        echo "</div>";
                                                        echo "</div>";
                                                    
                                                    }

                                                    ?>
                                                    <div class="row" id="actionrow">
                                                        <div class="col-md-12">
                                                            <p><a onclick="appendLevel();" class="btn btn-primary"><?php echo returnIntLang('menutmp append level', false); ?></a><?php if (checkParamVar('op')!='editsource') { ?> <a onclick="$('#op').val('editsource'); $('#menutemplateform').submit();" class="btn btn-info"><?php echo returnIntLang('menutmp edit sourcecode', false); ?></a><?php } ?></p>
                                                        </div>
                                                    </div>
                                                    <script>
                                                    
function removeLevel(levelID) {
    $('#' + levelID).fadeOut(1000, function(){
        $('#' + levelID).remove();
    });
}
                                                        
function appendLevel() {
    var myTime = Math.round((new Date()).getTime() / 1000);
    var newRow = '<div class="row" id="level-' + myTime + '">';
    newRow += '<div class="col-md-12 text-right">';
    newRow += '<p><span class="btn btn-xs btn-danger" onclick="removeLevel(\'level-' + myTime + '\')"><i class="fas fa-minus-square"></i> <?php echo returnIntLang('menutmp remove level', false) ?></span></p>';
    newRow += '</div>';
    newRow += '<div class="col-md-12 used-items"></div>';
    newRow += '<div class="col-md-12 unused-items">';
    <?php foreach ($menuproperties AS $mpk => $mpv) { ?>newRow += '<span class="btn btn-xs btn-primary <?php echo str_replace(".", "_", $mpk); ?>-add" style="margin-right: 3px; margin-bottom: 3px;" onclick="editItem(\'<?php echo $mpk; ?>\', \'level-' + myTime + '\')"><i class="fas fa-plus-square"></i> <?php echo $mpk; ?></span><input type="hidden" name="item[' + myTime + '][<?php echo $mpk; ?>]" class="<?php echo str_replace(".", "_", $mpk); ?>-level-' + myTime + '-value" value="" /> ';
    <?php } ?>
    newRow += '</div>';
    newRow += '<div class="col-md-12"><hr /></div>';
    newRow += '</div>';
    $('#actionrow').before(newRow);
}
                                                        
function editItem(itemOption, levelID) {
    $('#editlevel').val(levelID);
    $('#edititem').val(itemOption);
    $('#edititems > div').hide();
    $('#edititems').find('#appenditemprops').show();
    $('#edititems').find('#' + itemOption.replace('.', '_')).show();
    $('#editvalue').val($('.' + itemOption.replace('.', '_') + '-' + levelID + '-value').val());
    initItem(itemOption);
}
                         
function initItem(itemOption) {
    if (itemOption=='MENU.SHOW') {
        $('#MENU_SHOW-property').val($('#editvalue').val());
        initList('MENU_SHOW-options', $('#editvalue').val());
    }
    else if (itemOption=='MENU.HIDE') {
        $('#MENU_HIDE-property').val($('#editvalue').val());
        initList('MENU_HIDE-options', $('#editvalue').val());
    }
    else if (itemOption=='MENU.LIST') {
        $('#MENU_LIST-property').val($('#editvalue').val());
        initList('MENU_LIST-options', $('#editvalue').val());
    }
    else {
        $('#' + itemOption.replace('.', '_') + '-property').val($('#editvalue').val());
    }
}
                                                        
function updateItem() {
    var itemOption = $('#edititem').val();
    var levelID = $('#editlevel').val();
    var newVal = $('#' + itemOption.replace('.', '_') + '-property').val();
    var conInput = $('#' + levelID).find('.' + itemOption.replace('.', '_') + '-' + levelID + '-value');
    var conSpan = $('#' + levelID).find('.' + itemOption.replace('.', '_') + '-desc');
    var addSpan = $('#' + levelID).find('.' + itemOption.replace('.', '_') + '-add');
    conInput.val(newVal);
    if (conSpan.text()!='') {
        if (newVal!='' && newVal!=undefined) {
            conSpan.text(itemOption + ' : ' + newVal);
        }
        else {
            $('#' + levelID).find('.unused-items').find('p').append('<span class="btn btn-xs btn-primary ' + itemOption.replace('.', '_') + '-add" style="margin-right: 3px; margin-bottom: 3px;" onclick="editItem(\'' + itemOption + '\', \'' + levelID + '\')"><i class="fas fa-plus-square"></i> ' + itemOption + '</span>');
            conSpan.fadeOut(1000, function(){
                conSpan.remove();
            });
        }
    }
    if (addSpan.text()!='') {
        if (newVal!='' && newVal!=undefined) {
            conInput.after('<span class="btn btn-xs btn-default ' + itemOption.replace('.', '_') + '-desc" style="margin-right: 3px; margin-bottom: 3px; max-width: 200px; overflow: hidden; text-overflow: ellipsis;" onclick="editItem(\'' + itemOption + '\', \'' + levelID + '\')">' + itemOption + ' : ' + newVal + '</span>');
            addSpan.fadeOut(1000, function(){
                addSpan.remove();
            })
        } 
        else {
            // ???    
        }
    }
}
                                                        
                                                    </script>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="property_panel">
                                            <div class="panel">
                                                <div class="panel-heading">
                                                    <h3 class="panel-title">
                                                        <?php echo returnIntLang('menutmp edit level entry'); ?>
                                                    </h3>
                                                </div>
                                                <div class="panel-body" id="edititems">
                                                    <input type="hidden" id="editlevel" value="" />
                                                    <input type="hidden" id="edititem" value="" />
                                                    <input type="hidden" id="editvalue" value="" />
                                                    <div>
                                                        <p><?php echo returnIntLang('menutmp choose item from left to edit its properties'); ?></p>
                                                    </div>
                                                    <div id="TYPE" style="display: none;">
                                                        <p><?php echo returnIntLang('menutmp code type'); ?></p>
                                                        <p><?php echo returnIntLang('menutmp code type desc'); ?></p>
                                                        <p>
                                                            <select id="TYPE-property" class="form-control">
                                                                <option value=""><?php echo returnIntLang('menutmp code disable', false); ?></option>
                                                                <option value="LIST"><?php echo returnIntLang('menutmp code type listsub', false); ?></option>
                                                                <option value="LINK"><?php echo returnIntLang('menutmp code type link'); ?></option>
                                                                <option value="SELECT"><?php echo returnIntLang('menutmp code type select'); ?></option>
                                                            </select>
                                                        </p>
                                                    </div>
                                                    <div id="CONTAINER" style="display: none;">
                                                        <p><strong><?php echo returnIntLang('menutmp code container'); ?></strong></p>
                                                        <p><?php echo returnIntLang('menutmp code container desc'); ?></p>
                                                        <p><input type="text" id="CONTAINER-property" value="" class="form-control full"></p>
                                                    </div>
                                                    <div id="CONTAINER_CLASS" style="display: none;">
                                                        <p><?php echo returnIntLang('menutmp code containerclass'); ?></p>
                                                        <p><?php echo returnIntLang('menutmp code containerclass desc'); ?></p>
                                                        <p><input type="text" id="CONTAINER_CLASS-property" value="" class="form-control full"></p>
                                                    </div>
                                                    <div id="CONTAINER_DISPLAY" style="display: none;">
                                                        <p><?php echo returnIntLang('menutmp code containerdisplay'); ?></p>
                                                        <p><?php echo returnIntLang('menutmp code containerdisplay desc'); ?></p>
                                                        <p><select id="CONTAINER_DISPLAY-property" size="1" class="form-control">
                                                            <option value=""><?php echo returnIntLang('menutmp code disable', false); ?></option>
                                                            <option value="inline">'inline'</option>
                                                            <option value="block">'block'</option>
                                                            <option value="inline-block">'inline-block'</option>
                                                            <option value="table-cell">'table cell'</option>
                                                            <option value="none">Ausblenden</option>
                                                        </select></p>
                                                    </div>
                                                    <div id="SPACER" style="display: none;">
                                                        <p><?php echo returnIntLang('menutmp code spacer'); ?></p>
                                                        <p><?php echo returnIntLang('menutmp code spacer desc'); ?></p>
                                                        <p><input type="text" id="SPACER-property" class="form-control" value=""></p>
                                                    </div>
                                                    <div id="DELIMITER" style="display: none;">
                                                        <p>DELIMITER</p>
                                                        
                                                        <p><input type="text" id="DELIMITER-property" value="" class="form-control full"></p>
                                                    </div>
                                                    <div id="DELIMITER_CLASS" style="display: none;">
                                                        <p>DELIMITER.CLASS</p>
                                                        <p>Klasse der den Link umschließenden Elemente (nur bei TYPE:LIST und TYPE:LINK)</p>
                                                        <p><input type="text" id="DELIMITER_CLASS-property" value="" class="form-control" placeholder="" /></p>
                                                    </div>
                                                    <div id="DELIMITER_ACTIVE" style="display: none;">
                                                        <p>DELIMITER.ACTIVE</p>
                                                        <p><input type="text" id="DELIMITER_ACTIVE-property" value="" class="form-control full"></p>
                                                    </div>
                                                    <div id="DELIMITER_INACTIVE" style="display: none;">
                                                        <p>DELIMITER.INACTIVE</p>
                                                        <p><input type="text" id="DELIMITER_INACTIVE-property" value="" class="form-control full"></p>
                                                    </div>
                                                    <div id="LINK" style="display: none;">
                                                        <p><strong>LINK</strong></p>
                                                        <p>Klasse für &lt;a&gt;-Element</p>
                                                        <p><input type="text" id="LINK-property" value="" class="form-control" placeholder="" /></p>
                                                    </div>
                                                    <div id="LINK_CLASS" style="display: none;">
                                                        <p><strong>LINK.CLASS</strong></p>
                                                        <p>Klasse für &lt;a&gt;-Element</p>
                                                        <p><input type="text" id="LINK_CLASS-property" value="" class="form-control" placeholder="" /></p>
                                                    </div>
                                                    <?php 
                                                    $menupoints = subpMenu(0); 
                                                    ?>
                                                    <div id="MENU_SHOW" style="display: none;">
                                                        <p><strong>MENU.SHOW</strong></p>
                                                        <p>Über MENU.SHOW definierte Menüpunkte sind immer sichtbar und werden entsprechend der gewählten Reihenfolge angezeigt. Strukturinformationen werden verworfen.</p>
                                                        <input type="hidden" id="MENU_SHOW-property" value="" class="form-control" placeholder="" />
                                                        <div style="width: 100%; max-height: 20em; overflow: hidden; overflow-y: auto;">
                                                            <ol id="MENU_SHOW-options" class="sortable">
                                                                <?php 
                                                  
foreach ($menupoints AS $mk => $mv) {					
    $m_sql = "SELECT `description`, `level` FROM `menu` WHERE `mid` = ".intval($mv);
    $m_res = doSQL($m_sql);
    if ($m_res['num']>0) {
        echo "<li class='li-".intval($mv)."'><input type='checkbox' value='".intval($mv)."' onchange=\"updateList('MENU_SHOW-options')\" /> ";
        for ($l=1; $l<intval($m_res['set'][0]['level']); $l++) { echo ". "; }
        echo trim(setUTF8($m_res['set'][0]['description']));
        echo "</li>";
    }
}
                                                  
                                                                ?>
                                                            </ol>
                                                        </div>
                                                    </div>
                                                    <div id="MENU_HIDE" style="display: none;">
                                                        <p><strong>MENU.HIDE</strong></p>
                                                        <p>W&auml;hlen sie hier bei Bedarf die Men&uuml;punkte aus, die im Men&uuml; nicht dargestellt werden sollen. Sie können die Menüpunkte entsprechend per Klick markieren.</p>
                                                        <input type="hidden" id="MENU_HIDE-property" value="" class="form-control" placeholder="" />
                                                        <ol id="MENU_HIDE-options" class="sortable">
                                                            <?php 
                                                  
foreach ($menupoints AS $mk => $mv) {					
    $m_sql = "SELECT `description`, `level` FROM `menu` WHERE `mid` = ".intval($mv);
    $m_res = doSQL($m_sql);
    if ($m_res['num']>0) {
        echo "<li class='li-".intval($mv)."'><input type='checkbox' value='".intval($mv)."' onchange=\"updateList('MENU_HIDE-options');\" /> ";
        for ($l=1; $l<intval($m_res['set'][0]['level']); $l++) { echo ". "; }
        echo trim(setUTF8($m_res['set'][0]['description']));
        echo "</li>";
    }
}
                                                  
                                                            ?>
                                                        </ol>
                                                    </div>
                                                    <div id="MENU_LIST" style="display: none;">
                                                        <p><strong>MENU.LIST</strong></p>
                                                        <p>Über MENU.LIST definierte Menüpunkte richten sich in ihrer Sichtbarkeit nach den Einstellungen in der Seitenstruktur, können aber beliebig sortiert werden und werden entsprechend der gewählten Reihenfolge angezeigt.</p>
                                                        <input type="hidden" id="MENU_LIST-property" value="" class="form-control" placeholder="" />
                                                        <ol id="MENU_LIST-options" class="sortable">
                                                            <?php 
                                                
                                                  foreach ($menupoints AS $mk => $mv) {					
                                                      $m_sql = "SELECT `description`, `level` FROM `menu` WHERE `mid` = ".intval($mv);
                                                      $m_res = doSQL($m_sql);
                                                      if ($m_res['num']>0) {
                                                          echo "<li class='li-".intval($mv)."'><input type='checkbox' value='".intval($mv)."' onchange=\"updateList('MENU_LIST-options');\" /> ";
                                                          for ($l=1; $l<intval($m_res['set'][0]['level']); $l++) { echo ". "; }
                                                          echo trim(setUTF8($m_res['set'][0]['description']));
                                                          echo "</li>";
                                                      }
                                                  }
                                                  
                                                            ?>
                                                        </ol>
                                                    </div>
                                                    <hr class="clearbreak" />
                                                    <div id="appenditemprops" style="display: none;">
                                                        <p><a class="btn btn-danger" onclick="updateItem();"><?php echo returnIntLang('menutmp append property', false); ?></a></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                                    <?php } ?>
                                <?php } ?>
                                <p><input name="op" id="op" type="hidden" value="<?php echo $jobkind; ?>" /><input name="type" id="type" type="hidden" value="<?php echo $type; ?>" /><input name="id" id="id" type="hidden" value="<?php echo $id; ?>" /><a href="#" class="btn btn-primary" onClick="$('#menutemplateform').submit();"><?php echo returnIntLang('str save', false); ?></a> <a href="#" class="btn btn-info" onClick="$('#op').val('preview'); $('#menutemplateform').submit();"><?php echo returnIntLang('str preview', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-warning"><?php echo returnIntLang('str cancel', false); ?></a> <?php if ($id>0) { ?><a onclick="removeMenu(<?php echo $id; ?>, '<?php echo prepareTextField($title); ?>');" class="btn btn-danger"><?php echo returnIntLang('str delete', false); ?></a><?php } ?></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
                <?php } ?>
            <?php } else if ($menutpl_data['num']>0) { ?>
            <div class="row">
                <div class="col-md-12">
                    <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>?op=edit&id=0" class="btn btn-primary"><?php echo returnIntLang('menutmp createnewtemplate', false); ?></a></p>
                </div>
            </div>
            <?php } ?>
            <form id="deletemenu" name="deletemenu" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <input name="op" id="delop" type="hidden" value="delete" />
                <input name="id" id="delid" type="hidden" value="0" />
            </form>
        </div>
    </div>
</div>

<style>

    body.dragging, body.dragging * {
        cursor: move !important;
    }

    .dragged {
        position: absolute;
        opacity: 0.5;
        z-index: 2000;
    }

    ol.sortable { list-style-type: none; padding: 0px; margin: 10px 0px; }
    
    ol.sortable li {
        float: left; 
        border: 1px solid #ccc; padding: 0px 8px; margin-right: 3px; margin-bottom: 3px; line-height: 2.4em; height: 2.4em; }
    
    ol.sortable li.placeholder {
        position: relative;
        line-height: 2.4em; 
        height: 2.4em;
        width: 50px;
        background: red;
        opacity: 0.5;
    }

    ol.sortable li.placeholder:before {
        position: absolute;
        /** Define arrowhead **/
    }
    
</style>

<script>

function initList(listID, activeItems) {
    var activeMID = activeItems.split(';');
    activeMID = activeMID.reverse();
    // reset all checkboxes
    $('#' + listID).find('input').prop("checked", false);
    // run entries to enable checkboxes
    activeMID.forEach(function(item, index){
        $('#' + listID + ' .li-' + item).find('input').prop('checked', true);
        $('#' + listID).prepend($('#' + listID + ' .li-' + item).detach());
    });
    updateList(listID);
}
    
function updateList(listID) {
    var activeMID = new Array();
    $('#' + listID + ' input').each(function(e){
        if ($(this).prop('checked')) {
            activeMID.push($(this).val());
        }
    });
    $('#' + listID.replace('-options', '-property')).val(activeMID.join(';'));
}
    
function removeMenu(mID, menuTitle) {
    if (confirm('<?php echo returnIntLang('menutmp realy template delete1', false); ?>' + menuTitle + '<?php echo returnIntLang('menutmp realy template delete2', false); ?>')) { $('#delid').val(mID); $('#deletemenu').submit(); }
}
    
$(document).ready(function() {
    
    $(".sortable").sortable({
        onDrop: function ($item, container, _super) {
            updateList($item.parent('ol').attr('id'));
            _super($item, container);
        },
    });
    $(".allowTabChar").allowTabChar();
    $('.autogrow').autoGrow();

    // sticky property panel
    /*
    var nh = $('.navbar').outerHeight();
    var pt = $('#property_panel').offset().top;
    $(window).on('scroll', function(e) {
        var ws = $(window).scrollTop();
        if ((ws+nh+10)>(pt)) {
            $('#property_panel').css('margin-top', ((nh+ws)-pt+10));
        }
        else {
            $('#property_panel').css('margin-top','');
        }
        
    });
    */
    
});

</script>

<?php require ("data/include/footer.inc.php"); ?>
