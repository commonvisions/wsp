<?php
/**
* @description wsp footer file
* @author stefan@covi.de
* @since 3.1
* @version 7.0
* @lastchange 2021-01-19
*/

// remove loading entry so we have less entries in db and a loaded page is a good information ;)
$sql = "DELETE FROM `securitylog` WHERE `uid` = ".intval($_SESSION['wspvars']['userid'])." AND `lastaction` = 'start loading' ORDER BY `lastchange` DESC LIMIT 1";
doSQL($sql);
// save user position to security table to prevent double user access while changing contents or prefs
$sql = "UPDATE `security` SET `timevar` = '".time()."' WHERE `usevar` = '".$_SESSION['wspvars']['usevar']."'";
doSQL($sql);
// save user position to security log after loading page
$sql = "INSERT INTO `securitylog` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `lastposition` = '".$_SESSION['wspvars']['fpos']."', `lastaction` = 'end loading', `lastchange` = '".time()."'";
doSQL($sql);

if (isset($_REQUEST['clearsqlmsg'])) { unset($_SESSION['wspvars']['showsql']); }

// show post area AND log area, if in dev mode
if (defined('WSP_DEV') && WSP_DEV===true) { 
    echo '<div class="mainfix">';
    echo '<iframe src="./xajax/iframe.empty.php" width="45%" height="400" name="publisherpost" id="publisherpost" style="border: 1px solid #ccc; outline: none; width: 47%; height: 400px; margin-left: 30px;"></iframe>'; 
    echo '<pre id="publishoutput" style="float: right; margin-right: 30px; border-radius: 0px; background: transparent; border: 1px solid #ccc; outline: none; width: 45%; height: 400px;"></pre>';
    echo '</div>';
    ?>
    <script>

        pLog = true;
        cT = 5000;

    </script>
    <?php
}
// otherwise 'show' hidden iframe to submit post data to
else { 
    echo '<iframe src="./xajax/iframe.empty.php" width="1" height="1" scrolling="_no" name="publisherpost" id="publisherpost" style="border: none; outline: none; width: 1px; height: 1px; overflow: hidden; visibility: hidden;"></iframe>'; 
}
echo '<div class="clearfix"></div>';
echo '<footer>';
echo '<div class="container-fluid">';
echo '<p class="copyright">';
echo '<a href="http://www.websitepreview.de" target="_blank">WebSitePreview</a> © 2001 - 2021 ';
echo '<a href="http://www.covi.de" target="_blank">COVI.DE</a>';
// echo ', Theme © 2017 <a href="https://www.themeineed.com" target="_blank">Theme I Need</a>';
echo '</p>';
echo '</div>';
echo '</footer>';
// field to prevent unexpected leaving of page when values were changed
echo '<input type="hidden" id="cfc" value="0" />'; 
echo '</div>'; ?>

<!-- END WRAPPER -->

<script>
    
$(document).ready(function() {      
    
    callBackgroundPublish();
    
    setTimeout("showReLogin(<?php echo (defined('WSP_DEV')) ? WSP_DEV : 0 ?>);", <?php if (isset($_SESSION['wspvars']['lockscreen']) && $_SESSION['wspvars']['lockscreen']===true) { echo 0; } else { ?>(60000*<?php echo intval($_SESSION['wspvars']['autologout']); if(isset($_SESSION['wspvars']['noautologout']) && $_SESSION['wspvars']['noautologout']===true): echo "*1000"; endif; ?>)<?php } ?>);

    <?php if(isset($_SESSION['document_jumper']) && trim($_SESSION['document_jumper'])!=''): ?>
    $([document.documentElement, document.body]).animate({
            scrollTop: ($("<?php echo trim($_SESSION['document_jumper']); ?>").offset().top)-parseInt($('.navbar').outerHeight())-20
        }, 100);
    <?php unset($_SESSION['document_jumper']); endif; ?>
    
	});

$(window).bind('beforeunload', function () {
    if ($('#cfc').val()==1) {
        if (confirm ('<?php echo returnIntLang('request leave page without saving', false); ?>')) {
            return true;
        }
        else {
            return false;
        }
    }
});

</script>
<?php 

showWSPMsg(0); 
echo "</body>";
echo "</html>";

// EOF ?>

