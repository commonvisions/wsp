<?php
/**
 * Modulverwaltung
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-03-12
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

/* define page specific functions ------------ */

// set rights
function setRestrictions($aRights) {
	foreach ($aRights as $guid => $value) {
		// Recht in der Rechtetabelle hinterlegen
		$sRights = serialize($value['rights']);
		$sLabels = serialize($value['namerights']);

        addWSPMsg('errormsg', 'function setRestrictions in modinstall has errors');

		$sql = "SELECT `id` FROM `wsprights` WHERE `guid` = '".$guid."'";
		$rsRight = doSQL($sql);
		// Recht hinzufuegen
		if ($rsRight['num'] == 0) {
			$sql = "INSERT INTO `wsprights`
						(`guid`, `description`, `standard`, `options`, `labels`)
						VALUES('$guid', '".$value['title']."', '".$value['standard']."', '$sRights', '$sLabels')";
		}
		// Recht updaten
		else {
			$sql = "UPDATE `wsprights`
						SET `right`='".$value['title']."',
							`standard`='".$value['standard']."',
							`options`='".$sRights."',
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

// remove rights
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

// register interpreter/parser
function regParser($modul, $aParser, $modguid) {
    if (count($aParser) > 0) {
        foreach ($aParser as $value) {
            if (is_file(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$_SESSION['wspvars']['usevar'].DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR."install-".urltext(woe_basename($modul))."/".WSP_DIR."/data/interpreter/".$value))) {
                include_once (cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$_SESSION['wspvars']['usevar'].DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR."install-".urltext(woe_basename($modul))."/".WSP_DIR."/data/interpreter/".$value));
                $parser = new $interpreterClass();
				$sql = "SELECT `version` FROM `interpreter` WHERE `guid` = '".escapeSQL($parser->guid)."'";
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
							`phpvars` = '".((property_exists($parser, 'phpvars'))?intval($parser->phpvars):0)."',
							`bodyfunc` = '".((property_exists($parser, 'phpvars'))?intval($parser->bodyfunc):0)."',
							`metascript` = '".((property_exists($parser, 'phpvars'))?intval($parser->metascript):0)."'";
					$res = doSQL($sql);
                    if ($res['res']) {
                        addWSPMsg('resultmsg', returnIntLang('modinstall interpreter registrated1').trim($parser->title).returnIntLang('modinstall interpreter registrated2'));
                    }
                } 
                else {
					// parser already exists
					if (compVersion(trim($res['set'][0]['version']), $parser->version)>=0) {
						// update older version
						$sql = "UPDATE `interpreter` 
                            SET `name` = '".escapeSQL(trim($parser->title))."',
                                `classname` = '".escapeSQL($parser->classname)."',
                                `htmlmode` = '".escapeSQL($parser->htmlmode)."',
                                `parsefile` = '".escapeSQL($parser->parsefile)."',
                                `maxfields` = '".escapeSQL($parser->maxfields)."',
                                `version` = '".escapeSQL($parser->version)."',
                                `phpvars` = '".((property_exists($parser, 'phpvars'))?intval($parser->phpvars):0)."',
                                `bodyfunc` = '".((property_exists($parser, 'phpvars'))?intval($parser->bodyfunc):0)."',
                                `metascript` = '".((property_exists($parser, 'phpvars'))?intval($parser->metascript):0)."'
                            WHERE `guid` = '".escapeSQL($parser->guid)."'";
						$res = doSQL($sql);
                        if ($res['res']) {
                            addWSPMsg('resultmsg', returnIntLang('modinstall interpreter updated1').trim($parser->title).returnIntLang('modinstall interpreter updated2'));
                        }
                    }
				}
			}
		}
	}	// if
}	// regParser()

// register menu
function regMenu($modul, $aMenuParser, $modguid) {
	if (count($aMenuParser) > 0) {
		foreach ($aMenuParser as $value) {
			require (DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/modules/install-".urltext(woe_basename($modul))."/data/menu/".$value);
			$menuparser = new $menuClass();
			$sql = "SELECT `version` FROM `templates_menu` WHERE `guid`='".$menuparser->guid."'";
			$res = doSQL($sql);
			// Menu-Parser noch nicht vorhanden
			if ($res['num']==0) {
				$sql = "INSERT INTO `templates_menu` SET
                    `title` = '".escapeSQL($menuparser->title)."',
                    `describ` = '".escapeSQL($menuparser->describ)."',
                    `parser` = '".escapeSQL($menuparser->parsefile)."',
                    `version` = '".escapeSQL($menuparser->version)."',
                    `guid` = '".escapeSQL($menuparser->guid)."',
                    `module_guid` = '".escapeSQL($modguid)."'";
				doSQL($sql);
			}
			// Parser bereits vorhanden
			else {
				if (compVersion(trim($res['set'][0]['version']), $parser->version)>=0) {
					// older version is overwritten
					$sql = "UPDATE `templates_menu` SET
                        `title` = '".escapeSQL($menuparser->title)."',
                        `describ` = '".escapeSQL($menuparser->describ)."',
                        `parse` = '".escapeSQL($menuparser->parsefile)."',
                        `version` = '".escapeSQL($menuparser->version)."'
                        WHERE `guid` = '".escapeSQL($menuparser->guid)."'";
					doSQL($sql);
				}	// if
			}	// if
		}	// foreach
	}	// if
}	// regMenu()

// register plugin
function regPlugin($modul, $aPluginInfo, $modguid) {
	if (count($aPluginInfo) > 0):
		$sql = "SELECT `pluginfolder` FROM `wspplugins` WHERE `guid` = '".$aPluginInfo[$modguid]["guid"]."'";
		$res = doResultSQL($sql);
		if ($res!==false && trim($res)!=''):
			doSQL("INSERT INTO `wspplugins` SET `guid` = '".$aPluginInfo[$modguid]['guid']."', `pluginname` = '".$aPluginInfo[$modguid]['pluginname']."', `pluginfolder` = '".$aPluginInfo[$modguid]['pluginfolder']."'");
		endif;
	endif;	
	}	// regPlugin()

// register selfvars
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

// basename WithOutEnding
function woe_basename($basename) {
    $basename = substr(basename($basename), 0, strrpos(basename($basename), "."));
    return $basename;
}

// prepare installation of module files
if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=='modcheckinstall' && ((isset($_POST['serverfile']) && $_POST['serverfile']!='') || (isset($_FILES) && $_FILES['modulfile']['tmp_name']!=''))) {
    
    // choose temp. user directory
	$tmpdir = cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/modules/');
    $tmpftpdir = cleanPath('/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/modules/');
	$modfilename = "";
    $modfiletype = false;
	$install = false;
    
    if (!is_dir($tmpdir)) {
        // if modules dir doesnt exits create
        if (createDirFTP('/'.WSP_DIR.'/tmp/')===false) {
            addWSPMsg('errormsg', returnIntLang('system update could not create tmp directory'));
            $install = false;
        } else {
            $install = true;
        }
    } else {
        $install = true;
    }
    
    if (createDirFTP($tmpftpdir)===false) {
        addWSPMsg('errormsg', returnIntLang('modinstall could not create tmp modules directory'));
        $install = false;
    }
    
    // use server located module
	if (isset($_POST['serverfile']) && trim($_POST['serverfile'])!="" && $install) {
        $serverfile = trim($_POST['serverfile']);
		$fileupdate = '';
        // try to get file from update server by curl
        if (isCurl()) {
            $defaults = array( 
                CURLOPT_URL => trim('https://'.WSP_UPDSRV.'/versions/modules/?file='.$serverfile), 
                CURLOPT_HEADER => 0, 
                CURLOPT_RETURNTRANSFER => TRUE, 
                CURLOPT_TIMEOUT => 50,
                CURLOPT_FOLLOWLOCATION => TRUE
            );
            $ch = curl_init();
            curl_setopt_array($ch, $defaults);    
            if(!$fileupdate = curl_exec($ch)) { addWSPMsg('errormsg', curl_error($ch)); } 
            curl_close($ch);
        }
        // try to get file from update server by fopen
        else {
            $fh = fopen('https://'.WSP_UPDSRV."/versions/modules/?file=".trim($serverfile), 'r');
            if (intval($fh)!=0) {
                while (!feof($fh)) {
                    $fileupdate .= fgets($fh);
                }
            }
            fclose($fh);
        }
        // try to write file to local folder
        if (trim($fileupdate)!='') {
            // try to create tmp modules folder
            $modfile = cleanPath($tmpdir.'/'.basename($serverfile).'.zip');
            $modfiletype = 'zip';
            $tmpdat = fopen($modfile,'w');
            if (fwrite ($tmpdat, $fileupdate)) {
                $modtmpdir = cleanPath($tmpdir.'/install-'.trim(urltext($serverfile)));
                $modtmpftpdir = cleanPath($tmpftpdir.'/install-'.trim(urltext($serverfile)));
                $install = true;
                $modfilename = basename($serverfile).'.zip';
            } else {
                addWSPMsg('errormsg', returnIntLang('modinstall package could not be written to host'));
            }
            fclose ($tmpdat);
        }
        else {
            addWSPMsg('errormsg', returnIntLang('modinstall package could not be retrieved from server'));
            $install = false;
        }
	} 
    // use uploaded module
    else if (is_array($_FILES['modulfile']) && $install) {
		$modfile = cleanPath($tmpdir.'/'.$_FILES['modulfile']['name']);
		$modfilename = basename($_FILES['modulfile']['name']);
		$modtmpdir = cleanPath($tmpdir.'/install-'.urltext(woe_basename($_FILES['modulfile']['name'])));
        $modtmpftpdir = cleanPath($tmpftpdir.'/install-'.urltext(woe_basename($_FILES['modulfile']['name'])));
        if (move_uploaded_file($_FILES['modulfile']['tmp_name'], $modfile)) {
            $modfiletype = pathinfo($modfile, PATHINFO_EXTENSION);
            $install = true;
		}
	} 
    // return error and stop install process
    else {
		addWSPMsg('errormsg', returnIntLang('modinstall no package were uploaded or set to be retrieved from server'));
        $install = false;
	}
    
    // try to create tmp module folder
    if (createDirFTP($modtmpftpdir)===false) {
        addWSPMsg('errormsg', returnIntLang('modinstall could not create tmp module folder'));
        $install = false;
    }
    
    // try to do install finally
	if ($install && trim($modtmpdir)!="") {
        
        // if directory already exists, clean directory
        clearFolder($modtmpdir);

        // try to extract ZIP archive
        if ($modfiletype=='zip') {
            $zip = new ZipArchive;
            $zipfile = cleanPath($tmpdir.'/'.$modfilename);
            if ($zip->open($zipfile)===true) {        
                // run archive for files
                for($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $fileinfo = pathinfo($filename);
                    // dont use hidden files
                    if (substr($fileinfo['basename'],0,1)=='.') {
                        // entry will be ignored
                    } 
                    // dont use double underscore stuff
                    else if (substr($fileinfo['dirname'],0,2)=='__' || substr($fileinfo['basename'],0,2)=='__') {
                        // entry will be ignored
                    }
                    // rename _wsp_ folder to WSP_DIR
                    else if (substr($fileinfo['dirname'],0,5)=='_wsp_') {
                        if ($fileinfo['basename']==$fileinfo['filename'] && !(isset($fileinfo['extension']))) {
                            // it's a directory and the entry will be ignored
                        } else {
                            // extract only interpreter files to check but create all folders to check for allowed folders
                            if (isset($fileinfo['dirname']) && $fileinfo['dirname']=='_wsp_/data/interpreter') {
                                if (!(is_dir(cleanPath($modtmpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']))))) {
                                    $createdir = createDirFTP('/'.$modtmpftpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/');
                                }
                                @copy("zip://".$zipfile."#".$filename, cleanPath($modtmpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/'.$fileinfo['basename']));
                            } else {
                                if (!(is_dir(cleanPath($modtmpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']))))) {
                                    $createdir = createDirFTP('/'.$modtmpftpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/');
                                }
                            }
                        }
                    }
                    // rename wsp folder to WSP_DIR (for older or not correct formatted modules)
                    else if (substr($fileinfo['dirname'],0,3)=='wsp') {
                        if ($fileinfo['basename']==$fileinfo['filename'] && !(isset($fileinfo['extension']))) {
                            // it's a directory and the entry will be ignored
                        } else {
                            // extract only interpreter files to check but create all folders to check for allowed folders
                            if (isset($fileinfo['dirname']) && $fileinfo['dirname']=='wsp/data/interpreter') {
                                if (!(is_dir(cleanPath($modtmpdir.'/'.WSP_DIR.'/'.substr($fileinfo['dirname'],3))))) {
                                    $createdir = createDirFTP(cleanPath('/'.$modtmpftpdir.'/'.WSP_DIR.'/'.substr($fileinfo['dirname'],3).'/'));
                                }
                                @copy("zip://".$zipfile."#".$filename, cleanPath($modtmpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/'.$fileinfo['basename']));
                            } else {
                                if (!(is_dir(cleanPath($modtmpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']))))) {
                                    $createdir = createDirFTP('/'.$modtmpftpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/');
                                }
                            }
                        }
                    }
                    else {
                        if ($fileinfo['basename']==$fileinfo['filename'] && !(isset($fileinfo['extension']))) {
                            // it's a directory and the entry will be ignored
                        } else {
                            // extract only setup and database.xml but create all folders to check for allowed folders
                            if ($fileinfo['basename']=='setup.php') {
                                @copy("zip://".$zipfile."#".$filename, cleanPath($modtmpdir.'/setup.php'));
                            }
                            else if ($fileinfo['basename']=='database.xml') {
                                @copy("zip://".$zipfile."#".$filename, cleanPath($modtmpdir.'/database.xml'));
                            }
                            else {
                                if (!(is_dir(cleanPath($modtmpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']))))) {
                                    $createdir = createDirFTP('/'.$modtmpftpdir.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/');
                                }
                            }
                        }
                    }
                }      
                $zip->close();
                addWSPMsg('noticemsg', returnIntLang('modinstall copied all install files'));
            } else {
                addWSPMsg('errormsg', returnIntLang('modinstall could not open zip file'));
                $error = true;
            }
        } else if ($modfiletype=='tgz') {
            // tgz has to decompress already so the files exist in structure
            try {
                $phar = new PharData(cleanPath($modfile));
                $phar->extractTo($modtmpdir,NULL,true);
                $error = false;
            } catch (Exception $e) {
                addWSPMsg('errormsg', returnIntLang('modinstall could not open tgz file'));
                addWSPMsg('errormsg', $e->getMessage());
                $error = true;
            }
            // get the entries of extracted archive
            if (!($error)) {
                // get first level entries of extracted archive    
                $sdf = scandir($modtmpdir);
                foreach ($sdf AS $sdfk => $sdfv) {
                    // dont use hidden files
                    if (substr($sdfv,0,1)=='.') {
                        // entry will be ignored
                    } 
                    // dont use double underscore stuff
                    else if (substr($sdfv,0,2)=='__') {
                        // entry will be ignored
                    }
                    // rename _wsp_ folder to WSP_DIR
                    else if ($sdfv=='_wsp_' || substr($sdfv,0,3)=='wsp') {
                        // try to rename the directory to WSP_DIR if WSP_DIR!='wsp'
                        if (is_dir(cleanPath($modtmpdir.'/'.$sdfv)) && $sdfv!=WSP_DIR) {
                            // rename to WSP_DIR
                            if (@rename(cleanPath($modtmpdir.'/'.$sdfv),cleanPath($modtmpdir.'/'.WSP_DIR))===false) {
                                addWSPMsg('errormsg', returnIntLang('modinstall could not rename folder to WSP base folder name'));
                                $install = false;
                            }
                        }
                    }
                }  
            } else {
                addWSPMsg('errormsg', returnIntLang('modinstall could not open installer archive'));
            }
        }
        
        // load modules setup file
		if (!file_exists($modtmpdir."/setup.php")) {
			addWSPMsg('errormsg', returnIntLang('modinstall package could not be unpacked has no setup or is no module'));
			$install = false;
        }
		else {
			// check install paths
			$aDirFiles = checkTree($modtmpdir, '');
			$isin = true;
			$file = '';
            if (is_array($aDirFiles)) {
                for ($i=0; $i<count($aDirFiles); $i++) {
                    $isintemp=false;
                    for($j=0; $j<sizeof($_SESSION['wspvars']['allowdir']); $j++) {
                        if(strstr($aDirFiles[$i], str_replace("[wsp]",'/'.WSP_DIR, $_SESSION['wspvars']['allowdir'][$j]))) {
                            $isintemp=true;
                            $file.="<br />".$aDirFiles[$i];
                            break;
                        }
                    }
                    if ((!$isintemp) && ($j>count($_SESSION['wspvars']['allowdir']))) {
                        $isin=false;
                    }
                }
            }
            else {
                addWSPMsg('errormsg', returnIntLang('modinstall package had no files'));
            }
			
			if (!$isin) {
				addWSPMsg('errormsg', returnIntLang('modinstall structure does not match allowed structure'));
                addWSPMsg('errormsg', returnIntLang('modinstall forbidden:').$file);
				$install = false;
			}

			require ($modtmpdir."/setup.php");
			$modsetup = new modsetup();

			// check for allowed tables
			$aTable = array();
			$aTable = $modsetup->getSQLDescribe();
			for ($i=0; $i<sizeof($aTable); $i++) {
				if (in_array($aTable[$i]['tablename'],$_SESSION['wspvars']['forbiddentables'])) {
					addWSPMsg('errormsg', returnIntLang('modinstall sql table is not allowed'));
					$install = false;
				}
			}
		}
	
    }
}
// remove module installer files and folders
else if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=='modcancel') {
    $modfile = $_POST['modul'];
	$tmpdir = cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/modules");
	delTree($tmpdir."/install-".trim(urltext(woe_basename($modfile))));
	if (@unlink($tmpdir."/".$modfile)):
		addWSPMsg('noticemsg', "<p>".returnIntLang('modinstall install canceled tempfiles removed')."</p>");
	endif;
}

function getDirContent($source_dir, $directory_depth = 0, $linear = false, $hidden = false) {
    if ($fp = @opendir($source_dir)) {
        $filedata   = array();
        $new_depth  = $directory_depth - 1;
        $source_dir = rtrim($source_dir, '/').'/';
        while (($file = readdir($fp))!==false) {
            // Remove '.', '..', and hidden files [optional]
            if ( ! trim($file, '.') OR ($hidden == false && $file[0] == '.')) {
                continue;
            }
            if (($directory_depth < 1 OR $new_depth > 0) && @is_dir($source_dir.$file)) {
                $data = getDirContent($source_dir.$file.'/', $new_depth, $linear, $hidden);
                if ($linear) {
                    foreach ($data AS $dk => $dv) {
                        $filedata[] = cleanPath($file.'/'.$dv);
                    }
                }
                else {
                    $filedata[$file] = $data;
                }
            }
            else {
                $filedata[] = $file;
            }
        }
        closedir($fp);
        return $filedata;
    }
    else {
        return false;
    }
}

if (!function_exists('copyMod')) {
    function copyMod($moddir) {
        $didcopy = true;
        $filelist = getDirContent($moddir, 0, true);
        $copylist = getDirContent($moddir);
        $copydir = cleanToBase(cleanPath($moddir));
        if (is_array($copylist)) {
            foreach ($copylist AS $clk => $clv) {
                if ($clk!='setup.php' && $clk!='database.xml') {
                    $didcopy = (copyFolder(cleanPath($copydir.'/'.$clk),cleanPath('/'.$clk.'/')))?$didcopy:false;
                }
            }
        } 
        else {
            $didcopy = false;
        }
        return array(
            'didcopy' => $didcopy,
            'filelist' => $filelist
        );
    }
}

// head of file
require ("./data/include/header.inc.php");
require ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");
?>

<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('modinstall headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('modinstall info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1);
            
            if (isset($_POST) && isset($_POST['op']) && trim($_POST['op'])=='modinstall') {
                $modfile = $_POST['modul'];
                $tmpdir = cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/modules/');
                $moddir = cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar']."/modules/install-".urltext(woe_basename($modfile)));
                
                // copy all files - except setup and database.xml - to it's destinations
                $docopy = copyMod($moddir);
                $didcopy = (is_array($docopy) && isset($docopy['didcopy']))?$docopy['didcopy']:false;
                $filelist = (is_array($docopy) && isset($docopy['filelist']))?$docopy['filelist']:array();
                
                // run setup routine after copying files
                if ($didcopy) {
                    
                    // load module setup
                    require_once ($moddir."/setup.php");
                    $modsetup = new modsetup();
                    $type = $modsetup->getType();
                    
                    // Zugriffsrechte speichern
                    setRestrictions($modsetup->cmsRights());

                    // register parser/interpreter
                    if (isset($type['isparser']) && intval($type['isparser'])==1) {
                        regParser($modfile, $modsetup->getParser(), $modsetup->getGUID());
                    }

                    // register menuparser
                    if (isset($type['ismenu']) && intval($type['ismenu'])==1):
                        regMenu($modfile, $modsetup->getMenuParser(), $modsetup->getGUID());
                    endif;

                    // register plugin
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
                    // TODO !!!!! 2019-11-12
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

                            }else{
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
                                    $sql_stats = file ($tmpdir."/install-".urltext(woe_basename($modfile))."/".$sql);
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
                    // remove tables
                    if (method_exists($modsetup, 'getSQLDrop')) {
                        if (is_array($modsetup->getSQLDrop())) {
                            foreach ($modsetup->getSQLDrop() as $sql) {
                                doSQL($sql);
                            }
                        }
                    }

                    // register module
                    $sql = "SELECT `id`, `settings` FROM `modules` WHERE `guid` = '".$modsetup->getGUID()."'";
                    $res = doSQL($sql);
                    $dependencies = '';
                    foreach ($modsetup->dependencies() as $key => $value) {
                        $dependencies .= $key." ";
                    }
                    $dependencies = trim($dependencies);
                    
                    if ($res['num'] == 0):
                        $sql = "INSERT INTO `modules` SET
                            `name` = '".$modsetup->name()."',
                            `version` = '".$modsetup->version()."',
                            `guid` = '".$modsetup->getGUID()."',
                            `archive` = '".$modfile."',
                            `dependencies` = '".$dependencies."',
                            `isparser` = '".(isset($type['isparser'])?intval($type['isparser']):0)."',
                            `iscmsmodul` = '".(isset($type['iscmsmodul'])?intval($type['iscmsmodul']):0)."',
                            `ismenu` = '".(isset($type['ismenu'])?intval($type['ismenu']):0)."',
                            `modsetup` = '".$modsetup->getSetup()."',
                            `settings` = '".$modsetup->getSetupDefault()."',
                            `filelist` = '".escapeSQL(serialize($filelist))."'";
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
                            `dependencies` = '".$dependencies."',
                            `isparser` = '".(isset($type['isparser'])?intval($type['isparser']):0)."',
                            `iscmsmodul` = '".(isset($type['iscmsmodul'])?intval($type['iscmsmodul']):0)."',
                            `ismenu` = '".(isset($type['ismenu'])?intval($type['ismenu']):0)."',
                            `modsetup` = '".$modsetup->getSetup()."',
                            `settings` = '".$settings."',
                            `filelist` = '".escapeSQL(serialize($filelist))."'
                            WHERE `id` = ".intval($res['set'][0]['id']);
                    endif;
                    doSQL($sql);

                    delTree($tmpdir."/install-".urltext(woe_basename($modfile)));
                    @unlink($tmpdir."/".$modfile);

                    addWSPMsg('resultmsg', returnIntLang('modinstall install successful1')." <strong>".$modsetup->name()."</strong> ".returnIntLang('modinstall install successful2'));

                    ?>
                    <script type="text/javascript" language="javascript">
                    window.location.href = 'modules.php';
                    </script>
                    <?php
                }
                else {
                    echo 'could not copy files to it\'s destinations';
                    die();
                }
                
                // cleanup the tmp install folder
                
                // store module archive as last action
                if (is_file(cleanPath($tmpdir."/".$modfile))) {
                    $ftp = doFTP();
                    if ($ftp!==false) {
                        // put so the original file will be removed
                        ftp_put($ftp, cleanPath(FTP_BASE."/".WSP_DIR."/modules/".$modfile), cleanPath($tmpdir."/".$modfile), FTP_BINARY);
                        ftp_close($ftp);
                    }
                }
            }
            else if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=='modcheckinstall') {
		
                if ($install):
                    ?>
            <div class="row">
                <div class="col-md-12">
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

                    $unsafemodinstall = intval(getWSPProperties('unsafemodinstall'));
                    $parserfailure = false;
                    if ($unsafemodinstall==0) {
                        foreach ($newinterpreterfiles AS $value) {
                            if (in_array($value, $parserexists)) {
                                $parserfailure = true;
                            }
                        }
                    }
					
                    if ($parserfailure) {
                        echo "<p>Das aufgerufene Modul kann nicht installiert werden, da es Interpreterdateien anderer Module &uuml;berschreiben w&uuml;rde.</p>\n";
                        $breakinstall = true;
                    }
                    else if ($instcheck_num==0) {
                        $update = false;
                    }
                    else if ($instcheck_num > 0 && (compVersion(trim($instcheck_res['set'][0]['version']), $modsetup->version())<0)) {
                        addWSPMsg('noticemsg', "<p>".returnIntLang('modinstall noinstall newer version')."</p>");
                        $breakinstall = true;
                    }
                    else if (($modsetup->minWSP()!==false && floatval($modsetup->minWSP())>floatval(WSP_VERSION)) && $unsafemodinstall==0) {
                        echo "<p>".returnIntLang('modinstall module cant be installed to wsp version')."</p>\n";
                        echo "<p>".returnIntLang('modinstall minimum wsp version1')." ".floatval(WSP_VERSION)." ".returnIntLang('modinstall minimum wsp version2')."</p>\n";
                        $breakinstall = true;
                    }
                    else {
                        $update = true;
                    }
				
                    if (!($breakinstall)) { 
                    
                        error_reporting(E_ALL);
                        ini_set('display_errors', 0);
                        if (intval($_POST['moduldev'])==1) {
                            // enable/disable warnings for false class definitions
                            error_reporting(E_ALL);
                            ini_set('display_errors', 1);
                        }
                        
                    ?>
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    <?php echo ($update)?returnIntLang('modinstall modupdate'):returnIntLang('modinstall modinstall'); ?>
                                </h3>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-3"><?php echo returnIntLang('str module'); ?></div>
                                    <div class="col-md-6"><?php echo $modsetup->name()." ".$modsetup->version(); ?></div>
                                    <div class="col-md-3"><?php

                                        if (($update) || ($break)) {
                                            echo $modsetup->name()." ".trim($instcheck_res['set'][0]['version'])." ".returnIntLang('str installed');
                                        }

                                            ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3"><?php echo returnIntLang('str dependencies'); ?></div>
                                    <div class="col-md-9"><?php

                                    if (count($modsetup->dependencies()) == 0) {
                                        // keine abhaengigkeiten vorhanden
                                        echo returnIntLang('modinstall no dependencies');
                                        $canInstall = true;
                                    }
                                    else {
                                        // abhaengigkeiten vorhanden
                                        $canInstall = true;
                                        $cntDeps = 0;
                                        foreach ($modsetup->dependencies() as $guid => $modinfo):
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
                                                    // $cntDeps > 0 && $cntDeps == count($modsetup->dependencies()) => alle installiert => versionierung pruefen
                                        if ($cntDeps==0) {
                                            // KEINE erfuellten Abhaengigkeiten
                                            foreach ($modsetup->dependencies() as $guid => $modinfo) {
                                                echo returnIntLang('modinstall installbeforeinstall1')." <strong>".$modinfo[0]."</strong> ".returnIntLang('modinstall installbeforeinstall2')." <strong>".$modinfo[1]."</strong> ".returnIntLang('modinstall installbeforeinstall3')."<br />";
                                            }
                                            $canInstall = false;
                                        }
                                        else if ($cntDeps>0) {
                                            // unresolved dependencies
                                            $canInstall = false;
                                        }
                                    }

                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('modinstall datatoinstall'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <?php 
                                           
                                $type = $modsetup->getType();
                                $output = false;
                                
                                // parser/interpreter
                                if ((isset($type['isparser']) && $type['isparser']!=0) && (!(isset($type['iscmsmodul'])) || (isset($type['iscmsmodul']) && $type['iscmsmodul']==0))) {
                                    // create reference array $ci with clsInterpreter for method errors checks
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
                                    
                                    $output = false;
                                    foreach ($modsetup->getParser() as $value) {
                                        echo "<div class='row'>";
                                        if ($output===false) {
                                            echo "<div class='col-md-3'>".returnIntLang('modinstall parser standalone')."</div>";
                                        }
                                        echo "<div class='col-md-6 ".(($output===true)?'col-md-offset-3':'')."'>";
                                        if ($output===false) {
                                            $output = true;
                                        }
                                        // loading info
                                        $dpl = '';
                                        if (is_file($modtmpdir."/".WSP_DIR."/data/interpreter/".$value)) {
                                            
                                            $er = error_reporting();
                                            error_reporting(0);
                                            require $modtmpdir."/".WSP_DIR."/data/interpreter/".$value;
                                            $parser = new $interpreterClass();
                                            
                                            $ic = true;
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
                                                        $icmsg = returnIntLang('modules method error in interpreter1')." ".trim($parser->title)." ".trim($parser->version)." ".returnIntLang('modules method error in interpreter2')." ".$method->name." ".returnIntLang('modules method error in interpreter3');
                                                        $ic = false;
                                                    }
                                                }
                                            }
                                            error_reporting($er);
                                            if ($ic===true) {
                                                echo $parser->title.' '.$parser->version.'&nbsp;';
                                                if ($parser->guid) {
                                                    $sql = 'SELECT `name`, `version` FROM `interpreter` WHERE `guid` = "'.escapeSQL($parser->guid).'"';
                                                    $res = doSQL($sql);
                                                    $dpl = ($res['num']>0)?"<div class='col-md-3'>".trim($res['set'][0]['name'])." ".trim($res['set'][0]['version'])." ".returnIntLang('str installed')."</div>":'';
                                                }
                                            } else {
                                                echo $icmsg;
                                                $break = true;
                                            }
                                        }
                                        else {
                                            echo returnIntLang('modinstall error getting interpreter name')." <em>".$value."</em>";
                                            $break = true;
                                        }
                                        echo "</div>";
                                        echo $dpl;
                                        echo "</div>";
                                    }
                                }
                                // 
                                if (isset($type['iscmsmodul']) && $type['iscmsmodul']!=0) {
                                    $output = false;
                                    foreach ($modsetup->getParser() as $value) {
                                        echo "<div class='row'>";
                                        if ($output===false) {
                                            echo "<div class='col-md-3'>".returnIntLang('modinstall parser standalone')."</div>";
                                        }
                                        echo "<div class='col-md-6 ".(($output===true)?'col-md-offset-3':'')."'>";
                                        if ($output===false) {
                                            $output = true;
                                        }
                                        // load interpreter information
                                        if (is_file($modtmpdir."/".WSP_DIR."/data/interpreter/".$value)):
                                            include ($modtmpdir."/".WSP_DIR."/data/interpreter/".$value);
                                            $parser = new $interpreterClass();
                                            echo $parser->title.' '.$parser->version.'&nbsp;';
                                        else:
                                            echo "Eine f&uuml;r die Ausf&uuml;hrung des Interpreters erforderliche Datei ist im Installationspaket nicht vorhanden.";
                                            $break = true;
                                        endif;
                                        echo "</div>";
                                        $sql = 'SELECT `name`, `version` FROM `interpreter` WHERE `guid` = "'.$parser->guid.'"';
                                        $res = doSQL($sql);
                                        if ($res['num']>0):
                                            echo "<div class='col-md-3'>".trim($res['set'][0]['name'])." ".trim($res['set'][0]['version'])." ".returnIntLang('str installed')."</div>";
                                        endif;
                                        echo "</div>";
                                    }
                                } 
                                if ((count($modsetup->getMenuParser())==0) || ($type['ismenu']==0)) {
                                    // no menu data
                                }
                                else {
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
                                }
                                if ((count($modsetup->getPlugin())==0) || ($type['isplugin']==0)) {
                                    // no plugin data
                                }
                                else {
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
                                }
                                if (!($output)) {
                                    echo "<div class='row'><div class='col-md-12'>Bei diesem Modul handelt es sich ausschliesslich um eine Erweiterung für ein bestehendes Modul oder Plugin. Es werden keine Interpreter installiert.</div></div>";
                                }

                                ?>
                            </div>
                        </div>
                        <form name="modinstallform" id="modinstallform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                            <input type="hidden" name="op" value="modinstall" />
                            <input type="hidden" name="modul" value="<?php echo $modfilename; ?>" />
                        </form>
                        <form name="modinstallcancel" id="modinstallcancel" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                            <input type="hidden" name="op" value="modcancel" />
                            <input type="hidden" name="modul" value="<?php echo $modfilename; ?>" />
                        </form>
                        <div class="row">
                            <div class="col-md-12">
                                <p><?php if(($canInstall) && (!$break)): ?><a href="#" onclick="$('#modinstallform').submit();" class="btn btn-primary"><?php if ($update) { echo returnIntLang('modinstall modupdate', false); } else { echo returnIntLang('modinstall modinstall', false); } if($canInstall): ?></a><?php endif; endif; ?> <a href="#" onclick="$('#modinstallcancel').submit();" class="btn btn-warning"><?php echo returnIntLang('str cancel', false); ?></a></p>
                            </div>
                        </div>
                    <?php } 
                    else {?>
                    <div class="row">
                        <div class="col-md-12">
                            <p><a href="./modinstall.php" class="btn btn-danger"><?php echo returnIntLang('str back'); ?></a></p>
                        </div>
                    </div>
                    <?php
                    } ?>
                </div>
            </div>
        </div>
    </div>
    <?php
        else:
			?>
			<div class="row">
                <div class="col-md-12">
                    <p><a href="./modinstall.php" class="btn btn-danger"><?php echo returnIntLang('str back'); ?></a></p>
                </div>
			</div>
			<?php
		endif;
    }
            else {
		
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
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel">
                            <div class="panel-heading">
                                <h3 class="panel-title"><?php echo returnIntLang('modinstall choose source'); ?></h3>
                            </div>
                            <div class="panel-body">
                                <p><?php echo returnIntLang('modinstall choosefromdisk'); ?></p>
                                <div class="form-group">
                                    <input type="file" name="modulfile" id="modulfile" class="form-control fullwidth" />
                                </div>
                                <p><?php echo returnIntLang('modinstall activate strict mode'); ?></p>
                                <div class="form-group">
                                    <input type="hidden" name="moduldev" value="0" />
                                    <input type="checkbox" name="moduldev" class="form-control" value="1" />
                                </div>
                                <?php if (isset($mod) && is_array($mod)) { ?>
                                <p><?php echo returnIntLang('modinstall choosefromserver'); ?></p>
                                <div class="form-group">
                                    <select name="serverfile" id="serverfile" class="form-control fullwidth">
                                        <option value=""><?php echo returnIntLang('hint choose', false); ?></option>
                                        <?php foreach ($mod as $mkey => $mvalue) {
                                            echo "<option value=\"".$mvalue['file']."\">".$mvalue['name']." - ".$mvalue['release']."</option>";
                                        } ?>
                                    </select>
                                </div>
                                <?php } ?>
                                <p>&nbsp;</p>
                                <p><input type="hidden" name="op" id="op" value="modcheckinstall" /><a href="#" onclick="document.getElementById('formmodadd').submit(); return true;" class="btn btn-primary"><?php echo returnIntLang('str install', false); ?></a> <a href="modules.php" class="btn btn-warning"><?php echo returnIntLang('str cancel', false); ?></a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <?php } ?>
        </div>
    </div>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->