<?php
/**
 * Startseite von WSP3
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 */

/* base includes ----------------------------- */
require ("./data/include/generichead.inc.php");

// logout from wsp ---------------------------
if (isset($_REQUEST['logout'])):
    setcookie ('wspautologin', 'off', time()-36000, '/'.$_SESSION['wspvars']['wspbasedir'].'/', (isset($_SESSION['wspvars']['siteurl'])?$_SESSION['wspvars']['siteurl']:$_SERVER['HTTP_HOST']), false);
	session_regenerate_id(FALSE);
	header('location: index.php');
	$_SESSION['wspvars']['cookielogin'] = 0;
	require ("./data/include/usestat.inc.php");
	require ("./data/include/globalvars.inc.php");
endif;

// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = ''; // beschreibt als string, ob hier eine rechte-sperre vorliegt
$_SESSION['wspvars']['menuposition'] = 'index'; // string mit der aktuellen position fuer backend-auswertung
$_SESSION['wspvars']['mgroup'] = 1; // aktive menuegruppe
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF']; // fuer den eintrag im logfile sowie die entsprechende ueberpruefung der fposcheck
$_SESSION['wspvars']['fposcheck'] = false; // bestimmt, ob ein bereich fuer andere benutzer gesperrt wird (true), wenn sich hier schon ein benutzer befindet, oder nicht (false)
$_SESSION['wspvars']['preventleave'] = false; // tells user, to save data before leaving page
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

// check for request password
if (isset($_GET['requestpass'])):
	$_SESSION['extendedlogin'] = false;
	$_SESSION['cookiecheck'] = 'pass';
else:
	$_SESSION['cookiecheck'] = '';
endif;
// enable extended login field with ftp-data
if (isset($_GET['extendedlogin'])):
	$_SESSION['extendedlogin'] = true;
	$_SESSION['cookiecheck'] = '';
endif;
// disable extended login field 
if (isset($_GET['disableextended'])):
	$_SESSION['extendedlogin'] = false;
	$_SESSION['cookiecheck'] = '';
endif;

// submit mail login request
if (isset($_POST['loginmail']) && trim($_POST['loginmail'])!="") {
	$pass_sql = "SELECT * FROM `restrictions` WHERE `realmail` = '".trim($_POST['loginmail'])."' AND `usertype` != '0'";
	$pass_res = doSQL($pass_sql);
	$_SESSION['cookiecheck'] = "pass";
	if ($pass_res['num']==1) {
		$newpass = strtoupper(substr(md5($_SERVER['REMOTE_ADDR'].date("Ybsmnds")),0,8));
		$mail_data = array();
		$mail_data['mailTo'][] = array($pass_res['set'][0]["realmail"], setUTF8($pass_res['set'][0]["realname"])); 
		$mail_data['mailCC'][] = array();
		$mail_data['mailBCC'][] = array();
		$mail_data['mailFrom'] = array("noreply@".((substr($_SERVER['HTTP_HOST'],0,3)=='www')?str_replace("www.", "", $_SERVER['HTTP_HOST']):$_SERVER['HTTP_HOST']),"WSP");
		$mail_data['mailReply'] = array("noreply@".((substr($_SERVER['HTTP_HOST'],0,3)=='www')?str_replace("www.", "", $_SERVER['HTTP_HOST']):$_SERVER['HTTP_HOST']),"WSP");
		$mail_data['mailReturnPath'] = "noreply@".((substr($_SERVER['HTTP_HOST'],0,3)=='www')?str_replace("www.", "", $_SERVER['HTTP_HOST']):$_SERVER['HTTP_HOST']);
		$mail_data['mailHTML'] = returnIntLang('requestpass new pass for url', false).": ".$_SESSION['wspvars']['wspurl']."/".$_SESSION['wspvars']['wspbasedir']."/<br />".returnIntLang('requestpass new pass username str', false).": ".$pass_res['set'][0]['user']."<br />".returnIntLang('requestpass new pass password str', false).": ".$newpass."<br /><br />".returnIntLang('requestpass new pass requested date', false)." ".date("Y-m-d H:i:s")." ".returnIntLang('requestpass new pass requested ip', false)." ".$_SERVER['REMOTE_ADDR'];
		$mail_data['mailTXT'] = returnIntLang('requestpass new pass for url', false).": ".$_SESSION['wspvars']['wspurl']."/".$_SESSION['wspvars']['wspbasedir']."/\n".returnIntLang('requestpass new pass username str', false).": ".$pass_res['set'][0]['user']."\n".returnIntLang('requestpass new pass password str', false).": ".$newpass."\n\n".returnIntLang('requestpass new pass requested date', false)." ".date("Y-m-d H:i:s")." ".returnIntLang('requestpass new pass requested ip', false)." ".$_SERVER['REMOTE_ADDR'];
		$mail_data['mailSubject'] = setUTF8(returnIntLang('requestpass new pass', false)." »".$pass_res['set'][0]["user"]."«");
		$mail_data['useHTML'] = 1;
		$updpass = doSQL("UPDATE `restrictions` SET `pass` = '".md5($newpass)."' WHERE `rid` = ".intval($pass_res['set'][0]['rid']));
        if ($updpass['aff']==1) {
			if (checkandsendMail($mail_data)) {
                addWSPMsg('noticemsg', returnIntLang('logindata sent to mail', false));
            } else {
                addWSPMsg('errormsg', returnIntLang('logindata could not be sent', false));
            }
			$_SESSION['cookiecheck'] = "requested";
		}
	}
}

// DO login
if (isset($_POST['loginfield']) && $_POST['loginfield']=="go") {
	// set login failures on first login submit
	if (!(isset($_SESSION['wspvars']['fails']))) { $_SESSION['wspvars']['fails'] = 0; }

	$login_sql = "SELECT * FROM `restrictions` WHERE `user` = '".escapeSQL($_POST['loginuser'])."' AND `pass` = '".md5($_POST['loginpassword'])."' AND `usertype` != '0'";
	$login_res = doSQL($login_sql);

	if ($login_res['num'] === 1) {
		$_SESSION['wspvars']['usevar'] = md5($login_res['set'][0]['rid'].$_SERVER['REMOTE_ADDR'].time().$_SERVER['HTTP_USER_AGENT']);
		$sql = "INSERT INTO `security` SET 
			`referrer` = '".substr($_SERVER['REMOTE_ADDR'],0,12)."',
			`userid` = '".intval($login_res['set'][0]['rid'])."',
			`timevar` = '".time()."',
			`usevar` = '" . escapeSQL($_SESSION['wspvars']['usevar']) . "',
			`logintime` = '".time()."',
			`position` = 'loginattempt',
			`useragent` = '".$_SERVER['HTTP_USER_AGENT']."'"; //'
		$log = doSQL($sql);
        $ftp = false; $ftpt = 0;
		if (!(isset($_SESSION['wspvars']['ftpusage'])) || (isset($_SESSION['wspvars']['ftpusage']) && $_SESSION['wspvars']['ftpusage']!==false)) {
			while ($ftp===false && $ftpt<3) {
				$ftp = doFTP();
				$ftpt++;
			}
		}
		if ($ftp) {
            mkdir($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/tmp/'.$_SESSION['wspvars']['usevar'], 0777);
			chmod($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/tmp/'.$_SESSION['wspvars']['usevar'], 0777);
			copySkel($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/media/skel', $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/tmp/'.$_SESSION['wspvars']['usevar']);
			if (intval($login_res['set'][0]['usertype'])==1):
				for ($r=0;$r<count($_SESSION['wspvars']['rightabilities']);$r++):
					$changerights[$_SESSION['wspvars']['rightabilities'][$r]] = 1;
				endfor;
				$sql = "UPDATE `restrictions` SET rights = '".serialize($changerights)."', idrights = '' WHERE `rid` = ".intval($login_res['set'][0]['rid']);
				doSQL($sql);
			endif;
			
			$sql = "INSERT INTO `securitylog` SET `uid` = ".intval($login_res['set'][0]['rid']).", `lastposition` = '/".$_SESSION['wspvars']['wspbasedir']."/index.php', `lastaction` = 'login', `lastchange` = '".time()."'";
			doSQL($sql);
            
            unset($_SESSION['pathmid']);
			
			$_SESSION['wspvars']['createpdf'] = "nocheck";
            $createpdf_last = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'createpdf'");
			if (intval($createpdf_last)>=(time()-2592000)) {
                $_SESSION['wspvars']['createpdf'] = "checked";
            }
			
			$_SESSION['wspvars']['createimagefrompdf'] = "nocheck";
			$createimagefrompdf_last = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'createimagefrompdf'");
			if (intval($createimagefrompdf_last)>=(time()-2592000)) {
                $_SESSION['wspvars']['createimagefrompdf'] = "checked";
            }
			
			$_SESSION['wspvars']['createimage'] = "nocheck";
			$createimage_last = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'createimage'");
			if (intval($createimage_last)>=(time()-2592000)) {
                $_SESSION['wspvars']['createimage'] = "checked";
            }
			
			$_SESSION['wspvars']['createthumbfromimage'] = "nocheck";
			$createthumbfromimage_last = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'createthumbfromimage'");
			if (intval($createthumbfromimage_last)>=(time()-2592000)) {
                $_SESSION['wspvars']['createthumbfromimage'] = "checked";
			}
			
			// check for cookie login and set cookie
			setcookie ('wspautologin', md5($login_res['set'][0]['user'].$login_res['set'][0]['pass']), time()+60*60*24*intval($_SESSION['wspvars']['cookiedays']) , '/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/', $_SESSION['wspvars']['workspaceurl'], false);
			$loginindex = true;
			// do checkuser
			ftp_close($ftp);
			$_SESSION['wspvars']['ftpcon'] = true;
        
		} else {

			$sql = "INSERT INTO `securitylog` SET `uid` = ".intval($login_res['set'][0]['rid']).", `lastposition` = '/".$_SESSION['wspvars']['wspbasedir']."/index.php', `lastaction` = 'login', `lastchange` = '".time()."'";
			$log = doSQL($sql);
			$_SESSION['wspvars']['directwriting'] = false;
			@mkdir($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/tmp/', 0777);
			$mdstat = @mkdir($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/tmp/'.$_SESSION['wspvars']['usevar'], 0777);
			if ($mdstat===true) {
				$mfstat = fopen($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/tmp/'.$_SESSION['wspvars']['usevar'].'/test.txt', 'w');
				if ($mfstat!==false) {
					$_SESSION['wspvars']['directwriting'] = true;
					fclose($mfstat);
				}
			}
			
			if (isset($_POST['ignoreftp']) && $_POST['ignoreftp']=='1') {
                addWSPMsg('noticemsg', returnIntLang('try to handle login without ftp', false));
                $loginindex = true;
			} 
			else if ($_SESSION['wspvars']['directwriting']===true) {
				if (file_exists("./data/include/ftpaccess.inc.php")) {
					addWSPMsg('noticemsg', returnIntLang('use wsp with direct writing', false));
				}			
				$_SESSION['wspvars']['ftpcon'] = false;
				$loginindex = true;
			}
			else {
                addWSPMsg('errormsg', returnIntLang('wsp could not connect to filesystem', false));
                $loginindex = false;
                $_SESSION['wspvars']['usevar'] = false;
                doSQL("DELETE FROM `security` WHERE `userid` = '".intval($login_res['set'][0]['rid'])."'");
            }
		}
		if (isset($loginindex)) {
            require ("./data/include/checkuser.inc.php");
		}
	}
	else {
		$_SESSION['wspvars']['fails']++;
		if ($_SESSION['wspvars']['fails']>=4) {

			doSQL("UPDATE `restrictions` SET `usertype` = 0 WHERE `usertype` != 1 AND `user` = '".escapeSQL($_POST['loginuser'])."'");

            addWSPMsg('errormsg', returnIntLang('logindata failure username set inactive', false));
			$noticemail = "login host: ".$_SERVER['HTTP_HOST']."\n";
			$noticemail.= "login user: ".$_POST['loginuser']."\n";
			$noticemail.= "login time: ".date("Y-m-d H:i:s")."\n";
			$noticemail.= "login addr: ".$_SERVER['REMOTE_ADDR'];
			$subject = "user probably blocked at ".$_SERVER['HTTP_HOST'];
			$mail_data = array();
			$mail_data['mailCC'][] = array();
			$mail_data['mailBCC'][] = array();
			$mail_data['mailFrom'] = array("noreply@".$_SERVER['HTTP_HOST'],"WSP");
			$mail_data['mailReply'] = array("cms@covi.de","WSP");
			$mail_data['mailReturnPath'] = "";
			$mail_data['mailHTML'] = $noticemail;
			$mail_data['mailTXT'] = $noticemail;;
			$mail_data['mailSubject'] = $subject;
			$mail_data['useHTML'] = 0;
			$admin_sql = "SELECT `rid`, `realname`, `realmail` FROM `restrictions` WHERE `usertype` = 1";
			$admin_res = doSQL($admin_sql);
			if ($admin_res['num']>0) {
				foreach ($admin_res['set'] AS $ark => $arv) {
					$mail = setUTF8($arv["realname"])." <".$arv["realmail"].">";
					$mail_data['mailTo'][] = array($arv["realmail"],setUTF8($arv["realname"]));
					checkandsendMail($mail_data);
				}
			}
			else {
				$mail_data['mailTo'][] = array('cmsabuse@covi.de',""); 
				checkandsendMail($mail_data);
			}
        } else {
            addWSPMsg('errormsg', returnIntLang('logindata failure username or password', false));
		}
		
	}

}

// check for login stat
if ((int) ($_SESSION['wspvars']['actusersid'] ?? 0) > 0
    && (int) ($_SESSION['wspvars']['userid'] ?? 0) > 0
    && array_key_exists('usevar', $_SESSION['wspvars'] ?? [])) {
    $loginindex = true;
}

/* include head ------------------------------ */
require ("./data/include/header.inc.php");

if (array_key_exists('rights', $_SESSION['wspvars'] ?? []) && $loginindex===true) {
	// include menu if logged in 
	require ("./data/include/wspmenu.inc.php");
}

if (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && $loginindex===true): ?>
	<div id="contentholder">
		<div class="breadcrumb"><h1><?php echo returnIntLang('home dashboard'); ?></h1></div>
		<fieldset class="text full"><p><?php echo returnIntLang('home info'); ?> <strong><?php echo $_SESSION['wspvars']['workspaceurl']; ?></strong>.</p>
		<p><?php echo returnIntLang('home rights'); ?></p>
		<p><?php echo returnIntLang('home faqhint'); ?></p>
		<p><?php echo returnIntLang('home logouthint'); ?></p></fieldset>
        <?php 

		if (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('contents', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['contents']!=0): ?>
		<fieldset class="text two first">
			<legend><?php echo returnIntLang('home edited'); ?> <?php echo legendOpenerCloser('index_lasteditinfo_fieldset'); ?></legend>
			<div id="index_lasteditinfo_fieldset">
			<?php
			
			$lastedit_sql = "SELECT `cid`, `mid`, `lastchange` FROM `content` WHERE `lastchange` != 0 GROUP BY `mid`, `cid` ORDER BY `lastchange` DESC  LIMIT 0, 10"; 
			$lastedit_res = doSQL($lastedit_sql);
			
			if ($lastedit_res['num']>0) {
				foreach ($lastedit_res['set'] AS $lerk => $lerv) {
					$menuinfo_sql = "SELECT `description` FROM `menu` WHERE `mid` = ".intval($lerv['mid']);
					$menuinfo_res = doResultSQL($menuinfo_sql);
					if ($menuinfo_res) {
						echo "<p><em>".setUTF8($menuinfo_res)."</em> - ".returnIntLang('home edited text')." <em>";
						echo date(returnIntLang("format date time", false), intval($lerv['lastchange']));
						echo "</em></p>";
					}
				}
			}
			
			?>
			</div>
		</fieldset>
		<?php endif; ?>
		<?php if (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('publisher', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['publisher']!=0): ?>
		<fieldset class="text two second">
			<legend><?php echo returnIntLang('home published'); ?> <?php echo legendOpenerCloser('index_lastpublishinfo_fieldset'); ?></legend>
			<div id="index_lastpublishinfo_fieldset">
			<?php
			
			$menuinfo_sql = "SELECT `description`, `changetime` FROM `menu` WHERE `contentchanged` != 1 ORDER BY `changetime` DESC LIMIT 0, 10";
			$menuinfo_res = doSQL($menuinfo_sql);
			
			if ($menuinfo_res['num']>0) {
				foreach ($menuinfo_res['set'] AS $mirk => $mirv) {
					echo "<p><em>".$mirv['description']."</em>";
					if (intval($mirv['changetime'])>0) {
						echo " - ".returnIntLang('home published text')." <em>".date(returnIntLang("format date time", false), intval($mirv['changetime']))."</em></p>";
                    }
                }
            }
			
			?>
			</div>
		</fieldset>
		<?php endif; ?>
		<hr class="clearbreak" />
	<?php else: ?>
		<div id="logincontent">
			<?php 
			
			if(isset($_GET['logout'])) {
				unset($_COOKIE['wspautologin']);
			}

            ?><div id="logo">
                <div class="icon">
                    <div class="outline">
                        <i class="fa-solid fa-eye fa-xs fa-canvas-roomy"></i>
                    </div>
                </div>
                <div class="text">
                    <span class="w">W</span>
                    <span class="s">S</span>
                    <span class="p">P</span>
                    <span class="seven">7</span>
                </div>
            </div><?php

			(new wspBase())->showWSPMessages(echo: true);
		
			if (array_key_exists('cookiecheck', $_SESSION) && $_SESSION['cookiecheck']=="requested"): 
				echo returnIntLang('login please log in')." ";
				echo returnIntLang('login pass requested', true); 
				echo " <a href=\"index.php\">".returnIntLang('login return to login', true)."</a>";
			elseif (array_key_exists('cookiecheck', $_SESSION) && $_SESSION['cookiecheck']=="pass"): 
				echo returnIntLang('login request pass emailinfo', true); 
				?>
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="passrequest" name="passrequest">
				<fieldset>
					<legend><?php echo returnIntLang('login request pass', true); ?></legend>
					<table class="tablelist">
						<tr>
							<td class="tablecell four"><?php echo returnIntLang('str email', true); ?></td>
							<td class="tablecell four"><input name="loginmail" id="loginmail" type="text" value="<?php if (isset($_POST['loginmail'])) echo $_POST['loginmail']; ?>" class="one full" /></td>
						</tr>
					</table>
				</fieldset>
				<fieldset class="options">
					<p><input name="requestfield" type="hidden" value="go"><a href="#" onclick="document.getElementById('passrequest').submit();" class="greenfield"><?php echo returnIntLang('str request', true); ?></a> <a href="index.php" class="orangefield"><?php echo returnIntLang('str cancel', true); ?></a></p>
				</fieldset>
				</form>
			<?php else: 
				
				if (array_key_exists('extendedlogin', $_SESSION) && $_SESSION['extendedlogin']): 
					echo '<p>' . returnIntLang('extended login info', true) . '</p>'; 
				endif; ?>
				<script>
				
				document.write("<form action=\"<?php echo $_SERVER['PHP_SELF']; ?>\" method=\"POST\" id=\"wsplogin\" name=\"wsplogin\">");
				
				</script>
				<div class="card">
					<div class="card-header">
						<?php echo returnIntLang('str login', true); ?>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col">
								<label class="d-inline">
									<?php echo returnIntLang('str username', true); ?>
									<input name="loginuser" id="loginuser" type="text" value="" autofill="false" class="form-control" />
								</label>
							</div>
						</div>
						<div class="row">
							<div class="col">
								<label class="d-inline">
									<?php echo returnIntLang('str password', true); ?>
									<input name="loginpassword" id="loginpassword" type="password" class="form-control" />
								</label>
							</div>
						</div>
						<?php if (key_exists('extendedlogin', $_SESSION) && $_SESSION['extendedlogin']): ?>
							<div class="row">
								<div class="col-6">
									<label class="d-inline">
										<?php echo returnIntLang('editcon ftp host', true); ?>
										<input name="ftpserver" id="ftpserver" type="text" value="" class="form-control" />
									</label>
								</div>
								<div class="col-6">
									<label class="d-inline">
										<?php echo returnIntLang('editcon ftp basedir', true); ?>
										<input name="ftpbasedir" id="ftpbasedir" type="text" value="" class="form-control" />
									</label>
								</div>
							</div>
							<div class="row">
								<div class="col-6">
									<label class="d-inline">
										<?php echo returnIntLang('editcon ftp username', true); ?>
										<input name="ftpuser" id="ftpuser" type="text" value="" class="form-control" />
									</label>
								</div>
								<div class="col-6">
									<label class="d-inline">
										<?php echo returnIntLang('editcon ftp password', true); ?>
										<input name="ftppass" id="ftppass" type="password" value="" class="form-control" />
									</label>
								</div>
							</div>
						<?php elseif (file_exists("./data/include/ftpaccess.inc.php")): ?>
							<tr>
								<td class="tablecell four"><?php echo returnIntLang('login ignore ftp access', true); ?></td>
								<td class="tablecell four"><input type="checkbox" name="ignoreftp" id="ignoreftp" value="1" /></td>
							</tr>
						<?php endif; ?>
					</div>
					<div class="card-footer text-center">
						<script language="JavaScript" type="text/javascript">
						<!--
						
						document.write("<input name='loginfield' type='hidden' value='go'>");
						document.write("<input type='submit' style='visibility:hidden;width:1px;height:1px;-line-height:1px;font-size:1px;' />");
						document.write("<a role='button' onclick=\"$('#wsplogin').submit();\" class='btn btn-primary'><?php echo returnIntLang('btn login', false); ?></a>");
						document.write('</form>');
						
						// -->
						</script>
					</div>
				</div>
				
				<fieldset><p><?php echo returnIntLang('login request lostpass', true); ?> <a href="?requestpass"><?php echo returnIntLang('login click here', true); ?></a> <?php echo returnIntLang('login request extendedlogin', true); ?> <?php if (key_exists('extendedlogin', $_SESSION) && $_SESSION['extendedlogin']): ?><a href="?disableextended"><?php echo returnIntLang('extended login disable here', true); ?></a><?php else: ?><a href="?extendedlogin"><?php echo returnIntLang('extended login click here', true); ?></a><?php endif; ?></p></fieldset>

			<?php endif; ?>


		</div>
	<?php endif; ?>
</div>
<?php 

include_once ("data/include/footer.inc.php");