<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-22
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

if (isset($_POST['conhost']) && trim($_POST['conhost'])!='' && isset($_POST['conlocation']) && trim($_POST['conlocation'])!='' && isset($_POST['conuser']) && trim($_POST['conuser'])!='' && isset($_POST['conpass']) && trim($_POST['conpass'])!=''):
	/*
    if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/phpmailer/class.phpmailer.php")):
		require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/phpmailer/class.phpmailer.php";
		
		$mail = new phpmailer();
		$mail->IsSMTP(); // per SMTP verschicken
		$mail->Host = trim($_POST['conhost']); // SMTP-Server
		$mail->SMTPAuth = true;     // SMTP mit Authentifizierung benutzen
		$mail->Username = trim($_POST['conuser']);  // SMTP-Benutzername
		$mail->Password = trim($_POST['conpass']); // SMTP-Passwort
		$mailhost = $_SERVER['HTTP_HOST'];
		$mail->CharSet = 'utf-8';
		$mail->From = "smtptest@".$mailhost;
		$mail->FromName = "WSP SMTP Test";
		$mail->AddAddress("sh@covi.de",$_SESSION['wspvars']['realname']);
		$mail->AddReplyTo("noreply@".$mailhost,returnIntLang('editcon mailtest no answer', false));
		$mail->WordWrap = 50;
		$mail->IsHTML(true); // send as HTML
		$mail->Subject = returnIntLang('editcon mailtest subject', false);
		$mail->Body = returnIntLang('editcon mailtest html text', false);
		$mail->AltBody = returnIntLang('editcon mailtest plain text', false);
	
		if(!$mail->Send()): 
			echo $mail->ErrorInfo; exit;
		else:
			echo 99;
		endif;
	else:
		echo 11;
	endif;
    */

    // deprecated until 7.0
    echo 11;

endif;

endif;

// EOF ?>