<?php
/**
 * @author stefan@covi.de
 * @since 3.1.2
 * @version GIT
*/

ksort($_SESSION);

if (!(isset($_POST['previewid']))):
	
	$errormsgarray = array();
	$wspDir = ($_SESSION['wspvars']['wspbasedir'] ?? 'wsp');

	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html lang="de">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<meta name="author" content="http://www.covi.de">
		<meta name="copyright" content="http://www.covi.de">
		<meta name="publisher" content="http://www.covi.de">
		<meta name="robots" content="nofollow">
		<title><?php echo ($_SESSION['wspvars']['sitetitle'] ?? 'WSP Admin Panel'); ?></title>
		<!-- viewport definitions especially for mobile devices -->
		<meta name="viewport" content="width=device-width, user-scalable=no">
		<!-- WSP 6.27 ASSETS integration -->
		<link rel="stylesheet" href="/<?php echo $wspDir; ?>/assets/css/bootstrap.min.css">
		<link rel="stylesheet" href="/<?php echo $wspDir; ?>/assets/css/bootstrap-theme.min.css">
		<link rel="stylesheet" href="/<?php echo $wspDir; ?>/assets/css/dataTables.dataTables.min.css">
		<link rel="stylesheet" id="assets-fontawesome" href="/<?php echo $wspDir; ?>/assets/css/all.min.css">
		<script src="/<?php echo $wspDir; ?>/assets/js/jquery.min.js"></script>
		<script src="/<?php echo $wspDir; ?>/assets/js/jquery-migrate.min.js"></script>
		<script src="/<?php echo $wspDir; ?>/assets/js/jquery-ui.min.js"></script>
		<script src="/<?php echo $wspDir; ?>/assets/js/bootstrap.min.js"></script>
		<script src="/<?php echo $wspDir; ?>/assets/js/dataTables.min.js"></script>
		<script src="/<?php echo $wspDir; ?>/assets/js/dataTables.dataTables.min.js"></script>
		<!-- base desktop stylesheet -->
		<link rel="stylesheet" href="/<?php echo $wspDir; ?>/media/layout/flexible.css.php" media="screen" type="text/css">
		<!-- icons -->
		<link rel="shortcut icon" href="/<?php echo $wspDir; ?>/media/screen/favicon.ico">
		<link rel="apple-touch-icon" href="/<?php echo $wspDir; ?>/media/screen/iphone_favicon.png" />
		<!-- get WSP supported and/or required base scripts -->
		<script src="/<?php echo $wspDir; ?>/data/script/basescript.js.php"></script>
		<!-- fancyBox -->
		<link rel="stylesheet" href="/<?php echo $wspDir; ?>/data/script/fancybox/jquery.fancybox.css?v=2.1.5" type="text/css" media="screen" />
		<script type="text/javascript" src="/<?php echo $wspDir; ?>/data/script/fancybox/jquery.fancybox.pack.js?v=2.1.5"></script>
		<!-- fancybox helpers -->
		<link rel="stylesheet" href="/<?php echo $wspDir; ?>/data/script/fancybox/helpers/jquery.fancybox-buttons.css?v=1.0.5" type="text/css" media="screen" />
		<script type="text/javascript" src="/<?php echo $wspDir; ?>/data/script/fancybox/helpers/jquery.fancybox-buttons.js?v=1.0.5"></script>
		<script type="text/javascript" src="/<?php echo $wspDir; ?>/data/script/fancybox/helpers/jquery.fancybox-media.js?v=1.0.6"></script>
		<link rel="stylesheet" href="/<?php echo $wspDir; ?>/data/script/fancybox/helpers/jquery.fancybox-thumbs.css?v=1.0.7" type="text/css" media="screen" />
		<script type="text/javascript" src="/<?php echo $wspDir; ?>/data/script/fancybox/helpers/jquery.fancybox-thumbs.js?v=1.0.7"></script>
		<?php
		
		if(array_key_exists('wspvars', $_SESSION) && key_exists('userid', $_SESSION['wspvars']) && $_SESSION['wspvars']['userid']>0):
			?>
			<script type="text/javascript">
			
			$(window).load(function(){
				$(".opencloseButton" ).click(function() {
					var options = {};
					
					$('#' + $(this).attr("rel")).toggle('blind', options, 300, returnSetOpenTab($(this).attr("rel")));
					
					passLiTable('ul.tablelist', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
					return false;
					});
				});
			
			function returnSetOpenTab(tabname) {
				$.post("/<?php echo $_SESSION['wspvars']['wspbasedir'] ?>/xajax/ajax.setopentab.php", { 'tabname': tabname, 'tabstatus': $('#' + tabname).css('display')});
				}
				
			function updateMsg(uid) {
				$.post("/<?php echo $_SESSION['wspvars']['wspbasedir'] ?>/xajax/ajax.updatemsg.php", { 'uid': uid })
					.done (function(data) {
						$('#msgbar').html(data);
						})
				}
			
			function updateMsgClose(msgid) {
				$.post("/<?php echo $_SESSION['wspvars']['wspbasedir'] ?>/xajax/ajax.updatemsgclose.php", { 'msgid': msgid });
				}
			
			function backgroundPublish(msgid) {
				$.post("/<?php echo $_SESSION['wspvars']['wspbasedir'] ?>/xajax/ajax.backgroundpublish.php").done (function(data) {
					if (data!='') {
						callBackgroundPublish();
						console.log(data.log);
						};
					});
				}

			function showInnerMsg(msgtype) {
				$.post("/<?php echo $_SESSION['wspvars']['wspbasedir'] ?>/xajax/ajax.showinnermsg.php", { 'msgtype': msgtype })
					.done (function(data) {
						if (data) {
							$('#'+msgtype).html(data);
							$('#'+msgtype).toggle('blind');
							$(window).scrollTop(0);
							}
						})
				}
			
			</script>
		<?php if(intval($_SESSION['wspvars']['menustyle'])==1 && intval($_SESSION['wspvars']['userid'])>0): ?>
			<link rel="stylesheet" href="/<?php echo $wspDir; ?>/media/layout/vertical.css.php" media="screen" type="text/css">
			<link rel="stylesheet" type="text/css" href="/<?php echo $wspDir; ?>/data/script/ddmenu/css/ddsmoothmenu-v.css" />
			<script type="text/javascript">
			
			$(window).load(function(){
				$("#topholderback" ).attr('class', 'vertical');
				$("#topholder" ).attr('class', 'vertical');
				$('#menuholder ul li').each(function(index) {
					if (<?php if(key_exists('plugin', $_SESSION['wspvars']) && trim($_SESSION['wspvars']['plugin'])!=''): ?>$(this).hasClass('<?php echo $_SESSION['wspvars']['plugin']; ?>') || <?php else: ?>$(this).hasClass('basic') || <?php endif; ?>$(this).hasClass('select') || $(this).hasClass('cntdwn')) {
						$(this).fadeIn(300);
						}
					});
				$('#menuholder ul li').hover(function() {
					$(this).children('ul.level1').toggle('slide', 200);
					});
				});
			
			</script>
		<?php else: ?>
			<link rel="stylesheet" href="/<?php echo $wspDir; ?>/media/layout/horizontal.css.php" media="screen" type="text/css">
			<script type="text/javascript">
			
			$(window).load(function(){
				$('#menuholder ul li').each(function(index) {
					if (<?php if(key_exists('plugin', $_SESSION['wspvars']) && trim($_SESSION['wspvars']['plugin'])!=''): ?>$(this).hasClass('<?php echo $_SESSION['wspvars']['plugin']; ?>') || <?php else: ?>$(this).hasClass('basic') || <?php endif; ?>$(this).hasClass('select') || $(this).hasClass('cntdwn')) {
						$(this).fadeIn(300);
						}
					});
				
				$('#menuholder ul li.level0').hover(function() {
					$(this).toggleClass('activeover');
					$('#menuholder ul li.level0').each(function(){
						$(this).removeClass('activeover');
						$(this).children('ul.level1').hide('blind', 100);
						});
					$(this).find('ul.level1').stop(true, true).toggle('blind', 100);
					});
					
				$('#menuholder ul.level1 li').hover(function() {
					$(this).find('ul.level2').stop(true, true).toggle('slide', 100);
					});
					
				});
			
			</script>
		<?php endif; ?>
		<?php endif; ?>
	</head>
	<body id="wspbody">
	<?php 
	if (key_exists('wsperror', $_SESSION['wspvars']) && trim($_SESSION['wspvars']['wsperror'])!=""):
		echo "<p style=\"color: #CC0000;\">".$_SESSION['wspvars']['wsperror']."</p>";
	endif;
endif;

if (key_exists('userid', $_SESSION['wspvars']) && intval($_SESSION['wspvars']['userid'])>0): ?>
<div id="topholderback"></div>
<div id="topholder"></div>
<?php endif; ?>
<?php // EOF ?>