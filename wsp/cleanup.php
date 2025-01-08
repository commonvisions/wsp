<?php
/**
 * Cleanup
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 3.3
 * @version 6.9.1
 * @lastchange 2020-04-08
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
// checkParamVar -----------------------------

// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------

// define page specific funcs ----------------
// cleanup tmp directory
if (isset($_POST['action']) && $_POST['action']=='cleanuptmp'):
	if (is_array($_POST['tmp'])):
		foreach ($_POST['tmp'] AS $dk => $dv):
			CleanupFolder($dv);
		endforeach;
	endif;
endif;

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

function showDirs($sDir = '', $bRecursive = true) {
	$subdirs = array();
	$d = dir($_SERVER['DOCUMENT_ROOT'].$sDir);
	while (false !== ($entry = $d->read())):
		if ((substr($entry, 0, 1)!='.') && (is_dir($_SERVER['DOCUMENT_ROOT'].$sDir.'/'.$entry))):
			$subdirs[] = $sDir.'/'.$entry;
			if ($bRecursive):
				$subdirs = array_merge($subdirs, showDirs($sDir.'/'.$entry, true));
			endif;
		endif;
	endwhile;
	$d->close();
	return $subdirs;
	}	// showDirs()
			
?>
<div id="contentholder">
	<fieldset class="text"><h1><?php echo returnIntLang('cleanup headline'); ?></h1></fieldset>
	<fieldset class="text">
		<legend><?php echo returnIntLang('str legend', true); ?> <?php echo legendOpenerCloser('wsplegend'); ?></legend>
		<div id="wsplegend">
			<p><?php echo returnIntLang('cleanup info'); ?></p>
		</div>
	</fieldset>
	<?php
	
	$tempdirs = showDirs("/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp", false);
	
	if (count($tempdirs)>1):
		foreach ($tempdirs AS $key => $value):
			$stat = stat($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$value);
			$tempdirs[$key] = str_replace("//", "/", str_replace("//", "/", $value));
			if (intval($stat[9])>=intval(time()-1209600)):
				$tempdirs[$key] = "";
				unset($tempdirs[$key]);
			elseif (strchr($value, 'previewtmp')):
				unset($tempdirs[$key]);
			endif;
		endforeach;
	endif;

	if (count($tempdirs)>1):
		?>
		<fieldset>
			<legend><?php echo returnIntLang('cleanup tempdirs'); ?> <em>beta</em> <?php echo legendOpenerCloser('tempdirs'); ?></legend>
			<div id="tempdirs">
				<form name="cleanup_tmp" id="cleanup_tmp" method="post">
				<p><?php echo returnIntLang('cleanup temp directories found1'); ?> <?php echo count($tempdirs); ?> <?php echo returnIntLang('cleanup temp directories found2'); ?></p>
				<fieldset class="options">
					<p><a href="#" onClick="document.getElementById('cleanup_tmp').submit();" class="redfield"><?php echo returnIntLang('cleanup tmp submit', false); ?></a></p>
				</fieldset>
				<input type="hidden" name="action" value="cleanuptmp" />
				<?php
				
				foreach ($tempdirs AS $key => $value):
					if (!(strchr($value, $_SESSION['wspvars']['usevar'])) && !(strchr($value, 'previewtmp'))):
						echo "<input name=\"tmp[".$key."]\" value=\"".$value."\" type=\"hidden\" />";
					endif;
				endforeach;

				
				?>
				</form>
			</div>
		</fieldset>
	<?php endif; ?>
	<!-- <fieldset>
		<legend><?php echo returnIntLang('cleanup sysbackup'); ?> <?php echo legendOpenerCloser('sysbackup'); ?></legend>
		<div id="sysbackup">
			<form name="cleanup_sysbackup" id="cleanup_sysbackup">
			<p><?php echo returnIntLang('cleanup sysbackup description'); ?></p>
			<p><?php echo returnIntLang('cleanup sysbackup files'); ?></p>
			<fieldset class="options">
				<p><a href="#" onClick="document.getElementById('cleanup_sysbackup').submit();" class="redfield"><?php echo returnIntLang('cleanup sysbackup submit', false); ?></a></p>
			</fieldset>
			<input type="hidden" name="action" value="cleanup_sysbackup" />
			</form>
		</div>
	</fieldset> -->
	<fieldset>
		<legend><?php echo returnIntLang('cleanup filesystem'); ?> <em>beta</em> <?php echo legendOpenerCloser('filesystem'); ?></legend>
		<div id="filesystem">
			<p><?php echo returnIntLang('cleanup filesystem description'); ?></p>
			<?php
			
			$filesizes = array('Byte','KB','MB','GB');
			
			// do ftp connect to establish ONE ftp-connection
			$tFTP = 3;
			$cFTP = $tFTP;
			$ftp = false;
			while (!$ftp && ($tFTP > 0)):
				if ($cFTP != $tFTP):
					$cFTP = $tFTP;
					sleep(1);
				endif;
				$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
				$tFTP--;
			endwhile;
			if ($ftp === false):
				addWSPMsg('errormsg', returnIntLang('cleanup cant connect to ftp'));
			elseif (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])):
				addWSPMsg('errormsg', returnIntLang('cleanup cant login to ftp'));
			endif;
			// if ftp-connect exists
			if ($ftp):
				// setup some vars
				// basedir to read from
				$fsc['ftpbase'] = str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/"));
				$fsc['ftpdir'] = str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/"));
				// wsp-dir to disable from view
				$fsc['wspbasedir'] = str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasedir']."/"));
				// media-dir to disable from view
				$fsc['mediadir'] = "/media/";
				// data-dir to disable from view
				$fsc['datadir'] = "/data/";
				// document path to check for file or directory function
				$fsc['docdir'] = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/"));
				// if isset request to change directory, use it
				if (isset($_REQUEST['cfd']) && trim($_REQUEST['cfd'])!=''): $fsc['ftpdir'] = str_replace("//", "/", str_replace("//", "/", $fsc['ftpdir']."/".str_replace(".", "", trim($_REQUEST['cfd']))."/")); endif;
			
                if (isset($_POST['rdv']) && trim($_POST['rdv'])!='') {
                    if ($_SERVER['HTTP_REFERER']==$_SERVER['SCRIPT_URI']) {
                        $rdv = str_replace("//", "/", str_replace("//", "/", $fsc['ftpdir']."/".str_replace(".", "", trim($_REQUEST['rdv']))."/")); 
                        ftp_rmdir($ftp, $rdv);
                    }
                }
            
                // error and attack management
				
				// read list of folder and files from given directory
				$fsc['data'] = ftp_nlist($ftp, $fsc['ftpdir']);
				// setup emtpy files-array
				$fsc['files'] = array();
				// setup empty fileaction-array
				$fsc['fileaction'] = array();
				// setup directory array
				$fsc['dirs'] = array();
				$fsc['dirlink'] = array();
				
				/// known directory names of some systems that should not be accessible by "normal" users
				$fsc['sysdirs'] = array('img','plesk-stat','picture_library','cgi_bin','pma','piwik','cgi-bin');
				
                echo "<ul class='tablelist'>";
				$path = explode("/", substr($fsc['ftpdir'], strlen(str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/")))));
				$uplink = array();
				$linkpath = array();
				while (count($path)>0):
					$link = "/".implode("/",$path)."/";
					$key = array_pop($path);
					$uplink[] = array("key" => $key, "link" => $link);
				endwhile;
				foreach($uplink AS $uk => $uv):
					$linkpath[] = "<a onclick=\"document.getElementById('configcfd').value='".trim($uv['link'])."'; document.getElementById('switchcfd').submit();\" style=\"cursor: pointer; color: #fff;\">".$uv['key']."</a>";
				endforeach;
				$linkpath = array_reverse($linkpath);
				echo "<li class='tablecell eight head'>".str_replace("//", "/", str_replace("//", "/", "/".implode("/",$linkpath)))."</li>";
				echo "</ul>";
				
				// if files or folders in nlist-feedback
				if (is_array($fsc['data']) && count($fsc['data'])>0):
					// run array to differate between files and folders (for view)
					foreach ($fsc['data'] AS $fk => $fv):
						$checkftpname = str_replace("//", "/", str_replace("//", "/", substr($fv, strlen(str_replace("//", "/", str_replace("//", "/", "/".$fsc['ftpdir']."/"))))));
						$checksysname = str_replace("//", "/", str_replace("//", "/", $fsc['docdir']."/".substr($fv, strlen(str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/"))))));
						if (is_file($checksysname)):
							$fsc['files'][str_replace("//", "/", str_replace("//", "/", "/".substr(($fsc['ftpdir']."/".$checkftpname), strlen(str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/"))))))] = $checkftpname;
							$fsc['fileaction'][str_replace("//", "/", str_replace("//", "/", "/".substr(($fsc['ftpdir']."/".$checkftpname), strlen(str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/"))))))] = md5(str_replace("//", "/", str_replace("//", "/", "/".substr(($fsc['ftpdir']."/".$checkftpname), strlen(str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/")))))));
						else:
							if (!(in_array($checkftpname, $fsc['sysdirs']))):
								$subdata = count(ftp_nlist($ftp, $fsc['ftpdir']."/".$checkftpname));
								$fsc['dirs'][str_replace("//", "/", str_replace("//", "/", "/".substr(($fsc['ftpdir']."/".$checkftpname."/"), strlen(str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/"))))))] = $checkftpname;
								$fsc['diraction'][str_replace("//", "/", str_replace("//", "/", "/".substr(($fsc['ftpdir']."/".$checkftpname."/"), strlen(str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/"))))))] = intval($subdata);
							endif;
						endif;
					endforeach;
					// sorting directory- and files-array
					ksort($fsc['dirs']);
					ksort($fsc['files']);
				endif;

				if (isset($_POST['crf']) && trim($_POST['crf'])!=''): 
					$filekey = array_keys($fsc['fileaction'], trim($_POST['crf']));
					$filekey = $filekey[0];
					if (array_key_exists($filekey, $fsc['files'])):
						// remove from filesystem
						if (ftp_delete($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$filekey)))):
							// remove from files-array
							unset($fsc['files'][$filekey]);
						endif;
					endif;
				endif;

				if (isset($_POST['cif']) && trim($_POST['cif'])!=''): 
					$filekey = array_keys($fsc['fileaction'], trim($_POST['cif']));
					$filekey = $filekey[0];
					if (array_key_exists($filekey, $fsc['files'])):
						// set file content
						$tmpbuf = '<'.'?'.'php header("HTTP/1.1 301 Moved Permanently"); 
header("location: /"); 
?'.'>';
						$tmppath = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/";
						// define temp filename
						$tmpfile = tempnam($tmppath, 'wsp');
						// open file in tmp to write
						$fh = fopen($tmpfile, "r+");
						// write contents to file
						fwrite($fh, $tmpbuf);
						fclose($fh);
						
						if (ftp_put($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$filekey)), $tmpfile, FTP_BINARY)):
							addWSPMsg('resultmsg', "<p>Weiterleitung definiert</p>");
						else:
							addWSPMsg('errormsg', "<p>Die Datei \"".$ftppath."\" konnte nicht hochgeladen werden.</p>");
						endif;
						unlink($tmpfile);
					endif;
				endif;
				
				if (isset($_POST['cff']) && trim($_POST['cff'])!='') { 
					$filekey = array_keys($fsc['fileaction'], trim($_POST['cff']));
					$filekey = $filekey[0];
					if (array_key_exists($filekey, $fsc['files'])) {
						$target = returnInterpreterPath(intval($_POST['cffid']));
						// set file content
						$tmpbuf = '<'.'?'.'php header("HTTP/1.1 301 Moved Permanently"); 
header("location: '.$target.'"); 
?'.'>';
						$tmppath = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/";
						// define temp filename
						$tmpfile = tempnam($tmppath, 'wsp');
						// open file in tmp to write
						$fh = fopen($tmpfile, "r+");
						// write contents to file
						fwrite($fh, $tmpbuf);
						fclose($fh);
						
						if (ftp_put($ftp, str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/".$filekey)), $tmpfile, FTP_BINARY)):
							addWSPMsg('resultmsg', "<p>Weiterleitung definiert</p>");
						else:
							addWSPMsg('errormsg', "<p>Die Datei \"".$ftppath."\" konnte nicht hochgeladen werden.</p>");
						endif;
						unlink($tmpfile);
                    }
                }
				
				// create upwards-link only, if not on homedir
				if ($fsc['ftpdir']!=str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/"))) {
					$fsc['dirlink'][] = "<a onclick=\"document.getElementById('configcfd').value=''; document.getElementById('switchcfd').submit();\" style='cursor: pointer;'><span class='bubblemessage green'>⇖</span> TOP</a>";
                }
                
                // get level
                $tmplvl = 1;
                if (isset($_POST['cfd']) && trim($_POST['cfd'])!='') {
                    $tmpdir = explode("/", $_POST['cfd']);
                    foreach ($tmpdir AS $tdk => $tdv) {
                        if (trim($tdv)!='') {
                            $tmplvl++;
                        }
                    }
                }
            
                // run directories
				foreach ($fsc['dirs'] AS $fk => $fv) {
					if (trim($fk)!=$fsc['mediadir'] && trim($fk)!=$fsc['datadir'] && trim($fk)!=$fsc['wspbasedir']) {
						
                        $du_sql = "SELECT * FROM `menu` WHERE `filename` LIKE '%".str_replace("/", "", str_replace(".php", "", $fv))."%'";
						$du_res = doSQL($du_sql);
            
                        if (intval($fsc['diraction'][$fk])>0) {
							// not found in database
                            if ($du_res['num']==0) {
                                $createlink = "<a onclick=\"document.getElementById('configcfd').value='".trim($fk)."'; document.getElementById('switchcfd').submit();\" style='cursor: pointer;'><span class='bubblemessage red'>☰</span> ".$fv."</a>";
                            }
                            // multiple entries found in database
                            else if ($du_res['num']>1) {
                                $mplvl = array(); 
                                $tmptrash = 0;
                                foreach ($du_res['set'] AS $drk => $drv) {
                                    if ($drv['trash']==0) {
                                        $mplvl[] = $drv['level'];
                                    }
                                }
                                if (!(in_array($tmplvl, $mplvl))) {
                                    // dieser eintrag existiert auf DIESER ebene nicht
                                    $createlink = "<a onclick=\"document.getElementById('configcfd').value='".trim($fk)."'; document.getElementById('switchcfd').submit();\" style='cursor: pointer;'><span class='bubblemessage red'>↘</span> ".$fv."</a>";
                                } 
                                else {
                                    $createlink = "<a onclick=\"document.getElementById('configcfd').value='".trim($fk)."'; document.getElementById('switchcfd').submit();\" style='cursor: pointer;'><span class='bubblemessage green'>↘</span> ".$fv."</a>";
                                    $createlink.= " <span class='bubblemessage'>".intval($fsc['diraction'][$fk])."</span>";
                                }
                            } else {
                                // ONE entry found in database
                                if ($du_res['set'][0]['trash']!=1) {
                                    $createlink = "<a onclick=\"document.getElementById('configcfd').value='".trim($fk)."'; document.getElementById('switchcfd').submit();\" style='cursor: pointer;'><span class='bubblemessage green'>↘</span> ".$fv."</a>";
                                    $createlink.= " <span class='bubblemessage'>".intval($fsc['diraction'][$fk])."</span>";
                                }
                                // show directories, that should not exist
                                else {
                                    $createlink = "<a onclick=\"document.getElementById('configcfd').value='".trim($fk)."'; document.getElementById('switchcfd').submit();\" style='cursor: pointer;'><span class='bubblemessage red'>↘</span> ".$fv."</a>";
                                }
                            }
						}
                        else {
							$createlink = "<a onclick=\"document.getElementById('removecfd').value='".trim($fk)."'; document.getElementById('removedir').submit();\" style='cursor: pointer;'><span class='bubblemessage red'>✕</span> ".$fv."</a>";
						}
                        $fsc['dirlink'][] = $createlink;
					}
				}
				// creating displaying table
				echo "<table class='tablelist'>";
				// display directories in 4x2-format
				for ($dl=0; $dl<ceil(count($fsc['dirlink'])/4); $dl++) {
					echo "<tr>";
					for ($dlr=0; $dlr<4; $dlr++):
						if (array_key_exists((($dl*4)+$dlr), $fsc['dirlink'])):
							echo "<td class='tablecell two publishrequired'>".$fsc['dirlink'][(($dl*4)+$dlr)]."</td>";
						else:
							echo "<td class='tablecell two publishrequired'>&nbsp;</td>";
						endif;
					endfor;
					echo "</tr>";
                }
				// display files with actions
				if (count($fsc['files'])>0) {
					foreach ($fsc['files'] AS $fk => $fv) {
						// check db for file usage
						$fu_sql = "SELECT * FROM `menu` WHERE `filename` LIKE '%".str_replace("/", "", str_replace(".php", "", $fv))."%'";
						$fu_res = doSQL($fu_sql);
						
                        echo "<tr>";
						echo "<td class='tablecell two'>".$fv."</td>";
						
						$filesize = filesize($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fk);
						$fi = 0;
						while ($filesize>1014):
							$filesize = ceil($filesize/1024);
							$fi++;
						endwhile;
						$filemtime = filemtime($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fk);
						
						echo "<td class='tablecell one'>".$filesize." ".$filesizes[$fi]."</td>";
						echo "<td class='tablecell one'>".date("Y-m-d", $filemtime)."</td>";
						
						if ($fu_res['num']==0 && ($filemtime<(time()-(86400*14)))):
							// if no usage
							// REMOVE file without any other action
							echo "<td class='tablecell one'>";
							echo "<a onclick=\"document.getElementById('configremovefile').value='".$fsc['fileaction'][$fk]."';document.getElementById('removefile').submit();\"><span class='bubblemessage red'>".returnIntLang('bubble cleanup 404 remove', false)."</span></a>";
							echo "</td>";
						else:
							echo "<td class='tablecell one'></td>";
						endif;
						
						// if PHP-file REPLACE file with 301-header and forwarding to first level root
						if (!(strtolower($fv)=='index.php' && ($fsc['ftpbase']==$fsc['ftpdir'])) && substr($fk,-4)=='.php'):
							echo "<td class='tablecell one'>";
							echo "<a onclick=\"document.getElementById('configindexfile').value='".$fsc['fileaction'][$fk]."';document.getElementById('indexfile').submit();\"><span class='bubblemessage orange'>".returnIntLang('bubble cleanup 301 index', false)."</span></a>";
							echo "</td>";
						else:
							echo "<td class='tablecell one'></td>";
						endif;
						// if PHP-file REPLACE file with 301-header and forwarding to any other file that will be defined in the lightbox-overlay
						if (!(strtolower($fv)=='index.php' && ($fsc['ftpbase']==$fsc['ftpdir'])) && substr($fk,-4)=='.php'):
							echo "<td class='tablecell one'>";
							echo "<a onclick=\"document.getElementById('configfilefile').value='".$fsc['fileaction'][$fk]."';\" class='fancyhelper' href='#fileforwarding'><span class='bubblemessage orange'>".returnIntLang('bubble cleanup 301 file', false)."</span></a>";
							echo "</td>";
						else:
							echo "<td class='tablecell one'></td>";
						endif;
						// in any case
						echo "<td class='tablecell one'>";
						echo "<a href='".$fk."' target='_blank'><span class='bubblemessage green'>".returnIntLang('bubble cleanup open', false)."</span></a>";
						echo "</td>";
						echo "</tr>";
                    }
                }
                else {
					echo "<tr>";
					echo "<td class='tablecell eight'>";
					echo returnIntLang('cleanup no files in folder');
					echo "</td>";
					echo "</tr>";
                }
				echo "</table>";
				// close existing ftp-connection
				ftp_close($ftp);
			endif;
			
			?>
			<form id='switchcfd' name='switchcfd' method='post'><input type="hidden" id='configcfd' name='cfd' value='' /></form>
            <form id='removedir' name='removedir' method='post'><input type="hidden" id='removecfd' name='rdv' value='' /></form>
			<form id='removefile' name='removefile' method='post'><input type="hidden" name='cfd' value='<?php echo str_replace("//", "/", str_replace("//", "/", "/".substr($fsc['ftpdir'], strlen(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/")))))))); ?>' /><input type="hidden" id='configremovefile' name='crf' value='' /></form>
			<form id='indexfile' name='indexfile' method='post'><input type="hidden" name='cfd' value='<?php echo str_replace("//", "/", str_replace("//", "/", "/".substr($fsc['ftpdir'], strlen(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/")))))))); ?>' /><input type="hidden" id='configindexfile' name='cif' value='' /></form>
		</div>
	</fieldset>
	<fieldset>
		<ul class="icondesc">
            <li class="icondescitem"><span class="bubblemessage green">↘</span> Verzeichnis ist aktiv » in Verzeichnis wechseln</li>
            <li class="icondescitem"><span class="bubblemessage red">↘</span> Verzeichnis ist als gelöscht markiert » in Verzeichnis wechseln</li>
            <li class="icondescitem"><span class="bubblemessage red">✕</span> Verzeichnis ist leer » Verzeichnis löschen</li>
            <li class="icondescitem"><span class="bubblemessage red">☰</span> Verzeichnis ist nicht in der Datenbank  » in Verzeichnis wechseln</li>
			<li class="icondescitem"><span class="bubblemessage red "><?php echo returnIntLang('bubble cleanup 404 remove', false); ?></span> <?php echo returnIntLang('bubble cleanup 404 remove icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage orange"><?php echo returnIntLang('bubble cleanup 301 index', false); ?></span> <?php echo returnIntLang('bubble cleanup 301 index icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage orange"><?php echo returnIntLang('bubble cleanup 301 file', false); ?></span> <?php echo returnIntLang('bubble cleanup 301 file icondesc'); ?></li>
			<li class="icondescitem"><span class="bubblemessage green"><?php echo returnIntLang('bubble cleanup open', false); ?></span> <?php echo returnIntLang('bubble cleanup open icondesc'); ?></li>
		</ul>
	</fieldset>
	<div id="fileforwarding" style="display: none;">
		<form id='filefile' name='filefile' method='post'><input type="hidden" name='cfd' value='<?php echo str_replace("//", "/", str_replace("//", "/", "/".substr($fsc['ftpdir'], strlen(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['ftpbasedir']."/")))))))); ?>' /><input type="hidden" id='configfilefile' name='cff' value='' />
		<table class="tablelist">
			<tr>
				<td class="tablecell eight">&nbsp;<?php echo returnIntLang('inline forwarding targetpage'); ?></td>
			</tr>
			<tr>
				<td class="tablecell eight"><select name="cffid" class="one full">
					<?php getMenuLevel(0, 0, 1); ?>
				</select></td>
			</tr>
			<tr>
				<td class="tablecell eight">&nbsp;<a onclick="document.getElementById('filefile').submit();"><?php echo returnIntLang('inline set targetpage'); ?></a></td>
			</tr>
		</table>
		</form>
	</div>
	<script language="JavaScript" type="text/javascript">
	<!--
	
	$(document).ready(function() {
		$(".fancyhelper").fancybox({
			maxWidth	: 800,
			maxHeight	: 600,
			fitToView	: false,
			minWidth	: '20%',
			autoSize	: true,
			closeClick	: false,
			openEffect	: 'none',
			closeEffect	: 'none'
		});
	});
	//-->
	</script>
</div>
<?php
@ include ("./data/include/footer.inc.php");
?>
<!-- EOF -->