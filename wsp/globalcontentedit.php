<?php
/**
 * Globale Inhalte editieren
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-06-17
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// checkParamVar -----------------------------
$gcid = 0;
if (array_key_exists('wspvars', $_SESSION) && array_key_exists('editglobalcontentid', $_SESSION['wspvars']) && intval($_SESSION['wspvars']['editglobalcontentid'])>0):
	$gcid = intval($_SESSION['wspvars']['editglobalcontentid']);
endif;
if (isset($_POST['gcid']) && intval($_POST['gcid'])>0):
	$_SESSION['wspvars']['editglobalcontentid'] = intval($_POST['gcid']);
	$gcid = intval($_POST['gcid']);
endif;
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = 'contentedit';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-sitemap',returnIntLang('menu content'),returnIntLang('menu content global'));
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].";gcid=".$gcid;
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = true;
$_SESSION['wspvars']['addpagecss'] = array(
    'bootstrap-multiselect.css',
    'summernote-wsp.css',
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'bootstrap/bootstrap-multiselect.js',
    'summernote/summernote.js',
    'summernote/plugin/br/br.summernote.js',
    'summernote/plugin/imagemanager/imagemanager.summernote.js',
    'summernote/plugin/linkmanager/linkmanager.summernote.js',
    );
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
require ("./data/include/clsinterpreter.inc.php");
$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);

/* define page specific funcs ---------------- */

// text2generic
if (isset($_REQUEST['op']) && trim($_REQUEST['op'])=='togeneric' && isset($_REQUEST['gcid']) && intval($_REQUEST['gcid'])>0):
	$sql = "UPDATE `content_global` SET `interpreter_guid` = 'genericwysiwyg' WHERE `id` = ".intval($_REQUEST['gcid']);
	$res = doSQL($sql);
	if ($res['aff']>0):
        addWSPMsg('resultmsg', returnIntLang('globalcontent updated to genericwysiwyg'));
    endif;
endif;

if (isset($_POST['op']) && ($_POST['op']=='save' || $_POST['op']=='saveglobal') && intval($gcid)>0):
	// Interpreter einlesen
    
    
    // if no interpreter function => just serialize field values
    $value = serialize($_POST['field']);
    // update globalcontent table with new values
	$sql = "UPDATE `content_global` SET `valuefields`= '".escapeSQL($value)."', `content_lang` = '".escapeSQL($_POST['content_lang'])."' WHERE `id` = " . intval($gcid);
	doSQL($sql);
    // update menu db set contentchanged where global content is used
    $menu_sql = "UPDATE `menu` `m`, `content` `c` SET `m`.`contentchanged` = 2 WHERE `c`.`mid` = `m`.`mid` AND `c`.`globalcontent_id` = ".intval($gcid);
	doSQL($menu_sql);
	// find templates using this global content
    $gctemplate_sql = "SELECT `id` FROM `templates` WHERE `template` LIKE '%[\%GLOBALCONTENT:".intval($gcid)."\%]%'";
	$gctemplate_res = doSQL($gctemplate_sql);
    
    if ($gctemplate_res['num']>0):
        // get ALL menupoints using this template (even submenu etc.)
        foreach ($gctemplate_res['set'] AS $gctrk => $gctrv):
            var_export(getTemplateTree($gctrv));
        endforeach;
        /*
		$submid = array();
		for ($gres=0; $gres<$gctemplate_num; $gres++):
			$menutpl_sql = "SELECT `mid` FROM `menu` WHERE `templates_id` = ".intval(mysql_result($gctemplate_res, $gres, 'id'));
			$menutpl_res = mysql_query($menutpl_sql);
			$menutpl_num = 0; if ($menutpl_res): $menutpl_num = mysql_num_rows($menutpl_res); endif;
			if ($menutpl_num>0):
				$submid = array();
				for ($mtres=0; $mtres<$menutpl_num; $mtres++):
					$subtplmid = returnIDRoot(mysql_result($menutpl_res, $mtres, 'mid'));
					$submid = array_merge($submid, $subtplmid);
				endfor;
			endif;
			$submid = array_unique($submid);
			foreach ($submid AS $sk => $sv):
				if (getTemplateID($sv)==intval(mysql_result($gctemplate_res, $gres, 'id'))):
					$menutpl_sql = "UPDATE `menu` SET `contentchanged` = 3 WHERE `mid` = ".intval($sv);
					mysql_query($menutpl_sql);
				endif;
			endforeach;
			$menutpl_sql = "UPDATE `menu` SET `contentchanged` = 3 WHERE `templates_id` = ".intval(mysql_result($gctemplate_res, $gres, 'id'));
			mysql_query($menutpl_sql);
		endfor;
        */
	endif;

    if (isset($_POST['remove_from']) AND (intval($_POST['remove_from'])==1 || intval($_POST['remove_from'])==9)) {
        // really delete items from content because global contents are still avaiable as globals
        $sql = "DELETE FROM `content` WHERE `globalcontent_id` = ".intval($_REQUEST['gcid']);
        $res = doSQL($sql);
        if ($res['aff']>0) {
            addWSPMsg('errormsg', returnIntLang('globalcontent removed from contents 1').' '.intval($res['aff']).' '.returnIntLang('globalcontent removed from contents 2'));
        }
    }
    
    if (isset($_POST['remove_from']) AND (intval($_POST['remove_from'])==2 || intval($_POST['remove_from'])==9)):
        // remove var from templates, if they were found  
        $sql = "UPDATE `templates` SET `template` = REPLACE(`template`, '[%GLOBALCONTENT:".intval($gcid)."%]', '') WHERE `template` LIKE '%[\%GLOBALCONTENT:".intval($gcid)."\%]%'";
        $res = doSQL($sql);
        if ($res['aff']>0): 
            addWSPMsg('errormsg', returnIntLang('globalcontent removed from templates 1').' '.$res['aff'].' '.returnIntLang('globalcontent removed from templates 2'));
        endif;
    endif;
    
    if ((!(isset($_POST['remove_from'])) || (isset($_POST['remove_from']) AND intval($_POST['remove_from'])==0)) && isset($_POST['content_template']) && intval($_POST['content_template'])>0):
        
        // if sharing of global contents is set
        $intoct = intval($_POST['content_template']);
        $intoca = intval($_POST['content_area']);
        $intoeo = ((intval($_POST['empty_areas_only'])==1)?true:false); // into empty only
        $intoap = ((intval($_POST['active_pages_only'])==1)?true:false);; // into empty pages
        $newpos = 9999; if ($intoeo): $newpos = 1; endif;
        $menulist = array();
        if ($intoct==999999):
            // insert into every menu
            // select every active menupoint
            $menu_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0";
            $menulist = getResultSQL($menu_sql);
        else:
            // select only menupoints from template id
            $menulist = getTemplateTree($intoct);
        endif;

        if ($intoeo):
            // run all mid to check for empty menupoints
            if (count($menulist)>0):
                foreach ($menulist AS $mk => $mv):
                    // check if there are contents connected to this menu
                    $cc_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mv)." AND `trash` = 0 AND `content_area` = ".intval($intoca);
                    $cc_res = getNumSQL($cc_sql);
                    if ($cc_res>0): unset($menulist[$mk]); endif;
                endforeach;
            endif;
        endif;
        
        if ($intoap):
            // run all mid to check for empty menupoints
            if (count($menulist)>0):
                foreach ($menulist AS $mk => $mv):
                    // check if there are contents connected to this menu
                    $cc_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mv)." AND `trash` = 0 AND `visible` = 1";
                    $cc_res = getNumSQL($cc_sql);
                    if ($cc_res>0): unset($menulist[$mk]); endif;
                endforeach;
            endif;
        endif;
        
        if (count($menulist)>0):
            $i=0;
            foreach ($menulist AS $mk => $mv):
                $ngcsql = "INSERT INTO `content` SET 
                    `mid` = ".intval($mv).", 
                    `uid` = ".intval($_SESSION['wspvars']['userid']).", 
                    `globalcontent_id` = ".intval($gcid).",
                    `content_area` = ".intval($intoca).",
                    `content_lang` = '".escapeSQL($_POST['content_lang'])."',
                    `position` = ".$newpos.",
                    `visibility` = 1,
                    `showday` = 0,
                    `showtime` = '',
                    `sid` = 0,
                    `description` = '',
                    `valuefields` = '',
                    `xajaxfunc` = '',
                    `xajaxfuncnames` = '',
                    `lastchange` = ".time().",
                    `interpreter_guid` = '".escapeSQL($_POST['interpreter_guid'])."',
                    `trash` = 0";
                $res = getAffSQL($ngcsql);
                if ($res>0):
                    $menugc = "UPDATE `menu` SET `contentchanged` = 3 WHERE `mid` = ".intval($mv);
                    getAffSQL($menugc);
                    $i++;
                endif;
            endforeach;
            addWSPMsg('noticemsg', returnIntLang('globalcontent placed into menu 1').' '.$i.' '.returnIntLang('globalcontent placed into menu 2'));
        else:
            addWSPMsg('noticemsg', returnIntLang('globalcontent was not placed. no menupoints matched your selected properties.'));
        endif;
        
    endif;

    if (isset($_POST['op_back']) && $_POST['op_back']):
		header("Location: ./globalcontents.php");
	endif;

endif;

// find menupoints using this global content
$gcmsql = "SELECT `mid` FROM `content` WHERE `globalcontent_id` = ".intval($gcid)." GROUP BY `mid`";
$gcmres = getResultSQL($gcmsql);
// find templates using this global content
$gctsql = "SELECT `id` FROM `templates` WHERE `template` LIKE '%[\%GLOBALCONTENT:".intval($gcid)."\%]%'";
$gctres = getResultSQL($gctsql);

function gcexists($mid, $gc_id, $ca_id) {
	$stat = false;
	$cid_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid) . " AND `globalcontent_id`=".intval($gc_id) . " AND `content_area`=".intval($ca_id) . " AND `trash`=0";
	$cid_res = mysql_query($cid_sql);
	$cid_num = 0; if ($cid_res): $cid_num = mysql_num_rows($cid_res); endif;
	if ($cid_num>0):
		$stat = false;
	else:
		$stat = true;
	endif;
	return $stat;
	}

function emptyca($mid, $ca_id) {
	$stat = false;
	$cid_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid) . " AND `content_area` = ".intval($ca_id). " AND `trash` = 0";
	$cid_res = mysql_query($cid_sql);
	$cid_num = 0; if ($cid_res): $cid_num = mysql_num_rows($cid_res); endif;
	if ($cid_num>0):
		$stat = false;
	endif;
	return $stat;
	}

function emptypage($mid) {
	// check if active contents are connected to mid => so this page is not only a forwarding page 
	$stat = true;
	$ep_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid)." AND `visibility` = 1 AND `trash` = 0";
	$ep_res = mysql_query($ep_sql);
	$ep_num = 0; if ($ep_res): $ep_num = mysql_num_rows($ep_res); endif;
	if ($ep_num>0): $stat = false; endif;
	return $stat;	
	}

function get_mid_with_template($tid, $con_id=0){
	$mwt = array();
	if($con_id>0):
		$mwt_sql = "SELECT `mid`, `connected` FROM `menu` WHERE `connected`=" . intval($con_id) . " AND `templates_id`=0 AND `trash`=0";
	else:
		$mwt_sql = "SELECT `mid`, `connected` FROM `menu` WHERE `templates_id`=". intval($tid). " AND `trash`=0";
	endif;
	$mwt_res = mysql_query($mwt_sql);
	$mwt_num = 0; if ($mwt_res): $mwt_num = mysql_num_rows($mwt_res); endif;
	if ($mwt_num>0):
		for($smp=0;$smp<$mwt_num;$smp++):
			$mwt[] = intval(mysql_result($mwt_res,$smp,"mid"));
			$mwt = array_merge($mwt, get_mid_with_template($tid, mysql_result($mwt_res,$smp,"mid")));
		endfor;
	endif;
	return $mwt;
}

// head der datei
require ("data/include/header.inc.php");
require ("data/include/navbar.inc.php");
require ("data/include/sidebar.inc.php");
?>
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('globalcontent editor headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('globalcontent modlanginfo'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php
            
            if (key_exists('workspacelang', $_SESSION['wspvars']) && $_SESSION['wspvars']['workspacelang']==""):
                $_SESSION['wspvars']['workspacelang'] = $worklang['languages']['shortcut'][0];
            endif;
            if (isset($_POST['workspacelang']) && $_POST['workspacelang']!=""):
                $_SESSION['wspvars']['workspacelang'] = $_POST['workspacelang'];
            endif;
            
            if (is_array($worklang['languages']['shortcut']) && count($worklang['languages']['shortcut'])>0):
                if (!(in_array($_SESSION['wspvars']['workspacelang'],$worklang['languages']['shortcut']))):
                    $_SESSION['wspvars']['workspacelang'] = $worklang['languages']['shortcut'][0];
                endif;
            endif;
            
            $gc_sql = "SELECT * FROM `content_global` WHERE `id` = ".intval($gcid);
            $gc_res = doSQL($gc_sql);

            if ($gc_res['num']>0):
                
                $guid = $gc_res['set'][0]['interpreter_guid'];
                $values = unserializeBroken($gc_res['set'][0]['valuefields']);
            
                $interpreter_sql = "SELECT * FROM `interpreter` WHERE `guid` = '".escapeSQL($guid)."'";
                $interpreter_res = doSQL($interpreter_sql);
                if ($interpreter_res['num']>0):
                    $file = $interpreter_res['set'][0]["parsefile"];
                    $name = $interpreter_res['set'][0]["name"];
                else:
                    $file = 'genericwysiwyg';
                    $name = returnIntLang('hint generic wysiwyg', false);
                endif;
                
                ?>
                <form name="editcontent" id="editcontent" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="op_back" id="op_back" value="" />
                    <input type="hidden" name="op" id="op" value="" />
                    <input type="hidden" name="gcid" id="" value="<?php echo $gcid; ?>" />
                    <input type="hidden" name="interpreter" id="interpreter" value="<?php echo $file; ?>" />
                    <input type="hidden" name="interpreter_guid" id="interpreter_guid" value="<?php echo $guid; ?>" />
                    <div class="row">
                        <div class="col-md-9">
                            <?php 

                            if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".$file)):
                                // read interpreter file
                                require_once DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".$file;

                                $clsInterpreter = new $interpreterClass;
                                
                                if ($clsInterpreter):
                            
                                    $multilangcontent = false; if (property_exists($clsInterpreter, 'multilang')) $multilangcontent = $clsInterpreter -> multilang;
                                    $flexiblecontent = false; if (property_exists($clsInterpreter, 'flexible')) $flexiblecontent =  $clsInterpreter -> flexible;
                            
                                    ?>
                                    <div class="panel">
                                        <div class="panel-heading">
                                            <h3 class="panel-title"><?php echo $name; ?></h3>
                                            <p class="panel-subtitle"><?php echo $guid; ?></p>
                                        </div>
                                        <div class="panel-body">
                                            <?php
                                            
                                            if (!(is_array($multilangcontent)) || !($flexiblecontent)):
                                                echo "<div class='alert alert-danger alert-dismissible' role='alert'>";
                                                echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button>";
                                                echo "<i class='fa fa-warning'></i><p>";
                                                if (!(is_array($multilangcontent))):
                                                    echo returnIntLang('interpreter none multilang', true)." ";
                                                endif;
                                                if (!($flexiblecontent)):
                                                    echo returnIntLang('interpreter non flexible', true)." ";
                                                endif;
                                                echo "</p></div>";
                                            endif;
                                            
                                            if (is_array($multilangcontent)):
                                                foreach($lang AS $lkey => $lvalue):
                                                    if (array_key_exists($lkey, $multilangcontent) && is_array($multilangcontent[$lkey])):
                                                        $lang[$lkey] = array_merge($lang[$lkey], $multilangcontent[$lkey]);
                                                    endif;
                                                endforeach;
                                            endif;
                                            echo $clsInterpreter -> getEdit($values);
                                            ?>

                                        </div>
                                    </div>
                                    <?php 
                                else:
                                    ?>
                                    <div class="panel">
                                        <div class="panel-heading">
                                            <h3 class="panel-title"><?php echo $name; ?></h3>
                                            <p class="panel-subtitle"><?php echo $guid; ?></p>
                                        </div>
                                        <div class="panel-body">
                                            <?php returnIntLang('contentedit class false configured'); ?>
                                        </div>
                                    </div>
                                    <?php
                                endif;
                            else:
                                // parser file not found
                                // use generic text editor

                                $interpreterClass = "genericwysiwyg";

                                ?>
                                <div class="panel">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo $name; ?></h3>
                                        <p class="panel-subtitle"><?php echo $guid; ?></p>
                                    </div>
                                    <div class="panel-body">
                                            <p><?php echo returnIntLang('contentedit generic wysiwyg desc'); ?></p>
                                            <p><input type="text" name="field[desc]" id="field_desc" value="<?php if (isset($values['desc'])): echo prepareTextField($values['desc']); endif; ?>" class="six full" /></p> 
                                            <p><?php echo returnIntLang('contentedit generic wysiwyg content'); ?></p>
                                            <p><textarea name="field[content]" id="field_content" class="form-control summernote" style="min-height: 300px;"><?php if (isset($values['content'])): echo $values['content']; endif; ?></textarea></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                        <?php
                        // sprachbindung
                        if (intval(count($_SESSION['wspvars']['sitelanguages']['shortcut']))>1) { ?>
                            <div class="panel">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><?php echo returnIntLang('globalcontent binding language', true); ?></h3>
                                </div>
                                <div class="panel-body">
                                    <select name="content_lang" class="form-control singleselect fullwidth">
                                        <?php
                                        echo "<option value=\"\">".returnIntLang('globalcontent nobinding', false)."</option>";
                                        foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value) {
                                            if ($gc_res['set'][0]['content_lang']!="") {
                                                echo "<option value='".$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]."' ";
                                                if ($gc_res['set'][0]['content_lang']==$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]) {
                                                    echo " selected='selected' ";
                                                }
                                                echo ">".$_SESSION['wspvars']['sitelanguages']['longname'][$key]."</option>";
                                            }
                                            else {
                                                echo "<option value='".$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]."'>".$worklang['languages']['longname'][$key]."</option>";
                                            }
                                        }
                                        ?>
                                    </select>            
                                </div>
                            </div>
                            <div class="panel">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><?php echo returnIntLang('globalcontent options legend'); ?></h3>
                                </div>
                                <div class="panel-body">
                                    <?php if($gcmres || $gctres): ?><p><?php echo returnIntLang('globalcontent remove from', true); ?></p>
                                    <div class="form-group">
                                        <select name="remove_from" id="remove_from" class="form-control singleselect" onchange="showPlaceGlobal(this.value)">
                                            <option value="0">-</option>
                                            <?php if ($gcmres && count($gcmres)>0): ?><option value="1">content areas</option><?php endif; ?>
                                            <?php if ($gctres && count($gctres)>0): ?><option value="2">templates</option><?php endif; ?>
                                            <?php if($gcmres && $gctres): ?><option value="9">everywhere</option><?php endif; ?>
                                        </select>
                                    </div>
                                    <script>
                                    
                                    function showPlaceGlobal(gV) { if ((gV*1)>0) { $('#placeglobal').slideUp(400); } else { $('#placeglobal').slideDown(400); }}
                                    
                                    </script>
                                    <?php endif; ?>
                                    <div id="placeglobal">
                                        <p><?php echo returnIntLang('globalcontent place into', true); ?></p>
                                        <div class="form-group">
                                        <select name="content_template"  id="content_template" class="form-control singleselect" onchange="showCAreas(this.value)">
                                            <option value="0"><?php echo returnIntLang('globalcontent no placement', false); ?></option>
                                            <?php
                                            
                                            $gctemplate_sql = "SELECT `id`, `name`, `template` FROM `templates`";
                                            $gctemplate_res = doSQL($gctemplate_sql);
                                            if ($gctemplate_res['num']>0):
                                                $cas = array();
                                                echo "<option value=\"999999\">".returnIntLang('globalcontent option all templates', false)."</option>";
                                                foreach ($gctemplate_res['set'] AS $gctrk => $gctrv):
                                                    echo "<option value=\"".intval($gctrv['id'])."\">".$gctrv['name'];
                                                    if ($gctres && count($gctres)>0 && in_array($gctrv['id'], $gctres)): echo " * "; endif;
                                                    echo "</option>";
                                                    $template = $gctrv['template'];
                                                    @preg_match_all("/\[%CONTENTVAR.*%\]/",$template, $mvars);
                                                    if(is_array($mvars)):
                                                        $cas[''.intval($gctrv['id'])] = count($mvars[0]);
                                                    else:
                                                        $cas[''.intval($gctrv['id'])] = 0;
                                                    endif;
                                                endforeach;
                                            endif;

                                            ?>
                                        </select>
                                        </div>
                                        <p><?php echo returnIntLang('globalcontent option contentarea', true); ?></p>
                                        <?php

                                        $tmp_cas = $cas;
                                        sort($tmp_cas);
                                        $cas_min = $tmp_cas[0];
                                        $castring = '';

                                        for($i=0;$i<intval($cas_min);$i++):
                                            $castring .= '<option value="'.($i+1).'" >'.($i+1).'</option>'; 
                                        endfor;

                                        ?>	
                                        <script language="javascript" type="text/javascript">
                                        <!--
                                            function showCAreas(caid) {
                                                var cas = new Array();
                                                <?php
                                                echo "cas['999999'] = " . $cas_min . ";\n";
                                                foreach($cas AS $caskey => $casvalue):
                                                    echo "  cas['".$caskey."'] = ".$casvalue.";\n";
                                                endforeach;
                                                ?>
                                                var countcas = cas[caid];
                                                var castring = '';
                                                for(var i=0;i<countcas;i++) {
                                                    castring = castring + '<option value="' + (i+1) + '" >' + (i+1) + '</option>'; 
                                                }
                                                $('#content_area').html(castring);
                                            }
                                        //-->
                                        </script>
                                        <p><select name="content_area" id="content_area"><?php echo $castring; ?></select></p>
                                        <?php echo returnIntLang('globalcontent option into empty areas only', true); ?>
                                        <p><input type="hidden" name="empty_areas_only" value="0" /><input type="checkbox" name="empty_areas_only" value="1" /></p>
                                        <p><?php echo returnIntLang('globalcontent option only not empty pages', true); ?></p>
                                        <p><input type="hidden" name="active_pages_only" value="0" /><input type="checkbox" name="active_pages_only" value="1" /></p>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <p>
                                <a href="#" onclick="document.getElementById('op_back').value=0; document.getElementById('op').value='save'; document.getElementById('editcontent').submit(); return false;" class="btn btn-success"><?php echo returnIntLang('str save', false); ?></a> 
                                <a href="#" onclick="document.getElementById('op_back').value=1; document.getElementById('op').value='save'; document.getElementById('editcontent').submit();" class="btn btn-primary"><?php echo returnIntLang('btn save and back', false); ?></a>  
                                <?php if($interpreterClass=='text'): ?><a href="globalcontentedit.php?gcid=<?php echo intval($gcid); ?>&op=togeneric" class="orangefield"><?php echo returnIntLang('str text2generic', false); ?></a><?php endif; ?> 
                                <a href="globalcontents.php" class="btn btn-warning"><?php echo returnIntLang('str back', false); ?></a>
                            </p>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require ("./data/panels/editorinit.inc.php"); ?>
<?php require ("./data/include/footer.inc.php");
               
// EOF