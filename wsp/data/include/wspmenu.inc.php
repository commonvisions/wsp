<?php
/**
 * aufbau des menues
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 *
 * 2023-01-10
 * 6.11.1
 * Fixed error with "DELETE *"
 *
 * 2024-01-08
 * Fixed error with GROUP BY statements
 *
 */

if (!function_exists('buildWSPMenu')) {
	function buildWSPMenu($parent, $spaces, $rights) {
		$checkrights = [];

		if (is_array($rights)) {
			foreach ($rights as $key => $value) {
				if ($value == 1) {
					$checkrights[] = $key;
				}
			}
		}

		$wspmenu_sql = "SELECT `id`, `title`, `link`, `parent_id`, `position`, `guid`
			FROM `wspmenu`
			WHERE `parent_id` = " . intval($parent) . "
			ORDER BY `position`, `title`";
		$wspmenu_res = doSQL($wspmenu_sql);

		if ($wspmenu_res['num'] > 0) {
			foreach ($wspmenu_res['set'] as $wmrsv) {
				if (
					!in_array($wmrsv['guid'], $checkrights)
					&& (int) ($_SESSION['wspvars']['usertype'] ?? 0) !== 1
				) {
					continue;
				}

				$_SESSION['wspvars']['wspmodmenu'][$wmrsv['guid']] = [
					$spaces,
					intval($wmrsv['id']),
					intval($wmrsv['parent_id']),
					trim($wmrsv['title']),
					trim($wmrsv['link'])
				];

				if ($spaces == 0) {
					$_SESSION['wspvars']['wspmodmenucount']++;
				}

				$wspsubmenu_sql = "SELECT `id` FROM `wspmenu` WHERE `parent_id` = " . intval($wmrsv['id']);
				$wspsubmenu_res = doSQL($wspsubmenu_sql);
				if ($wspsubmenu_res['num'] > 0) {
					for ($smres = 0; $smres < $wspsubmenu_res['num']; $smres++) {
						buildWSPMenu(intval($wmrsv['id']), $spaces + 1, $rights);
					}
				}
			}
		}
	}
}

/**
* Hauptfunktion
*/
$menu = checkParamVar('menu', '');
$mp = 0;

// request defined standard template for preview/publisher/content

$standardtemp = intval(@doResultSQL(
	"SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'"
));
$isanalytics = trim(@doResultSQL(
	"SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'googleanalytics'"
));
$isextended = intval(@doResultSQL(
	"SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'extendedmenu'"
));

// disable filesystem functions without ftp etc
$fsaccess = false;
if (isset($_SESSION['wspvars']['ftpcon']) && $_SESSION['wspvars']['ftpcon']===true) {
	$fsaccess = true;
}
if (isset($_SESSION['wspvars']['directwriting']) && $_SESSION['wspvars']['directwriting']===true) {
	$fsaccess = true;
}

?>
<script type="text/javascript">

function jumpTo(jumpValue) {
	var jumpParts = jumpValue.split("_");
	if (jumpValue == 'logout') {
		window.location.href = '/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/logout.php';
		}
	else if (jumpParts[0] == 'site') {
		// ajax function to setup site
		$.post("xajax/ajax.setwspsite.php", { 'site': jumpParts[1] }).done(function(data) {});
		}
	else {
		$('li.level0').not('li.select').hide('fade');
		$('li.level0').not('li.select').css('display', 'none');
		$('li.' + jumpValue).show('fade');
		}
	}

function mobileJump(jumpValue) {
	window.location.href = jumpValue;
	}

</script>
<div id="menuholder" class="horizontal">
	<div id="togglenav">
		<i class="fa-solid fa-bars"></i>
	</div> 
	<nav><ul>
		<?php $mp = 1; ?>
		<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/index.php"><i class="fa-solid fa-house"></i> <?php echo returnIntLang('menu home'); ?></a> <ul class="basic level1">
			<li><a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/index.php"><?php echo returnIntLang('menu home cms'); ?></a></li>
			<?php if ($_SESSION['wspvars']['liveurl']==$_SESSION['wspvars']['workspaceurl']){ ?>
				<li><a href="http://<?php echo $_SESSION['wspvars']['liveurl']; ?>" target="_blank" title="<?php echo returnIntLang('hint newwindow', false); ?>"><?php echo returnIntLang('menu home'); ?> <?php echo returnIntLang('menu home website'); ?></a></li>
			<?php } else{ ?>
				<li><a href="http://<?php echo $_SESSION['wspvars']['liveurl']; ?>" target="_blank" title="<?php echo returnIntLang('hint newwindow', false); ?>"><?php echo returnIntLang('menu home website'); ?> (LIVE)</a></li>
				<li><a href="http://<?php echo $_SESSION['wspvars']['workspaceurl']; ?>" target="_blank" title="<?php echo returnIntLang('hint newwindow', false); ?>"><?php echo returnIntLang('menu home website'); ?> (DEV)</a></li>
			<?php } ?>
		</ul></li>
	<?php $mp = 2; if ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1) { ?>
		<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a><?php echo returnIntLang('menu user'); ?></a>
		<ul class="basic level1">
			<li class="level1" id="m_<?php echo $mp; ?>_0">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/usermanagement.php"><?php echo returnIntLang('menu user manage'); ?></a>
			</li>
			<li class="level1" id="m_<?php echo $mp; ?>_1">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/usershow.php"><?php echo returnIntLang('menu user login'); ?></a>
			</li>
			<?php if ($isextended==1){ ?>
				<li class="level1" id="m_<?php echo $mp; ?>_3">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/userhistory.php"><?php echo returnIntLang('menu user logs'); ?></a>
				</li>
			<?php } ?>
		</ul>
		</li>
	<?php } else { ?>
		<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a><?php echo returnIntLang('menu user'); ?></a> <?php

			if (isset($_SESSION['wspvars']['messages'])){
				$allmessage = unserializeBroken($_SESSION['wspvars']['messages']) ?: [];
				$i = 0;
				if (count($allmessage)>0 && strlen(trim($_SESSION['wspvars']['messages']))>4){
					foreach ($allmessage AS $key => $value) {
						if ($value[3]==0){
							$i++;
						}
					}
				}
				if ($i>0){
					echo "<span class=\"bubblemessageholder\"><span class=\"bubblemessage orange\" id=\"\">".$i."</span></span>";
				}
			}

			?>
			<ul class="basic level1">
				<li id="m_<?php echo $mp; ?>_0">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/usermanagement.php"><?php echo returnIntLang('menu user managedata'); ?></a>
				</li>
			</ul>
		</li>
	<?php } ?>

	<?php $mp = 3; if (
		($_SESSION['wspvars']['rights']['siteprops'] ?? 0) != 0) { ?>
		<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a><?php echo returnIntLang('menu siteprefs'); ?></a>
		<ul class="basic level1">
			<?php if ($isextended==1){ ?>
				<li class="level1" id="m_<?php echo $mp; ?>_2">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/siteprefs.php"><?php echo returnIntLang('menu siteprefs generell'); ?></a>
				</li>
				<li class="level1" id="m_<?php echo $mp; ?>_2">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/sites.php"><?php echo returnIntLang('menu siteprefs sites'); ?></a>
				</li>
				<li class="level1" id="m_<?php echo $mp; ?>_1">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/headerprefs.php"><?php echo returnIntLang('menu siteprefs redirects'); ?></a>
				</li>
			<?php } ?>
			<li class="level1" id="m_<?php echo $mp; ?>_3">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/semanagement.php"><?php echo returnIntLang('menu siteprefs seo'); ?></a>
			</li>
			<li class="level1" id="m_<?php echo $mp; ?>_4">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/analytics.php"><?php echo returnIntLang('menu siteprefs google'); ?></a>
			</li>
			<li class="level1" id="m_<?php echo $mp; ?>_4">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/privacy.php"><?php echo returnIntLang('menu siteprefs privacy'); ?></a>
			</li>
		</ul>
		</li>
		<?php } ?>

	<?php
	$mp = 4;
	if (($_SESSION['wspvars']['rights']['design'] ?? 0) != 0) { ?>
			<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a><?php echo returnIntLang('menu design'); ?></a> <ul class="basic level1">
			<?php if ($fsaccess) { ?><li class="level1" id="m_<?php echo $mp; ?>_0">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/screenmanagement.php"><?php echo returnIntLang('menu design media'); ?></a>
			</li>
			<?php if ($isextended==1) { ?>
				<li class="level1" id="m_<?php echo $mp; ?>_8">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/fontmanagement.php"><?php echo returnIntLang('menu design fonts'); ?></a>
				</li>
			<?php } ?>
			<?php } ?>
			<li class="level1" id="m_<?php echo $mp; ?>_1">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/designedit.php"><?php echo returnIntLang('menu design css'); ?></a>
			</li>
			<?php if ($isextended==1){ ?>
				<li class="level1" id="m_<?php echo $mp; ?>_7">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/scriptedit.php"><?php echo returnIntLang('menu design js'); ?></a>
				</li>
			<?php } ?>
			<li class="level1" id="m_<?php echo $mp; ?>_2">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/menutemplate.php"><?php echo returnIntLang('menu design menutmp'); ?></a>
			</li>
			<li class="level1" id="m_<?php echo $mp; ?>_3">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/selfvarsedit.php"><?php echo returnIntLang('menu design selfvars'); ?></a>
			</li>
			<li class="level1" id="m_<?php echo $mp; ?>_4">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/templates.php"><?php echo returnIntLang('menu design templates'); ?></a>
			</li>
			</ul>
			</li>
		<?php } ?>

	<?php
	$mp = 6;
	if (
		($_SESSION['wspvars']['rights']['imagesfolder'] ?? "0") != "0"
		|| ($_SESSION['wspvars']['rights']['downloadfolder'] ?? "0") != "0"
		|| ($_SESSION['wspvars']['rights']['flashfolder'] ?? "0") != "0"
	) {

		if ($fsaccess) {
			?>
			<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_6"><a><?php echo returnIntLang('menu files'); ?></a>
			<ul id="m_6s" class="basic level1">
				<?php if (($_SESSION['wspvars']['rights']['imagesfolder'] ?? 0)!="0") { ?>
				<li class="level1" id="m_6_1">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/imagemanagement.php"><?php echo returnIntLang('menu files images'); ?></a>
				</li>
				<?php } ?>
				<?php if (($_SESSION['wspvars']['rights']['downloadfolder'] ?? 0)!="0") { ?>
				<li class="level1" id="m_6_2">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/documentmanagement.php"><?php echo returnIntLang('menu files docs'); ?></a>
				</li>
				<?php } ?>
				<?php if (($_SESSION['wspvars']['rights']['videofolder'] ?? 0)!="0") { ?>
				<li class="level1" id="m_6_3">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/videomanagement.php"><?php echo returnIntLang('menu files video'); ?></a>
				</li>
				<?php } ?>
			</ul>
			</li>
		<?php
		}
		}

		if ($standardtemp>0) { /* allow structure/contents only with defined standard template */ ?>
		<?php
		$mp = 5;
		if (
			(($_SESSION['wspvars']['rights']['sitestructure'] ?? "0") != "0")
			|| (($_SESSION['wspvars']['rights']['contents'] ?? "0") != "0")
			|| ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)
		) { ?>
				<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a><?php echo returnIntLang('menu content'); ?></a>
				<ul class="basic level1">
					<?php if (($_SESSION['wspvars']['rights']['sitestructure'] ?? 0 || ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)) != 0){ ?>
						<li class="level1" id="m_<?php echo $mp; ?>_0">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/menuedit.php"><?php echo returnIntLang('menu content structure'); ?></a>
						</li>
					<?php } ?>
					<?php if (($_SESSION['wspvars']['rights']['contents'] ?? 0) != 0 || ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)){ ?>
						<li class="level1" id="m_<?php echo $mp; ?>_1">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/contentstructure.php"><?php echo returnIntLang('menu content contents'); ?></a>
						</li>
					<?php } ?>
					<?php if ($isextended==1) {
						$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
						if (((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)) { ?>
							<li class="level1" id="m_<?php echo $mp; ?>_4">
								<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/languagetools.php"><?php echo returnIntLang('menu content localize'); ?></a>
							</li>
						<?php }
						if (($_SESSION['wspvars']['rights']['contents'] ?? 0)==1 || ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)) {
							echo '<li class="level1" id="m_' . $mp . '_2">';
							echo '<a href="/' . $_SESSION['wspvars']['wspbasedir'] . '/globalcontent.php">';
							echo returnIntLang('menu content global');
							echo '</a></li>';
						}
					echo '</ul></li>';
					}
				} ?>
		<?php }
		$mp = 7;
		if (
			(int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1
			|| (
				($_SESSION['wspvars']['rights']['publisher'] ?? 0) != 0
				&& ($_SESSION['wspvars']['rights']['publisher'] ?? 0) < 100
			)
		) {
			if ($standardtemp>0){ // allow preview/publisher only with defined standard template

				$queue_sql = "SELECT `id` FROM `wspqueue` WHERE `done` = 0 GROUP BY `param`, `id`";
				$queue_res = doSQL($queue_sql);
				$queue_num = $queue_res['num'];

				if ($isextended==1) {
				// show publisher and queue link as submenupoints
				if ($fsaccess) {
				?>
				<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php"><?php echo returnIntLang('menu changed publisher'); ?> <?php if($queue_num>0){ echo "&nbsp;<span class='bubblemessage orange'>".$queue_num."</span>&nbsp;"; } ?></a> <ul class="basic level1">
					<li class="level1" id="m_<?php echo $mp; ?>_0">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php"><?php echo returnIntLang('menu changed'); ?></a>
					</li>
					<li class="level1" id="m_<?php echo $mp; ?>_1">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publishqueue.php"><?php echo returnIntLang('menu changed queue'); ?><?php if($queue_num>0){ echo "&nbsp;&nbsp;&nbsp;<span class='bubblemessage orange'>".$queue_num."</span>&nbsp;"; } ?></a>
					</li>
				</ul></li>
				<?php
				}
			}
				else {
					if ($fsaccess) {
					// show only publisher as main menupoint
					?>
					<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php"><?php echo returnIntLang('menu changed'); ?></a></li>
					<?php
					}
				} ?>
		<?php } ?>
		<?php } elseif (($_SESSION['wspvars']['rights']['contents'] ?? 0)!=0){ ?>
			<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php"><?php echo returnIntLang('menu changed preview'); ?></a></li>
		<?php } ?>

		<?php
		$mp = 10;
		if ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1) {
		?>
			<li class="basic level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a><?php echo returnIntLang('menu manage'); ?><?php if (isset($_SESSION['wspvars']['updatesystem']) && $_SESSION['wspvars']['updatesystem']===true){ echo " &nbsp;<span class='bubblemessage orange'>!</span>"; } ?></a>
			<ul id="m_10s" class="basic level1">
				<?php if (is_array($_SESSION['wspvars']['locallanguages']) && count($_SESSION['wspvars']['locallanguages'])>1){

					ksort($_SESSION['wspvars']['locallanguages'], SORT_STRING);

					?>
					<li><a><?php echo returnIntLang('menu manage language'); ?></a>
					<ul class="basic level2" id="m_lang" style="display: none;">
						<?php
						foreach ($_SESSION['wspvars']['locallanguages'] as $llkey => $llvalue) {
							echo "<li style=\"\"><a href=\"" . $_SERVER['PHP_SELF'] . "?setlang=" . $llkey . "\">" . $llvalue . "</a></li>";
						}
						?>
					</ul>
				</li>
				<?php } ?>
			<li class="level1" id="m_<?php echo $mp; ?>_0">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/editorprefs.php"><?php echo returnIntLang('menu manage editor'); ?></a>
			</li>
			<?php if ($isextended==1){ ?>
				<li class="level1" id="m_<?php echo $mp; ?>_4">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/dev.php"><?php echo returnIntLang('menu manage developer'); ?></a>
				</li>
				<?php if ($fsaccess) { ?>
				<li class="level1" id="m_<?php echo $mp; ?>_4">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/cleanup.php"><?php echo returnIntLang('menu manage cleanup'); ?></a>
				</li>
                <li class="level1" id="m_<?php echo $mp; ?>_6">
                    <a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/trash.php"><?php echo returnIntLang('menu content trash'); ?></a>
                </li>
				<?php } ?>
				<!-- <li class="level1" id="m_<?php echo $mp; ?>_6">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/editcon.php"><?php echo returnIntLang('menu manage connections'); ?></a>
				</li> -->
			<?php } ?>
			<li class="level1" id="m_<?php echo $mp; ?>_2">
				<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/modules.php"><?php echo returnIntLang('menu manage modules'); ?></a>
			</li>
			<?php if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/system.php")){ ?>
				<li class="level1" id="m_<?php echo $mp; ?>_3">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/system.php"><?php echo returnIntLang('menu manage system'); if (isset($_SESSION['wspvars']['updatesystem']) && $_SESSION['wspvars']['updatesystem']===true){ echo " &nbsp;<span class='bubblemessage orange'>!</span>"; } ?></a>
				</li>
			<?php } ?>
			</ul></li>
		<?php } else{ ?>
			<?php if (is_array($_SESSION['wspvars']['locallanguages']) && count($_SESSION['wspvars']['locallanguages'])>1){

			ksort($_SESSION['wspvars']['locallanguages'], SORT_STRING);

			?>
			<li class="basic level0"><a><?php echo returnIntLang('menu manage language'); ?></a> <ul class="basic level1" id="m_lang">
				<?php
				foreach ($_SESSION['wspvars']['locallanguages'] as $llkey => $llvalue) {
					echo "<li><a href=\"" . $_SERVER['PHP_SELF'] . "?setlang=" . $llkey . "\">" . $llvalue . "</a></li>";
				}
				?>
			</ul></li>
			<?php } ?>
		<?php } ?>
		<?php

		$_SESSION['wspvars']['wspmodmenu'] = array();
		$_SESSION['wspvars']['wspmodmenucount'] = 0;
		if (isset($_SESSION['wspvars']['rights'])){
			buildWSPMenu (0, 0, $_SESSION['wspvars']['rights']);
		}

		$mrun = 0;
		foreach ($_SESSION['wspvars']['wspmodmenu'] as $key => $value) {
			$showmodmenu[$mrun]['guid'] = $key;
			$showmodmenu[$mrun]['level'] = $value[0];
			$showmodmenu[$mrun]['id'] = $value[1];
			$showmodmenu[$mrun]['parent_id'] = $value[2];
			$showmodmenu[$mrun]['title'] = $value[3];
			$showmodmenu[$mrun]['link'] = $value[4];
			$mrun++;
		}

		if ($_SESSION['wspvars']['wspmodmenucount'] == 1) {
			echo "<li class=\"basic level0\"><a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[0]['id']."\" title=\"".$showmodmenu[0]['title']."\">".$showmodmenu[0]['title']."</a>";
			echo "<ul class=\"basic level1\">";
			$wmstart = 1;
		} elseif ($_SESSION['wspvars']['wspmodmenucount'] > 1) {
			echo "<li class=\"basic level0\" ";
			echo "><a>".returnIntLang('menu modmenu')."</a> ";
			echo "<ul class=\"basic level1\">";
			$wmstart = 0;
		}

		if ($_SESSION['wspvars']['wspmodmenucount'] > 0) {
			for ($wmrun = $wmstart; $wmrun < $mrun; $wmrun++) {
				$buf = "";
				if ($showmodmenu[$wmrun]['level'] == 0) {
					$buf .= "<li id=\"m_".$showmodmenu[$wmrun]['id']."\" >";
					if ($wmstart == 0 && key_exists($wmrun + 1, $showmodmenu) && $showmodmenu[$wmrun + 1]['level'] > 0) {
						$buf .= "\n<a>".$showmodmenu[$wmrun]['title']." ...</a>";
						$buf .= "<ul id=\"submod_".$showmodmenu[$wmrun]['id']."\" class=\"level2\" style=\"display: none;\">";
						while (isset($showmodmenu[$wmrun + 1]['level']) && $showmodmenu[$wmrun + 1]['level'] > 0) {
							$wmrun++;
							$buf .= "<li style=\"\">\n<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">".$showmodmenu[$wmrun]['title']."</a></li>";
						}
						$buf .= "</ul>";
					} else {
						$buf .= "\n<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">".$showmodmenu[$wmrun]['title']."</a>";
					}
					$buf .= "</li>";
				} elseif ($wmstart == 1 && $showmodmenu[$wmrun]['level'] > 0) {
					$buf .= "<li id=\"m_".$showmodmenu[$wmrun]['id']."\">";
					$buf .= "\n<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">".$showmodmenu[$wmrun]['title']."</a>";
					$buf .= "</li>";
				}
				echo $buf;
			}
		}

		if ($_SESSION['wspvars']['wspmodmenucount'] > 0) {
			echo "</ul></li>";
		}

		?>
	</ul>
    <ul style="float: right;">
        <li class="basic level0 cntdwn">
            <script>
                <!--

                TargetDate = "<?php echo date("m/d/Y h:i:s A", time()+(60*intval($_SESSION['wspvars']['autologout']))-1); ?>";
                CountActive = true;
                CountStepper = -1;
                LeadingZero = true;
                DisplayFormat = "%%H%%:%%M%%:%%S%%";

                function calcage(secs, num1, num2) {
                    s = ((Math.floor(secs/num1))%num2).toString();
                    if (LeadingZero && s.length < 2)
                        s = "0" + s;
                    return "<b>" + s + "</b>";
                }

                function CountBack(secs) {
                    if (secs < 0) {
                        window.location.href = '/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/logout.php';
                        return;
                    }
                    DisplayStr = DisplayFormat.replace(/%%D%%/g, calcage(secs,86400,100000));
                    DisplayStr = DisplayStr.replace(/%%H%%/g, calcage(secs,3600,24));
                    DisplayStr = DisplayStr.replace(/%%M%%/g, calcage(secs,60,60));
                    DisplayStr = DisplayStr.replace(/%%S%%/g, calcage(secs,1,60));

                    document.getElementById("cntdwn").innerHTML = DisplayStr;
                    setTimeout("CountBack(" + (secs+CountStepper) + ")", SetTimeOutPeriod);
                }

                function putspan() {
                    document.write("<span id='cntdwn'></span>");
                }

                CountStepper = Math.ceil(CountStepper);
                if (CountStepper == 0)
                    CountActive = false;
                var SetTimeOutPeriod = (Math.abs(CountStepper)-1)*1000 + 990;
                putspan();
                var dthen = new Date(TargetDate);
                var dnow = new Date();
                if(CountStepper>0)
                    ddiff = new Date(dnow-dthen);
                else
                    ddiff = new Date(dthen-dnow);
                gsecs = Math.floor(ddiff.valueOf()/1000);
                CountBack(gsecs);

                -->
            </script>
        </li>
        <li class="basic level0 px-2" style="display: initial">
            <a href="./logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </li>
    </ul>
</div>
<?php

$msgcleanup = "DELETE FROM `wspmsg` WHERE `read` = 1";
doSQL($msgcleanup);

?>
<fieldset id="dhtmltooltip"></fieldset>
<div id="topspacer">&nbsp;</div>
<div id="msgbar"></div>
<div id="infoholder"><fieldset id="locationholder"><?php if (key_exists('location', $_SESSION['wspvars']) && $_SESSION['wspvars']['location']!='') echo $_SESSION['wspvars']['location']; $_SESSION['wspvars']['location']=''; ?></fieldset>
<fieldset id="noticemsg" style="display: none;"></fieldset>
<fieldset id="errormsg" style="display: none;"></fieldset>
<fieldset id="resultmsg" style="display: none;"></fieldset></div>

<script language="JavaScript" type="text/javascript">

var offsetxpoint=-60 //Customize x offset of tooltip
var offsetypoint=20 //Customize y offset of tooltip
var ie=document.all
var ns6=document.getElementById && !document.all
var enabletip=false
if (ie||ns6)
var tipobj=document.all? document.all["dhtmltooltip"] : document.getElementById? document.getElementById("dhtmltooltip") : ""

function ietruebody() {
	return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
	}

function ddrivetip(thetext, thecolor, thewidth) {
	if (ns6||ie){
		if (typeof thewidth!="undefined") tipobj.style.width=thewidth+"px"
		if (typeof thecolor!="undefined" && thecolor!="") tipobj.style.backgroundColor=thecolor
		tipobj.innerHTML=thetext
		enabletip=true
		return false
		}
	}

function positiontip(e){
if (enabletip){
var curX=(ns6)?e.pageX : event.clientX+ietruebody().scrollLeft;
var curY=(ns6)?e.pageY : event.clientY+ietruebody().scrollTop;
//Find out how close the mouse is to the corner of the window
var rightedge=ie&&!window.opera? ietruebody().clientWidth-event.clientX-offsetxpoint : window.innerWidth-e.clientX-offsetxpoint-20
var bottomedge=ie&&!window.opera? ietruebody().clientHeight-event.clientY-offsetypoint : window.innerHeight-e.clientY-offsetypoint-20

var leftedge=(offsetxpoint<0)? offsetxpoint*(-1) : -1000

//if the horizontal distance isn't enough to accomodate the width of the context menu
if (rightedge<tipobj.offsetWidth)
//move the horizontal position of the menu to the left by it's width
tipobj.style.left=ie? ietruebody().scrollLeft+event.clientX-tipobj.offsetWidth+"px" : window.pageXOffset+e.clientX-tipobj.offsetWidth+"px"
else if (curX<leftedge)
tipobj.style.left="5px"
else
//position the horizontal position of the menu where the mouse is positioned
tipobj.style.left=curX+offsetxpoint+"px"

//same concept with the vertical position
if (bottomedge<tipobj.offsetHeight)
tipobj.style.top=ie? ietruebody().scrollTop+event.clientY-tipobj.offsetHeight-offsetypoint+"px" : window.pageYOffset+e.clientY-tipobj.offsetHeight-offsetypoint+"px"
else
tipobj.style.top=curY+offsetypoint+"px"
tipobj.style.visibility="visible"
}
}

function hideddrivetip(){
if (ns6||ie){
enabletip=false
tipobj.style.visibility="hidden"
tipobj.style.left="-1000px"
tipobj.style.backgroundColor=''
tipobj.style.width=''
}
}

document.onmousemove=positiontip

function blendItem(objID, start, blenddir) {
	if (blenddir=='hide') {
		$('#' + objID).slideUp(500);
		}
	else if (blenddir=='show') {
		$('#' + objID).slideDown(500);
		}
	}

if (document.getElementById('locationholder').innerHTML == '') {
	document.getElementById('locationholder').style.display = 'none';
	}

// -->
</script>
<?php

if (isset($_SESSION['wspvars']['devstat']) && $_SESSION['wspvars']['devstat']===true){
    $mgu = memory_get_usage(); $size = array('B','KB','MB','GB'); $m = 0;
    while ($mgu>1024) { $mgu = $mgu/1024; $m++; }
    echo "<p style='padding: 0px 1%; color: darkgreen;'>RAM ".round($mgu, 2)." ".$size[$m]."</p>";
}

// EOF ?>
