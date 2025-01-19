<?php
/**
 * Modulverwaltung
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2025-01-19
 * fixed GROUP BY clause in SQL statement
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
require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
/* checkParamVar ----------------------------- */
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'modules';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* page specific includes */
require ("./data/include/clssetup.inc.php");
require ("./data/include/clsinterpreter.inc.php");
/* define page specific vars ----------------- */
$op = checkParamVar('op', '');
$id = checkParamVar('id', 0);

/**
* Zugriffsrechte entfernen
*/
function delRestrictions($aRights) {
	foreach ($aRights as $guid => $value) {
		// globale Definition des Zugriffsrechts loeschen
		doSQL("DELETE FROM `wsprights` WHERE `guid` = '".escapeSQL(trim($guid))."'");
		// individuelle Zugriffsrechte der User loeschen
		$sql = "SELECT `rid`, `rights` FROM `restrictions`";
		$res = doSQL($sql);
		if ($res['num']>0) {
            foreach ($res['set'] AS $rk => $row) {
				$restriction = unserializeBroken($row['rights']);
				if (isset($restriction[$guid])) {
					unset($restriction[$guid]);
					doSQL("UPDATE `restrictions` SET `rights` = '".escapeSQL(serialize($restriction))."' WHERE `rid` = ".intval($row['rid']));
				}	// if
			}	// while
		}	// if
	}	// foreach
}	// delRestrictions()

/**
* Abhaengigkeiten anderer WSP-Module zu dem zu loeschenden WSP-Modul pruefen;
* wenn keine Abhï¿½ngigkeiten existieren, uninstall durchfuehren; ansonsten
* Hinweis auf vorhandene Abhï¿½ngigkeiten anderer WSP-Module und Abbruch
*/
function modCheckUninstall() {
    
	$sql = "SELECT * FROM `modules` WHERE `id` = ".intval($_REQUEST['id']);
	$res = doSQL($sql);
	$name = trim($res['set'][0]['name']);
    $guid = trim($res['set'][0]['guid']);
	$version = trim($res['set'][0]['version']);
	$success = false;
    
    if ($guid!='') {
        $sql = "SELECT `id` FROM `modules` WHERE `dependences` LIKE '%".escapeSQL(trim($guid))."%'";
        $res = doSQL($sql);
        // no dependencies
        if ($res['num']==0):
            $sql = 'SELECT `guid` FROM `interpreter` WHERE `module_guid` = "'.escapeSQL(trim($guid)).'"';
            $res = doResultSQL($sql);
            if ($res!==false):
                doSQL("UPDATE `content` SET `trash` = 1 WHERE `interpreter_guid` = '".trim($res)."'");
                doSQL("DELETE FROM `interpreter` WHERE `guid` = '".trim($res)."'");
                // remove menu entries
                doSQL("DELETE FROM `wspmenu` WHERE `module_guid` = '".$guid."'");
                // remove self vars
                doSQL("DELETE FROM `selfvars` WHERE `module_guid` = '".$guid."'");
                // remove from modules
                doSQL("DELETE FROM `modules` WHERE `guid` = '".$guid."'");
        
        
                /*
        // Interpreter-Eintrï¿½ge loeschen
        foreach ($modsetup->getParser() as $guid => $value) {
            $sql = "DELETE FROM `interpreter` WHERE `guid`='$guid'";
            mysql_query($sql) or die(writeMySQLError($sql));
        }	// foreach

        // Zugriffsrechte entfernen
        delRestrictions($modsetup->cmsRights());

        // Tabellen aus Datenbank loeschen
        if (count($modsetup->getSQLDescribe())>0) {
            foreach ($modsetup->getSQLDescribe() as $sql) {
            //	mysql_query("DROP TABLE `".$sql['tablename']."`");
            }

        delTree($tmppath);
        unlink($_SERVER['DOCUMENT_ROOT']."/wsp/modules/".mysql_db_name($rs, 0, 'archive'));
        ftpDeleteFile($_SESSION['wspvars']['ftpbasedir']."/wsp/modules/".mysql_db_name($rs, 0, 'archive'));
        */
        
        
            endif;
//          $buf = modUninstall();
            addWSPMSg('noticemsg', $name.' wurde deinstalliert.');
            $success = true;
        else:
            $buf = "<p>F&uuml;r das Modul '".$name."' (Version ".$version.") ";
            if ($res['num']==1) {
                $buf.= "ist 1 Abh&auml;ngigkeit";
            }
            else {
                $buf.= "sind ".$res['num']." Abh&auml;ngigkeiten";
            }	// if
            $buf.= " vorhanden.<br />Die Deinstallation kann nicht durchgef&uuml;hrt werden.";
        endif;
    }
	?>
	<fieldset class="<?php if ($success): echo 'noticemsg'; else: echo 'errormsg'; endif; ?>">
		<?php echo $buf; ?>
	</fieldset>
	<?php
}	// modCheckUninstall()

/**
* Abhaengigkeiten anderer WSP-Module zu dem zu loeschenden WSP-Modul pruefen;
* wenn keine Abhï¿½ngigkeiten existieren, uninstall durchfuehren; ansonsten
* Hinweis auf vorhandene Abhï¿½ngigkeiten anderer WSP-Module und Abbruch
*/
function pluginCheckUninstall() {
	global $usevar, $id;

	$success = false;
	
	$buf = "Das Deinstallieren von Plugins ist derzeit nicht m&ouml;glich.";
	
	?>
	<fieldset class="<?php if ($success): echo 'noticemsg'; else: echo 'errormsg'; endif; ?>">
		<?php echo $buf; ?>
	</fieldset>
	<?php
	}	// pluginCheckUninstall()

// call module setup 
function modSetup() {
	global $id;
	$sql = "SELECT `modsetup`, `name` FROM `modules` WHERE `id` = ".$id;
	$res = doSQL($sql);
	include 'data/modsetup/'.trim($res['set'][0]['modsetup']);
	echo '<fieldset class="text"><h2>Einstellungen f&uuml;r das Modul "'.trim($res['set'][0]['name']).'"</h2></fieldset>';
	modulSettings();
}	// modSetup()

function modRights() {
	global $usevar, $id, $wspvars;
	
	$mod_sql = "SELECT * FROM `wspmenu` WHERE `module_guid` = '".escapeSQL(trim($_POST['module_guid']))."' ORDER BY `parent_id` ASC";
	$mod_res = doSQL($mod_sql);
	
    $moddata_sql = "SELECT * FROM `modules` WHERE `guid` = '".escapeSQL(trim($_POST['module_guid']))."'";
	$moddata_res = doSQL($moddata_sql);
    
	if ($mod_res['num']>0):
		$modguid = trim($mod_res['set'][0]['guid']);
		?>
		<fieldset><h2><?php echo returnIntLang('modrights rights for module'); ?> "<?php echo trim($mod_res['set'][0]['title']); ?>"</h2></fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('str legend'); ?></legend>
			<p><?php echo returnIntLang('modrights rights legend'); ?></p>
		</fieldset>
        <form name="setrightsform" id="setrightsform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<fieldset>
			<legend><?php echo returnIntLang('modrights activate modrights'); ?></legend>
			
			<?php if ($mod_res['num']>1): 
				$checkid = array();
				foreach ($mod_res['set'] AS $mresk => $mresv) {
					$checkid[] = "document.getElementById('setrights_".intval($mresv['id'])."').checked";
				}
				?>
				<script language="JavaScript" type="text/javascript">
				<!--
				function checkParent(parentid) {
					if (<?php echo implode(" || ", $checkid); ?>) {
						document.getElementById('setrights_' + parentid).checked = true;
						}
					else {
						document.getElementById('setrights_' + parentid).checked = false;
						<?php
						
						foreach ($checkid AS $value):
							echo $value." = false;\n";
						endforeach;
						
						?>
						}
					}
				// -->
				</script>
			<?php endif; ?>
			<table class="tablelist">
				<?php foreach ($mod_res['set'] AS $mresk => $mresv):
					
					$rights_sql = "SELECT * FROM `wsprights` WHERE `guid` = '".escapeSQL(trim($mresv['guid']))."'";
					$rights_res = doSQL($rights_sql);
									
					?>
					<tr>
						<td class="tablecell two"><?php 
						if (intval($mresv['parent_id'])>0): echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; endif;
						echo trim($mresv['title']); ?></td>
						<td class="tablecell one"><input type="checkbox" name="setrights[<?php echo trim($mresv['guid']); ?>]" id="setrights_<?php echo intval($mresv['id']); ?>" value="1" <?php if ($rights_res['num']>0): echo "checked=\"checked\""; endif; if (intval($mresv['parent_id'])>0): echo " onchange=\"checkParent(".intval($mresv['parent_id']).");\""; else: echo " onchange=\"checkParent(".intval($mresv['id']).");\" readonly=\"readonly\" "; endif; ?> /><input type="hidden" name="guid[<?php echo trim($mresv['guid']); ?>]" value="<?php echo trim($mresv['guid']); ?>"></td>
						<td class="tablecell one"><?php echo returnIntLang('modrights rights open name'); ?>:</td>
						<td class="tablecell four"><input type="text" name="modname[<?php echo trim($mresv['guid']); ?>]" value="<?php echo trim($mresv['describ']); ?>" class="one full" /></td>
					</tr>
				<?php endforeach; ?>
			</table>
            <input type="hidden" name="op" value="setrights">
		</fieldset>
            <fieldset>
                <legend><?php echo returnIntLang('modrights manage affectedcontents'); ?></legend>
                <?php
                
                $colset = array();
                $modtable_sql = "SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `TABLE_SCHEMA` = '".DB_NAME."' AND `TABLE_NAME` LIKE '".escapeSQL(trim($mresv['module_guid']))."%'";
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
                
                $affectedcontent = unserializeBroken($moddata_res['set'][0]['affectedcontent']);
                if (!(is_array($affectedcontent))): $affectedcontent = array(); endif;
                
                // connected contents from module table and media system
                if (count($colset)>0) { ?>
                <div class="row text-primary">
                    <div class="col-md-6"><p><strong><?php echo returnIntLang('moddetails affects tablename', false); ?></strong></p></div>
                    <div class="col-md-6"><p><strong><?php echo returnIntLang('moddetails affects fieldname', false); ?></strong></p></div>
                </div>
                <input type="hidden" name="module_guid" value="<?php echo trim($mresv['module_guid']); ?>" />
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
                <?php } 
                
                unset($actcsk);

                ?>
            </fieldset>
		</form>
        <fieldset class="options">
			<p><a onclick="document.getElementById('setrightsform').submit();" style="cursor: pointer;" class="greenfield">&Auml;nderungen speichern</a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield">Zur&uuml;ck</a></p>	
		</fieldset>
		<?php
	else:
		?>
		<fieldset class="text">
			<p>Diesem Modul k&ouml;nnen keine Rechte zugewiesen werden.</p>	
		</fieldset>
		<fieldset class="options">
			<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield">Zur&uuml;ck</a></p>	
		</fieldset>
		<?php
	endif;
	}	// modRights()
	
function pluginRights() {
	global $usevar, $id, $wspvars;
	
	$mod_sql = "SELECT * FROM `wspplugins` WHERE `guid` = '".$_POST['module_guid']."'";
	$mod_res = doSQL($mod_sql);
	
	if ($mod_res['num']>0):
		$modguid = trim($mod_res['set'][0]['guid']);
		?>
		<fieldset><h2><?php echo returnIntLang('modrights rights for plugin'); ?> "<?php echo trim($mod_res['set'][0]['pluginname']); ?>"</h2></fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('str legend'); ?></legend>
			<p><?php echo returnIntLang('modrights rights for plugin info'); ?></p>	
		</fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('modrights activate modrights'); ?></legend>
			<form name="setrightsform" id="setrightsform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<table border="0" cellspacing="0" cellpadding="3" width="100%">
				<?php 
				
				foreach ($mod_res['set'] AS $mresk => $mresv):
					
					$rights_sql = "SELECT * FROM `wsprights` WHERE `guid` = '".escapeSQL(trim($mresv['guid']))."'";
					$rights_res = doSQL($rights_sql);
					
					?>
					<tr>
						<td width="25%"><?php echo trim($mresv['pluginname']); ?></td>
						<td width="75%"><input type="checkbox" name="setrights[<?php echo trim($mresv['guid']); ?>]" id="setrights_<?php echo intval($mresv['id']); ?>" value="1" <?php if ($rights_res['num']>0): echo "checked=\"checked\""; endif; ?> /><input type="hidden" name="guid[<?php echo trim($mresv['guid']); ?>]" value="<?php echo trim($mresv['guid']); ?>"> <?php echo returnIntLang('str description'); ?> <input type="text" name="modname[<?php echo trim($mresv['guid']); ?>]" value="<?php echo trim($mresv['pluginname']); ?>"></td>
					</tr>
					<?php
				
				endforeach;
				
				?>
			</table>
			<input type="hidden" name="op" value="setrights">
			</form>
		</fieldset>
		<fieldset class="options">
			<p><a style="cursor: pointer;" onclick="document.getElementById('setrightsform').submit();" class="greenfield"><?php echo returnIntLang('modrights save changes', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str back', false); ?></a></p>	
		</fieldset>
		<?php
	else:
		?>
		<fieldset class="text">
			<p>Diesem Plugin k&ouml;nnen keine Rechte zugewiesen werden.</p>	
		</fieldset>
		<fieldset class="options">
			<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str back', false); ?></a></p>	
		</fieldset>
		<?php
	endif;
	}	// pluginRights()

// update module
function modUpdate($data) {
	$fh = fopen($_SESSION['wspvars']['updateuri'].'/updater.php?key='.$_SESSION['wspvars']['updatekey'].'&file='.$data, 'r');
	$fileupdate = '';
	while (!feof($fh)) {
		$fileupdate .= fgets($fh, 4096);
	}	// if
	fclose($fh);

	$tmppfad = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".basename($data);
	$tmpdat=fopen($tmppfad,'w');
	fwrite($tmpdat, $fileupdate);
	fclose($tmpdat);

	$ftpAttempt=3;
	$aReturn = array(2);
	$aReturn[0] = false;
	$ftp = false;

	while (!$ftp && ($ftpAttempt > 0)) {
		if ($counterOld != $ftpAttempt) {
			$counterOld = $ftpAttempt;
			sleep(1);
		}
		// if
		$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
		$ftpAttempt--;
	}
	// while

	if ($ftp === false) {
		$aReturn[0] = true;
		$aReturn[1] = 'Kann erzeugte Datei nicht hochladen. (Connect)';
	}
	// if
	else if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) {
		$aReturn[0] = true;
		$aReturn[1] = 'Kann erzeugte Datei nicht hochladen. (Login)';
	}
	else {
		if (!!ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir']."/".$data, $tmppfad, FTP_BINARY)) {
		$aReturn[0] = true;
		$aReturn[1] = 'Kann erzeugte Datei nicht hochladen. (Put)';
		}
	}
	// if
@unlink($tmppfad);
}

// set rights
function modSetRights() {
	if (isset($_POST['op']) && $_POST['op']=="setrights") {
        foreach ($_POST['guid'] AS $key => $value) {
            $sql = "DELETE FROM `wsprights` WHERE `guid` = '".escapeSQL($key)."'";
            $res = doSQL($sql);
            if (intval($_POST['setrights'][$key])==1) {
                $possibilities = array(1,0);
                $labels = array("Ja","Nein");
                doSQL("INSERT INTO `wsprights` SET `guid` = '".escapeSQL($key)."', `right` = '".escapeSQL($_POST['modname'][$key])."', `standard` = '1', `possibilities` = '".escapeSQL(serialize($possibilities))."', `labels` = '".escapeSQL(serialize($labels))."'");
            }
        }
        if (isset($_POST['affects'])) {
            $affectedcontent = array();
            foreach ($_POST['affects'] AS $table => $fields) {
                foreach ($fields AS $fk => $fv) {
                    if ($fv==1): $affectedcontent[$table][] = $fk; endif;
                }
            }
            $sql = "UPDATE `modules` SET `affectedcontent` = '".((count($affectedcontent)>0)?escapeSQL(serialize($affectedcontent)):NULL)."' WHERE `guid` = '".escapeSQL($_POST['module_guid'])."'";
            if (getAffSQL($sql)>0) { addWSPMsg('resultmsg', returnIntLang('moddetails updated affected fields')); }
        }
    }
}

$mods_sql = "SELECT * FROM `modules` WHERE `ismenu` != 1 ORDER BY `name`";
$mods_res = doSQL($mods_sql);
$mods_num = $mods_res['num'];

$menumod_sql = "SELECT * FROM `modules` WHERE `ismenu` = 1 ORDER BY `name`";
$menumod_res = doSQL($menumod_sql);
$menumod_num = $menumod_res['num'];

$parsermod_sql = "SELECT * FROM `modules` WHERE `isparser` = 1 && `iscmsmodul` != 1 ORDER BY `name`";
$parsermod_res = doSQL($parsermod_sql);
$parsermod_num = $parsermod_res['num'];

$cmsmod_sql = "SELECT * FROM `modules` WHERE `iscmsmodul` = 1 ORDER BY `name`";
$cmsmod_res = doSQL($cmsmod_sql);
$cmsmod_num = $cmsmod_res['num'];

$addmod_sql = "SELECT * FROM `modules` WHERE `ismenu` != 1 && `isparser` != 1 && `iscmsmodul` != 1 ORDER BY `name`";
$addmod_res = doSQL($addmod_sql);
$addmod_num = $addmod_res['num'];

$plugin_sql = "SELECT * FROM `wspplugins` ORDER BY `pluginname`";
$plugin_res = doSQL($plugin_sql);
$plugin_num = $plugin_res['num'];

// head der datei

include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

?>
<div id="contentholder">
	<pre id="debugcontent"></pre>
	<fieldset><h1><?php echo returnIntLang('modules headline'); ?></h1></fieldset>
    <?php
    
    if ($op == 'modrights'):
        modRights();
    elseif ($op == 'pluginrights'):
        pluginRights();
    elseif ($op == 'modsetup'):
        modSetup();
    else:
        modSetRights();
        if (($op == 'modcheckuninstall') && ($id > 0)) {
			modCheckUninstall();
        } else if (($op == 'plugincheckuninstall') && ($id > 0)) {
			pluginCheckUninstall();
		}
    
        if ($mods_num>0) {
			?>
            <div id="includedmods"></div>
            <script type="text/javascript" language="javascript">
    <!--

    function openerModDetails(id) {
        if (document.getElementById('moddetailsoff_' + id).style.display!='none') {
            document.getElementById('moddetailsoff_' + id).style.display = 'none';
            document.getElementById('moddetailson_' + id).style.display = 'block';
            }
        else {
            document.getElementById('moddetailsoff_' + id).style.display = 'block';
            document.getElementById('moddetailson_' + id).style.display = 'none';
            }
        }	// openerModDetailes()

    function openerUseDetails(id) {
        alert (id);
        }	// openerUseDetailes()

    function highlightRow(id) {
        if (document.getElementById(id).className=='trhighligt') {
            document.getElementById(id).className = '';
            }
        else {
            document.getElementById(id).className = 'trhighligt';
            }// if
        }	// hightlightRow()

    function modRename(sid) {
        alert ('ID ' + sid);
        }

    function showDetails(iID) {
        $('.details-' + iID).toggle(0);
        }

    //-->
    </script>
            <?php 
        
            if ($parsermod_num>0) { ?>
                <fieldset class="text" id="fieldset_parsermod">
				<legend><?php echo $parsermod_num; ?> <?php echo returnIntLang('modules parsermod'); ?> <?php echo legendOpenerCloser('parsermod'); ?></legend>
				<div id="parsermod">
				<table class="tablelist">
					<tr>
						<td class="tablecell four head"><?php echo returnIntLang('str name'); ?></td>
						<td class="tablecell two head"><?php echo returnIntLang('str usage'); ?></td>
						<td class="tablecell two head"><?php echo returnIntLang('str action'); ?></td>
					</tr>
					<?php
					
                    $clang = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'languages'");
                    if ($clang!==false) {
                        $clang = unserializeBroken($clang);
                    } else {
                        $clang['shortcut'] = array('de');
                    }
                                   
                    foreach ($parsermod_res['set'] AS $presk => $presv): 
				        $parserusedesc = array();
                        $parseruse_sql = 'SELECT 
                            s.`mid` AS `cnt`,
                            c.`cid` AS `cid`,
                            s.`description` AS `description`, 
                            c.`content_area` AS `carea` 
                        FROM 
                            `content` AS c, 
                            `menu` AS s, 
                            `interpreter` AS i
                        WHERE 
                            c.`trash` = 0 AND 
                            s.`trash` = 0 AND 
                            c.`mid` = s.`mid` AND 
                            c.`content_lang` IN (\''.implode("','", $clang['shortcut']).'\') AND
                            (c.`interpreter_guid` = i.`guid` AND i.`module_guid` = "'.trim($presv["guid"]).'") 
                        GROUP BY 
                            s.`mid`,
							c.`cid`';
                        $parseruse_res = doSQL($parseruse_sql);
                        
                        echo "<tr>";
                        if ($parseruse_res['num']>0) {
                            echo "<td class='tablecell four'><a onclick='showDetails(".intval($presv["id"]).")' style='cursor: pointer;'>".trim($presv["name"])." ".trim($presv["version"])." <span class=\"bubblemessage orange\">".returnIntLang('bubble down', false)."</span></a></td>";
                            echo "<td class='tablecell two'><a onclick='showDetails(".intval($presv["id"]).")' style='cursor: pointer;'>".$parseruse_res['num']." ".returnIntLang('modules pages', false)." <span class=\"bubblemessage orange\">".returnIntLang('bubble down', false)."</span></a></td>";
                            echo "<td class='tablecell two'><span class=\"bubblemessage red disabled\">".returnIntLang('bubble delete', false)."</span></td>";
                        }
                        else {
                            echo "<td class='tablecell four'>".trim($presv["name"])." ".trim($presv["version"])."</td>";          
                            echo "<td class='tablecell two'>-</td>";
                            echo "<td class='tablecell two'><a href=\"".$_SERVER['PHP_SELF']."?op=modcheckuninstall&id=".intval($presv["id"])."\" onclick=\"return confirm(unescape('Soll das Modul %27".prepareTextField(setUTF8($presv["name"]))."%27 wirklich deinstalliert werden?'));\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a></td>";
                        }
						echo "</tr>";
                               
						if ($parseruse_res['num']>0):
							$cell = array();
							foreach($parseruse_res['set'] AS $pkey => $pvalue) {
								$cell[] = "<a href=\"contentstructure.php?mjid=".$pvalue['cnt']."\">".$pvalue['description']."</a>";
							}
							for ($r=0; $r<(ceil(count($cell)/3)); $r++) {
								echo "<tr id=\"\" class=\"details-".intval($presv["id"])."\" style=\"display: none;\">";
								echo "<td class='tablecell two'>";
								if ($r==0): echo "<em>".returnIntLang('modules pages', false)."</em>"; endif;
								echo "</td>";
								for ($c=0; $c<3; $c++):
									echo "<td class='tablecell two'>";
									if (isset($cell[(($r*3)+$c)])): echo $cell[(($r*3)+$c)]; endif;
									echo "</td>";
								endfor;
								echo "</tr>";
							}
						endif;
						
						$details_sql = 'SELECT `name`, `version`, `sid` FROM `interpreter` WHERE `module_guid` = "'.trim($presv["guid"]).'" ORDER BY `name`';
						$details_res = doSQL($details_sql);
						
						if ($details_res['num']>0):
                            for ($r=0; $r<(ceil($details_res['num']/3)); $r++):
								echo "<tr id=\"\" class=\"details-".intval($presv["id"])."\" style=\"display: none;\">";
								echo "<td class='tablecell two'>";
								if ($r==0): echo "<em>".returnIntLang('modules parser')."</em>"; endif;
								echo "</td>";
								for ($c=0; $c<3; $c++):
									echo "<td class='tablecell two'>";
									if ((($r*3)+$c)<$details_num): 
										echo trim($details_res['set'][(($r*3)+$c)]["name"])." ".trim($details_res['set'][(($r*3)+$c)]["version"]);
									endif;
									echo "</td>";
								endfor;
								echo "</tr>";
							endfor;
						endif;
						
					endforeach;
					
					?>
					</table>
				</div>
			</fieldset>
        <?php } 
        
            if ($cmsmod_num>0) { ?>
			<fieldset class="text" id="fieldset_cmsmod">
				<legend><?php echo $cmsmod_num; ?> <?php echo returnIntLang('modules cmsmod'); ?> <?php echo legendOpenerCloser('cmsmod'); ?></legend>
				<div id="cmsmod">
				<ul class="tablelist">
					<li class="tablecell four head"><?php echo returnIntLang('str name'); ?></li>
					<li class="tablecell two head"><?php echo returnIntLang('str usage'); ?></li>
					<li class="tablecell two head"><?php echo returnIntLang('str action'); ?></li>
					<?php
					
					foreach ($cmsmod_res['set'] AS $cresk => $cresv): 

                        echo "<li id=\"trmod_".intval($cresv["id"])."\" class=\"tablecell four\">";
						
						$details_sql = 'SELECT `name`, `version` FROM `interpreter` WHERE `module_guid` = "'.trim($cresv["guid"]).'" ORDER BY `name`';
						$details_res = doSQL($details_sql);
						
                        echo trim($cresv["name"])." ".trim($cresv["version"]);
						$detailset = array();
                        if ($details_num>0):
							foreach ($details_res['set'] AS $dresk => $dresv):
								$detailset[] = "<em>".trim($dresv["name"])." - ".trim($dresv["version"])."</em>";
							endforeach;
						endif;
						echo "</li>\n";
                    
						$cmsuse_sql = 'SELECT c.`mid` AS `cnt` FROM `content` c, `interpreter` i, `modules` m WHERE c.`interpreter_guid` = i.`guid` && i.`module_guid` = m.`guid` && m.`guid` = "'.trim($cresv["guid"]).'"';
						$cmsuse_res = doSQL($cmsuse_sql);
						
						echo "<li class=\"tablecell two\">";
						if ($cmsuse_res['num']>0):
							$cmsusedesc = array();
							foreach ($cmsuse_res['set'] AS $curesk => $curesv) {
								$cmsusedesc_sql = "SELECT `description` FROM `menu` WHERE `mid` = ".intval($curesv["cnt"]);
								$cmsusedesc_res = doResultSQL($cmsusedesc_sql);
								if ($cmsusedesc_res!==false) {
									$cmsusedesc[intval($curesv["cnt"])] = trim($cmsusedesc_res);
								}
                            }
							array_unique($cmsusedesc);
							sort($cmsusedesc);
							
							if (count($cmsusedesc)>0):
								echo $cmsuse_res['num']." ".returnIntLang('modules usage on', false)." ".count($cmsusedesc)." ".returnIntLang('modules pages', false);
							else:
								echo "-";
							endif;
						else:
							echo "-";
						endif;
						echo "</li>\n";
						
						echo "<li class=\"tablecell two\">";
						
						if ($cmsuse_res['num']==0):
							echo "<a href=\"".$_SERVER['PHP_SELF']."?op=modcheckuninstall&id=".intval($cresv["id"])."\" onclick=\"return confirm(unescape('Soll das Modul %27".prepareTextField(setUTF8($cresv["name"]))."%27 wirklich deinstalliert werden?'));\" class=\"red\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a>";
						else:
							echo "<span class=\"bubblemessage red disabled\">".returnIntLang('bubble delete', false)."</span>";
						endif;
												
						$modmenu_sql = "SELECT * FROM `wspmenu` WHERE `module_guid` = '".trim($cresv["guid"])."' ORDER BY `parent_id` ASC";
						$modmenu_res = doSQL($modmenu_sql);
						
						if ($modmenu_res['num']>0):
							echo " <a style=\"cursor: pointer;\" onclick=\"document.getElementById('setrights_".intval($cresv["id"])."').submit();\"><span class=\"bubblemessage orange\">".returnIntLang('bubble rights', false)."</span></a>";
							echo "<form action=\"".$_SERVER['PHP_SELF']."\" name=\"setrights_".intval($cresv["id"])."\" id=\"setrights_".intval($cresv["id"])."\" method=\"post\">";
							echo "<input type=\"hidden\" name=\"module_guid\" value=\"".trim($cresv["guid"])."\">";
							echo "<input type=\"hidden\" name=\"op\" value=\"modrights\">";
							echo "</form>";
						endif;
						echo "</li>\n";
                    
						// parser info
						if (count($detailset)>0):
							echo "<li id=\"trmod_".intval($cresv["id"])."_details\" class=\"tablecell two\"><em>".returnIntLang('modules parser')."</em></li>";
							echo "<li id=\"trmod_".intval($cresv["id"])."_parser\" class=\"tablecell six\">".implode(", ", $detailset)."</li>";
						endif;
						
						
					endforeach;
					
					?>
					
				</ul>
				</div>
			</fieldset>
        <?php }
        
            if ($addmod_num>0) { ?>
			<fieldset class="text" id="fieldset_addmod">
				<legend><?php echo $addmod_num; ?> <?php echo returnIntLang('modules addmod'); ?> <?php echo legendOpenerCloser('addmod'); ?></legend>
				<div id="addmod">
				<ul class="tablelist">
					<li class="tablecell six head"><?php echo returnIntLang('str name'); ?></li>
					<li class="tablecell two head"><?php echo returnIntLang('str action'); ?></li>
					<?php
					
					foreach ($addmod_res['set'] AS $aresk => $aresv) {
						echo "<li id=\"trmod_".intval($aresv['id'])."\" class=\"tablecell six\">\n";
						
						$details_sql = 'SELECT `name`, `version` FROM `interpreter` WHERE `module_guid` = "'.trim($aresv['guid']).'" ORDER BY `name`';
						$details_res = doSQL($details_sql);
						
						echo trim($aresv['name'])." ".trim($aresv['version']);
						if ($details_res['num']>0):
							echo " <a href=\"\" title=\"Details\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/expandm.gif\" border=\"0\" align=\"texttop\" style=\"cursor: pointer;\" /></a>";
						endif;
						
						echo "</li>\n";
						
						$adduse_sql = 'SELECT COUNT(c.`cid`) AS `cnt` FROM `content` c, `interpreter` i, `modules` m WHERE c.`interpreter_guid`=i.`guid` && i.`module_guid`=m.`guid` && m.`guid`="'.trim($aresv['guid']).'"';
						$adduse_res = doSQL($adduse_sql);
						
						echo "<li class=\"tablecell two\">";
						echo "<a href=\"".$_SERVER['PHP_SELF']."?op=modcheckuninstall&id=".intval($aresv['id'])."\" onclick=\"return confirm(unescape('Soll das Modul %27".prepareTextField(setUTF8($aresv["name"]))."%27 wirklich deinstalliert werden?'));\" class=\"red\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a>";
						echo "</li>\n";
                    }
					
					?>
				</ul>
				</div>
			</fieldset>
        <?php }
    
            if ($plugin_num>0) { ?>
			<fieldset class="text" id="fieldset_plugin">
				<legend><?php echo $plugin_num; ?> <?php echo returnIntLang('modules plugins'); ?> <?php echo legendOpenerCloser('plugin'); ?></legend>
				<div id="plugin">
				<ul class="tablelist">
					<li class="tablecell six head"><?php echo returnIntLang('str name'); ?></li>
					<li class="tablecell two head"><?php echo returnIntLang('str action'); ?></li>
					<?php
					
					for($pres=0; $pres<$plugin_num; $pres++): 
						echo "<li id=\"trmod_".intval($plugin_res['set'][$pres]["id"])."\" class=\"tablecell six\">".trim($plugin_res['set'][$pres]["pluginname"])."</li>";
						
						$adduse_sql = 'SELECT COUNT(c.`cid`) AS `cnt` FROM `content` c, `interpreter` i, `modules` m WHERE c.`interpreter_guid`=i.`guid` && i.`module_guid`=m.`guid` && m.`guid`="'.trim($plugin_res['set'][$pres]["guid"]).'"';
						$adduse_res = doSQL($adduse_sql);
						
						echo "<li class=\"tablecell two\">";
						echo "<a href=\"".$_SERVER['PHP_SELF']."?op=plugincheckuninstall&id=".intval($plugin_res['set'][$pres]["id"])."\" onclick=\"return confirm(unescape('Soll das Plugin %27".trim($plugin_res['set'][$pres]["pluginname"])."%27 wirklich deinstalliert werden? Durch das Installieren werden alle Datenbankeintraege, Dateien und Interpreter geloescht, die dem Plugin zugeordnet sind.'));\" title=\"Modul '".trim($plugin_res['set'][$pres]["pluginname"])."' deinstallieren\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a> ";
						
						$modplugin_sql = "SELECT * FROM `wspplugins` WHERE `guid` = '".trim($plugin_res['set'][$pres]["guid"])."'";
						$modplugin_res = doSQL($modplugin_sql);
						
						if ($modplugin_res['num']>0):
							echo "<a style=\"cursor: pointer;\" title=\"Rechteverwaltung f&uuml;r Plugin '".trim($plugin_res['set'][$pres]["pluginname"])."' bearbeiten\" onclick=\"document.getElementById('setrights_".intval($plugin_res['set'][$pres]["id"])."').submit();\"><span class=\"bubblemessage orange\">".returnIntLang('bubble rights', false)."</span></a>";
							echo "<form action=\"".$_SERVER['PHP_SELF']."\" name=\"setrights_".intval($plugin_res['set'][$pres]["id"])."\" id=\"setrights_".intval($plugin_res['set'][$pres]["id"])."\" method=\"post\">";
							echo "<input type=\"hidden\" name=\"module_guid\" value=\"".trim($plugin_res['set'][$pres]["guid"])."\">";
							echo "<input type=\"hidden\" name=\"op\" value=\"pluginrights\">";
							echo "</form>";
						endif;
						
						echo "</li>\n";
					endfor;
					
					?>
				</ul>
				</div>
			</fieldset>
        <?php }
        }
        else { 
            echo "<fieldset>";
            echo "<p>".returnIntLang('modules none installed')."</p>";
            echo "</fieldset>";
        } 
    
        ?>
        <fieldset class="options">
            <p><a href="/<?php echo $wspvars['wspbasedir'] ?>/modinstall.php" class="greenfield"><?php echo returnIntLang('modules modinstall', false); ?></a></p>
        </fieldset>
    <?php endif; ?>
</div>

<?php @ include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->