<?php
/**
 * Userverwaltung
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
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */
$op = checkParamVar('op', '');
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['menuposition'] = 'usermanagement';
$_SESSION['wspvars']['mgroup'] = 2;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false; // tells user, to save data before leaving page
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

/* define page specific functions ------------ */
// update own password
if (isset($_POST['self_data'])) {
	if (trim($_POST['my_new_pass'])!="" && trim($_POST['my_act_pass'])==""):
		addWSPMsg('noticemsg', returnIntLang('usermanagement confirm passchange with old password', true));
	elseif (trim($_POST['my_new_pass'])!="" && trim($_POST['my_act_pass'])!=""):
		$actuser_sql = "SELECT `pass` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
		$actuser_res = doResultSQL($actuser_sql);
		if ($actuser_res!==false) {
            if (md5($_POST['my_act_pass'])==$actuser_res) {
                $sql = "UPDATE `restrictions` SET `pass` = '".md5($_POST['my_new_pass'])."' WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
                $res = doSQL($sql);
                if ($res['aff']==1) {
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
	endif;
	if (intval($_POST['my_message_disable'])==1) {
		$sql = "UPDATE `restrictions` SET `disablenews` = 1 WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
		doSQL($sql);
		$_SESSION['wspvars']['disablenews'] = 1;
	}
    else {
		$sql = "UPDATE `restrictions` SET `disablenews` = 0 WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
		doSQL($sql);
		$_SESSION['wspvars']['disablenews'] = 0;
	}
	if (intval($_POST['my_save_session'])==1) {
		$sql = "UPDATE `restrictions` SET `saveprops` = 1 WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
		doSQL($sql);
		$_SESSION['wspvars']['saveprops'] = 1;
    }
	else {
		$sql = "UPDATE `restrictions` SET `saveprops` = 0 WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
		doSQL($sql);
		$_SESSION['wspvars']['saveprops'] = 0;
	}
}
// convert admin to user
if (isset($_POST['op']) && isset($_POST['id']) && $_POST['op']=="au" && $_POST['id']>0 && intval($_POST['id'])!=intval($_SESSION['wspvars']['userid'])) {
	// check AGAIN, if this (admin) user has logged in
	// if he IS logged in, dont remove the admin option
	$adminlogin_sql = "SELECT `sid` FROM `security` WHERE `userid` = ".intval($_POST['id']);
	$adminlogin_res = doResultSQL($adminlogin_sql);
	if ($adminlogin_res===false) {
		$sql = "UPDATE `restrictions` SET `usertype` = 2 WHERE `rid` = ".intval($_POST['id']);
		doSQL($sql);
    }
	$op = "";
}
// convert user to admin 
if (isset($_POST['op']) && isset($_POST['id']) && $_POST['op']=="ua" && $_POST['id']>0 && $_SESSION['wspvars']['usertype']==1) {
	// zuweisung ALLER rechte fuer den admin
	for ($r=0;$r<count($_SESSION['wspvars']['rightabilities']);$r++) {
		$changerights[$_SESSION['wspvars']['rightabilities'][$r]] = 1;
	}
	$sql = "UPDATE `restrictions` SET `usertype` = 1, rights = '".serialize($changerights)."', idrights = '' WHERE `rid` = ".intval($_POST['id']);
	doSQL($sql);
}
// goto history
if (isset($_POST['op']) && isset($_POST['id']) && $_POST['op']=="hy" && $_POST['id']>0 && $_SESSION['wspvars']['usertype']==1) {
	header('location: userhistory.php');
}
// delete user
if (isset($_POST['user_exist']) && (intval($_POST['user_exist']) > 0) && ($op == "ud")) {
	$sql = "DELETE FROM `restrictions` WHERE `rid` = ".intval($_POST['user_exist'])." && `usertype` != 1";
	doSQL($sql);
	addWSPMsg('resultmsg', "<p>".returnIntLang('usermanagement account deleted', true)."</p>");
	$op = "";
}
// disable user
if (isset($_POST['id']) && (intval($_POST['id']) > 0) && ($op == "us")) {
	$sql = "UPDATE `restrictions` SET `usertype` = 0 WHERE `rid` = ".intval($_POST['id'])." && `usertype` != 1";
	doSQL($sql);
	addWSPMsg('noticemsg', "<p>".returnIntLang('usermanagement account deactivated', true)."</p>");
	$op = "";
}
// activate user
if (isset($_POST['id']) && (intval($_POST['id']) > 0) && ($op == "uw")) {
	$sql = "UPDATE `restrictions` SET `usertype` = 2 WHERE `rid` = ".intval($_POST['id'])." && `usertype` != 1";
	doSQL($sql);
	addWSPMsg('resultmsg', "<p>".returnIntLang('usermanagement account activated', true)."</p>");
	$op = "";
}
// create new user
if (isset($_POST['user_data']) && $op=="user_new" && $_POST['new_username']!="" && $_POST['new_realname']!="" && $_POST['new_email']!="") {
	if ($_POST['new_position']=="") { $_POST['new_position'] = "undefined"; }
	$newuser_sql = "SELECT `rid` FROM `restrictions` WHERE `user` LIKE '".$_POST['new_username']."'";
	$newuser_res = doSQL($newuser_sql);
	if ($newuser_res['num']==0):
		if ($_POST['new_position']=="admin"):
			for ($r=0;$r<count($_SESSION['wspvars']['rightabilities']);$r++):
				$changerights[$_SESSION['wspvars']['rightabilities'][$r]] = 1;
			endfor;
			$sql = "INSERT INTO `restrictions` SET `usertype` = 1, `user` = '".escapeSQL($_POST['new_username'])."', `realname` = '".escapeSQL($_POST['new_realname'])."', `realmail` = '".escapeSQL($_POST['new_email'])."', rights = '".escapeSQL(serialize($changerights))."', idrights = ''";
			$res = doSQL($sql);
			$_SESSION['wspvars']['hiddengetvars'] = array(
				'userrid' => $res['inf'],
				);
			header ("location: useredit.php");
			die();
		else:
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
			if ($res['inf']>0):
				$_SESSION['wspvars']['hiddengetvars'] = array('userrid' => $res['inf']);
				header ("location: useredit.php");
				die();
			else:
				addWSPMsg('errormsg', returnIntLang('usermanagement user could not be created', false));
			endif;
		endif;
	else:
		addWSPMsg('errormsg', returnIntLang('usermanagement username already used', false));
	endif;
	$op = "";
}

// head der datei

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");
?>

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('usermanagement headline'); ?></h1></fieldset>
	<fieldset>
		<legend><?php echo returnIntLang('str legend'); ?> <span class="opencloseButton bubblemessage" rel="wsplegend">↕</span></legend>
		<div id="wsplegend" style="<?php echo isset($_SESSION['opentabs']['wsplegend']) ? $_SESSION['opentabs']['wsplegend'] : ''; ?>">
			<p><?php echo returnIntLang('usermanagement userlegend'); ?> <?php if($_SESSION['wspvars']['usertype']==1): ?><?php echo returnIntLang('usermanagement adminlegend'); ?><?php endif; ?></p>
		</div>
	</fieldset>
	
	<fieldset id="ownpass">
		<?php
		$userdata_sql = "SELECT `user` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
		$userdata_res = doResultSQL($userdata_sql);
		if ($userdata_res!==false) {
			$my_act_username = $userdata_res;
		}
		?>
		<script language="javascript" type="text/javascript"><!--

		function checklengthpass(){
			if (document.getElementById('my_new_pass').value.length>7 || document.getElementById('my_new_pass').value.length==0) {
				document.getElementById('frmuseredit').submit(); return false;
				} 
			else {
				alert("Das Passwort muss min. 8 Zeichen enthalten");
				}
			}
			
		--></script>
		<legend><?php echo returnIntLang('usermanagement changeuserdata1'); ?> '<? echo $my_act_username; ?>' <?php echo returnIntLang('usermanagement changeuserdata2'); ?> <span class="opencloseButton bubblemessage" rel="fieldset_ownpass_content">↕</span></legend>
		<div id="fieldset_ownpass_content" style="<?php echo isset($_SESSION['opentabs']['fieldset_ownpass_content']) ? $_SESSION['opentabs']['fieldset_ownpass_content'] : ''; ?>"><form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="frmuseredit">
		<table class="tablelist">
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('usermanagement oldpass'); ?></td>
				<td class="tablecell two"><input name="my_act_pass" type="password" class="one full"></td>
				<td class="tablecell two"><?php echo returnIntLang('usermanagement newpass'); ?></td>
				<td class="tablecell two"><input id="my_new_pass" name="my_new_pass" type="text" class="one full" /></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('usermanagement disable mailmessage'); ?></td>
				<td class="tablecell two"><input name="my_message_disable" type="hidden" value="0" /><input name="my_message_disable" type="checkbox" value="1" <?php if(isset($_SESSION['wspvars']['disablenews']) && $_SESSION['wspvars']['disablenews']==1) echo "checked='checked'"; ?> />&nbsp;</td>
				<td class="tablecell two"><?php echo returnIntLang('usermanagement save session'); ?></td>
				<td class="tablecell two"><input name="my_save_session" type="hidden" value="0" /><input name="my_save_session" type="checkbox" value="1" <?php if(isset($_SESSION['wspvars']['saveprops']) && $_SESSION['wspvars']['saveprops']==1) echo "checked='checked'"; ?> />&nbsp;</td>
			</tr>
		</table>
		<input type="hidden" name="self_data" value="changepass" />
		</form>
		<fieldset class="options innerfieldset">
			<p><a href="#" onclick="checklengthpass();" class="greenfield"><?php echo returnIntLang('usermanagement updateprops'); ?></a></p>
		</fieldset>
		</div>
	</fieldset>
	<?php if ($_SESSION['wspvars']['usertype']==1):
	
	$usercheck_sql = "SELECT * FROM `restrictions` WHERE `rid` != ".intval($_SESSION['wspvars']['userid'])." ORDER BY `user` ASC";
	$usercheck_res = doSQL($usercheck_sql);

	if ($usercheck_res['num']>0):
	?>
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
	
	<fieldset>
		<legend><?php echo returnIntLang('usermanagement manageexisting'); ?> <span class="opencloseButton bubblemessage" rel="fieldset_existinguser_content">↕</span></legend>
		<div id="fieldset_existinguser_content" style="<?php echo isset($_SESSION['opentabs']['fieldset_existinguser_content']) ? $_SESSION['opentabs']['fieldset_existinguser_content'] : ''; ?>">
		<table class="tablelist">
			<tr>
				<td class="tablecell two head"><?php echo returnIntLang('str user'); ?></td>
				<td class="tablecell two head"><?php echo returnIntLang('usermanagement regmail'); ?></td>
				<td class="tablecell two head"><?php echo returnIntLang('str rights'); ?></td>
				<td class="tablecell two head"><?php echo returnIntLang('str action'); ?></td>
			</tr>
			<?php
			$r=0; 
            foreach ($usercheck_res['set'] AS $ucrk => $ucrv) {
				$userprops = "";
				$userrights = unserialize($ucrv['rights']);
				$modicon = false;
				if ($ucrv['rights']!=""):
					$systemrights = array();
					foreach ($_SESSION['wspvars']['rightabilityarray'] AS $key => $value):
						$systemrights[] = $key;
					endforeach;
					foreach ($userrights as $key => $value):
						if (in_array($key, $systemrights)):
							if ($value!=0):
								$userprops .= "<span class=\"bubblemessage\">".strtoupper($key)."</span> ";
							endif;
						else:
							if ($value==0):
								$plgdesc_num = 0;
								$plgdesc_sql = "SELECT `pluginname` FROM `wspplugins` WHERE `guid` = '".$key."'";
								$plgdesc_res = doSQL($plgdesc_sql);
								if ($plgdesc_res['num']>0):
									$userprops .= "<span class=\"bubblemessage orange\">".strtoupper($plgdesc_res['set'][0]['pluginname'])."</span> ";
								else:
									$moddesc_num = 0;
									$moddesc_sql = "SELECT `right` FROM `wsprights` WHERE `guid` = '".$key."'";
									$moddesc_res = doSQL($moddesc_sql);
									if ($moddesc_res['num']>0):
										$userprops .= "<span class=\"bubblemessage\">".strtoupper($moddesc_res['set'][0]['right'])."</span> ";
									endif;
								endif;
							endif;
						endif;
					endforeach;
				endif;
				if ($ucrv["usertype"]==1):
					echo "<tr>";
					echo "<td class=\"tablecell two\"><a href=\"#\" onClick=\"document.getElementById('edituser_".$ucrv["rid"]."').submit();\" title=\"".setUTF8($ucrv["realname"])."\">";
					echo setUTF8($ucrv["realname"]);
					echo " [".setUTF8($ucrv["user"])."]";
					echo "</a></td>";
					echo "<td class=\"tablecell two\">".$ucrv["realmail"];
					echo "<form action=\"useredit.php\" method=\"post\" id=\"edituser_".$ucrv['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"userrid\" value=\"".$ucrv['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"predefined\" value=\"\">\n";
					echo "</form>\n";
					echo "</td>\n";
					echo "<td class=\"tablecell two\">".$userprops."&nbsp;</td>";
					echo "<td class=\"tablecell two\">";
					$adminlogin_sql = "SELECT `sid` FROM `security` WHERE `userid` = '".$ucrv['rid']."'";
					$adminlogin_res = doResultSQL($adminlogin_sql);
					if ($adminlogin_res===false):
						echo "<a href=\"#\" onClick=\"document.getElementById('removeadmin_".$ucrv["rid"]."').submit();\"><span class=\"bubblemessage red strike\">ADMIN</span></a> ";
					endif;
					echo "<a href=\"#\" onClick=\"document.getElementById('userhistory_".$ucrv["rid"]."').submit();\"><span class=\"bubblemessage orange\">".strtoupper(returnIntLang('bubble history'))."</span></a>";
					if ($adminlogin_res===false):
						echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"removeadmin_".$ucrv['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
						echo "<input type=\"hidden\" name=\"id\" value=\"".$ucrv['rid']."\">\n";
						echo "<input type=\"hidden\" name=\"op\" value=\"au\">\n";
						echo "</form>\n";
					endif;
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"userhistory_".$ucrv['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$ucrv['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"hy\">\n";
					echo "</form>\n";
					echo "</td>";
					echo "</tr>";
				else:
					echo "<tr>";
					echo "<td class=\"tablecell two\"><a href=\"#\" onClick=\"document.getElementById('edituser_".$ucrv["rid"]."').submit();\" title=\"".setUTF8($ucrv["realname"])."\">";
					if ($ucrv['usertype']==0): echo "<span style=\"text-decoration: line-through\">"; endif;
					echo setUTF8($ucrv["realname"])." [".setUTF8($ucrv["user"])."]";
					if ($ucrv['usertype']==0): echo "</span>"; endif;
					echo "</a></td>";
					echo "<td class=\"tablecell two\">";
					if ($ucrv['usertype']==0): echo "<span style=\"text-decoration: line-through\">"; endif;
					echo $ucrv["realmail"];
					if ($ucrv['usertype']==0): echo "</span>"; endif;
					echo "<form action=\"useredit.php\" method=\"post\" id=\"edituser_".$ucrv['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"userrid\" value=\"".$ucrv['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"predefined\" value=\"\">\n";
					echo "</form>\n";
					
					echo "</td>\n";
					echo "<td class=\"tablecell two\">";
					if ($ucrv['usertype']!=0): echo $userprops; endif;
					echo "&nbsp;</td>\n";
					echo "<td class=\"tablecell two\">\n";
					if ($ucrv['usertype']!=0):
						echo "<a href=\"#\" onClick=\"document.getElementById('makeadmin_".$ucrv["rid"]."').submit();\"><span class=\"bubblemessage green\">&#8594; ADMIN</span></a> ";
					endif;
					echo "<a href=\"#\" onClick=\"document.getElementById('userhistory_".$ucrv["rid"]."').submit();\"><span class=\"bubblemessage orange\">".returnIntLang('bubble history')."</span></a> ";
					if ($ucrv['usertype']!=0):
						echo "<a href=\"#\" onClick=\"checkUserInactive('inactiveuser_".$ucrv['rid']."','".$ucrv['realname']."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble deactivate')."</span></a> ";
					elseif ($ucrv['usertype']==0):
						echo "<a href=\"#\" onClick=\"checkUserActive('activeuser_".$ucrv['rid']."','".$ucrv['realname']."');\"><span class=\"bubblemessage green\">".returnIntLang('bubble activate')."</span></a> ";
					endif;
					echo "<a href=\"#\" onClick=\"checkUserDel('deluser_".$ucrv['rid']."','".$ucrv['realname']."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete')."</span></a>";
					
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"deluser_".$ucrv['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"usevar\" value=\"".$_SESSION['wspvars']['usevar']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"ud\">\n";
					echo "<input type=\"hidden\" name=\"user_exist\" value=\"".$ucrv['rid']."\">\n";
					echo "</form>\n";
					
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"activeuser_".$ucrv['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$ucrv['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"uw\">\n";
					echo "</form>\n";
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"inactiveuser_".$ucrv['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$ucrv['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"us\">\n";
					echo "</form>\n";
					
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"makeadmin_".$ucrv['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$ucrv['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"ua\">\n";
					echo "</form>\n";
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"userhistory_".$ucrv['rid']."\" style=\"margin: 0px; padding: 0px;\">\n";
					echo "<input type=\"hidden\" name=\"id\" value=\"".$ucrv['rid']."\">\n";
					echo "<input type=\"hidden\" name=\"op\" value=\"hy\">\n";
					echo "</form>\n";
					echo "</td>\n";
					echo "</tr>";
				endif;
            }
			?>
		</table></div>
	</fieldset>
	<?php endif; ?>

	<script type="text/javascript">
	<!--
	function checkForUnixNames(givenValue, checkedField, fieldName) {
		var tempValue = '';
		var errorCount = 0;
		for (g=0; g<givenValue.length; g++) {
			if (givenValue[g] < "0" || givenValue[g] > "9") {
				if (givenValue[g] < "a" || givenValue[g] > "z") {
					if (givenValue[g] < "A" || givenValue[g] > "Z") {
						if (givenValue[g] != "." && givenValue[g] != "_") {
							errorCount++;
							}
						else {
							tempValue += givenValue[g];
							}
						}
					else {
						tempValue += givenValue[g];
						}
					}
				else {
					tempValue += givenValue[g];
					}
				}
			else {
				tempValue += givenValue[g];
				}
			}
		if (errorCount > 0) {
			alert ("Bitte verwenden Sie im Feld '" + fieldName + "' nur Buchstaben ('a-z'), Zahlen ('0-9'), Punkt ('.') und/oder Unterstrich '_'");
			document.getElementById(checkedField).value = tempValue;
			return false;
			}
		else {
			return true;
			}
		}
		
		function checklengthuser(){
		
			if(document.getElementById('new_username').value.length>2){
				if (checkForUnixNames(document.getElementById('new_username').value, 'new_username', 'Username')) {
					if(document.getElementById('new_realname').value.length>1){
						if(document.getElementById('new_email').value.length>8){
							document.getElementById('frmcreateuser').submit();
							return false;
							}
						else{
							alert("<?php echo returnIntLang('usermanagement new user setup email', false); ?>");
							return false;
							}
						document.getElementById('frmcreateuser').submit(); return false;
						return false;
						}
					else{
						alert("<?php echo returnIntLang('usermanagement new user setup real name', false); ?>");
						return false;
						}
					document.getElementById('frmcreateuser').submit(); return false;
					return false;
					}
				}
			else {
				alert("<?php echo returnIntLang('usermanagement new username too short', false); ?>");
				return false;
				}
			}
	-->
	</script>

	<fieldset>
		<legend><?php echo returnIntLang('usermanagement createnew'); ?> <span class="opencloseButton bubblemessage" rel="fieldset_newuser_content">↕</span></legend>
		<div id="fieldset_newuser_content" style="<?php echo isset($_SESSION['opentabs']['fieldset_newuser_content']) ? $_SESSION['opentabs']['fieldset_newuser_content'] : ''; ?>"><form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="frmcreateuser">

		<table class="tablelist">
			<tr>
				<td class="tablecell two desc"><?php echo returnIntLang('str username'); ?></td>
				<td class="tablecell two prop"><input name="new_username" id="new_username" type="text" size="20" maxlength="16" class="full" placeholder="<?php echo returnIntLang('usermanagement username desc', false); ?>" /></td>
				<td class="tablecell two desc"><?php echo returnIntLang('str email'); ?></td>
				<td class="tablecell two prop"><input name="new_email" id="new_email" type="text" size="20" maxlength="200" style="width: 95%;" placeholder="<?php echo returnIntLang('usermanagement email desc', false); ?>" /><input name="op" type="hidden" value="user_new" /><input type="hidden" name="user_data" value="setup" /></td>
			</tr>
			<tr>
				<td class="tablecell two desc"><?php echo returnIntLang('usermanagement realname'); ?></td>
				<td class="tablecell two prop"><input name="new_realname" id="new_realname" type="text" size="20" maxlength="200" class="full" /></td>
				<td class="tablecell two desc"><?php echo returnIntLang('usermanagement prepos'); ?></td>
				<td class="tablecell two prop"><select name="new_position" id="new_position" size="1" style="width: 95%;">
					<option value=""><?php echo returnIntLang('usermanagement prepos noselect', false); ?></option>
					<?php
					
					$cloneuser_sql = "SELECT * FROM `restrictions` WHERE `rid` != ".intval($_SESSION['wspvars']['userid'])." AND `usertype` != 1 ORDER BY `user` ASC";
					$cloneuser_res = doSQL($cloneuser_sql);
					if ($cloneuser_res['num']>0) {
						echo "<optgroup label=\"".returnIntLang('usermanagement clonerights', false)."\" name=\"".returnIntLang('usermanagement clonerights', false)."\">";
						foreach ($cloneuser_res['set'] AS $curk => $curv) {
							echo "<option value=\"".intval($curv['rid'])."\">".trim($curv['realname'])."</option>";
                        }
						echo "</optgroup>";
					}
					
					?>
					<optgroup name="<?php echo returnIntLang('usermanagement position', false); ?>" label="<?php echo returnIntLang('usermanagement position', false); ?>">
						<option value="admin"><?php echo returnIntLang('usermanagement prepos admin', false); ?></option>
						<option value="developer"><?php echo returnIntLang('usermanagement prepos developer', false); ?></option>
						<option value="technics"><?php echo returnIntLang('usermanagement prepos technician', false); ?></option>
						<option value="seo"><?php echo returnIntLang('usermanagement prepos seo', false); ?></option>
						<option value="redaktion"><?php echo returnIntLang('usermanagement prepos editor', false); ?></option>
					</optgroup>
				</select></td>
			</tr>
		</table>
		<p class="tooltip"><?php echo returnIntLang('usermanagement createnew desc'); ?></p>
		<fieldset class="options innerfieldset">
			<p><a href="#" onclick="checklengthuser();" class="greenfield"><?php echo returnIntLang('usermanagement createnew'); ?></a></p>
		</fieldset>
		</form></div>
	</fieldset>
	<?php endif; ?>
</div>
<?php require ("./data/include/footer.inc.php"); ?>
<!-- EOF -->