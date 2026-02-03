<?php
/**
 * Modulverwaltung
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2023-01-06
 * fixed error with version compare
 * 2025-01-19
 * fixed bug with false return of module installation server
 */

// enable/disable warnings for false class definitions
error_reporting(E_ALL);

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
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
/* page specific includes -------------------- */
require ("./data/include/clssetup.inc.php");
require ("./data/include/clsinterpreter.inc.php");
/* define page specific vars ----------------- */
/* define page specific functions ------------ */

// Kopiert eine Verzeichnisstruktur mit allen Files und Subdirs an den angegebenen Platz
// gibt es auch schon in der system.php
if (!(function_exists('copyTree'))) {
    function copyTree($src, $dest) {
        
		$src = str_replace('//', '/', $src);
		$dest = str_replace('//', '/', $dest);
		$ftp = null;
		$ftpt = 0;

		if (isset($_SESSION['wspvars']['directwriting']) && $_SESSION['wspvars']['directwriting']===true) {
			$usedirect = true;
		} else {
			while (empty($ftp) && $ftpt<3) {
				$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
				if ($ftp!==false) {
					if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { 
						$ftp = false; 
					}
				}
				if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { 
					ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); 
				}
				$ftpt++;
			}
		}
        
        if ($ftp) {
            $dh = dir($src);
            while (false !== ($entry = $dh->read())) {
                if (substr($entry, 0, 1) != '.') {
                    if (is_dir($src."/".$entry)) {
                        @ftp_mkdir($ftp, $dest."/".$entry);
                        copyTree($src."/".$entry, $dest."/".$entry);
                    }
                    elseif (is_file($src."/".$entry)) {
                        @ftp_put($ftp, $dest."/".$entry, $src."/".$entry, FTP_BINARY);
                    }
                }
            }
            ftp_close($ftp);
            return true;
        } 
		else if ($usedirect) {
			$dh = dir($src);
			$dest = $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasediradd'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/data/interpreter/';
            $dest = str_replace('//', '/', $dest);
			while (false !== ($entry = $dh->read())) {
				if (substr($entry, 0, 1) != '.') {
                    if (is_dir($src."/".$entry)) {
                        /*
						mkdir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'] . '/'. $_SESSION['wspvars']['wspbasediradd']. '/' . returnPath(intval($mid_res['set'][0]['mid']), 1, '', $lang))), 0755, true)
						*/
                        copyTree($src."/".$entry, $dest."/".$entry);
                    }
                    elseif (is_file($src."/".$entry)) {
						copy(
							$src."/".$entry, 
							str_replace('//', '/', $dest."/".$entry)
						);
                    }
                }
            }
            return true;
		}
        else {
            addWSPMsg('errormsg', 'modinstall copytree failed');
            return false;
        }
    }
}

/**
* Zugriffsrechte fuer das neue Modul setzen
*/
function setRestrictions($aRights) {
	foreach ($aRights as $guid => $value) {
		// Recht in der Rechtetabelle hinterlegen
		$sRights = serialize($value['rights']);
		$sLabels = serialize($value['namerights']);

		$sql = "SELECT `id` FROM `wsprights` WHERE `guid` = '".$guid."'";
		$rsRight = doSQL($sql);
		// Recht hinzufuegen
		if ($rsRight['num'] == 0) {
			$sql = "INSERT INTO `wsprights`
						(`guid`, `right`, `standard`, `possibilities`, `labels`)
						VALUES('$guid', '".$value['title']."', '".$value['standard']."', '$sRights', '$sLabels')";
		}
		// Recht updaten
		else {
			$sql = "UPDATE `wsprights`
						SET `right`='".$value['title']."',
							`standard`='".$value['standard']."',
							`possibilities`='".$sRights."',
							`labels`='".$sLabels."'
						WHERE `guid`='".$guid."'";
		}	// if
		doSQL($sql);

		// individuelle Zugriffsrechte mit Standard belegen, sofern nicht bereits vergeben vergeben
		$sql = "SELECT `rid`, `rights` FROM `restrictions`";
		$rsRestrictions = doSQL($sql);
		if ($rsRestrictions['num'] > 0) {
			foreach ($rsRestrictions['set'] AS $row) {
				$restriction = unserializeBroken($row['rights']);
				if ((!isset($restriction[$guid])) || ((count($value['rights'])-1) < ($restriction[$guid]))) {
					$restriction[$guid] = $value['standard'];
					doSQL("UPDATE `restrictions` SET `rights`='".serialize($restriction)."' WHERE `rid` = ".intval($row['rid']));
				}	// if
			}	// if
		}	// if
	}	// foreach
}	// setRestrictions()

/**
* Zugriffsrechte entfernen
*/
function delRestrictions($aRights) {
	foreach ($aRights as $guid => $value) {
		// globale Definition des Zugriffsrechts loeschen
		doSQL("DELETE FROM `wsprights` WHERE `guid` = '".escapeSQL(trim($guid))."'");

		// individuelle Zugriffsrechte der User loeschen
		$sql = "SELECT `rid`, `rights` FROM `restrictions`";
		$rsRestrictions = doSQL($sql);
		if ($rsRestrictions['num'] > 0) {
			foreach ($rsRestrictions['set'] AS $row) {
				$restriction = unserialize($row['rights']);
				if (isset($restriction[$guid])) {
					unset($restriction[$guid]);
					doSQL("UPDATE `restrictions` SET `rights`='".serialize($restriction)."' WHERE `rid` = ".intval($row['rid']));
				}	// if
			}	// while
		}	// if
	}	// foreach
}	// delRestrictions()

/**
* Parser registrieren
*/
function regParser($modul, $aParser, $modguid) {
	if (count($aParser) > 0) {
        foreach ($aParser as $value) {
			if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules/install".$modul."/wsp/data/interpreter/".$value)) {
				include_once ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules/install".$modul."/wsp/data/interpreter/".$value);
				$parser = new $interpreterClass();
				if (property_exists($parser, 'dbCon')) {
					$parser->dbCon = $_SESSION['wspvars']['dbcon'];
				}
				$sql = "SELECT `version` FROM `interpreter` WHERE `guid` = '".$parser->guid."'";
				$res = doSQL($sql);
	
				if ($res['num'] == 0) {
					// parser doesn't exist
					$sql = "INSERT INTO `interpreter`
						SET `name` = '".escapeSQL($parser->title)."',
							`classname` = '".escapeSQL($parser->classname)."',
							`htmlmode` = '".escapeSQL($parser->htmlmode)."',
							`parsefile` = '".escapeSQL($parser->parsefile)."',
							`maxfields` = '".escapeSQL($parser->maxfields)."',
							`version` = '".escapeSQL($parser->version)."',
							`guid` = '".escapeSQL($parser->guid)."',
							`module_guid` = '".escapeSQL($modguid)."',
							`phpvars` = '".intval($parser->phpvars)."',
							`bodyfunc` = '".intval($parser->bodyfunc)."',
							`metascript` = '".intval($parser->metascript)."'";
					$res = doSQL($sql);
                    if ($res['res']) {
                            addWSPMsg('resultmsg', returnIntLang('modinstall interpreter registrated'));
                        }
				} else {
					// parser already exists
					if (compVersion(trim($res['set'][0]['version']), $parser->version)>=0) {
						// update older version
						$sql = "UPDATE `interpreter` SET `name` = '".escapeSQL(trim($parser->title))."', `classname` = '".escapeSQL($parser->classname)."', `htmlmode` = '".escapeSQL($parser->htmlmode)."', `parsefile` = '".escapeSQL($parser->parsefile)."', `maxfields` = '".escapeSQL($parser->maxfields)."', `version` = '".escapeSQL($parser->version)."', `phpvars` = '".intval($parser->phpvars)."', `bodyfunc` = '".intval($parser->bodyfunc)."', `metascript` = '".intval($parser->metascript)."' WHERE `guid`='".$parser->guid."'";
						$res = doSQL($sql);
                        if ($res['res']) {
                            addWSPMsg('resultmsg', returnIntLang('modinstall interpreter updated'));
                        }
                    }
				}
			}
		}
	}	// if
}	// regParser()

/**
* Menue registrieren
*/
function regMenu($modul, $aMenuParser, $modguid) {

	if (count($aMenuParser) > 0) {
		foreach ($aMenuParser as $value) {
			require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules/install".$modul."/data/menu/".$value);
			$menuparser = new $menuClass();
			$sql = "SELECT `version` FROM `templates_menu` WHERE `guid`='".$menuparser->guid."'";
			$res = doSQL($sql);
			// Menu-Parser noch nicht vorhanden
			if ($res['num']==0) {
				$sql = "INSERT INTO `templates_menu`
							(`title`, `describ`, `parser`, `version`, `guid`, `module_guid`)
							VALUES('".$menuparser->title."',
								'".$menuparser->describ."',
								'".$menuparser->parsefile."',
								'".$menuparser->version."',
								'".$menuparser->guid."',
								'".$modguid."')";
				doSQL($sql);
			}
			// Parser bereits vorhanden
			else {
				if (compVersion(trim($res['set'][0]['version']), $parser->version)>=0) {
					// older version is overwritten
					$sql = "UPDATE `templates_menu`
								SET `title`='".$menuparser->title."',
									`describ`='".$menuparser->describ."',
									`parse`='".$menuparser->parsefile."',
									`version`='".$menuparser->version."'
								WHERE `guid`='".$menuparser->guid."'";
					doSQL($sql);
				}	// if
			}	// if
		}	// foreach
	}	// if
}	// regMenu()

/**
* Parser registrieren
*/
function regPlugin($modul, $aPluginInfo, $modguid) {
	if (count($aPluginInfo) > 0):
		$sql = "SELECT `pluginfolder` FROM `wspplugins` WHERE `guid` = '".$aPluginInfo[$modguid]["guid"]."'";
		$res = doResultSQL($sql);
		if ($res!==false && trim($res)!=''):
			doSQL("INSERT INTO `wspplugins` SET `guid` = '".$aPluginInfo[$modguid]['guid']."', `pluginname` = '".$aPluginInfo[$modguid]['pluginname']."', `pluginfolder` = '".$aPluginInfo[$modguid]['pluginfolder']."'");
		endif;
	endif;	
	}	// regPlugin()

/**
 * SelfVars registrieren
 *
 * @param array $aSelfVars
 * @param string $modguid
 */
function regSelfVars($aSelfVars, $modguid) {
	foreach ($aSelfVars as $guid => $aSelfVar) {
		$sql = 'SELECT COUNT(`id`) AS `cnt`
					FROM `selfvars`
					WHERE `name`="'.$aSelfVar[0].'"';
		$rsSelfVar = doResultSQL($sql);
		if (intval($rsSelfVar)>0) {
			$sql = 'UPDATE `selfvars`
						SET `selfvar`="'.escapeSQL($aSelfVar[1]).'",
							`module_guid`="'.$modguid.'" WHERE `guid`="'.$guid.'" ';
		}
		else {
			$sql = 'INSERT INTO `selfvars`
						(`name`, `selfvar`, `module_guid`, `guid`)
						VALUES("'.escapeSQL($aSelfVar[0]).'", "'.escapeSQL($aSelfVar[1]).'", "'.$modguid.'", "'.$guid.'")';
		}	// if
		doSQL($sql);
	}	// foreach
}	// regSelfVars()

/**
 * WSP updaten
 */
function modUpdate($data) {
	$fh = fopen($_SESSION['wspvars']['updateuri'].'/updater.php?key='.$_SESSION['wspvars']['updatekey'].'&file='.$data, 'r');
	$fileupdate = '';
	while (!feof($fh)) {
		$fileupdate .= fgets($fh, 4096);
	}	// if
	fclose($fh);

	$tmppfad = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".basename($data);
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


if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=='modcheckinstall'):
	// choose temp. user directory
	$tmpdir = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules/";
	$modfilename = "";
	$install = false;
	// if dir doesnt exits create
	if (!(is_dir($tmpdir))):
        mkdir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules", 0777, true);
        if (!(is_dir($tmpdir))):
            addWSPMsg('noticemsg', returnIntLang('modinstall tmp dir does not exist'));
        endif;
	endif;
	if (isset($_POST['serverfile']) && trim($_POST['serverfile'])!=""):
		
        $serverfile = trim($_POST['serverfile']);
		$fileupdate = '';
        if (_isCurl()) {
            $defaults = array( 
                CURLOPT_URL => trim($_SESSION['wspvars']['updateuri'].'/updater.php?file='.$serverfile), 
                CURLOPT_HEADER => 0, 
                CURLOPT_RETURNTRANSFER => TRUE, 
                CURLOPT_TIMEOUT => 4 
            );
            $ch = curl_init();
            curl_setopt_array($ch, $defaults);    
            if( ! $fileupdate = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
            curl_close($ch);
        } 
        else {
            $fh = fopen(trim($_SESSION['wspvars']['updateuri'].'/updater.php?file='.$serverfile), 'r');
            $fileupdate = '';
            if (intval($fh)!=0):
                while (!feof($fh)):
                    $fileupdate .= fgets($fh, 4096);
                endwhile;
            endif;
            fclose($fh);
        }
        
        $install = false;
		if (!file_exists($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules")): mkdir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules", 0777, true); endif;

		$modfile = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules/".basename($serverfile)));
		$tmpdat = fopen($modfile,'w');
		if (fwrite ($tmpdat, $fileupdate)):
			$modtmpdir = str_replace("//", "/", str_replace("//", "/", $tmpdir."install".basename($serverfile)));
			$install = true;
			$modfilename = basename($serverfile);
		else:
			addWSPMsg('errormsg', returnIntLang('modinstall package could not be retrieved from server'));
		endif;
		fclose ($tmpdat);
	elseif (is_array($_FILES['modulfile'])):
		// hochgeladenes modul nehmen
		$tmpdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules/"));
		$modfile = $tmpdir.$_FILES['modulfile']['name'];
		$modfilename = $_FILES['modulfile']['name'];
		$modtmpdir = str_replace("//", "/", str_replace("//", "/", $tmpdir."install".$_FILES['modulfile']['name']));
		if (move_uploaded_file($_FILES['modulfile']['tmp_name'], $modfile)):
			$install = true;
		endif;
	else:
		addWSPMsg('errormsg', returnIntLang('modinstall no package were uploaded or set to be retrieved from server'));
	endif;
	
	if ($install && trim($modtmpdir)!=""):
		
		@mkdir($modtmpdir);
        
        try {
            $phar = new PharData($modfile);
            $phar->extractTo($modtmpdir); // extract all files
        } catch (Exception $e) {
            exec("cd ".$modtmpdir."; tar xzf ".$modfile);
        }
		
		// load modules setup file
		if (!file_exists($modtmpdir."/setup.php")):
			addWSPMsg('errormsg', "<p>".returnIntLang('modinstall package could no be unpacked has no setup or is no module')."</p>");
			$install = false;
		else:
			// check install paths
			$aDirFiles = checkTree($modtmpdir, '');
			$isin = true;
			$file = '';
			for ($i=0; $i<sizeof($aDirFiles); $i++):
				$isintemp=false;
				for($j=0; $j<sizeof($_SESSION['wspvars']['allowdir']); $j++):
					if(strstr($aDirFiles[$i], str_replace("[wsp]",'/'.$_SESSION['wspvars']['wspbasedir'],$_SESSION['wspvars']['allowdir'][$j]))):
						$isintemp=true;
						$file.="<br />".$aDirFiles[$i];
						break;
					endif;
				endfor;
				if ((!$isintemp) && ($j>count($_SESSION['wspvars']['allowdir']))):
					$isin=false;
				endif;
			endfor;
			
			if (!$isin):
				$_SESSION['wspvars']['errormsg'].= "Die Verzeichnisstruktur stimmt nicht mit der erlaubten Verzeichnisstruktur &uuml;berein<br />".$file;
				$install = false;
			endif;

			require ($modtmpdir."/setup.php");
			$modsetup = new modsetup();

			// check for allowed tables
			$aTable = array();
			$aTable = $modsetup->getSQLDescribe();
			for ($i=0; $i<sizeof($aTable); $i++):
				if (in_array($aTable[$i]['tablename'],$_SESSION['wspvars']['forbiddentables'])):
					addWSPMsg('errormsg', returnIntLang('modinstall sql table is not allowed'));
					$install = false;
				endif;
			endfor;
		
		endif;
	endif;
elseif (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=='modcancel'):
	$modfile = $_POST['modul'];
	$tmpdir = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules";
	delTree($tmpdir."/install".$modfile);
	if (@unlink($tmpdir."/".$modfile)):
		addWSPMsg('noticemsg', "<p>".returnIntLang('modinstall install canceled tempfiles removed')."</p>");
	endif;
endif;

// head der datei

include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");
?>

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('modinstall headline'); ?></h1></fieldset>
	<?php
	
	if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=='modinstall'):
		
		$modfile = $_POST['modul'];
		$tmpdir = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/modules";
        $didcopy = false;
    
		// copy modules files and directories to final destination
		if (is_dir($tmpdir."/install".$modfile."/")):
            $dh = dir($tmpdir."/install".$modfile."/");
            if ($dh!==false):
                while (false !== ($entry = $dh->read())):
                    if (($entry != '.') && ($entry != '..') && ($entry != 'setup.php')):
                        if (is_dir($tmpdir."/install".$modfile."/".$entry)):
                            $didcopy = copyTree($tmpdir."/install".$modfile."/".$entry, $_SESSION['wspvars']['ftpbasedir']."/".$entry);
                        endif;
                    endif;
                endwhile;
                $dh->close();
            endif;
        endif;
    
		flush();flush();flush();
		
        if (is_file(str_replace("//", "/", str_replace("//", "/", $tmpdir."/".$modfile)))):
            // Modul-Archiv speichern
            $ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
            if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }}
            if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }        
            if ($ftp!==false) {
                ftp_put($ftp, str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$_SESSION['wspvars']['wspbasedir']."/modules/".$modfile)), str_replace("//", "/", str_replace("//", "/", $tmpdir."/".$modfile)), FTP_BINARY);
                ftp_close($ftp);
            }
        endif;
        
        if ($didcopy && is_file(str_replace("//", "/", str_replace("//", "/", $tmpdir."/install".$modfile."/setup.php")))) {
            // Setup-Klasse des Moduls laden
            require_once ($tmpdir."/install".$modfile."/setup.php");
            $modsetup = new modsetup();
            $type = $modsetup->getType();

            // Zugriffsrechte speichern
            setRestrictions($modsetup->cmsRights());

            // Parser registrieren
//          if (isset($type['isparser']) && intval($type['isparser'])==1):
                regParser($modfile, $modsetup->getParser(), $modsetup->getGUID());
//          endif;
    
            // Menue registrieren
            if (isset($type['ismenu']) && intval($type['ismenu'])==1):
                regMenu($modfile, $modsetup->getMenuParser(), $modsetup->getGUID());
            endif;

            // plugin registrieren
            if (isset($type['isplugin']) && intval($type['isplugin'])==1):
                regPlugin($modfile, $modsetup->getPlugin(), $modsetup->getGUID());
            endif;
	
            // Selfvars registrieren
            regSelfVars($modsetup->getSelfVars(), $modsetup->getGUID());

            // Menueeintraege installieren
            if (count($modsetup->cmsMenu())) {
                foreach ($modsetup->cmsMenu() as $guid => $value) {
                    $wspmenu_sql = "SELECT `id` FROM `wspmenu` WHERE `guid` = '".$value['guid']."'";
                    $wspmenu_res = intval(doResultSQL($wspmenu_sql));
                    if ($wspmenu_res==0) {
                        // no entry set yet
                        $sql = "INSERT INTO `wspmenu` SET `guid` = '".escapeSQL(trim($value['guid']))."', `title` = '".escapeSQL(trim($value['title']))."', `link` = '".escapeSQL(trim($value['link']))."', `describ` = '".escapeSQL(trim($value['description']))."', `module_guid` = '".escapeSQL(trim($modsetup->getGUID()))."', `parent_id` = ";
                        if (!(isset($value['parent'])) || trim($value['parent'])=='') {
                            $sql.= 0;
                        } else if (trim($value['parent'])!='') {
                            $sql.= intval(doResultSQL("SELECT `id` FROM `wspmenu` WHERE `guid` = '".escapeSQL(trim($value['parent']))."'")); 
                        }
                        doSQL($sql);
                    } else {
                        $sql = "UPDATE `wspmenu` SET `title` = '".escapeSQL(trim($value['title']))."', `link` = '".escapeSQL(trim($value['link']))."', `describ` = '".escapeSQL(trim($value['description']))."', `parent_id` = ";
                        if (!(isset($value['parent'])) || trim($value['parent'])=='') {
                            $sql.= 0;
                        } else if (trim($value['parent'])!='') {
                            $sql.= intval(doResultSQL("SELECT `id` FROM `wspmenu` WHERE `guid` = '".escapeSQL(trim($value['parent']))."'")); 
                        }
                        $sql.= " WHERE `id` = ".intval($wspmenu_res);
                        doSQL($sql);
                    }
                }
            }	// if

            // create tables from array
            if (count($modsetup->getSQLDescribe())>0) {
                $db_tables=mysql_query("SHOW TABLES");
                $db_tables_num=mysql_num_rows($db_tables);
                $tables= array();
                for($i=0;$i<$db_tables_num;$i++){
                    $tables[$i]=mysql_result($db_tables,$i,0);
                }
                $i=0;
                foreach ($modsetup->getSQLDescribe() as $sql) {
                    if(in_array($sql['tablename'],$tables)){
                        if($sql['delete']!=true){
                            $table_sql="DESCRIBE `".$sql['tablename']."`";
                            $table_query=mysql_query($table_sql);
                            $table_num=mysql_num_rows($table_query);
                            //spalte ändern oder löchen
                            $fieldnames=array();
                            for($c=0;$c<$table_num;$c++){
                                $fieldname = mysql_result($table_query,$c,"Field");
                                $fieldnames[$c]=$fieldname;
                                if(array_key_exists($fieldname,$sql['fields'])){

                                    if(mysql_result($table_query,$c,"TYPE")==$sql['fields'][$fieldname]['type'] && mysql_result($table_query,$c,"NULL")==$sql[$i]['fields'][$fieldname]['null'] && mysql_result($table_query,$c,"DEFAULT")==$sql[$i]['fields'][$fieldname]['default'] && mysql_result($table_query,$c,"EXTRA")==$sql[$i]['fields'][$fieldname]['extra']){

                                    }else{
                                        //ändert Spalte
                                        $change.="ALTER TABLE `".$sql['tablename']."` MODIFY `".mysql_result($table_query,$c,"Field")."` ";
                                        if(mysql_result($table_query,$c,"TYPE")==$sql['fields'][$fieldname]['type']){

                                        }else {
                                            $change.=", ".$sql['fields'][$fieldname]['type'];
                                        }
                                        if(mysql_result($table_query,$c,"NULL")==$sql['fields'][$fieldname]['null']){

                                        }else {
                                            $change.=", ".$sql['fields'][$fieldname]['null'];
                                        }
                                        if(mysql_result($table_query,$c,"DEFAULT")==$sql['fields'][$fieldname]['default']){

                                        }else {
                                            if($sql['fields'][$fieldname]['default']!=""){
                                                $change.=", ".$sql['fields'][$fieldname]['default'];
                                            }else {
                                                $change.="";
                                            }
                                        }
                                        if(mysql_result($table_query,$c,"EXTRA")==$sql['fields'][$fieldname]['extra']){

                                        }else {
                                            $change.=", ".$sql['fields'][$fieldname]['extra'];
                                        }

                                    }
                                }else{
                                    //löscht spalte
                                    $change = "ALTER TABLE `".$sql['tablename']."` DROP COLUMN `".mysql_result($table_query,$c,"Field")."`";
                                }
                                mysql_query($change);
                            }

                            //fügt Spalte hinzu
                            foreach ($sql['fields'] as $fields) {
                                if(!in_array($fields['field'],$fieldnames)){
                                    if($fields['default']!=""){
                                        $default="default '".$fields['default']."'";
                                    }else{
                                        $default="";
                                    }
                                    mysql_query("ALTER TABLE `".$sql['tablename']."` ADD `".$fields['field']."` ".$fields['type']." ".$fields['null']." ".$default." ".$fields['extra']);
                                }
                            }
                        }else {
                            // delete Table
                            // mysql_query("DROP TABLE `".$sql['tablename']."`");
                        }
                        $dbkey=mysql_query("SHOW INDEX FROM `".$sql['tablename']."`");
                        $dbkeynum=mysql_num_rows($dbkey);
                        $dbkeys=array();
                        $name=0;

                        for($z=0;$z<$dbkeynum;$z++){

                            if(mysql_result($dbkey,$z,"Seq_in_index")>1){

                            }else{
                                if(mysql_result($dbkey,$z,"Key_name")!="PRIMARY"){
                                    mysql_query("ALTER TABLE DROP INDEX `".mysql_result($dbkey,$z,"Key_name")."`");
                                    $name++;
                                }
                            }
                        }
                        for($d=0;$d<sizeof($sql['key']);$d++){
                            if($sql['key'][$d]['name']=="PRIMARY"){
                            mysql_query("ALTER TABLE `".$sql['tablename']."` DROP PRIMARY KEY,ADD PRIMARY KEY (`".$sql['key'][$d]['value'][0]."`)");
                            }else {
                                $index.="ALTER TABLE `".$sql['tablename']."` ADD INDEX `".$sql['key'][$d]['name']."(";
                                for($x=0;$x<sizeof($sql['key'][$d]['value'])-1;$x++){
                                $index.= "`".$sql['key'][$d]['value'][$x]."`, ";
                                }
                                $index.= "`".$sql['key'][$d]['value'][sizeof($sql['key'][$d]['value'])-1]."`) ";
                                mysql_query($index);
                            }
                        }

                    } else {
                        $sql_query="CREATE TABLE `".$sql['tablename']."` ( ";
                        $count=0;
                        foreach ($sql['fields'] as $fields) {
                            if($fields['default']!=""){
                                $default="default '".$fields['default']."'";
                            }else{
                                $default="";
                            }
        //					if($count<sizeof($fields)-1){
                                $sql_query.="`".$fields['field']."` ".$fields['type']." ".$fields['null']." ".$default." ".$fields['extra'].", ";
        //					} else {
        //						$sql_query.="`".$fields['field']."` ".$fields['type']." ".$fields['null']." ".$default." ".$fields['extra'];
        //					}
                            $count++;
                        }

                        if (substr($sql_query, -2)==', ') {
                            $sql_query = substr($sql_query, 0, -2);
                        }

                        if(count($sql['key']>0)){
                            for($k=0;$k<sizeof($sql['key']);$k++){
                                if($sql['key'][$k]['name']=="PRIMARY"){
                                    $sql_query.=", PRIMARY KEY (`".$sql['key'][$k]['value'][0]."`)";
                                } else {
                                    $sql_query.=", KEY `".$sql['key'][$k]['name']."` (";
                                    for($x=0;$x<sizeof($sql['key'][$k]['value'])-1;$x++){
                                        $sql_query.= "`".$sql['key'][$k]['value'][$x]."`, ";
                                    }
                                    $sql_query.= "`".$sql['key'][$k]['value'][sizeof($sql['key'][$k]['value'])-1]."`) ";
                                }
                            }
                        }
                        $sql_query.= ");";
                        mysql_query($sql_query);
                        if(sizeof($sql['contents'])>0){
                            for($w=0;$w<sizeof($sql['contents']);$w++){
                                mysql_query($sql['contents'][$w]);
                            }
                        }
                    }

                    $i++;
                }

            }
            // create tables from sql-file
            else if (is_array($modsetup->getSQLCreate())) {
                $typeofsqlinstall = $modsetup->getSQLCreate();
                $statcount = 0;
                if (isset($typeofsqlinstall[0]) && $typeofsqlinstall[0]=="file"):
                    foreach ($modsetup->getSQLCreate() as $sql):
                        if ($sql!="file"):
                            // read file and process sql-statements
                            $sql_stats = file ($tmpdir."/install".$modfile."/".$sql);
                            foreach ($sql_stats as $sql):
                                if (doSQL($sql)['res']):
                                    $statcount = $statcount+1;
                                endif;
                            endforeach;
                        endif;
                    endforeach;
                else:
                    foreach ($modsetup->getSQLCreate() as $sql):
                        doSQL($sql);
                    endforeach;
                endif;
            }

            // Tabelle loeschen
            if (method_exists($modsetup, 'getSQLDrop')):
                if (is_array($modsetup->getSQLDrop())):
                    foreach ($modsetup->getSQLDrop() as $sql):
                        doSQL($sql);
                    endforeach;
                endif;
            endif;

            // Modul registrieren
            $sql = "SELECT `id`, `settings` FROM `modules` WHERE `guid`='".$modsetup->getGUID()."'";
            $res = doSQL($sql);
            $dependences = '';

            foreach ($modsetup->dependences() as $key => $value):
                $dependences .= $key." ";
            endforeach;
            $dependences = trim($dependences);

            if ($res['num'] == 0):
                $sql = "INSERT INTO `modules` SET
                    `name` = '".$modsetup->name()."',
                    `version` = '".$modsetup->version()."',
                    `guid` = '".$modsetup->getGUID()."',
                    `archive` = '".$modfile."',
                    `isparser` = '".(isset($type['isparser'])?intval($type['isparser']):0)."',
                    `iscmsmodul` = '".(isset($type['iscmsmodul'])?intval($type['iscmsmodul']):0)."',
                    `ismenu` = '".(isset($type['ismenu'])?intval($type['ismenu']):0)."',
                    `modsetup` = '".$modsetup->getSetup()."',
                    `settings` = '".$modsetup->getSetupDefault()."'";
            else:
                if (trim($res['set'][0]['settings'])==''):
                    $settings = $modsetup->getSetupDefault();
                else:
                    $settings = '`settings`';
                endif;
                $sql = "UPDATE `modules` SET 
                    `name` = '".$modsetup->name()."',
                    `version` = '".$modsetup->version()."',
                    `guid` = '".$modsetup->getGUID()."',
                    `archive` = '".$modfile."',
                    `dependences` = '".$dependences."',
                    `isparser` = '".(isset($type['isparser'])?intval($type['isparser']):0)."',
                    `iscmsmodul` = '".(isset($type['iscmsmodul'])?intval($type['iscmsmodul']):0)."',
                    `ismenu` = '".(isset($type['ismenu'])?intval($type['ismenu']):0)."',
                    `modsetup` = '".$modsetup->getSetup()."',
                    `settings` = '".$settings."'
                    WHERE `id` = ".intval($res['set'][0]['id']);
            endif;
            doSQL($sql);

    		delTree($tmpdir."/install".$modfile);
            unlink($tmpdir."/".$modfile);
            
            addWSPMsg('resultmsg', returnIntLang('modinstall install successful1')." \"".$modsetup->name()."\" ".returnIntLang('modinstall install successful2'));
    
            ?>
            <script type="text/javascript" language="javascript">
		    window.location.href = 'modules.php';
            </script>
            <?php
        };
	elseif (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=='modcheckinstall'):
		if ($install):
			?>
			<fieldset>
				<?php
					
				$canInstall = false;
				$update = false;
				$break = false;
				$breakinstall = false;
				
				// check if module already installed
				$instcheck_sql = "SELECT `id`, `name`, `version` FROM `modules` WHERE `guid` = '".$modsetup->getGUID()."'";
				$instcheck_res = doSQL($instcheck_sql);
				$instcheck_num = $instcheck_res['num'];
				
				// check if interpreter file for other module already exists
				$parsercheck_sql = "SELECT `parsefile` FROM `interpreter` WHERE `module_guid` != '".$modsetup->getGUID()."'";
				$parsercheck_res = doSQL($parsercheck_sql);
				$parsercheck_num = $parsercheck_res['num'];
				$parserexists = array();
				
				if ($parsercheck_res['num']>0):
					foreach ($parsercheck_res['set'] AS $presk => $presv):
						$parserexists[$presk] = trim($presv['parsefile']);
					endforeach;
				endif;
					
				$checkdirforparserfiles = $modtmpdir."/wsp/data/interpreter/";
				$newinterpreterfiles = array();
				
				$result = array_diff($newinterpreterfiles, $parserexists);
					
				$unsafemodinstall = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'unsafemodinstall'"));
				$parserfailure = false;
				if ($unsafemodinstall==0):
					foreach ($newinterpreterfiles AS $value):
						if (in_array($value, $parserexists)):
							$parserfailure = true;
						endif;
					endforeach;
				endif;
				
				/*
                var_export($modsetup->minWSP());
                var_export($modsetup->maxWSP());
                var_export(floatval($_SESSION['wspvars']['wspversion']));
                */

                if ($parserfailure):
					echo "<p>Das aufgerufene Modul kann nicht installiert werden, da es Interpreterdateien anderer Module &uuml;berschreiben w&uuml;rde.</p>\n";
					$breakinstall = true;
				elseif ($instcheck_num==0):
					echo "<legend>".returnIntLang('modinstall modinstall')."</legend>";
					$update = false;
				elseif ($instcheck_num > 0 && (compVersion(trim($instcheck_res['set'][0]['version']), $modsetup->version())<0)):
					echo "<p>".returnIntLang('modinstall noinstall newer version')."</p>\n";
					$breakinstall = true;
				elseif (($modsetup->minWSP()!==false || $modsetup->maxWSP()!==false) && $unsafemodinstall==0):
                    if (version_compare($_SESSION['wspvars']['wspversion'], $modsetup->minWSP())!=1):
                        echo "<p>".returnIntLang('modinstall module cant be installed to wsp version')."</p>\n";
                        echo "<p>".returnIntLang('modinstall minimum wsp version1')." ".floatval($_SESSION['wspvars']['wspversion'])." ".returnIntLang('modinstall minimum wsp version2')."</p>\n";
                        $breakinstall = true;
					endif;
					if (version_compare($modsetup->maxWSP(), $_SESSION['wspvars']['wspversion'])!=1):
                        echo "<p>".returnIntLang('modinstall module should not be installed to wsp version')."</p>\n";
                        echo "<p>".returnIntLang('modinstall maximum wsp version1')." ".floatval($_SESSION['wspvars']['wspversion'])." ".returnIntLang('modinstall maximum wsp version2')."</p>\n";
                    endif;
                else:
					echo "<legend>".returnIntLang('modinstall modupdate')."</legend>\n";
					$update = true;
				endif;
				
				if (!($breakinstall)):
					?>
					<table class="tablelist">
						<tr>
							<td class="tablecell two"><?php echo returnIntLang('str module'); ?></td>
							<td class="tablecell four"><?php echo $modsetup->name()." ".$modsetup->version(); ?></td>
							<td class="tablecell two"><?php
	
							if (($update) || ($break)):
								echo $modsetup->name()." ".trim($instcheck_res['set'][0]['version'])." ".returnIntLang('str installed');
							endif;
							
							?></td>
						</tr>
						<tr>
							<td class="tablecell two"><?php echo returnIntLang('str dependences'); ?></td>
							<td class="tablecell six"><?php
								
							if (count($modsetup->dependences()) == 0):
								// keine abhaengigkeiten vorhanden
								echo returnIntLang('modinstall no dependences');
								$canInstall = true;
							else:
								// abhaengigkeiten vorhanden
								$canInstall = true;
								$cntDeps = 0;
								foreach ($modsetup->dependences() as $guid => $modinfo):
									$dep_sql = "SELECT `version` FROM `modules` WHERE `guid` = '".$guid."'";
									$dep_res = doResultSQL($dep_sql);
									if ($dep_res!==false):
										$instversion = explode(".",trim($dep_res));
										$newversion = explode(".",$modinfo[1]);
										$thisdep = false;
										foreach ($instversion AS $vkey => $vvalue):
											if (intval($newversion[$vkey])>intval($instversion[$vkey])):
												$thisdep = true;
											endif;
										endforeach;
										if ($thisdep):
											echo returnIntLang('modinstall updatebeforeinstall1')." <strong>".$modinfo[0]."</strong> ".returnIntLang('modinstall updatebeforeinstall2')." <strong>".$modinfo[1]."</strong> ".returnIntLang('modinstall updatebeforeinstall3')."<br />";
											$cntDeps++;
										else:
											echo "<strong>".$modinfo[0]."</strong> ".returnIntLang('modinstall depyetinstalled1')." <strong>".trim($dep_res)."</strong> ".returnIntLang('modinstall depyetinstalled2')."<br />";
											$cntDeps--;
										endif;
									endif;
								endforeach;
								// $cntDeps == 0 => required modules nicht vorhanden
								// $cntDeps > 0 && $cntDeps == count($modsetup->dependences()) => alle installiert => versionierung pruefen
								if ($cntDeps==0):
									// KEINE erfuellten Abhaengigkeiten
									foreach ($modsetup->dependences() as $guid => $modinfo):
										echo returnIntLang('modinstall installbeforeinstall1')." <strong>".$modinfo[0]."</strong> ".returnIntLang('modinstall installbeforeinstall2')." <strong>".$modinfo[1]."</strong> ".returnIntLang('modinstall installbeforeinstall3')."<br />";
									endforeach;
									$canInstall = false;
								elseif ($cntDeps>0):
									// unerfuellte Abhaengigkeiten
									$canInstall = false;
								endif;
							endif;
	
							?></td>
						</tr>
						<tr>
							<td class="tablecell eight head"><?php echo returnIntLang('modinstall datatoinstall'); ?></td>
						</tr>
					</table>
					
					<?php $type = $modsetup->getType(); $r = 0; ?>
						
						<table class="tablelist">
							<?php 
							
							$output = false;
							
							if ((isset($type['isparser']) && $type['isparser']!=0) && (!(isset($type['iscmsmodul'])) || (isset($type['iscmsmodul']) && $type['iscmsmodul']==0))):
								$output = false;
								foreach ($modsetup->getParser() as $value):
									$r++;
									echo "<tr>";
									if ($output):
										echo "<td class='tablecell two'>&nbsp;</td>";
									else:
										echo "<td class='tablecell two'>".returnIntLang('modinstall parser standalone')."</td>";
										$output = true;
									endif;
									echo "<td class='tablecell four'>";
						
									// loading info
									if (is_file($modtmpdir."/wsp/data/interpreter/".$value)) {
										require $modtmpdir . "/wsp/data/interpreter/" . $value;
										$parser = new $interpreterClass();
										if (property_exists($parser, 'dbCon')) {
											$parser->dbCon = $_SESSION['wspvars']['dbcon'];
										}
										echo $parser->title.' '.$parser->version.'&nbsp;';
									} else {
										echo "Fehler bei der Namensaufl&ouml;sung<br /><em>".$value."</em>";
										$break = true;
									}
									
									echo "</td>";
									
									echo "<td class='tablecell two'>";
									$sql = 'SELECT `name`, `version` FROM `interpreter` WHERE `guid`="'.$parser->guid.'"';
									$res = doSQL($sql);
									if ($res['num']==0):
										echo '&nbsp;';
									else:
										echo trim($res['set'][0]['name'])." ".trim($res['set'][0]['version'])." ".returnIntLang('str installed');
									endif;
									echo "</td>";
									echo "</tr>";
								endforeach;
							endif;
							
							if (isset($type['iscmsmodul']) && $type['iscmsmodul']!=0):

								$output = false;
								foreach ($modsetup->getParser() as $value):
									echo "<tr>";
										if ($output):
											echo "<td class=\"tablecell two\">&nbsp;</td>";
										else:
											echo "<td class=\"tablecell two\">".returnIntLang('modinstall parser modular')."</td>";
											$output = true;
										endif;
										
										echo "<td class=\"tablecell four\">";
										//
										// load interpreter information
										//
										if (is_file($modtmpdir."/wsp/data/interpreter/".$value)):
											include ($modtmpdir."/wsp/data/interpreter/".$value);
											$parser = new $interpreterClass();
											echo $parser->title.' '.$parser->version.'&nbsp;';
										else:
											echo "Eine f&uuml;r die Ausf&uuml;hrung des Interpreters erforderliche Datei ist im Installationspaket nicht vorhanden.";
										endif;
										
										echo "</td>";
										
										echo "<td class=\"tablecell two\">";
										
										$sql = 'SELECT `name`, `version` FROM `interpreter` WHERE `guid` = "'.$parser->guid.'"';
										$res = doSQL($sql);
										if ($res['num']==0):
											echo '&nbsp;';
										else:
											echo trim($res['set'][0]['name']).' '.trim($res['set'][0]['version'])." ".returnIntLang('str installed');
										endif;

										echo "</td>";
									echo "</tr>";
								endforeach;
							endif; ?>
							
							<?php if ((count($modsetup->getMenuParser())==0) || ($type['ismenu']==0)): ?>
							
							<?php else:
								$output = false;
								foreach ($modsetup->getMenuParser() as $value):
									$r++;
									echo "<tr ";
									if($r/2==ceil($r/2)): echo "class=\"firstcol\""; else: echo "class=\"secondcol\""; endif;
									echo ">";
									if ($output):
										echo "<td>&nbsp;</td>";
									else:
										echo "<td>Men&uuml;modul:</td>";
										$output = true;
									endif;
									
									// load module info
									
									require $modtmpdir."/data/menu/".$value;
									$menuParser = new $menuClass();
									echo '<td><span title="'.$menuParser->describ.'">'.$menuParser->title.' v'.$menuParser->version.'</span>&nbsp;</td>';
									
									$sql = 'SELECT `version` FROM `templates_menu` WHERE `guid` = "'.$menuParser->guid.'"';
									$res = doResultSQL($sql);
									if ($res===false):
										echo "<td>-</td>";
									else:
										echo '<td>(installiert '.trim($res).')</td>';
									endif;
									
									echo "</tr>";
								endforeach;
							endif;
							?>
							<?php if ((count($modsetup->getPlugin())==0) || ($type['isplugin']==0)): ?>
							
							<?php else:
								$output = false;
								foreach ($modsetup->getPlugin() as $value):
									$r++;
									echo "<tr ";
									if($r/2==ceil($r/2)): echo "class=\"firstcol\""; else: echo "class=\"secondcol\""; endif;
									echo ">";
									if ($output):
										echo "<td>&nbsp;</td>";
									else:
										echo "<td>Plugin:</td>";
										$output = true;
									endif;
									// show plugin info
									echo '<td>'.$value['pluginname'].'&nbsp;</td>';
									
									$sql = 'SELECT `pluginfolder` FROM `wspplugins` WHERE `guid` = "'.$value["guid"].'"';
									$res = doResultSQL($sql);
									if ($res===false):
										echo "<td>-</td>";
									else:
										echo "<td>bereits installiert</td>";
									endif;
									
									echo "</tr>";
								endforeach;
							endif;
							
							if (!($output)):
								echo "<tr><td class='tablecell eight'>Bei diesem Modul handelt es sich ausschliesslich um eine Erweiterung für ein bestehendes Modul oder Plugin. Es werden keine Interpreter installiert.</td></tr>";
							endif;
							
							?>
						</table> 
						<?php endif; ?>
				<form name="modinstallform" id="modinstallform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
					<input type="hidden" name="op" value="modinstall" />
					<input type="hidden" name="modul" value="<?php echo $modfilename; ?>" />
				</form>
				<form name="modinstallcancel" id="modinstallcancel" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
					<input type="hidden" name="op" value="modcancel" />
					<input type="hidden" name="modul" value="<?php echo $modfilename; ?>" />
				</form>
			</fieldset>
			<fieldset class="options">
				<p><?php if(($canInstall) && (!$break)): ?><a href="#" onclick="document.getElementById('modinstallform').submit();" class="greenfield"><?php if ($update) { echo returnIntLang('modinstall modupdate', false); } else { echo returnIntLang('modinstall modinstall', false); } if($canInstall): ?></a><?php endif; endif; ?> <a href="#" onclick="document.getElementById('modinstallcancel').submit();" class="orangefield"><?php echo returnIntLang('str cancel', false); ?></a></p>
			</fieldset>
			<?php
		else:
			?>
			<fieldset>
				<p>Dies ist kein g&uuml;ltiges WSP-Modul oder es enth&auml;lt Fehler. Bitte wenden Sie sich an den Distributor dieses WSP3-Moduls.</p>
				<fieldset class="innerfieldset options"><p><a href="modules.php" class="redfield">Zur&uuml;ck</a></p></fieldset>
			</fieldset>
			<?php
		endif;
	else:
		
        $values = false;
        $xmldata = '';
    
        if (_isCurl()) {
            $defaults = array( 
                CURLOPT_URL => $_SESSION['wspvars']['updateuri'].'/versionsmodules.php', 
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
        } 
        else {
            $fh = fopen($_SESSION['wspvars']['updateuri'].'/versionsmodules.php', 'r');
            $xmlversion = '';
            if (intval($fh)==0) {
                addWSPMsg('errormsg', "error reading file \"".$_SESSION['wspvars']['updateuri']."/versionsmodules.php\" from update server<br />");
                }
            else {
                while (!feof($fh)) {
                    $xmlversion .= fgets($fh, 4096);
                }
                fclose($fh);
                $xml = xml_parser_create();
                xml_parse_into_struct($xml, $xmlversion, $values, $index);
            }
        }

		$i = 0;
		foreach ($values as $file) {
			if ($file['tag']=='PACKAGENAME'):
				$i++;
				$mod[$i]['name'] = $file['value'];
			endif;
			if ($file['tag']=='SIZE'):
				$mod[$i]['size'] = $file['value'];
			endif;
			if ($file['tag']=='RELEASE'):
				$mod[$i]['release'] = $file['value'];
			endif;
			if ($file['tag']=='FILE'):
				$mod[$i]['file'] = $file['value'];
			endif;
        }
		
		?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formmodadd">
		<fieldset id="fieldset_installmod">
			<table class="tablelist">
				<tr>
					<td class='tablecell two'><?php echo returnIntLang('modinstall choosefromdisk'); ?></td>
					<td class='tablecell six'><input type="file" name="modulfile" id="modulfile" class="three full" /></td>
				</tr>
				<?php if (is_array($mod ?? false)): ?>
				<tr>
					<td class='tablecell two'><?php echo returnIntLang('modinstall choosefromserver'); ?></td>
					<td class='tablecell six'><select name="serverfile" id="serverfile" class="three full">
						<option value=""><?php echo returnIntLang('hint choose', false); ?></option>
						<?php
						
						foreach ($mod as $mkey => $mvalue):
							echo "<option value=\"".$mvalue['file']."\">".$mvalue['name']." [".$mvalue['release']."]</option>";
						endforeach;
						
						?>
					</select></td>
				</tr>
				<?php endif; ?>
			</table>
		</fieldset>
		<fieldset class="options">
			<p><input type="hidden" name="op" id="op" value="modcheckinstall" /><a href="#" onclick="document.getElementById('formmodadd').submit(); return true;" class="greenfield"><?php echo returnIntLang('str install', false); ?></a> <a href="modules.php" class="orangefield"><?php echo returnIntLang('str cancel', false); ?></a></p>
		</fieldset>
		</form>
	<?php endif; ?>
	</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->