<?php
/**
 * Userverwaltung
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-11-04
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['menuposition'] = 'usermanagement';
$_SESSION['wspvars']['pagedesc'] = array('far fa-user',returnIntLang('menu manage'),returnIntLang('menu user managedata'));
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['mpos'] = 2;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false; // tells user, to save data before leaving page
$_SESSION['wspvars']['addpagecss'] = array(
    'bootstrap-multiselect.css'
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'bootstrap/bootstrap-multiselect.js'
    );
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
if (intval($_SESSION['wspvars']['usertype'])==1):
    $_SESSION['wspvars']['pagedesc'] = array('fa fa-cogs',returnIntLang('menu manage'),returnIntLang('menu user manage')); // 
endif;

// define page specific functions ------------
if (isset($_POST['self_data']) && $_POST['self_data']=='changeself') {
	if (trim($_POST['my_new_pass'])!="" && trim($_POST['my_act_pass'])=="") {
		addWSPMsg('noticemsg', returnIntLang('usermanagement confirm passchange with old password', true));
        }
	else if (trim($_POST['my_new_pass'])!="" && trim($_POST['my_act_pass'])!="") {
		$actuser_sql = "SELECT `pass` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
		$actuser_res = doSQL($actuser_sql);
        if (md5($_POST['my_act_pass'])==$actuser_res['set'][0]['pass']) {
            $sql = "UPDATE `restrictions` SET `pass` = '".md5($_POST['my_new_pass'])."' WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
            $res = doSQL($sql);
            if ($res['aff']) {
                addWSPMsg('resultmsg', returnIntLang('usermanagement password succesfully changed', true));
            }
            else {
                addWSPMsg('errormsg', returnIntLang('usermanagement password change db error', true));
            }
        }
        else {
            addWSPMsg('errormsg', returnIntLang('usermanagement false old pass', true));
        }
	}
    if (trim($_POST['my_new_realname'])=="") {
        addWSPMsg('errormsg', returnIntLang('usermanagement realname must be set', true));
    } else if (trim($_POST['my_new_realname'])!="" && trim($_POST['my_new_realname'])!=trim($_POST['my_act_realname'])) {
        $sql = "UPDATE `restrictions` SET `realname` = '".escapeSQL(trim($_POST['my_new_realname']))."' WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
        $res = doSQL($sql);
        if ($res['aff']==1) {
            addWSPMsg('resultmsg', returnIntLang('usermanagement realname was changed', true));
        }
    }
	if (isset($_POST['my_message_disable'])) {
		doSQL("UPDATE `restrictions` SET `disablenews` = ".intval($_POST['my_message_disable'])." WHERE `rid` = ".intval($_SESSION['wspvars']['userid']));
    	$_SESSION['wspvars']['disablenews'] = intval($_POST['my_message_disable']);
	}
	if (isset($_POST['my_save_session'])) {
		doSQL("UPDATE `restrictions` SET `saveprops` = ".intval($_POST['my_save_session'])." WHERE `rid` = ".intval($_SESSION['wspvars']['userid']));
		$_SESSION['wspvars']['saveprops'] = intval($_POST['my_save_session']);
	}
	if (isset($_POST['my_help_disable'])) {
		doSQL("UPDATE `restrictions` SET `disablehelp` = ".intval($_POST['my_help_disable'])." WHERE `rid` = ".intval($_SESSION['wspvars']['userid']));
		$_SESSION['wspvars']['disablehelp'] = intval($_POST['my_help_disable']);
	}

}

if (isset($_POST['op']) && $_POST['op']=="setfree" && is_array($_POST['setfree']) && count($_POST['setfree'])>0) {
	// check AGAIN, if this (admin) user has logged in
	// if he IS logged in, dont remove the admin option
	foreach ($_POST['setfree'] AS $sfk => $sfv) {
        // check to prevent self logout ;)
        if (intval($sfv)!=$_SESSION['wspvars']['userid']) {
            // find all `usevar` entries to this user
            $sql = "SELECT `usevar` FROM `security` WHERE `userid` = ".intval($sfv);
            $res = getResultSQL($sql);
            if (count($res)>0) {
                // if some usevars are found ..
                foreach($res AS $k => $v) {
                    // remove user directories (or try to) to prevent data storage
                    removeDir(str_replace("//","/",str_replace("//","/",WSP_DIR."/tmp/".str_replace("./", "/", str_replace("../", "/", $v)))));
                }
            }
            // remove entries from database
            $sql = "DELETE FROM `security` WHERE `userid` = ".intval($sfv);
            doSQL($sql);
        }
    }
}

if (isset($_POST['op']) && $_POST['op']=="au" && $_POST['id']>0 && $_POST['id']!=$_SESSION['wspvars']['userid']) {
	// check AGAIN, if this (admin) user has logged in
	// if admin IS logged in, dont remove the admin option
	$adminlogin_sql = "SELECT `sid` FROM `security` WHERE `userid` = ".intval($_POST['id']);
	$adminlogin_res = doSQL($adminlogin_sql);
	if ($adminlogin_res['num']==0) {
		$sql = "UPDATE `restrictions` SET `usertype` = 2 WHERE `rid` = ".intval($_POST['id']);
		doSQL($sql);
    }
	$op = "";
}

if (isset($_POST['op']) && isset($_POST['id']) && $_POST['op']=="ua" && $_POST['id']>0 && $_SESSION['wspvars']['usertype']==1) {
	// create admin
	$sql = "UPDATE `restrictions` SET `usertype` = 1, rights = '', idrights = '' WHERE `rid` = '".intval($_POST['id'])."'";
	doSQL($sql);
}

if (isset($_POST['op']) && isset($_POST['id']) && $_POST['op']=="hy" && $_POST['id']>0 && $_SESSION['wspvars']['usertype']==1) {
	header('location: userhistory.php');
}

if (isset($_POST['op']) && isset($_POST['user_exist']) && (intval($_POST['user_exist']) > 0) && ($_POST['op'] == "ud")) {
	$sql = "DELETE FROM `restrictions` WHERE `rid` = ".intval($_POST['user_exist'])." && `usertype` != 1";
	$res = doSQL($sql);
	if ($res['aff']==1) {
        addWSPMsg('resultmsg', "<p>".returnIntLang('usermanagement account deleted', true)."</p>");
    }
	$op = "";
}

if (isset($_POST['op']) && isset($_POST['id']) && (intval($_POST['id']) > 0) && ($_POST['op'] == "us")) {
	$sql = "UPDATE `restrictions` SET `usertype` = 0 WHERE `rid`='".intval($_POST['id'])."' && `usertype` != 1";
	$res = doSQL($sql);
	if ($res['aff']==1) {
        addWSPMsg('noticemsg', "<p>".returnIntLang('usermanagement account deactivated', true)."</p>");
    }
	$op = "";
}

if (isset($_POST['op']) && isset($_POST['id']) && (intval($_POST['id']) > 0) && ($_POST['op'] == "uw")) {
	$sql = "UPDATE `restrictions` SET `usertype` = 2 WHERE `rid`='".intval($_POST['id'])."' && `usertype` != 1";
	$res = doSQL($sql);
	if ($res['aff']==1) {
        addWSPMsg('resultmsg', "<p>".returnIntLang('usermanagement account activated', true)."</p>");
    }
	$op = "";
}

if (isset($_POST['op']) && isset($_POST['user_data']) && $_POST['op']=="user_new" && trim($_POST['new_username'])!="") {
	if ($_POST['new_position']=="") {
		$_POST['new_position'] = "undefined";
	}
	$checkforusername = doResultSQL("SELECT `rid` FROM `restrictions` WHERE `user` LIKE '".escapeSQL(strtolower($_POST['new_username']))."'");
	if (!($checkforusername)) {
		if ($_POST['new_position']=="admin") {
			$sql = "INSERT INTO `restrictions` SET `usertype` = 1, `user` = '".escapeSQL(trim($_POST['new_username']))."', `realname` = '".escapeSQL(trim($_POST['new_realname']))."', `realmail` = '".escapeSQL(trim($_POST['new_email']))."', rights = '', idrights = ''";
			$res = doSQL($sql);
			if ($res['aff']==1 && intval($res['inf'])>0) {
				$_SESSION['wspvars']['hiddengetvars'] = array('userrid' => intval($res['inf']));
				header ("location: useredit.php");
				die();
            }
        }
        else if ($_POST['new_position']=="webuser") {
            $sql = "INSERT INTO `restrictions` SET `usertype` = 22, `user` = '".escapeSQL(trim($_POST['new_username']))."', `realname` = '".escapeSQL(trim($_POST['new_username']))."', `realmail` = '".md5(trim($_POST['new_email']))."', rights = '', idrights = ''";
			$res = doSQL($sql);
			if ($res['aff']==1 && intval($res['inf'])>0) {
                addWSPMsg('noticemsg', returnIntLang('usermanagement webuser created', false));
            }
        }
		else {
			$addsql = "";
			if (intval($_POST['new_position'])>0):
				// clone rights from given user
				$clone_sql = "SELECT `rights`, `idrights` FROM `restrictions` WHERE `rid` = ".intval($_POST['new_position']);
				$clone_res = doSQL($clone_sql);
				if ($clone_res['num']>0):
					$addsql = " , `rights` = '".escapeSQL(serialize(unserializeBroken($clone_res['set'][0]['rights'])))."', `idrights` = '".escapeSQL(serialize(unserializeBroken($clone_res['set'][0]['idrights'])))."' ";
				endif;
			endif;
			$sql = "INSERT INTO `restrictions` SET `usertype` = 2, `user` = '".escapeSQL(trim($_POST['new_username']))."', `realname` = '".escapeSQL(trim($_POST['new_realname']))."', `realmail` = '".escapeSQL(trim($_POST['new_email']))."' ".$addsql;
            $res = doSQL($sql);
            if ($res['aff']==1 && intval($res['inf'])>0):
				$_SESSION['wspvars']['hiddengetvars'] = array('userrid' => intval($res['inf']));
				header ("location: useredit.php");
				die();
			endif;
		}
    }
	else {
		addWSPMsg('errormsg', returnIntLang('usermanagement username already used', false));
	}
	$op = "";
}

// head of file - first regular output -------
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
                <h1 class="page-title"><?php echo returnIntLang('usermanagement headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('usermanagement userlegend'); ?> <?php if($_SESSION['wspvars']['usertype']==1): ?><?php echo returnIntLang('usermanagement adminlegend'); ?><?php endif; ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php if (intval($_SESSION['wspvars']['usertype'])==1): ?>
                <!-- admin panel -->
                <div class="row">
                    <div class="col-md-6">
                        <?php require ("./data/panels/usermanagement.change.inc.php"); ?>
                    </div>
                    <div class="col-md-6">
                        <?php require ("./data/panels/usermanagement.create.inc.php"); ?>
                    </div>
                </div>
                <?php 
	
                $usercheck_sql = "SELECT * FROM `restrictions` WHERE `rid` != ".intval($_SESSION['wspvars']['userid'])." ORDER BY `user` ASC";
                $usercheck_res = doSQL($usercheck_sql);

                if ($usercheck_res['num']>0):
                ?>
                <div class="row">
                    <div class="col-md-12 col-lg-9">
                        <?php require ("./data/panels/usermanagement.showuser.inc.php"); ?>
                    </div>
                    <div class="col-md-12 col-lg-3">
                        <?php require ("./data/panels/usermanagement.lastlogs.inc.php"); ?>
                    </div>
                </div>
                <script type="text/javascript">
                <!--
                function checkUserDel(delUser, userName) {
                    checkDel = confirm ('<?php echo returnIntLang('usermanagement confirmdelete1', false); ?> "' + userName + '" <?php echo returnIntLang('usermanagement confirmdelete2', false); ?>');
                    if (checkDel) {
                        document.getElementById(delUser).submit();
                        }
                    }

                function checkUserInactive(inactiveUser, userName) {
                    checkInactive = confirm ('<?php echo returnIntLang('usermanagement confirmdeactivate1', false); ?> "' + userName + '" <?php echo returnIntLang('usermanagement confirmdeactivate2', false); ?>');
                    if (checkInactive) {
                        document.getElementById(inactiveUser).submit();
                        }
                    }

                function checkUserActive(activeUser, userName) {
                    checkActive = confirm ('<?php echo returnIntLang('usermanagement confirmactivate1', false); ?> "' + userName + '" <?php echo returnIntLang('usermanagement confirmactivate2', false); ?>');
                    if (checkActive) {
                        document.getElementById(activeUser).submit();
                        }
                    }
                -->
                </script>
                <?php endif; ?>
            <?php else: ?>
                <!-- user panel -->
                <div class="row">
                    <?php require ("./data/panels/usermanagement.change.inc.php"); ?>
                </div>
            <?php endif; ?>
		
            </div>
        </div>
    <!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->
<script>
    
$(document).ready(function() { 
    $('.singleselect').multiselect();
    });

</script>

<?php require ("./data/include/footer.inc.php"); ?>