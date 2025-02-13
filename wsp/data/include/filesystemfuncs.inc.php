<?php
/**
 * funktionen, die das dateisystem betreffen, z. b.
 * anlegen von ordnern
 * upload von dateien
 * pruefung und umschreiben von dateinamen
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.9
 * @lastchange 2020-11-25
 */

if (!(function_exists("CleanupFolder"))):
function CleanupFolder($path) {
	if(trim($path)!=""):
		if (is_dir($_SERVER['DOCUMENT_ROOT'].$path)):
			$dir = opendir ($_SERVER['DOCUMENT_ROOT'].$path);
			while ($entry = readdir($dir)):
				if ($entry == '.' || $entry == '..') continue;
				if (is_dir ("..".$path.'/'.$entry)):
					CleanupFolder ($path.'/'.$entry);
				elseif (is_file ("..".$path.'/'.$entry) || is_link ("..".$path.'/'.$entry)):
					if ($_SESSION['wspvars']['ftpcon']===true) {
						ftpDeleteFile($_SESSION['wspvars']['ftpbasedir'].'/'.$path.'/'.$entry, false);
					}
					@unlink(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$path."/".$entry))));
				endif;
			endwhile;
			closedir ($dir);
			if ($_SESSION['wspvars']['ftpcon']===true) {
				ftpDeleteDir($_SESSION['wspvars']['ftpbasedir'].$path, false);
			}
			@rmdir(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$path))));
		endif;
    endif;
	}
endif;

// delete ftp-structure below given directory
if (!(function_exists("ftpDeleteDir"))):
function ftpDeleteDir($dir, $output = true) {
	// create ftp-connection
	$ftphdl = @((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
	// check for successful ftp-connection
	if ($ftphdl):
		$login = @ftp_login($ftphdl, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
		if ($login):
			// delete directory
			ftp_chdir($ftphdl, $_SESSION['wspvars']['ftpbasedir']);
		    if ($output && @ftp_rmdir($ftphdl, $dir)):
				addWSPMsg('noticemsg', returnIntLang('ftp removed dir1', false)." ".str_replace($_SESSION['wspvars']['ftpbasedir'], "", str_replace("//", "/", $dir))." ".returnIntLang('ftp removed dir2', false));
		    elseif ($output):
				addWSPMsg('errormsg', returnIntLang('ftp could not remove dir1', false)." ".str_replace($_SESSION['wspvars']['ftpbasedir'], "", str_replace("//", "/", $dir))." ".returnIntLang('ftp could not remove dir2', false));
		    endif;
			// close ftp-connection
		elseif ($output):
			addWSPMsg('errormsg', returnIntLang('ftp could not login to host', false));
		endif;
		ftp_quit($ftphdl);
	elseif ($output):
		addWSPMsg('errormsg', returnIntLang('ftp could not connect to host', false));
	endif;
	}	// ftpDeleteDir()
endif;

// delete files by ftp
if (!(function_exists("ftpDeleteFile"))):
function ftpDeleteFile($file, $output = true) {
	$file = str_replace('//', '/', $file);
	// create ftp-connection
	$ftphdl = @((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
	// check for successful ftp-connection
	if ($ftphdl):
		$login = @ftp_login($ftphdl, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
		if ($login):
			ftp_chdir($ftphdl, $_SESSION['wspvars']['ftpbasedir']);
			// remove file
			if ($output && @ftp_delete($ftphdl, $file)):
				addWSPMsg('noticemsg', returnIntLang('ftp removed file1', false)." ".str_replace($_SESSION['wspvars']['ftpbasedir'], "", $file)." ".returnIntLang('ftp removed file2', false));
			elseif ($output):
				addWSPMsg('errormsg', returnIntLang('ftp could not remove file1', false)." ".str_replace($_SESSION['wspvars']['ftpbasedir'], "", $file)." ".returnIntLang('ftp could not remove file2', false));
			endif;
		elseif ($output):
			addWSPMsg('errormsg', returnIntLang('ftp could not login to host', false));
		endif;
		// closing ftp-connection
		ftp_quit($ftphdl);
	elseif ($output):
		addWSPMsg('errormsg', returnIntLang('ftp could not connect to host', false));
	endif;
	}	// ftpDeleteFile()
endif;

// create ftp-structure in given path
// basedir -> directory below directories should be created
// finaldir -> 
if (!(function_exists("ftpCreateDir"))):
function ftpCreateDir($basedir = '', $finaldir = '') {
	$finaldir = str_replace("//", "/", str_replace("//", "/", $finaldir));
//	if (trim($basedir)=='' || strstr($basedir, "..")):
//		$basedir = $_SESSION['wspvars']['ftpbasedir'];
//	endif;
//	$substructure = str_replace($finaldir, '', $basedir);

	addWSPMsg('noticemsg', $substructure);

	if ($substructure==$finaldir):
		// do nothing
	else:
		if (substr($finaldir, 0, 1)=="/"):
			$finaldir = substr($finaldir, 1);
		endif;
		$finaldir = explode("/", $finaldir);
		$ftphdl = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
		$login = @ftp_login($ftphdl, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
		// check connection
		if ((!$ftphdl) || (!$login)):
			addWSPMsg('errormsg', "<p>FTP-Verbindung nicht hergestellt!</p><p>Verbindung mit \"".$_SESSION['wspvars']['ftphost']."\" als Benutzer \"".$_SESSION['wspvars']['ftpuser']."\" nicht m&ouml;glich</p>");
			return false;
		else:
			foreach ($finaldir AS $subvalue):
				ftp_mkdir($ftphdl, str_replace("//", "/", str_replace("//", "/", $basedir."/".$subvalue)));
				$basedir = str_replace("//", "/", str_replace("//", "/", $basedir."/".$subvalue));
			endforeach;
		endif;
		ftp_quit($ftphdl);
	endif;
	}
endif;

if (!(function_exists("fileinuse"))):
function fileinuse($path, $file) {
	unset($used);
	$checkfile = str_replace("/media/", "/", $file);
	$checkfile = str_replace("/images/", "/", $checkfile);
	$checkfile = str_replace("/screen/", "/", $checkfile);
	$checkfile = str_replace("/download/", "/", $checkfile);
	$checkfile = str_replace("//", "/", $checkfile);
	$checkfile = str_replace("//", "/", $checkfile);
	$checkfile = str_replace("//", "/", $checkfile);
	$checkfile = str_replace("//", "/", $checkfile);
	if (trim($checkfile)!=""):
		$sql = "SELECT `mid`, `cid` FROM `content` WHERE `trash` = 0 AND `valuefields` LIKE '%".escapeSQL(trim($checkfile))."%'";
		$res = doSQL($sql);
		$cnum = $res['num'];
        if ($res['num']>0):
            foreach ($res['set'] AS $rsk => $data):
                $used[$data['mid']] = $data['mid'];
                $content[$data['mid']][] = $data['cid'];
            endforeach;
        endif;
		$sql = "SELECT c.`mid` AS mid, g.`id` AS gid FROM `globalcontent` AS g, `content` AS c WHERE g.`trash` = 0 AND c.`trash` = 0 AND g.`id` = c.`globalcontent_id` AND (g.`valuefield` LIKE '%".escapeSQL(trim($checkfile))."%')";
		$res = doSQL($sql);
		$cnum+= $res['num'];
		if ($res['num']>0):
            foreach ($res['set'] AS $rsk => $data):
                $used[$data['mid']] = $data['mid'];
                $globalcontent[$data['mid']][] = $data['gid'];
            endforeach;
        endif;
    	$sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0 AND `imageon`='".escapeSQL(trim($checkfile))."' OR `imageoff`='".escapeSQL(trim($checkfile))."' OR `imageakt`='".escapeSQL(trim($checkfile))."' OR `imageclick`='".escapeSQL(trim($checkfile))."'";
		$res = doSQL($sql);
		$cnum+= $res['num'];
		if ($res['num']>0):
            foreach ($res['set'] AS $rsk => $data):
                $used[$data['mid']] = $data['mid'];
                $menuimg[$data['mid']][] = $data['mid'];
            endforeach;
        endif;
		$sql = "SELECT `id` FROM `stylesheets` WHERE `stylesheet` LIKE '%".escapeSQL(trim($checkfile))."%'";
		$res = doSQL($sql);
		$cnum+= $res['num'];
        if ($res['num']>0):
            foreach ($res['set'] AS $rsk => $data):
                $style[$data['id']] = $data['id'];
            endforeach;
        endif;
		$mod_sql = "SELECT `affectedcontent` FROM `modules` WHERE `affectedcontent` != ''";
		$mod_res = doSQL($mod_sql);
		if ($mod_res['num']>0):
			foreach ($mod_res['set'] AS $mrk => $mrv):
				$affected = unserializeBroken(trim($mrv['affectedcontent']));
				if (is_array($affected) && count($affected)>0):
					foreach($affected AS $table => $cells):
						foreach ($cells AS $cellselect):
							$sql = "SELECT * FROM `".$table."` WHERE `".$cellselect."` LIKE '%".escapeSQL(trim($checkfile))."%'";
							$res = doSQL($sql);
							if ($res['num']>0): $used[] = $sql; endif;
						endforeach;
					endforeach;
				endif;
			endforeach;
		endif;
		if(isset($used) || $cnum>0):
			return true;
		else:
			return false;
		endif;
	else:
		return false;
	endif;
	}
endif;

if (!(function_exists("folderinuse"))):
function folderinuse($folder) {
	$used = false;
	foreach ($folder AS $file => $args):
		if ($args['inuse']==true): $used = true; endif;
	endforeach;
	return $used;
	}
endif;

if (!(function_exists("uploadMedia"))):
// upload documents to folder '/media/' into required structure
function uploadMedia($countfiles = 1) {
	addWSPMsg('errormsg', '<p>Function uploadMedia() is deprecated and should not be used anymore. Please contact your module developer to fix this problem.');
	}	// uploadMedia();
endif;

if (!(function_exists('newUploadMedia'))):
	function newUploadMedia($mediafiles=array()) {
		$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
		$login = ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
		if ($login):
			foreach($mediafiles AS $value):
				// path to file folder
				$chkfilepath = $_SERVER['DOCUMENT_ROOT']."/media/".$GLOBALS['mediafolder']."/";
				// temp media folder
				$tmpfilepath = str_replace('//', '/', str_replace('//', '/', $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/media/".$GLOBALS['mediafolder']."/"));
				
				// Upload-Datei im End-Dir
				$ftpfilepath = str_replace('//', '/', str_replace('//', '/', $_SESSION['wspvars']['ftpbasedir']."/media/".$GLOBALS['mediafolder']."/".$GLOBALS['path']."/"));  
				ftpCreateDir('', '/media/'.$GLOBALS['mediafolder'].'/'.$GLOBALS['path'].'/');
				
				// Thumb-Datei im TMP
				$tmptmbpath = str_replace('//', '/', str_replace('//', '/', $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/media/".$GLOBALS['mediafolder']."/thumbs/"));  
				createDir('/media/'.$GLOBALS['mediafolder'].'/thumbs/');
				
				// Thumb-Datei im End-Dir
				$ftptmbpath = str_replace('//', '/', str_replace('//', '/', $_SESSION['wspvars']['ftpbasedir']."/media/".$GLOBALS['mediafolder']."/thumbs/".$GLOBALS['path']."/"));  
				ftpCreateDir('', '/media/'.$GLOBALS['mediafolder'].'/thumbs/'.$GLOBALS['path'].'/');
				
				// Prev-Datei im TMP
				$tmpprvpath = str_replace('//', '/', str_replace('//', '/', $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/media/".$GLOBALS['mediafolder']."/preview/"));  
				createDir('/media/'.$GLOBALS['mediafolder'].'/preview/');
				
				// Prev-Datei im End-Dir
				$ftpprvpath = str_replace('//', '/', str_replace('//', '/', $_SESSION['wspvars']['ftpbasedir']."/media/".$GLOBALS['mediafolder']."/preview/".$GLOBALS['path']."/"));  
				
				
				$file = strtolower(removeSpecialChar($_FILES[$value]['name'])); // convert filename
				$filename = substr($file,0,strrpos($file, "."));
				// $filetype = $_FILES[$value]['type'];
				$filetype = substr($file,strrpos($file, "."));
				$replacer = "-"; // replace hard coded definition with users choice ..
				if ($_POST['media_'.$value.'_handling']!="overwrite"):
					if (is_file($chkfilepath.$file)): 
						$file = $filename.$replacer.mktime().$filetype;
					endif;
				endif;
				$scaleit = false;
				if (move_uploaded_file($_FILES[$value]['tmp_name'], $tmpfilepath.$file)):
					if ($filetype=='.pdf'):
						if ($ftpprvpath!=''):
							if ($_SESSION['wspvars']['createimagefrompdf']=="checked" && $GLOBALS['mediafolder']=="download"):
								$wspvars['pdfscalepreview'] = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'pdfscalepreview'"));
	
							// Beginn PDF-Erzeugung ???							
								@ exec("/usr/bin/gs -q -dNOPAUSE -dBATCH -sDEVICE=jpeg -sOutputFile=".$tmpprvpath.$filename.".jpg ".$tmpfilepath.$file);
							// Ende PDF-Erzeugung ???
								
								$_SESSION['noticemsg'] .= "<p>PDF zur Preview machen. ".$wspvars['pdfscalepreview']." </p>";
								
							endif;
							$dimensions = array();
							$org_dimensions = array();
							$dimensions = explode("x", $_POST['autoscale']);
							$width = intval($dimensions[0]);
							$height = intval($dimensions[1]);
							if($height>0 && $width>0):
								$org_dimensions = getimagesize($tmpprvpath.$filename.".jpg");
								if((intval($org_dimensions[0])>$width) || (intval($org_dimensions[1])>$height)):
									resizeGDimage($tmpprvpath.$filename.".jpg", $tmpprvpath.$filename."scale", 0, $width, $height, 1);
									$scaleit = true;
								endif;
							endif;
							resizeGDimage($tmpprvpath.$filename.".jpg", $tmptmbpath.$filename.".jpg", 0, 100, 75, 1);
	
							if (!(@ftp_put($ftp, $ftpprvpath.$filename.".jpg", $tmpprvpath.$filename."scale", FTP_BINARY))): // upload Prev
								$_SESSION['errormsg'] .= "<p>Probleme beim Upload des Bildes.</p>";
							else:
								$_SESSION['noticemsg'] .= "<p>Datei \"".$file."\" erfolgreich hochgeladen.</p>";
							endif;
	
							if (!(@ftp_put($ftp, $ftpfilepath.$file, $tmpfilepath.$file, FTP_BINARY))): // upload file 
								$_SESSION['errormsg'] .= "<p>Probleme beim Upload des Bildes.</p>";
							else:
								$_SESSION['noticemsg'] .= "<p>Datei \"".$file."\" erfolgreich hochgeladen.</p>";
							endif;
							if (!(@ftp_put($ftp, $ftptmbpath.$filename.".jpg", $tmptmbpath.$filename.".jpg", FTP_BINARY))): // upload Thumb
								$_SESSION['errormsg'] .= "<p>Probleme bei der Erzeugung des Thumbnails. Dies wirkt sich nicht auf die Verf&uuml;gbarkeit des Bildes aus.</p>";
							endif;
	
	
						endif;
				
					else:
						
						if($_POST['autoscale']!=""):
							$dimensions = array();
							$org_dimensions = array();
							$dimensions = explode("x", $_POST['autoscale']);
							$width = intval($dimensions[0]);
							$height = intval($dimensions[1]);
							if($height>0 && $width>0):
								$org_dimensions = @getimagesize($tmpfilepath.$file);
								if((intval($org_dimensions[0])>$width) || (intval($org_dimensions[1])>$height)):
									resizeGDimage($tmpfilepath.$file, $tmpfilepath.$file."scale", 0, $width, $height, 1);
									$scaleit = true;
								endif;
							endif;
						endif;
						
						if ($_SESSION['wspvars']['createthumbfromimage']=="checked" && ($GLOBALS['mediafolder']=="images" || $GLOBALS['mediafolder']=="screen")):
							resizeGDimage($tmpfilepath.$file, $tmptmbpath.$file, 0, 100, 75, 1);
						endif;
						if($scaleit):
							if (!(@ftp_put($ftp, $ftpfilepath.$file, $tmpfilepath.$file."scale", FTP_BINARY))): // upload file and thumbnail
								$_SESSION['wspvars']['errormsg'] .= "<p>Probleme beim Upload bzw. der Skalierung des Bildes.</p>";
							else:
								$_SESSION['wspvars']['resultmsg'] .= "<p>Datei \"".$file."\" erfolgreich hochgeladen und skaliert.</p>";
							endif;
						else:
							if (!(@ftp_put($ftp, $ftpfilepath.$file, $tmpfilepath.$file, FTP_BINARY))): // upload file and thumbnail
								$_SESSION['wspvars']['errormsg'] .= "<p>Probleme beim Upload des Bildes.</p>";
							else:
								$_SESSION['wspvars']['resultmsg'] .= "<p>Datei \"".$file."\" erfolgreich hochgeladen.</p>";
							endif;
						endif;
						if ($GLOBALS['mediafolder']=="images" || $GLOBALS['mediafolder']=="screen"):
							if (!(@ftp_put($ftp, $ftptmbpath.$file, $tmptmbpath.$file, FTP_BINARY))):
								$_SESSION['wspvars']['errormsg'] .= "<p>Probleme bei der Erzeugung des Thumbnails. Dies wirkt sich nicht auf die Verf&uuml;gbarkeit des Bildes aus.</p>";
							endif;
						endif;
										
					endif;
				endif;
			endforeach;
		else:
			$_SESSION['wspvars']['errormsg'].= "Kein FTP-Login";
		endif;
		ftp_close($ftp);
	} // newUploadMedia();
endif;

if (!(function_exists("getDirList"))):
/**
 * Ermittelt alle Unterverzeichnisse des gegebenen Verzeichnisses; ausgehend von DocumentRoot
 *
 * @param String $sDir	Verzeichnis, in dem die Unterverzeichnisse ermittelt werden
 * @param Boolean $bRecursive	Sollen auch die Untervezeichnisse durchsucht werden
 * @return array
 */
function getDirList($sDir, $bRecursive) {
	// define subdir array
	$subdirs = array();
	// open directory
	$d = dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$sDir)));
	while (false !== ($entry = $d->read())):
		if ((substr($entry, 0, 1)!='.') && (is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$sDir.'/'.$entry))))):
			$subdirs[] = $sDir.'/'.$entry;
			if ($bRecursive):
				$subdirs = array_merge($subdirs, getDirList($sDir.'/'.$entry, true));
			endif;
		endif;
	endwhile;
	$d->close();
	return $subdirs;
	}
endif;

// EOF ?>