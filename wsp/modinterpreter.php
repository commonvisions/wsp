<?php
/**
 * WSP-Modul ausfuehren
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-08-02
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'modinterpreter';
$_SESSION['wspvars']['mgroup'] = 20;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['pagedesc'] = array('fas fa-folder',returnIntLang('modules'),returnIntLang('modules modinterpreter'));
$_SESSION['wspvars']['addpagecss'] = array(
    'summernote-wsp.css',
    'bootstrap-multiselect.css',
    );
$_SESSION['wspvars']['addpagejs'] = array(
    '/summernote/summernote.min.js',
    '/summernote/plugin/br/br.summernote.js',
    '/summernote/plugin/imagemanager/imagemanager.summernote.js',
    '/summernote/plugin/linkmanager/linkmanager.summernote.js',
    '/jquery/jquery.autogrowtextarea.js',
    'bootstrap/bootstrap-multiselect.js',
    );
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$op = checkParamVar('op', '');
$mod = checkParamVar('mod', '');
$modid = checkParamVar('modid', 0);
$mod_sql = 'SELECT w.`link`, w.`parent_id`, w.`guid`, `m`.id FROM `wspmenu` w, `modules` m WHERE w.`module_guid` = m.`guid` && w.`id` = '.intval($modid);
$mod_res = doSQL($mod_sql);
// redefining FPOS
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].";mod=".intval($modid);

if ($mod_res['num']>0) {
	if (intval($mod_res['set'][0]['parent_id'])!=0) {
		$_SESSION['wspvars']['mgroup'] = 20 + intval($mod_res['set'][0]['parent_id']);
	}
    else {
		$_SESSION['wspvars']['mgroup'] = 20 + intval($modid);
	}
	$_SESSION['wspvars']['lockstat'] = $mod_res['set'][0]['guid'];
}

$loadmodule = false;

if ($_SESSION['wspvars']['usertype']!=1 && (!(isset($_SESSION['wspvars']['wspmodmenu'])) || (isset($_SESSION['wspvars']['wspmodmenu']) && !(is_array($_SESSION['wspvars']['wspmodmenu']))) || (isset($_SESSION['wspvars']['wspmodmenu']) && count($_SESSION['wspvars']['wspmodmenu'])<1))) {
    addWSPMsg('errormsg', returnIntLang('modules no modules access allowed'));
}
else if ($_SESSION['wspvars']['usertype']!=1 && (!(array_key_exists($_SESSION['wspvars']['lockstat'], $_SESSION['wspvars']['wspmodmenu'])))) {
    addWSPMsg('errormsg', returnIntLang('modules no access to this module allowed'));
}
else if (($mod_res['num']>0 && !(is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/modules/".$mod_res['set'][0]['link'])))) {
    addWSPMsg('errormsg', returnIntLang('modules no associated contents found'));
}
else if ($mod_res['num']==0) {
    addWSPMsg('errormsg', returnIntLang('modules called wrong id'));
    if (defined('WSP_DEV') && WSP_DEV) {
        addWSPMsg('errormsg', var_export($mod_res, true));
    }
}
else {
    $moddir = explode("/", $mod_res['set'][0]['link']);
    if (trim($moddir[0])!="") {
        // get module config file
        if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/modules/".$moddir[0]."/config.inc.php")) {
            include(DOCUMENT_ROOT."/".WSP_DIR."/data/modules/".$moddir[0]."/config.inc.php");
        }
        if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/modules/".$moddir[0]."/lang.inc.php")) {
            include(DOCUMENT_ROOT."/".WSP_DIR."/data/modules/".$moddir[0]."/lang.inc.php");
            if (is_array($modlang[$_SESSION['wspvars']['locallang']])) {
                $lang[$_SESSION['wspvars']['locallang']] = array_merge($lang[$_SESSION['wspvars']['locallang']], $modlang[$_SESSION['wspvars']['locallang']]);
            }
            else {
                addWSPMsg('noticemsg', returnIntLang('modules not localized language', true));
            }
        }
        else {
            addWSPMsg('noticemsg', returnIntLang('modules not localized file', true));
        }
        // get module funcs file
        if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/modules/".$moddir[0]."/funcs.inc.php")) {
            include(DOCUMENT_ROOT."/".WSP_DIR."/data/modules/".$moddir[0]."/funcs.inc.php");
        }
    }
    $loadmodule = cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/data/modules/".$mod_res['set'][0]['link']);
    if (trim($lang[$_SESSION['wspvars']['locallang']]['modinterpreter modlanginfo']=='')) {
        $lang[$_SESSION['wspvars']['locallang']]['modinterpreter modlanginfo'] = cleanPath('/'.WSP_DIR.'/data/modules/'.$mod_res['set'][0]['link']);
    }
    if (!(is_file($loadmodule))) {
        addWSPMsg('errormsg', returnIntLang('modules no associated contents found', true));
    }
}

// affected content
/*

alle menüpunkte mit inhalten, die diese guid's als interpreter haben, müssen auf contentchanged = x gesetzt werden, damit sie im publisher als "zu veröffentlichen" gekennzeichnet sind, wenn im modul auf speichern geklickt wird 

*/
if ($loadmodule && isset($updatecontent) && $updatecontent===true) {
    // will only work if module could be loaded AND some data was sent with $updatecontent-option set to true
    
    $aff_sql = "SELECT `dynamiccontent` FROM `modules` WHERE `id` = ".intval($mod_res['set'][0]['id']);
    $aff_res = doSQL($aff_sql);
    $aff_tables = array();
    if ($aff_res['num']>0) {
        foreach($aff_res['set'] AS $ask => $asv) {
            $aff_tables = unserializeBroken($asv['dynamiccontent']);
        }
    }
    if (count($aff_tables)>0) {
        $plug_sql = array();
        foreach ($aff_tables AS $atk => $atv) {
            $plug_sql[] = " `pluginconfig` LIKE '%".escapeSQL(trim($atk))."%' ";
        }
        // get menupoints that use ONE or MORE of dynamic table connections
        $dynaff_sql = "SELECT `mid`, `description`, `pluginconfig` FROM `menu` WHERE (".implode(" OR ", $plug_sql).") AND `trash` = 0 AND `editable` = 9";
        $dynaff_res = doSQL($dynaff_sql);
        // run the updates
        foreach ($dynaff_res['set'] AS $dsk => $dsv) {
            $outputrule = array(true, false);
            $config = unserializeBroken($dsv['pluginconfig']);
            $dynamiccontent[intval($dsv['mid'])] = createDynamicMenu(intval($dsv['mid']), $config, $outputrule[intval($config['outputrule'])], false);
        }
        if (isset($dynamiccontent) && is_array($dynamiccontent)) {
            $dynamicinfo = 0;
            foreach ($dynamiccontent AS $dck => $dcv) {
                $dynamicinfo = (isset($dcv['posvalues'])?$dynamicinfo+intval($dcv['posvalues']):$dynamicinfo);
            }
        }
        
        if ($dynamicinfo>0) {
            addWSPMsg('resultmsg', returnIntLang('modinterpreter dynamic changes1')." ".$dynamicinfo." ".returnIntLang('modinterpreter dynamic changes2'));
        } else {
            addWSPMsg('noticemsg', returnIntLang('modinterpreter no dynamic changes'));
        }
    }
}

// head der datei
require ("./data/include/header.inc.php");
require ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");

?>
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('modinterpreter headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('modinterpreter modlanginfo'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <!-- <?php if (isset($dynamiccontent)) { echo "<pre>".var_export($dynamiccontent,true)."</pre>"; } ?> -->
            <?php if ($loadmodule && is_file($loadmodule)) { include ($loadmodule); } ?>
        </div>
    </div>
</div>

<?php require_once ("./data/panels/editorinit.inc.php"); ?>
<?php include_once ("./data/include/footer.inc.php");
            
// EOF