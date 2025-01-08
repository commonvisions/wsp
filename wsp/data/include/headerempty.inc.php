<?php
/**
 *
 * @author COVI
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8
 * @lastchange 2018-09-18
 */

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="<?php echo $int; ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="author" content="http://www.covi.de">
	<meta name="copyright" content="http://www.covi.de">
	<meta name="publisher" content="http://www.covi.de">
	<meta name="robots" content="nofollow">
	<title><?php echo $wspvars['sitetitle']; ?></title>
	<link rel="stylesheet" href="<?php if (array_key_exists('wspvars', $_SESSION) && array_key_exists('wspbasedir', $_SESSION['wspvars'])): echo "/".$_SESSION['wspvars']['wspbasedir']; else: echo '/wsp'; endif; ?>/media/layout/flexible.css.php" media="screen" type="text/css">
	<!-- print_screen extensions -->
	<link rel="stylesheet" href="<?php if (array_key_exists('wspvars', $_SESSION) && array_key_exists('wspbasedir', $_SESSION['wspvars'])): echo "/".$_SESSION['wspvars']['wspbasedir']; else: echo '/wsp'; endif; ?>/media/layout/print.css.php" media="print" type="text/css">
	<!-- self colorize extensions -->
	<?php if (array_key_exists('wspstyle', $_SESSION['wspvars']) && trim($_SESSION['wspvars']['wspstyle'])!=""): echo "<link rel=\"stylesheet\" href=\"/".$_SESSION['wspvars']['wspbasedir']."/media/layout/".trim($_SESSION['wspvars']['wspstyle']).".css.php\" media=\"screen\" type=\"text/css\">\n"; else: echo "<link rel=\"stylesheet\" href=\"/".$_SESSION['wspvars']['wspbasedir']."/media/layout/wsp.css.php\" media=\"screen\" type=\"text/css\">\n"; endif; ?>
</head>
<body style="margin: 0px">
<?php // EOF ?>