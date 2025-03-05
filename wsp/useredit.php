<?php
/**
 * Userdaten bearbeiten
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-06-18
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = ''; // beschreibt als string, ob hier eine rechte-sperre vorliegt
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-user',returnIntLang('menu manage'),returnIntLang('menu user managedata'));
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false; 
$_SESSION['wspvars']['preventleave'] = true;
$_SESSION['wspvars']['addpagecss'] = array(
    'bootstrap-multiselect.css'
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'bootstrap/bootstrap-multiselect.js'
    );
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$rights = array('siteprops' => 0, 'sitestructure' => 0, 'design' => 0, 'contents' => 0, 'publisher' => 0, 'imagesfolder' => 0, 'downloadfolder' => 0, 'mediafolder' => 0);
/* define page specific funcs ---------------- */
if (isset($_POST['userrid']) && intval($_POST['userrid'])>0):
	$_SESSION['wspvars']['hiddengetvars']['userrid'] = intval($_POST['userrid']);
endif;
if (intval($_SESSION['wspvars']['usertype'])==1):
    $_SESSION['wspvars']['pagedesc'] = array('fa fa-cogs',returnIntLang('menu manage'),returnIntLang('menu user manage')); // 
endif;

// setup userdata from saved preference groups
if (isset($_POST['predefined']) && trim($_POST['predefined'])!="undefined"):
	if (trim($_POST['predefined'])=="developer"):
		$_POST['changerights'] = $rights = array(
            'siteprops' => 1, 'sitestructure' => 1, 'design' => 1, 'contents' => 1, 'publisher' => 1, 'imagesfolder' => '/', 'downloadfolder' => '/', 'mediafolder' => '/');
	elseif (trim($_POST['predefined'])=="technics"):
        $_POST['changerights'] = $rights = array(
            'siteprops' => 0, 'sitestructure' => 0, 'design' => 1, 'contents' => 0, 'publisher' => 0, 'imagesfolder' => '/', 'downloadfolder' => 0, 'mediafolder' => 0);
	elseif (trim($_POST['predefined'])=="seo"):
        $_POST['changerights'] = $rights = array(
            'siteprops' => 1, 'sitestructure' => 3, 'design' => 0, 'contents' => 0, 'publisher' => 0, 'imagesfolder' => '/', 'downloadfolder' => 0, 'mediafolder' => 0);
	elseif (trim($_POST['predefined'])=="redaktion"):
		$_POST['changerights'] = $rights = array(
            'siteprops' => 0, 'sitestructure' => 1, 'design' => 0, 'contents' => 1, 'publisher' => 1, 'imagesfolder' => '/', 'downloadfolder' => '/', 'mediafolder' => 0);
    endif;
endif;

if (isset($_POST['change_username']) && isset($_POST['change_realname']) && isset($_POST['change_realmail']) &&  trim($_POST['change_username'])!="" && trim($_POST['change_realname'])!="" && trim($_POST['change_realmail'])!="" && intval($_SESSION['wspvars']['hiddengetvars']['userrid'])>0):
	$changename = false;
    $username_sql = "SELECT `user`, `usertype` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['hiddengetvars']['userrid']);
	$username_res = doSQL($username_sql);
    if ($username_res['num'] > 0 && $username_res['set'][0]['user']!=trim($_POST['change_username'])):
		$doublename_sql = "SELECT `rid` FROM `restrictions` WHERE `user` = '".escapeSQL(trim($_POST['change_username']))."'";
		$doublename_res = getNumSQL($doublename_sql);
		if ($doublename_res>0):
			$_POST['change_username'] = $username_res['set'][0]['user'];
			addWSPMsg('errormsg', 'usermanagement choosen username is already in use');
		else:
			$changename = true;
		endif;
	endif;
    
	$sql = "UPDATE `restrictions` SET ";
    // update user name
    if ($changename): $sql.= " `user` = '".escapeSQL(trim($_POST['change_username']))."', "; endif;
	// update password
	if (intval($_POST['change_password'])==1 && trim($_POST['set_newpass'])!=""): $sql.= " `pass` = '".escapeSQL(md5(trim($_POST['set_newpass'])))."', "; endif;
	// update personal data
	$sql.= " `realname` = '".escapeSQL(trim($_POST['change_realname']))."', ";
    $sql.= " `realmail` = '".escapeSQL(trim($_POST['change_realmail']))."' ";
	$sql.= " WHERE `rid` = ".intval($_SESSION['wspvars']['hiddengetvars']['userrid']);
    $res = doSQL($sql);
    // notice of successful update of user rights
    if ($res['aff']==1): addWSPMsg('noticemsg', returnIntLang('usermanagement userinfo updated', false)); endif;

	if (intval($username_res['set'][0]['usertype'])!=1):
        // define empty arrays
        $changerights = array(); // holds rights and modular rights
        $changeidrights = array(); // holds mid's for structure, contents and or publisher
        // start sql statement
        $sql = "UPDATE `restrictions` SET ";
        //
		// save rights for non-admin-users
        
		foreach ($rights AS $key => $value): 
			$changerights[$key] = $_POST['changerights'][$key];
            if (intval($_POST['changerights'][$key])==2 || 
                intval($_POST['changerights'][$key])==4 || 
                intval($_POST['changerights'][$key])==7):
                if (isset($_POST['changeidrights']) && isset($_POST['changeidrights'][$key]) && count($_POST['changeidrights'][$key])>0):
                    $changeidrights[$key] = $_POST['changeidrights'][$key];
                else:
                    $changerights[$key] = 0;
                endif;
            endif;
            if (intval($_POST['changerights'][$key])==12 || 
                intval($_POST['changerights'][$key])==15):
                if (count($_POST['changeidrights']['sitestructure'])>0):
                    $changeidrights[$key] = $_POST['changeidrights']['sitestructure'];
                else:
                    $changerights[$key] = 0;
                endif;
            endif;
		endforeach;
		
		// save modular rights
		$modrights_sql = "SELECT * FROM `wsprights`";
		$modrights_res = doSQL($modrights_sql);
		if ($modrights_res['num']>0):
            foreach ($modrights_res['set'] AS $mrsk => $mrsv):
                $modrights = unserializeBroken($mrsv['options']);
                if (isset($_POST[$mrsv['guid']])):
                    $changerights[$mrsv['guid']] = intval($_POST[$mrsv['guid']]);
                endif;
            endforeach;
        endif;
        
        /*
        echo "<pre>".var_export($changerights, true)."</pre>";
        echo "<pre>".var_export($changeidrights, true)."</pre>";
        */

		// zusammenfassung aller rechte, sowie der menuids in einem serialisierten array und ab damit ...
		$sql .= " `rights` = '".serialize($changerights)."', idrights = '".serialize($changeidrights)."' ";
        // finish sql-statement
        $sql .= " WHERE `rid` = ".intval($_SESSION['wspvars']['hiddengetvars']['userrid']);
        $res = doSQL($sql);
        // notice of successful update of user rights
        if ($res['aff']==1): addWSPMsg('noticemsg', returnIntLang('usermanagement userrights updated', false)); endif;
	endif;
	
    if (intval($_POST['change_password'])==1 && intval($_POST['email_password'])==1 && trim($_POST['set_newpass'])!=""):
		
        $domain_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'siteurl'";
        $domain_res = mysql_query($domain_sql);
        if ($domain_res):
            $domain_num = mysql_num_rows($domain_res);
        endif;
        if ($domain_num>0):
            $domain = trim(mysql_result($domain_res, 0));
        else:
            $domain = $_SERVER['HTTP_HOST'];
        endif;	
        $domain = str_replace("www.","",$domain);
        $domain = str_replace("http://","",$domain);

        mail($_POST['change_realmail'],
		returnIntLang('mailtemplate account created or changed', false),
		returnIntLang('mailtemplate your account to', false)." '".$domain."' ".returnIntLang('mailtemplate was created or updated', false).".\n".
		returnIntLang('mailtemplate your account data', false).":\n\n".
		returnIntLang('mailtemplate your account username', false).": ".$_POST['change_username']."\n".
		returnIntLang('mailtemplate your account password', false).": ".trim($_POST['set_newpass'])."\n\n".
		returnIntLang('mailtemplate your account login page', false)." http://www.".$domain."/".$wspvars['wspbasedir']."/\n",
		"From: wsp@".$domain."\n");

        addWSPMsg('noticemsg', returnIntLang('usermanagement passwordmail sent', false));     

	endif;
endif;

$userinfo_sql = "SELECT * FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['hiddengetvars']['userrid'])." AND `rid` != ".intval($_SESSION['wspvars']['userid']);
$userinfo_res = doSQL($userinfo_sql);
if ($userinfo_res['num']!=0):
	if (trim($userinfo_res['set'][0]['pass'])==""):
		addWSPMsg('noticemsg', returnIntLang('rights no password sent', true));
		$checkpass = true;
	endif;
endif;

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/navbar.inc.php");
include ("./data/include/sidebar.inc.php");
?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('rights headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('rights info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php if ($userinfo_res['num']!=0): ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" id="frmuseredit" method="post">
            <?php
	
            $saved_usertype = intval($userinfo_res['set'][0]['usertype']);
            $saved_username = trim($userinfo_res['set'][0]['user']);
            $saved_password = trim($userinfo_res['set'][0]['pass']);
            $saved_realname = trim($userinfo_res['set'][0]['realname']);
            $saved_realmail = trim($userinfo_res['set'][0]['realmail']);
            $saved_rights = unserializeBroken($userinfo_res['set'][0]['rights']);
            $saved_idrights = unserializeBroken($userinfo_res['set'][0]['idrights']);
            $rights = $saved_rights;
                
            ?>
            <div class="row">
                <?php require ("./data/panels/useredit.data.inc.php"); ?>
            </div>
            <?php 
            
            // 1 is admin
            // 22 is webuser
                
            if($saved_usertype!=1 && $saved_usertype!=22) { ?>
                <div class="row">
                    <div class="col-md-12">
                        <?php require ("./data/panels/useredit.rights.inc.php"); ?>
                    </div>
                </div>
                <?php $modrights_res = doSQL("SELECT * FROM `wsprights`"); if ($modrights_res['num']>0) { ?>
                    <div class="row">
                        <?php require ("./data/panels/useredit.modrights.inc.php"); ?>
                    </div>
                <?php } ?>
            <?php } ?>
	   <?php endif; ?>
                
        <?php if ($userinfo_res['num']!=0): ?><p><a href="#" onclick="valiData(); return false;" class="btn btn-primary"><?php echo returnIntLang('str save', false); ?></a> <a href="usermanagement.php" class="btn btn-warning"><?php echo returnIntLang('str back', false); ?></a></p><input type="hidden" name="userrid" value="<?php echo intval($_SESSION['wspvars']['hiddengetvars']['userrid']); ?>" /></form>
        <?php else: ?>
            <fieldset class="errormsg"><p><?php echo returnIntLang('rights noaccess'); ?></p></fieldset>
            <p><a href="usermanagement.php" class="btn btn-warning"><?php echo returnIntLang('str back', false); ?></a></p><input type="hidden" name="userrid" value="<?php echo intval($_SESSION['wspvars']['hiddengetvars']['userrid']); ?>" />
        <?php endif; ?>    
            </div>
        </div>
    </div>
</div>
    
<script type="text/javascript" language="javascript">
<!--
    
function valiData() {
    if (document.getElementById('change_username').value == '') {
        alert('Bitte geben Sie einen Usernamen ein!');
        document.getElementById('change_username').focus();
        return false;
    }	// if
    if (document.getElementById('change_realname').value == '') {
        alert('Bitte geben Sie die Anrede für den Nutzer ein!');
        document.getElementById('change_realname').focus();
        return false;
    }	// if
    if (document.getElementById('change_realmail').value == '') {
        alert('Bitte geben Sie die eMail-Adresse des Nutzers ein!');
        document.getElementById('change_realmail').focus();
        return false;
    }	// if

    document.getElementById('frmuseredit').submit();
}	// valiData()

//-->
</script>

<?php require ("./data/include/footer.inc.php"); ?>