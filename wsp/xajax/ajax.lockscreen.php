<?php
/**
 * @description open locked screen
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2019-11-04
 */

session_start();

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {

    require("../data/include/globalvars.inc.php");
    require("../data/include/errorhandler.inc.php");
    require("../data/include/siteinfo.inc.php");

    if (isset($_SESSION['wspvars']['usevar'])) {
        $_SESSION['wspvars']['lockedvar'] = $_SESSION['wspvars']['usevar'];
        unset($_SESSION['wspvars']['usevar']);
        $_SESSION['wspvars']['lockscreen'] = true;
    
        ?><div id="lockscreenbg"></div>
            <div class="vertical-align-wrap">
                <div class="vertical-align-middle">
                    <div class="auth-box lockscreen clearfix">
                        <div class="content">
                            <div class="logo text-center">
                                <h1>WebSitePreview</h1>
                            </div>
                            <div class="info text-center">
                                <p><?php echo returnIntLang('timeout please re-login'); ?></p>
                            </div>
                            <div class="user text-center">
                                <h2 class="name"><?php if(isset($_SESSION['wspvars']['realname'])): echo $_SESSION['wspvars']['realname']; endif; ?></h2>
                            </div>
                            <form method="post" enctype="text/plain">
                            <div class="input-group">
                                <input type="hidden" name="reuser" id="reuser" value="<?php echo $_SESSION['wspvars']['lockedvar']; ?>" />
                                <input type="password" name="repass" id="repass" class="form-control" placeholder="<?php echo returnIntLang('timeout please re-enter your password', false); ?>">
                                <span class="input-group-btn">
                                    <button onclick="doReLogin(<?php echo (defined('WSP_DEV')) ? WSP_DEV : 0 ?>); return false;" class="btn btn-primary"><i class="fa fa-arrow-right"></i></button>
                                </span>
                            </div>
                            <div class="info text-center">
                                <p>&nbsp;</p>
                                <p><a href="<?php echo "/".WSP_DIR."/login.php?logout" ?>"><?php echo returnIntLang('timeout back to login'); ?></a></p>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
        </div>
        <?php
    }
    else {
        ?>
        <script> window.location.assign("./logout.php"); </script>
        <?php
    }
}
