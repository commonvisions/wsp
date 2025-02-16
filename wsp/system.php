<?php
/**
 * system administration
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2023-01-09
 * 6.11.4
 * fixed error with wspkey
 * 
 * 6.11.5
 * removed unnesseccary "update filesystem" fieldset
 * 
 * 2025-01-19
 * Fixed missing array key warnings
 * Fixed missing files warnings
 */

// switching off errors to prevent update failures
error_reporting(E_ALL ^ E_DEPRECATED ^ E_STRICT);
ini_set('display_errors', 1);

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// first includes ----------------------------
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
// checkParamVar -----------------------------

// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = 'system';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = false;
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// page specific includes --------------------

// define page specific vars -----------------

$c = 0; $freespace = diskfreespace($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']);
while ($freespace>1024):
	$freespace = $freespace/1024;
	$c++;
endwhile;

// define page specific funcs
// head der datei

$op = checkParamVar('op', '');
$id = checkParamVar('id', '');

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");

flush();flush();flush();

?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('system headline'); ?></h1></fieldset>
	<?php 

	$gitData = false;

	if (_isCurl()) {
		$gitInfo = array( 
			CURLOPT_URL => "https://api.github.com/repos/commonvisions/wsp/releases/latest", 
			CURLOPT_HEADER => 0, 
			CURLOPT_RETURNTRANSFER => TRUE, 
			CURLOPT_TIMEOUT => 4, 
			CURLOPT_SSL_VERIFYPEER => FALSE, // Disable SSL verification if needed
			CURLOPT_HTTPHEADER => array(
				"User-Agent: WSP Updater"
			)
		);
		$ch = curl_init();
		curl_setopt_array($ch, $gitInfo);
		$gitResponse = curl_exec($ch);
		curl_close($ch);
		if (!empty($gitResponse)) {
			$gitData = json_decode($gitResponse, true);
		}
		$GIT = [
			'url' => $gitData['html_url'] ?? 'https://github.com/commonvisions/wsp/',
			'update' => date(returnIntLang('format date'), strtotime($gitData['published_at'] ?? date('Y-m-d H:i:s'))),
			'version' => $gitData['tag_name'],
		];
	}

	?>
	<fieldset class="text">
		<legend><?php echo returnIntLang('system sysinfo'); ?> <?php echo legendOpenerCloser('sysinfo'); ?></legend>
		<div id="sysinfo">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('system zend version'); ?></td>
					<td class="tablecell two"><?php echo zend_version(); ?></td>
					<td class="tablecell two"><?php echo returnIntLang('system php version'); ?></td>
					<td class="tablecell two"><?php echo phpversion(); ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('system mysql version'); ?></td>
					<td class="tablecell two"><?php echo (doSQL("SHOW GLOBAL VARIABLES LIKE '%version%'")['mysqli']); ?></td>
					<td class="tablecell two"><?php echo returnIntLang('system gdlib version'); ?></td>
					<td class="tablecell two"><?php $gdinfo = gd_info(); echo $gdinfo['GD Version']; ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('system wsp version'); ?></td>
					<td class="tablecell two"><?php echo $wspvars['wspversion']; ?></td>
					<td class="tablecell two"><?php echo returnIntLang('system free space'); ?></td>
					<td class="tablecell two"><?php
				
					$spacevals = array(
						1 => returnIntLang('system free space Byte'),
						2 => returnIntLang('system free space kB'),
						3 => returnIntLang('system free space MB'),
						4 => returnIntLang('system free space GB'),
						5 => returnIntLang('system free space TB')
						);
					
					echo ceil($freespace).' '.$spacevals[$c].'<br />';
					
					?></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('system structure entries all'); ?></td>
					<td class="tablecell two"><?php echo doSQL('SELECT `mid` FROM `menu`')['num']; ?></td>
					<td class="tablecell two"><?php echo returnIntLang('system structure entries active'); ?></td>
					<td class="tablecell two"><?php echo doSQL('SELECT `mid` FROM `menu` WHERE `trash` = 0')['num']; ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('system content entries all'); ?></td>
					<td class="tablecell two"><?php echo doSQL('SELECT `cid` FROM `content`')['num']; ?></td>
					<td class="tablecell two"><?php echo returnIntLang('system content entries active'); ?></td>
					<td class="tablecell two"><?php echo doSQL('SELECT `cid` FROM `content` WHERE `trash` = 0')['num']; ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('system post size'); ?></td>
					<td class="tablecell two"><?php echo ini_get('post_max_size'); ?></td>
					<td class="tablecell two"><?php echo returnIntLang('system upload size'); ?></td>
					<td class="tablecell two"><?php echo ini_get('upload_max_filesize'); ?></td>
				</tr>
			</table>
		</div>
	</fieldset>
	<?php if ($GIT ?? false) { ?>
	<fieldset class="text">
		<legend><?php echo returnIntLang('Information about GIT support', false); ?></legend>
		<p><?php echo sprintf(returnIntLang('Our self-hosted update service ended. You can download the latest version from <strong><a href="%1$s" target="_blank">github.com [↗]</a></strong> or use the github.com repository as your git source. Latest release version is <strong>%2$s</strong> from <strong>%3$s</strong>.', false), $GIT['url'], $GIT['version'], $GIT['update']); ?></p>
	</fieldset>
	<?php } else { ?>
		<fieldset class="text">
			<legend><?php echo returnIntLang('Information about GIT support', false); ?></legend>
			<p><?php echo sprintf(returnIntLang('Our self-hosted update service ended. You can download the latest version from <strong><a href="%1$s" target="_blank">github.com [↗]</a></strong> or use the github.com repository as your git source.', false), 'https://github.com/commonvisions/wsp/'); ?></p>
		</fieldset>
	<?php } ?>
</div>
<?php require ("./data/include/footer.inc.php"); ?>
<!-- EOF -->