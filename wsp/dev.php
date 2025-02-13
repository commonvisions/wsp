<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
// only for DEV-page !!!
$_SESSION['wspvars']['realdevstat'] = $_SESSION['wspvars']['devstat'];
$_SESSION['wspvars']['devstat'] = true;
//
require ("./data/include/globalvars.inc.php");
// first includes ----------------------------
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false; // tells user, to save data before leaving page
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");

if (isset($_POST['save_data'])) {
	// save changes
	$setstat = array("false", "true");
	$usestatfile = "<?php
/**
 * @since 3.1
 * @version 7.".time()."
 * @lastchange ".date('Y-m-d')."
 */

\$_SESSION['wspvars']['devstat'] = ".$setstat[intval($_POST['devstat'])]."; // true | false
\$_SESSION['wspvars']['showdeverrors'] = ".$setstat[intval($_POST['showdeverrors'])]."; // true | false
\$_SESSION['wspvars']['logdeverrors'] = ".$setstat[intval($_POST['logdeverrors'])]."; // true | false
\$_SESSION['wspvars']['showdevmsg'] = ".$setstat[intval($_POST['showdevmsg'])]."; // true | false
\$_SESSION['wspvars']['devcontent'] = ".$setstat[intval($_POST['devcontent'])]."; // true | false
\$_SESSION['wspvars']['xajaxdebug'] = false; // true | false
\$_SESSION['wspvars']['debugcontent'] = ".$setstat[intval($_POST['debugcontent'])]."; // true | false
\$_SESSION['wspvars']['showsql'] = ".$setstat[intval($_POST['showsql'])]."; // true | false
\$_SESSION['wspvars']['displaystyle'] = \"".$_POST['displaystyle']."\" // iconized | text

// EOF ?>";
	
	$fh = fopen($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/usestat.inc.php", "w+");
	fwrite($fh, $usestatfile);

    $ftp = doFTP();
    if ($ftp) {
        if (ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/usestat.inc.php", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/usestat.inc.php", FTP_BINARY)) {
            addWSPMsg('noticemsg', 'could upload usestat.inc.php');
        } else {
            addWSPMsg('errormsg', 'could not put usestat.inc.php');
        }
        ftp_close($ftp);
    } else {
        addWSPMsg('errormsg', 'could not upload usestat.inc.php');
    }
	header("location: dev.php");
	
}
if (isset($_POST['sqlquery'])) {
    // make sql statements
    $sqlresult = '';
    $sqlquery = '';
    if ($_SESSION['wspvars']['usertype']==1 || $_SESSION['wspvars']['usertype']=='admin') {
        if (isset($_POST['sqlquery']) && trim($_POST['sqlquery'])!='') {
            $sqlquery = trim($_POST['sqlquery']);
            $sqltest_sql = $sqlquery;
            $sqltest_res = doSQL($sqltest_sql);
            $sqltest_num = 0; 
            if ($sqltest_res['res']) {
                $sqltest_num = $sqltest_res['num'];
                $sqlresult = "<pre>".var_export($sqltest_res, true)."</pre>";
            } else {
                $sqlresult = "<pre>".var_export($sqltest_res['err'], true)."</pre>";
            }
        }
    }
}

// head der datei
include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");
?>
<div id="contentholder">
	<fieldset class="text"><h1>Developer Settings</h1></fieldset>
	<fieldset class="text"><p>Activate or deactivate development settings. The results of these settings are visible to all users.</p></fieldset>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="frmprefs" style="margin: 0px;">
	<fieldset>
		<table border="0" cellspacing="0" cellpadding="3" width="100%">
			<tr class="firstcol">
				<td width="25%">Variables Box</td>
				<td width="25%"><input type="checkbox" name="devstat" value="1" <?php if(array_key_exists('realdevstat', $_SESSION['wspvars']) && $_SESSION['wspvars']['realdevstat']) echo "checked=\"checked\""; ?> /></td>
				<td width="25%">shows box containing all defined variables</td>
			</tr>
			<tr class="secondcol">
				<td width="25%">Log Publishing</td>
				<td width="25%"><input type="checkbox" name="logdeverrors" value="1" <?php if(array_key_exists('logdeverrors', $_SESSION['wspvars']) && $_SESSION['wspvars']['logdeverrors']) echo "checked=\"checked\""; ?> /></td>
				<td width="25%">logs publishing in tmp folder</td>
			</tr>
			<tr class="secondcol">
				<td width="25%">Show development messages</td>
				<td width="25%"><input type="checkbox" name="showdevmsg" value="1" <?php if(array_key_exists('showdevmsg', $_SESSION['wspvars']) && $_SESSION['wspvars']['showdevmsg']) echo "checked=\"checked\""; ?> /></td>
				<td width="25%">shows dev output, if returned</td>
			</tr>
			<tr class="secondcol">
				<td width="25%">Show development params</td>
				<td width="25%"><input type="checkbox" name="devcontent" value="1" <?php if(array_key_exists('devcontent', $_SESSION['wspvars']) && $_SESSION['wspvars']['devcontent']) echo "checked=\"checked\""; ?> /></td>
				<td width="25%">shows development params such as ids, hiddenfields</td>
			</tr>
			<tr class="secondcol">
				<td width="25%">Show AJAX contentdebug output</td>
				<td width="25%"><input type="checkbox" name="debugcontent" value="1" <?php if(array_key_exists('debugcontent', $_SESSION['wspvars']) && $_SESSION['wspvars']['debugcontent']) echo "checked=\"checked\""; ?> /></td>
				<td width="25%">shows xajax contentdebug output, if returned</td>
			</tr>
			<tr class="firstcol">
				<td width="25%">Show SQL messages</td>
				<td width="25%"><input type="checkbox" name="showsql" value="1" <?php if(isset($_SESSION['wspvars']['showsql']) && $_SESSION['wspvars']['showsql']) echo "checked=\"checked\""; ?> /></td>
				<td width="50%">shows sql output, if new styled sql-requests returned</td>
			</tr>
			<tr class="secondcol">
				<td width="25%">Displaystyle</td>
				<td width="25%"><select name="displaystyle" size="1">
					<option value="text" <?php if(isset($_SESSION['wspvars']['displaystyle']) && $_SESSION['wspvars']['displaystyle']=="text") echo "selected=\"selected\""; ?>>Text</option>
					<option value="iconized" <?php if(isset($_SESSION['wspvars']['displaystyle']) && $_SESSION['wspvars']['displaystyle']=="iconized") echo "selected=\"selected\""; ?>>Icons</option>
				</select></td>
				<td width="50%">use icons or text for displaying buttons</td>
			</tr>
		</table>
		<fieldset class="options innerfieldset">
			<p><a href="#" onclick="document.getElementById('frmprefs').submit(); return false;" class="greenfield">save</a><input name="save_data" type="hidden" value="Speichern" /></p>
		</fieldset>
	</fieldset>
	</form>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="sqltest" style="margin: 0px;">
	<fieldset>
		<legend>SQL Tester <?php echo legendOpenerCloser('sqltester'); ?></legend>
		<div id="sqltester">
			<ul class="tablelist">
				<li class="tablecell two">SQL query</li>
				<li class="tablecell six"><textarea name="sqlquery" class="full large" ><?php echo (isset($sqlquery)?$sqlquery:''); ?></textarea></li>
				<?php if (isset($sqlresult) && trim($sqlresult)!=''): ?>
					<li class="tablecell two">SQL result</li>
					<li class="tablecell six"><?php echo $sqlresult; ?></li>
					<li class="tablecell six"><?php echo $sqlresultdata; ?></li>
				<?php endif; ?>
			</ul>
			<fieldset class="options innerfieldset">
				<p><a href="#" onclick="document.getElementById('sqltest').submit(); return false;" class="greenfield">submit</a></p>
			</fieldset>
		</div>
	</fieldset>
	</form>
</div>
<?php

include ("./data/include/footer.inc.php");
$_SESSION['wspvars']['devstat'] = false;

// EOF ?>