<?php
/**
 * Verwaltung von Dateien
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2025-01-19
 * 2025-05-22
 * Bugfix viewing documents other like imagefiles
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
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
require ("./data/include/mediafuncs.inc.php");
/* checkParamVar ----------------------------- */
$op = checkParamVar('op', '');
$extern = checkParamVar('extern', 0);
/* define actual system position ------------- */
$_SESSION['wspvars']['mgroup'] = 6;
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['fposition'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");

if (!(isset($_REQUEST['showfile']))):
	if (isset($_REQUEST['medialoc'])):
		header('location: /'.$_REQUEST['medialoc']);
	else:
		header('location: /'.$_SESSION['wspvars']['wspbasedir'].'/imagemanagement.php');
	endif;
endif;

$mediafolder = array('/media/images/', '/media/screen/', '/media/download/');
$thumbtypes = array('png', 'jpg');

// get details
$details['fullpath'] = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".trim($_REQUEST['showfile'])))));
$details['fullfile'] = substr($details['fullpath'], (strrpos($details['fullpath'], "/")+1));
$details['filetype'] = str_replace(".", "", substr($details['fullfile'], strrpos($details['fullfile'], ".")));
$details['filename'] = str_replace(".".$details['filetype'], "", $details['fullfile']);
$details['fullfold'] = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace($details['fullfile'], "", $details['fullpath']))));
// path below the selected mediadir
foreach ($mediafolder AS $mfk => $mfv) {
    $searchmedia = strpos(strval($details['fullpath']), strval($mfv));
    if ($searchmedia!==false && $searchmedia==0) {
        $details['mediapath'] = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".substr($details['fullpath'], strlen($mfv)))));
    }
}

// seitenspezifische funktionen
// bildbearbeitung
// doscale
if (isset($_POST['action']) && $_POST['action']=='doscale'):
	if ($_POST['widthtype']=='percent'):
		$newwidth = ceil(intval($_POST['orgwidth'])*($_POST['newwidth']/100));
	else:
		$newwidth = intval($_POST['newwidth']);
	endif;
	if ($_POST['heighttype']=='percent'):
		$newheight = ceil(intval($_POST['orgheight'])*($_POST['newheight']/100));
	else:
		$newheight = intval($_POST['newheight']);
	endif;
	$imageTargetFolder = str_replace("//", "/", str_replace("//", "/", trim($showpath)));
	$baseFile = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$details['fullpath']));
	$imageTmpDirectory = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/"));
	$imageFtpDirectory = str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$imageTargetFolder));
	resizeGDimage($baseFile, $imageTmpDirectory.$details['filename'].'.'.$details['filetype'], 0, $newwidth, $newheight, intval($_POST['usescale']));
	$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']))); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }} if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
	if ($ftp):
		if(@ftp_put($ftp, $imageFtpDirectory."/".$details['filename'].'.'.$details['filetype'], $imageTmpDirectory."/".$details['filename'].'.'.$details['filetype'], FTP_BINARY)):
			@unlink($imageTmpDirectory."/".$details['filename'].'.'.$details['filetype']);
		endif;
        ftp_close($ftp);
	endif;
endif;
// docopy
if (isset($_POST['action']) && $_POST['action']=='docopy'):
	$copyTargetFolder = str_replace("//", "/", str_replace("//", "/", trim($showpath)));
	$baseFile = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$details['fullpath'] ));
	$copyFtpDirectory = str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$copyTargetFolder));
	$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']))); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }} if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
	if ($ftp):
		if(@ftp_put($ftp, $copyFtpDirectory."/".$details['filename'].'-'.time().'.'.$details['filetype'], $baseFile, FTP_BINARY)):
			addWSPMsg('resultmsg', returnIntLang('mediadetails file copied'));
		endif;
        ftp_close($ftp);
	endif;
endif;
// doupload (own thumb)
if (isset($_POST['action']) && $_POST['action']=='doupload'):

	$thumbTargetFolder = str_replace("//", "/", str_replace("//", "/", trim($showpath)));
	$baseFile = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$details['fullpath'] ));
	$thumbTmpDirectory = $_FILES['media_upload']['tmp_name'];
	$thumbFtpDirectory = str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".str_replace("/media/".$_SESSION['wspvars']['upload']['basetarget']."/", "/media/".$_SESSION['wspvars']['upload']['basetarget']."/thumbs/", $thumbTargetFolder)));
	$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']))); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }} if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
	if ($ftp):
		if(@ftp_put($ftp, $thumbFtpDirectory."/".$details['filename'].'.'.$details['filetype'], $thumbTmpDirectory, FTP_BINARY)):
			$details['thumbnail'] = str_replace("//", "/", str_replace("//", "/", "/".str_replace("/media/".$_SESSION['wspvars']['upload']['basetarget']."/", "/media/".$_SESSION['wspvars']['upload']['basetarget']."/thumbs/", $thumbTargetFolder)."/".$details['filename'].'.'.$details['filetype']));
			$_SESSION['xajaxmediastructure'][$showpath][$showfile]['thumbnail'] = $details['thumbnail'];
			@unlink($thumbTmpDirectory);
			addWSPMsg('resultmsg', returnIntLang('mediadetails thumb uploaded'));
		endif;
		ftp_close($ftp);
	endif;
endif;
// savedesc
if (isset($_POST['action']) && $_POST['action']=='savedesc'):
	doSQL("INSERT INTO `mediadesc` SET `mediafile` = '".escapeSQL(trim($details['fullpath']))."', `filedesc` = '".escapeSQL(trim($_POST['media_desc']))."', `filekeys` = '".escapeSQL(trim($_POST['media_keys']))."'");	
endif;
// updatedesc
if (isset($_POST['action']) && $_POST['action']=='updatedesc'):
	doSQL("UPDATE `mediadesc` SET `filedesc` = '".escapeSQL(trim($_POST['media_desc']))."', `filekeys` = '".escapeSQL(trim($_POST['media_keys']))."' WHERE `mediafile` = '".escapeSQL(trim($details['fullpath']))."'");	
endif;
// save changed filename
if (isset($_POST['action']) && isset($_POST['media_filename']) && trim($_POST['media_filename'])!=trim($_POST['media_orgfilename'])):
	$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']))); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }} if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
	if ($ftp):
		$newpath = str_replace("//", "/", str_replace("//", "/", $_POST['media_folder']."/".trim($_POST['media_filename']).".".$details['filetype']));
		if (trim($details['fullpath'])!='' && trim($newpath)!=''):
			if (ftp_rename ($ftp, str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$details['fullpath'])), str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$newpath)))):
				$details['fullpath'] = $newpath;
                $_REQUEST['showfile'] = $newpath;
				$details['fullfile'] = trim($_POST['media_filename']).".".$details['filetype'];
				$details['filename'] = trim($_POST['media_filename']);
				// insert new mediadesc
				doSQL("INSERT INTO `mediadesc` SET `filedesc` = '".escapeSQL(trim($_POST['media_desc']))."', `filekeys` = '".escapeSQL(trim($_POST['media_keys']))."' WHERE `mediafile` = '".escapeSQL(trim($details['fullpath']))."'");
				// sql statement for wspmedia
				doSQL("UPDATE `wspmedia` SET `filename` = '".$details['filename'].".".$details['filetype']."', `lastchange` = ".time()." WHERE (`filename` = '".trim($_POST['media_orgfilename']).".".$details['filetype']."' AND `mediafolder` = '".trim($_POST['media_folder'])."')");
			endif;
		endif;
		ftp_close($ftp);
	endif;
endif;

// more details
$details['fileinfo'] = @getimagesize(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$details['fullpath'])));
$details['filesize'] = @filesize(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$details['fullpath'])));
// try to get thumbnail
$details['thumbnail'] = '';
foreach ($mediafolder AS $mk => $mv):
	foreach ($thumbtypes AS $tk => $tv):
		$checkthumbpath = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".str_replace(".".$details['filetype'], ".".$tv, str_replace($mv, $mv."thumbs/", $details['fullpath']))));
		if (is_file($checkthumbpath) && str_replace(".".$details['filetype'], ".".$tv, str_replace($mv, $mv."thumbs/", $details['fullpath']))!=$details['fullpath']):
			$details['thumbnail'] = str_replace(".".$details['filetype'], ".".$tv, str_replace($mv, $mv."thumbs/", $details['fullpath']));
		endif;
	endforeach;
endforeach;
$details['preview'] = '';
foreach ($mediafolder AS $mk => $mv):
	foreach ($thumbtypes AS $tk => $tv):
		$checkthumbpath = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".str_replace(".".$details['filetype'], ".".$tv, str_replace($mv, $mv."preview/", $details['fullpath']))));
		if (is_file($checkthumbpath) && str_replace(".".$details['filetype'], ".".$tv, str_replace($mv, $mv."preview/", $details['fullpath']))!=$details['fullpath']):
			$details['preview'] = str_replace(".".$details['filetype'], ".".$tv, str_replace($mv, $mv."preview/", $details['fullpath']));
		endif;
	endforeach;
endforeach;

if ($details['thumbnail']==''):
	// 2015-09-22
	// try to generate thumbnail (again)
endif;
if ($details['filetype']=='pdf' && $details['preview']==''):
	// 2015-09-22
	// try to generate preview (again)
endif;
if ($details['filetype']=='jpg' || $details['filetype']=='jpeg' || $details['filetype']=='gif' || $details['filetype']=='png'):
	$details['preview'] = $details['fullpath'];
endif;

// check usage
$details['usage'] = array();
$details['usage_content'] = array();
$details['usage_global'] = array();
$details['usage_menu'] = array();
$details['usage_style'] = array();
$details['usage_modtable'] = array();

// check content usage
$cc_sql = "SELECT c.`mid`, c.`cid` FROM `content` AS c, `menu` AS m WHERE c.`trash` = 0 AND (m.`trash` = 0 AND m.`mid` = c.`mid`) AND (c.`valuefields` LIKE '%".escapeSQL(trim($details['fullpath']))."%' OR c.`valuefields` LIKE '%".escapeSQL(trim(str_replace("//", "/", str_replace($_POST['media_folder'], "/",$details['fullpath'])))) . "%')";
$cc_res = doSQL($cc_sql);
if ($cc_res['num']>0) {
	foreach ($cc_res['set'] AS $ccrk => $ccrv) {
		$details['usage'][] = $ccrv['mid'];
		$details['usage_content'][] = $ccrv['cid'];
	}
}

// check global content usage
$gc_sql = "SELECT c.`mid`, g.`id` FROM `globalcontent` AS g, `content` AS c WHERE g.`trash` = 0 AND c.`trash` = 0 AND g.`id` = c.`globalcontent_id` AND (g.`valuefield` LIKE '%".escapeSQL(trim($details['fullpath']))."%')";
$gc_res = doSQL($gc_sql);
if ($gc_res['num']>0):
    foreach ($gc_res['set'] AS $gcrk => $gcrv) {
		$details['usage'][] = $gcrv['mid'];
		$details['usage_global'][] = $gcrv['id'];
    }
endif;

// check menuimage usage
$mc_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0 AND (`imageon`='".escapeSQL(trim($details['fullpath']))."' OR `imageoff`='".escapeSQL(trim($details['fullpath']))."' OR `imageakt`='".escapeSQL(trim($details['fullpath']))."' OR `imageclick`='".escapeSQL(trim($details['fullpath'])). "')";
$mc_res = doSQL($mc_sql);
if ($mc_res['num']>0):
    foreach ($mc_res['set'] AS $mcrk => $mcrv) {
		$details['usage'][] = $mcrv['mid'];
		$details['usage_menu'][] = $mcrv['mid'];
    }

endif;

// check stylesheet usage
$sc_sql = "SELECT `id` FROM `stylesheets` WHERE `stylesheet` LIKE '%".escapeSQL(trim($details['fullpath']))."%'";
$sc_res = doSQL($sc_sql);
if ($sc_res['num']>0) {
    foreach ($sc_res['set'] AS $scrk => $scrv) {
		$details['usage'][] = $scrv['id'];
		$details['usage_style'][] = $scrv['id'];
    }
}

// check modular usage
$moduleusage_sql = "SELECT * FROM `modules` WHERE `affectedcontent` != '' && `affectedcontent` IS NOT NULL";
$moduleusage_res = doSQL($moduleusage_sql);
if ($moduleusage_res['num']>0) {
    foreach ($moduleusage_res['set'] AS $murk => $murv) {
        $grepdata = unserializeBroken($murv['affectedcontent']);
        foreach ($grepdata AS $table => $fieldnames) {
            $fileval_sql = array();
            foreach ($fieldnames AS $fieldname) {
                $fileval_sql[] = " `".$fieldname."` LIKE '%".escapeSQL(trim($details['fullpath']))."%' ";
                $fileval_sql[] = " `".$fieldname."` LIKE '%".escapeSQL(trim($details['mediapath']))."%' ";
                $fileval_sql[] = " `".$fieldname."` LIKE '%".escapeSQL(trim($details['fullfile']))."%' ";
            }
            $filemod_sql = "SELECT * FROM `".$table."` WHERE (".implode(" OR ", $fileval_sql).")";
            $filemod_num = getNumSQL($filemod_sql);
            if ($filemod_num>0) {
                $details['usage'][] = $table;
                $details['usage_modtable'][] = $murv['name'];
            }
        }
    }
}

if (count($details['usage'])==0):
	doSQL("UPDATE `wspmedia` SET `embed` = 0, `lastchange` = ".time()." WHERE `filename` = '".escapeSQL(trim($details['fullfile']))."' AND `mediafolder` = '".escapeSQL(trim($details['fullfold']))."'");
elseif (count($details['usage'])>0):
	doSQL("UPDATE `wspmedia` SET `embed` = 1, `lastchange` = ".time()." WHERE `filename` = '".escapeSQL(trim($details['fullfile']))."' AND `mediafolder` = '".escapeSQL(trim($details['fullfold']))."'");
endif;

// mediadesc pruefen
$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".escapeSQL(trim($details['fullpath']))."%'";
$desc_res = doSQL($desc_sql);
$details['mediadesc'] = '';
$details['mediakeys'] = '';
if ($desc_res['num']>0):
	$details['mediadesc'] = trim($desc_res['set'][0]["filedesc"]);
	$details['mediakeys'] = trim($desc_res['set'][0]["filekeys"]);
endif;

include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

?>
<div id="contentholder">
	<?php if ($extern!=1) echo "<fieldset><h1>".returnIntLang('mediadetails headline', true)."</h1></fieldset>\n"; ?>
	<?php
	
	if (empty($details['filesize'])) {
		echo '<fieldset class="text"><p>' . returnIntLang('file doesnt exist in filesystem') . '</p></fieldset>';
	} else {

		// gather document information
		$maxwidth = 450;
		$maxheight = 300;
		$scale = 100;

		if (intval($details['fileinfo'][2] ?? 0)>0):
			$thumb = givebackThumb($details['fileinfo'][0],$details['fileinfo'][1],$maxwidth,$maxheight);
			$scale = ceil($thumb[0]/$details['fileinfo'][0]*100);
		endif;
		
		if ($scale > 100) {
			$scale = 100;
		} elseif ($scale > 74) {
			$scale = 75;
		} elseif ($scale > 49) {
			$scale = 50;
		} elseif ($scale > 24) {
			$scale = 25;
		} else {
			$scale = 10;
		}
		
		$imageedit = (
			($_SESSION['wspvars']['createimage'] ?? null) == "checked" && 
			($_SESSION['wspvars']['createthumbfromimage'] ?? null) == "checked");
		
		if ($details['thumbnail']!=""):
			?><fieldset <?php if ($details['preview']!=""): echo " class='four left' "; endif; ?>>
				<legend><?php echo returnIntLang('mediadetails thumbnail', true); ?> <?php echo legendOpenerCloser('fieldset_thumbnail'); ?></legend>
				<div id="fieldset_thumbnail">
				<table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom: 5px;">
					<tr>
						<td width="100%" valign="top" align="center"><img src="<?php echo $details['thumbnail']; ?>" align="center" style="border: 1px solid #000; max-width: 100%;" /></td>
					</tr>
				</table>
				</div>
			</fieldset><?php
		endif;
		
		if ($details['preview']!=""):
			?>
			<fieldset <?php if ($details['thumbnail']!=""): echo " class='four right' "; endif; ?>>
				<legend><?php echo returnIntLang('mediadetails lightbox', true); ?> <?php echo legendOpenerCloser('fieldset_preview'); ?></legend>
				<div id="fieldset_preview">
				<table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom: 5px;">
					<tr>
						<td width="100%" valign="top" align="center"><img src="<?php echo $details['preview']; ?>" align="center" style="border: 1px solid #000; max-width: 100%;" /></td>
					</tr>
				</table>
				</div>
			</fieldset>
			<fieldset class="full" style="display: none;">
				<legend><?php echo returnIntLang('mediadetails toolbox', true); ?> <?php echo legendOpenerCloser('fieldset_toolbox'); ?></legend>
				<script type="text/javascript">
				<!--
				
				function updateWH(changetype) {
					var origwidth = <?php echo ($details['fileinfo'][0] ?? 'null'); ?>;
					var origheight = <?php echo ($details['fileinfo'][1] ?? 'null'); ?>;
					var origscale = <?php echo (($details['fileinfo'][0] ?? false) && ($details['fileinfo'][1] ?? false) ? ($details['fileinfo'][0]/$details['fileinfo'][1]) : 1); ?>;
					if (changetype=='usescale') {
						if (document.getElementById('usescale').checked) {
							changetype = 'newwidth';
							document.getElementById('heighttype').value = document.getElementById('widthtype').value;
							}
						}
					if (changetype=='newwidth') {
						if (document.getElementById('usescale').checked) {
							if (document.getElementById('widthtype').value=='px') {
								newheight = Math.round((document.getElementById('newwidth').value*1)/origscale);
								if (document.getElementById('heighttype').value=='px') {
									document.getElementById('newheight').value = newheight;
									}
								else if (document.getElementById('heighttype').value=='percent') {
									document.getElementById('newheight').value = Math.round((newheight/origheight)*100);
									}
								}
							else {
								newheight = Math.round((document.getElementById('newwidth').value*1)*origheight/100);
								if (document.getElementById('heighttype').value=='px') {
									document.getElementById('newheight').value = newheight;
									}
								else if (document.getElementById('heighttype').value=='percent') {
									document.getElementById('newheight').value = document.getElementById('newwidth').value;
									}
								}
							}
						}
					else if (changetype=='newheight') {
						if (document.getElementById('usescale').checked) {
							if (document.getElementById('heighttype').value=='px') {
								newwidth = Math.round((document.getElementById('newheight').value*1)*origscale);
								if (document.getElementById('widthtype').value=='px') {
									document.getElementById('newwidth').value = newwidth;
									}
								else if (document.getElementById('widthtype').value=='percent') {
									document.getElementById('newwidth').value = Math.round((newwidth/origwidth)*100);
									}
								}
							else {
								newwidth = Math.round((document.getElementById('newheight').value*1)*origwidth/100);
								if (document.getElementById('widthtype').value=='px') {
									document.getElementById('newwidth').value = newwidth;
									}
								else if (document.getElementById('widthtype').value=='percent') {
									document.getElementById('newwidth').value = document.getElementById('newheight').value;
									}
								}
							}
						}
					else if (changetype=='widthtype') {
						if (document.getElementById('widthtype').value=='percent') {
							document.getElementById('newwidth').value = Math.round((document.getElementById('newwidth').value/origwidth)*100);
							}
						else {
							document.getElementById('newwidth').value = Math.round(origwidth*(document.getElementById('newwidth').value/100));
							}
						}
					else if (changetype=='heighttype') {
						if (document.getElementById('heighttype').value=='percent') {
							document.getElementById('newheight').value = Math.round((document.getElementById('newheight').value/origheight)*100);
							}
						else {
							document.getElementById('newheight').value = Math.round(origheight*(document.getElementById('newheight').value/100));
							}
						}
									
					// alert (changetype + ' + ' + origwidth + ' + ' + origheight + ' + ' + origscale);
					}
					
				function chooseAction(doAction) {
					document.getElementById('action_scale').style.display = 'none';
					document.getElementById('action_turn').style.display = 'none';
					document.getElementById('action_crop').style.display = 'none';
					document.getElementById('action_mirror').style.display = 'none';
					document.getElementById('action_copy').style.display = 'none';
					document.getElementById('action_thumb').style.display = 'none';
					if (doAction!="") {
						document.getElementById('action_' + doAction).style.display = 'block';
						if (doAction=="copy") {
							document.getElementById('select_action').selectedValue = 'copy';
							}
						}
					else {
						document.getElementById('select_action').selectedIndex = 0;
						}
					}
											
				//-->
				</script>
				<div id="fieldset_toolbox">
					<ul class="tablelist">
						<li class="tablecell two"><?php echo returnIntLang('str width', true); ?> <?php echo $details['fileinfo'][0]; ?> px</li>
						<li class="tablecell two"><?php echo returnIntLang('str height', true); ?> <?php echo $details['fileinfo'][1]; ?> px</li>
						<li class="tablecell two"><?php echo returnIntLang('str filesize', true); ?> <?php 
							
						$c = 1;
						$disk = $details['filesize'];
						while ($disk>1024):
							$disk = $disk/1024;
							$c++;
						endwhile;
						$spacevals = array(
							1 => returnIntLang('mediadetails space Byte', true),
							2 => returnIntLang('mediadetails space kB', true),
							3 => returnIntLang('mediadetails space MB', true),
							4 => returnIntLang('mediadetails space GB', true),
							5 => returnIntLang('mediadetails space TB', true)
						);
					
						echo ceil($disk).' '.$spacevals[$c]; ?></li>
						<li class="tablecell two"><?php echo returnIntLang('str filetype', true); ?> <?php 
						
						echo strtoupper($details['filetype']);
						
						?></li>
						<?php if ($imageedit): ?>
						<li class="tablecell eight head"><select id="select_action" onchange="chooseAction(this.value);">
							<option value=""><?php echo returnIntLang('mediadetails toolbox choose action', false); ?></option>
							<option value="scale"><?php echo returnIntLang('mediadetails toolbox action scale', false); ?></option>
							<!-- <option value="turn"><?php echo returnIntLang('mediadetails toolbox action turn or mirror', false); ?></option> -->
							<!-- <option value="crop"><?php echo returnIntLang('mediadetails toolbox action crop', false); ?></option> -->
							<!-- <option value="mirror"><?php echo returnIntLang('mediadetails toolbox action mirroring', false); ?></option> -->
							<option value="copy"><?php echo returnIntLang('mediadetails toolbox action copy', false); ?></option>
							<option value="thumb"><?php echo returnIntLang('mediadetails toolbox action upload thumb', false); ?></option>
						</select></li>
						<?php endif; ?>
					</ul>
				</div>
			</fieldset>
		<?php endif; ?>
		<?php if (isset($details['usage']) && is_array($details['usage']) && count($details['usage'])>0): ?>
			<fieldset class="full">
				<legend><?php echo returnIntLang('mediadetails usage information', true); ?> <?php echo legendOpenerCloser('fieldset_use'); ?></legend>
				<div id="fieldset_use">
					<ul class="tablelist">
					<?php 
						
						if (isset($details['usage_content']) && is_array($details['usage_content']) && count($details['usage_content'])>0) { 
							echo "<li class=\"tablecell eight\">".returnIntLang('moddetails usage in content')."</li>";
							foreach ($details['usage_content'] AS $udk => $udv) {
								$cinfo_sql = "SELECT `m`.`description` FROM `menu` AS `m`, `content` AS `c` WHERE `c`.`mid` = `m`.`mid` AND `c`.`cid` = ".intval($udv);
								$cinfo_res = doSQL($cinfo_sql);
								if ($cinfo_res['num']>0) {
									echo "<li class=\"tablecell two\">".$cinfo_res['set'][0]['description']."</li>";
								}
							}
						}
						
						if (isset($details['usage_global']) && is_array($details['usage_global']) && count($details['usage_global'])>0) { 
							echo "<li class=\"tablecell eight\">".returnIntLang('moddetails usage in global content')."</li>";
						}
						
						if (isset($details['usage_menu']) && is_array($details['usage_menu']) && count($details['usage_menu'])>0) { 
							echo "<li class=\"tablecell eight\">".returnIntLang('moddetails usage in menu')."</li>";
						}
						
						if (isset($details['usage_style']) && is_array($details['usage_style']) && count($details['usage_style'])>0) { 
							echo "<li class=\"tablecell eight\">".returnIntLang('moddetails usage in css')."</li>";
						}
						
						if (isset($details['usage_modtable']) && is_array($details['usage_modtable']) && count($details['usage_modtable'])>0) { 
							echo "<li class=\"tablecell eight\">".returnIntLang('moddetails usage in module')."</li>";
							foreach ($details['usage_modtable'] AS $udk => $udv) {
								echo "<li class=\"tablecell two\">".$udv."</li>";
							}
						}
						
						?>
					</ul>
				</div>
			</fieldset>
		<?php endif; ?>
		<script type="text/javascript">
		<!--
		function checkMediaFileName() {
			var newFileName = $('#media_setfilename').prop('value');
			var orgFileName = $('#media_orgfilename').prop('value');
			if (newFileName!=orgFileName) {
				$.post("xajax/ajax.checkmediafilename.php", {'filefolder': '<?php echo $details['fullfold']; ?>', 'showfile': '<?php echo $details['fullpath']; ?>', 'filetype': '<?php echo $details['filetype']; ?>', 'newfilename': newFileName, 'orgfilename': orgFileName}).done (function(data) {
					if (data!=1) {
						$('#media_setfilename').prop('value', data);
						}
					})
				}
			}
						
		//-->
		</script>
		<fieldset class="full">
			<legend><?php echo returnIntLang('mediadetails description', true); ?> <?php echo legendOpenerCloser('fieldset_desc'); ?></legend>
			<div id="fieldset_desc">
				<form name="mediadescform" id="mediadescform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
				<table class="tablelist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('mediadetails filepath', true); ?></td>
						<td class="tablecell six"><?php echo "http://".str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['liveurl']."/".$details['fullpath'])); ?></td>
					</tr>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('mediadetails filename', true); ?></td>
						<td class="tablecell six"><input type="text" name="media_filename" id="media_setfilename" onblur="checkMediaFileName();" value="<?php echo prepareTextField($details['filename']); ?>" class="full" /><input type="hidden" name="media_orgfilename" id="media_orgfilename" value="<?php echo prepareTextField($details['filename']); ?>" /></td>
					</tr>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('str filesize', true); ?></td>
						<td class="tablecell six"><?php $disk = $details['filesize'];
						$c = 1; while ($disk>1024):
							$disk = $disk/1024;
							$c++;
						endwhile;
						$spacevals = array(
							1 => returnIntLang('mediadetails space Byte', true),
							2 => returnIntLang('mediadetails space kB', true),
							3 => returnIntLang('mediadetails space MB', true),
							4 => returnIntLang('mediadetails space GB', true),
							5 => returnIntLang('mediadetails space TB', true)
						);
						echo ceil($disk).' '.$spacevals[$c]; ?></td>
					</tr>
				</table>
				<input type="hidden" name="action" value="<?php if ($desc_num>0): echo "updatedesc"; else: echo "savedesc"; endif; ?>">
				<input type="hidden" name="showfile" value="<?php echo str_replace('//', '/', str_replace('//', '/', $_REQUEST['showfile'])); ?>" />
				<input type="hidden" name="media_folder" value="<?php echo str_replace('//', '/', str_replace('//', '/', $details['fullfold'])); ?>" />
				<input type="hidden" name="medialoc" value="<?php echo $_REQUEST['medialoc']; ?>" />
				</form>
				<fieldset class="options innerfieldset">
					<p><a href="#" onClick="document.getElementById('mediadescform').submit();" class="greenfield"><?php echo returnIntLang('button save data', false); ?></a></p>
				</fieldset>
			</div>
		</fieldset>
	<?php } ?>
		<fieldset class="options">
			<p><a href="#" onclick="document.getElementById('back').submit();" class="orangefield"><?php echo returnIntLang('str back', false); ?></a></p>
		</fieldset>	
		<form id="back" name="back" action="<?php if (trim($_REQUEST['medialoc'])!=""): echo $_REQUEST['medialoc']; else: echo "/wsp/imagemanagement.php"; endif; ?>" method="post">
		<input type="hidden" name="medialoc" value="<?php if (trim($_REQUEST['medialoc'])!=""): echo $_REQUEST['medialoc']; else: echo "/wsp/imagemanagement.php"; endif; ?>" />
		</form>
		<form name="jumptodesign" id="jumptodesign" method="post" action="designedit.php">
		<input name="op" type="hidden" value="edit" />
		<input name="id" id="jumptodesignid" type="hidden" value="" />
		</form>
		<form name="jumptocontent" id="jumptocontent" method="post" action="contentedit.php">
		<input name="op" type="hidden" value="edit" />
		<input name="cid" id="jumptocontentid" type="hidden" value="" />
		</form>
		<form name="jumptoglobalcontent" id="jumptoglobalcontent" method="post" action="globalcontentedit.php">
		<input name="gcid" id="jumptoglobalcontentid" type="hidden" value="" />
		</form>
		<form name="jumptomenu" id="jumptomenu" method="post" action="menuedit.php">
		<input name="action" type="hidden" value="edit" />
		<input name="mid" id="jumptomenuid" type="hidden" value="" />
		</form>
	</div>
	<?php

if ($extern=='1'):
	include ("./data/include/footerempty.inc.php");
else:
	include ("./data/include/footer.inc.php");
endif;

?>
<!-- EOF -->