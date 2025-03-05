<?php
/**
 * WSP3 login page
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2021-09-16
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
include("./data/include/usestat.inc.php");
include("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['menuposition'] = 'login';
$_SESSION['wspvars']['mgroup'] = 1;
/* second includes --------------------------- */
include("./data/include/errorhandler.inc.php");
include("./data/include/siteinfo.inc.php");
// define page specific vars -----------------

// cleanup older temporary folders
$tmpfolder = scandirs(".".DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR);
if (is_array($tmpfolder)) {
    $tf = 0;
    foreach ($tmpfolder AS $tfk => $tfv) {
        $fdata = cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$tfv);
        $fstat = stat($fdata);
        if ($tf<5 && is_dir($fdata) && substr($tfv,0,1)!='.') {
            if ($fstat['mtime']<(time()+(86400*3))) {
                deleteFolder(cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$tfv), false);
                $tf++;
            }
        }
    }
}

// handle logout
if (isset($_REQUEST['logout'])):
    if (isset($_SESSION['wspvars']['usevar']) && trim($_SESSION['wspvars']['usevar'])!=''):
        // remove temporary data
        deleteFolder(cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$_SESSION['wspvars']['usevar'].DIRECTORY_SEPARATOR), false);
        // make logout from database
        $sql = "DELETE FROM `security` WHERE `usevar` = '".escapeSQL($_SESSION['wspvars']['usevar'])."'";
        doSQL($sql);
    endif;
    unset($_SESSION['wspvars']['lockedvar']);
    // destroy session and redirect
    session_regenerate_id(FALSE);
    session_destroy();
    session_start();
endif;

if (isset($_SESSION['wspvars']['lockedvar'])):
    // remove temporary data
    deleteFolder(cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$_SESSION['wspvars']['lockedvar']));
    // make logout from database
    $sql = "DELETE FROM `security` WHERE `usevar` = '".escapeSQL($_SESSION['wspvars']['lockedvar'])."'";
    doSQL($sql);
    session_regenerate_id(FALSE);
    session_destroy();
    session_start();
endif;

// enable extended login field with ftp-data
if (isset($_REQUEST['extendedlogin']) && (!(defined('FTP_USAGE')) || (defined('FTP_USAGE') && FTP_USAGE!==false))) {
    $_SESSION['wspvars']['showlogin'] = 'extended';
}
// check for request password
else if (isset($_REQUEST['requestpass'])) {
	$_SESSION['wspvars']['showlogin'] = 'pass';
}
else {
	$_SESSION['wspvars']['showlogin'] = true;
}

// submit mail login request
if (isset($_POST['loginmail']) && trim($_POST['loginmail'])!="") {
	$pass_sql = "SELECT * FROM `restrictions` WHERE `realmail` = '".escapeSQL(trim($_POST['loginmail']))."' AND `usertype` != '0'";
	$pass_res = doSQL($pass_sql);
	if ($pass_res['num']==1) {
		$newpass = strtoupper(substr(md5($_SERVER['REMOTE_ADDR'].date("Ybsmnds").rand(0,10000000)),0,12));
		$mail_data = array();
        $mail_data['mailTo'][] = array(trim($pass_res['set'][0]['realmail']), setUTF8(trim($pass_res['set'][0]['realname']))); 
		$mail_data['mailCC'][] = array();
		$mail_data['mailBCC'][] = array();
		$mail_data['mailFrom'] = array("noreply@".((substr($_SERVER['HTTP_HOST'],0,4)!='www.')?$_SERVER['HTTP_HOST']:substr($_SERVER['HTTP_HOST'],4)), "WSP");
		$mail_data['mailReply'] = array("noreply@".((substr($_SERVER['HTTP_HOST'],0,4)!='www.')?$_SERVER['HTTP_HOST']:substr($_SERVER['HTTP_HOST'],4)),"WSP");
		$mail_data['mailReturnPath'] = "noreply@".((substr($_SERVER['HTTP_HOST'],0,4)!='www.')?$_SERVER['HTTP_HOST']:substr($_SERVER['HTTP_HOST'],4));
		$mail_data['mailHTML'] = "<p>".returnIntLang('requestpass new pass requested intro', false)."</p><p>&nbsp;</p><p>".returnIntLang('requestpass new pass for url', false)."</p><h2>".cleanPath($_SESSION['wspvars']['workspaceurl']."/".WSP_DIR)."</h2><p>".returnIntLang('requestpass new pass username str', false)."</p><h1>".trim($pass_res['set'][0]["user"])."</h1><p>".returnIntLang('requestpass new pass password str', false)."</p><h1>".$newpass."</h1></p><p>&nbsp;</p><p>&nbsp;</p><p><a href='https://".cleanPath($_SESSION['wspvars']['workspaceurl']."/".WSP_DIR."/")."' class='button' target='_blank'>".returnIntLang('requestpass go to url', false)."</a></p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p style='font-size: 0.8em;'>".returnIntLang('requestpass new pass requested date', false)." <strong>".date("Y-m-d H:i:s")."</strong> ".returnIntLang('requestpass new pass requested ip', false)." <strong>".$_SERVER['REMOTE_ADDR']."</strong>";
		$mail_data['mailTXT'] = returnIntLang('requestpass new pass requested intro', false)."\n\n======================================================================\n".returnIntLang('requestpass new pass for url', false).": ".cleanPath($_SESSION['wspvars']['workspaceurl']."/".WSP_DIR)."/\n======================================================================\n".returnIntLang('requestpass new pass username str', false).": ".trim($pass_res['set'][0]["user"])."\n".returnIntLang('requestpass new pass password str', false).": ".$newpass."\n======================================================================\n\n".returnIntLang('requestpass new pass requested date', false)." ".date("Y-m-d H:i:s")."\n".returnIntLang('requestpass new pass requested ip', false)." ".$_SERVER['REMOTE_ADDR'];
		$mail_data['mailSubject'] = setUTF8(returnIntLang('requestpass new pass', false)."»".trim($pass_res['set'][0]["user"])."«");
		$mail_data['useHTML'] = 1;
        // make password update and submit mail if succesfull
        $np_sql = "UPDATE `restrictions` SET `pass` = '".md5($newpass)."' WHERE `rid` = ".intval(trim($pass_res['set'][0]['rid']));
        $np_res = doSQL($np_sql);
        if ($np_res['aff']>0) {
			checkandsendMail($mail_data);
			$_SESSION['wspvars']['showlogin'] = "requested";
		}
	}
}

// DO login
if (isset($_POST['loginfield']) && $_POST['loginfield']=="true") {
    // set login failures on first login submit
    if (!(isset($_SESSION['wspvars']['fails']))) { $_SESSION['wspvars']['fails'] = 0; }
    // try to do the login
	$login_sql = "SELECT * FROM `restrictions` WHERE (`user` = '".escapeSQL($_POST['loginuser'])."' OR `realmail` = '".escapeSQL($_POST['loginuser'])."') AND `pass` = '".md5($_POST['loginpassword'])."' AND `usertype` != '0'";
	$login_res = doSQL($login_sql);
	$login_num = $login_res['num'];
    // if user was found    
    if ($login_num==1) {
        // create usevar
        $_SESSION['wspvars']['usevar'] = md5($login_res['set'][0]['rid'].$_SERVER['REMOTE_ADDR'].time().$_SERVER['HTTP_USER_AGENT']);
        // do security entry
		$sql = "INSERT INTO `security` SET 
			`referrer` = '".escapeSQL($_SERVER['REMOTE_ADDR'])."',
			`userid` = ".intval($login_res['set'][0]['rid']).",
			`timevar` = ".time().",
			`usevar` = '".escapeSQL($_SESSION['wspvars']['usevar'])."',
			`logintime` = ".time().",
			`position` = 'loginattempt'";
		doSQL($sql);
        // bring ftp-data to session
        if (defined('FTP_USAGE') && FTP_USAGE===false) {
            $_SESSION['wspvars']['ftp'] = false;
            $_SESSION['wspvars']['srv'] = true;
        } else {
            // define ability to go on with apache writing if ftp is false
            $_SESSION['wspvars']['srv'] = (isset($_REQUEST['ignoreftp'])?true:false);
            $_SESSION['wspvars']['ftp_host'] = defined('FTP_HOST')?trim(FTP_HOST):false;
            $_SESSION['wspvars']['ftp_user'] = defined('FTP_USER')?trim(FTP_USER):false;
            $_SESSION['wspvars']['ftp_pass'] = defined('FTP_PASS')?trim(FTP_PASS):false;
            $_SESSION['wspvars']['ftp_base'] = defined('FTP_BASE')?trim(FTP_BASE):false;
            $_SESSION['wspvars']['ftp_port'] = (defined('FTP_PORT')?intval(FTP_PORT):21);
            $_SESSION['wspvars']['ftp_ssl'] = (defined('FTP_SSL')?FTP_SSL:false);
            $_SESSION['wspvars']['ftp_pasv'] = (defined('FTP_PASV')?FTP_PASV:false);
            if ($_SESSION['wspvars']['ftp_host']!==false && $_SESSION['wspvars']['ftp_user']!==false && $_SESSION['wspvars']['ftp_pass']!==false && $_SESSION['wspvars']['ftp_base']!==false) {
                // normal ftp-connection OR temporary ftp-connection are true
                // check, if basedir is set correct by checking, if wsp-basefolder is found     
                $ftp = (($_SESSION['wspvars']['ftp_ssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftp_host'], $_SESSION['wspvars']['ftp_port'], 5):ftp_connect($_SESSION['wspvars']['ftp_host'], $_SESSION['wspvars']['ftp_port'], 5)); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftp_user'], $_SESSION['wspvars']['ftp_pass'])) { $ftp = false; }} if ($ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftp_pasv']); }
                // create user directory with normal ftp-connection
                if ($ftp!==false) {
                    $mkdir = ftp_mkdir($ftp, $_SESSION['wspvars']['ftp_base'].'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar']);
                    if ($mkdir) {
                        $chmod = ftp_chmod($ftp, 0777, $mkdir);
                        if ($chmod) {
                            ftp_close($ftp); unset($ftp);
                            if (isset($_SESSION['wspvars']['related']) && trim($_SESSION['wspvars']['related'])!='') {
                                $_SESSION['wspvars']['srv'] = false;
                                $_SESSION['wspvars']['ftp'] = true;
                                
                                die(trim($_SESSION['wspvars']['related']));
                                
                                header("location: .".trim($_SESSION['wspvars']['related']));
                            }
                            else {
                                $_SESSION['wspvars']['srv'] = false;
                                $_SESSION['wspvars']['ftp'] = true;
                                header("location: ./index.php");
                            }
                            die();
                        } else {
                            if (defined('WSP_DEV') && WSP_DEV) {
                                addWSPMsg('errormsg', returnIntLang('login could not set rights on temporary directory', false));
                            }
                            $_SESSION['wspvars']['srv'] = true;
                            $_SESSION['wspvars']['ftp'] = false;
                        }
                    }
                    else {
                        if (defined('WSP_DEV') && WSP_DEV) {
                            addWSPMsg('errormsg', returnIntLang('login could not create temporary directory', false));
                        }
                        $_SESSION['wspvars']['srv'] = true;
                        $_SESSION['wspvars']['ftp'] = false;
                    }
                    @ftp_close($ftp);
                }
            } else {
                // try to use the srv-access
                $_SESSION['wspvars']['srv'] = true;
                $_SESSION['wspvars']['ftp'] = false;
            }
        }
        
        if ($_SESSION['wspvars']['srv']===true) {
            // try workaround with apache rights
            $mdstat = @mkdir(DOCUMENT_ROOT.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'], 0777);
			if ($mdstat===true) {
				$mfstat = fopen(DOCUMENT_ROOT.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/test.txt', 'w');
				if ($mfstat!==false) {
					fclose($mfstat);
				} else {
                    $_SESSION['wspvars']['srv'] = false;
                }
			}
			if ($_SESSION['wspvars']['srv']===true) {
				addWSPMsg('noticemsg', returnIntLang('use wsp with direct writing', false));
				$loginindex = true;
                if (isset($_SESSION['wspvars']['related']) && trim($_SESSION['wspvars']['related'])!='') {
                    header("location: .".trim($_SESSION['wspvars']['related']));
                }
                else {
                    header("location: ./index.php");
                }
			}
			else {
                $_SESSION['wspvars']['srv'] = false;
                $_SESSION['wspvars']['ftp'] = false;
                doSQL("DELETE FROM `security` WHERE `userid` = '".intval($login_res['set'][0]['rid'])."'");
            }
        }
        else {
			$loginindex = false;
			addWSPMsg('errormsg', returnIntLang('login try extended login', false));
        }
    } else {
        if (defined('WSP_DEV') && WSP_DEV) {
            var_export($login_res);
        }
        $_SESSION['wspvars']['fails']++;
        if (isset($_SESSION['wspvars']['loginfails']) && $_SESSION['wspvars']['fails']>=$_SESSION['wspvars']['loginfails']) {
            
            addWSPMsg('errormsg', returnIntLang('logindata failure username set inactive', false));
            

            // send message to basemail
            if (defined('BASEMAIL')) {
                $notice = "username »".trim($_POST['loginuser'])."« had too many login attempts with false pass from IP ".trim($_SERVER['REMOTE_ADDR'])."\n\n";
                mail(BASEMAIL , 'user blocked from '.$_SERVER['HTTP_HOST'] , $notice);
            }
            $_SESSION['wspvars']['showlogin'] = false;
        } else {
            addWSPMsg('errormsg', returnIntLang('logindata failure username or password', false));
        }
		$loginindex = false;
	}
}

/* include head ------------------------------ */
include("./data/include/loginheader.inc.php");

?>

    <div id="wrapper">
        <div class="vertical-align-wrap">
            <div class="vertical-align-middle">
                <div class="auth-box ">
                    <div class="left">
                        <div class="content">
                            <div class="header">
                                <h1><?php echo returnIntLang('home welcome'); ?></h1>
                                <p class="lead"><?php 
                                
                                if (isset($_SESSION['wspvars']['showlogin']) && $_SESSION['wspvars']['showlogin']==="pass") {
                                    echo returnIntLang('login request pass emailinfo', true);
                                } 
                                else if ($_SESSION['wspvars']['showlogin']!==false) {
                                    echo returnIntLang('login please activate cookies', true); 
                                    echo "<noscript> ";
                                    echo returnIntLang('login please activate jscript1', true); 
                                    echo "</noscript> ";
                                    echo returnIntLang('login please activate', true);
                                    if (isset($_REQUEST['extended'])) {
                                        echo " ".returnIntLang('extended login info', true); 
                                    }
                                }

                                ?></p>
                                <?php
                                
                                if (isset($_SESSION['wspvars']['errormsg']) && (is_array($_SESSION['wspvars']['errormsg']) || trim($_SESSION['wspvars']['errormsg'])!='')) {
                                    echo "<div class='error'>".(is_array($_SESSION['wspvars']['errormsg'])?implode('<br />', $_SESSION['wspvars']['errormsg']):trim($_SESSION['wspvars']['errormsg']))."</div>";
                                    unset($_SESSION['wspvars']['errormsg']);
                                }

                                ?>
                            </div>
                            <?php if (isset($_SESSION['wspvars']['showlogin']) && $_SESSION['wspvars']['showlogin']==="pass"): ?>
                                <script language="JavaScript" type="text/javascript">
                                <!--

                                document.write("<form action=\"<?php echo $_SERVER['PHP_SELF']; ?>\" method=\"post\" id=\"wsplogin\" name=\"wsplogin\" class=\"form-auth-small\" >");

                                // -->
                                </script>
                                <div class="form-group">
                                    <label for="signin-email" class="control-label sr-only"><?php echo returnIntLang('str email', true); ?></label>
                                    <input type="email" class="form-control" id="signin-usermail"  name="loginmail" value="" placeholder="<?php echo returnIntLang('str email', false); ?>">
                                </div>
                                <script language="JavaScript" type="text/javascript">
                                <!--     

                                document.write("<input name='requestfield' type='hidden' value='true' /><button type='submit' class='btn btn-primary' /><?php echo returnIntLang('btn request', false); ?></button>");
                                document.write('</form>');

                                // -->
                                </script>
                                <div class="bottom">
                                    <p><span class="helper-text"><i class="fa fa-lock"></i> <a href="?login"><?php echo returnIntLang('login back to login', true); ?></a></span></p>
                                </div>
                            <?php else: ?>
                                <script language="JavaScript" type="text/javascript">
                                <!--

                                document.write("<form action=\"<?php echo $_SERVER['PHP_SELF']; ?>\" method=\"post\" id=\"wsplogin\" name=\"wsplogin\" class=\"form-auth-small\" >");

                                // -->
                                </script>
                                <div class="form-group">
                                    <label for="signin-username" class="control-label sr-only"><?php echo returnIntLang('str username', true); ?></label>
                                    <input type="text" class="form-control" required id="signin-username"  name="loginuser" value="<?php if (isset($_POST['loginuser'])) echo $_POST['loginuser']; ?>" placeholder="<?php echo returnIntLang('str username', false); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="signin-password" class="control-label sr-only"><?php echo returnIntLang('str password', true); ?></label>
                                    <input type="password" class="form-control" id="signin-password" name="loginpassword" value="" placeholder="<?php echo returnIntLang('str password', false); ?>">
                                </div>
                                <?php if (isset($_SESSION['wspvars']['showlogin']) && $_SESSION['wspvars']['showlogin']==='extended') { ?>
                                    <div class="form-group">
                                        <label for="signin-ftpserver" class="control-label sr-only"><?php echo returnIntLang('editcon ftp host', true); ?></label>
                                        <input type="text" class="form-control" id="signin-ftpserver" name="ftpserver" value="<?php if (isset($_POST['ftpserver'])) echo $_POST['ftpserver']; ?>" placeholder="<?php echo returnIntLang('editcon ftp host', false); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="signin-ftpbasedir" class="control-label sr-only"><?php echo returnIntLang('editcon ftp basedir', true); ?></label>
                                        <input type="text" class="form-control" id="signin-ftpbasedir" name="ftpbasedir" value="<?php if (isset($_POST['ftpbasedir'])) echo $_POST['ftpbasedir']; ?>" placeholder="<?php echo returnIntLang('editcon ftp basedir', false); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="signin-ftpuser" class="control-label sr-only"><?php echo returnIntLang('editcon ftp username', true); ?></label>
                                        <input type="text" class="form-control" id="signin-ftpuser" name="ftpuser" value="<?php if (isset($_POST['ftpuser'])) echo $_POST['ftpuser']; ?>" placeholder="<?php echo returnIntLang('editcon ftp username', false); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="signin-ftppass" class="control-label sr-only"><?php echo returnIntLang('editcon ftp password', true); ?></label>
                                        <input type="password" class="form-control" id="signin-ftppass" name="ftppass" value="<?php if (isset($_POST['ftppass'])) echo $_POST['ftppass']; ?>" placeholder="<?php echo returnIntLang('editcon ftp password', false); ?>">
                                    </div>
                                <?php } else if ($_SESSION['wspvars']['showlogin']!==false) {
                                    if (!(defined('FTP_USAGE')) || (defined('FTP_USAGE') && FTP_USAGE!==false)) { ?>
                                    <div class="form-group clearfix">
                                        <label class="fancy-checkbox element-center custom-bgcolor-blue">
                                            <input type="checkbox" name="ignoreftp" id="ignoreftp" value="true" />
                                            <span class="text-muted"><?php echo returnIntLang('login ignore ftp access', true); ?></span>
                                        </label>
                                    </div>
                                <?php }} ?>
                            
                                <script language="JavaScript" type="text/javascript">
                                <!--     

                                document.write("<input name='loginfield' type='hidden' value='true' /><button type='submit' class=\"btn btn-primary\" /><?php echo returnIntLang('btn login', false); ?></button>");
                                document.write('</form>');

                                // -->
                                </script>

                                <div class="bottom">
                                    <p>
                                        <span class="helper-text"><i class="fa fa-envelope"></i> <a href="?requestpass"><?php echo returnIntLang('login request lostpass', true); ?></a></span>
                                        <?php if (isset($_SESSION['wspvars']['showlogin']) && $_SESSION['wspvars']['showlogin']!==true) { ?>
                                             &nbsp; <span class="helper-text"><i class="fa fa-exclamation-triangle"></i> <a href="?disableextended"><?php echo returnIntLang('login back to login', true); ?></a></span>
                                        <?php } else if (!(defined('FTP_USAGE')) || (defined('FTP_USAGE') && FTP_USAGE!==false)) { ?> &nbsp; <span class="helper-text"><i class="fa fa-exclamation-triangle"></i> <a href="?extendedlogin"><?php echo returnIntLang('login request extendedlogin', true); ?></a></span><?php } ?> &nbsp; <span class="helper-text"><i class="fa fa-home"></i> <a href="/"><?php echo returnIntLang('login go to homepage', true); ?></a></span></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
<?php 
			
if (isset($_SESSION['wspvars']['cookiecheck']) && $_SESSION['wspvars']['cookiecheck']=="requested") { 
    echo returnIntLang('login please log in')." ";
    echo returnIntLang('login pass requested', true); 
    echo " <a href=\"index.php\">".returnIntLang('login return to login', true)."</a>";
} else if (isset($_SESSION['wspvars']['cookiecheck']) && $_SESSION['wspvars']['cookiecheck']=="pass") {
	echo returnIntLang('login request pass emailinfo', true); 
	echo returnIntLang('login request pass', true);
    echo returnIntLang('str email', true); 
    echo '<input name="loginmail" id="loginmail" type="text" value="'.(isset($_POST['loginmail'])?$_POST['loginmail']:'').'" class="one full" />';
    echo '<p><input name="requestfield" type="hidden" value="go"><a href="#" onclick="document.getElementById(\'passrequest\').submit();" class="greenfield">'.returnIntLang('str request', true).'</a></p>';
} else { 
    echo '</p></fieldset>';
}

// EOF