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
error_reporting(E_ALL ^ E_DEPRECATED);
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

$structureReport = null;
$structurePreview = null;
$structurePreviewHash = '';
$structureFile = __DIR__ . DIRECTORY_SEPARATOR . 'structure.xml';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['op'] ?? ''), ['previewstructure', 'applystructure'], true)) {
	if (!is_file($structureFile) || !is_readable($structureFile)) {
		$structureReport = ['success' => false, 'actions' => [], 'errors' => ['Die Datei wsp/structure.xml wurde nicht gefunden oder kann nicht gelesen werden.']];
	} else {
		require_once __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'dbtools.inc.php';
		$dbTools = new dbTools($_SESSION['wspvars']['db'] ?? null);
		$structureXml = file_get_contents($structureFile);
		if ($_POST['op'] === 'previewstructure') {
			$structurePreview = $dbTools->previewStructure($structureXml);
			if ($structurePreview['success']) $structurePreviewHash = hash('sha256', $structureXml);
		} elseif (isset($_POST['structure_hash']) && hash_equals((string)$_POST['structure_hash'], hash('sha256', $structureXml))) {
			$structureReport = $dbTools->applyStructure($structureXml);
		} else {
			$structureReport = ['success' => false, 'actions' => [], 'errors' => ['Die Vorschau fehlt oder wsp/structure.xml wurde seit der Vorschau geändert. Bitte erneut prüfen.']];
		}
	}
}

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");

flush();flush();flush();

?>
<div id="contentholder">
	<div class="breadcrumb">
		<h1>Verwaltung</h1>
		<h1><?php echo returnIntLang('system headline'); ?></h1>
	</div>
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
			'version' => $gitData['tag_name'] ?? ('#6.' . date('y'))
		];
	}

	?>
	<?php if ($GIT ?? false) { ?>
		<div class="card" id="fieldset_sysinfo">
			<div class="card-header card-header-sm bg-warning">
				<strong><?php echo returnIntLang('Information about GIT support', false); ?></strong>
			</div>
			<div class="card-body">
				<p><?php echo sprintf(returnIntLang('Our self-hosted update service ended. You can download the latest version from <strong><a href="%1$s" target="_blank">github.com [↗]</a></strong> or use the github.com repository as your git source. Latest release version is <strong>%2$s</strong> from <strong>%3$s</strong>.', false), $GIT['url'], $GIT['version'], $GIT['update']); ?></p>
			</div>
		</div>
	<?php } else { ?>
		<div class="card" id="fieldset_sysinfo">
			<div class="card-header card-header-sm bg-warning">
				<strong><?php echo returnIntLang('Information about GIT support', false); ?></strong>
			</div>
			<div class="card-body">
				<p><?php echo sprintf(returnIntLang('Our self-hosted update service ended. You can download the latest version from <strong><a href="%1$s" target="_blank">github.com [↗]</a></strong> or use the github.com repository as your git source.', false), 'https://github.com/commonvisions/wsp/'); ?></p>
			</div>
		</div>
	<?php } ?>
	<div class="card" id="fieldset_sysinfo">
		<div class="card-header card-header-sm">
			<?php echo cardOpenerCloser(returnIntLang('system sysinfo'), 'sysinfo'); ?>
		</div>
		<div class="card-body striped" id="sysinfo">
			<div class="row mb-1">
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system zend version'); ?>
						<input type="text" class="form-control" value="<?php echo zend_version(); ?>" readonly />
					</label>
				</div>
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system php version'); ?>
						<input type="text" class="form-control" value="<?php echo phpversion(); ?>" readonly />
					</label>
				</div>
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system mysql version'); ?>
						<input type="text" class="form-control" value="<?php echo (doSQL("SHOW GLOBAL VARIABLES LIKE '%version%'")['mysqli']); ?>" readonly />
					</label>
				</div>
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system gdlib version'); ?>
						<input type="text" class="form-control" value="<?php $gdinfo = gd_info(); echo $gdinfo['GD Version'] ?? '-'; ?>" readonly />
					</label>
				</div>
			</div>
			<div class="row mb-1">
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system wsp version'); ?>
						<input type="text" class="form-control" value="<?php echo WSPVERSION; ?>" readonly />
					</label>
				</div>
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system free space'); ?>
						<input type="text" class="form-control" value="<?php
				
						$spacevals = [
							1 => returnIntLang('system free space Byte'),
							2 => returnIntLang('system free space kB'),
							3 => returnIntLang('system free space MB'),
							4 => returnIntLang('system free space GB'),
							5 => returnIntLang('system free space TB')
						];
						
						echo ceil($freespace).' '.$spacevals[$c];
				
						?>" readonly />
					</label>
				</div>
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system post size'); ?>
						<input type="text" class="form-control" value="<?php echo ini_get('post_max_size'); ?>" readonly />
					</label>
				</div>
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system upload size'); ?>
						<input type="text" class="form-control" value="<?php echo ini_get('upload_max_filesize'); ?>" readonly />
					</label>
				</div>
			</div>
			<div class="row mb-1">
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system structure entries all'); ?>
						<input type="text" class="form-control" value="<?php echo doSQL('SELECT `mid` FROM `menu`')['num']; ?>" readonly />
					</label>
				</div>
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system structure entries active'); ?>
						<input type="text" class="form-control" value="<?php echo doSQL('SELECT `mid` FROM `menu` WHERE `trash` = 0')['num']; ?>" readonly />
					</label>
				</div>
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system content entries all'); ?>
						<input type="text" class="form-control" value="<?php echo doSQL('SELECT `cid` FROM `content`')['num']; ?>" readonly />
					</label>
				</div>
				<div class="col-3">
					<label class="d-block">
						<?php echo returnIntLang('system content entries active'); ?>
						<input type="text" class="form-control" value="<?php echo doSQL('SELECT `cid` FROM `content` WHERE `trash` = 0')['num']; ?>" readonly />
					</label>
				</div>
			</div>
		</div>
	</div>
	<div class="card" id="fieldset_dbinfo">
		<div class="card-header card-header-sm">
			<?php echo cardOpenerCloser('Datenbankintegrität prüfen', 'dbinfo'); ?>
		</div>
		<div class="card-body" id="dbinfo">
			<p>Die Datei <code>structure.xml</code> wird mit den Tabellen der aktuellen WSP-Installation abgeglichen. Fehlende Tabellen und Felder können angelegt, abweichende Felddefinitionen angepasst werden. Vorhandene Tabellen oder Felder, die nicht in der Struktur definiert sind, werden dabei nicht gelöscht.</p>
			<?php if (is_array($structurePreview) && $structurePreview['success']) { ?>
			<table class="table table-striped">
				<thead>
					<tr>
						<th>Tabelle</th>
						<th>Feld</th>
						<th>Aktion</th>
						<th>Details</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($structurePreview['tables'] as $tableName) {
					$tableChanges = array_values(array_filter($structurePreview['changes'], function ($change) use ($tableName) { return $change['table'] === $tableName; }));
					if (!$tableChanges) { ?>
						<tr><td><?php echo htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8'); ?></td><td colspan="3">Keine Änderungen erforderlich.</td></tr>
					<?php } else { foreach ($tableChanges as $change) { ?>
						<tr><td><?php echo htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($change['field'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($change['operation'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($change['details'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
					<?php } }
				} ?>
				</tbody>
			</table>
			<?php if (!empty($structurePreview['changes'])) { ?>
				<p>Der bestehende Abgleich löscht keine Tabellen oder Felder.</p>
				<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" style="display:inline"><input type="hidden" name="op" value="applystructure" /><input type="hidden" name="structure_hash" value="<?php echo htmlspecialchars($structurePreviewHash, ENT_QUOTES, 'UTF-8'); ?>" /><button type="submit" class="greenfield">Angezeigte Änderungen bestätigen und ausführen</button></form>
				<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" style="display:inline"><input type="hidden" name="op" value="cancelstructure" /><button type="submit" class="orangefield">Abbrechen</button></form>
			<?php } ?>
		<?php } elseif (is_array($structurePreview)) { ?><ul class="errormsg"><?php foreach ($structurePreview['errors'] as $error) { ?><li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li><?php } ?></ul><?php } ?>
		<?php if (is_array($structureReport)) { ?>
			<h3><?php echo $structureReport['success'] ? 'Abgleich erfolgreich.' : 'Abgleich mit Fehlern abgeschlossen.'; ?></h3>
			<?php if (!empty($structureReport['actions'])) { ?><ul><?php foreach ($structureReport['actions'] as $action) { ?><li><?php echo htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?></li><?php } ?></ul><?php } else { ?><p>Keine Änderungen erforderlich.</p><?php } ?>
			<?php if (!empty($structureReport['errors'])) { ?><ul class="errormsg"><?php foreach ($structureReport['errors'] as $error) { ?><li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li><?php } ?></ul><?php } ?>
		<?php } ?>

			<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post">
				<input type="hidden" name="op" value="previewstructure" />
				<p><button type="submit" class="btn btn-success">Datenbankvergleich anzeigen</button></p>
			</form>
		</div>
	</div>
</div>
<?php require ("./data/include/footer.inc.php"); ?>
<!-- EOF -->
