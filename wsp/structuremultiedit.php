<?php
/**
 * edit menupoints details
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-03-13
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// checkParamVar -----------------------------
$mid = 0;
$op = '';
if (!(array_key_exists('wspvars', $_SESSION) && array_key_exists('editmenuid', $_SESSION['wspvars']) && is_array($_SESSION['wspvars']['editmenuid']) && count($_SESSION['wspvars']['editmenuid'])>0)) {
    header('location: structure.php');
}
// checking for operation --------------------
if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="edit") {
	$op = "edit";
}
if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="save") {
	$op = "save";
}
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'sitestructure';
$_SESSION['wspvars']['pagedesc'] = array('fa fa-sitemap',returnIntLang('menu content'),returnIntLang('menu content structure multiedit'));
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].";mid=".$mid;
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = true;
$_SESSION['wspvars']['addpagecss'] = array(
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'jquery/jquery.timepicker.js',
    'jquery/jquery.autogrowtextarea.js'
    );

/* second includes --------------------------- */
require ("data/include/checkuser.inc.php");
require ("data/include/siteinfo.inc.php");

/* define page specific vars ----------------- */
$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
/* page specific funcs and actions */
if ($op=='save') {
    $updated = 0;
    foreach ($_POST['mid'] AS $mk => $mv) {
        // update contentchanged info
        $nccres = contentChangeStat(intval($mv), "structure");
        // update query		
        $menuupdate_sql = "UPDATE `menu` SET ";
//      $menuupdate_sql.= "`editable` = ".intval($_POST['editable']).", "; unset($_POST['editable']);
        $menuupdate_sql.= "`templates_id` = ".intval($_POST['template']).", ";
        $menuupdate_sql.= "`contentchanged` = ".intval($nccres).", ";
        $menuupdate_sql.= "`changetime` = '".time()."', ";
        if (array_key_exists('usejs', $_POST) && is_array($_POST['usejs'])):
            $menuupdate_sql.= "`addscript` = '".escapeSQL(serialize($_POST['usejs']))."', ";
        else:
            $menuupdate_sql.= "`addscript` = '', ";	
        endif;
        if (array_key_exists('usecss', $_POST) && is_array($_POST['usecss'])):
            $menuupdate_sql.= "`addcss` = '".escapeSQL(serialize($_POST['usecss']))."', ";
        else:
            $menuupdate_sql.= "`addcss` = '', ";
        endif;
        $menuupdate_sql.= "`addclass` = '".escapeSQL(trim($_POST['useclass']))."', ";
        $menuupdate_sql.= "`weekday` = ".intval(array_sum($_POST['weekday'])).", ";
        $menuupdate_sql.= "`mobileexclude` = ".intval($_POST['mobileexclude']).", ";
//      if (array_key_exists('loginuser', $_POST) && is_array($_POST['loginuser'])):
//          $menuupdate_sql.= "`logincontrol` = '".mysql_real_escape_string(serialize($_POST['loginuser']))."', "; unset($_POST['loginuser']);
//      else:
//          $menuupdate_sql.= "`logincontrol` = '', ";	
//      endif;
        $menuupdate_sql.= "`login` = ".intval($_POST['logincontrol'])." ";
        $menuupdate_sql.= " WHERE `mid` = ".intval($mv);
        $menuupdate_res = doSQL($menuupdate_sql);
        if ($menuupdate_res['aff']==1):
            $updated++;
        else:
            addWSPMsg('errormsg', returnIntLang('menuedit error updating menupoint1').intval($mv).returnIntLang('menuedit error updating menupoint2'));
            if (WSP_DEV) { addWSPMsg('errormsg', '<p>'.$menuupdate_sql.'</p>'); }
        endif;

        // update contentchanged var to all related pages
        $relmid = array_merge(returnIDRoot($mv),returnIDTree($mv));
        if (is_array($relmid) && count($relmid)>0):
            foreach($relmid AS $rk => $rv):
                $ccres = intval(doResultSQL("SELECT `contentchanged` FROM `menu` WHERE `contentchanged` != 1 && `contentchanged` != 3 && `mid` = ".intval($rv)));
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
    }
    if ($updated==count($_POST['mid'])):
        addWSPMsg('resultmsg', returnIntLang('menuedit menupoints successfully updated', false));
    endif;
};

if (isset($_POST) && array_key_exists('backjump', $_POST) && $_POST['backjump']=="back") {
	$_SESSION['wspvars']['editmenuid'] = false;
	header('location: structure.php');
}

$menudetails_sql = "SELECT * FROM `menu` WHERE `mid` IN (".implode(",", $_SESSION['wspvars']['editmenuid']).")";
$menudetails_res = doSQL($menudetails_sql);

if ($menudetails_res['num']==0): 
    header ('location: structure.php');
else:
    $menueditdata = array(
        'visibility' => array(),
        'real_templates_id' => array(),
        'editable' => array(),
        'mobileexclude' => array(),
        'lockpage' => array(),
        'addcss' => array(),
        'addclass' => array(),
        'addscript' => array(),
        'weekday' => array(),
        'login' => array(),
    );
    foreach ($menudetails_res['set'] AS $mdrsk => $mdrsv) {
        $menueditdata['visibility'][] = $mdrsv['visibility'];
        $menueditdata['editable'][] = $mdrsv['editable'];
        $menueditdata['mobileexclude'][] = $mdrsv['mobileexclude'];
        $menueditdata['lockpage'][] = $mdrsv['lockpage'];
        $menueditdata['real_templates_id'][] = getTemplateID(intval($mdrsv['templates_id']));
        $menueditdata['addcss'][] = $mdrsv['addcss'];
        $menueditdata['addscript'][] = $mdrsv['addscript'];
        $menueditdata['addclass'][] = $mdrsv['addclass'];
        $menueditdata['weekday'][] = $mdrsv['weekday'];
    }
    foreach ($menueditdata AS $medk => $medv) {
        $menueditdata[$medk] = array_unique($medv);
        $menueditdata[$medk] = array_values($menueditdata[$medk]);
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
                    <?php echo returnIntLang('structure multiedit headline'); ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo returnIntLang('structure multiedit info'); ?>
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
                                <div class="row">
                                    <div class="col-md-10">
                                        <p><?php echo returnIntLang('structure edit generell show', true); ?></p>
                                    </div>
                                    <div class="col-md-2 text-right">
                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                            <input type="hidden" name="visibility" value="0" /><input name="visibility" type="checkbox" value="1" <?php if (count($menueditdata['visibility'])==1 && intval($menueditdata['visibility'][0])==1) echo " checked='checked' " ; ?> onchange="document.getElementById('cfc').value = 1;" /><span>&nbsp;</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <p>
                                            <?php echo returnIntLang('structure exclude mobile'); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-2 text-right">
                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                            <input type="hidden" name="mobileexclude" value="0" /><input type="checkbox" name="mobileexclude" id="mobileexclude" value="1" <?php if(count($menueditdata['mobileexclude'])==1 && intval($menueditdata['mobileexclude'][0])==1) echo " checked='checked' "; ?> onchange="document.getElementById('cfc').value = 1;"><span>&nbsp;</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <p>
                                            <?php echo returnIntLang('structure edit generell block', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-2 text-right">
                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                            <input type="hidden" name="editable" value="0" /><input name="editable" type="checkbox" value="1" <?php if (count($menueditdata['editable'])==1 && intval($menueditdata['editable'][0])==1) echo " checked='checked' " ; ?> onchange="document.getElementById('cfc').value = 1;" /><span>&nbsp;</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <p>
                                            <?php if (isset($_SESSION['wspvars']['bindcontentview']) && intval($_SESSION['wspvars']['bindcontentview'])==1):
                                                echo returnIntLang('structure show content even menu inactive');
                                            else:
                                                echo returnIntLang('structure hide content when menu inactive');
                                            endif; ?> ???
                                        </p>
                                    </div>
                                    <div class="col-md-2 text-right">
                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                            <?php if (isset($_SESSION['wspvars']['bindcontentview']) && intval($_SESSION['wspvars']['bindcontentview'])>0):
                                            ?><input type="hidden" name="lockpage" value="0" /><input type="checkbox" name="lockpage" value="1" <?php if(isset($menueditdata['lockpage']) && count($menueditdata['lockpage'])==1 && intval($menueditdata['lockpage'][0])==1): echo " checked='checked' " ; endif; ?> />
                                            <?php
                                        else:
                                            ?><input type="hidden" name="lockpage" value="0" /><input type="checkbox" name="lockpage" value="1" <?php if(isset($menueditdata['lockpage']) && count($menueditdata['lockpage'])==1 && intval($menueditdata['lockpage'][0])==1): echo " checked='checked' " ; endif; ?> />
                                            <?php 
                                        endif; ?><span>&nbsp;</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <?php echo returnIntLang('structure templatename', true); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <select name="template" id="template" class="form-control singleselect">
                                            <option value="-1">
                                                <?php echo returnIntLang('structure pleasechoosetemplate', true); ?>
                                            </option>
                                            <option value="0" <?php if (count($menueditdata['real_templates_id'])==1 && intval($menueditdata['real_templates_id'])==0): echo ' selected="selected"' ; endif; ?>>
                                                <?php echo returnIntLang('structure chooseuppertemplate', true); ?>
                                            </option>
                                            <?php

                                            $templates_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
                                            $templates_res = doSQL($templates_sql);
                                            if ($templates_res['num']>0) {
                                                foreach ($templates_res['set'] AS $trsk => $trsv) {
                                                    echo "<option value='".intval($trsv['id'])."' ";
                                                    // select if all selected mids use same template
                                                    if (count($menueditdata['real_templates_id'])==1 && intval($menueditdata['real_templates_id'][0])==intval($trsv['id'])): echo ' selected="selected"'; endif;
                                                    echo ">";
                                                    echo $trsv['name'];
                                                    // little markup, if template is in the set of used templates    
                                                    if (count($menueditdata['real_templates_id'])!=1 && in_array($trsv['id'], $menueditdata['real_templates_id'])) { echo " - "; }
                                                    echo "</option>";
                                                }
                                            }

                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-tab">
                            <div class="panel-heading">
                                <ul class="nav nav-tabs pull-left">
                                    <li class="active"><a href="#specialviewtime" data-toggle="tab"><i class="fa fa-clock-o"></i>
                                        <?php echo returnIntLang('structure special view time', true); ?></a></li>
                                    <li><a href="#specialviewuser" data-toggle="tab"><i class="fa fa-user-o"></i>
                                        <?php echo returnIntLang('structure special view user', true); ?></a></li>
                                    <li><a href="#addons" data-toggle="tab"><i class="fa fa-gears"></i>
                                        <?php echo returnIntLang('structure edit addon', true); ?></a></li>
                                </ul>
                                <h3 class="panel-title">&nbsp;</h3>
                            </div>
                            <div class="panel-body">
                                <div class="tab-content no-padding">
                                    <div class="tab-pane fade in active" id="specialviewtime">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p>
                                                    <?php echo returnIntLang('structure special view description', true); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><?php echo returnIntLang('structure daily based view'); ?><input type="hidden" name="weekday[0]" value="0" /></p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <?php
                                                
                                                if (count($menueditdata['weekday'])==1) {
                                                    $showday = intval($menueditdata['weekday'][0]);
                                                    for ($sd=6;$sd>=0;$sd--) {
                                                        if ($showday-pow(2,$sd)>=0) {
                                                            $weekdayvalue[($sd+1)] = " checked=\"checked\" ";
                                                            $showday = $showday-(pow(2,$sd));
                                                        } else {
                                                            $weekdayvalue[($sd+1)] = "";
                                                        }
                                                    }
                                                }
                                                else {
                                                    for ($sd=6;$sd>=0;$sd--) {
                                                        $weekdayvalue[($sd+1)] = "";
                                                    }
                                                }
			

                                                ?>
                                                <label class="fancy-checkbox custom-bgcolor-blue nowrap"><input type="checkbox" name="weekday[1]" id="weekday_1" value="1" <?php echo $weekdayvalue[1]; ?> /> <span><?php echo returnIntLang('str monday'); ?></span></label> 
                                                <label class="fancy-checkbox custom-bgcolor-blue nowrap"><input type="checkbox" name="weekday[2]" id="weekday_2" value="2" <?php echo $weekdayvalue[2]; ?> /> <span><?php echo returnIntLang('str tuesday'); ?></span></label> 
                                                <label class="fancy-checkbox custom-bgcolor-blue nowrap"><input type="checkbox" name="weekday[3]" id="weekday_3" value="4" <?php echo $weekdayvalue[3]; ?> /> <span><?php echo returnIntLang('str wednesday'); ?></span></label> 
                                                <label class="fancy-checkbox custom-bgcolor-blue nowrap"><input type="checkbox" name="weekday[4]" id="weekday_4" value="8" <?php echo $weekdayvalue[4]; ?> /> <span><?php echo returnIntLang('str thursday'); ?></span></label> 
                                                <label class="fancy-checkbox custom-bgcolor-blue nowrap"><input type="checkbox" name="weekday[5]" id="weekday_5" value="16" <?php echo $weekdayvalue[5]; ?> /> <span><?php echo returnIntLang('str friday'); ?></span></label> 
                                                <label class="fancy-checkbox custom-bgcolor-blue nowrap"><input type="checkbox" name="weekday[6]" id="weekday_6" value="32" <?php echo $weekdayvalue[6]; ?> /> <span><?php echo returnIntLang('str saturday'); ?></span></label> 
                                                <label class="fancy-checkbox custom-bgcolor-blue nowrap"><input type="checkbox" name="weekday[7]" id="weekday_7" value="64" <?php echo $weekdayvalue[7]; ?> /> <span><?php echo returnIntLang('str sunday'); ?></span></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade in" id="specialviewuser">
                                        <div class="row">
                                            <div class="col-md-10">
                                                <p><?php echo returnIntLang('structure login control'); ?></p>
                                            </div>
                                            <div class="col-md-2 text-right">
                                                <label class="fancy-checkbox custom-bgcolor-blue">
                                                    <input type="hidden" name="logincontrol" value="0" /><input name="logincontrol" type="checkbox" value="1" <?php if(count($menueditdata['login'])==1 && intval($menueditdata['login'][0])==1) { echo " checked=' checked' "; } ?> onchange="document.getElementById('cfc').value = 1;" /><span>&nbsp;</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade in" id="addons">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><?php echo returnIntLang('structure edit addon jsfiles', true); ?></p>
                                            </div>
                                            <div class="col-md-12">
                                                <?php

                                                if (count($menueditdata['addscript'])==1) {
                                                    $extrajs = unserializeBroken($menueditdata['addscript'][0]);
                                                } else {
                                                    $extrajs = array();
                                                }
                                                $jsuse_sql = "SELECT `javascript_id` FROM `r_temp_jscript` WHERE `templates_id` = '".intval($menueditdata['real_templates_id'])."'";
                                                $jsuse_res = doSQL($jsuse_sql);
                                                if ($jsuse_res['num']>0) {
                                                    $usejs = array();
                                                    foreach ($jsuse_res['set'] AS $jsusek => $jsusev) {
                                                        $usejs[] = " `id` != ".intval($jsusev['javascript_id']);
                                                    }
                                                    $js_sql = "SELECT `id`, `describ` FROM `javascript` WHERE ".implode(" AND ", $usejs);
                                                }
                                                else {
                                                    $js_sql = "SELECT `id`, `describ` FROM `javascript`";
                                                }
                                                $js_res = doSQL($js_sql);
                                                if ($js_res['num']>0) {
                                                    foreach ($js_res['set'] AS $jsk => $jsv) {
                                                        echo "<label class='fancy-checkbox custom-bgcolor-blue'><input type=\"checkbox\" id=\"addjs_check_".$jsk."\" name=\"usejs[]\" value=\"".intval($jsv['id'])."\" ";
                                                        if (is_array($extrajs) && in_array(intval($jsv['id']), $extrajs)):
                                                            echo " checked=\"checked\"";
                                                        endif;
                                                        echo " /> <span>".trim($jsv['describ'])."</span></label>";
                                                    }
                                                }
                                                else {
                                                    echo returnIntLang('structure edit all js-files used in templage');
                                                }

                                                ?>
                                            </div>
                                            <div class="col-md-12">
                                                <p><?php echo returnIntLang('structure edit addon cssfiles', true); ?></p>
                                            </div>
                                            <div class="col-md-12">
                                                <?php

                                                if (count($menueditdata['addcss'])==1) {
                                                    $extracss = unserializeBroken($menueditdata['addcss']);
                                                } else {
                                                    $extracss = array();
                                                }
                                                $cssuse_sql = "SELECT `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = '".intval($menueditdata['real_templates_id'])."'";
                                                $cssuse_res = doSQL($cssuse_sql);
                                                if ($cssuse_res['num']>0):
                                                    $usecss = array();
                                                    foreach ($cssuse_res['set'] AS $cssusek => $cssusev) {
                                                        $usecss[] = " `id` != ".intval($cssusev['stylesheets_id']);
                                                    }
                                                    $css_sql = "SELECT `id`, `describ` FROM `stylesheets` WHERE ".implode(" AND ", $usecss);
                                                else:
                                                    $css_sql = "SELECT `id`, `describ` FROM `stylesheets`";
                                                endif;
                                                $css_res = doSQL($css_sql);
                                                if ($css_res['num']>0):
                                                    foreach ($css_res['set'] AS $cssk => $cssv) {
                                                        echo "<label class='fancy-checkbox custom-bgcolor-blue'><input type=\"checkbox\" id=\"addcss_check_".$cssk."\" name=\"usecss[]\" value=\"".intval($cssv['id'])."\" ";
                                                        if (is_array($extracss) && in_array(intval($cssv['id']), $extracss)):
                                                            echo " checked=\"checked\"";
                                                        endif;
                                                        echo " /> <span>".trim($cssv['describ'])."</span></label> ";
                                                    }
                                                else:
                                                    echo returnIntLang('structure edit all css-files used in template');
                                                endif;

                                                ?>
                                            </div>
                                            <div class="col-md-12">
                                                <p><?php echo returnIntLang('structure edit addon cssclass', false); ?></p>
                                            </div>
                                            <div class="col-md-12">
                                                <p><input type="text" name="useclass" value="<?php if (count($menueditdata['addclass'])==1) { echo $menueditdata['addclass'][0]; } ?>" class="form-control" placeholder="<?php if (count($menueditdata['addclass'])<2) { echo returnIntLang('structure edit addon cssclass', false); } else { echo implode(" ", $menueditdata['addclass']); } ?>" /></p>
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
                        <?php foreach($_SESSION['wspvars']['editmenuid'] AS $emik => $emiv) { echo "<input type='hidden' name='mid[]' value='".$emiv."' />"; } ?>
                        <input name="backjump" id="backjump" type="hidden" value="" />
                        <input name="op" id="ophidden" type="hidden" value="" />
                        <p><a onclick="saveMenuEdit(false);" class="btn btn-primary">
                            <?php echo returnIntLang('str save', true); ?></a> <a onclick="saveMenuEdit(true);" class="btn btn-primary">
                            <?php echo returnIntLang('btn save and back', true); ?></a> <a href="structure.php" class="btn btn-warning">
                            <?php echo returnIntLang('str back', true); ?></a></p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">

    function saveMenuEdit(backjump) {
        if (backjump) {
            $('#backjump').val('back');
        }
        $('#cfc').val('0');
        $('#ophidden').val('save');
        $('#frmmenudetail').submit();
    }

</script>

<?php endif; ?>
<?php require ("./data/include/footer.inc.php"); ?>