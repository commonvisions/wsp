<?php
/**
 * Userdaten bearbeiten
 * @author stefan@covi.de
  * @since 3.1
 * @version 6.11
 * @lastchange 2022-10-25
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("data/include/usestat.inc.php");
require ("data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */
$userrid = checkParamVar('userrid', '0', false, false);
$predefined = checkParamVar('predefined', '', false, false);
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = ''; // beschreibt als string, ob hier eine rechte-sperre vorliegt
$_SESSION['wspvars']['mgroup'] = 2; // aktive menuegruppe
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false; 
$_SESSION['wspvars']['preventleave'] = true;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

/* define page specific funcs ---------------- */

if (isset($_POST['userrid']) && intval($_POST['userrid'])>0):
	$_SESSION['wspvars']['hiddengetvars']['userrid'] = intval($_POST['userrid']);
endif;

// userdaten und -zugriffsrechte speichern

if (trim($predefined)!="" && trim($predefined)!="undefined"):
	if (trim($predefined)=="developer"):
		$rights['siteprops'] = 1;
		$_POST['changerights_siteprops'] = 1;
		$rights['sitestructure'] = 1;
		$_POST['changerights_sitestructure'] = 1;
		$rights['contents'] = 1;
		$_POST['changerights_contents'] = 1;
		$rights['filesystem'] = 1;
		$_POST['changerights_filesystem'] = 1;
		$rights['rss'] = 1;
		$_POST['changerights_rss'] = 1;
		$rights['publisher'] = 1;
		$_POST['changerights_publisher'] = 1;
		$rights['imagesfolder'] = "/";
		$_POST['changerights_imagesfolder'] = "/";
		$rights['downloadfolder'] = "/";
		$_POST['changerights_downloadfolder'] = "/";
		$rights['flashfolder'] = "/";
		$_POST['changerights_flashfolder'] = "/";
		$rights['design'] = 1;
		$_POST['changerights_design'] = 1;
	elseif (trim($predefined)=="technics"):
		$rights['siteprops'] = 0;
		$_POST['changerights_siteprops'] = 0;
		$rights['sitestructure'] = 0;
		$_POST['changerights_sitestructure'] = 0;
		$rights['contents'] = 0;
		$_POST['changerights_contents'] = 0;
		$rights['filesystem'] = 0;
		$_POST['changerights_filesystem'] = 0;
		$rights['rss'] = 0;
		$_POST['changerights_rss'] = 0;
		$rights['publisher'] = 0;
		$_POST['changerights_publisher'] = 0;
		$rights['imagesfolder'] = "/";
		$_POST['changerights_imagesfolder'] = "/";
		$rights['downloadfolder'] = "0";
		$_POST['changerights_downloadfolder'] = "0";
		$rights['flashfolder'] = "/";
		$_POST['changerights_flashfolder'] = "/";
		$rights['design'] = 1;
		$_POST['changerights_design'] = 1;
	elseif (trim($predefined)=="seo"):
		$rights['siteprops'] = 1;
		$_POST['changerights_siteprops'] = 1;
		$rights['sitestructure'] = 3;
		$_POST['changerights_sitestructure'] = 3;
		$rights['contents'] = 0;
		$_POST['changerights_contents'] = 0;
		$rights['filesystem'] = 0;
		$_POST['changerights_filesystem'] = 0;
		$rights['rss'] = 0;
		$_POST['changerights_rss'] = 0;
		$rights['publisher'] = 0;
		$_POST['changerights_publisher'] = 0;
		$rights['imagesfolder'] = "0";
		$_POST['changerights_imagesfolder'] = "0";
		$rights['downloadfolder'] = "0";
		$_POST['changerights_downloadfolder'] = "0";
		$rights['flashfolder'] = "0";
		$_POST['changerights_flashfolder'] = "0";
		$rights['design'] = 0;
		$_POST['changerights_design'] = 0;
	elseif (trim($predefined)=="redaktion"):
		$rights['siteprops'] = 0;
		$_POST['changerights_siteprops'] = 0;
		$rights['sitestructure'] = 1;
		$_POST['changerights_sitestructure'] = 1;
		$rights['contents'] = 1;
		$_POST['changerights_contents'] = 1;
		$rights['filesystem'] = 1;
		$_POST['changerights_filesystem'] = 1;
		$rights['rss'] = 1;
		$_POST['changerights_rss'] = 1;
		$rights['publisher'] = 1;
		$_POST['changerights_publisher'] = 1;
		$rights['imagesfolder'] = "/";
		$_POST['changerights_imagesfolder'] = "/";
		$rights['downloadfolder'] = "/";
		$_POST['changerights_downloadfolder'] = "/";
		$rights['flashfolder'] = "0";
		$_POST['changerights_flashfolder'] = "0";
		$rights['design'] = 0;
		$_POST['changerights_design'] = 0;
	endif;
endif;

if (isset($_POST['user_data']) && ($_POST['change_username']!="") && ($_POST['change_realname']!="") && ($_POST['change_realmail']!="")):
	
    $sql = "UPDATE `restrictions` SET ";

    $username_sql = "SELECT `user`, `usertype` FROM `restrictions` WHERE `rid` = ".intval($_POST['userrid']);
	$username_res = doSQL($username_sql);
	if (isset($username_res['set'][0]['user']) && $username_res['set'][0]['user']!=$_POST['change_username']):
		$doublename_sql = "SELECT `rid` FROM `restrictions` WHERE `user` = '".escapeSQL($_POST['change_username'])."'";
		$doublename_res = doSQL($doublename_sql);
		if ($doublename_res['num']>0):
			$changename = FALSE;
			$_POST['change_username'] = $username_res['set'][0]['user'];
			$_SESSION['errormsg'] .= "<p>Der Benutzername konnte nicht ver&auml;ndert werden, da der gew&auml;hlte Benutzername schon im System vorhanden ist.<br /></p>";
		else:
            // update username
            $sql.= " `user` = '".escapeSQL($_POST['change_username'])."', ";
		endif;
	else:
		$changename = FALSE;
	endif;

	// update des passworts
	if (intval($_POST['change_password'])==1 && trim($_POST['set_newpass'])!="" && strlen(trim($_POST['set_newpass']))>=8):
		$sql .= " `pass` = '".md5(trim($_POST['set_newpass']))."', ";
	endif;
	// update der personendaten
	$sql .= "`realname` = '".escapeSQL(trim($_POST['change_realname']))."', `realmail` = '".escapeSQL(trim($_POST['change_realmail']))."' ";
	
	if (intval($username_res['set'][0]['usertype'])!=1):
		// save rights for non-admin-users
		$changeidrights = array();
		foreach ($wspvars['rightabilityarray'] AS $key => $value) { 
			$changerights[$key] = $_POST['changerights_'.$key];
			if ($changerights[$key]==2 || $changerights[$key]==4):
				$checkpost = "change_".$key."_ids";
				if (count($_POST[$checkpost])>0):
					$changeidrights[$key] = $_POST[$checkpost];
				else:
					$changerights[$key] = 0;
				endif;
			endif;
			if ($changerights[$key]==7):
				$checkpost = "downchange_".$key."_ids";
				if (count($_POST[$checkpost])>0):
					$changeidrights[$key] = $_POST[$checkpost];
				else:
					$changerights[$key] = 0;
				endif;
			endif;
			// special for contents and/or publisher
			if ($changerights[$key]==15 || $changerights[$key]==12):
				$changeidrights[$key] = $changeidrights['sitestructure'];
			endif;
		}
		// modulare zugriffsrechte fuer den user festlegen
		$rm_sql = 'SELECT * FROM `wsprights`';
		$rm_res = doSQL($rm_sql);
        foreach ($rm_res['set'] AS $rmk => $rmv) {
			$modrights = unserializeBroken($rmv['possibilities']);
			if (!(array_key_exists($rmv['guid'], $_POST)) || (array_key_exists($rmv['guid'], $_POST) && array_search($_POST[$rmv['guid']], $modrights)===false)):
				$changerights[$rmv['guid']] = $rmv['standard'];
			else:
				$changerights[$rmv['guid']] = array_search($_POST[$rmv['guid']], $modrights);
			endif;
		}
		// zusammenfassung aller rechte, sowie der menuids in einem serialisierten array und ab damit ...
		$sql.= ", `rights` = '".escapeSQL(serialize($changerights))."', idrights = '".escapeSQL(serialize($changeidrights))."' ";
	endif;
	$sql.= " WHERE `rid` = '".$_POST['userrid']."'";
    $res = doSQL($sql);
	if ($res['aff']==1):
		addWSPMsg('noticemsg', returnIntLang('usermanagement userrights updated', false));
	else:
		addWSPMsg('noticemsg', returnIntLang('usermanagement userrights failed or did not change', false));
	endif;

    // send notification mail
	$domain_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'siteurl'";
	$domain_res = doResultSQL($domain_sql);
	if ($domain_res!==false):
		$domain = trim($domain_res);
	else:
		$domain = $_SERVER['HTTP_HOST'];
	endif;	
	$domain = str_replace("www.","",$domain);
	$domain = str_replace("http://","",$domain);
	if (intval($_POST['change_password'])==1 && intval($_POST['email_password'])==1 && trim($_POST['set_newpass'])!=""):
		mail($_POST['change_realmail'],
		returnIntLang('mailtemplate account created or changed', false),
		returnIntLang('mailtemplate your account to', false)." '".$domain."' ".returnIntLang('mailtemplate was created or updated', false).".\n".
		returnIntLang('mailtemplate your account data', false).":\n\n".
		returnIntLang('mailtemplate your account username', false).": ".$_POST['change_username']."\n".
		returnIntLang('mailtemplate your account password', false).": ".trim($_POST['set_newpass'])."\n\n".
		returnIntLang('mailtemplate your account login page', false)." http://www.".$domain."/".$wspvars['wspbasedir']."/\n",
		"From: wsp@".$domain."\n");
	endif;
	addWSPMsg('noticemsg', returnIntLang('usermanagement userproperties set', false));
endif;

$userinfo_sql = "SELECT * FROM `restrictions` WHERE `rid` = ".intval($userrid)." AND `rid` != ".intval($_SESSION['wspvars']['userid']);
$userinfo_res = doSQL($userinfo_sql);
if ($userinfo_res['num']==0):
	$_SESSION['wspvars']['errormsg'].= "<p>".returnIntLang('rights error returning userdata', false)."</p>";
else:
	if (trim($userinfo_res['set'][0]["pass"])==""):
		addWSPMsg('errormsg', returnIntLang('rights no password sent', true));
		$checkpass = TRUE;
	endif;
endif;

// head der datei

include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");
?>

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

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('rights headline'); ?></h1></fieldset>
	<?php if ($userinfo_res['num']!=0): ?>
	<fieldset class="text"><p><?php echo returnIntLang('rights description'); ?></p></fieldset>
	<?php if (isset($notify) && $notify!=""): echo "<p style=\"color: #FF0000;\">".$notify."</p>"; endif; ?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" id="frmuseredit" method="post">
	<?php
	
	$saved_usertype = intval($userinfo_res['set'][0]["usertype"]);
	$saved_username = trim($userinfo_res['set'][0]["user"]);
	$saved_password = trim($userinfo_res['set'][0]["pass"]);
	$saved_realname = trim($userinfo_res['set'][0]["realname"]);
	$saved_realmail = trim($userinfo_res['set'][0]["realmail"]);
	
	?>
	<fieldset>
		<legend><?php echo returnIntLang('str userinfo'); ?> <span class="opencloseButton bubblemessage" rel="userinfo">↕</span></legend>
		<div id="userinfo" style="<?php echo isset($_SESSION['opentabs']['userinfo']) ? $_SESSION['opentabs']['userinfo'] : ''; ?>">
		<table class="tablelist">
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('str username'); ?></td>
				<td class="tablecell two"><input name="change_username" id="change_username" type="text" value="<?php echo $saved_username; ?>" maxlength="16" size="20" class="full" placeholder="<?php echo returnIntLang('rights usernamehint'); ?>" /></td>
				<td class="tablecell two"><?php echo returnIntLang('str email'); ?></td>
				<td class="tablecell two"><input name="change_realmail" id="change_realmail" type="text" value="<?php echo $saved_realmail; ?>" maxlength="200" size="20" class="full" placeholder="<?php echo returnIntLang('rights emailhint'); ?>" /></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('str realname'); ?></td>
				<td class="tablecell two"><input name="change_realname" id="change_realname" type="text" value="<?php echo $saved_realname; ?>" maxlength="200" size="20" class="full" /></td>
				<?php if ($saved_usertype!=1): ?>
					<td class="tablecell two"><?php echo returnIntLang('usermanagement prepos'); ?></td>
					<td class="tablecell two"><select name="predefined" id="predefined_position" size="1" class="full">
						<option value=""><?php echo returnIntLang('usermanagement prepos noselect', false); ?></option>
						<option value="developer"><?php echo returnIntLang('usermanagement prepos developer', false); ?></option>
						<option value="technics"><?php echo returnIntLang('usermanagement prepos technician', false); ?></option>
						<option value="seo"><?php echo returnIntLang('usermanagement prepos seo', false); ?></option>
						<option value="redaktion"><?php echo returnIntLang('usermanagement prepos editor', false); ?></option>
					</select></td>
				<?php else: ?>
					<td class="tablecell two">&nbsp;</td>
					<td class="tablecell two">&nbsp;</td>
					<?php endif; ?>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('rights setnewpass'); ?> <input name="change_password" type="hidden" value="0" /><input name="change_password" id="change_password" type="checkbox" value="1" /></td>
					<td class="tablecell two"><input name="set_newpass" id="set_newpass" type="text" value="<?php echo strtoupper(substr(md5(date("YmdHis")),0,8)); ?>" maxlength="20" class="full" /></td>
					<td class="tablecell two"><?php echo returnIntLang('rights setnewpasshint'); ?> <input name="email_password" type="hidden" value="0" /><input name="email_password" id="email_password" type="checkbox" value="1" /></td>
					<td class="tablecell two">&nbsp;</td>
				</tr>
			</table>
		</div>
	</fieldset>
	<?php
		
	$saved_rights = trim($userinfo_res['set'][0]["rights"]);
	//
	// erst mal werden alle auswahl-menue-ids immer gleich zugewiesen, das sollte
	// aber auch noch geaendert werden, da sie ja schon so abgespeichert werden ...
	//
	if (trim($userinfo_res['set'][0]["idrights"])!=""):
		$saved_rights_ids = unserializeBroken(trim($userinfo_res['set'][0]["idrights"]));
		if (is_array($saved_rights_ids)):
			foreach ($saved_rights_ids AS $key => $value):
				$preparesaved = "saved_".$key."_ids";
				$$preparesaved = $value;
			endforeach;
		endif;
	endif;
	
	//
	// einblendung der modularen rechte sichern
	//
	$modrights['devolveadmin'] = "0";
	if ($saved_rights!=""):
		foreach (unserializeBroken($saved_rights) as $key => $value):
			if (strlen($key)==36):
				$modrights[$key] = $value;
			else:
				$rights[$key] = $value;
			endif;
		endforeach;
	else:
		$modrights = array();
		$rights = array();
	endif;
	
	if ($saved_usertype!=1):
	?>
	<script type="text/JavaScript">

	function check(obj) {
		k = 0;
		for(i=0;i<document.getElementById('change_' + obj + '_field').options.length;i++) {
			if(document.getElementById('change_' + obj + '_field').options[i].selected) {
				k++;
				}
			}
		// alert(k);
		}
	
	function showSelArea(obj) {
		<?php
		foreach ($wspvars['rightabilityarray'] AS $key => $value): 
			if ($wspvars['rightabilityarray'][$key]['mode']==2 || $wspvars['rightabilityarray'][$key]['mode']==4 || $wspvars['rightabilityarray'][$key]['mode']==5):
				echo "document.getElementById('change_".$key."_ids').style.display = 'none'; ";
			endif;
		endforeach;	
		?>
		if (obj!="") {
			document.getElementById('change_' + obj + '_ids').style.display = 'table';
			document.getElementById('change_' + obj + '_ids').style.width = '100%';
			document.getElementById('change_' + obj + '_field').style.width = '60em';
			}
		}
	
	function checkSelect(obj) {
		if (document.getElementById('changerights_' + obj).value==2 || document.getElementById('changerights_' + obj).value==4) {
			document.getElementById('change_' + obj + '_field').style.display = 'block';
			document.getElementById('change_' + obj + '_singlefield').style.display = 'none';
			}
		else if (document.getElementById('changerights_' + obj).value==7) {
			document.getElementById('change_' + obj + '_singlefield').style.display = 'block';
			document.getElementById('change_' + obj + '_field').style.display = 'none';
			}
		else {
			document.getElementById('change_' + obj + '_field').style.display = 'none';
			document.getElementById('change_' + obj + '_singlefield').style.display = 'none';
			}
		}
	
	</script>
	<fieldset>
		<legend><?php echo returnIntLang('str rights'); ?> <span class="opencloseButton bubblemessage" rel="rightsdiv">↕</span></legend>
		<div id="rightsdiv" style="<?php echo isset($_SESSION['opentabs']['rightsdiv']) ? $_SESSION['opentabs']['rightsdiv'] : ''; ?>">
			<?php
			$r = 0;
			foreach ($wspvars['rightabilityarray'] AS $key => $value): 
				if ($wspvars['rightabilityarray'][$key]['mode']!=6): // modus 6 bezieht sich auf ordner im dateisystem
				?>
				<table class="tablelist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('rights '.$key); ?></td>
						<td class="tablecell six"><select name="changerights_<?php echo $key; ?>" id="changerights_<?php echo $key; ?>" style="width: 98%;" onchange="checkSelect('<?php echo $key; ?>')">
						<option value="1" <?php if (isset($rights[$key]) && intval($rights[$key])==1): echo " selected=\"selected\""; endif; ?>><?php echo returnIntLang('rights allrights', false); ?></option>
						<option value="0" <?php if (isset($rights[$key]) && intval($rights[$key])==0): echo " selected=\"selected\""; endif; ?>><?php echo returnIntLang('rights norights', false); ?></option>
						<?php if($wspvars['rightabilityarray'][$key]['mode']==2 || $wspvars['rightabilityarray'][$key]['mode']==5 || $wspvars['rightabilityarray'][$key]['mode']==15): ?>
							<option value="2" <?php if (isset($rights[$key]) && intval($rights[$key])==2): echo " selected=\"selected\""; endif; ?>><?php echo returnIntLang('rights allrightstoselected', false); ?></option>
						<?php endif; ?>
						<?php if($wspvars['rightabilityarray'][$key]['mode']>3 && $wspvars['rightabilityarray'][$key]['mode']!=12): ?>
							<option value="7" <?php if (isset($rights[$key]) && intval($rights[$key])==7): echo " selected=\"selected\""; endif; ?>><?php echo returnIntLang('rights allrightsselecedstructure', false); ?></option>
						<?php endif; ?>
						<?php if($wspvars['rightabilityarray'][$key]['mode']>2 && $wspvars['rightabilityarray'][$key]['mode']!=12): ?>
							<option value="3" <?php if (isset($rights[$key]) && intval($rights[$key])==3): echo " selected=\"selected\""; endif; ?>><?php echo returnIntLang('rights editall', false); ?></option>
						<?php endif; ?>
						<?php if($wspvars['rightabilityarray'][$key]['mode']>3): ?>
							<option value="4" <?php if (isset($rights[$key]) && intval($rights[$key])==4): echo " selected=\"selected\""; endif; ?>><?php echo returnIntLang('rights editselected', false); ?></option>
						<?php endif; ?>
						<?php if($wspvars['rightabilityarray'][$key]['mode']>10): ?>
							<option value="<?php echo $wspvars['rightabilityarray'][$key]['mode']; ?>" <?php if (isset($rights[$key]) && intval($rights[$key])==intval($wspvars['rightabilityarray'][$key]['mode'])): echo " selected=\"selected\""; endif; ?>><?php echo returnIntLang('rights use sitestructure prefs', false); ?></option>
						<?php endif; ?>
						</select></td>
					</tr>
				</table>
				<?php if($wspvars['rightabilityarray'][$key]['mode']==2 || $wspvars['rightabilityarray'][$key]['mode']==4 || $wspvars['rightabilityarray'][$key]['mode']==5 || $wspvars['rightabilityarray'][$key]['mode']==15 || $wspvars['rightabilityarray'][$key]['mode']==7): ?>
					<div id="change_<?php echo $key; ?>_field" style="<?php if(!(array_key_exists($key, $rights)) || intval($rights[$key])==0 || intval($rights[$key])==1 || intval($rights[$key])==3 || intval($rights[$key])==7 || intval($rights[$key])==15): ?>display: none;<?php endif; ?>">
					<table class="tablelist">
					<tr id="change_<?php echo $key; ?>_row">
						<td class="tablecell two"><?php echo returnIntLang('rights please select'); ?></td>
						<td id="change_<?php echo $key; ?>_cell" class="tablecell six"><select name="change_<?php echo $key; ?>_ids[]" size="<?php echo ceil(count($wspvars['rightabilityarray'])*1.3); ?>" multiple style="width: 100%; height: <?php echo ceil(count($wspvars['rightabilityarray'])*1.3); ?>em;" onBlur="check('<?php echo $key; ?>');"><?php
						$requestsaved = "saved_".$key."_ids";
						getMenuLevel(0, '', 4, array($requestsaved));
						?></select></td>
					</tr>
					</table>
					</div>
				<?php endif; ?>
				<?php if ($wspvars['rightabilityarray'][$key]['mode']==2 || $wspvars['rightabilityarray'][$key]['mode']==4 || $wspvars['rightabilityarray'][$key]['mode']==5 || $wspvars['rightabilityarray'][$key]['mode']==15 || $wspvars['rightabilityarray'][$key]['mode']==7): ?>
					<div id="change_<?php echo $key; ?>_singlefield" style="<?php if(!(array_key_exists($key, $rights)) || intval($rights[$key])!=7): ?>display: none;<?php endif; ?>">
					<table class="tablelist">
						<tr>
							<td class="tablecell two"><?php echo returnIntLang('rights please select'); ?></td>
							<td class="tablecell six"><select name="downchange_<?php echo $key; ?>_ids[]" size="1" class="full" onBlur="check('<?php echo $key; ?>');"><?php
							$requestsaved = "saved_".$key."_ids";
							$singlerequest = $$requestsaved;
							$singlerequest = intval($singlerequest[0]);
							getMenuLevel(0, '', 4, array($singlerequest));
							?></select></td>
						</tr>
					</table>
					</div>
				<?php endif;
				$r++;
				endif;
			endforeach; ?>
		<p><?php echo returnIntLang('rights selectrightsdescription'); ?></p>
		<table class="tablelist">
			<?php 

			foreach ($wspvars['rightabilityarray'] AS $key => $value): 
				if ($wspvars['rightabilityarray'][$key]['mode']==6):
					$path = str_replace("//","/",str_replace("//","/",str_replace("//","/",str_replace(".","",$wspvars['rightabilityarray'][$key]['basefolder']))));
					$directory = array();
					if (is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$path)))):
						mediaDirList($path, $wspvars['rightabilityarray'][$key]['basefolder']);
						sort($directory);
					endif;
					?>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang($wspvars['rightabilityarray'][$key]['desc']); ?></td>
						<td class="tablecell six"><select name="changerights_<?php echo $key; ?>" id="<?php echo $key; ?>" style="width: 98%;">
							<option value="0" <?php if (isset($rights[$key]) && $rights[$key]=="0"): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('rights filesystem noaccess', false); ?></option>
							<option value="/" <?php if (isset($rights[$key]) && $rights[$key]=="/"): echo "selected=\"selected\""; endif; ?>><?php echo returnIntLang('rights filesystem norestriction', false); ?></option>
							<?php
							
							foreach ($directory AS $dkey => $dvalue):
								echo "<option value=\"".trim(str_replace("//", "/", $dvalue))."\"";
								if ($rights[$key]==$dvalue):
									echo " selected=\"selected\"";
								endif;
								echo ">".$dvalue."</option>\n";
							endforeach;
							
							?>
						</select></td>
					</tr>
				<?php endif;
			endforeach; ?>
		</table>
		<p class="tooltip"><?php echo returnIntLang('rights filesystemdesc'); ?></p>
		</div>
	</fieldset>
	<?php 
	
	$wsprights_sql = "SELECT * FROM `wsprights`";
	$wsprights_res = doSQL($wsprights_sql);
	
	if ($wsprights_res['num']>0):
	?>
	<fieldset>
		<legend><?php echo returnIntLang('rights modrights'); ?> <span class="opencloseButton bubblemessage" rel="modrightsdiv">↕</span></legend>
		<div id="modrightsdiv">
		<?php
		
		$wspmod = array();
		foreach ($wsprights_res['set'] AS $wrresk => $wrresv) {
			
			$wspmod_sql = "SELECT * FROM `wspmenu` WHERE `guid` = '".trim($wrresv["guid"])."'";
			$wspmod_res = doSQL($wspmod_sql);
			if ($wspmod_res['num']>0) {
				$wspmod['guid'][trim($wrresv["guid"])] = trim($wrresv["guid"]);
				$wspmod['self_id'][trim($wrresv["guid"])] = intval($wrresv["id"]);
				$wspmod['parent_id'][trim($wrresv["guid"])] = intval($wrresv["parent_id"]);
				$wspmod['position'][trim($wrresv["guid"])] = intval($wrresv["position"]);
				$wspmod['right'][trim($wrresv["guid"])] = trim($wrresv["right"]);
				if (strlen($wrresv["labels"]) > 0):
					$wspmod['labels'][trim($wrresv["guid"])] = unserializeBroken($wrresv["labels"]);
				else:
					$wspmod['labels'][trim($wrresv["guid"])] = unserializeBroken($wrresv["possibilities"]);
				endif;
				$wspmod['possibilities'][trim($wrresv["guid"])] = trim($wrresv["possibilities"]);
				$wspmod['standard'][trim($wrresv["guid"])] = trim($wrresv["standard"]);
			}
			
			$pluginmod_sql = "SELECT * FROM `wspplugins` WHERE `guid` = '".trim($wrresv["guid"])."'";
			$pluginmod_res = doSQL($pluginmod_sql);
			
			if ($pluginmod_res['num']>0) {
				$wspmod['guid'][trim($wrresv["guid"])] = trim($wrresv["guid"]);
				$wspmod['self_id'][trim($wrresv["guid"])] = intval($pluginmod_res['set'][0]["id"]);
				$wspmod['parent_id'][trim($wrresv["guid"])] = 0;
				$wspmod['position'][trim($wrresv["guid"])] = 0;
				$wspmod['right'][trim($wrresv["guid"])] = trim($wrresv["right"])." [Plugin]";
				if (strlen($wrresv["labels"]) > 0):
					$wspmod['labels'][trim($wrresv["guid"])] = unserializeBroken($wrresv["labels"]);
				else:
					$wspmod['labels'][trim($wrresv["guid"])] = unserializeBroken($wrresv["possibilities"]);
				endif;
				$wspmod['possibilities'][trim($wrresv["guid"])] = trim($wrresv["possibilities"]);
				$wspmod['standard'][trim($wrresv["guid"])] = trim($wrresv["standard"]);
			}
        }
		
		if (is_array($wspmod['parent_id'])) {
			asort($wspmod['parent_id']);
		}
		
		?>
		<table class="tablelist">
		<?php
		
		$r = 0;
		foreach ($wspmod['parent_id'] AS $key => $value):
			if ($value==0):
				?>
				<tr>
				<td class="tablecell two"><?php echo $wspmod['right'][$key]; ?></td>
				<td class="tablecell six"><?php
				
				foreach (unserializeBroken($wspmod['possibilities'][$key]) as $poskey => $posvalue):
					if (key_exists('rights', $wspmod)):
						$restrictions = unserializeBroken($wspmod['rights'][$key]);
					else:
						$restrictions = array();
					endif;
					$checked = "";
					if (isset($modrights[$key]) && ($poskey == $modrights[$key])):
						$checked = 'checked="checked" ';
					elseif ((!isset($modrights[$key])) && ($poskey == $wspmod['standard'][$key])):
						$checked = 'checked="checked" ';
					endif;
					?>
					<input name="<?php echo $key; ?>" id="<?php echo $key.'_'.$posvalue; ?>" <?php echo $checked; ?>type="radio" value="<?php echo $posvalue; ?>" onchange="checkSub('<?php echo $wspmod['self_id'][$key]; ?>','<?php echo $posvalue; ?>')" />&nbsp;&nbsp;&nbsp;<label for="<?php echo $key.'_'.$posvalue; ?>"><?php echo $wspmod['labels'][$key][$poskey]; ?></label>&nbsp;&nbsp;&nbsp;
					<?php
				
				endforeach; 
				echo "</td></tr>";
				
				$r++;
				foreach ($wspmod['parent_id'] AS $subkey => $subvalue):
					if ($wspmod['self_id'][$key]==$subvalue):
						?>
						<tr>
						<td class="tablecell two">&nbsp;</td>
						<td class="tablecell two"><?php echo $wspmod['right'][$subkey]; ?></td>
						<td class="tablecell four"><?php
						
						foreach (unserialize($wspmod['possibilities'][$subkey]) as $poskey => $posvalue):
							if (key_exists('rights', $wspmod)):
								$restrictions = unserializeBroken($wspmod['rights'][$subkey]);
							else:
								$restrictions = array();
							endif;
							$checked = "";
							if (isset($modrights[$subkey]) && ($poskey == $modrights[$subkey])):
								$checked = 'checked="checked" ';
							elseif ((!isset($modrights[$subkey])) && ($poskey == $wspmod['standard'][$subkey])):
								$checked = 'checked="checked" ';
							endif;
							?>
							<input name="<?php echo $subkey; ?>" id="<?php echo $subkey.'_'.$posvalue; ?>" <?php echo $checked; ?>type="radio" value="<?php echo $posvalue; ?>" onchange="checkSub(<?php echo $subvalue; ?>, 2)" />&nbsp;&nbsp;&nbsp;<label for="<?php echo $subkey.'_'.$posvalue; ?>"><?php echo $wspmod['labels'][$subkey][$poskey]; ?></label>&nbsp;&nbsp;&nbsp;
							<?php
						
						endforeach; 
						
						?></td>
						</tr>
						<?php
						$r++;
					endif;
				endforeach;
			endif;
		endforeach;
		?>
		</table>
		<script language="JavaScript" type="text/javascript">
		<!--
		
		function checkSub(id, stat) {
<?php
			foreach ($wspmod['parent_id'] AS $key => $value):
				if ($value==0):
					$elements = array();
					foreach ($wspmod['parent_id'] AS $subkey => $subvalue):
						if ($wspmod['self_id'][$key]==$subvalue):
							$elements[] = $subkey;
						endif;
					endforeach;
					if (count($elements)>0):
						echo "if (id==".$wspmod['self_id'][$key].") {\n";
						echo "if (stat==0) {\n";
						foreach ($elements AS $ekey => $evalue):
							echo "document.getElementById('".$evalue."_0').checked = true;\n";
						endforeach;
						echo "}\n";
						echo "else if (stat==1) {\n";
						foreach ($elements AS $ekey => $evalue):
							echo "document.getElementById('".$evalue."_1').checked = true;\n";
						endforeach;
						echo "}\n";
						echo "else if (stat==2) {\n";
						echo "if (!(document.getElementById('".implode("_1').checked) && !(document.getElementById('", $elements)."_1').checked)) {\n";
						echo "document.getElementById('".$key."_0').checked = true;\n";
						echo "}\n";
						echo "else {\n";
						echo "document.getElementById('".$key."_1').checked = true;\n";
						echo "}\n";
						echo "}\n";
						echo "}\n";
					endif;
				endif;
			endforeach;
?>
			}
		
		// -->
		</script>
		</div>
	</fieldset>
	<?php endif; ?>
	<?php endif; ?>
	<fieldset class="options">
		<p><a href="#" onclick="valiData(); return false;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a> <a href="usermanagement.php" class="orangefield"><?php echo returnIntLang('str back', false); ?></a></p>
		<input type="hidden" name="notify_user" value="yes" />
		<input type="hidden" name="user_data" value="Speichern">
		<input type="hidden" name="userrid" value="<?php echo intval($userrid); ?>">
	</fieldset>
	</form>
	<?php else: ?>
	<fieldset class="errormsg"><p><?php echo returnIntLang('rights noaccess'); ?></p></fieldset>
	<fieldset class="options">
		<p><a href="usermanagement.php" class="orangefield"><?php echo returnIntLang('str back', false); ?></a></p>
		<input type="hidden" name="notify_user" value="yes" />
		<input type="hidden" name="user_data" value="Speichern">
		<input type="hidden" name="userrid" value="<?php echo intval($userrid); ?>">
	</fieldset>
	<?php endif; ?>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->