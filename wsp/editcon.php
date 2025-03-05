<?php
/**
 * @description connection and access data
 * @author stefan@covi.de
 * @since 3.3.0
 * @version 7.0
 * @lastchange 2019-11-05
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = 'siteprops';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-gears',returnIntLang('menu manage'),returnIntLang('menu manage connections'));
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$proof = false;
$check = false;
$rootphrase = false;
/* define page specific functions ------------ */

// check and save connection
if (isset($_POST['action']) && $_POST['action']=="save"):
    $ftp_con = false;
    $db_con = false;
    $smtp_con = false;
    
    // check for ftp changes
    $doftpupdate = false;
    if (trim($_POST['ftp_host'])!=FTP_HOST) $doftpupdate = true;
    if (trim($_POST['ftp_base'])!=FTP_BASE) $doftpupdate = true;
    if (trim($_POST['ftp_user'])!=FTP_USER) $doftpupdate = true;
    if (trim($_POST['ftp_pass'])!=FTP_PASS) $doftpupdate = true;
    if (intval($_POST['ftp_port'])!=intval(FTP_PORT)) $doftpupdate = true;
    if (intval($_POST['ftp_ssl'])!=intval(FTP_SSL)) $doftpupdate = true;
    // check for ftp-connection
    if ($doftpupdate):
        $ftp = ftp_connect($_POST['ftp_host'], $_POST['ftp_port']);
        if ($ftp):
            $ftpcon = ftp_login($ftp, $_POST['ftp_user'], $_POST['ftp_pass']);
            if ($ftpcon):
                if (ftp_chdir($ftp, $_POST['ftp_base'])):
                    $ftp_con = true;
                    addWSPMsg('resultmsg', returnIntLang('editcon ftp check 3', false));
                else:
                    addWSPMsg('errormsg', returnIntLang('editcon ftp false 3', false));
                endif;
            else:
                addWSPMsg('errormsg', returnIntLang('editcon ftp false 2', false));
            endif;
            @ftp_close($ftpcon);
        else:
            addWSPMsg('errormsg', returnIntLang('editcon ftp false 1', false));
        endif;
    else:
        $ftp_con = true;
    endif;
    
    // check for db changes
    $dodbupdate = false;
    if (trim($_POST['db_host'])!=DB_HOST) $dodbupdate = true;
    if (trim($_POST['db_name'])!=DB_NAME) $dodbupdate = true;
    if (trim($_POST['db_user'])!=DB_USER) $dodbupdate = true;
    if (trim($_POST['db_pass'])!=DB_PASS) $dodbupdate = true;
    if (trim($_POST['db_prefix'])!=DB_PREFIX) $dodbupdate = true;
    // check for db connection
    if ($dodbupdate):
        $db_con = mysqli_connect(trim($_POST['db_host']),trim($_POST['db_user']),trim($_POST['db_pass']),trim($_POST['db_name']));
        if($db_con):
            // check for a wsp-required table
            $db_con = mysqli_query($db_con, 'SELECT `rid` FROM `restrictions`');
            if ($db_con===false):
                addWSPMsg('errormsg', returnIntLang('editcon db false 2', false));
            else:
                addWSPMsg('resultmsg', returnIntLang('editcon db check 1', false));
            endif;
        else:
            addWSPMsg('errormsg', returnIntLang('editcon db false 1', false));
        endif;
    else:
        $db_con = true;
    endif;
    
    // check for smtp changes
    $dosmtpupdate = false;
    if (trim($_POST['smtp_host'])!=SMTP_HOST) $dosmtpupdate = true;
    if (trim($_POST['smtp_user'])!=SMTP_USER) $dosmtpupdate = true;
    if (trim($_POST['smtp_pass'])!=SMTP_PASS) $dosmtpupdate = true;
    if (intval($_POST['smtp_port'])!=intval(SMTP_PORT)) $dosmtpupdate = true;
    if (intval($_POST['smtp_ssl'])!=intval(SMTP_SSL)) $dosmtpupdate = true;
    // check for smtp
    if ($dosmtpupdate):
        if (is_file("./data/include/phpmailer/class.phpmailer.php")):
            // get user mail address
            $usermail_res = doResultSQL("SELECT `realmail` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']));
            if ($usermail_res):
                require_once("./data/include/phpmailer/PHPMailerAutoload.php");
                $mail = new phpmailer();
                $mail->AddAddress($usermail_res);
                $mail->isSMTP();
                $mail->Host     = trim($_POST['smtp_host']);
                $mail->SMTPAuth = true;
                $mail->Username = trim($_POST['smtp_user']);
                $mail->Password = trim($_POST['smtp_pass']);
                $mail->SMTPSecure = ((intval($_POST['smtp_ssl'])==1)?'tls':'');
                $mail->Port     = intval($_POST['smtp_port']);
                $mail->setFrom  = (trim($_POST['smtp_user']));
                $mail->WordWrap = 50;
                $mail->isHTML(true);
                $mail->Subject  = "WSP SMTP Connection Test";
                $mail->Body     = "WSP SMTP <b>Connection Test</b>";
                $mail->AltBody  = "WSP SMTP Connection Test";
                if($mail->Send()):
                    addWSPMsg('resultmsg', returnIntLang('editcon smtp check 1', false));
                    $smtp_con = true;
                else:
                    addWSPMsg('errormsg', returnIntLang('editcon smtp false 3', false));
                    addWSPMsg('errormsg', $mail->ErrorInfo);
                endif;
            else:
                addWSPMsg('errormsg', returnIntLang('editcon smtp false 2', false));
            endif;
        else:
            addWSPMsg('errormsg', returnIntLang('editcon smtp false 1', false));
        endif;
    else:
        $smtp_con = true;
    endif;
    
    // remove older connections
    $sql = "DELETE FROM `wspaccess` WHERE `type` = 'smtp'";
    $res = doSQL($sql);
    // add additional smtp-connections to database
    if (isset($_POST['smtp_name_xtd']) && count($_POST['smtp_name_xtd'])>0):
        foreach ($_POST['smtp_name_xtd'] AS $psxk => $psxv):
            if (trim($psxv)!=''):
                $name = trim($_POST['smtp_name_xtd'][$psxk]);
                $host = trim($_POST['smtp_host_xtd'][$psxk]);
                $user = trim($_POST['smtp_user_xtd'][$psxk]);
                $pass = trim($_POST['smtp_pass_xtd'][$psxk]);
                $port = intval($_POST['smtp_port_xtd'][$psxk]);
                $ssl = intval($_POST['smtp_ssl_xtd'][$psxk]);
                if ($name!='' && $host!='' && $user!='' && $pass!='' && $port>0):
                    $sql = "INSERT INTO `wspaccess` SET ";
                    if (intval($psxk)>0):
                        $sql.= " `id` = ".intval($psxk).", ";
                    endif;
                    $sql.= " `description` = '".escapeSQL($name)."', `type` = 'smtp', `host` = '".escapeSQL($host)."', `location` = '".$port."', `username` = '".escapeSQL($user)."', `passphrase` = '".escapeSQL(trim($xtea->Encrypt($pass)))."', `definition` = '".intval($ssl)."' ";
                    $res = doSQL($sql);
                endif;
            endif;
        endforeach;
    endif;    
    // check if there were ANY changes between old and new data
    $doupdate = false;
    if (trim($_POST['db_host'])!=DB_HOST) $doupdate = true;
    if (trim($_POST['db_name'])!=DB_NAME) $doupdate = true;
    if (trim($_POST['db_user'])!=DB_USER) $doupdate = true;
    if (trim($_POST['db_pass'])!=DB_PASS) $doupdate = true;
    if (trim($_POST['db_prefix'])!=DB_PREFIX) $doupdate = true;
    if (trim($_POST['ftp_host'])!=FTP_HOST) $doupdate = true;
    if (trim($_POST['ftp_base'])!=FTP_BASE) $doupdate = true;
    if (trim($_POST['ftp_user'])!=FTP_USER) $doupdate = true;
    if (trim($_POST['ftp_pass'])!=FTP_PASS) $doupdate = true;
    if (intval($_POST['ftp_port'])!=intval(FTP_PORT)) $doupdate = true;
    if (intval($_POST['ftp_ssl'])!=intval(FTP_SSL)) $doupdate = true;
    if (trim($_POST['smtp_host'])!=SMTP_HOST) $doupdate = true;
    if (trim($_POST['smtp_user'])!=SMTP_USER) $doupdate = true;
    if (trim($_POST['smtp_pass'])!=SMTP_PASS) $doupdate = true;
    if (intval($_POST['smtp_port'])!=intval(SMTP_PORT)) $doupdate = true;
    if (intval($_POST['smtp_ssl'])!=intval(SMTP_SSL)) $doupdate = true;
    // update config file
    if ($ftp_con && $db_con && $doupdate):
        
        $fh = fopen(cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/wspconf.inc.php"), "w+");
        fwrite($fh, "<?php
/**
 * WSP conf file (system written)
 * @since 7.0
 * @version 7.".time()."
 */

define('DB_HOST', '".trim($_POST['db_host'])."' ); // optional
define('DB_NAME', '".trim($_POST['db_name'])."' );
define('DB_USER', '".trim($_POST['db_user'])."' );
define('DB_PASS', '".trim($_POST['db_pass'])."' );
define('DB_PREFIX', '".trim($_POST['db_prefix'])."' ); // optional

define('FTP_HOST', '".trim($_POST['ftp_host'])."' ); // optional
define('FTP_BASE', '".trim(cleanPath("/".$_POST['ftp_base']."/"))."' ); // base-directory relative to your ftp root directory you are logging in
define('FTP_USER', '".trim($_POST['ftp_user'])."' );
define('FTP_PASS', '".trim($_POST['ftp_pass'])."' );
define('FTP_PORT', '".trim($_POST['ftp_port'])."' ); // optional
define('FTP_SSL', ".(($_POST['ftp_ssl']==1)?'true':'false')." ); // optional

define('SMTP_HOST', '".trim($_POST['smtp_host'])."' );
define('SMTP_USER', '".trim($_POST['smtp_user'])."' );
define('SMTP_PASS', '".trim($_POST['smtp_pass'])."' ); 
define('SMTP_PORT', '".trim($_POST['smtp_port'])."' ); // optional
define('SMTP_SSL', ".((intval($_POST['smtp_ssl'])==1)?'true':'false')." ); // optional
".((!($smtp_con))?"define('SMTP_CON', false );":"")."

define('ROOTPHRASE', '".((defined('ROOTPHRASE'))?ROOTPHRASE:'')."' );

define('WSP_DIR', '".((defined('WSP_DIR'))?WSP_DIR:'wsp')."' );
define('WSP_SPACE', '".((defined('WSP_SPACE'))?WSP_SPACE:'0')."' );

define('WSP_MSG', ".((defined('WSP_MSG'))?WSP_MSG:'0')." ); // 0 = pop, 1 = inline (above content)
define('WSP_DEV', ".((defined('WSP_DEV'))?var_export(WSP_DEV, true):'false')." ); // optional

define('WSP_UPDKEY', '".((defined('WSP_UPDKEY'))?WSP_UPDKEY:'')."' );
define('WSP_UPDSRV', '".((defined('WSP_UPDSRV'))?WSP_UPDSRV:'')."' ); // update-server location

define('BASEMAIL', '".((defined('BASEMAIL'))?BASEMAIL:'')."' ); // optional -> this mail can get every notification even if no user is set

?>");
        fclose($fh);
        // copy config file to new ftp location
        $ftp = ftp_connect($_POST['ftp_host'], $_POST['ftp_port']);
        if ($ftp):
            $ftpcon = ftp_login($ftp, $_POST['ftp_user'], $_POST['ftp_pass']);
            if ($ftpcon):
                if (ftp_chdir($ftp, $_POST['ftp_base'])):
                    $ftpput = @ftp_put($ftp, './'.WSP_DIR.'/data/include/wspconf.inc.php', cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/wspconf.inc.php"), FTP_BINARY);
                    if ($ftpput):
                        addWSPMsg('resultmsg', returnIntLang('editcon created new config file', false));
                    endif;
                endif;
            endif;
            @ftp_close($ftpcon);
        endif;
        // try to copy config file to old ftp location
        $ftp = ftp_connect(FTP_HOST, FTP_PORT);
        if ($ftp):
            $ftpcon = ftp_login($ftp, FTP_USER, FTP_PASS);
            if ($ftpcon):
                if (ftp_chdir($ftp, FTP_BASE)):
                    $ftpput = @ftp_put($ftp, './'.WSP_DIR.'/data/include/wspconf.inc.php', cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/wspconf.inc.php"), FTP_BINARY);
                endif;
            endif;
            @ftp_close($ftpcon);
        endif;
        
    elseif ($ftp_con && $db_con && !($doupdate)):
        
        addWSPMsg('noticemsg', returnIntLang('editcon updating config file not necessary', false));
        
    endif;
endif;

// allow smtp definition if phpmailer is avaiable
if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/include/phpmailer/class.phpmailer.php")): $smtp = true; else: $smtp = false; endif;

// head der datei
include ("data/include/header.inc.php");
include ("data/include/navbar.inc.php");
include ("data/include/sidebar.inc.php");

?>
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('editcon headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('editcon legend'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php
	       
            $con_res = false; if ($rootphrase):
                // get extended SMTP connections
                $con_sql = "SELECT * FROM `wspaccess` WHERE `type` = 'smtp' ORDER BY `id`";
                $con_res = doSQL($con_sql);
            endif;
            
            ?>
            <div class="row">
                <div class="col-md-12">
                     <div class="panel panel-tab">
                        <div class="panel-heading">
                            <ul class="nav nav-tabs pull-left">
                                <li class="active"><a href="#conftp" data-toggle="tab"><i class="fa fa-server"></i> <?php echo returnIntLang('editcon ftp', false); ?></a></li>
                                <li><a href="#condb" data-toggle="tab"><i class="fa fa-database"></i> <?php echo returnIntLang('editcon db', false); ?></a></li>
                                <?php if ($smtp): ?><li><a href="#consmtp_wsp" data-toggle="tab"><i class="fa fa-envelope"></i> <?php echo returnIntLang('editcon smtp wsp', false); ?></a></li><?php endif; ?>
                                <?php if ($rootphrase && ($con_res['num'])>0): 
                                foreach ($con_res['set'] AS $crsk => $crsv):
                                    echo "<li><a href='#consmtp_".$crsv['id']."' data-toggle='tab'><i class='fa fa-envelope'></i> ".returnIntLang('editcon smtp', false)." ".$crsv['description']."</a></li>";
                                endforeach;
                                endif; ?>
                                <li><a href="#consmtp_0" data-toggle="tab"><i class="fa fa-database"></i> <?php echo returnIntLang('editcon smtp new', false); ?></a></li>
                            </ul>
                            <h3 class="panel-title">&nbsp;</h3>
                        </div>
                        <form method="post" id="conform" >
                        <div class="panel-body" >
                            <div class="tab-content no-padding">
                                <div class="tab-pane fade in active" id="conftp">
                                    <div class="row">
                                        <div class="col-md-6">
                                                <?php
                                                
                                                if ($_SESSION['wspvars']['ftp']===false) {
                                                    print('<div class="row">
                                                    <div class="col-md-12"><p>'.returnIntLang('no ftp connection setup. wsp works in srv mode.').'</p></div>
                                                    </div>');
                                                }
                                                
                                                ?>
                                                <div class="form-group">
                                                    <label for="ticket-name" class="col-sm-3 control-label">FTP_HOST</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-server"></i></span>
                                                            <input type="text" class="form-control" name="ftp_host" id="ftp_host" placeholder="FTP_HOST" value="<?php echo defined('FTP_HOST')?FTP_HOST:''; ?>" data-toggle="tooltip" data-trigger="focus" data-placement="top" data-original-title="<?php echo returnIntLang('editcon ftp host help', false); ?>" onchange="updateCon('conftp',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="ticket-name" class="col-sm-3 control-label">FTP_BASE</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-terminal"></i></span>
                                                            <input type="text" required class="form-control" name="ftp_base" id="ftp_base" placeholder="<?php echo returnIntLang('editcon ftp basedir', false); ?>" value="<?php echo defined('FTP_BASE')?FTP_BASE:''; ?>" data-toggle="tooltip" data-trigger="focus" data-placement="top" data-original-title="<?php echo returnIntLang('editcon ftp basedir help', false); ?>" onchange="updateCon('conftp',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="ticket-name" class="col-sm-3 control-label">FTP_USER</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                                            <input type="text" required class="form-control" name="ftp_user" id="ftp_user" placeholder="<?php echo returnIntLang('editcon ftp username', false); ?>" value="<?php echo defined('FTP_USER')?FTP_USER:''; ?>" data-toggle="tooltip" data-trigger="focus" data-placement="top" data-original-title="<?php echo returnIntLang('editcon ftp username help', false); ?>" onchange="updateCon('conftp',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="ticket-name" class="col-sm-3 control-label">FTP_PASS</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
                                                            <input type="text" required class="form-control" name="ftp_pass" id="ftp_pass" placeholder="FTP_PASS" value="<?php echo defined('FTP_PASS')?FTP_PASS:''; ?>" data-toggle="tooltip" data-trigger="focus" data-placement="top" data-original-title="<?php echo returnIntLang('ftp pass help', false); ?>" onchange="updateCon('conftp',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="ticket-name" class="col-sm-3 control-label">FTP_PORT</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="far fa-dot-circle"></i></span>
                                                            <input type="number" min="1" class="form-control" name="ftp_port" id="ftp_port" placeholder="<?php echo returnIntLang('editcon ftp port', false); ?>" value="<?php echo defined('FTP_PORT')?FTP_PORT:''; ?>" data-toggle="tooltip" data-trigger="focus" data-placement="top" data-original-title="<?php echo returnIntLang('editcon ftp port help', false); ?>" onchange="updateCon('conftp',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="ticket-name" class="col-sm-3 control-label">FTP_SSL</label>
                                                    <div class="col-sm-9">
                                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                                            <input type="hidden" name="ftp_ssl" value="0" /><input type="checkbox" id="ftp_ssl" name="ftp_ssl" value="1" onchange="updateCon('conftp',this.id,this.value);" ><span class="text-muted" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon ftp ssl help', false); ?>">&nbsp;</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="ticket-name" class="col-sm-3 control-label">FTP_PASV</label>
                                                    <div class="col-sm-9">
                                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                                            <input type="hidden" name="ftp_pasv" value="0" /><input type="checkbox" id="ftp_pasv" name="ftp_pasv" value="1" onchange="updateCon('conftp',this.id,this.value);" ><span class="text-muted" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon ftp pasv help', false); ?>">&nbsp;</span>
                                                        </label>
                                                    </div>
                                                </div>

                                        </div>
                                        <div class="col-md-6">
<pre id="conftp_preview">
define('FTP_HOST', 'localhost' );
define('FTP_BASE', '/dev/' );
define('FTP_USER', 'ftp_wsp' );
define('FTP_PASS', 'ypGM7W2J' );
define('FTP_PORT', '21' );
define('FTP_SSL', false );
define('FTP_PASV', false );
</pre>
                                            <input type="hidden" id="conftp_checked" value="1" class="con-checked" />
                                            <p><a onclick="checkCon('ftp', 'conftp')" class="btn btn-primary disabled"><?php echo returnIntLang('str check', false); ?></a></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade in" id="condb">
                                    <div class="row">
                                        <div class="col-md-6">
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon db host help', false); ?>">
                                                    <label for="ticket-name" class="col-sm-3 control-label">DB_HOST</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-database"></i></span>
                                                            <input type="text" class="form-control" name="db_host" id="db_host" placeholder="DB_HOST" value="<?php echo DB_HOST; ?>" onchange="updateCon('condb',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon db name help', false); ?>">
                                                    <label for="ticket-name" class="col-sm-3 control-label">DB_NAME</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-at"></i></span>
                                                            <input type="text" required class="form-control" name="db_name" id="db_name" placeholder="<?php echo returnIntLang('editcon db name', false); ?>" value="<?php echo DB_NAME ?>" onchange="updateCon('condb',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon db username help', false); ?>">
                                                    <label for="ticket-name" class="col-sm-3 control-label">DB_USER</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                                            <input type="text" required class="form-control" name="db_user" id="db_user" placeholder="<?php echo returnIntLang('editcon db username', false); ?>" value="<?php echo DB_USER; ?>" onchange="updateCon('condb',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon db pass help', false); ?>">
                                                    <label for="ticket-name" class="col-sm-3 control-label">DB_PASS</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
                                                            <input type="text" required class="form-control" name="db_pass" id="db_pass" placeholder="DB_PASS" value="<?php echo DB_PASS; ?>" onchange="updateCon('condb',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon db prefix help', false); ?>">
                                                    <label for="ticket-name" class="col-sm-3 control-label">DB_PREFIX</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-terminal"></i></span>
                                                            <input class="form-control" name="db_prefix" id="db_prefix" placeholder="<?php echo returnIntLang('editcon db prefix', false); ?>" value="<?php echo DB_PREFIX; ?>" onchange="updateCon('condb',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                        </div>
                                        <div class="col-md-6">
<pre id="condb_preview">
define('DB_HOST', 'localhost' );
define('DB_NAME', 'wsp_dev' );
define('DB_USER', 'wsp_dev_usr' );
define('DB_PASS', 'tSdDtEjm' );
define('DB_PREFIX', '' );
</pre>                   
                                            <input type="hidden" id="condb_checked" value="1" class="con-checked" />
                                            <p><a onclick="checkCon('db', 'condb')" class="btn btn-primary disabled"><?php echo returnIntLang('str check', false); ?></a></p>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($smtp): ?>
                                    <div class="tab-pane fade in" id="consmtp_wsp">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp host help', false); ?>">
                                                    <label for="ticket-name" class="col-sm-3 control-label">SMTP_HOST</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-server"></i></span>
                                                            <input type="text" class="form-control" name="smtp_host" id="smtp_host" placeholder="SMTP_HOST" value="<?php echo SMTP_HOST; ?>" onchange="updateCon('consmtp_wsp',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp username help', false); ?>">
                                                    <label for="ticket-name" class="col-sm-3 control-label">SMTP_USER</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                                            <input type="text" required class="form-control" name="smtp_user" id="smtp_user" placeholder="<?php echo returnIntLang('editcon smtp username', false); ?>" value="<?php echo SMTP_USER; ?>" onchange="updateCon('consmtp_wsp',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('smtp pass help', false); ?>">
                                                    <label for="ticket-name" class="col-sm-3 control-label">SMTP_PASS</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
                                                            <input type="text" required class="form-control" name="smtp_pass" id="smtp_pass" placeholder="SMTP_PASS" value="<?php echo SMTP_PASS; ?>"  onchange="updateCon('consmtp_wsp',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp port help', false); ?>">
                                                    <label for="ticket-name" class="col-sm-3 control-label">SMTP_PORT</label>
                                                    <div class="col-sm-9">
                                                        <div class="input-group form-group">
                                                            <span class="input-group-addon"><i class="far fa-dot-circle"></i></span>
                                                            <input type="number" min="1" class="form-control" name="smtp_port" id="smtp_port" placeholder="<?php echo returnIntLang('editcon smtp port', false); ?>" value="<?php echo SMTP_PORT; ?>" onchange="updateCon('consmtp_wsp',this.id,this.value);" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="ticket-name" class="col-sm-3 control-label">SMTP_SSL</label>
                                                    <div class="col-sm-9">
                                                        <label class="fancy-checkbox custom-bgcolor-blue">
                                                            <input type="hidden" name="smtp_ssl" value="0" /><input type="checkbox" id="smtp_ssl" name="smtp_ssl" value="1" <?php echo (SMTP_SSL)?' checked="checked" ':''; ?> onchange="updateCon('consmtp_wsp',this.id,this.value);" ><span class="text-muted" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp ssl help', false); ?>">&nbsp;</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
<pre id="consmtp_wsp_preview">
define('SMTP_HOST', '<?php echo SMTP_HOST; ?>' );
define('SMTP_USER', '<?php echo SMTP_USER; ?>' );
define('SMTP_PASS', '<?php echo SMTP_PASS; ?>' );
define('SMTP_PORT', '<?php echo SMTP_PORT; ?>' );
define('SMTP_SSL', <?php var_export(SMTP_SSL); ?> );
</pre>
                                                <input type="hidden" id="consmtp_wsp_checked" value="1" class="con-checked" />
                                                <p><a onclick="checkCon('smtp', 'consmtp_wsp')" class="btn btn-primary disabled"><?php echo returnIntLang('str check', false); ?></a></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($rootphrase && ($con_res['num'])>0): 
                                        foreach ($con_res['set'] AS $crsk => $crsv): ?>
                                        <div class="tab-pane fade in" id="consmtp_<?php echo $crsv['id']; ?>">
                                            <div class="row">
                                                <div class="col-md-6">
                                                        <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp name of connection help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label"><?php echo returnIntLang('editcon smtp name of connection', false); ?></label>
                                                            <div class="col-sm-9">
                                                                <div class="input-group form-group">
                                                                    <span class="input-group-addon"><i class="fa fa-comment"></i></span>
                                                                    <input type="text" class="form-control" name="smtp_name_xtd[<?php echo $crsv['id']; ?>]" placeholder="<?php echo returnIntLang('editcon smtp name of connection', false); ?>" value="<?php echo $crsv['description']; ?>" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp host help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label">SMTP_HOST</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group form-group">
                                                                <span class="input-group-addon"><i class="fa fa-server"></i></span>
                                                                <input type="text" class="form-control" name="smtp_host_xtd[<?php echo $crsv['id']; ?>]" id="smtp_host" placeholder="SMTP_HOST" value="<?php echo $crsv['host']; ?>" onchange="updateCon('consmtp_<?php echo $crsv['id']; ?>', this.id, this.value);" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp username help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label">SMTP_USER</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group form-group">
                                                                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                                                <input type="text" required class="form-control" name="smtp_user_xtd[<?php echo $crsv['id']; ?>]" id="smtp_user" placeholder="<?php echo returnIntLang('editcon smtp username', false); ?>" value="<?php echo $crsv['username']; ?>" onchange="updateCon('consmtp_<?php echo $crsv['id']; ?>', this.id, this.value);" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('smtp pass help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label">SMTP_PASS</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group form-group">
                                                                <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
                                                                <input type="text" required class="form-control" name="smtp_pass_xtd[<?php echo $crsv['id']; ?>]" id="smtp_pass" placeholder="SMTP_PASS" value="<?php echo trim($xtea->Decrypt(trim($crsv['passphrase']))); ?>" onchange="updateCon('consmtp_<?php echo $crsv['id']; ?>', this.id, this.value);" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp port help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label">SMTP_PORT</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group form-group">
                                                                <span class="input-group-addon"><i class="far fa-dot-circle"></i></span>
                                                                <input type="number" min="1" class="form-control" name="smtp_port_xtd[<?php echo $crsv['id']; ?>]" id="smtp_port" placeholder="<?php echo returnIntLang('editcon smtp port', false); ?>" value="<?php echo $crsv['location']; ?>" onchange="updateCon('consmtp_<?php echo $crsv['id']; ?>', this.id, this.value);" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="ticket-name" class="col-sm-3 control-label">SMTP_SSL</label>
                                                        <div class="col-sm-9">
                                                            <label class="fancy-checkbox custom-bgcolor-blue">
                                                                <input type="hidden" name="smtp_ssl_xtd[<?php echo $crsv['id']; ?>]" value="0" /><input type="checkbox" id="smtp_ssl" name="smtp_ssl_xtd[<?php echo $crsv['id']; ?>]" value="1" <?php echo ($crsv['definition']==1)?' checked="checked" ':''; ?> onchange="updateCon('consmtp_<?php echo $crsv['id']; ?>', this.id, this.value);" ><span class="text-muted" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp ssl help', false); ?>">&nbsp;</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
<pre id="consmtp_<?php echo $crsv['id']; ?>_preview">
define('SMTP_HOST', '<?php echo $crsv['host']; ?>' );
define('SMTP_USER', '<?php echo $crsv['username']; ?>' );
define('SMTP_PASS', '<?php echo trim($xtea->Decrypt(trim($crsv['passphrase']))); ?>' );
define('SMTP_PORT', '<?php echo $crsv['location']; ?>' );
define('SMTP_SSL', <?php echo ($crsv['definition']==1)?'true':'false'; ?> );
</pre>
                                                    <input type="hidden" id="consmtp_<?php echo $crsv['id']; ?>_checked" class="con-checked" value="1" />
                                                    <p><a onclick="checkCon('smtp', 'consmtp_<?php echo $crsv['id']; ?>');" class="btn btn-primary disabled"><?php echo returnIntLang('str check', false); ?></a> <a onclick="deleteCon('consmtp_<?php echo $crsv['id']; ?>')" class="btn btn-danger"><?php echo returnIntLang('str delete', false); ?></a></p>
                                                </div>
                                            </div>
                                        </div><?php
                                        endforeach;
                                    endif; ?>
                                    <div class="tab-pane fade in" id="consmtp_0">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp name of connection help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label"><?php echo returnIntLang('editcon smtp name of connection', false); ?></label>
                                                            <div class="col-sm-9">
                                                                <div class="input-group form-group">
                                                                    <span class="input-group-addon"><i class="fa fa-comment"></i></span>
                                                                    <input type="text" class="form-control" name="smtp_name_xtd[0]" placeholder="<?php echo returnIntLang('editcon smtp name of connection', false); ?>" value="" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp host help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label">SMTP_HOST</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group form-group">
                                                                <span class="input-group-addon"><i class="fa fa-server"></i></span>
                                                                <input type="text" class="form-control" name="smtp_host_xtd[0]" id="smtp_host" placeholder="SMTP_HOST" value="" onchange="updateCon('consmtp_0', this.id, this.value);" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp username help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label">SMTP_USER</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group form-group">
                                                                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                                                <input type="text" required class="form-control" name="smtp_user_xtd[0]" id="smtp_user" placeholder="<?php echo returnIntLang('editcon smtp username', false); ?>" value="" onchange="updateCon('consmtp_0', this.id, this.value);" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('smtp pass help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label">SMTP_PASS</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group form-group">
                                                                <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
                                                                <input type="text" required class="form-control" name="smtp_pass_xtd[0]" id="smtp_pass" placeholder="SMTP_PASS" value="" onchange="updateCon('consmtp_0', this.id, this.value);" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp port help', false); ?>">
                                                        <label for="ticket-name" class="col-sm-3 control-label">SMTP_PORT</label>
                                                        <div class="col-sm-9">
                                                            <div class="input-group form-group">
                                                                <span class="input-group-addon"><i class="far fa-dot-circle"></i></span>
                                                                <input type="number" min="1" class="form-control" name="smtp_port_xtd[0]" id="smtp_port" placeholder="<?php echo returnIntLang('editcon smtp port', false); ?>" value="587" onchange="updateCon('consmtp_0', this.id, this.value);" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col-sm-9 col-md-offset-3">
                                                            <label class="fancy-checkbox custom-bgcolor-blue">
                                                                <input type="hidden" name="smtp_ssl_xtd[0]" value="0" /><input type="checkbox" id="smtp_ssl" name="smtp_ssl_xtd[0]" value="1" checked="checked" onchange="updateCon('consmtp_0', this.id, this.value);" ><span class="text-muted" data-toggle="tooltip" data-placement="top" data-original-title="<?php echo returnIntLang('editcon smtp ssl help', false); ?>">SMTP_SSL</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
<pre id="consmtp_0_preview">
define('SMTP_HOST', '' );
define('SMTP_USER', '' );
define('SMTP_PASS', '' );
define('SMTP_PORT', '' );
define('SMTP_SSL', true );
</pre>
                                                    <input type="hidden" id="consmtp_0_checked" class="con-checked" value="1" />
                                                    <p><a onclick="checkCon('smtp', 'consmtp_0');" class="btn btn-primary disabled"><?php echo returnIntLang('str check', false); ?></a> </p>
                                                </div>
                                            </div>
                                        </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <input type="hidden" name="action" id="con_check_action" value"" />    
                        </form>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <p><a href="#" id="finalcheck" onclick="$('#conform').submit();" class="btn btn-primary disabled"><?php echo returnIntLang('btn check and write config', false); ?></a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

function deleteCon(conTab) {
    if (confirm('really?')) {
        $('a[href="#' + conTab + '"]').closest('li').prev('li').children('a').tab('show');
        $('a[href="#' + conTab + '"]').closest('li').hide(500, function(){
            $('#' + conTab).html('');
        });
        window.localStorage.removeItem('editcontab');
        enablePost();
    }
}
    
function checkCon(conType, conTab) {
    var fieldname = new Array();
    var fielddata = new Array();
    $('#' + conTab).find('input').each(function(e) {
        if ($(this).attr('id')) {
            if ($(this).hasClass('con-checked')) {
                // ignore field
            } else if ($(this).attr('type')=='checkbox') {
                fielddata.push($(this).prop('checked'));
                fieldname.push($(this).attr('id'));
            } else {
                fielddata.push($(this).val());
                fieldname.push($(this).attr('id'));
            }
        }
    });
    
    $.post("xajax/ajax.checkcon.php", { 'checkType' : conType, 'checkName' : fieldname, 'checkData' : fielddata,  })
        .done (function(data) {
        
            console.log(data);
        
            var sd = JSON.parse(data);
            if (sd.success==1) {
                $('#' + conTab + '_checked').val(1);
                }
            else {
                alert (sd.msg);
                }
            })
    enablePost();
    
}

function enablePost() {
    var a=0, b=0;
    $('.con-checked').each(function() {
        if ($(this).val()==1) { a++; }
        b++;
    });
    if (a==b) { $('#finalcheck').removeClass('disabled'); $('#con_check_action').val('save');  }
}
    
function updateCon(conTab, conFieldID, conFieldValue) {
    var text = '';
    $('#finalcheck').addClass('disabled');
    $('#' + conTab + '_checked').val(0);
    $('#' + conTab + '_checked').siblings('p').find('a.btn.disabled').removeClass('disabled');
    $('#' + conTab).find('input').each(function(e) {
        if ($(this).attr('id')) {
            if ($(this).hasClass('con-checked')) {
                // ignore field
            } else if ($(this).attr('type')=='checkbox') {
                text+= 'define(\'' + ($(this).attr('id') + '').toUpperCase() + '\', ' + $(this).prop('checked') + " );\n";
            } else {
                text+= 'define(\'' + ($(this).attr('id') + '').toUpperCase() + '\', \'' + $(this).val() + "\' );\n";
            }
        }
    });
    $('#' + conTab + '_preview').text(text);
}
    
$(document).ready(function() { 
    
    $('a[data-toggle="tab"]').on('click', function(e) {
        window.localStorage.setItem('editcontab', $(e.target).attr('href'));
    });
    var editconTab = window.localStorage.getItem('editcontab');
    if (editconTab) {
        $('a[data-toggle="tab"]').parent('li').removeClass('active');
        $('.tab-pane.active').removeClass('active');
        $('a[href="' + editconTab + '"]').tab('show');
    }
    
});
    

</script>
<?php require ("data/include/footer.inc.php"); ?>