<?php
/**
 * Userverwaltung
 * @author stefan@covi.de
 * @since 3.5
 * @version 6.8
 * @lastchange 2019-01-22
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("data/include/usestat.inc.php");
require ("data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wsplang.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */
$op = checkParamVar('op', '');
/* define actual system position ------------- */
$_SESSION['wspvars']['menuposition'] = "usernotice";
$_SESSION['wspvars']['mgroup'] = 2;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
/* define page specific funcs ----------------- */

if ($op=="rm"):
	$allmessage = unserialize($wspvars['messages']);
	if ($allmessage!=""):
		foreach ($allmessage AS $key => $value):
			if ($value[1]==$_POST['rm']):
				$allmessage[$key][3] = 1;
			endif;
		endforeach;
	endif;
	doSQL("UPDATE `restrictions` SET `usernotice` = '".escapeSQL(serialize($allmessage))."' WHERE `rid` = ".intval($_SESSION['wspvars']['userid']));
    $getread_sql = "SELECT `usernotice` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
	$getread_res = doSQL($getread_sql);
	if ($getread_res['num']>0):
		$wspvars['messages'] = $getread_res['set'][0]['usernotice'];
	else:
		$wspvars['messages'] = "";
	endif;
elseif ($op=="dm"):
	$allmessage = unserialize($wspvars['messages']);
	if ($allmessage!=""):
		foreach ($allmessage AS $key => $value):
			if ($value[1]==$_POST['dm']):
				unset($allmessage[$key]);
			endif;
		endforeach;
	endif;
	doSQL("UPDATE `restrictions` SET `usernotice` = '".escapeSQL(serialize($allmessage))."' WHERE `rid` = '".intval($_SESSION['wspvars']['userid']));
    $getread_sql = "SELECT `usernotice` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
	$getread_res = doSQL($getread_sql);
	if ($getread_res['num']>0):
		$wspvars['messages'] = trim($getread_res['set'][0]['usernotice']);
	else:
		$wspvars['messages'] = "";
	endif;
	
elseif ($op=="sm" && intval($_POST['submitto'])>0 && intval($_POST['submitfrom'])>0 && intval($_POST['submitto'])!=intval($_SESSION['wspvars']['userid']) && intval($_POST['submitfrom'])==intval($_SESSION['wspvars']['userid'])):
	
	$insertmessage = array(
		0 => intval($_POST['submitfrom']),
		1 => mktime(),
		2 => htmlentities($_POST['newmessage']),
		3 => 0
		);
	
	$getread_sql = "SELECT `usernotice` FROM `restrictions` WHERE `rid` = ".intval($_POST['submitto']);
	$getread_res = doSQL($getread_sql);
	if ($getread_res['num']>0):
		$usermessages = unserializeBroken($getread_res['set'][0]['usernotice']);
		if (is_array($usermessages)):
			array_unshift($usermessages, $insertmessage);
		else:
			$usermessages = array($insertmessage);
		endif;
	endif;
	doSQL("UPDATE `restrictions` SET `usernotice` = '".escapeSQL(serialize($usermessages))."' WHERE `rid` = ".intval($_POST['submitto']));
	doSQL("INSERT INTO `wspmsg` SET `targetuid` = ".intval($_POST['submitto']).", `message` = 'You have new message from <strong>".escapeSQL(setUTF8($wspvars['realname']))."</strong>', `read` = 0");
	
	$insertmessage = array(
		0 => intval($_POST['submitto']),
		1 => mktime(),
		2 => htmlentities($_POST['newmessage']),
		3 => 2
		);
	
	$allmessage = unserializeBroken($wspvars['messages']);
	if (is_array($allmessage)):
		array_unshift($allmessage, $insertmessage);
	else:
		$allmessage = array($insertmessage);
	endif;
	doSQL("UPDATE `restrictions` SET `usernotice` = '".escapeSQL(serialize($allmessage))."' WHERE `rid` = ".intval($_SESSION['wspvars']['userid']));
    $getread_sql = "SELECT `usernotice` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
	$getread_res = doSQL($getread_sql);
	if ($getread_res['num']>0):
		$wspvars['messages'] = trim($getread_res['set'][0]['usernotice']);
	else:
		$wspvars['messages'] = "";
	endif;
elseif ($op=="srm" && intval($_POST['submitfrom'])>0 && intval($_POST['submitfrom'])==intval($_SESSION['wspvars']['userid']) && trim($_POST['newmessage'])!=""):
	
	$userselect_sql = "SELECT `rid` FROM `restrictions` WHERE `rid` != ".intval(intval($_SESSION['wspvars']['userid']));
	$userselect_res = doSQL($userselect_sql);

    if ($userselect_res['num']>0):
        foreach ($userselect_res['set'] AS $usresk => $usresv):
			$insertmessage = array(
				0 => intval(intval($_SESSION['wspvars']['userid'])),
				1 => mktime(),
				2 => htmlentities($_POST['newmessage']),
				3 => 0
				);
			$getread_sql = "SELECT `usernotice` FROM `restrictions` WHERE `rid` = ".intval($usresv['rid']);
			$getread_res = doSQL($getread_sql);
			if ($getread_res['num']>0):
				$usermessages = unserializeBroken($getread_res['set'][0]['usernotice']);
				if (is_array($usermessages)):
					array_unshift($usermessages, $insertmessage);
				else:
					$usermessages = array($insertmessage);
				endif;
			endif;
			doSQL("UPDATE `restrictions` SET `usernotice` = '".escapeSQL(serialize($usermessages))."' WHERE `rid` = ".intval($usresv['rid']));
            doSQL("INSERT INTO `wspmsg` SET `targetuid` = ".intval($usresv['rid']).", `message` = 'You have new message from <strong>".escapeSQL(setUTF8($wspvars['realname']))."</strong>', `read` = 0");

        endforeach;
		$_SESSION['wspvars']['noticemsg'].= "<p>Nachricht wurde an alle Benutzer verschickt.</p>";
	endif;
	
	$insertmessage = array(
		0 => 'all',
		1 => mktime(),
		2 => htmlentities($_POST['newmessage']),
		3 => 2
		);
	
	$allmessage = unserialize($wspvars['messages']);
	if (is_array($allmessage)):
		array_unshift($allmessage, $insertmessage);
	else:
		$allmessage = array($insertmessage);
	endif;
	doSQL("UPDATE `restrictions` SET `usernotice` = '".escapeSQL(serialize($allmessage))."' WHERE `rid` = ".intval($_SESSION['wspvars']['userid']));

    $getread_sql = "SELECT `usernotice` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
	$getread_res = doSQL($getread_sql);
	if ($getread_res['num']>0):
		$wspvars['messages'] = $getread_res['set'][0]['usernotice'];
	else:
		$wspvars['messages'] = "";
	endif;

endif;

// head der datei

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");
?>

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('user messages'); ?></h1></fieldset>
	<?php if (intval($_SESSION['wspvars']['usertype'])==1):

		$usercheck_sql = "SELECT `realname`, `rid` FROM `restrictions` WHERE `rid` != ".intval($_SESSION['wspvars']['userid'])." ORDER BY `user` ASC";
		$usercheck_res = doSQL($usercheck_sql);
		
		if ($usercheck_res['num']>0):
		?>
		<fieldset>
			<legend><?php echo returnIntLang('str legend'); ?> <?php echo legendOpenerCloser('wsplegend'); ?></legend>
			<div id="wsplegend">
				<p><?php echo returnIntLang('user messages info'); ?></p>
			</div>
		</fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('user messages existing'); ?> <?php echo legendOpenerCloser('messagetoexistingusers'); ?></legend>
			<div id="messagetoexistingusers">
			<table class="contenttable">
			<tr class="tablehead">
				<td width="50%"><?php echo returnIntLang('str user'); ?></td><td width="50%">&nbsp;</td>
			</tr>
			<tr>
				<td width="50%"><?php echo returnIntLang('user messages allusers'); ?></td><td width="50%"><a onclick="document.getElementById('createmessageto_all').submit();"><span class="bubblemessage orange"><?php echo strtoupper(returnIntLang('str roundmail', false)); ?></span></a><form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="createmessageto_all" style="margin: 0px; padding: 0px;"><input type="hidden" name="op" value="nm"><input type="hidden" name="uid" value="all" /></form></td>
			</tr>
			<?php
			foreach ($usercheck_res['set'] AS $ucrsk => $ucrsv):
				echo "<tr>\n";
				echo "\t<td width=\"50%\">".setUTF8($ucrsv["realname"])."</td>\n";
				echo "<td width=\"50%\">\n";
				echo "<a onclick=\"document.getElementById('createmessageto_".$ucrsv["rid"]."').submit();\"><span class=\"bubblemessage orange\">".strtoupper(returnIntLang('str mail', false))."</span></a>";
				echo "<form action=\"/".$_SESSION['wspvars']['wspbasedir']."/usernotice.php\" method=\"post\" id=\"createmessageto_".$ucrsv["rid"]."\" style=\"margin: 0px; padding: 0px;\">\n";
				echo "<input type=\"hidden\" name=\"op\" value=\"nm\">\n";
				echo "<input type=\"hidden\" name=\"uid\" value=\"".$ucrsv["rid"]."\">\n";
				echo "</form>\n";
				echo "</td>\n";
				echo "</tr>\n";
			endforeach;
			?>
			</table></div>
		</fieldset>
		<?php endif; ?>
	<?php else: 
		$usercheck_num = 0;
		$usercheck_sql = "SELECT `realname`, `rid` FROM `restrictions` WHERE `rid` != ".intval($_SESSION['wspvars']['userid'])." AND `usertype` = 1 ORDER BY `user` ASC";
		$usercheck_res = doSQL($usercheck_sql);
			
		if ($usercheck_res['num']>0):
		?>
		<fieldset><p><?php echo returnIntLang('user messages askadmin'); ?></p></fieldset>
		<fieldset>
			<legend><?php echo returnIntLang('user messages existing'); ?> <span class="opencloseButton bubblemessage" rel="messagecreate">↕</span></legend>
			<div id="messagecreate" style="<?php echo $_SESSION['opentabs']['messagecreate']; ?>"><table class="contenttable">
			<tr class="tablehead">
				<td width="50%"><?php echo returnIntLang('str user'); ?></td><td width="50%">&nbsp;</td>
			</tr>
			<?php
			foreach ($usercheck_res['set'] AS $ucrsk => $ucrsv):
				echo "<tr>\n";
				echo "\t<td width=\"50%\">".$ucrsv["realname"]."</td>\n";
				echo "<td width=\"50%\">\n";
				echo "<a onclick=\"document.getElementById('createmessageto_".$ucrsv["rid"]."').submit();\"><span class=\"bubblemessage orange\">".strtoupper(returnIntLang('str mail', false))."</span></a>";
				echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"createmessageto_".$ucrsv["rid"]."\" style=\"margin: 0px; padding: 0px;\">\n";
				echo "<input type=\"hidden\" name=\"op\" value=\"nm\">\n";
				echo "<input type=\"hidden\" name=\"uid\" value=\"".$ucrsv["rid"]."\">\n";
				echo "</form>\n";
				echo "</td>\n";
				echo "</tr>\n";
			endforeach;
			?>
			</table></div>
		</fieldset>
		<?php endif;
	endif; ?>
	
	<?php if ($op=="nm"): 
	
		$userto_sql = "SELECT `realname` FROM `restrictions` WHERE `rid` = ".intval($_POST['uid']);
		$userto_res = doSQL($userto_sql);
		
		if ($userto_res['num']>0):
			?>
			<fieldset>
				<legend><?php echo returnIntLang('user messages create'); ?></legend>
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="submitnewmessage">
				<table class="contenttable">
				<tr class="secondcol">
					<td width="25%"><?php echo returnIntLang('str recipient'); ?>:</td>
					<td width="75%"><?php echo setUTF8(trim($userto_res['set'][0]["realname"])); ?></td>
				</tr>
				<tr>
					<td width="25%"><?php echo returnIntLang('str message'); ?>:</td>
					<td width="75%"><textarea name="newmessage" id="newmessage" rows="3" cols="20" style="width: 95%;"></textarea></td>
				</tr>
				</table>
				<input type="hidden" name="submitto" value="<?php echo $_POST['uid']; ?>" />
				<input type="hidden" name="submitfrom" value="<?php echo intval($_SESSION['wspvars']['userid']); ?>" />
				<input type="hidden" name="op" value="sm" />
				<fieldset class="options innerfieldset"><p><a href="#" onclick="document.getElementById('submitnewmessage').submit();" class="greenfield"><?php echo returnIntLang('str submit'); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel'); ?></a></p></fieldset>
				</form>
			</fieldset>
		<?php elseif($_POST['uid']=="all"): ?>
			<fieldset class="text">
				<legend><?php echo returnIntLang('user messages roundmail create'); ?></legend>
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="submitnewmessage">
				<table class="contenttable">
				<tr class="secondcol">
					<td width="25%"><?php echo returnIntLang('str recipient'); ?>:</td>
					<td width="75%"><?php echo returnIntLang('user messages allusers'); ?></td>
				</tr>
				<tr>
					<td width="25%"><?php echo returnIntLang('str message'); ?>:</td>
					<td width="75%"><textarea name="newmessage" id="newmessage" rows="3" cols="20" style="width: 95%;"></textarea></td>
				</tr>
				</table>
				<input type="hidden" name="submitfrom" value="<?php echo intval($_SESSION['wspvars']['userid']); ?>" />
				<input type="hidden" name="op" value="srm" />
				<fieldset class="options innerfieldset"><p><a href="#" onclick="document.getElementById('submitnewmessage').submit();" class="greenfield"><?php echo returnIntLang('str submit'); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel'); ?></a></p></fieldset>
				</form>
			</fieldset>
		<?php else: ?>
			<fieldset>
				<legend><?php echo returnIntLang('user message create'); ?> <?php echo showOpenerCloser('fieldset_3_content','open'); ?></legend>
				<span id="fieldset_3_content">
				<p><?php echo returnIntLang('hint nouser'); ?></p>
				</span>
			</fieldset>
		<?php endif; ?>
	<?php endif; ?>
	
	<fieldset>
		<legend><?php echo returnIntLang('user messages own'); ?> <?php echo legendOpenerCloser('mymessages'); ?></legend>
		<div id="mymessages">
		<?php

		$allmessage = unserializeBroken($_SESSION['wspvars']['messages']);
		
		if (count($allmessage)>0 && strlen(trim($wspvars['messages']))>4):
			echo "<table class=\"contenttable\">";
			echo "<tr class=\"tablehead\">";
			echo "<td width=\"25%\">".returnIntLang('str sender')."</td>";
			echo "<td width=\"25%\">".returnIntLang('str date')."</td>";
			echo "<td width=\"25%\">".returnIntLang('str message')."</td>";
			echo "<td width=\"25%\">".returnIntLang('str action')."</td>";
			echo "</tr>";
			
			foreach ($allmessage AS $key => $value):
				echo "<tr>";
				
				$userto_sql = "SELECT `realname` FROM `restrictions` WHERE `rid` = ".intval($value[0]);
				$userto_res = doSQL($userto_sql);
				if ($userto_res['num']>0):
					$msguser = setUTF8(trim($userto_res['set'][0]['realname']));
				elseif($value[0]=="all"):
					$msguser = "Alle Benutzer";
				else:
					$msguser = "Unbekannter Benutzer";
				endif;
				
				if ($value[3]==0):
					echo "<td width=\"25%\" valign=\"top\"><strong>".setUTF8($msguser)."</strong></td>";
				elseif ($value[3]==2):
					echo "<td width=\"25%\" valign=\"top\"><em>Gesendet an ".setUTF8($msguser)."</em></td>";
				else:
					echo "<td width=\"25%\" valign=\"top\">".setUTF8($msguser)."</td>";
				endif;
				if ($value[3]==0):
					echo "<td width=\"25%\" valign=\"top\"><strong>".date("d.m.Y H:i:s", $value[1])."</strong></td>";
				elseif ($value[3]==2):
					echo "<td width=\"25%\" valign=\"top\"><em>".date("d.m.Y H:i:s", $value[1])."</em></td>";
				else:
					echo "<td width=\"25%\" valign=\"top\">".date("d.m.Y H:i:s", $value[1])."</td>";
				endif;
				if ($value[3]==0):
					echo "<td width=\"25%\" valign=\"top\"><strong>".setUTF8(stripslashes($value[2]))."</strong></td>";
				elseif ($value[3]==2):
					echo "<td width=\"25%\" valign=\"top\"><em>".setUTF8(stripslashes($value[2]))."</em></td>";
				else:
					echo "<td width=\"25%\" valign=\"top\">".setUTF8(stripslashes($value[2]))."</td>";
				endif;
				echo "<td valign=\"top\" nowrap>";
				if ($value[3]==0):
					echo "<a href=\"#\" onclick=\"document.getElementById('readmessage_".$value[1]."').submit();\"><span class=\"bubblemessage green\">MARK READ</span></a> ";
				else:
					echo "<a href=\"#\" onclick=\"document.getElementById('deletemessage_".$value[1]."').submit();\"><span class=\"bubblemessage red\">DEL</span></a> ";
				endif;
				echo "<a href=\"#\" onclick=\"document.getElementById('createmessage_".$value[1]."').submit();\"><span class=\"bubblemessage green\">REPLY</span></a>";
				
				echo "<form action=\"/".$wspvars['wspbasedir']."/usernotice.php\" method=\"post\" id=\"createmessage_".$value[1]."\" style=\"margin: 0px; padding: 0px;\">\n";
				echo "<input type=\"hidden\" name=\"op\" value=\"nm\">\n";
				echo "<input type=\"hidden\" name=\"uid\" value=\"".$value[0]."\">\n";
				echo "</form>\n";
				
				echo "<form action=\"/".$wspvars['wspbasedir']."/usernotice.php\" method=\"post\" id=\"readmessage_".$value[1]."\" style=\"margin: 0px; padding: 0px;\">\n";
				echo "<input type=\"hidden\" name=\"op\" value=\"rm\">\n";
				echo "<input type=\"hidden\" name=\"rm\" value=\"".$value[1]."\">\n";
				echo "</form>\n";
				
				echo "<form action=\"/".$wspvars['wspbasedir']."/usernotice.php\" method=\"post\" id=\"deletemessage_".$value[1]."\" style=\"margin: 0px; padding: 0px;\">\n";
				echo "<input type=\"hidden\" name=\"op\" value=\"dm\">\n";
				echo "<input type=\"hidden\" name=\"dm\" value=\"".$value[1]."\">\n";
				echo "</form>\n";
				
				echo "</td>";
				echo "</tr>";
			endforeach;
			echo "</table>";
		else:
			echo "<p>".returnIntLang('home messages nomessage')."</p>";
		endif;
		
		?>
		</span>
	</fieldset>
</div>
<?php include ("data/include/footer.inc.php"); ?>
<!-- EOF -->