<?php
/**
 * checking connections from editcon.php
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2019-06-17
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
    session_start();
    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");
    // check for allowed access and existing tmp user directory
    if (isset($_SESSION['wspvars']['usevar']) && is_dir(cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']))):
        foreach ($_POST['checkName'] AS $cnk => $cnv):
            $check[str_replace($_POST['checkType']."_", "", $cnv)] = $_POST['checkData'][$cnk];
        endforeach;
        if ($_POST['checkType']=='ftp'):
            $ftp = ftp_connect($check['host'], $check['port']);
            if ($ftp):
                $ftpcon = ftp_login($ftp, $check['user'], $check['pass']);
                if ($ftpcon):
                    if (ftp_chdir($ftp, $check['base'])):
                        $ftp_con = true;
                    endif;
                endif;
                @ftp_close($ftpcon);
            endif;
            if ($ftp_con===true):
                echo json_encode(array('success' => 1, 'msg' => returnIntLang('editcon checkcon ftp success', false)));
            else:
                echo json_encode(array('success' => 0, 'msg' => returnIntLang('editcon checkcon ftp error', false)));
            endif;
        elseif ($_POST['checkType']=='db'):
            $db_con = mysqli_connect(trim($check['host']),trim($check['user']),trim($check['pass']),trim($check['name']));
            if($db_con):
                // check for a wsp-required table
                $db_con = mysqli_query($db_con, 'SELECT `rid` FROM `restrictions`');
                if ($db_con!==false):
                    $db_con = true;
                endif;
            endif;
            if ($db_con===true):
                echo json_encode(array('success' => 1, 'msg' => returnIntLang('editcon checkcon db success', false)));
            else:
                echo json_encode(array('success' => 0, 'msg' => returnIntLang('editcon checkcon db error', false)));
            endif;
        elseif ($_POST['checkType']=='smtp'):
            
            if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/include/phpmailer/class.phpmailer.php")):
                // get user mail address
                $usermail_res = doResultSQL("SELECT `realmail` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']));
                if ($usermail_res):
                    require_once(DOCUMENT_ROOT."/".WSP_DIR."/data/include/phpmailer/PHPMailerAutoload.php");
                    $mail = new phpmailer();
                    $mail->AddAddress($usermail_res);
                    $mail->isSMTP();
                    $mail->Host     = trim($check['host']);
                    $mail->SMTPAuth = true;
                    $mail->Username = trim($check['user']);
                    $mail->Password = trim($check['pass']);
                    $mail->SMTPSecure = ((intval($check['ssl'])==1)?'tls':'');
                    $mail->Port     = intval($check['port']);
                    $mail->setFrom  = (trim($check['user']));
                    $mail->WordWrap = 50;
                    $mail->isHTML(true);
                    $mail->Subject  = "WSP SMTP Connection Test";
                    $mail->Body     = "WSP SMTP <b>Connection Test</b>";
                    $mail->AltBody  = "WSP SMTP Connection Test";
                    if($mail->Send()):
                        echo json_encode(array('success' => 1, 'msg' => returnIntLang('editcon checkcon smtp success', false)));
                    else:
                        echo json_encode(array('success' => 0, 'msg' => returnIntLang('editcon checkcon smtp error', false)));
                    endif;
                else:
                    echo json_encode(array('success' => 0, 'msg' => returnIntLang('editcon checkcon smtp no user mail found', false)));
                endif;
            else:
                echo json_encode(array('success' => 1, 'msg' => returnIntLang('editcon checkcon smtp could not be tested', false)));
            endif;
            
        else:
            echo json_encode(array('success' => 0, 'msg' => returnIntLang('editcon checkcon type not allowed', false)));
        endif;
    else:
        echo json_encode(array('success' => 0, 'msg' => returnIntLang('no external access allowed', false)));
    endif;
else:
    echo json_encode(array('success' => 0, 'msg' => returnIntLang('no direct access allowed', false)));
endif;
?>