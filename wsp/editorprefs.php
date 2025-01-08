<?php
/**
 * @description global editor properties
 * @author s.haendler@covi.de
 * @copyright (c) 2021, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8.1
 * @lastchange 2021-01-19
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
/* checkParamVar ----------------------------- */

// definition der aktiven position und rahmenbedingungen zur benutzung der seite
$_SESSION['wspvars']['lockstat'] = 'siteprops';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");

if (file_exists('data/include/config.inc.php')):
    @include 'data/include/config.inc.php';
endif;

// check for rootphrase file
if (file_exists("data/include/rootphrase.inc.php")):
	require ("data/include/rootphrase.inc.php");
else:
	$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
    if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }}
    if (isset($_SESSION['wspvars']['ftppasv'])) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
	$ftpcon = ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
	if ($ftpcon):
		$fh = fopen("./tmp/rootphrase.inc.php", "w+");
		fwrite($fh, "<?php
/**
 * blowfish and xtea passphrase
 * @author system
 * @since 4.0
 * @version ".$GLOBALS['wspvars']['wspversion']."
 * @lastchange ".date("Y-m-d")."
 */

\$wspvars['rootphrase'] = \"".md5($_SESSION['wspvars']['ftppass'])."\";
?>");
		fclose($fh);
		// copy file to structure
		ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/rootphrase.inc.php', $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/tmp/rootphrase.inc.php', FTP_BINARY);
		@ftp_close($ftpcon);
	endif;
	if (file_exists("data/include/rootphrase.inc.php")):
		require ("data/include/rootphrase.inc.php");
	else:
		$errormsg = "<p>Daten konnten nicht empfangen werden.</p>";
	endif;
endif;
require ("data/include/xtea/xtea.class.php");
// initiate xtea class
if (array_key_exists('rootphrase', $_SESSION['wspvars'])):
	$xtea = new XTEA($_SESSION['wspvars']['rootphrase']);
else:
	$xtea = new XTEA($wspvars['rootphrase']);
endif;

// save editor prefs
if (isset($_POST['save_data'])):
	if (intval($_POST['backupsteps'])<3) { $_POST['backupsteps'] = 3; }
	if (intval($_POST['autologout'])<15) { $_POST['autologout'] = 15; }
	if (intval($_POST['loginfails'])<3) { $_POST['loginfails'] = 3; }
	// replace spaces in thumbsize, pdfscalepreview, hiddenmedia
	if(isset($_POST['thumbsize'])) $_POST['thumbsize'] = str_replace(" ", "", $_POST['thumbsize']);
	if(isset($_POST['pdfscalepreview'])) $_POST['pdfscalepreview'] = str_replace(" ", "", $_POST['pdfscalepreview']);
	if(isset($_POST['hiddenmedia'])) $_POST['hiddenmedia'] = str_replace(" ", "", $_POST['hiddenmedia']);
    if(isset($_POST['hiddenimages'])) $_POST['hiddenimages'] = str_replace(" ", "", $_POST['hiddenimages']);
    if(isset($_POST['hiddendropdown'])) $_POST['hiddendropdown'] = str_replace(" ", "", $_POST['hiddendropdown']);
	// run db entry
	foreach ($_POST AS $key => $value):
		if ($key!="save_data"):
			doSQL("DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL($key)."'");
			if (is_array($value)):
				doSQL("INSERT INTO `wspproperties` SET `varname` = '".escapeSQL($key)."', `varvalue` = '".escapeSQL(serialize($value))."'");
			elseif($key=="smtp_authpw"):
				doSQL("INSERT INTO `wspproperties` SET `varname` = '".escapeSQL($key)."', `varvalue` = '".$xtea->Encrypt($value)."'");
			else:
				doSQL("INSERT INTO `wspproperties` SET `varname` = '".escapeSQL($key)."', `varvalue` = '".escapeSQL($value)."'");
			endif;
		endif;
	endforeach;
	$_SESSION['wspvars']['resultmsg'] = "<p>Die Einstellungen wurden gespeichert.</p>";
endif;

// get siteinfo facts from saved file
require ("./data/include/siteinfo.inc.php");
// head der datei
require ("./data/include/header.inc.php");
require ("./data/include/wspmenu.inc.php");
// get siteinfo facts from db
$siteinfo_sql = "SELECT * FROM `wspproperties`";
$siteinfo_res = doSQL($siteinfo_sql);
if ($siteinfo_res['num']>0):
	foreach ($siteinfo_res['set'] AS $sirk => $sirv):
		$sitedata[trim($sirv['varname'])] = $sirv['varvalue'];
	endforeach;
endif;
// setup some siteinfo facts
$sitedata['devurl'] = trim(str_replace("http://", "", $sitedata['devurl']));
if (trim(str_replace("http://", "", $sitedata['devurl']))==""): $sitedata['devurl'] = trim(str_replace("http://", "", $sitedata['siteurl']));	endif;
if (intval($sitedata['backupsteps'])<3): $sitedata['backupsteps'] = 3; endif;
if (intval($sitedata['autologout'])<15): $sitedata['autologout'] = 15; endif;
if (intval($sitedata['loginfails'])<3): $sitedata['loginfails'] = 3; endif;
if (!(isset($sitedata['cookiedays'])) || (isset($sitedata['cookiedays']) && intval($sitedata['cookiedays']))<1): $sitedata['cookiedays'] = 1; endif;

// create rotots.txt if needed
if ((isset($sitedata['wsprobots']) && $sitedata['wsprobots']==1) || (isset($sitedata['disabledrobots']) && trim($sitedata['disabledrobots']!=''))):
	$disallow = array("# robots.txt page ".$sitedata['siteurl']."\n", "User-agent: *");
	if (isset($sitedata['wsprobots']) && $sitedata['wsprobots']==1): $disallow[] = str_replace("//", "/", str_replace("//", "/", "Disallow: /".$_SESSION['wspvars']['wspbasedir']."/")); endif;
	if (isset($sitedata['disabledrobots']) && trim($sitedata['disabledrobots']!='')):
		$disdir = explode(",", $sitedata['disabledrobots']);
		foreach ($disdir AS $dv):
			if (trim($dv)!=''):
				$disallow[] = str_replace("//", "/", str_replace("//", "/", "Disallow: /".trim($dv)."/"));
			endif;
		endforeach;	
	endif;
	$disallow = implode("\n", $disallow);
	// create ftp-connect
    $ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
    if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }}
    if (isset($_SESSION['wspvars']['ftppasv'])) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
	$ftpcon = ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
	if ($ftpcon):
		// create file content
		$fh = fopen("./tmp/robots.txt", "w+");
		fwrite($fh, $disallow);
		fclose($fh);
		// copy file to structure
		ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir'].'/robots.txt', $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/tmp/robots.txt', FTP_BINARY);
		@ftp_close($ftp);
		unlink("./tmp/robots.txt");
	endif;
endif;

?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('editorprefs headline'); ?></h1></fieldset>
	<fieldset>
		<legend><?php echo returnIntLang('str legend', true); ?> <?php echo legendOpenerCloser('wsplegend'); ?></legend>
		<div id="wsplegend">
			<p><?php echo returnIntLang('editorprefs desc'); ?></p>
		</div>
	</fieldset>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="frmprefs" style="margin: 0px;">
	<fieldset id="fieldset_environment">
		<legend><?php echo returnIntLang('editorprefs environment'); ?> <?php echo legendOpenerCloser('environment'); ?></legend>
		<div id="environment">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs devurl'); ?> <?php helpText(returnIntLang('editorprefs devurl help', false)); ?></td>
					<td class="tablecell six"><input type="text" name="devurl" id="devurl" value="<?php echo $sitedata['devurl']; ?>" class="one full" placeholder="<?php echo returnIntLang('editorprefs devurl without http://', false); ?>" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs base template'); ?></td>
					<td class="tablecell two"><select name="templates_id" class="three full">
						<?php
	                   
                        $template_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
                        $template_res = doSQL($template_sql);
                        if ($template_res['num']>0):
                            foreach ($template_res['set'] AS $rsk => $row):
                                if ($row['id']==$sitedata['templates_id']):
                                    echo "<option value=\"".$row['id']."\" selected=\"selected\">".$row['name']."</option>\n";
                                else:
                                    echo "<option value=\"".$row['id']."\">".$row['name']."</option>\n";
                                endif;
                            endforeach;
                        endif;
                        
						?>
					</select></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs menudisplay'); ?></td>
					<td class="tablecell two"><select name="menustyle" class="one full">
						<option value="0" <?php if(intval($sitedata['menustyle'])==0) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('editorprefs menustyle horizontal', false); ?></option>
						<option value="1" <?php if(intval($sitedata['menustyle'])==1) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('editorprefs menustyle vertical', false); ?></option>
					</select></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs wspstyle'); ?></td>
					<td class="tablecell two"><?php
						
					/* read all files from /media/layout/ and find files with @type design */
					$funcfolder = array();
					$designfiles = array();
					$functiondir = opendir ($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasediradd']."/".$wspvars['wspbasedir']."/media/layout/");
				    while ($entry = readdir($functiondir)):
						if (stristr($entry, ".css.php")):
							$cssfile[] = $entry;
						endif;
					endwhile;
				    closedir ($functiondir);
					sort ($cssfile);
					foreach($cssfile AS $key => $fileinfo):
						$filearray = file($_SERVER['DOCUMENT_ROOT']."/".$wspvars['wspbasediradd']."/".$wspvars['wspbasedir']."/media/layout/".$fileinfo);
						$designfiles[$key]['file'] = str_replace(".css.php", "", $fileinfo);
						for ($fa=1; $fa<count($filearray); $fa++):
							if (substr(trim($filearray[$fa]),0,3)=="* @"):
								if (substr(trim($filearray[$fa]),3,11)=="description"):
									$designfiles[$key]['desc'] = trim(substr(trim($filearray[$fa]),14));
								elseif (substr(trim($filearray[$fa]),3,4)=="type"):
									$designfiles[$key]['type'] = trim(substr(trim($filearray[$fa]),7));
								elseif (substr(trim($filearray[$fa]),3,7)=="version"):
									$designfiles[$key]['vers'] = trim(substr(trim($filearray[$fa]),10));
								endif;
							endif;
							if ($fa>20 || substr(trim($filearray[$fa]),0,2)=="*/"):
								$fa = count($filearray);
							endif;
						endfor;
						if (!(key_exists('type', $designfiles[$key])) || (key_exists('type', $designfiles[$key]) && trim($designfiles[$key]['type'])=="")):
							unset($designfiles[$key]);
						endif;
					endforeach;
					if (count($designfiles)>0):
						echo "<select name=\"wspstyle\" class=\"one full\">";
						foreach ($designfiles AS $dkey => $dvalue):
						echo "<option value=\"".$dvalue['file']."\" ";
						if ($sitedata['wspstyle']==$dvalue['file']): echo " selected=\"selected\""; endif;
						echo ">";
						echo trim(trim($dvalue['desc'])." ".trim($dvalue['vers']));
						echo "</option>";
						endforeach;
						echo "</select>";
					else:
						echo returnIntLang('editorprefs wspstyle not found', true);
					endif;
						
					?></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs wspbaselang'); ?></td>
					<td class="tablecell two"><select name="wspbaselang" class="one full">
						<?php foreach($_SESSION['wspvars']['locallanguages'] AS $llkey => $llvalue): 
							echo "<option value=\"".$llkey."\" ";
							if (array_key_exists('wspbaselang', $sitedata) && $sitedata['wspbaselang']==$llkey): echo " selected=\"selected\" "; endif;
							echo ">".$llvalue."</option>";
						endforeach; ?>
					</select></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs extendedmenu'); ?></td>
					<td class="tablecell six"><input type="hidden" name="extendedmenu" value="0" /><input type="checkbox" name="extendedmenu" id="extendedmenu" <?php if(array_key_exists('extendedmenu', $sitedata) && intval($sitedata['extendedmenu'])==1) echo "checked=\"checked\""; ?> value="1" /></td>
				</tr>
			</table>
		</div>
	</fieldset>
	<?php
	$smtp_sql ="SELECT * FROM `wspaccess` WHERE `type`='smtp'";
	if(intval(doSQL($smtp_sql)['num'])>0):
		?>
		<fieldset id="fieldset_mailsetting">
			<legend><?php echo returnIntLang('editorprefs mailsetting'); ?> <?php echo legendOpenerCloser('mailsettings'); ?></legend>
			<div id="mailsettings">
				<table class="tablelist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('editorprefs mailsetting method'); ?></td>
						<td class="tablecell six"><select name="mailclass" class="three full">
							<option value="0" <?php if($sitedata['mailclass']==0): echo "selected"; endif; ?>><?php echo returnIntLang('editorprefs standardmail'); ?></option>
							<option value="1" <?php if($sitedata['mailclass']==1): echo "selected"; endif; ?>><?php echo returnIntLang('editorprefs smtpmail'); ?></option>
						</select></td>
					</tr>
				</table>
			</div>
		</fieldset>
	<?php else: ?>
		<input type="hidden" name="mailclass" value="0" style="width: 99%;">
	<?php endif; ?>
	<fieldset id="fieldset_workflow">
		<legend><?php echo returnIntLang('editorprefs workflow'); ?> <?php echo legendOpenerCloser('workflow'); ?></legend>
		<div id="workflow">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs deleted structure', true); ?></td>
					<td class="tablecell two"><select id="deletedmenu" name="deletedmenu" class="one full">
						<option value="0"><?php echo returnIntLang('editorprefs deleted structure stay', false); ?></option>
						<option value="1" <?php if(isset($sitedata['deletedmenu']) && $sitedata['deletedmenu']=='1') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs deleted structure delete', false); ?></option>
						<option value="2" <?php if($sitedata['deletedmenu']=='2') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs deleted structure index', false); ?></option>
						<option value="3" <?php if($sitedata['deletedmenu']=='3') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs deleted structure hint', false); ?></option>
					</select></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs bind content visibility to menu', true); ?> <?php helpText(returnIntLang('editorprefs bind content visibility to menu help', false)); ?></td>
					<td class="tablecell two"><input type="hidden" name="bindcontentview" value="0" /><input type="checkbox" name="bindcontentview" id="bindcontentview" <?php if(isset($sitedata['bindcontentview']) && intval($sitedata['bindcontentview'])==1) echo "checked=\"checked\""; ?> value="1" />&nbsp;</td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs hidden structure', true); ?></td>
					<td class="tablecell two"><select id="hiddenmenu" name="hiddenmenu" class="one full">
						<option value="1" <?php if(isset($sitedata['hiddenmenu']) && $sitedata['hiddenmenu']=='1') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs hidden structure hide contents', false); ?></option>
						<option value="2" <?php if(isset($sitedata['hiddenmenu']) && $sitedata['hiddenmenu']=='2') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs hidden structure disable page', false); ?></option>
					</select></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs nocontent parsing', true); ?></td>
					<td class="tablecell two"><select id="nocontentmenu" name="nocontentmenu" class="one full">
						<option value="0" <?php if($sitedata['nocontentmenu']=='0') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs nocontent noparse', false); ?></option>
						<option value="1" <?php if($sitedata['nocontentmenu']=='1') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs nocontent nocontent', false); ?></option>
					</select></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs replacechars', true); ?> <?php helpText(returnIntLang('editorprefs replacechars help', false)); ?></td>
					<td class="tablecell two"><select name="filereplacer" id="filereplacer"><option value="-"<?php if($sitedata['filereplacer']=='-') echo ' selected="selected"' ?>>-</option><option value="_"<?php if($sitedata['filereplacer']=='_') echo ' selected="selected"' ?>>_</option><option value="."<?php if($sitedata['filereplacer']=='.') echo ' selected="selected"' ?>><?php echo returnIntLang('str remove', false); ?></option></select></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs parsedirectories', true); ?> <?php helpText(returnIntLang('editorprefs parsedirectories help', false)); ?></td>
					<td class="tablecell two"><input type="hidden" name="parsedirectories" value="0" /><input type="checkbox" name="parsedirectories" id="parsedirectories" <?php if(intval($sitedata['parsedirectories'])==1) echo "checked=\"checked\""; ?> value="1" />&nbsp;</td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs autopublish structure', true); ?> <?php helpText(returnIntLang('editorprefs autopublish structure help', false)); ?></td>
					<td class="tablecell two"><input type="hidden" name="autoparsestructure" value="0" /><input type="checkbox" name="autoparsestructure" id="autoparsestructure" <?php if(intval($sitedata['autoparsestructure'])==1) echo "checked=\"checked\""; ?> value="1" />&nbsp;</td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs autopublish content', true); ?> <?php helpText(returnIntLang('editorprefs autopublish content help', false)); ?></td>
					<td class="tablecell two"><input type="hidden" name="autoparsecontent" value="0" /><input type="checkbox" name="autoparsecontent" id="autoparsecontent" <?php if(intval($sitedata['autoparsecontent'])==1) echo "checked=\"checked\""; ?> value="1" />&nbsp;</td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs stripslashes', true); ?> <?php helpText(returnIntLang('editorprefs stripslashes help', false)); ?></td>
					<td class="tablecell two"><input name="stripslashes" type="text" class="one full" id="stripslashes" value="<?php echo intval($sitedata['stripslashes']); ?>" /></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs backup steps', true); ?></td>
					<td class="tablecell two"><input name="backupsteps" type="text" class="one full" id="backupsteps" value="<?php echo intval($sitedata['backupsteps']); ?>" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs no auto index', true); ?> <?php helpText(returnIntLang('editorprefs no auto index help', false)); ?></td>
					<td class="tablecell two"><input type="hidden" name="noautoindex" value="0" /><input type="checkbox" name="noautoindex" id="noautoindex" <?php if(intval($sitedata['noautoindex'])==1) echo "checked=\"checked\""; ?> value="1" />&nbsp;</td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs mask email@', true); ?></td>
					<td class="tablecell two"><input name="maskmail" type="text" class="one full" id="maskmail" value="<?php if (isset($sitedata['maskmail'])) echo ($sitedata['maskmail']); ?>" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs pref container', true); ?> </td>
					<td class="tablecell two"><select name="container" class="one full">
						<option value="4" <?php if(isset($sitedata['container']) && intval($sitedata['container'])==4) echo " selected=\"selected\" "; ?>><?php echo returnIntLang('str none'); ?></option>
						<option value="0" <?php if(isset($sitedata['container']) && intval($sitedata['container'])==0 || !(isset($sitedata['container']))) echo " selected=\"selected\" "; ?>>SECTION</option>
						<option value="1" <?php if(isset($sitedata['container']) && intval($sitedata['container'])==1) echo " selected=\"selected\" "; ?>>DIV</option>
						<option value="2" <?php if(isset($sitedata['container']) && intval($sitedata['container'])==2) echo " selected=\"selected\" "; ?>>SPAN</option>
						<option value="3" <?php if(isset($sitedata['container']) && intval($sitedata['container'])==3) echo " selected=\"selected\" "; ?>>LI</option>
					</select></td>
					<td class="tablecell two"></td>
					<td class="tablecell two"></td>
				</tr>
			</table>
			</div>
			<input type="hidden" class="one full" name="defaultpublish" id="defaultpublish" value="5">
		</fieldset>
		<?php if ($_SESSION['wspvars']['createthumbfromimage']=="checked"): ?>
		<fieldset id="fieldset_grafix" class="text">
		<legend><?php echo returnIntLang('editorprefs files'); ?> <?php echo legendOpenerCloser('pref-files'); ?></legend>
		<div id="pref-files">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs display mediafiles', true); ?></td>
					<td class="tablecell two"><select id="displaymedia" name="displaymedia" class="one full">
						<option value="list"><?php echo returnIntLang('editorprefs display mediafiles list', false); ?></option>
						<option value="box" <?php if(key_exists('displaymedia', $sitedata) && $sitedata['displaymedia']=='box') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs display mediafiles box', false); ?></option>
					</select></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs sort mediafiles', true); ?></td>
					<td class="tablecell two"><select id="medialistsort" name="medialistsort" class="one triple">
						<option value="name"><?php echo returnIntLang('editorprefs sort mediafiles name', false); ?></option>
						<option value="size" <?php if($sitedata['medialistsort']=='size') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs sort mediafiles size', false); ?></option>
						<option value="date" <?php if($sitedata['medialistsort']=='date') echo "selected=\"selected\""; ?>><?php echo returnIntLang('editorprefs sort mediafiles date', false); ?></option>
					</select></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs autoscale preselect', true); ?></td>
					<?php if($_SESSION['wspvars']['createimagefrompdf']=="checked"): ?>
						<td class="tablecell two"><input type="text" id="autoscalepreselect" name="autoscalepreselect" placeholder="1024 x 768" value="<?php if(isset($sitedata['autoscalepreselect'])): echo str_replace("x", " x ", str_replace(" ", "", $sitedata['autoscalepreselect'])); endif; ?>" style="width: 10em;" /> PX x PX</td>
						<td class="tablecell two"><?php echo returnIntLang('editorprefs converting pdf to image', true); ?></td>
						<td class="tablecell two"><input type="text" id="pdfscalepreview" name="pdfscalepreview" style="width: 10em;" placeholder="800x600" value="<?php if(isset($sitedata['pdfscalepreview'])): echo str_replace(" ", "", $sitedata['pdfscalepreview']); endif; ?>" />  PX x PX</td>
				<?php else: ?>
					<td class="tablecell six"><input type="text" id="autoscalepreselect" name="autoscalepreselect" placeholder="1024 x 768" value="<?php if(isset($sitedata['autoscalepreselect'])): echo str_replace("x", " x ", str_replace(" ", "", $sitedata['autoscalepreselect'])); endif; ?>" style="width: 10em;" /> PX x PX</td>
				<?php endif; ?>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs hold original', true); ?></td>
					<td class="tablecell two"><input type="hidden" name="holdoriginalimages" value="0" /><input type="checkbox" name="holdoriginalimages" id="holdoriginalimages" <?php if(isset($sitedata['holdoriginalimages']) && $sitedata['holdoriginalimages']==1) echo "checked=\"checked\""; ?> value="1" />&nbsp;</td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs thumbnail size', true); ?> <?php helpText(returnIntLang('editorprefs thumbnail size help', false)); ?></td>
					<td class="tablecell two"><input type="text" id="thumbsize" name="thumbsize" placeholder="200" value="<?php if(isset($sitedata['thumbsize'])): echo intval($sitedata['thumbsize']); endif; ?>" style="width: 10em;" /> PX</td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs overwrite files', true); ?></td>
					<td class="tablecell two"><input type="hidden" name="overwriteuploads" value="0" /><input type="checkbox" name="overwriteuploads" id="overwriteuploads" <?php if(isset($sitedata['overwriteuploads']) && $sitedata['overwriteuploads']==1) echo "checked=\"checked\""; ?> value="1" />&nbsp;</td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs strip filenames', true); ?></td>
					<td class="tablecell two"><input type="text" id="stripfilenames" name="stripfilenames" style="width: 10em;" placeholder="60" value="<?php if(isset($sitedata['stripfilenames'])): echo intval($sitedata['stripfilenames']); endif; ?>" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs hidden media folders', true); ?> <?php helpText(returnIntLang('editorprefs hidden media folders help', false)); ?></td>
					<td class="tablecell six"><input type="text" id="hiddenmedia" name="hiddenmedia" class="one full" placeholder="thumbs, preview, originals" value="<?php if(isset($sitedata['hiddenmedia'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['hiddenmedia'])); endif; ?>" /></td>
				</tr>
                <tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs hidden dropdown images folders', true); ?> <?php helpText(returnIntLang('editorprefs hidden dropdown images folders help', false)); ?></td>
					<td class="tablecell two"><input type="text" id="hiddenimages" name="hiddenimages" class="one full" placeholder="" value="<?php if(isset($sitedata['hiddenimages'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['hiddenimages'])); endif; ?>" /></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs hidden dropdown download folders', true); ?> <?php helpText(returnIntLang('editorprefs hidden dropdown download folders help', false)); ?></td>
					<td class="tablecell two"><input type="text" id="hiddendownload" name="hiddendownload" class="one full" placeholder="" value="<?php if(isset($sitedata['hiddendownload'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['hiddendownload'])); endif; ?>" /></td>
				</tr>
			</table>
		</div>
	</fieldset>
	<?php endif; ?>
	
	<input type="hidden" name="use_css" value="wsp">
	
	<script src="/wsp/data/script/jquery/jquery.autogrowtextarea.js"></script>
	<script>
	$(document).ready(function() {
		$(".growingarea").autoGrow();
		});
	</script>
	
	<fieldset id="fieldset_errorhandling">
		<legend><?php echo returnIntLang('editorprefs errorhandling and security', true); ?> <?php echo legendOpenerCloser('errorhandling'); ?></legend>
		<div id="errorhandling">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs autologout', true); ?></td>
					<td class="tablecell two"><input type="number" name="autologout" id="autologout" value="<?php echo intval($sitedata['autologout']); ?>" style="width: 5em;" /> <?php echo returnIntLang('str minutes', true); ?></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs loginfails', true); ?></td>
					<td class="tablecell two"><input type="number" name="loginfails" id="loginfails" value="<?php echo intval($sitedata['loginfails']); ?>" style="width: 5em;" /></td>
				</tr>
				<!--
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs login cookie runtime', true); ?></td>
					<td class="tablecell two"><input type="text" name="cookiedays" id="cookiedays" value="<?php echo intval($sitedata['cookiedays']); ?>" style="width: 3em;" />&nbsp;&nbsp;<?php echo returnIntLang('str days', true); ?></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs cookie based autologin', true); ?></td>
					<td class="tablecell two"><input type="hidden" name="cookielogin" value="0" /><input type="checkbox" name="cookielogin" id="cookielogin" <?php if($sitedata['cookielogin']==1) echo "checked=\"checked\""; ?> value="1" /></td>
				</tr>
				-->
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs forbidden filenames', true); ?> <?php helpText(returnIntLang('editorprefs forbidden filenames help', false)); ?></td>
					<td class="tablecell six"><textarea name="nonames" id="nonames" rows="6" class="six full growingarea"><?php
					if (isset($sitedata['nonames'])):
						$forbidden = explode(";", $sitedata['nonames']);
						foreach ($forbidden AS $value):
							echo trim($value)."\n";
						endforeach;
					endif;
					?></textarea></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs errormessages', true); ?></td>
					<td class="tablecell two"><input type="hidden" name="errorreporting" value="0" /><input type="checkbox" name="errorreporting" id="errorreporting" <?php if($sitedata['errorreporting']==1) echo "checked=\"checked\""; ?> value="1" /> <?php echo returnIntLang('editorprefs errormessages submit to developers', true); ?></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs mod install', true); ?></td>
					<td class="tablecell two"><input type="hidden" name="unsafemodinstall" value="0" /><input type="checkbox" name="unsafemodinstall" id="unsafemodinstall" <?php if($sitedata['unsafemodinstall']==1) echo "checked=\"checked\""; ?> value="1" /> <?php echo returnIntLang('editorprefs allow unsecure install', true); ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs disable wsp robots', true); ?></td>
					<td class="tablecell two"><input type="hidden" name="wsprobots" value="0" /><input type="checkbox" name="wsprobots" id="wsprobots" <?php if(isset($sitedata['wsprobots']) &&  $sitedata['wsprobots']==1) echo "checked=\"checked\""; ?> value="1" /></td>
					<td class="tablecell two"><?php echo returnIntLang('editorprefs disable directories', true); ?></td>
					<td class="tablecell two"><input type="text" id="disabledrobots" name="disabledrobots" class="one full" placeholder="data, media" value="<?php if(isset($sitedata['disabledrobots'])): echo str_replace(",", ", ", str_replace(" ", "", $sitedata['disabledrobots'])); endif; ?>" /></td>
				</tr>
			</table>
		</div>
		<input type="hidden" name="cookiedays" value="1" />
		<input type="hidden" name="cookielogin" value="0" />
	</fieldset>
	<fieldset class="options">
		<p><a href="#" onclick="document.getElementById('frmprefs').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a><input name="save_data" type="hidden" value="Speichern" /></p>
	</fieldset>
	</form>
</div>
<?php
require ("./data/include/footer.inc.php");
?>
<!-- EOF -->