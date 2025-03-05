<?php
/**
 * Modulverwaltung
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2020-04-30
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'modules';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-cogs',returnIntLang('menu manage'),returnIntLang('menu manage modules'));
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* page specific includes */
require ("./data/include/clssetup.inc.php");
require ("./data/include/clsinterpreter.inc.php");
/* define page specific vars ----------------- */

/* define page specific functions ----------------- */

if (isset($_POST['op']) && $_POST['op']=="setrights") {
    if (isset($_POST['setrights'])) {
        $aff = 0;
        foreach ($_POST['setrights'] AS $pgk => $pgv) {
            $sql = "DELETE FROM `wsprights` WHERE `guid` = '".escapeSQL($pgk)."'";
            doSQL($sql);
            if (intval($pgv)==1) {
                $possibilities = array(1,0);
                $labels = array(returnIntLang('str yes'),returnIntLang('str no'));
                $sql = "INSERT INTO `wsprights` SET 
                    `guid` = '".escapeSQL($pgk)."',
                    `description` = '".escapeSQL(trim($_POST['modname'][$pgk]))."',
                    `standard` = '1',
                    `options` = '".escapeSQL(serialize($possibilities))."',
                    `labels` = '".escapeSQL(serialize($labels))."'";
                if (getAffSQL($sql)>0) {
                    $aff++;
                }
            }
        }
        if ($aff>0) { addWSPMsg('resultmsg', returnIntLang('moddetails updated rights')); }
    }
}
	
if (isset($_POST['op']) && $_POST['op']=="setaffects") {
    $affectedcontent = array();
    if (isset($_POST['affects'])) {
        foreach ($_POST['affects'] AS $table => $fields) {
            foreach ($fields AS $fk => $fv) {
                if ($fv==1): $affectedcontent[$table][] = $fk; endif;
            }
        }
    }
    $sql = "UPDATE `modules` SET `affectedcontent` = '".((count($affectedcontent)>0)?escapeSQL(serialize($affectedcontent)):NULL)."' WHERE `guid` = '".escapeSQL(base64_decode($_POST['mk']))."'";
    if (getAffSQL($sql)>0) { addWSPMsg('resultmsg', returnIntLang('moddetails updated affected fields')); }
}

if (isset($_POST['op']) && $_POST['op']=="setdynamic") {
    $dynamiccontent = array();
    if (isset($_POST['dynamic'])) {
        foreach ($_POST['dynamic'] AS $table => $fields) {
            foreach ($fields AS $fk => $fv) {
                if ($fv==1) { $dynamiccontent[$table][] = $fk; }
            }
        }
    }
    $sql = "UPDATE `modules` SET `dynamiccontent` = '".((count($dynamiccontent)>0)?escapeSQL(serialize($dynamiccontent)):NULL)."' WHERE `guid` = '".escapeSQL(base64_decode($_POST['mk']))."'";
    if (getAffSQL($sql)>0) { addWSPMsg('resultmsg', returnIntLang('moddetails updated dynamic fields')); }
}

if (isset($_POST['op']) && $_POST['op']=="removemod" && trim($_POST['mk'])!='') {
    $success = true;
    $guid = base64_decode($_POST['mk']);
    $dep_res = doSQL("SELECT `id` FROM `modules` WHERE `dependencies` LIKE '%".escapeSQL($guid)."%'");
    if ($dep_res['num']>0) {
        addWSPMsg('noticemsg', returnIntLang('modules cannot remove module because of dependencies1').$dep_res['num'].returnIntLang('modules cannot remove module because of dependencies2'));
        $success = false;
    }
    // if no dependencies » remove it  
    if ($success) {
        // find all interpreter associated with this module
        $int_res = doSQL("SELECT `name`, `guid` FROM `interpreter` WHERE `module_guid` = '".escapeSQL($guid)."'");
        if ($int_res['num']>0) {
            foreach ($int_res['set'] AS $ik => $iv) {
                // set all contents to trash where interpreter was removed
                doSQL("UPDATE `content` SET `trash` = 1 WHERE `trash` = 0 AND `interpreter_guid` = '".escapeSQL($iv['guid'])."'");
                // delete interpreter
                doSQL("DELETE FROM `interpreter` WHERE `guid` = '".escapeSQL($iv['guid'])."'");
                addWSPMsg('resultmsg', returnIntLang('modules removed interpreter1').trim($iv['name']).returnIntLang('modules removed interpreter1'));
            }
        }
        // find menus !?!?!?
        
        
        
        
        // finally remove module
        $mod_res = doSQL("SELECT `name` FROM `modules` WHERE `guid` = '".escapeSQL($guid)."'");
        $del_res = doSQL("DELETE FROM `modules` WHERE `guid` = '".escapeSQL($guid)."'");
        if ($del_res['aff']==1) {
            addWSPMsg('resultmsg', returnIntLang('modules removed module1').trim($mod_res['set'][0]['name']).returnIntLang('modules removed module2'));
            header ('location: ./modules.php');
        }
    }
}

// jump to content from usage area
if (isset($_POST['editcontentid']) && intval($_POST['editcontentid'])>0) {
	$_SESSION['wspvars']['editcontentid'] = intval($_POST['editcontentid']);
	header('location: contentedit.php');
	die();
}

// get information about selected module
$modules_sql = "SELECT * FROM `modules` WHERE `guid` = '".escapeSQL(base64_decode($_REQUEST['mk']))."' ORDER BY `name`";
$modules_res = doSQL($modules_sql);

// get update information from server
$serverversion = $servertag = $serverfile = array();
$values = false;
$xmldata = '';
$defaults = array( 
    CURLOPT_URL => 'https://'.WSP_UPDSRV.'/versions/modules/', 
    CURLOPT_HEADER => 0, 
    CURLOPT_RETURNTRANSFER => TRUE, 
    CURLOPT_TIMEOUT => 4 
);
$ch = curl_init();
curl_setopt_array($ch, $defaults);    
if( ! $xmldata = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
curl_close($ch);
$xml = xml_parser_create();
xml_parse_into_struct($xml, $xmldata, $values, $index);
$i = 0;

foreach ($values as $file) {
    if ($file['tag']=='VERSION') {
        $serverversion[$i] = $file['value'];
        if (strpos($serverversion[$i], '.')===false) {
            $serverversion[$i] = $serverversion[$i].".0";
        }
    }
    if ($file['tag']=='FILE') {
        if (intval(strpos($file['value'], '#'))>0) {
            $servertag[$i] = explode("#", str_replace("/updater/media/modules/", "", cleanPath($file['value'])))[0];
        }
        else {
            $servertag[$i] = explode("-", str_replace("/updater/media/modules/", "", cleanPath($file['value'])))[0];
        }
        $serverfile[$i] = cleanPath($file['value']);
        // because FILE is the last entry in set, we count $i on that place
        $i++;
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
        <?php if ($modules_res['num']>0): ?>
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('moddetails headline'); ?></h1>
                <p class="page-subtitle"><?php echo trim($modules_res['set'][0]['name']." ".$modules_res['set'][0]['version'])." [".$modules_res['set'][0]['guid']."]"; ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('moddetails info'); ?></h3>
                            <?php
                            
                            $serverdata = isset($modules_res['set'][0]['tag'])?array_keys($servertag, trim($modules_res['set'][0]['tag'])):false;
                            if (is_array($serverdata) && count($serverdata)==1) {
                                if ($serverversion[intval($serverdata[0])]!=$modules_res['set'][0]['version']) {
                                    $diff = compareVersion($modules_res['set'][0]['version'], $serverversion[intval($serverdata[0])]);
                                    if ($diff>0) {
                                        echo '<div class="right">';
                                        echo '<p onclick="$(\'#installform\').submit();" style="cursor: pointer;"><i class="fas fa-cloud-download-alt"></i> '.returnIntLang('modules update avaiable').'</p>';
                                        echo '<form method="post" action="./modinstall.php" id="installform">';
                                        echo '<input type="hidden" name="serverfile" value="'.$serverfile[intval($serverdata[0])].'" />';
                                        echo '<input type="hidden" name="op" id="op" value="modcheckinstall">';
                                        echo '</form>';
                                        echo '</div>';
                                    }
                                }
                            }
                            
                            ?>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-4 col-lg-3 text-center">
                                    <?php
                                    
                                    if ($modules_res['set'][0]['isparser']==1):
                                        echo '<pre class="file-preview pdf"><br /><i class="fas fa-external-link-square-alt" style="border-radius: 10px; padding: 10px; font-size: 24px;"></i><br />&nbsp;</pre>';
                                    elseif ($modules_res['set'][0]['iscmsmodul']==1):
                                        echo '<pre class="file-preview xls"><br /><i class="fas fa-cogs" style="border-radius: 10px; padding: 10px; font-size: 24px;"></i><br />&nbsp;</pre>';
                                    endif;
                                    
                                    $type = array();
                                    if ($modules_res['set'][0]['isparser']==1): $type[] = returnIntLang('str interpreter'); endif; 
                                    if ($modules_res['set'][0]['iscmsmodul']==1): $type[] = returnIntLang('str module'); endif;
                                    
                                    ?>
                                </div>
                                <div class="col-md-8 col-lg-9"><h3><?php echo $modules_res['set'][0]['name']; ?><br />
                                    <?php echo $modules_res['set'][0]['version']; ?> <span style="font-size: 0.55em; font-weight: 400;">[<?php echo implode(', ', $type); ?>]</span></h3></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('moddetails files'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-3"><?php echo returnIntLang('moddetails filearchive'); ?></div>
                                <div class="col-md-9"><?php echo $modules_res['set'][0]['archive']; ?></div>
                            </div>
                            <hr />
                            <div class="row">
                                <div class="col-md-3"><?php echo returnIntLang('moddetails filelist'); ?></div>
                                <div class="col-md-9"><?php
                                    
                                $filelist = unserializeBroken($modules_res['set'][0]['filelist']);    
                                if (!($filelist) && trim($modules_res['set'][0]['filelist'])!='') {
                                    $filelist = explode(PHP_EOL, trim($modules_res['set'][0]['filelist']));
                                }
                                if (is_array($filelist)) {
                                    foreach($filelist AS $flk => $flv) {
                                        if (substr(trim($flv),-4)=='.sql') {
                                            echo returnIntLang('moddetails filelist dbfile')."<br />";
                                        }
                                        else if (trim($flv)=='/database.xml') {
                                            echo returnIntLang('moddetails filelist dbfile xml')."<br />";
                                        }
                                        else if (trim($flv)=='/setup.php') {
                                            // setup will not be output
                                        }
                                        else {
                                            if (substr(trim($flv),0,14)=='/data/modules/') {
                                                echo returnIntLang('moddetails filelist data').substr(trim($flv),13)."<br />";
                                            }
                                            else if (substr(trim($flv),4,18)=='/data/interpreter/') {
                                                echo returnIntLang('moddetails filelist interpreter').substr(trim($flv),21)."<br />";
                                            }
                                            else if (substr(trim($flv),4,14)=='/data/modules/') {
                                                echo returnIntLang('moddetails filelist modules').substr(trim($flv),17)."<br />";
                                            }
//                                          else {
//                                              echo $flv."<br />";
//                                          }
                                        }
                                    }
                                }
                                else {
                                    echo "<p>".returnIntLang('moddetails filelist unknown')."</p>";
                                }
                                    
                                ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                
                $itp_set = array();
                
                // get associated interpreter for module 
                $itp_sql = "SELECT `name`, `version`, `parsefile`, `guid` FROM `interpreter` WHERE `module_guid` = '".escapeSQL($modules_res['set'][0]['guid'])."'";
                $itp_res = doSQL($itp_sql);

                foreach($itp_res['set'] AS $sk => $sv) {
                    $itp_set[] = $sv['guid'];
                }
                
                if ($itp_res['num']>0) { ?>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('moddetails accoc interpreter'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-12">
                                    <?php foreach($itp_res['set'] AS $sk => $sv) {
                                        echo '<p>'.$sv['name'].' '.$sv['version'].'</p>';
                                    } ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php }
                
                // get accociated menu entries for module
                            
                $mnu_sql = "SELECT * FROM `wspmenu` WHERE `module_guid` = '".escapeSQL($modules_res['set'][0]['guid'])."' ORDER BY `parent_id` ASC";
                $mnu_res = doSQL($mnu_sql);

                if ($mnu_res['num']>0) { ?>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('moddetails rights'); ?></h3>
                                <p class="panel-subtitle"><?php echo returnIntLang('modrights rights legend'); ?></p>
                            </div>
                            <div class="panel-body">
                                <form name="setrightsform" id="setrightsform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                    <?php if ($mnu_res['num']>1) {
                                        $checkid = array();
                                        for ($mres=1; $mres<$mnu_res['num']; $mres++) {
                                            $checkid[] = "$('#setrights_".intval($mnu_res['set'][$mres]['id'])."').prop('checked'";
                                        }
                                        ?>
                                    <script>

                                        function checkParent(parentid, param) { 
                                            if (param==1 && $('#setrights_' + parentid).prop('checked')) { 
                                                <?php foreach ($checkid AS $value) {
                                                    echo " ".$value.",true); "; 
                                                } ?> } 
                                            else if (param==1 && !($('#setrights_' + parentid).prop('checked'))) {
                                                <?php foreach ($checkid AS $value) { 
                                                    echo " ".$value.",false); "; 
                                                } ?> 
                                            } 
                                            else { 
                                                if (<?php echo implode(") || ", $checkid); ?>) { 
                                                    $('#setrights_' + parentid).prop('checked', true);
                                                } 
                                                else { 
                                                    $('#setrights_' + parentid).prop('checked', false);
                                                    <?php foreach ($checkid AS $value) { echo " ".$value.",false);"; } ?> 
                                                }
                                            }
                                        };

                                    </script>
                                    <?php } ?>
                                    <?php for ($mres=0; $mres<$mnu_res['num']; $mres++):

                                        $rights_sql = "SELECT * FROM `wsprights` WHERE `guid` = '".escapeSQL(trim($mnu_res['set'][$mres]['guid']))."'";
                                        $rights_res = doSQL($rights_sql);

                                        ?>
                                        <div class="row">
                                            <div class="col-md-3"><p><?php echo ((intval($mnu_res['set'][$mres]['parent_id'])>0)?"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$mnu_res['set'][$mres]['title']:$mnu_res['set'][$mres]['title']); ?></p></div>
                                            <div class="col-md-2"><p><input type="hidden" name="setrights[<?php echo trim($mnu_res['set'][$mres]['guid']); ?>]" value="0" /><input type="checkbox" name="setrights[<?php echo trim($mnu_res['set'][$mres]['guid']); ?>]" id="setrights_<?php echo intval($mnu_res['set'][$mres]['id']); ?>" value="1" <?php if ($rights_res['num']>0): echo "checked=\"checked\""; endif; if (intval($mnu_res['set'][$mres]['parent_id'])>0): echo " onchange=\"checkParent(".intval($mnu_res['set'][$mres]['parent_id']).",0);\""; else: echo " onchange=\"checkParent(".intval($mnu_res['set'][$mres]['id']).",1);\" readonly=\"readonly\" "; endif; ?> /><input type="hidden" name="guid[<?php echo trim($mnu_res['set'][$mres]['guid']); ?>]" value="<?php echo trim($mnu_res['set'][$mres]['guid']); ?>"></p></div>
                                            <div class="col-md-3"><p><?php echo returnIntLang('modrights rights open name'); ?></p></div>
                                            <div class="col-md-4 form-group"><input type="text" name="modname[<?php echo trim($mnu_res['set'][$mres]['guid']); ?>]" value="<?php if ($rights_res['num']>0 && trim($rights_res['set'][0]['description'])!=''): echo trim($rights_res['set'][0]['description']); else: echo $mnu_res['set'][$mres]['describ']; endif; ?>" class="form-control" /></div>
                                        </div>
                                    <?php endfor; ?>
                                    <input type="hidden" name="op" value="setrights">
                                    <input type="hidden" name="mk" value="<?php echo base64_encode($modules_res['set'][0]['guid']); ?>">
                                </form>
                                <p><a onclick="document.getElementById('setrightsform').submit();" style="cursor: pointer;" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></a></p>
                            </div>
                        </div>
                    </div>
                <?php } 
                
                $con_sql = "SELECT c.`cid` AS cid, c.`mid` AS cmid, c.`globalcontent_id` AS gid, c.`trash` AS ctrash, m.`trash` AS mtrash, c.`description` AS cdesc FROM `content` AS c, `menu` AS m WHERE c.`trash` = 0 AND c.`mid` = m.`mid` AND c.`interpreter_guid` IN ('".implode("','", $itp_set)."') GROUP BY c.`cid`";   
                $con_res = doSQL($con_sql);
                foreach ($con_res['set'] AS $csk => $csv) {
                    if ($csv['mtrash']==1) {
                        unset($con_res['set'][$csk]);
                        $con_res['num']--;
                    }
                }
                
                $gcon_sql = "SELECT `id` AS gid, `trash` FROM `content_global` WHERE `trash` = 0 AND `interpreter_guid` IN ('".implode("','", $itp_set)."')";   
                $gcon_res = doSQL($gcon_sql);
                
                if ($con_res['num']>0 || $gcon_res['num']>0) {
                
                ?>
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('moddetails usage'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <?php if ($con_res['num']>0) { ?>
                            <div class="row">
                                <div class="col-md-4"><?php echo returnIntLang('moddetails usage as content'); ?></div>
                                <div class="col-md-8"><?php
                                    
                                foreach ($con_res['set'] AS $csk => $csv) {
                                    $cdata_sql = "SELECT `description` FROM `menu` WHERE `mid` = ".intval($csv['cmid']);
                                    $cdata_res = doResultSQL($cdata_sql);
                                    echo "<p><a onclick='jtC(".$csv['cid'].")' style='cursor: pointer;'>".$cdata_res."</a></p>";
                                }
                                    
                                ?></div>
                            </div>
                            <script>
                            
                            function jtC(cid) {
                                if (cid>0) {
                                    $('#editcontentid').val(cid);
                                    $('#editcontents').submit();
                                }
                            }
                            
                            </script>
                            <form id="editcontents" method="post" action="./contents.php">
                                <input type="hidden" id="editcontentid" name="editcontentid" value="" />
                            </form>
                            <?php } 
                            if ($gcon_res['num']>0) { ?>
                            <div class="row">
                                <div class="col-md-4"><?php echo returnIntLang('moddetails usage as globalcontent'); ?></div>
                                <div class="col-md-8"><?php
                                    
                                var_export($gcon_res);
                                    
                                ?></div>
                            </div>
                            <?php } ?>
                        </div>
                   </div>
                </div>
                <?php }
                                
                $colset = array();
                $modtable_sql = "SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `TABLE_SCHEMA` = '".DB_NAME."' AND `TABLE_NAME` LIKE '".escapeSQL($modules_res['set'][0]['guid'])."%'";
                $modtable_res = getResultSQL($modtable_sql);
                if (is_array($modtable_res)):
                    foreach ($modtable_res AS $mtrk => $mtrv):
                        $col_sql = "SHOW FULL COLUMNS FROM `".$mtrv."` WHERE (`Type` LIKE '%varchar%' OR `Type` LIKE '%text%') AND `Type` NOT LIKE '%varchar(1_)%' AND `Type` NOT LIKE '%varchar(_)%'";
                        $col_res = doSQL($col_sql);
                        if ($col_res['num']>0):
                            foreach($col_res['set'] AS $crk => $crv):
                                $colset[$mtrv][] = array('fieldname' => $crv['Field']);
                            endforeach;
                        endif;
                    endforeach;
                endif;
                
                $affectedcontent = unserializeBroken($modules_res['set'][0]['affectedcontent']);
                if (!(is_array($affectedcontent))): $affectedcontent = array(); endif;
                
                // connected contents from module table and media system
                if (count($colset)>0) { ?>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('moddetails affects'); ?></h3>
                                <p class="panel-subtitle"><?php echo returnIntLang('moddetails affects desc'); ?></p>
                            </div>
                            <div class="panel-body">
                                <form name="setaffectsform" id="setaffectsform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                    <div class="row text-primary">
                                        <div class="col-md-6"><p><strong><?php echo returnIntLang('moddetails affects tablename', false); ?></strong></p></div>
                                        <div class="col-md-6"><p><strong><?php echo returnIntLang('moddetails affects fieldname', false); ?></strong></p></div>
                                    </div>
                                    <?php foreach ($colset AS $csk => $csv): 
                                        foreach ($csv AS $csfk => $csfv): ?>
                                            <?php if (isset($actcsk) && $actcsk!=$csk) { echo "<hr />"; } ?>
                                            <div class="row">
                                                <div class="col-md-6"><p><?php echo $csk; $actcsk = $csk; ?></p></div>
                                                <div class="col-md-5"><p><?php echo $csfv['fieldname']; ?></p></div>
                                                <div class="col-md-1"><input type="hidden" name="affects[<?php echo $csk; ?>][<?php echo $csfv['fieldname']; ?>]" value="0" /><input type="checkbox" name="affects[<?php echo $csk; ?>][<?php echo $csfv['fieldname']; ?>]" value="1" <?php if(isset($affectedcontent[$csk]) && in_array($csfv['fieldname'],$affectedcontent[$csk])): echo ' checked="checked" '; endif; ?> /></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                    
                                    <input type="hidden" name="op" value="setaffects">
                                    <input type="hidden" name="mk" value="<?php echo base64_encode($modules_res['set'][0]['guid']); ?>">
                                </form>
                                <p><a onclick="document.getElementById('setaffectsform').submit();" style="cursor: pointer;" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></a></p>
                            </div>
                        </div>
                    </div>
                <?php } 
                
                unset($actcsk);
                
                $colset = array();
                $modtable_sql = "SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `TABLE_SCHEMA` = '".DB_NAME."' AND `TABLE_NAME` LIKE '".escapeSQL($modules_res['set'][0]['guid'])."%'";
                $modtable_res = getResultSQL($modtable_sql);
                if (is_array($modtable_res)):
                    foreach ($modtable_res AS $mtrk => $mtrv):
                        $col_sql = "SHOW FULL COLUMNS FROM `".$mtrv."` WHERE (`Type` LIKE '%int%' OR `Type` LIKE '%varchar%' OR `Type` LIKE '%text%') AND `Type` NOT LIKE '%varchar(1_)%' AND `Type` NOT LIKE '%varchar(_)%'";
                        $col_res = doSQL($col_sql);
                        if ($col_res['num']>0):
                            foreach($col_res['set'] AS $crk => $crv):
                                $re = '/[a-z]*/m';
                                preg_match($re, $crv['Type'], $matches);
                                $colset[$mtrv][] = array('fieldname' => $crv['Field'], 'fieldtype' => $matches[0]);
                            endforeach;
                        endif;
                    endforeach;
                endif;
                
                $dynamiccontent = isset($modules_res['set'][0]['dynamiccontent'])?unserializeBroken($modules_res['set'][0]['dynamiccontent']):array();
                if (!(is_array($dynamiccontent))) { $dynamiccontent = array(); }
                
                // connected module table fields and dynamic contents
                if (count($colset)>0) { ?>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('moddetails dynamic'); ?></h3>
                                <p class="panel-subtitle"><?php echo returnIntLang('moddetails dynamic desc'); ?></p>
                            </div>
                            <div class="panel-body">
                                <form name="setdynamicform" id="setdynamicform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                    <div class="row text-primary">
                                        <div class="col-md-6"><p><strong><?php echo returnIntLang('moddetails dynamic tablename', false); ?></strong></p></div>
                                        <div class="col-md-6"><p><strong><?php echo returnIntLang('moddetails dynamic fieldname', false); ?></strong></p></div>
                                    </div>
                                    <?php foreach ($colset AS $csk => $csv): 
                                        foreach ($csv AS $csfk => $csfv): ?>
                                            <?php if (isset($actcsk) && $actcsk!=$csk) { echo "<hr />"; } ?>
                                            <div class="row">
                                                <div class="col-md-6"><p><?php echo $csk; $actcsk = $csk; ?></p></div>
                                                <div class="col-md-3"><p><?php echo $csfv['fieldname']; ?></p></div>
                                                <div class="col-md-2"><p><?php echo $csfv['fieldtype']; ?></p></div>
                                                <div class="col-md-1"><input type="hidden" name="dynamic[<?php echo $csk; ?>][<?php echo $csfv['fieldname']; ?>]" value="0" /><input type="checkbox" name="dynamic[<?php echo $csk; ?>][<?php echo $csfv['fieldname']; ?>]" value="1" <?php if(isset($dynamiccontent[$csk]) && in_array($csfv['fieldname'],$dynamiccontent[$csk])): echo ' checked="checked" '; endif; ?> /></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                    
                                    <input type="hidden" name="op" value="setdynamic">
                                    <input type="hidden" name="mk" value="<?php echo base64_encode($modules_res['set'][0]['guid']); ?>">
                                </form>
                                <p><a onclick="document.getElementById('setdynamicform').submit();" style="cursor: pointer;" class="btn btn-primary"><?php echo returnIntLang('str save'); ?></a></p>
                            </div>
                        </div>
                    </div>
                <?php } 
                
                $dep_res = doSQL("SELECT `guid`, `name` FROM `modules` WHERE `dependencies` LIKE '%".escapeSQL($modules_res['set'][0]['guid'])."%'");
            
                if ($modules_res['set'][0]['dependencies']!='' || $dep_res['num']>0) {
                    ?>
                    <div class="col-md-6">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('moddetails dependencies'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <?php if ($dep_res['num']>0) { ?>
                                <div class="row">
                                    <div class="col-md-4"><?php echo returnIntLang('moddetails dependencies parent'); ?></div>
                                    <div class="col-md-8"><?php foreach ($dep_res['set'] AS $drsk => $drsv) {
                        
                                        echo '<p><a href="./moddetails.php?mk='.base64_encode($drsv['guid']).'">'.$drsv['name'].'</a></p>';
                        
                                    } ?></div>
                                </div>
                                <?php }
                    
                                if ($modules_res['set'][0]['dependencies']!='') {
                                    
                                    $par_res = doSQL("SELECT `guid`, `name` FROM `modules` WHERE `guid` = '".escapeSQL($modules_res['set'][0]['dependencies'])."'");
                                    
                                ?>
                                <div class="row">
                                    <div class="col-md-4"><?php echo returnIntLang('moddetails dependencies child'); ?></div>
                                    <div class="col-md-8"><?php echo '<p><a href="./moddetails.php?mk='.base64_encode($par_res['set'][0]['guid']).'">'.$par_res['set'][0]['name'].'</a></p>'; ?></div>
                                </div>
                                <?php } ?>
                            </div>
                       </div>
                    </div>
                <?php } ?>
            </div>
            
            <?php if ($itp_res['num']>0) { 
            
                foreach ($itp_res['set'] AS $irk => $irv) {
                    $defaults = array( 
                        CURLOPT_URL => $_SERVER['HTTP_HOST'].'/'.WSP_DIR.'/xajax/ajax.checkclass.php?mk='.base64_encode($irv['guid']), 
                        CURLOPT_HEADER => 0, 
                        CURLOPT_RETURNTRANSFER => true, 
                        CURLOPT_TIMEOUT => 4 
                    );
                    $ch = curl_init();
                    curl_setopt_array($ch, $defaults);    
                    if( ! $xmldata = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                    curl_close($ch);
                    
                    if (strlen($xmldata)>3) {
                        echo $irv['name']." has errors";
                    }
                    else if (strlen($xmldata)==3) {
                        echo $irv['name']." : ".$xmldata;
                    } else {
                        echo $xmldata;
                    }
                }
            } ?>
            
            <div class="row">
                <div class="col-md-12">
                    <p><a onclick="removeMod('<?php echo prepareTextField($modules_res['set'][0]['name']." ".$modules_res['set'][0]['version']); ?>','<?php echo base64_encode($modules_res['set'][0]['guid']); ?>');" class="btn btn-danger"><?php echo returnIntLang('str delete'); ?></a> <a href="./modules.php" class="btn btn-warning"><?php echo returnIntLang('str back'); ?></a></p>
                </div>
            </div>
            <script>

                function removeMod(modName, modCode) {
                    if (confirm('<?php echo returnIntLang('modules confirm delete1', false); ?>' + modName + '<?php echo returnIntLang('modules confirm delete2', false); ?>')) {
                        $('#removemod-mk').val(modCode);
                        $('#removemod-form').submit();
                    }
                }

            </script>
            <form method="post" id="removemod-form">
                <input type="hidden" name="op" value="removemod" />
                <input type="hidden" name="mk" id="removemod-mk" value="" />
            </form>
            
            
        </div>
        <?php else: 
        
        addWSPMsg('errormsg', 'modules moddetails requested module not found');    
        
        ?>
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('moddetails headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('moddetails info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
        </div>
        <?php endif; ?>
    </div>
</div>   
        
<?php include ("./data/include/footer.inc.php"); ?>