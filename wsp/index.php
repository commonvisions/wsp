<?php
/**
 * Startseite von WSP3
 * @author s.haendler@covi.de
 * @copyright (c) 2022, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.11
 * @lastchange 2022-10-25
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

// logout from wsp ---------------------------
if (isset($_REQUEST['logout'])):
    setcookie ('wspautologin', 'off', time()-36000, '/'.$_SESSION['wspvars']['wspbasedir'].'/', (isset($_SESSION['wspvars']['siteurl'])?$_SESSION['wspvars']['siteurl']:$_SERVER['HTTP_HOST']), false);
//	session_destroy();
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
	
	if ($login_res['num']==1) {
		$_SESSION['wspvars']['usevar'] = md5($login_res['set'][0]['rid'].$_SERVER['REMOTE_ADDR'].time().$_SERVER['HTTP_USER_AGENT']);
		$sql = "INSERT INTO `security` SET 
			`referrer` = '".substr($_SERVER['REMOTE_ADDR'],0,12)."',
			`userid` = '".intval($login_res['set'][0]['rid'])."',
			`timevar` = '".time()."',
			`usevar` = '".$_SESSION['wspvars']['usevar']."',
			`logintime` = '".time()."',
			`position` = 'loginattempt',
			`useragent` = '".$_SERVER['HTTP_USER_AGENT']."'"; //'
		$log = doSQL($sql);
        $ftp = false; $ftpt = 0;
		if (!(isset($_SESSION['wspvars']['ftpusage'])) || (isset($_SESSION['wspvars']['ftpusage']) && $_SESSION['wspvars']['ftpusage']!==false)) {
			while ($ftp===false && $ftpt<3) {
				$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']), 5):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']), 5));
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

		if ($ftp!==false) {

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
			doSQL($sql);

			$_SESSION['wspvars']['directwriting'] = false;
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
				addWSPMsg('noticemsg', returnIntLang('use wsp with direct writing', false));
				$_SESSION['wspvars']['ftpcon'] = false;
				$loginindex = true;
			}
			else {
                addWSPMsg('errormsg', returnIntLang('login without ftp1', false).$ftpt.returnIntLang('login without ftp2', false));
                $loginindex = false;
                $_SESSION['wspvars']['usevar'] = false;
                doSQL("DELETE FROM `security` WHERE `userid` = '".intval($login_res['set'][0]['rid'])."'");
            }
		}
		require ("./data/include/checkuser.inc.php");
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
if (array_key_exists('actusersid', $_SESSION['wspvars']) && intval($_SESSION['wspvars']['actusersid'])>0 && array_key_exists('userid', $_SESSION['wspvars']) && intval($_SESSION['wspvars']['userid'])>0 && array_key_exists('usevar', $_SESSION['wspvars'])) {
	$loginindex = true;
}

// check for system update
if (_isCurl()) {
    $lastupdate_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'lastupdate'";
    $lastupdate_res = doResultSQL($lastupdate_sql);
    if ($lastupdate_res!==false) {
        $_SESSION['wspvars']['updatesystem'] = false;
        $defaults = array( 
            CURLOPT_URL => trim($_SESSION['wspvars']['updateuri']."/updatenote.txt?sys=".$_SESSION['wspvars']['wspversion']), 
            CURLOPT_HEADER => 0, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_TIMEOUT => 4 
        );
        $ch = curl_init();
        curl_setopt_array($ch, $defaults);    
        if( ! $fileupdate = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
        curl_close($ch);
        if ($fileupdate>intval($lastupdate_res)) {
            $_SESSION['wspvars']['updatesystem'] = true;
        }
    }
    else {
        $sql = "INSERT INTO `wspproperties` SET `varname` = 'lastupdate', `varvalue` = '".time()."'";
        doSQL($sql);
    }
} else {
    addWSPMsg('errormsg', 'CURL not supported.');
}

/* include head ------------------------------ */
require ("./data/include/header.inc.php");

if (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && $loginindex===true) {
	// include menu if logged in 
	require ("./data/include/wspmenu.inc.php");
} else {
	// include some funcs to display messages, if not logged in ;)
	require ("./data/include/msgheader.inc.php");
}

if(array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && $loginindex===true): ?>
	<div id="contentholder">
		<fieldset><h1><?php echo returnIntLang('home welcome'); ?></h1></fieldset>
		<?php if ($_SESSION['wspvars']['usertype']==1):
			$lastupdate_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'lastversion'";
			$sysrun = doResultSQL($lastupdate_sql);
			$runnum = 10;
            while ($sysrun!='finished' && $runnum>0) {
                include("./data/include/sysrun.inc.php");
                $runnum--;
            }
		endif; ?>
		<fieldset class="text full"><p><?php echo returnIntLang('home info'); ?> <strong><?php echo $_SESSION['wspvars']['workspaceurl']; ?></strong>.</p>
		<p><?php echo returnIntLang('home rights'); ?></p>
		<p><?php echo returnIntLang('home faqhint'); ?></p>
		<p><?php echo returnIntLang('home logouthint'); ?></p></fieldset>
        <?php if (_isCurl()) {
            $wspinfo = array( 
                CURLOPT_URL => trim($_SESSION['wspvars']['updateuri']."/wspinfo.php?sys=".$_SESSION['wspvars']['wspversion']."&lang=".$_SESSION['wspvars']['locallang']), 
                CURLOPT_HEADER => 0, 
                CURLOPT_RETURNTRANSFER => TRUE, 
                CURLOPT_TIMEOUT => 4 
            );
            $ch = curl_init();
            curl_setopt_array($ch, $wspinfo);
            if( ! $getinfo = curl_exec($ch)) {} 
            curl_close($ch);
            if (trim($getinfo)!='') { echo $getinfo; }
        }

		$plugin_sql = "SELECT * FROM `wspplugins`";
		$plugin_res = doSQL($plugin_sql);
		if ($plugin_res['num']>0) {
			foreach ($plugin_res['set'] AS $prk => $prv) {
				$pluginident = $prv["guid"];
				if (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists($pluginident, $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights'][$pluginident]==1) {
					$pluginfolder = $prv["pluginfolder"];
					if (is_file(DOCUMENT_ROOT."/".$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir']."/plugins/".$pluginfolder."/cmindex.php")) {
						include (DOCUMENT_ROOT."/".$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir']."/plugins/".$pluginfolder."/cmindex.php");
					}
				}
			}
		}
		
		if (array_key_exists('wspvars', $_SESSION) && array_key_exists('rights', $_SESSION['wspvars']) && array_key_exists('contents', $_SESSION['wspvars']['rights']) && $_SESSION['wspvars']['rights']['contents']!=0): ?>
		<fieldset class="text two first">
			<legend><?php echo returnIntLang('home edited'); ?> <?php echo legendOpenerCloser('index_lasteditinfo_fieldset'); ?></legend>
			<div id="index_lasteditinfo_fieldset">
			<?php
			
			$lastedit_sql = "SELECT `cid`, `mid`, `lastchange` FROM `content` WHERE `lastchange` != 0 GROUP BY `mid` ORDER BY `lastchange` DESC  LIMIT 0, 10"; 
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
		<?php if (array_key_exists('wspvars', $_SESSION) && array_key_exists('usertype', $_SESSION['wspvars']) && $_SESSION['wspvars']['usertype']==1): ?>
		<fieldset class="text two third">
			<legend><?php echo returnIntLang('home logins'); ?> <?php echo legendOpenerCloser('index_lastuserinfo_fieldset'); ?></legend>
			<div id="index_lastuserinfo_fieldset">
			<?php
			
			$loginfo_sql = "SELECT * FROM `securitylog` WHERE `lastaction` = 'login' ORDER BY `lastchange` DESC"; 
			$loginfo_res = doSQL($loginfo_sql);
			
			if ($loginfo_res['num']>0) {
				$trackuid = array();
				foreach ($loginfo_res['set'] AS $lirk => $lirv) {
					if (!(in_array(intval($lirv['uid']), $trackuid)) && count($trackuid)<5) {
						$trackuid[] = intval($lirv['uid']);
						$userinfo_sql = "SELECT `realname` FROM `restrictions` WHERE `rid` = ".intval($lirv['uid']);
						$userinfo_res = doResultSQL($userinfo_sql);
						if (trim($userinfo_res)!='') {
							echo "<p><em>".setUTF8($userinfo_res)."</em> - ".returnIntLang('home logins text')." <em>".date(returnIntLang("format date time", false), intval($lirv['lastchange']))."</em></p>";
                        }
                    }
                }
            }
                
			?>
			</div>
		</fieldset>
		<?php endif; ?>
		<hr class="clearbreak" />
	<?php else: ?>
		<div id="topspacer">&nbsp;</div>
		<div id="logincontent">
			<?php 
			
			if(isset($_GET['logout'])): 
				unset($_COOKIE['wspautologin']);
			endif; 
			?>
			<fieldset id="noticemsg" style="display: none;"></fieldset>
			<fieldset id="errormsg" style="display: none;"></fieldset>
			<fieldset id="resultmsg" style="display: none;"></fieldset>
			<fieldset><h1><?php echo returnIntLang('home welcome'); ?></h1></fieldset>
			<fieldset><p><?php 
			
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
				echo returnIntLang('login please activate cookies', true); 
				echo "<noscript> ";
				echo returnIntLang('login please activate jscript1', true); 
				echo "</noscript> ";
				echo returnIntLang('login please activate', true); 
				if (array_key_exists('extendedlogin', $_SESSION) && $_SESSION['extendedlogin']): 
					echo returnIntLang('extended login info', true); 
				endif; ?></p>
			</fieldset>
			<script language="JavaScript" type="text/javascript">
			<!--
			
			document.write("<form action=\"<?php echo $_SERVER['PHP_SELF']; ?>\" method=\"POST\" id=\"wsplogin\" name=\"wsplogin\">");
			
			// -->
			</script>
				<fieldset>
					<legend><?php echo returnIntLang('str login', true); ?></legend>
					<table class="tablelist">
						<tr>
							<td class="tablecell four"><?php echo returnIntLang('str username', true); ?></td>
							<td class="tablecell four"><input name="loginuser" id="loginuser" type="text" value="<?php if (isset($_POST['loginuser'])) echo $_POST['loginuser']; ?>" class="one full" /></td>
						</tr>
						<tr>
							<td class="tablecell four"><?php echo returnIntLang('str password', true); ?></td>
							<td class="tablecell four"><input name="loginpassword" id="loginpassword" type="password" class="one full" /></td>
						</tr>
						<?php if (key_exists('extendedlogin', $_SESSION) && $_SESSION['extendedlogin']): ?>
							<tr>
								<td class="tablecell four"><?php echo returnIntLang('editcon ftp host', true); ?></td>
								<td class="tablecell four"><input name="ftpserver" id="ftpserver" type="text" value="<?php if (isset($_POST['ftpserver'])) echo $_POST['ftpserver']; ?>" class="one full" /></td>
							</tr>
							<tr>
								<td class="tablecell four"><?php echo returnIntLang('editcon ftp basedir', true); ?></td>
								<td class="tablecell four"><input name="ftpbasedir" id="ftpbasedir" type="text" value="<?php if (isset($_POST['ftpbasedir'])) echo $_POST['ftpbasedir']; ?>" class="one full" /></td>
							</tr>
							<tr>
								<td class="tablecell four"><?php echo returnIntLang('editcon ftp username', true); ?></td>
								<td class="tablecell four"><input name="ftpuser" id="ftpuser" type="text" value="<?php if (isset($_POST['ftpuser'])) echo $_POST['ftpuser']; ?>" class="one full" /></td>
							</tr>
							<tr>
								<td class="tablecell four"><?php echo returnIntLang('editcon ftp password', true); ?></td>
								<td class="tablecell four"><input name="ftppass" id="ftppass" type="password" class="one full" /></td>
							</tr>
						<?php else: ?>
						<tr>
							<td class="tablecell four"><?php echo returnIntLang('login ignore ftp access', true); ?></td>
							<td class="tablecell four"><input type="checkbox" name="ignoreftp" id="ignoreftp" value="1" /></td>
						</tr>
						<?php endif; ?>
					</table>
				</fieldset>
				<script language="JavaScript" type="text/javascript">
				<!--
				
				document.write("<fieldset class=\"options\">");
				document.write("<p><input name=\"loginfield\" type=\"hidden\" value=\"go\"><a href=\"javascript: document.getElementById('wsplogin').submit();\" class=\"greenfield\"><?php echo returnIntLang('btn login', false); ?></a><input type=\"submit\" style=\"visibility:hidden;width:1px;height:1px;-line-height:1px;font-size:1px;\" /></p>");
				document.write('</fieldset>');
				document.write('</form>');
				
				// -->
				</script>
				<fieldset><p><?php echo returnIntLang('login request lostpass', true); ?> <a href="?requestpass"><?php echo returnIntLang('login click here', true); ?></a> <?php echo returnIntLang('login request extendedlogin', true); ?> <?php if (key_exists('extendedlogin', $_SESSION) && $_SESSION['extendedlogin']): ?><a href="?disableextended"><?php echo returnIntLang('extended login disable here', true); ?></a><?php else: ?><a href="?extendedlogin"><?php echo returnIntLang('extended login click here', true); ?></a><?php endif; ?></p></fieldset>

			<?php endif; ?>


		</div>
	<?php endif; ?>
</div>
<?php include ("data/include/footer.inc.php"); ?>
<!-- EOF -->