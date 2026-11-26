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
<div id="mainmenu">
	<nav>
		<ul>
			<?php $mp = 1; ?>
			<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>">
				<a>
					<span class="icon"><i class="fa-solid fa-house"></i></span>
					<span class="link"><?php echo returnIntLang('menu home'); ?></span>
					<i class="fa-solid fa-caret-left <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "fa-rotate-270";?>"></i>
				</a>
				<ul class="level1" <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "style='display: block'";?>>
					<li><a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/index.php">
						<span class="icon"><i class="fa-solid fa-gauge"></i></span>
						<span class="link"><?php echo returnIntLang('menu home cms'); ?></span>
					</a></li>
					<?php if ($_SESSION['wspvars']['liveurl']==$_SESSION['wspvars']['workspaceurl']){ ?>
						<li><a href="http://<?php echo $_SESSION['wspvars']['liveurl']; ?>" target="_blank" title="<?php echo returnIntLang('hint newwindow', false); ?>">
							<span class="icon"><i class="fa-solid fa-globe"></i></span>
							<span class="link"><?php echo returnIntLang('menu home'); ?> <?php echo returnIntLang('menu home website'); ?></span>
						</a></li>
					<?php } else{ ?>
						<li><a href="http://<?php echo $_SESSION['wspvars']['liveurl']; ?>" target="_blank" title="<?php echo returnIntLang('hint newwindow', false); ?>">
							<span class="icon"><i class="fa-solid fa-globe"></i></span>
							<span class="link"><?php echo returnIntLang('menu home website'); ?> (LIVE)</span>
						</a></li>
						<li><a href="http://<?php echo $_SESSION['wspvars']['workspaceurl']; ?>" target="_blank" title="<?php echo returnIntLang('hint newwindow', false); ?>">
							<span class="icon"><i class="fa-brands fa-dev"></i></span>
							<span class="link"><?php echo returnIntLang('menu home website'); ?> (DEV)</span>
						</a></li>
					<?php } ?>
			</ul></li>
			<?php $mp = 2; if ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1) { ?>
				<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>">
					<a>
						<span class="icon"><i class="fa-solid fa-user"></i></span>
						<span class="link"><?php echo returnIntLang('menu user'); ?></span>
						<i class="fa-solid fa-caret-left <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "fa-rotate-270";?>"></i>
					</a>
					<ul class="level1" <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "style='display: block;'";?>>
						<li class="level1" id="m_<?php echo $mp; ?>_0">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/usermanagement.php">
								<span class="icon"><i class="fa-solid fa-user-group"></i></span>
								<span class="link"><?php echo returnIntLang('menu user manage'); ?></span>
							</a>
						</li>
						<li class="level1" id="m_<?php echo $mp; ?>_1">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/usershow.php">
								<span class="icon"><i class="fa-solid fa-user-lock"></i></span>
								<span class="link"><?php echo returnIntLang('menu user login'); ?></span>
							</a>
						</li>
						<?php if ($isextended==1){ ?>
							<li class="level1" id="m_<?php echo $mp; ?>_3">
								<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/userhistory.php">
									<span class="icon"><i class="fa-solid fa-user-clock"></i></span>
									<span class="link"><?php echo returnIntLang('menu user logs'); ?></span>
								</a>
							</li>
						<?php } ?>
					</ul>
				</li>
			<?php } else { ?>
				<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>">
					<a><?php echo returnIntLang('menu user'); ?></a> <?php

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
				<ul class="level1">
					<li id="m_<?php echo $mp; ?>_0">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/usermanagement.php">
							<span class="icon"><i class="fa-regular fa-circle-question"></i></span>
							<span class="link"><?php echo returnIntLang('menu user managedata'); ?></span>
						</a>
					</li>
				</ul>
			</li>
		<?php } ?>

		<?php $mp = 3; if (
			($_SESSION['wspvars']['rights']['siteprops'] ?? 0) != 0) { ?>
			<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>">
				<a>
					<span class="icon"><i class="fa-brands fa-safari"></i></span>
					<span class="link"><?php echo returnIntLang('menu siteprefs'); ?></span>
					<i class="fa-solid fa-caret-left <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "fa-rotate-270";?>"></i>
				</a>
			<ul class="level1" <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "style='display: block;'";?>>
				<?php if ($isextended==1){ ?>
					<li class="level1" id="m_<?php echo $mp; ?>_2">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/siteprefs.php">
							<span class="icon"><i class="fa-solid fa-toolbox"></i></span>
							<span class="link"><?php echo returnIntLang('menu siteprefs generell'); ?></span>
						</a>
					</li>
					<!-- 
					<li class="level1" id="m_<?php echo $mp; ?>_2">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/sites.php">
							<span class="icon"><i class="fa-regular fa-circle-question"></i></span>
							<span class="link"><?php echo returnIntLang('menu siteprefs sites'); ?></span>
						</a>
					</li>
					-->
					<li class="level1" id="m_<?php echo $mp; ?>_1">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/headerprefs.php">
							<span class="icon"><i class="fa-solid fa-share-from-square"></i></span>
							<span class="link"><?php echo returnIntLang('menu siteprefs redirects'); ?></span>
						</a>
					</li>
				<?php } ?>
				<li class="level1" id="m_<?php echo $mp; ?>_3">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/semanagement.php">
						<span class="icon"><i class="fa-brands fa-google"></i></span>
						<span class="link"><?php echo returnIntLang('menu siteprefs seo'); ?></span>
					</a>
				</li>
				<li class="level1" id="m_<?php echo $mp; ?>_4">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/analytics.php">
						<span class="icon"><i class="fa-solid fa-chart-line"></i></span>
						<span class="link"><?php echo returnIntLang('menu siteprefs google'); ?></span>
					</a>
				</li>
				<li class="level1" id="m_<?php echo $mp; ?>_4">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/privacy.php">
						<span class="icon"><i class="fa-solid fa-user-shield"></i></span>
						<span class="link"><?php echo returnIntLang('menu siteprefs privacy'); ?></span>
					</a>
				</li>
			</ul>
			</li>
			<?php } ?>

		<?php
		$mp = 4;
		if (($_SESSION['wspvars']['rights']['design'] ?? 0) != 0) { ?>
				<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>">
					<a>
						<span class="icon"><i class="fa-solid fa-palette"></i></span>
						<span class="link"><?php echo returnIntLang('menu design'); ?></span>
						<i class="fa-solid fa-caret-left <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "fa-rotate-270";?>"></i>
					</a>
					<ul class="level1" <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "style='display: block'";?>>
				<?php if ($fsaccess) { ?><li class="level1" id="m_<?php echo $mp; ?>_0">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/screenmanagement.php">
						<span class="icon"><i class="fa-solid fa-image"></i></span>
						<span class="link"><?php echo returnIntLang('menu design media'); ?></span>
					</a>
				</li>
				<?php if ($isextended==1) { ?>
					<li class="level1" id="m_<?php echo $mp; ?>_8">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/fontmanagement.php">
							<span class="icon"><i class="fa-brands fa-fonticons"></i></span>
							<span class="link"><?php echo returnIntLang('menu design fonts'); ?></span>
						</a>
					</li>
				<?php } ?>
				<?php } ?>
				<li class="level1" id="m_<?php echo $mp; ?>_1">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/designedit.php">
						<span class="icon"><i class="fa-brands fa-css"></i></span>
						<span class="link"><?php echo returnIntLang('menu design css'); ?></span>
					</a>
				</li>
				<?php if ($isextended==1){ ?>
					<li class="level1" id="m_<?php echo $mp; ?>_7">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/scriptedit.php">
							<span class="icon"><i class="fa-brands fa-square-js"></i></span>
							<span class="link"><?php echo returnIntLang('menu design js'); ?></span>
						</a>
					</li>
				<?php } ?>
				<li class="level1" id="m_<?php echo $mp; ?>_2">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/menutemplate.php">
						<span class="icon"><i class="fa-solid fa-rectangle-list"></i></span>
						<span class="link"><?php echo returnIntLang('menu design menutmp'); ?></span>
					</a>
				</li>
				<li class="level1" id="m_<?php echo $mp; ?>_3">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/selfvarsedit.php">
						<span class="icon"><i class="fa-solid fa-code"></i></span>
						<span class="link"><?php echo returnIntLang('menu design selfvars'); ?></span>
					</a>
				</li>
				<li class="level1" id="m_<?php echo $mp; ?>_4">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/templates.php">
						<span class="icon"><i class="fa-regular fa-file-code"></i></span>
						<span class="link"><?php echo returnIntLang('menu design templates'); ?></span>
					</a>
				</li>
				</ul>
				</li>
			<?php } ?>

		<?php
		$mp = 6;
		if (
			($_SESSION['wspvars']['rights']['imagesfolder'] ?? "0") != "0"
			|| ($_SESSION['wspvars']['rights']['downloadfolder'] ?? "0") != "0"
		) {

			if ($fsaccess) {
				?>
				<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_6">
					<a>
						<span class="icon"><i class="fa-solid fa-file"></i></span>
						<span class="link"><?php echo returnIntLang('menu files'); ?></span>
						<i class="fa-solid fa-caret-left <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "fa-rotate-270";?>"></i>
					</a>
					<ul class="level1" <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "style='display: block;'";?>>
					<?php if (($_SESSION['wspvars']['rights']['imagesfolder'] ?? 0)!="0") { ?>
						<li class="level1" id="m_6_1">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/imagemanagement.php">
								<span class="icon"><i class="fa-solid fa-file-image"></i></span>
								<span class="link"><?php echo returnIntLang('menu files images'); ?></span>
							</a>
						</li>
						<?php } ?>
						<?php if (($_SESSION['wspvars']['rights']['downloadfolder'] ?? 0)!="0") { ?>
						<li class="level1" id="m_6_2">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/documentmanagement.php">
								<span class="icon"><i class="fa-solid fa-file-arrow-down"></i></span>
								<span class="link"><?php echo returnIntLang('menu files docs'); ?></span>
							</a>
						</li>
						<?php } ?>
						<?php if (($_SESSION['wspvars']['rights']['videofolder'] ?? 0)!="0") { ?>
						<li class="level1" id="m_6_3">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/videomanagement.php">
								<span class="icon"><i class="fa-solid fa-file-video"></i></span>
								<span class="link"><?php echo returnIntLang('menu files video'); ?></span>
							</a>
						</li>
						<?php } ?>
					</ul>
				</li>
			<?php
			}
			}

			if ($standardtemp>0) { /* allow structure/contents only with defined standard template */
				$mp = 5;
				if (
					(($_SESSION['wspvars']['rights']['sitestructure'] ?? "0") != "0")
					|| (($_SESSION['wspvars']['rights']['contents'] ?? "0") != "0")
					|| ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)
				) { ?>
					<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>">
						<a>
							<span class="icon"><i class="fa-solid fa-file-invoice"></i></span>
							<span class="link"><?php echo returnIntLang('menu content'); ?></span>
							<i class="fa-solid fa-caret-left <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "fa-rotate-270";?>"></i>
						</a>
						<ul class="level1" <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "style='display: block'";?>>
							<?php if (($_SESSION['wspvars']['rights']['sitestructure'] ?? 0 || ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)) != 0){ ?>
								<li class="level1" id="m_<?php echo $mp; ?>_0">
									<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/menuedit.php">
										<span class="icon"><i class="fa-solid fa-sitemap"></i></span>
										<span class="link"><?php echo returnIntLang('menu content structure'); ?></span>
									</a>
								</li>
							<?php } ?>
							<?php if (($_SESSION['wspvars']['rights']['contents'] ?? 0) != 0 || ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)){ ?>
								<li class="level1" id="m_<?php echo $mp; ?>_1">
									<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/contentstructure.php">
										<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
										<span class="link"><?php echo returnIntLang('menu content contents'); ?></span>
									</a>
								</li>
							<?php } ?>
							<?php if ($isextended==1) {
								$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
								if (((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)) { ?>
									<li class="level1" id="m_<?php echo $mp; ?>_4">
										<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/languagetools.php">
											<span class="icon"><i class="fa-solid fa-spell-check"></i></span>
											<span class="link"><?php echo returnIntLang('menu content localize'); ?></span>
										</a>
									</li>
								<?php }
								if (($_SESSION['wspvars']['rights']['contents'] ?? 0)==1 || ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1)) {
									echo '<li class="level1" id="m_' . $mp . '_2">';
									echo '<a href="/' . $_SESSION['wspvars']['wspbasedir'] . '/globalcontent.php">';
									echo '<span class="icon"><i class="fa-brands fa-buffer"></i></span>';
									echo '<span class="link">' . returnIntLang('menu content global') . '</span>';
									echo '</a></li>';
								}
							}
						echo '</ul></li>';
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
					<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>">
						<a>
							<span class="icon"><i class="fa-solid fa-cloud-arrow-up"></i>
							<?php if($queue_num>0) {
								echo "<div class='badge badge-sm badge-float bg-danger'>".$queue_num."</div>"; 
							} ?></span>
							<span class="link"><?php echo returnIntLang('menu changed publisher'); ?></span>
							<i class="fa-solid fa-caret-left <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "fa-rotate-270";?>"></i>
						</a>
						<ul class="level1" <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "style='display: block'";?>>
							<li class="level1" id="m_<?php echo $mp; ?>_0">
								<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php">
									<span class="icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
									<span class="link"><?php echo returnIntLang('menu changed'); ?></span>
								</a>
							</li>
							<li class="level1" id="m_<?php echo $mp; ?>_1">
								<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publishqueue.php">
									<span class="icon"><i class="fa-solid fa-hourglass-half"></i></span>
									<span class="link"><?php echo returnIntLang('menu changed queue'); ?></span>
								</a>
							</li>
						</ul>
					</li>
					<?php
					}
				}
					else {
						if ($fsaccess) {
						// show only publisher as main menupoint
						?>
						<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>">
							<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php">
								<span class="icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
								<span class="link"><?php echo returnIntLang('menu changed'); ?></span>
							</a>
						</li>
						<?php
						}
					} ?>
			<?php } ?>
			<?php } elseif (($_SESSION['wspvars']['rights']['contents'] ?? 0)!=0){ ?>
				<li class="level0 <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "active";?>" id="m_<?php echo $mp; ?>"><a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/publisher.php"><?php echo returnIntLang('menu changed preview'); ?></a></li>
			<?php } ?>

			<?php
			$mp = 10;
			if ((int) ($_SESSION['wspvars']['usertype'] ?? 0) === 1) {
			?>
				<li class="level0 hassub <?php if($_SESSION['wspvars']['mgroup'] == $mp) echo "active";?>" id="m_<?php echo $mp; ?>">
					<a>
						<span class="icon"><i class="fa-solid fa-gear"></i></span>
						<span class="link"><?php echo returnIntLang('menu manage'); ?></span>
						<i class="fa-solid fa-caret-left <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "fa-rotate-270";?>"></i>
					</a>
				<ul id="m_10s" class="level1" <?php if($_SESSION['wspvars']['mgroup']==$mp) echo "style='display: block'";?>">
					<?php if (is_array($_SESSION['wspvars']['locallanguages']) && count($_SESSION['wspvars']['locallanguages'])>1){
						ksort($_SESSION['wspvars']['locallanguages'], SORT_STRING);
						?>
						<li class="level1 hassub">
							<a>
								<span class="icon"><i class="fa-solid fa-language"></i></span>
								<span class="link"><?php echo returnIntLang('menu manage language'); ?></span>
								<i class="fa-solid fa-caret-left"></i>
							</a>
							<ul class="level2">
								<?php
								foreach ($_SESSION['wspvars']['locallanguages'] as $llkey => $llvalue) {
									echo "<li>";
									echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?setlang=" . $llkey . "\">";
									echo "<span class='icon'><strong>" . strtoupper($llkey) . "</strong></span>";
									echo "<span class='link'>" . $llvalue . "</span>";
									echo "</a></li>";
								}
								?>
							</ul>
						</li>
					<?php } ?>
				<li class="level1" id="m_<?php echo $mp; ?>_0">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/editorprefs.php">
						<span class="icon"><i class="fa-regular fa-circle-question"></i></span>
						<span class="link"><?php echo returnIntLang('menu manage editor'); ?></span>
					</a>
				</li>
				<?php if ($isextended==1){ ?>
					<li class="level1" id="m_<?php echo $mp; ?>_4">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/dev.php">
							<span class="icon"><i class="fa-regular fa-circle-question"></i></span>
							<span class="link"><?php echo returnIntLang('menu manage developer'); ?></span>
						</a>
					</li>
					<?php if ($fsaccess) { ?>
					<li class="level1" id="m_<?php echo $mp; ?>_4">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/cleanup.php">
							<span class="icon"><i class="fa-regular fa-circle-question"></i></span>
							<span class="link"><?php echo returnIntLang('menu manage cleanup'); ?></span>
						</a>
					</li>
					<li class="level1" id="m_<?php echo $mp; ?>_6">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/trash.php">
							<span class="icon"><i class="fa-regular fa-circle-question"></i></span>
							<span class="link"><?php echo returnIntLang('menu content trash'); ?></span>
						</a>
					</li>
					<?php } ?>
				<?php } ?>
				<li class="level1" id="m_<?php echo $mp; ?>_2">
					<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/modules.php">
						<span class="icon"><i class="fa-solid fa-box-archive"></i></span>
						<span class="link"><?php echo returnIntLang('menu manage modules'); ?></span>
					</a>
				</li>
				<?php if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/system.php")){ ?>
					<li class="level1" id="m_<?php echo $mp; ?>_3">
						<a href="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/system.php">
							<span class="icon">…</span>
							<span class="link"><?php echo returnIntLang('menu manage system'); if (isset($_SESSION['wspvars']['updatesystem']) && $_SESSION['wspvars']['updatesystem']===true){ echo " &nbsp;<span class='bubblemessage orange'>!</span>"; } ?></span>
						</a>
					</li>
				<?php } ?>
				</ul></li>
			<?php } else{ ?>
				<?php if (is_array($_SESSION['wspvars']['locallanguages']) && count($_SESSION['wspvars']['locallanguages'])>1){

				ksort($_SESSION['wspvars']['locallanguages'], SORT_STRING);

				?>
				<li class="level0"><a><?php echo returnIntLang('menu manage language'); ?></a>
					<ul class="level1" id="m_lang">
					<?php
					foreach ($_SESSION['wspvars']['locallanguages'] as $llkey => $llvalue) {
						echo "<li>";
						echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?setlang=" . $llkey . "\">" . $llvalue . "</a></li>";
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
				echo "<li class=\"level0\"><a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[0]['id']."\" title=\"".$showmodmenu[0]['title']."\">".$showmodmenu[0]['title']."</a>";
				echo "<ul class=\"level1\">";
				$wmstart = 1;
			} elseif ($_SESSION['wspvars']['wspmodmenucount'] > 1) {
				echo "<li class='level0 hassub'>";
				echo "<a><span class='icon'><i class='fa-solid fa-boxes-stacked'></i></span>";
				echo "<span class='link'>".returnIntLang('menu modmenu')."</span>";
				echo "<i class='fa-solid fa-caret-left'></i></a>";
				echo "<ul class='level1'>";
				$wmstart = 0;
			}

			if ($_SESSION['wspvars']['wspmodmenucount'] > 0) {
				for ($wmrun = $wmstart; $wmrun < $mrun; $wmrun++) {
					$buf = "";
					if ($showmodmenu[$wmrun]['level'] == 0) {
						$buf .= "<li class='hassub' id=\"m_".$showmodmenu[$wmrun]['id']."\" >";
						if ($wmstart == 0 && key_exists($wmrun + 1, $showmodmenu) && $showmodmenu[$wmrun + 1]['level'] > 0) {
							$buf .= "\n<a>";
							$buf .= "<span class='icon'><i class='fa-solid fa-box-open'></i></span>";
							$buf .= "<span class='link'>" . $showmodmenu[$wmrun]['title']."</span>";
							$buf .= "<i class='fa-solid fa-caret-left'></i></a>";
							$buf .= "<ul id=\"submod_".$showmodmenu[$wmrun]['id']."\" class=\"level2\" style=\"display: none;\">";
							while (isset($showmodmenu[$wmrun + 1]['level']) && $showmodmenu[$wmrun + 1]['level'] > 0) {
								$wmrun++;
								$buf .= "<li style=\"\">\n";
								$buf .= "<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">";
								$buf .= "<span class='icon'><i class='fa-solid fa-circle-dot'></i></span>";
								$buf .= '<span class="link">' . $showmodmenu[$wmrun]['title'] . '</span>'; 
								$buf .= "</a></li>";
							}
							$buf .= "</ul>";
						} else {
							$buf .= "<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">";
							$buf .= "<span class='icon'><i class='fa-regular fa-circle-dot'></i></span>";
							$buf .= '<span class="link">' . $showmodmenu[$wmrun]['title'] . '</span>';
							$buf .= "</a>";
						}
						$buf .= "</li>";
					} elseif ($wmstart == 1 && $showmodmenu[$wmrun]['level'] > 0) {
						$buf .= "<li id=\"m_".$showmodmenu[$wmrun]['id']."\">";
						$buf .= "<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/modgoto.php?modid=".$showmodmenu[$wmrun]['id']."\" title=\"".$showmodmenu[$wmrun]['title']."\">";
						$buf .= "<span class='icon'><i class='fa-solid fa-circle-dot'></i></span>";
						$buf .= '<span class="link">' . $showmodmenu[$wmrun]['title'] . '</span>';
						$buf .= "</a>";
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
	</nav>
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

<script>

	$('.hassub > a .icon, .hassub > a .fa-caret-left').on('click', function() {
		$(this).closest('li.hassub > a').find('.fa-caret-left').toggleClass('fa-rotate-270');
		$(this).closest('li.hassub > a').next('ul').slideToggle();
	});


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
