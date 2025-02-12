<?php
/**
 * website publisher
 * @author s.haendler@covi.de
 * @copyright (c) 2021, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.10
 * @lastchange 2021-03-02
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'publisherqueue';
$_SESSION['wspvars']['mgroup'] = 7;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

/* define page specific functions ------------ */

// temporaere loesung, bis ueberall das neue
// rechtesystem umgesetzt werden kann

if (isset($_POST) && array_key_exists('jobid', $_POST) && intval($_POST['jobid'])>0):
	$cpsql = "DELETE FROM `wspqueue` WHERE `id` = ".intval($_POST['jobid']);
	doSQL($cpsql);
endif;

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

?>
<div id="contentholder">
	<pre id="debugcontent"></pre>
	<fieldset><h1><?php echo returnIntLang('queue headline'); ?></h1></fieldset>
	<fieldset>
		<legend><?php echo returnIntLang('str legend'); ?> <?php echo legendOpenerCloser('wsplegend', 'closed'); ?></legend>
		<div id="wsplegend" style="<?php echo $_SESSION['opentabs']['wsplegend']; ?>">
			<p><?php echo returnIntLang('queue legend', true); ?></p>
		</div>
	</fieldset>
	<?php
	
	$_SESSION['publishrun'] = 0;
	
	$queue_sql = "SELECT * FROM `wspqueue` WHERE `done` = 0 GROUP BY `param` ORDER BY `priority` DESC, `action` ASC, `set` ASC, `id` ASC";
	$queue_res = doSQL($queue_sql);

	if ($queue_res['num']>0):
		?>
		<fieldset>
			<legend><?php echo returnIntLang('publisher queue', true); ?></legend>
			
			<table class="tablelist">
				<tr>
					<td class="tablecell head one info"><?php echo returnIntLang('queue setuptime'); ?></td>
					<td class="tablecell head two info"><?php echo returnIntLang('queue user'); ?></td>
					<td class="tablecell head one info"><?php echo returnIntLang('queue action'); ?></td>
					<td class="tablecell head two info"><?php echo returnIntLang('queue param'); ?></td>
					<td class="tablecell head one info"><?php echo returnIntLang('queue timeout'); ?></td>
					<td class="tablecell head one info"><?php echo returnIntLang('str action'); ?></td>
				</tr>
				<?php foreach ($queue_res['set'] AS $qresk => $qresv): ?>
					<tr>
						<td class="tablecell one"><?php echo date('d.m.Y H:i', intval($qresv['set'])); ?> <?php if (intval($qresv['priority'])>1): echo "<span class='bubblemessage red'>".intval($qresv['priority'])."</span>"; endif; ?></td>
						<td class="tablecell two info"><?php 
						
						$usrinfo_sql = "SELECT `realname` FROM `restrictions` WHERE `rid` = ".intval($qresv['uid']);
						$usrinfo_res = doResultSQL($usrinfo_sql);
						if (trim($usrinfo_res)!=''): echo trim($usrinfo_res); else: echo returnIntLang('queue user system', false); endif;
						
						?></td>
						
						<td class="tablecell one"><?php echo returnIntLang('queue action '.$qresv['action']); ?></td>
						<td class="tablecell two"><?php 
						
						if (intval($qresv['param'])==$qresv['param']) {
							$mnuinfo_sql = "SELECT `description`, `langdescription` FROM `menu` WHERE `mid` = ".intval($qresv['param']);
							$mnuinfo_res = doSQL($mnuinfo_sql);
							if ($mnuinfo_res['num']>0) {
								if (trim($mnuinfo_res['set'][0]['langdescription'])=='') {
									if (strpos($mnuinfo_res['set'][0]['description'], 'tofile-')==2) {
										echo returnIntLang('queue autofile');
									} else {
										echo trim($mnuinfo_res['set'][0]['description']);
									}
								}
								else {
									$langdesc = unserializeBroken($mnuinfo_res['set'][0]['langdescription']);
									if (is_array($langdesc) && count($langdesc)>0) {
										if (isset($langdesc[trim($qresv['lang'])])) {
											echo trim($langdesc[trim($qresv['lang'])]);
										}
										else {
											echo trim($mnuinfo_res['set'][0]['description'])." [".trim($qresv['lang'])."]";
										}
									}
									else {
										if (strpos($mnuinfo_res['set'][0]['description'], 'tofile-')==2) {
											echo returnIntLang('queue autofile');
										} else {
											echo trim($mnuinfo_res['set'][0]['description']);
										}
									}
								}
							}
							else {
								echo trim($qresv['param']); 
								if(trim($qresv['lang'])!='') { 
									echo " ; ".trim($qresv['lang']); 
								}
							}
                        }
                        else {
							echo $qresv['param']; ?> <?php if(trim($qresv['lang'])!=''): echo " ; ".trim($qresv['lang']); endif; 
                        }
							
						?></td>
						<td class="tablecell one info"><?php if (intval($qresv['timeout'])>0): echo date('d.m.Y H:i', intval($qresv['timeout'])); else: echo returnIntLang('queue timeout running'); endif; ?></td>
						<td class="tablecell"><a onclick="document.getElementById('jobid').value = <?php echo intval($qresv['id']); ?>; document.getElementById('killjob').submit();"><span class="bubblemessage red"><?php echo returnIntLang('queue kill job', false); ?></span></a></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<form name="killjob" id="killjob" method="post"><input type="hidden" name="jobid" id="jobid" value="" /></form>
		</fieldset>
	<?php else: 
		echo '<fieldset id="showqueue"><p>'.returnIntLang('queue no jobs in queue').'</p></fieldset>';
	endif; 
	
	$subqueue_sql = "SELECT `id` FROM `wspqueue` WHERE `done` = -1 GROUP BY `param`";
	$subqueue_res = doSQL($subqueue_sql);
	
	if ($subqueue_res['num']>0) {
		echo '<fieldset id="showqueue"><p>'.returnIntLang('queue jobs in subqueue').'</p></fieldset>';
	}
	
	?>
	</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->