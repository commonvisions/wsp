<?php
/**
 * Modulverwaltung
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2020-07-02
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
$_SESSION['wspvars']['pagedesc'] = array('far fa-cogs',returnIntLang('menu manage'),returnIntLang('menu manage modules'));
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* page specific includes */
require ("./data/include/clssetup.inc.php");
require ("./data/include/clsinterpreter.inc.php");
/* define page specific vars ----------------- */
$op = checkParamVar('op');
$mk = checkParamVar('mk', false);

if (isset($_REQUEST['so']) && trim($_REQUEST['so'])!='') {
    if (intval($_REQUEST['so'])==1) {
        if (isset($_SESSION['wspvars']['modshow']['parser']) && $_SESSION['wspvars']['modshow']['parser']===true) {
            unset($_SESSION['wspvars']['modshow']['parser']);
        } else {
            $_SESSION['wspvars']['modshow']['parser'] = true;
        }
    }
    if (intval($_REQUEST['so'])==2) {
        if (isset($_SESSION['wspvars']['modshow']['modules']) && $_SESSION['wspvars']['modshow']['modules']===true) {
            unset($_SESSION['wspvars']['modshow']['modules']);
        } else {
            $_SESSION['wspvars']['modshow']['modules'] = true;
        }
    }
    if (intval($_REQUEST['so'])==3) {
        if (isset($_SESSION['wspvars']['modshow']['menus']) && $_SESSION['wspvars']['modshow']['menus']===true) {
            unset($_SESSION['wspvars']['modshow']['menus']);
        } else {
            $_SESSION['wspvars']['modshow']['menus'] = true;
        }
    }
    if (intval($_REQUEST['so'])==4) {
        if (isset($_SESSION['wspvars']['modshow']['plugins']) && $_SESSION['wspvars']['modshow']['plugins']===true) {
            unset($_SESSION['wspvars']['modshow']['plugins']);
        } else {
            $_SESSION['wspvars']['modshow']['plugins'] = true;
        }
    }
    if (intval($_REQUEST['so'])==5) {
        if (isset($_SESSION['wspvars']['modshow']['extensions']) && $_SESSION['wspvars']['modshow']['extensions']===true) {
            unset($_SESSION['wspvars']['modshow']['extensions']);
        } else {
            $_SESSION['wspvars']['modshow']['extensions'] = true;
        }
    }
}

// remove module
if ($op=='removemod' && trim($mk)!='') {
    $success = true;
    $guid = base64_decode($mk);
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
                addWSPMsg('resultmsg', returnIntLang('modules removed interpreter1').trim($iv['name']).returnIntLang('modules removed interpreter2'));
                // find menus !?!?!?
                addWSPMsg('noticemsg', returnIntLang('modules did not try to remove menuentry for')." ".trim($iv['name']));
                addWSPMsg('noticemsg', returnIntLang('modules did not try to remove selfvar for')." ".trim($iv['name']));
                addWSPMsg('noticemsg', returnIntLang('modules did not try to remove globalcontent for')." ".trim($iv['name']));
                addWSPMsg('noticemsg', returnIntLang('modules did not try to remove template content for')." ".trim($iv['name']));
            }
        }
        // finally remove module
        $mod_res = doSQL("SELECT `name` FROM `modules` WHERE `guid` = '".escapeSQL($guid)."'");
        $del_res = doSQL("DELETE FROM `modules` WHERE `guid` = '".escapeSQL($guid)."'");
        if ($del_res['aff']==1) {
            addWSPMsg('resultmsg', returnIntLang('modules removed module1').trim($mod_res['set'][0]['name']).returnIntLang('modules removed module2'));
        }
    }
}









/**
* Kopiert eine Verzeichnisstruktur mit allen Files und Subdirs an den angegebenen Platz
*/
function copyTree($src, $dest) {
	
    addWSPMsg('errormsg', 'function copyTree in modules has errrors');
    
    $dh = dir($src);
	while (false !== ($entry = $dh->read())) {
		if (($entry != '.') && ($entry != '..')) {
			$ftphdl = ftp_connect($_SESSION['wspvars']['ftphost'], $_SESSION['wspvars']['ftpport']);
			$login = ftp_login($ftphdl, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
			if (is_dir("$src/$entry")) {
				@ftp_mkdir($ftphdl, "$dest/$entry");
//				@mkdir("$src/$entry");
				copyTree("$src/$entry", "$dest/$entry");
			}
			else if (is_file("$src/$entry")) {
				ftp_put($ftphdl, "$dest/$entry", "$src/$entry", FTP_BINARY);
//				copy("$src/$entry", "$dest/$entry");
			}	// if
			ftp_close($ftphdl);
		}	// if
	}	// while

}	// copyTree()

/**
* Zugriffsrechte entfernen
*/
function delRestrictions($aRights) {
	foreach ($aRights as $guid => $value) {
		// globale Definition des Zugriffsrechts loeschen
		$right_sql = "DELETE FROM `wsprights` WHERE `guid` = '".escapeSQL(trim($guid))."'";
		doSQL($right_sql);
		// individuelle Zugriffsrechte der User loeschen
		$rest_sql = "SELECT `rid`, `rights` FROM `restrictions`";
		$rest_res = doSQL($sql);
		if ($rest_res['num']>0) {
			foreach ($rest_res['set'] AS $rrk => $rrv) {
				$restriction = unserializeBroken($rrv['rights']);
				if (isset($restriction[$guid])) {
					unset($restriction[$guid]);
					$upd_sql = "UPDATE `restrictions` SET `rights` = '".escapeSQL(serialize($restriction))."' WHERE `rid` = ".intval($rrv['rid']);
					doSQL($upd_sql);
				}
			}
		}
	}
}

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

/**
* Modul loeschen
*/
function modUninstall() {
	global $usevar, $id, $wspvars;

	$sql = "SELECT `archive`,`name`,`id` FROM `modules` WHERE `id` = ".$id;
	$rs = mysql_query($sql) or die(writeMySQLError($sql));
	$wsparchive = $_SERVER['DOCUMENT_ROOT']."/wsp/modules/".mysql_db_name($rs, 0, 'archive');
	$tmppath = $_SERVER['DOCUMENT_ROOT']."/wsp/tmp/".$_SESSION['wspvars']['usevar']."/modules/p".mysql_db_name($rs, 0, 'archive');

    doSQL("DELETE FROM `wsprights` WHERE `guid` = '".escapeSQL(trim($key))."'");

	@mkdir($tmppath);
	exec("cd ".$tmppath."; tar xzf ".$wsparchive);

	// Setup-Infos des Moduls laden

	require "$tmppath/setup.php";
	$modsetup = new modsetup();

	// Menï¿½-Eintrï¿½ge loeschen
	//
	// read menu-entry to hide the menu live
	//
	$hidemenu_sql = "SELECT `id` FROM `wspmenu` WHERE `module_guid`='".$modsetup->getGUID()."'";
	$hidemenu_res = mysql_query($hidemenu_sql);
	
	$sql = "DELETE FROM `wspmenu` WHERE `module_guid`='".$modsetup->getGUID()."'";
	mysql_query($sql) or die(writeMySQLError($sql));

	// Interpreter-Eintrï¿½ge loeschen
	foreach ($modsetup->getParser() as $guid => $value) {
		$sql = "DELETE FROM `interpreter` WHERE `guid`='$guid'";
		mysql_query($sql) or die(writeMySQLError($sql));
	}	// foreach

	// Selfvars loeschen
	$sql = "DELETE FROM `selfvars` WHERE `module_guid`='".$modsetup->getGUID()."'";
	mysql_query($sql) or die(writeMySQLError($sql));

	// Modul-Eintrag loeschen
	$sql = "DELETE FROM `modules` WHERE `guid`='".$modsetup->getGUID()."'";
	mysql_query($sql) or die(writeMySQLError($sql));

	// Zugriffsrechte entfernen
	delRestrictions($modsetup->cmsRights());

	// Tabellen aus Datenbank loeschen
	if (count($modsetup->getSQLDescribe())>0) {
		foreach ($modsetup->getSQLDescribe() as $sql) {
		//	mysql_query("DROP TABLE `".$sql['tablename']."`");
		}

	}

	delTree($tmppath);
//	unlink($_SERVER['DOCUMENT_ROOT']."/wsp/modules/".mysql_db_name($rs, 0, 'archive'));
	ftpDeleteFile($_SESSION['wspvars']['ftpbasedir']."/wsp/modules/".mysql_db_name($rs, 0, 'archive'));

	ob_start();
	?>
	<p>Das Modul '<?php echo mysql_db_name($rs, 0, 'name'); ?>' wurde erfolgreich deinstalliert.</p>
	<script language="JavaScript" type="text/javascript">
	<!--
	document.getElementById('m_<?php echo (mysql_result($hidemenu_res, 0)+20); ?>').style.display = 'none';
	// -->
	</script>

	<?php
	$buf = ob_get_contents();
	ob_end_clean();

	return $buf;
	}	// modUninstall()

/**
 * Setup-Routine des Modules aufrufen
 */
function modSetup() {
	global $usevar, $id, $wspvars;

	$sql = "SELECT `modsetup`, `name` FROM `modules` WHERE `id` = ".$id;
	$rs = mysql_query($sql) or die(writeMySQLError($sql));
	include 'data/modsetup/'.mysql_db_name($rs, 0, 'modsetup');
	echo '<fieldset class="text"><h2>Einstellungen f&uuml;r das Modul "'.mysql_db_name($rs, 0, 'name').'"</h2></fieldset>';
	modulSettings();
}	// modSetup()

function modRights() {
	global $usevar, $id, $wspvars;
	
	$mod_sql = "SELECT * FROM `wspmenu` WHERE `module_guid` = '".$_POST['module_guid']."' ORDER BY `parent_id` ASC";
	$mod_res = mysql_query($mod_sql) or die(writeMySQLError($mod_sql));
	$mod_num = mysql_num_rows($mod_res);
	
	if ($mod_num>0):
		$modguid = mysql_result($mod_res, 0, 'guid');
		?>
		<fieldset><h2><?php echo returnIntLang('modrights rights for module'); ?> "<?php echo mysql_result($mod_res, 0, 'title') ?>"</h2></fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('str legend'); ?></legend>
			<p><?php echo returnIntLang('modrights rights legend'); ?></p>
		</fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('modrights activate modrights'); ?></legend>
			<form name="setrightsform" id="setrightsform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<?php if ($mod_num>1): 
				$checkid = array();
				for ($mres=1; $mres<$mod_num; $mres++):
					$checkid[] = "document.getElementById('setrights_".intval(mysql_result($mod_res, $mres, 'id'))."').checked";
				endfor;
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
				<?php for ($mres=0; $mres<$mod_num; $mres++):
					
                    $rights_sql = "SELECT * FROM `wsprights` WHERE `guid` = '".mysql_real_escape_string(trim(mysql_result($mod_res, $mres, 'guid')))."'";
					$rights_res = doSQL($rights_sql);
					$rights_num = $rights_res['num'];
									
					?>
					<tr>
						<td class="tablecell two"><?php 
						if (intval(mysql_result($mod_res, $mres, 'parent_id'))>0): echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; endif;
						echo mysql_result($mod_res, $mres, 'title'); ?></td>
						<td class="tablecell one"><input type="checkbox" name="setrights[<?php echo mysql_result($mod_res, $mres, 'guid'); ?>]" id="setrights_<?php echo mysql_result($mod_res, $mres, 'id'); ?>" value="1" <?php if ($rights_num>0): echo "checked=\"checked\""; endif; if (intval(mysql_result($mod_res, $mres, 'parent_id'))>0): echo " onchange=\"checkParent(".intval(mysql_result($mod_res, $mres, 'parent_id')).");\""; else: echo " onchange=\"checkParent(".intval(mysql_result($mod_res, $mres, 'id')).");\" readonly=\"readonly\" "; endif; ?> /><input type="hidden" name="guid[<?php echo mysql_result($mod_res, $mres, 'guid'); ?>]" value="<?php echo mysql_result($mod_res, $mres, 'guid'); ?>"></td>
						<td class="tablecell one"><?php echo returnIntLang('modrights rights open name'); ?>:</td>
						<td class="tablecell four"><input type="text" name="modname[<?php echo mysql_result($mod_res, $mres, 'guid'); ?>]" value="<?php echo mysql_result($mod_res, $mres, 'describ'); ?>" class="one full" /></td>
					</tr>
				<?php endfor; ?>
			</table>
			<input type="hidden" name="op" value="setrights">
			</form>
		</fieldset>
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
	$mod_res = mysql_query($mod_sql) or die(writeMySQLError($mod_sql));
	$mod_num = mysql_num_rows($mod_res);
	
	if ($mod_num>0):
		$modguid = mysql_result($mod_res, 0, 'guid');
		?>
		<fieldset><h2><?php echo returnIntLang('modrights rights for plugin'); ?> "<?php echo mysql_result($mod_res, 0, 'pluginname'); ?>"</h2></fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('str legend'); ?></legend>
			<p><?php echo returnIntLang('modrights rights for plugin info'); ?></p>	
		</fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('modrights activate modrights'); ?></legend>
			<form name="setrightsform" id="setrightsform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<table border="0" cellspacing="0" cellpadding="3" width="100%">
				<?php 
				
				for ($mres=0; $mres<$mod_num; $mres++):
					
					$rights_sql = "SELECT * FROM `wsprights` WHERE `guid` = '".mysql_result($mod_res, $mres, 'guid')."'";
					$rights_res = doSQL($rights_sql);
					$rights_num = $rights_res['num'];
					
					?>
					<tr>
						<td width="25%"><?php 
						
						echo mysql_result($mod_res, $mres, 'pluginname'); ?></td>
						<td width="75%"><input type="checkbox" name="setrights[<?php echo mysql_result($mod_res, $mres, 'guid'); ?>]" id="setrights_<?php echo mysql_result($mod_res, $mres, 'id'); ?>" value="1" <?php if ($rights_num>0): echo "checked=\"checked\""; endif; ?> /><input type="hidden" name="guid[<?php echo mysql_result($mod_res, $mres, 'guid'); ?>]" value="<?php echo mysql_result($mod_res, $mres, 'guid'); ?>"> <?php echo returnIntLang('str description'); ?> <input type="text" name="modname[<?php echo mysql_result($mod_res, $mres, 'guid'); ?>]" value="<?php echo mysql_result($mod_res, $mres, 'pluginname'); ?>"></td>
					</tr>
					<?php
				
				endfor;
				
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
		$ftp = ftp_connect($_SESSION['wspvars']['ftphost'], $_SESSION['wspvars']['ftpport']);
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

// get l|ost int|erpreter NOT associated to a module
$lint_res = doSQL("SELECT * FROM `interpreter` AS i WHERE `module_guid` NOT IN (SELECT `guid` FROM `modules`)");
if ($lint_res>0) {
    foreach ($lint_res['set'] AS $lik => $liv) {
        $res = doSQL("INSERT INTO `modules` SET `name` = '".escapeSQL(trim($liv['name']))."', `version` = '".escapeSQL(trim($liv['version']))."', `guid` = '".escapeSQL(trim($liv['module_guid']))."', `archive` = NULL, `dependencies` = NULL, `isparser` = 1, `iscmsmodul` = 0, `ismenu` = 0, `modsetup` = NULL, `settings` = NULL, `affectedcontent` = NULL, `filelist` = NULL");
        if ($res['aff']==1) {
            addWSPMsg('errormsg', returnIntLang('modules lost and found interpreter1')." ".trim($liv['name'])." ".trim($liv['version']).returnIntLang('modules lost and found interpreter2'));
        } else {
            if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg('errormsg', var_export($res, true));
            }
        }
    }
}


$modset = array();
$mods_num = getNumSQL("SELECT * FROM `modules` WHERE `ismenu` != 1 ORDER BY `name`");
$menumod_num = getNumSQL("SELECT * FROM `modules` WHERE `ismenu` = 1 ORDER BY `name`");
$parsermod_num = getNumSQL("SELECT * FROM `modules` WHERE `isparser` = 1 && `iscmsmodul` != 1 ORDER BY `name`");
$cmsmod_num = getNumSQL("SELECT * FROM `modules` WHERE `iscmsmodul` = 1 ORDER BY `name`");
$addmod_num = getNumSQL("SELECT * FROM `modules` WHERE `ismenu` != 1 && `isparser` != 1 && `iscmsmodul` != 1 ORDER BY `name`");

$modules_sql = "SELECT * FROM `modules` ORDER BY `name`";
$modules_res = doSQL($modules_sql);
foreach ($modules_res['set'] AS $mkey => $mvalue) {
    $mvalue['isparser'] = boolval($mvalue['isparser']);
    $mvalue['iscmsmodul'] = boolval($mvalue['iscmsmodul']);
    $mvalue['ismenu'] = boolval($mvalue['ismenu']);
    $modset[trim($mvalue['name'])] = $mvalue;
}

$plugin_sql = "SELECT * FROM `wspplugins` ORDER BY `pluginname`";
$plugin_res = doSQL($plugin_sql);
foreach ($plugin_res['set'] AS $pkey => $pvalue) {
    $modset[trim($pvalue['pluginname'])] = array(
        'id' => $pvalue['id'],
        'name' => trim($pvalue['pluginname']),
        'version' => NULL,
        'guid' => trim($pvalue['guid']),
        'archive' => NULL,
        'dependencies' => NULL,
        'isparser' => false,
        'iscmsmodul' => false,
        'ismenu' => false,
        'isplugin' => true,
        'modsetup' => NULL,
        'settings' => NULL,
        'affectedcontent' => NULL,
        'filelist' => NULL,
    );
}

// get update information from server
$serverversion =
$servertag = 
$serverfile = 
    array();
$values = false;
$xmldata = '';
$defaults = array( 
    CURLOPT_URL => 'https://update.wsp-server.info/versions/modules/', 
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

// check all existing interpreter for method-errors
$ip_sql = 'SELECT `sid`, `name`, `version`, `parsefile` FROM `interpreter`';
$ip_res = doSQL($ip_sql);
if ($ip_res['num']>0) {
    // create reference array $ci with clsInterpreter
    $ci = array();
    $rf = new ReflectionClass('clsInterpreter');
    //run through all methods.
    foreach ($rf->getMethods() as $method) {
        $ci[$method->name] = array();
        //run through all parameters of the method.
        foreach ($method->getParameters() as $parameter) {
            $ci[$method->name][$parameter->getName()] = $parameter->getType();
        }
    }
    unset($rf);
    $er = error_reporting();
    error_reporting(0);
    foreach ($ip_res['set'] AS $irk => $irv) {
        if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".$irv['parsefile'])) {
            include DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".$irv['parsefile'];
            $me = array();
            $rf = new ReflectionClass($interpreterClass);
            //run through all methods
            foreach ($rf->getMethods() as $method) {
                $me[$method->name] = array();
                //run through all parameters of the method.
                foreach ($method->getParameters() as $parameter) {
                    $me[$method->name][$parameter->getName()] = $parameter->getType();
                }
                // do compare
                if (isset($ci[$method->name])) {
                    if (count(array_diff_key($ci[$method->name], $me[$method->name]))>0 || count(array_diff_key($me[$method->name], $ci[$method->name]))>0) {
                        addWSPMsg('errormsg', returnIntLang('modules method error in interpreter1')." ".trim($irv['name'])." ".trim($irv['version'])." ".returnIntLang('modules method error in interpreter2')." ".$method->name." ".returnIntLang('modules method error in interpreter3'));
                    }
                }
            }
        }
        else {
            addWSPMsg('errormsg', returnIntLang('modules lost file interpreter1')." ".trim($irv['name'])." ".trim($irv['version']).returnIntLang('modules lost file interpreter2'));
        }
    }
    error_reporting($er);
}

// head der datei

require ("./data/include/header.inc.php");
require ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");

// resolving if modules or plugins are avaiable
$countmodules = $modules_res['num'] + $plugin_res['num'];

?>
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('modules headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('modules info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?> 
            <?php if (trim($op)=='op') { ?>
                <div class="row">
                    <?php

                    var_export($op);
                    var_export($mk);

                    if ($op=='modrights') {
                        modRights();
                    }
                    elseif ($op=='pluginrights') {
                        pluginRights();
                    }
                    elseif ($op == 'modsetup') {
                        modSetup();
                    }
                    elseif ($op == 'plugincheckuninstall') {
                        pluginCheckUninstall();
                    }

                    ?>
                </div>
            <?php } else if ($countmodules>0) { ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('modules overview'); ?></h3>
                                <div class="right">
                                    <div class="dropdown">
                                        <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-bars"></i> </a>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="?so=1"><i class="fas <?php echo ((isset($_SESSION['wspvars']['modshow']) && isset ($_SESSION['wspvars']['modshow']['parser']) && $_SESSION['wspvars']['modshow']['parser']===true)?'fa-check-square':'fa-external-link-square-alt'); ?>"></i> <?php echo returnIntLang('modules show parser'); ?></a></li>
                                            <li><a href="?so=2"><i class="fas <?php echo ((isset($_SESSION['wspvars']['modshow']) && isset ($_SESSION['wspvars']['modshow']['modules']) && $_SESSION['wspvars']['modshow']['modules']===true)?'fa-check-square':'fa-cog'); ?>"></i> <?php echo returnIntLang('modules show modules'); ?></a></li>
                                            <li><a href="?so=3"><i class="fas <?php echo ((isset($_SESSION['wspvars']['modshow']) && isset ($_SESSION['wspvars']['modshow']['menus']) && $_SESSION['wspvars']['modshow']['menus']===true)?'fa-check-square':'fa-bars'); ?>"></i> <?php echo returnIntLang('modules show menus'); ?></a></li>
                                            <li><a href="?so=4"><i class="fas <?php echo ((isset($_SESSION['wspvars']['modshow']) && isset ($_SESSION['wspvars']['modshow']['plugins']) && $_SESSION['wspvars']['modshow']['plugins']===true)?'fa-check-square':'fa-plus-square'); ?>"></i> <?php echo returnIntLang('modules show plugins'); ?></a></li>
                                            <li><a href="?so=5"><i class="fas <?php echo ((isset($_SESSION['wspvars']['modshow']) && isset ($_SESSION['wspvars']['modshow']['extensions']) && $_SESSION['wspvars']['modshow']['extensions']===true)?'fa-check-square':'fa-swatchbook'); ?>"></i> <?php echo returnIntLang('modules show extensions'); ?></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="">
                                    <?php if($modules_res['num']>0 || $plugin_res['num']>0) { ?>
                                        <ul class="list-unstyled list-files">
                                            <?php 
                                            foreach($modules_res['set'] AS $mk => $mv) {
                                                $mv['isextension'] = (($mv['isparser']==0 && $mv['iscmsmodul']==0 && $mv['ismenu']==0)?1:0);
                                                ?>
                                                <li class="file-item" <?php

                                                    if (isset($_SESSION['wspvars']['modshow']) && count($_SESSION['wspvars']['modshow'])>0) {
                                                        $style = ' style="display: none;" ';
                                                        if (isset($_SESSION['wspvars']['modshow']['modules']) && $mv['iscmsmodul']==1) {
                                                            $style = '';
                                                        }
                                                        if (isset($_SESSION['wspvars']['modshow']['parser']) && $mv['isparser']==1 && $mv['iscmsmodul']==0) {
                                                            $style = '';
                                                        }
                                                        if (isset($_SESSION['wspvars']['modshow']['menus']) && $mv['ismenu']==1 && $mv['iscmsmodul']==0) {
                                                            $style = '';
                                                        }
                                                        if (isset($_SESSION['wspvars']['modshow']['plugins']) && $mv['isplugin']==1 && $mv['iscmsmodul']==0) {
                                                            $style = '';
                                                        }
                                                        echo $style;
                                                    }

                                                    ?>>
                                                    <a href="./moddetails.php?mk=<?php echo base64_encode($mv['guid']); ?>">
                                                        <?php if (isset($mv['iscmsmodul']) && $mv['iscmsmodul']==1) {
                                                            echo "<span class='file-preview xls'><i class='fa fa-cog'></i><span class='file-extension'>.mod</span></span>";
                                                        }
                                                        else if (isset($mv['isparser']) && $mv['isparser']==1) {
                                                            echo "<span class='file-preview pdf'><i class='fas fa-external-link-square-alt'></i><span class='file-extension'>.parser</span></span>";
                                                        }
                                                        else if (isset($mv['ismenu']) && $mv['ismenu']==1) {
                                                            echo "<span class='file-preview audio'><i class='fas fa-bars'></i><span class='file-extension'>.menu</span></span>";
                                                        }
                                                        else if (isset($mv['isplugin']) && $mv['isplugin']==1) {
                                                            echo "<span class='file-preview doc'><i class='fas fa-plus-square'></i><span class='file-extension'>.plugin</span></span>";
                                                        }
                                                        else {
                                                            echo "<span class='file-preview doc'><i class='fas fa-swatchbook'></i><span class='file-extension'>.xtn</span></span>";
                                                        }
                                                        
                                                        ?>
                                                    </a>
                                                    <div class="file-info">
                                                        <a href="./moddetails.php?mk=<?php echo base64_encode($mv['guid']); ?>">
                                                            <span class="file-name"><?php echo $mv['name']." ".$mv['version']; 

                                                                $serverdata = (isset($mv['tag']))?array_keys($servertag, trim($mv['tag'])):array();
                                                                if (is_array($serverdata) && count($serverdata)==1) {
                                                                    if ($serverversion[intval($serverdata[0])]!=$mv['version']) {
                                                                        if (compareVersion($mv['version'], $serverversion[intval($serverdata[0])])>0) {
                                                                            echo " <i class='fas fa-cloud-download-alt'></i>";
                                                                        };
                                                                    }
                                                                }
                                                
                                                                if (trim($mv['filelist'])!='') { echo " <i class='fas fa-check'></i>"; }

                                                            ?></span>
                                                        </a>
                                                        <span class="file-date">
                                                            <?php
                                                            
                                                            $itp_set = array();
                                                            // get associated interpreter for module 
                                                            $itp_sql = "SELECT `name`, `version`, `guid` FROM `interpreter` WHERE `module_guid` = '".escapeSQL($mv['guid'])."'";
                                                            $itp_res = doSQL($itp_sql);
                                                            foreach($itp_res['set'] AS $sk => $sv) { $itp_set[] = $sv['guid']; }
                                                
                                                            $con_sql = "SELECT c.`cid` AS cid, c.`mid` AS cmid, c.`globalcontent_id` AS gid, c.`trash` AS ctrash, m.`trash` AS mtrash, c.`description` AS cdesc FROM `content` AS c, `menu` AS m WHERE c.`mid` = m.`mid` AND c.`interpreter_guid` IN ('".implode("','", $itp_set)."') GROUP BY c.`cid`";
                                                            $con_res = doSQL($con_sql);
                                                            foreach ($con_res['set'] AS $csk => $csv) {
                                                                if ($csv['mtrash']==1) {
                                                                    $con_res['num']--;
                                                                }
                                                            }
                                                            if ($con_res['num']>0) {
                                                                echo '<i class="fas fa-sitemap"></i> ';
                                                            }
                
                                                            $gcon_sql = "SELECT `id` AS gid, `trash` FROM `content_global` WHERE `trash` = 0 AND `interpreter_guid` IN ('".implode("','", $itp_set)."')";   
                                                            $gcon_res = doSQL($gcon_sql);
                                                            
                                                            if ($gcon_res['num']>0) {
                                                                echo '<i class="fas fa-globe"></i> ';
                                                            }
                                                
                                                            ?>&nbsp;
                                                        </span>
                                                        <div class="dropdown">
                                                            <a href="#" class="toggle-dropdown" data-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a>
                                                            <ul class="dropdown-menu dropdown-menu-right">
                                                                <li><a href="./moddownload.php?mk=<?php echo base64_encode($mv['guid']); ?>"><i class="fas fa-file-download"></i> <?php echo returnIntLang('str download'); ?></a></li>
                                                                <li><a href="./moddetails.php?mk=<?php echo base64_encode($mv['guid']); ?>"><i class="fas fa-file-invoice"></i> <?php echo returnIntLang('str properties'); ?></a></li>
                                                                <?php if($con_res['num']==0 && $gcon_res['num']==0) { ?>
                                                                    <li><a onclick="removeMod('<?php echo prepareTextField($mv['name']." ".$mv['version']); ?>','<?php echo base64_encode($mv['guid']); ?>');"><i class="fa fa-trash"></i> <?php echo returnIntLang('str delete'); ?></a></li>
                                                                <?php } ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php } ?>
                                        </ul>
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
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <p><a href="./modinstall.php" class="btn btn-primary"><?php echo returnIntLang('modules modinstall', false); ?></a></p>
                    </div>
                </div>
            <?php } else { ?>
                <div class="row">
                    <div class="col-md-12">
                        <h3 style="text-align: center; font-weight: 300"><?php echo returnIntLang('modules just install your first module', false); ?></h3>
                        <h1 style="text-align: center; font-size: 8vw; padding-top: 1vw">
                            <a href="./modinstall.php"><i class="fas fa-cloud-download-alt"></i></a>
                        </h1>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php include ("./data/include/footer.inc.php"); ?>