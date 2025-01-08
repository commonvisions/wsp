<?php
/**
 * Userverwaltung
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-22
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

/* define actual system position ------------- */
$_SESSION['wspvars']['menuposition'] = "userhistory";
$_SESSION['wspvars']['mgroup'] = 2;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
/* define page specific funcs ----------------- */
if (isset($_POST['op']) && $_POST['op']=="cl"):
	$sql = "DELETE FROM `securitylog` WHERE `uid` = ".intval($_POST['userrid'])." LIMIT ".intval($_POST['countrows']);
    $res = doSQL($sql);
	if ($res['res']):
        addWSPMsg('noticemsg', intval($_POST['countrows'])." ".returnIntLang('userlog rowsdeleted', true));
	endif;
endif;

include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");
?>

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('userlog'); ?></h1></fieldset>
	<?php
	
	if ($_SESSION['wspvars']['usertype']==1):

		$usercheck_sql = "SELECT * FROM `restrictions` ORDER BY `user` ASC";
		$usercheck_res = doSQL($usercheck_sql);

        if(isset($sid) && intval($sid)>0):
			$userlist_open_stat = 'closed';
		else:
			$userlist_open_stat = 'open';
		endif;

		if ($usercheck_res['num']>0): ?>
			<script language="JavaScript1.2" type="text/javascript">
			<!--
			
			function checkClearLog(logid, logname, logrows) {
				var countrows = prompt('<?php echo setUTF8(returnIntLang('userlog confirmdeletecountrows', false)); ?>', logrows*1);
				if (countrows!=null && countrows>0) {
					if (countrows>logrows) {
						document.getElementById('countrows_' + logid).value = logrows*1;
						}
					else {
						document.getElementById('countrows_' + logid).value = countrows*1;
						}
					document.getElementById('clearlogs_' + logid).submit();
					}
				}
			
			// -->
			</script>
			<fieldset>
				<legend><?php echo returnIntLang('userlog show userlogs'); ?> <?php echo legendOpenerCloser('userlogs'); ?></legend>
				<div id="userlogs">
				<ul class="tablelist">
					<li class="tablecell two head"><?php echo returnIntLang('str user'); ?></li>
					<li class="tablecell two head"><?php echo returnIntLang('userlog lastlogin'); ?></li>
					<li class="tablecell two head"><?php echo returnIntLang('userlog showcount'); ?></li>
					<li class="tablecell two head"><?php echo returnIntLang('str action'); ?></li>
				<?php
				$usernames = array(); 
				foreach ($usercheck_res['set'] AS $uresk => $uresv) {
					$usernames[intval($uresv['rid'])] = trim($uresv['realname']);
					echo "<li class=\"tablecell two\"><a onclick=\"document.getElementById('showlogs_".intval($uresv['rid'])."').submit();\" style=\"cursor: pointer;\">";
					echo trim($uresv['realname']);
					echo "</a></li>";
					
					$log_sql = "SELECT `lastchange` FROM `securitylog` WHERE `uid` = ".intval($uresv['rid'])." ORDER BY `lastchange` DESC";
					$log_res = doSQL($log_sql);
                    if ($log_res['num']>0):
						$lastlogin = date("Y-m-d", $log_res['set'][0]['lastchange']);
					else:
						$lastlogin = returnIntLang('userlog nologin');
					endif;
					
					echo "<li class=\"tablecell two\">".$lastlogin."</li>";
					echo "<li class=\"tablecell two\">".intval($log_res['num'])."</li>";
					echo "<li class=\"tablecell two\">";
					if ($log_res['num']>0):
						echo "<a onClick=\"document.getElementById('showlogs_".intval($uresv['rid'])."').submit();\"><span class=\"bubblemessage green\">".strtoupper(returnIntLang('bubble showlog', false))."</span></a> ";
						echo "<a onClick=\"checkClearLog('".intval($uresv['rid'])."','".trim($uresv['realname'])."',".intval($log_res['num']).");\"><span class=\"bubblemessage red\">".strtoupper(returnIntLang('bubble clearlog', false))."</span></a> ";
						
						echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"clearlogs_".intval($uresv['rid'])."\" style=\"margin: 0px; padding: 0px;\">\n";
						echo "<input type=\"hidden\" name=\"userrid\" value=\"".intval($uresv['rid'])."\">\n";
						echo "<input type=\"hidden\" name=\"op\" value=\"cl\">\n";
						echo "<input type=\"hidden\" id=\"countrows_".intval($uresv['rid'])."\" name=\"countrows\" value=\"1\">\n";
						echo "</form>\n";
						echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" id=\"showlogs_".intval($uresv['rid'])."\" style=\"margin: 0px; padding: 0px;\">\n";
						echo "<input type=\"hidden\" name=\"userrid\" value=\"".intval($uresv['rid'])."\">\n";
						echo "<input type=\"hidden\" name=\"op\" value=\"sl\">\n";
						echo "</form>\n";
					endif;
					echo "</li>";
                }
				?>
				</ul></div>
			</fieldset>
		<?php endif; ?>
		<?php if(isset($_POST) && array_key_exists('op', $_POST) && array_key_exists('userrid', $_POST) && $_POST['op']=='sl' && intval($_POST['userrid'])>0): 
			
			$log_num = 0;
			$log_sql = "SELECT * FROM `securitylog` WHERE `uid` = ".intval($_POST['userrid'])." ORDER BY `lastchange` DESC";
			$log_res = doSQL($log_sql);
			
			if ($log_res['num']>0):
				?>
				<fieldset>
					<?php
					
					if ($log_res['num']<101):
						$showlog = $log_res['num'];
					else:
						$showlog = 101;
					endif;
					
					?>
					<legend><?php echo returnIntLang('userlog last'); echo "&nbsp;".$showlog."&nbsp;"; echo returnIntLang('userlog log for'); ?> '<?php echo $usernames[intval($_POST['userrid'])]; ?>'</legend>
					<ul id="loglist" class="tablelist">
					<?php 
					
					for ($log=1; $log<$showlog; $log++):
						echo "<li class=\"tablecell two\">".date("Y-m-d H:i:s", intval($log_res['set'][$log]['lastchange']))."</li>";
						echo "<li class=\"tablecell two\">";
						if (trim($log_res['set'][$log]['lastaction'])=='login'):
							echo "Login";
						else:
							if (trim($log_res['set'][$log]['lastposition'])!=''):
								$posparam = explode(";",trim($log_res['set'][$log]['lastposition']));
								if (isset($posparam[1]) && trim($posparam[1])!=''):
									$posdesc = returnIntLang('userlog posdesc '.str_replace("//", "/", str_replace("//", "/", str_replace("/".$_SESSION['wspvars']['wspbasedir']."/", "/", $posparam[0]))), false);
									$posdesc.= " : ".$posparam[1];
								else:
									$posdesc = returnIntLang('userlog posdesc '.str_replace("//", "/", str_replace("//", "/", str_replace("/".$_SESSION['wspvars']['wspbasedir']."/", "/", trim($log_res['set'][$log]['lastposition'])))), false);
								endif;
								
								echo $posdesc." ".returnIntLang(trim('userlog action '.trim($log_res['set'][$log]['lastaction'])));
							else:
								echo returnIntLang('userlog pageload success');
							endif;
						endif;
						echo "</li>";
					endfor;
					
					?>
					</ul>
				</fieldset>
			<?php else: ?>
				<fieldset><p><?php echo returnIntLang('userlog no logs for user'); ?></p></fieldset>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->