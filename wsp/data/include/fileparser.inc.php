<?php
/**
 * Allgemeine parser-functions
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2021-09-28
 */

if (!(function_exists('getHeadVar'))) {
    function getHeadVar($mid, $publishlang, $preview = false, $metascript = array()) {
        // get site based data for building a head
        $globalheaddata = getWSPProperties();
        $siteurl = isset($globalheaddata['siteurl'])?$globalheaddata['siteurl']:$_SERVER['HTTP_HOST'];
        $sitetitle = isset($globalheaddata['sitetitle'])?$globalheaddata['sitetitle']:$_SERVER['HTTP_HOST'];
        $siterobots = isset($globalheaddata['siterobots'])?$globalheaddata['siterobots']:'all';
        $sitekeys = isset($globalheaddata['sitekeys'])?$globalheaddata['sitekeys']:'';
        $sitedesc = isset($globalheaddata['sitedesc'])?$globalheaddata['sitedesc']:'';
        $sitecopy = isset($globalheaddata['sitecopy'])?$globalheaddata['sitecopy']:'WSP7';
        $siteauthor = isset($globalheaddata['siteauthor'])?$globalheaddata['siteauthor']:'WSP7';
        $googleverify = isset($globalheaddata['googleverify'])?$globalheaddata['googleverify']:'';
        $revisit = isset($globalheaddata['revisit'])?$globalheaddata['revisit']:14;
        $doctype = $globalheaddata['doctype'];
        $langinfo = $globalheaddata['languages'];
        // get page based data
        $pagehead_sql = "SELECT `pagetitle`, `pagekeys`, `pagedesc`, `pagecopy` FROM `pageproperties` WHERE `mid` = ".intval($mid);
        $pagehead_res = doSQL($pagehead_sql);
        if ($pagehead_res['num']>0):
            if (trim($pagehead_res['set'][0]["pagetitle"])!=""):
                $sitetitle = trim($pagehead_res['set'][0]["pagetitle"]);
            endif;
            if (trim($pagehead_res['set'][0]["pagekeys"])!=""):
                $sitekeys = trim($pagehead_res['set'][0]["pagekeys"]).",".$sitekeys;
            endif;
            if (trim($pagehead_res['set'][0]["pagedesc"])!=""):
                $sitedesc = trim($pagehead_res['set'][0]["pagedesc"]);
            endif;
        endif;
        // grep all data to mid
        $middata_sql = "SELECT * FROM `menu` WHERE `mid` = ".intval($mid);
        $middata_res = doSQL($middata_sql);
        // get information of displaying page contents (of hidden menupoints)
        $visibility = 0;
        $lockpage = 0;
        // first get visibility status of page
        if ($middata_res['num']>0):
            $middata = $middata_res['set'][0];
        endif;
        // append information to vars
        if ($middata_res['num']>0):
            $visibility = intval($middata['visibility']);
            $lockpage = intval($middata['lockpage']);
            $denylang = unserializeBroken(trim($middata['denylang']));
            if (is_array($denylang) && in_array($publishlang, $denylang)):
                $visibility = 0;
            endif;
        endif;
        // now get content handler
        $hiddenmenu = intval($globalheaddata['hiddenmenu']);
        $nocontentmenu = intval($globalheaddata['nocontentmenu']);
        if ($hiddenmenu==2):
            $contentvisibility = intval($nocontentmenu);
        endif;
        // compare menu and content visibility
        if ($visibility==0):
            // ?? WHAT HERE
        endif;

        $headvar = "";

        // if no data can be accessed - return a simple head
        if ($middata_res['num']==0) {
            $headvar.= "<!DOCTYPE html>\n";
            $headvar.= "<html>\n";
            $headvar.= "<head>\n";
            $headvar.= "<title>".setUTF8(stripslashes($sitetitle))."</title>\n";
            $headvar.= "</head>\n";
        // else - build head
        } else {
            // check if contents avaiable
            $cavail_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid)." AND `visibility` != 0 AND `trash` = 0";
            $cavail_res = doSQL($cavail_sql);
            $cavail_num = $cavail_res['num'];
            // if required forwarding to another page 
            if ($middata['docintern']!='') {
                if ($preview) {
                    echo "forwarding to: /media/download".$middata['docintern'];
                    die();
                }
                $headvar.= "<?php\n\n";
                $headvar.= "header('location: ".str_replace("//", "/", str_replace("//", "/", "/media/download/".$middata['docintern']))."');\ndie();\n\n";
                $headvar.= "?>\n";
            // if required forwarding to another page 
            } else if (intval($middata['forwarding_id'])>0) {
                // get php head with forwarding ...
                $headvar.= "<!DOCTYPE html>\n";
                $headvar.= "<html>\n";
                $headvar.= "<head>\n";
                $headvar.= "<title>".setUTF8(stripslashes($sitetitle))."</title>\n";
                $headvar.= "</head>\n";
            // elseif no contents avaiable and forwarding to next active menupoint is selected 
            } else if (intval($middata['forwarding_id'])==0 && $cavail_num==0 && $preview===false) {
                // get php head with forwarding ...
                $headvar.= "<!DOCTYPE html>\n";
                $headvar.= "<html>\n";
                $headvar.= "<head>\n";
                $headvar.= "<title>".setUTF8(stripslashes($sitetitle))."</title>\n";
                $headvar.= "</head>\n";
            // else - get used template to find out some required css, js, etc ...
            } else {
                $used_template = getTemplateID($mid);
                if (isset($_REQUEST['previewtpl']) && intval($_REQUEST['previewtpl'])>0) {
                    $used_template = intval($_REQUEST['previewtpl']);
                }
                $template_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($used_template);
                $template_res = doSQL($template_sql);
                $template_head = '';
                $template_body = '';
                $template_iphone_opt = 0;
                $template_ipad_opt = 0;
                $template_iphone_viewport = '';
                $template_ipad_viewport = '';
                $template_generic_viewport = '';
                if ($template_res['num']>0) {
                    $template_head = trim($template_res['set'][0]['head']);
                    $template_body = trim($template_res['set'][0]['bodytag']);
                    $template_generic_viewport = trim($template_res['set'][0]['generic_viewport']);
                    $template_framework = unserializeBroken(trim($template_res['set'][0]['framework']));
                    $template_fonts = unserializeBroken(trim($template_res['set'][0]['fonts']));
                }
                // doctype
                if ($doctype=="xhtml1-1") { // XHTML 1.1
                    $headvar.= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"";
                    $headvar.= "\n\t\t\t\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
                } else if ($doctype=="xhtml1strict") { // XHTML 1 Strict
                    $headvar.= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"";
                    $headvar.= "\n\t\t\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
                } else if ($doctype=="xhtml1trans") { // XHTML 1 Transitional
                    $headvar.= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"";
                    $headvar.= "\n\t\t\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
                } else if ($doctype=="html5") { // HTML 5
                    $headvar.= "<!DOCTYPE html>\n";
                } else if ($doctype=="html4strict") { // HTML 4 Strict
                    $headvar.= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"";
                    $headvar.= "\n\t\t\t\"http://www.w3.org/TR/html4/strict.dtd\">\n";
                } else { // html4trans
                    $headvar.= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"";
                    $headvar.= "\n\t\t\t\"http://www.w3.org/TR/html4/loose.dtd\">\n";
                }
                // language
                $headvar.= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".$publishlang."\" lang=\"".$publishlang."\">\n";	
                $headvar.= "<head>\n";
                // 2012-03-01 use only utf8-encoding codepage
                $headvar.= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
                $headvar.= "<!-- content-language: ".$publishlang." -->\n";
                // meta script top
                if (isset($metascript) && is_array($metascript) && count($metascript)>0) {
                    $headvar.= "<!-- page generated meta top  -->\n";
                    foreach ($metascript AS $msk => $msv) {
                        if ($msv['pos']==0 && trim($msv['content'])!='') {
                            $headvar.= $msv['content'];
                            $headvar.= "\n";
                        }
                    }
                }
                // sitetitle
                $headvar.= "<title>".setUTF8(stripslashes($sitetitle))."</title>\n";
                // meta information
                $headvar .= "<!-- classic meta -->\n";
                $headvar.= "<meta name=\"description\" content=\"".setUTF8(str_replace("\"", "'", stripslashes($sitedesc)))."\" />\n";
                $headvar.= "<meta name=\"keywords\" content=\"".setUTF8(str_replace("\"", "'", stripslashes($sitekeys)))."\" />\n";
                if (trim($siteauthor)!="") {
                    $headvar.= "<meta name=\"author\" content=\"".setUTF8(str_replace("\"", "'", stripslashes($siteauthor)))."\" />\n";
                } else {
                    $headvar.= "<meta name=\"author\" content=\"".setUTF8(str_replace("\"", "'", stripslashes($_SESSION['wspvars']['realname'])))."\" />\n";
                }
                $headvar.= "<meta name=\"generator\" content=\"".WSP_LONGVERSION."\" />\n";
                $headvar.= "<meta name=\"robots\" content=\"".$siterobots."\" />\n";
                // revisit
                if ($revisit!="") {
                    $headvar .= "<meta name=\"revisit-after\" content=\"".$revisit."\" />\n";
                }
                $headvar .= "<!-- setup mobile + desktop classes -->\n";
                $headvar .= "<style type='text/css'><!--\n";
                $headvar .= ".mobile {display: none;} @media only screen and (max-width: 480px){ .mobile {display: inherit;} .desktop {display: none;} }\n";
                $headvar .= "--></style>\n";
                $headvar.= "<meta name=\"version\" content=\"".date('Y.m.d.H.i.s')."\" />\n";
                // googleverify
                if ($googleverify!='') {
                    $headvar .= "<!-- google webmaster tools site verification -->\n";
                    $headvar .= "<meta name=\"verify-v1\" content=\"".$googleverify."\">\n";
                    $headvar .= "<meta name=\"google-site-verification\" content=\"".$googleverify."\">\n";
                }
                // predefined frameworks
                if (is_array($template_framework)) {
                    $headvar .= "<!-- wsp integrated frameworks -->\n";
                    foreach ($template_framework AS $tfwk => $tfwv) {
                        if ($tfwk=='jquery' && $tfwv==1) {
                            $headvar .= "<script src=\"/data/script/jquery/jquery-3.3.1.js\" type=\"text/javascript\"></script>\n";
                        }
                        if ($tfwk=='jquerygoogle' && $tfwv==1) {
                            $headvar .= "<script src=\"//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js\"></script>\n";
                        }
                        if ($tfwk=='covifuncs' && $tfwv==1) {
                            $headvar .= "<script src=\"/data/script/covifuncs.js\" type=\"text/javascript\"></script>\n";
                        }
                    }
                }
                // predefined fonts
                if (is_array($template_fonts)) {
                    $headvar .= "<!-- wsp integrated fonts -->\n";
                    if (isset($template_fonts['source']) && $template_fonts['source']=='google' && isset($template_fonts['list']) && $template_fonts['list']!='') {
                        $headvar .= "<link href=\"//fonts.googleapis.com/css?family=".trim($template_fonts['list'])."\" rel=\"stylesheet\" type=\"text/css\">\n";
                    }
                }
                // js from template
                // if preview === true » inline script should be used
                $jscript_sql = "SELECT `javascript_id` FROM `r_temp_jscript` WHERE `templates_id` = ".intval($used_template)." ORDER BY `id`";
                $jscript_res = doSQL($jscript_sql);
                if ($jscript_res['num']>0) {
                    $headvar .= "<!-- template js-files -->\n";
                    foreach ($jscript_res['set'] AS $jsresk => $jsresv) {
                        $javascript_sql = "SELECT * FROM `javascript` WHERE `id` = ".intval($jsresv['javascript_id']);
                        $javascript_res = doSQL($javascript_sql);
                        if ($javascript_res['num']>0) {
                            if (trim($javascript_res['set'][0]['file'])!='') {
                                // just files
                                $headvar .= "<script src=\"/data/script/".trim($javascript_res['set'][0]['file']).".js\" type=\"text/javascript\"></script>\n";
                            } else {
                                // handling js-folders!!!
                                $jscript = unserializeBroken(trim($javascript_res['set'][0]['scriptcode']));
                                if (is_array($jscript)) { 
                                    foreach ($jscript AS $jk => $jv) {
                                        $headvar .= str_replace("//", "/", str_replace("//", "/", "<script src=\"/data/script/".trim($javascript_res['set'][0]['cfolder'])."/".trim($jv)."\" type=\"text/javascript\"></script>\n"));
                                    }
                                }
                            }
                        }
                    }
                }
                // js addon from menu
                if (trim($middata['addscript'])!='') {
                    $addjs = unserializeBroken(trim($middata['addscript']));
                    if (is_array($addjs) && count($addjs)>0) {
                        $headvar .= "<!-- page based js-files -->\n";
                        foreach ($addjs AS $jk => $jv) {
                            $javascript_sql = "SELECT * FROM `javascript` WHERE `id` = ".intval($jv);
                            $javascript_res = doSQL($javascript_sql);
                            if ($javascript_res['num']>0) {
                                if (trim($javascript_res['set'][0]['file'])!='') {
                                    // just files
                                    $headvar .= "<script src=\"/data/script/".trim($javascript_res['set'][0]['file']).".js\" type=\"text/javascript\"></script>\n";
                                }
                                else {
                                    $jscript = unserializeBroken(trim($javascript_res['set'][0]['scriptcode']));
                                    // handling js-folders!!!
                                    foreach ($jscript AS $jk => $jv) {
                                        $headvar .= str_replace("//", "/", str_replace("//", "/", "<script src=\"/data/script/".trim($javascript_res['set'][0]['cfolder'])."/".trim($jv)."\" type=\"text/javascript\"></script>\n"));
                                    }
                                }
                            }
                        }
                    }
                }
                // css from template
                $css_sql = "SELECT `stylesheets_id` FROM `r_temp_styles` WHERE `templates_id` = ".intval($used_template)." ORDER BY `id`";
                $css_res = doSQL($css_sql);
                if ($css_res['num']>0) {
                    $headvar .= "<!-- template css-files -->\n";
                    foreach ($css_res['set'] AS $csresk => $csresv) {
                        $style_sql = "SELECT * FROM `stylesheets` WHERE `id` = ".intval($csresv['stylesheets_id']);
                        $style_res = doSQL($style_sql);
                        if ($style_res['num']>0) {
                            if (trim($style_res['set'][0]['file'])!='') {
                                if ($preview) {

                                    $headvar .= "<style>\n";

                                    $headvar .= "</style>\n";

                                } else {
                                    // just files
                                    if (trim($style_res['set'][0]['browser'])=="all" || trim($style_res['set'][0]['browser'])=="") {
                                        $headvar .= "<link rel=\"stylesheet\" href='/media/layout/".trim($style_res['set'][0]['file']).".css' media=\"".trim($style_res['set'][0]['media'])."\" type=\"text/css\" />\n";
                                    } else {
                                        $headvar .= "<!--[if ".trim($style_res['set'][0]['browser'])."]>\n";
                                        $headvar .= "<link rel=\"stylesheet\" href='/media/layout/".trim($style_res['set'][0]['file']).".css' media=\"".trim($style_res['set'][0]['media'])."\" type=\"text/css\" />\n";
                                        $headvar .= "<![endif]-->\n";
                                    }
                                }
                            }
                            else {
                                // handling css-folders!!!
                                // can't be handled inline
                                $styles = unserializeBroken(trim($style_res['set'][0]['stylesheet']));
                                foreach ($styles AS $sk => $sv) {
                                    $headvar .= str_replace("//", "/", str_replace("//", "/", "<link rel=\"stylesheet\" href='/media/layout/".trim($style_res['set'][0]['cfolder'])."/".trim($sv)."' type='text/css' />\n"));
                                }
                            }
                        }
                    }
                }
                // css addon from menu
                if (trim($middata['addcss'])!='') {
                    $addcss = unserializeBroken(trim($middata['addcss']));
                    if (is_array($addcss) && count($addcss)>0) {
                        $headvar .= "<!-- page based css-files -->\n";
                        foreach ($addcss AS $ck => $cv) {
                            $style_sql = "SELECT * FROM `stylesheets` WHERE `id` = ".intval($cv);
                            $style_res = doSQL($style_sql);
                            if ($style_res['num']>0) {
                                if (trim($style_res['set'][0]['file'])!='') {
                                    // just files
                                    if (trim($style_res['set'][0]['browser'])=="all" || trim($style_res['set'][0]['browser'])=="") {
                                        $headvar .= "<link rel=\"stylesheet\" href='/media/layout/".trim($style_res['set'][0]['file']).".css'   media=\"".trim($style_res['set'][0]['media'])."\" type=\"text/css\" />\n";
                                    } else {
                                        $headvar .= "<!--[if ".trim($style_res['set'][0]['browser'])."]>\n";
                                        $headvar .= "<link rel=\"stylesheet\" href='/media/layout/".trim($style_res['set'][0]['file']).".css' media=\"".trim($style_res['set'][0]['media'])."\" type=\"text/css\" />\n";
                                        $headvar .= "<![endif]-->\n";
                                    }
                                }
                                else {
                                    // handling css-folders!!!
                                    $styles = unserializeBroken(trim($style_res['set'][0]['stylesheet']));
                                    foreach ($styles AS $sk => $sv) {
                                        $headvar .= str_replace("//", "/", str_replace("//", "/", "<link rel=\"stylesheet\" href='/media/layout/".trim($style_res['set'][0]['cfolder'])."/".trim($sv)."' type='text/css' />\n"));
                                    }
                                }
                            }
                        }
                    }
                }
                if (!($preview)) {
                    // rss
                    $rss_sql = "SELECT `rss_id` FROM `r_temp_rss` WHERE `templates_id` = ".intval($used_template)." LIMIT 0,1";
                    $rss_res = doSQL($rss_sql);
                    if ($rss_res['num']>0) {
                        $rssfile_sql = "SELECT * FROM `rssdata` WHERE `id` = ".intval($rss_res['set'][0]['rss_id']);
                        $rssfile_res = doSQL($rssfile_sql);
                        if ($rssfile_res['num']>0) {
                            $headvar .= "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"RSS-Datei\" href=\"/media/rss/".trim($rssfile_res['set'][0]['rssfilename'])."\">\n";
                        }
                    }
                }
                // favicon
                if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/media/screen/favicon.ico")) {
                    $headvar .= "<!-- icons -->\n";
                    $headvar .= "<link rel=\"shortcut icon\" href=\"/media/screen/favicon.ico\" />\n";
                }
                // iOS properties
                if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/media/screen/iphone_favicon.png")) {
                    $headvar .= "<link rel=\"apple-touch-icon\" href=\"/media/screen/iphone_favicon.png\" />\n";
                }
                // viewport properties
                if ($template_generic_viewport!='') {
                    $headvar .= "<meta name=\"viewport\" content=\"".$template_generic_viewport."\" />\n";
                }
                // opengraph properties
                // opengraph screenshot
                $headvar .= "<!-- og meta -->\n";
                if (is_file(DOCUMENT_ROOT."/media/screen/ogscreenshot.png")) {
                    $headvar .= "<meta property=\"og:image\" content=\"https://".$siteurl."/media/screen/ogscreenshot.png\" />\n";
                }
                // facebook title
                $headvar .= "<meta property=\"og:title\" content=\"".str_replace("\"", "'", stripslashes(trim($sitetitle)))."\" />\n"; //'
                // facebook url
                $headvar .= "<meta property=\"og:url\" content=\"//".$siteurl."\" />\n";
                // facebook page type
                $headvar .= "<meta property=\"og:type\" content=\"website\" />\n";
                // template based head extensions
                if ($template_head!="") {
                    $headvar .= "<!-- template head data -->\n";
                    $headvar .= stripslashes($template_head)."\n";
                }
                // meta script bottom
                if (isset($metascript) && is_array($metascript) && count($metascript)>0) {
                    $headvar.= "<!-- page generated meta bottom -->\n";
                    foreach ($metascript AS $msk => $msv):
                        if ($msv['pos']==1 && trim($msv['content'])!=''):
                            $headvar.= $msv['content'];
                            $headvar.= "\n";
                        endif;
                    endforeach;
                }
                $analytics_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'analyticsid'";
                $analytics_res = doResultSQL($analytics_sql);
                if ($analytics_res===false) {
                    // analytics fallback
                    $analytics_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'googleanalytics'";
                    $analytics_res = doSQL($analytics_sql);
                    if ($analytics_res['num']>0 && !($preview)) {
                        if (trim($analytics_res['set'][0]['varvalue'])!="") {
                            $headvar.= "<!-- analytics setup -->\n".stripslashes(trim($analytics_res['set'][0]['varvalue']))."\n";
                        }
                    }
                }
                $headvar .= "</head>\n";
                // analytics functions
                $analytics_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'analyticsid'";
                $analytics_res = doResultSQL($analytics_sql);
                if ($analytics_res!==false && !($preview)) {
                    if (trim($analytics_res)!="") {
                        $headvar .= "<!-- google analytics -->\n";
                        $headvar .= "<script>\n";
                        $headvar .= "var gaProperty = '".trim($analytics_res)."';\n";
                        $headvar .= "var disableStr = 'ga-disable-' + gaProperty;\n";
                        $headvar .= "if (document.cookie.indexOf(disableStr + '=true') > -1) {\n";
                        $headvar .= "  window[disableStr] = true;\n";
                        $headvar .= "}\n";
                        $headvar .= "function gaOptout() {\n";
                        $headvar .= "  document.cookie = disableStr + '=true; expires=Thu, 31 Dec ".date('Y', mktime(1,1,1,1,1,(date(Y)+99)))." 23:59:59 UTC; path=/';\n";
                        $headvar .= "  window[disableStr] = true;\n";
                        $headvar .= "}\n";
                        $headvar .= "</script>\n";
                        $headvar .= "<!-- Global Site Tag (gtag.js) - Google Analytics -->\n";
                        $headvar .= "<script async src='//www.googletagmanager.com/gtag/js?id=".trim($analytics_res)."'></script>\n";
                        $headvar .= "<script>\n";
                        $headvar .= "window.dataLayer = window.dataLayer || [];\n";
                        $headvar .= "function gtag(){dataLayer.push(arguments);}\n";
                        $headvar .= "gtag('js', new Date());\n";
                        $headvar .= "gtag('config', '".trim($analytics_res)."', { 'anonymize_ip': true });\n";
                        $headvar .= "</script>\n";
                    }
                }
                // do js-based location reload if forwarding is active in any way ..
                $headvar .= "<body";
                if ($template_body!="") {
                    $headvar .= " ".$bodytag;
                }
                $headvar .= ">\n";
            }
        }
        return $headvar;
        }
}

// close pages
if (!(function_exists('getFootVar'))) {
function getFootVar() {
    $footervar = '';
    $footervar.= "</body>\n</html>\n";
	return $footervar;
	}	// getFootVar()
}

if (!(function_exists('getContentvars'))) {
function getContentvars($buf, $templateID = 0) {
    if (intval($templateID)>0) {
        $templatevars = getTemplateVars($templateID);
    }
	$cnt = array();
	$pos = strpos($buf, '[%CONTENTVAR%]');
	if (!($pos===false)) { $cnt[0] = "[%CONTENTVAR%]"; }
	if (isset($templatevars['contentareas']) && is_array($templatevars['contentareas'])) {
        foreach ($templatevars['contentareas'] AS $tvck => $tvsv) {
            if ($tvsv!=0) { $cnt[$tvsv] = "[%CONTENTVAR:".$tvsv."%]"; }
        }
    } 
    else {
        for ($cvar=1; $cvar<20; $cvar++) {
            $pos = strpos($buf, "[%CONTENTVAR:".$cvar."%]");
            if (!($pos===false)) {
                $cnt[$cvar] = "[%CONTENTVAR:".$cvar."%]";
            }
        }
    }
	return $cnt;
	}
}

if (!(function_exists('getGlobalContentvars'))) {
    function getGlobalContentvars($buf) {
        $cnt = array();
        $gcid_sql = "SELECT MIN(id) AS min, MAX(id) AS max FROM `content_global`";
        $gcid_res = doSQL($gcid_sql);
        if ($gcid_res['num']>0) {
            for ($cvar=intval($gcid_res['set'][0]['min']); $cvar<=intval($gcid_res['set'][0]['max']); $cvar++) {
                $pos = strpos($buf, "[%GLOBALCONTENT:".$cvar."%]");
                if (!($pos===false)) {
                    $cnt[$cvar] = "[%GLOBALCONTENT:".$cvar."%]";
                }
            }
        }
        return $cnt;
	}
}

/**
 * ermittelt den String fr die if()-Bedingung fr die Zeit/Tag-gesteuerte
 * Anzeige von Contents.
 *
 * @param integer $contentID
 * @return string
 */
if (!(function_exists('getShowtimeString'))) {
    function getShowtimeString($contentID) {	
        if ($contentID>0):
            $st_sql = "SELECT c.`showday`, c.`showtime` FROM `content` c WHERE c.`cid` = ".intval($contentID);
            $st_res = doSQL($st_sql);
            if($st_res['num']>0):
                $cDay = intval($st_res['set'][0]["showday"]);
                $cTime = unserializeBroken(trim($st_res['set'][0]["showtime"]));
                if($cDay>0 && (is_array($cTime) && count($cTime)>0)):
                    // weekday and time are set
                    // create timeset
                    $datesArray = array();
                    foreach ($cTime AS $ck => $cv):
                        $datesArray[] = "(time()>=".$cv[0]." AND time()<=".$cv[1].")";
                    endforeach;
                    $timesString = "(".implode(" OR ", $datesArray).")";
                    // create dayset
                    $wda = array(1,2,3,4,5,6,0);
                    for ($sd=6;$sd>=0;$sd--):
                        if ($cDay-pow(2,$sd)>=0):
                            $weekday[] = $wda[$sd];
                            $cDay = $cDay-(pow(2,$sd));
                        endif;
                    endfor;
                    foreach ($weekday AS $key => $value):
                        $daysArray[] = "(date(\"w\")==".$value.")";
                    endforeach;
                    $daysString = "(".implode(" OR ", $daysArray).")";
                    $showtimeString= "<"."?"."php if(" . $timesString . " AND " . $daysString . "): "."?".">";
                elseif($cDay>0 && !(is_array($cTime) && count($cTime)>1)):
                    // only weekday set
                    $wda = array(1,2,3,4,5,6,0);
                    for ($sd=6;$sd>=0;$sd--):
                        if ($cDay-pow(2,$sd)>=0):
                            $weekday[] = $wda[$sd];
                            $cDay = $cDay-(pow(2,$sd));
                        endif;
                    endfor;
                    foreach ($weekday AS $key => $value): $daysArray[] = "(date(\"w\")==".$value.")"; endforeach;
                    $daysString = implode(" OR ", $daysArray);
                    $showtimeString= "<"."?"."php if(".$daysString."): "."?".">";
                elseif($cDay==0 && (is_array($cTime) && count($cTime)>0)):
                    // only time set
                    $datesArray = array();
                    foreach ($cTime AS $ck => $cv):
                        $datesArray[] = "(time()>=".$cv[0]." AND time()<=".$cv[1].")";
                    endforeach;
                    $showtimeString= "<"."?"."php if(".implode(" OR ", $datesArray)."): "."?".">";
                else:
                    // nothing set - why ever
                    $showtimeString= "";
                endif;
            else:
                $showtimeString = "";
            endif;
        else:
            $showtimeString = "";
        endif;
        return $showtimeString;
    }	// getShowtimeString()
}

if (!(function_exists('publishStructure'))) {
    function publishStructure($templateID) {

        // throw error because below is nothing happening
        addWSPMsg('errormsg', 'publisher function "publishStructure" failed publishing menu');

        // select used template 
        $template_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($templateID);
        $template_res = doSQL($template_sql);
        if ($template_res['num']>0) {
            foreach ($template_res['set'] AS $trsk => $trsv) {
                // remove all tags etc, trim, strip slashes from template
                $templatecode = trim(htmlentities(strip_tags(stripslashes(trim($trsv['template'])))));
                $regsearch = "'\[%MENUVAR [0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}%\]'";
                preg_match_all($regsearch, $templatecode, $mtplmatches);
                if (is_array($mtplmatches[0])) {
                    $mtplmatches = array_unique($mtplmatches[0]);
                    foreach ($mtplmatches AS $umvalue) {
                        $menuguid = substr(trim($umvalue), 10, -2);
                        $menu_sql = "SELECT `parser`, `code`, `startlevel` FROM `templates_menu` WHERE `guid` = '".trim($menuguid)."' AND `code` != ''";
                        $menu_res = doSQL($menu_sql);
                        if ($menu_res['num']>0) {
                            // ??????
    //						echo trim($menuguid)." gefunden<br />";
                        }
                    }
                }
            }
        }
    }
}

if (!(function_exists('publishMenu'))) {
    function publishMenu($pubid, $mode = 'publish', $lang = 'de', $newendmenu = false, $rename = false) {
        /* grab menupoint information */
        $mid_sql = "SELECT * FROM `menu` WHERE `mid` = ".intval($pubid);
        $mid_res = doSQL($mid_sql);
        if ($mid_res['num']>0):
            // get template info
            $used_template = getTemplateID(intval($pubid));
            if (isset($_REQUEST['previewtpl']) && intval($_REQUEST['previewtpl'])>0):
                $used_template = intval($_REQUEST['previewtpl']);
            endif;
            $template_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($used_template);
            $template_res = doSQL($template_sql);
            if ($template_res['num']>0):
                $parsetime = microtime(true);
                $templatecode = stripslashes(trim($template_res['set'][0]['template']));

                $tmpbuf = $templatecode;
                // replace all wsp<6 menuvars with wsp>=6 menuvar style
                $tmpbuf = str_replace("[%MENUVAR ", "[%MENUVAR:", $tmpbuf);
                // run through tmpbuf to find all menu variables and convert var to content with menu data
                $tmpbuf = createMenu($tmpbuf, intval($mid_res['set'][0]['mid']), $lang, false);
                // update db entry

                // if change stat 7 then rename file 
                if ($rename):
                    renameFile(intval($mid_res['set'][0]['mid']), $lang);
                endif;

                // set calculated contentchanged stat
                $cchange = 0;
                if (getChangeStat(intval($mid_res['set'][0]['mid']))==0): $cchange = 0; endif;
                if (getChangeStat(intval($mid_res['set'][0]['mid']))==1): $cchange = 1; endif;
                if (getChangeStat(intval($mid_res['set'][0]['mid']))==2): $cchange = 2; endif;
                if (getChangeStat(intval($mid_res['set'][0]['mid']))==3): $cchange = 0; endif;
                if (getChangeStat(intval($mid_res['set'][0]['mid']))==4): $cchange = 0; endif;
                if (getChangeStat(intval($mid_res['set'][0]['mid']))==5): $cchange = 1; endif;
                if (getChangeStat(intval($mid_res['set'][0]['mid']))==6): $cchange = 2; endif;

                doSQL("UPDATE `menu` SET `contentchanged` = ".intval($cchange)." WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
                $returnstat = true;

                if($newendmenu):
                    createNewMenu();
                endif;

            else:
                $returnstat = "notemplatefound";
                addWSPMsg('errormsg', "<em>Fehler 2.".$used_template."</em>: Template zu Punkt \"".trim($mid_res['set'][0]['description'])."\" nicht gefunden.");
            endif;
        else:
            $returnstat = "nomenufound";
            addWSPMsg('errormsg', "<em>Fehler 1.".$pubid."</em>: Men&uuml;punkt nicht gefunden.");
        endif;
        return $returnstat;
        }
}

// publish selected page(s)
if (!(function_exists('publishSites'))) {
function publishSites($pubid, $mode = 'publish', $lang = 'de', $newendmenu = false, $con = false) {
	/* $mode => publishing possibilities */
	/* 'publish' publishs menu AND contents */ /* ??????? */
	/* 'structure' should read existing file from structure and replace menu parts with new menu */
	/* 'content' publishs contents */
	/* 'preview' publishs menu AND contents in preview mode */
	if ($mode!='preview') { $_SESSION['preview'] = 0; }
    if ($lang=='') { $lang = trim(getWSPProperties('wspbaselang')); }
    if ($lang=='') { $lang = 'de'; }
	$returnstat = false;
	// define empty vars
	$header = '';
	$bodyfunc = '';
	$metapath = '';
	$metascript = array();
	$content = array();
	$globalcontent = array();
    $opencontainer = false;
	$ccarray = array(0 => 'section', 1 => 'div', 2 => 'span', 3 => 'li');
	// grep parsedir information
	$parsedir = intval(getWSPProperties('parsedirectories'));
	// bind content view to menu visibility
	$bindcontentview = intval(getWSPProperties('bindcontentview'));
	// how to handle contents that are connected to hidden menupoints
	$hiddenmenu = intval(getWSPProperties('hiddenmenu'));
	// how to handle pages without contents
	$nocontentmenu = intval(getWSPProperties('nocontentmenu'));

    /* grab menupoint information */
	$mid_sql = "SELECT * FROM `menu` WHERE `mid` = ".intval($pubid);
	$mid_res = doSQL($mid_sql);
	if ($mid_res['num']>0) {
		$midvisibility = 1;
		$lockpage = intval($mid_res['set'][0]['lockpage']);
		$denylang = unserializeBroken(trim($mid_res['set'][0]['denylang']));
		if (intval($mid_res['set'][0]['visibility'])==0) {
			$midvisibility = 0;
		} else if (is_array($denylang) && in_array($lang, $denylang)) {
			$midvisibility = 0;
		}
        
        // get data for selfvars
        $selfvardata = array();
        $selfvar_sql = "SELECT `id`, `name`, `selfvar` FROM `selfvars`";
        $selfvar_res = doSQL($selfvar_sql);
        if ($selfvar_res['num']>0) {
            foreach ($selfvar_res['set'] AS $svresk => $svresv) {
                $vardata = trim($svresv['selfvar']);
                if ($_SESSION['wspvars']['stripslashes']>0) {
                    for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
                        $vardata = stripslashes($vardata);
                    endfor;
                }
                $selfvardata['VAR:'.strtoupper(trim($svresv['name']))] = $vardata;
                $selfvardata[strtoupper(trim($svresv['name']))] = $vardata;
            }
        }
            
        // get menupoint template info
        $used_template = getTemplateID($pubid);
        // set menupoint template id for preview if requested
        if (isset($_REQUEST['previewtpl']) && intval($_REQUEST['previewtpl'])>0 && $mode=='preview') {
            $used_template = intval($_REQUEST['previewtpl']);
        }

        // get template data from db
        $template_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($used_template);
        $template_res = doSQL($template_sql);
        if ($template_res['num']>0) {
            $parsetime = microtime(true);
            $templatecode = stripslashes(trim($template_res['set'][0]['template']));
            $bodytagcode = stripslashes(trim($template_res['set'][0]['bodytag']));
            // check for forwarding
            // heading intern page
            $internPage = 0;
            if (trim($mid_res['set'][0]['interntarget'])=="_parent") {
                $internPage = intval($mid_res['set'][0]['internlink_id']);
            }
            $externPage = 0;
            if ($internPage==0 && trim($mid_res['set'][0]['externtarget'])=="_parent") {
                // heading to extern page
                $externPage = trim($mid_res['set'][0]['offlink']);
            }
            $forwardPage = intval($mid_res['set'][0]['forwarding_id']);
            // get origin filename
            $filename = trim($mid_res['set'][0]['filename']);
            // get is index flag
            $isindex = intval($mid_res['set'][0]['isindex']);


            // get contents
            $content_sql = "SELECT * FROM `content` WHERE `content_lang` = '".escapeSQL($lang)."' AND `mid` = ".intval($mid_res['set'][0]['mid'])." AND `visibility` > 0 AND `trash` = 0 ORDER BY `content_area` ASC, `position` ASC";
            $content_res = doSQL($content_sql);
            $content_num = $content_res['num'];

            // setting real content num to get info of existing contents later
            $realcontent_num = $content_res['num'];
            
            if ($bindcontentview==1) {
                // if bind contentview is set, the visibility of contents shall match the menupoint visibility
                // set content num to 0 if visibility of content IS NOT locked to menupoint and menupoint is hidden 
                if ($midvisibility==0 && intval($mid_res['set'][0]['lockpage'])==0): $content_num = 0; endif;
            }
            else {
                // set content num to 0 if visibility of content IS locked to menupoint and menupoint is hidden 
                if ($midvisibility==0 && intval($mid_res['set'][0]['lockpage'])==1): $content_num = 0; endif;
            }

            // if viewable contents for the page are avaiable and no forwarding is defined
            if ($content_num>0 && $forwardPage==0 && $internPage==0 && $externPage==0) {
                // run every content element to fetch data
                foreach ($content_res['set'] AS $coresk => $coresv) {
                    // check for existing contents or use global content
                    $interpreter = '';
                    $contentvalue = '';
                    if (intval($coresv['globalcontent_id'])>0) {
                        $gc_sql = "SELECT * FROM `content_global` WHERE `id` = ".intval($coresv['globalcontent_id'])." LIMIT 0,1";
                        $gc_res = doSQL($gc_sql);
                        if ($gc_res['num']>0) {
                            $contentvalue = trim($gc_res['set'][0]['valuefields']);
                            $interpreter = trim($gc_res['set'][0]['interpreter_guid']);
                        }
                    }
                    else {
                        $contentvalue = trim($coresv['valuefields']);
                        $interpreter = trim($coresv['interpreter_guid']);
                    }
                    
                    // setting up empty content area to prevent php warning messages
                    if (!(array_key_exists(intval($coresv['content_area']), $content))) {
                        $content[intval($coresv['content_area'])] = '';
                    }
                    
                    // check visibility options
                    if ($mode!='preview') {
                        // parse limited visibility to content
                        if (intval($coresv['visibility'])==2) {
                            // show only without logged in user
                            $content[intval($coresv['content_area'])].= "<"."?"."php if(array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===false): "."?".">";
                        }
                        else if (intval($coresv['visibility'])==3) {
                            // show only for logged in user
                            $content[intval($coresv['content_area'])].= "<"."?"."php if(array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===true): "."?".">";
                        }
                        else if (intval($coresv['visibility'])==4) {
                            // show only for selected logged in user
                            $logincontrol = unserializeBroken($coresv['logincontrol']);
                            if (is_array($logincontrol) && count($logincontrol)>0) {
                                $loginLink = "<"."?"."php if(array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===true ";
                                $UID = array();
                                foreach ($logincontrol AS $lk => $lv):
                                    $UID[] = "\$_SESSION['wsppage']['uservalue']=='".$lv."'";
                                endforeach;
                                $loginLink.= " AND (".implode(" OR ", $UID).")";
                                $loginLink.= "): "."?".">";
                            } 
                            // if system tried to find some webusers, but couldn't fetch them »
                            // give access to all logged in users 
                            else {
                                $loginLink = "<"."?"."php if(array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===true): "."?".">";
                            }
                            $content[intval($coresv['content_area'])].= $loginLink;
                        }
                    }
                    else {
                        // display limited visibility to preview
                        if(intval($coresv['visibility'])==2):
                            // show only without logged in user
                            $content[intval($coresv['content_area'])].= "<section style=\"background: orange;\">";	
                        elseif(intval($coresv['visibility'])==3):
                            // show only for logged in user
                            $content[intval($coresv['content_area'])].= "<section style=\"background: red;\">";
                        elseif(intval($coresv['visibility'])==4):
                            // show only for selected logged in user
                            $content[intval($coresv['content_area'])].= "<section style=\"background: darkseagreen;\">";
                        endif;
                    }
                    
                    // lookup for time based visibiliy
                    if ($mode!='preview') {
                        $content[intval($coresv['content_area'])].= getShowtimeString(intval($coresv['cid']));
                    }
                    else {
                        if (getShowtimeString(intval($coresv['cid']))!='') {
                            $content[intval($coresv['content_area'])].= "<section style='background: rgba(207,225,230,0.5)' title='timebased'>";
                        }
                    }
                    
                    // lookup for container 
                    // lookup for container class
                    $containerclass = ' class=\"';
                    $displayclass = array(1 => 'desktop', 2 => 'mobile', 3 => 'print');
                    if (trim($coresv['containerclass'])!=''): $containerclass.= " ".trim($coresv['containerclass'])." "; endif;
                    if (intval($coresv['displayclass'])>0): $containerclass.= " ".$displayclass[intval($coresv['displayclass'])]." "; endif;
                    $containerclass.= '\" ';
                    // lookup for jumper
                    $containerid = '';
                    if (trim($coresv['containeranchor'])!=''): 
                        $containerid = trim($coresv['containeranchor']);
                    else: 
                        $containerid = "ID".intval($coresv['cid']);
                    endif;
                    // build container
                    // 4 = NO CONTAINER
                    // 5 = COMBINE WITH ELEMENT BEFORE SO HERE nothing happens
                    if (intval($coresv['container'])!=4 && intval($coresv['container'])!=5) {
                        $content[intval($coresv['content_area'])].= "<".$ccarray[intval($coresv['container'])]." id=\"".$containerid."\" ".$containerclass.">";
                    }

                    // lookup for interpreter and parsefile
                    $interpreter_sql = "SELECT `parsefile` FROM `interpreter` WHERE `guid` = '".escapeSQL(trim($interpreter))."'";
                    $interpreter_res = doSQL($interpreter_sql);
                    if ($interpreter_res['num']>0):
                        // lookup for parsefile
                        if (trim($interpreter_res['set'][0]['parsefile'])!='' && is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".trim($interpreter_res['set'][0]['parsefile']))):
                            require DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".trim($interpreter_res['set'][0]['parsefile']);
                            $parser = new $interpreterClass();
                        endif;
                        // detect classes functions and required argument set
                        $me = array();
                        $rf = new ReflectionClass($interpreterClass);
                        //run through all methods.
                        foreach ($rf->getMethods() as $method) {
                            $me[$method->name] = array();
                            //run through all parameters of the method.
                            foreach ($method->getParameters() as $parameter) {
                                $me[$method->name][$parameter->getName()] = $parameter->getType();
                            }
                        }
                        unset($rf);
                        // bring data to files php header ...
                        if (intval(method_exists($interpreterClass,'getHeader'))==1):
                            $header .= $parser->getHeader(trim($contentvalue), intval($mid_res['set'][0]['mid']), intval($coresv['cid']), $lang);
                            if (method_exists($interpreterClass, 'closeInterpreterDB')) { $parser->closeInterpreterDB(); }
                        endif;
                        // bring function calls to body tag
                        if (intval(method_exists($interpreterClass,'getBodyFunction'))==1):
                            $bodyfunc .= $parser->getBodyFunction(trim($contentvalue), intval($mid_res['set'][0]['mid']), intval($coresv['cid']));
                            if (method_exists($interpreterClass, 'closeInterpreterDB')) { $parser->closeInterpreterDB(); }
                        endif;
                        // bring data to files head ...
                        if (intval(method_exists($interpreterClass,'getMetaScript'))==1):
                            $metascript[] = $parser->getMetaScript(trim($contentvalue), intval($mid_res['set'][0]['mid']), intval($coresv['cid']), $lang);
                            if (method_exists($interpreterClass, 'closeInterpreterDB')) { $parser->closeInterpreterDB(); }
                        endif;
                        // finally parse real content
                        if (intval(method_exists($interpreterClass,'getContent'))==1):
                            // returning content from parser class
                            $content[intval($coresv['content_area'])].= $parser->getContent(unserializeBroken(trim($contentvalue)), intval($mid_res['set'][0]['mid']), intval($coresv['cid']), $lang);
                            if (method_exists($interpreterClass, 'closeInterpreterDB')) { $parser->closeInterpreterDB(); }
                        endif;
                    elseif ($interpreter=='genericwysiwyg'):
                        // if generic wysiwyg was used ... 
                        $genericcontent = unserializeBroken($contentvalue);
                        $content[intval($coresv['content_area'])].= trim($genericcontent['content']);
                    endif;
                    
                    // close container
                    // if next element exists and next element shall be connected with THIS element
                    if (isset($content_res['set'][($coresk+1)]) && isset($content_res['set'][($coresk+1)]['container']) && intval($content_res['set'][($coresk+1)]['container'])==5) {
                        // if this is the first element of connected elements
                        if ($opencontainer===false) {
                            $opencontainer = intval($coresv['container']);
                        }
                    }
                    // if this is the LAST element in list of connected elements
                    else if ($opencontainer!==false) {
                        if ($opencontainer!=4 && $opencontainer!=5) {
                            $content[intval($coresv['content_area'])].= "</".$ccarray[intval($opencontainer)].">";
                            $opencontainer = false;
                        }
                    }
                    else if (intval($coresv['container'])!=4 && intval($coresv['container'])!=5 ) {
                        $content[intval($coresv['content_area'])].= "</".$ccarray[intval($coresv['container'])].">";
                        $opencontainer = false;
                    }

                    // close time based visibility
                    if ($mode!='preview'):
                        if(trim(getShowtimeString(intval($coresv['cid'])))!=''):
                            $content[intval($coresv['content_area'])].= "<"."?"."php endif; "."?".">";	
                        endif;
                    elseif ($mode=='preview'):
                        if(trim(getShowtimeString(intval($coresv['cid'])))!=''):
                            $content[intval($coresv['content_area'])].= "</section>";	
                        endif;
                    endif;
                    // close visibility options
                    if ($mode!='preview'):
                        if(intval($coresv['visibility'])>1):
                            $content[intval($coresv['content_area'])].= "<"."?"."php endif; "."?".">";	
                        endif;
                    elseif ($mode=='preview'):
                        if(intval($coresv['visibility'])>1):
                            $content[intval($coresv['content_area'])].= "</section>";	
                        endif;
                    endif;
                    // generate linked contents, if not done in interpreter
                    $content[intval($coresv['content_area'])] = stripslashes(returnLinkedText($content[intval($coresv['content_area'])]));
                }
            } else {
                // no viewable contents or some forwarding was defined
                // internal forwarding
                if (intval($internPage)>0) {
                    $pathdata = fileNamePath(intval($internPage),0,0,0);
                    if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
                        $metapath = $pathdata['path'];
                    else:
                        $metapath = $pathdata['file'];
                    endif;
                } else if (trim($externPage)!="") {
                    $metapath = trim($externPage);
                    // check for http ???
                } else if (intval($forwardPage)>0) {
                    $pathdata = fileNamePath(intval($forwardPage),0,0,0);
                    if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {
                        $metapath = $pathdata['path'];
                    }
                    else {
                        $metapath = $pathdata['file'];
                    }
                } else {
                    // find first menupoint WITH contents as sub from mid
                    $subfwd = returnIDRoot(intval($pubid));
                    if (is_array($subfwd) && count($subfwd)>0):
                        $cc_num = 0;
                        foreach ($subfwd AS $sk => $sv):
                            if ($forwardPage==0):
                                $cc_sql = "SELECT `cid` FROM `content` WHERE `content_lang` = '".escapeSQL($lang). "' AND `mid` = ".intval($sv)." AND `visibility` > 0 AND `trash` = 0";
                                $cc_res = doSQL($cc_sql);
                                $cc_num = $cc_res['num'];
                                if ($cc_num>0): $forwardPage = $sv; endif;
                            endif;
                        endforeach;
                    endif;
                    if (intval($forwardPage)>0) {
                        $pathdata = fileNamePath(intval($forwardPage),0,0,0);
                        if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {
                            $metapath = $pathdata['path'];
                        }
                        else {
                            $metapath = $pathdata['file'];
                        }
                    }
                }
            }

            // find global contents used in template ...
            $globalcontentvars = getGlobalContentvars($templatecode);
            foreach ($globalcontentvars AS $key => $value) {
                $interpreter = '';
                $contentvalue = '';
                $gccontent_sql = "SELECT * FROM `content_global` WHERE `id` = ".intval($key)." AND (`content_lang` = '".escapeSQL($lang)."' OR `content_lang` = '') AND `trash` = 0";
                $gccontent_res = doSQL($gccontent_sql);
                $gccontent_num = $gccontent_res['num'];
                // set global content num to 0 if visibility of contents is locked to (hidden) menupoint 
                if ($midvisibility==0 && intval($mid_res['set'][0]['lockpage'])==1): $gccontent_num = 0; endif;
                if ($gccontent_num>0):
                    $contentvalue = trim($gccontent_res['set'][0]['valuefields']);
                    $interpreter = trim($gccontent_res['set'][0]['interpreter_guid']);
                endif;
                // lookup for interpreter and parsefile
                $interpreter_sql = "SELECT `parsefile` FROM `interpreter` WHERE `guid` = '".trim($interpreter)."'";
                $interpreter_res = doSQL($interpreter_sql);
                if ($interpreter_res['num']>0):
                    // lookup for parsefile
                    if (trim($interpreter_res['set'][0]['parsefile'])!='' && is_file(DOCUMENT_ROOT."/".WSP_DIR."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".trim($interpreter_res['set'][0]['parsefile']))):
                        require DOCUMENT_ROOT."/".WSP_DIR."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".trim($interpreter_res['set'][0]['parsefile']);
                        $parser = new $interpreterClass();
                    endif;
                    // detect classes functions and required argument set
                    $me = array();
                    $rf = new ReflectionClass($interpreterClass);
                    //run through all methods.
                    foreach ($rf->getMethods() as $method) {
                        $me[$method->name] = array();
                        //run through all parameters of the method.
                        foreach ($method->getParameters() as $parameter) {
                            $me[$method->name][$parameter->getName()] = $parameter->getType();
                        }
                    }
                    unset($rf);
                    
                    // bring data from Interpreter to files php header ...
                    if (intval(method_exists($interpreterClass,'getHeader'))==1):
                        $header .= $parser->getHeader(trim($contentvalue), intval($mid_res['set'][0]['mid']), 0, $lang);
                        $parser->closeInterpreterDB();
                    endif;
                    // bring function calls to body tag
                    if (intval(method_exists($interpreterClass,'getBodyFunction'))==1):
                        $bodyfunc .= $parser->getBodyFunction(trim($contentvalue), intval($mid_res['set'][0]['mid']));
                        $parser->closeInterpreterDB();
                    endif;
                    // bring data to files head ...
                    if (intval(method_exists($interpreterClass,'getMetaScript'))==1):
                        $metascript[] = $parser->getMetaScript(trim($contentvalue), intval($mid_res['set'][0]['mid']), 0, $lang);
                        $parser->closeInterpreterDB();
                    endif;
                    // finally parse real content
                    if (intval(method_exists($interpreterClass,'getContent'))==1):
                        // returning content from parser class
                        $globalcontent[intval($key)] = $parser->getContent(trim($contentvalue), intval($mid_res['set'][0]['mid']), 0, $lang);
                        $globalcontent[intval($key)] = returnLinkedText($globalcontent[intval($key)]);
                        $parser->closeInterpreterDB();
                    endif;
                elseif ($interpreter=='genericwysiwyg'):
                    // if generic wysiwyg was used ... 
                    $genericcontent = unserializeBroken($contentvalue);
                    $globalcontent[intval($key)] = stripslashes(returnLinkedText(trim($genericcontent['content'])));
                endif;
            }
            
            // built final file data
            $tmpbuf = '';
            // get some PHP header variables, if defined
            $phpheader = "session_start();\n";
            
            // get URL or VARIABLE based forwarding BEFORE doing other forwarding actions 
            if ($isindex==1 && intval($mid_res['set'][0]['connected'])==0 && $mode!="preview") {
                // get URL based forwarding
                $urlforward_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` LIKE 'url_forward_%'";
                $urlforward_res = doSQL($urlforward_sql);
                $urlforward = '';
                if ($urlforward_res['num']>0) {
                    $urlforward.= "/"."* ".$urlforward_res['num']." url based forwardings *"."/\n";
                    foreach ($urlforward_res['set'] AS $ufresk => $ufresv) {
                        $urlforwarddata = unserializeBroken(trim($ufresv['varvalue']));
                        $targetdata = '';
                        if (is_array($urlforwarddata)):
                            $urlforward.= "if ((\$_SERVER['SERVER_NAME']=='".trim($urlforwarddata['url'])."' || \$_SERVER['SERVER_NAME']=='www.".trim($urlforwarddata['url'])."') && \$_SERVER['SERVER_NAME']!='".$_SESSION['wspvars']['liveurl']."') {\n";
                            if (intval($urlforwarddata['rewrite'])==1):
                                $targetdata.= "http://".$_SESSION['wspvars']['liveurl'];
                            else:
                                $targetdata.= "http://'".$urlforwarddata['url']."'";
                            endif;
                            // parsedir option
                            if ((isset($_SESSION['wspvars']['publisherdata']['parsedirectories']) && intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1) || $parsedir==1):
                                $targetdata.= returnPath(intval($urlforwarddata['target']), 1);
                            else:
                                $targetdata.= returnPath(intval($urlforwarddata['target']), 2);
                            endif;
                            $urlforward.= "\tif('http://'.\$_SERVER['SERVER_NAME'].\$_SERVER['PHP_SELF']!='".$targetdata."') {\n";
                            $urlforward.= "\t\theader('Location: ".$targetdata."');\n";
                            $urlforward.= "\t\tdie();\n";
                            $urlforward.= "\t}\n";
                            $urlforward.= "}\n";
                        endif;
                        unset($urlforwarddata);
                    }
                }
                // get VARIABLE based forwarding
                $varforward_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` LIKE 'var_forward_%'";
                $varforward_res = doSQL($varforward_sql);
                $varforward = '';
                if ($varforward_res['num']>0) {
                    $varforward.= "/"."* ".$varforward_num." var based forwardings *"."/\n";
                    foreach ($varforward_res['set'] AS $vfresk => $vfresv) {
                        $varforwarddata = unserializeBroken(trim($vfresv['varvalue']));
                        $targetdata = '';
                        if (is_array($varforwarddata)):
                            if (trim($varforwarddata['varvalue'])!=""):
                                $varforward.= "if(isset(\$_REQUEST['".trim($varforwarddata['varname'])."']) && \$_REQUEST['".trim($varforwarddata['varname'])."']=='".$varforwarddata['varvalue']."') {\n";
                            else:
                                $varforward.= "if(isset(\$_REQUEST['".trim($varforwarddata['varname'])."'])) {\n";
                            endif;
                            $targetdata.= "http://'.\$_SERVER['SERVER_NAME'].'";
                            if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
                                $targetdata.= returnPath(intval($varforwarddata['target']), 1);
                            else:
                                $targetdata.= returnPath(intval($varforwarddata['target']), 2);
                            endif;
                            $varforward.= "\tif('http://'.\$_SERVER['SERVER_NAME'].\$_SERVER['PHP_SELF']!='".$targetdata."') {\n";
                            $varforward.= "\t\theader('Location: ".$targetdata."');\n";
                            $varforward.= "\t\tdie();\n";
                            $varforward.= "\t}\n";
                            $varforward.= "}\n";
                        endif;
                        unset($varforwarddata);
                    }
                }
                // combine both forwarding options
                $phpheader.= $urlforward.$varforward."\n";
            }

            // use php based header forwarding, if $metapath not empty
            if (($metapath==0 || trim($metapath)!='') && $mode!="preview") {
                $phpheader.= "header(\"HTTP/1.1 302 Found\");\n";
                $phpheader.= "header(\"location: ".(($metapath==0)?'/':trim($metapath))."\");\n";
                $phpheader.= "die();\n";
            } else if(($metapath==0 || trim($metapath)!='') && $mode=="preview") {
                $_SESSION['previewforward'] = returnIntLang('preview forwarded to', false)." '".(($metapath==0)?returnIntLang('str start page'):trim($metapath))."'";
            } else {
                $_SESSION['previewforward'] = '';
            }

            $phpheader.= "\n";
            // use globalvars include
            $phpheader.= "DEFINE('MID', ".intval($pubid).");\n";
            $phpheader.= "\n";
            // use globalvars include
            $phpheader.= str_replace("//", "/", str_replace("//", "/", "@include \$_SERVER['DOCUMENT_ROOT'].'/data/include/global.inc.php';\n"));
            // output parsedirectories property for dynamic link creation on page
            if (isset($_SESSION['wspvars']['publisherdata']['parsedirectories'])) {
                $phpheader.= "\$_SESSION['wsppage']['pagelinks'] = ".intval($_SESSION['wspvars']['publisherdata']['parsedirectories']).";\n";
            } else {
                $phpheader.= "\$_SESSION['wsppage']['pagelinks'] = 0;\n";
            }
            // output pagelang in file to use with localiced modules and contents
            $phpheader.= "\$_SESSION['wsppage']['pagelang'] = '".trim($lang)."';\n";
            // output mid in file
            $phpheader.= "\$_SESSION['wsppage']['mid'] = ".intval($pubid).";\n";
            // output mid tree in file
            $phpheader.= "\$_SESSION['wsppage']['midtree'] = unserializeBroken(\"".serialize(returnIDTree($pubid))."\");\n";
            
            // if menupoint should not be visible by day/time restriction or login ..
            if (intval($mid_res['set'][0]['login'])==1 && !($mode=="preview")) {
                $phpheader.= "if (isset(\$_SESSION['wsppage']['userlogin']) && \$_SESSION['wsppage']['userlogin']===false) { 
                    die('<em>login required</em>');
                }\n";
            }

            if ($content_num==0 && $content_num!=$realcontent_num) {
                // the contents where hidden by lockpage preference
                if ($hiddenmenu==2 && $nocontentmenu==0) {
                    $phpheader.= "die('<em>This page is out of date or accessing this page is restricted. If you see this on a page you were awaiting contents for sure, please contact our webmaster.</em>');\n";
                }
            } else if ($content_num==0) {
                // there were really NO contents ;)
                if ($nocontentmenu==0) {
                    $phpheader.= "die('<em>Die Inhalte dieser Seite sind nicht mehr vorhanden oder der Zugang ist gesperrt. Wenn Sie sicher sind, dass hier ein Inhalt zu sehen sein sollte, kontaktieren Sie bitte unserer Webmaster.<br />This page is out of date or accessing this page is restricted. If you see this on a page you were awaiting contents for sure, please contact our webmaster.</em>');\n";
                }
            }

            // clean paths used in php header
            if ($header!="") {
                $phpheader .= $header;
            }
            // insert php part into files
            if ($phpheader!="") {
                $tmpbuf .= '<'.'?'.'php'."\n".$phpheader.'?'.'>';
            }
            // free memory
            unset($header);
                
            // create normalized header with title and metainformation
            if ($mode=="preview"):
                $tmpbuf .= getHeadVar(intval($mid_res['set'][0]['mid']), $lang, true, $metascript);
            else:
                $tmpbuf .= getHeadVar(intval($mid_res['set'][0]['mid']), $lang, false, $metascript);
            endif;
            
            // if menupoint should not be visible by day/time restriction or login ..
            if (intval($mid_res['set'][0]['login'])==1 && !($mode=="preview")):
                // just dont insert templatecode for locked files or files that will not show anything ;)
                $tmpbuf .= "<!-- the content of this page is hidden and shall not be shown to anyone. if this text appears, something went wrong in php header -->";
            else:
                // inserting templatecode to file IF contents OR frame shall be displayed
                $tmpbuf .= $templatecode;
            endif;
            
            // create footer
            $tmpbuf .= getFootVar();
            
            // replace selfvars with its contents
            foreach ($selfvardata as $key => $value) {
                if ($_SESSION['wspvars']['stripslashes']>0) {
                    for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++) {
                        $value = stripslashes($value);
                    }
                }
                $tmpbuf = str_replace("[%".$key."%]", $value, $tmpbuf);
            }
            
            // find and replace global contents used in template with its contents ...
            foreach ($globalcontentvars AS $key => $value):
                if (array_key_exists($key, $globalcontent)):
                    $tmpbuf = str_replace($value, $globalcontent[$key], $tmpbuf);
                else:
                    // replace empty globalcontent vars with ... EMPTY string 
                    $tmpbuf = str_replace($value, '', $tmpbuf);
                endif;
            endforeach;
            
            // find all content vars in file code
            $contentvars = getContentvars($tmpbuf, intval($used_template));
            
            // replace wsp-content-vars with its contents
            foreach ($contentvars AS $key => $value) {
                // replace empty content vars on forwarding pages with a link
                if (trim($metapath)!='' && $mode=="preview" && trim($content[$key])=='') {
                    $content[$key] = returnIntLang('preview this page has no contents and is automatically forwarding to')." <a href='?previewid=".intval($forwardPage)."&previewlang=".$lang."'>".returnPath($forwardPage, 1, '', $lang)."</a>";
                }
                if (array_key_exists($key, $content)) {
                    $tmpbuf = str_replace($value, $content[$key], $tmpbuf);
                } else {
                    // replace empty content vars with ... EMPTY string 
                    $tmpbuf = str_replace($value, '', $tmpbuf);
                }
            }
            
            // replace language placeholder
            $languages = $_SESSION['wspvars']['sitelanguages'];
            $langbuf = "";
            if (count($languages['shortcut'])>0) {
                $l_num = 0;
                // check, if there are language alternatives
                foreach ($languages['shortcut'] AS $lkey => $lvalue) {
                    // if language select should be linked to content avaiability
                    if (isset($_SESSION['wspvars']['publisherdata']['setoutputlang']) && $_SESSION['wspvars']['publisherdata']['setoutputlang']=='content') {
                        $l_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid_res['set'][0]['mid'])." AND `content_lang` = '".$lvalue."' AND `trash` = 0";
                        $l_res = doSQL($l_sql);
                        if ($l_res['num']>0) { $l_num++; }
                    } 
                    // else language select is bind to menupoint
                    else {
                        $l_sql = "SELECT `mid` FROM `menu` WHERE `mid` = ".intval($mid_res['set'][0]['mid'])." AND `trash` = 0";
                        $l_res = doSQL($l_sql);
                        if ($l_res['num']>0) { $l_num++; }
                    }
                }
                if ($l_num>1) {
                    if (isset($_SESSION['wspvars']['publisherdata']['showlang']) && $_SESSION['wspvars']['publisherdata']['showlang']=="dropdown") {
                        $langbuf.= "<form name='switchpagelang' id='switchpagelang' method='post' target='_top'><select name='choosepagelang' id='choosepagelang' onchange=\"document.getElementById('switchpagelang').action = document.getElementById('choosepagelang').value; document.getElementById('switchpagelang').submit();\">";
                    }
                    else {
                        $langbuf.= "<ul class=\"langlist\">";
                    }
                    foreach ($languages['shortcut'] AS $lkey => $lvalue) {
                        if ($l_res['num']>0) {
                            if ($mode=="preview") {
                                $langlink = "/".WSP_DIR."/showpreview.php?previewid=".intval($mid_res['set'][0]['mid'])."&previewlang=".$languages['shortcut'][$lkey];
                            }
                            else {
                                $pathdata = fileNamePath(intval($mid_res['set'][0]['mid']), 0, 0, 0);
                                if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {
                                    $langlink = cleanPath('/'.(($languages['shortcut'][$lkey]!=WSP_LANG)?$languages['shortcut'][$lkey]:'').'/'.$pathdata['path']);
                                }
                                else {
                                    $langlink = cleanPath('/'.(($languages['shortcut'][$lkey]!=WSP_LANG)?$languages['shortcut'][$lkey]:'').'/'.$pathdata['file']);
                                }
                            }
                            if (isset($_SESSION['wspvars']['publisherdata']['showlang']) && $_SESSION['wspvars']['publisherdata']['showlang']=="dropdown") {
                                $langbuf.= "<option value=\"".$langlink."\"";
                                if ($languages['shortcut'][$lkey]==$lang) {
                                    $langbuf.= " selected=\"selected\"";
                                }
                                $langbuf.= ">";
                                $langbuf.= $languages['longname'][$lkey];
                                $langbuf.= "</option>";
                            }
                            else {
                                $langbuf.= "<li class=\"langitem ".$languages['shortcut'][$lkey]." ";
                                $langbuf.= ($languages['shortcut'][$lkey]==$lang)?" activelang ":" inactivelang ";
                                $langbuf.= "\">";
                                $langbuf.= "<a href=\"";
                                $langbuf.= $langlink."\"";
                                $langbuf.= ($languages['shortcut'][$lkey]==$lang)?" class='activelang' ":" class='inactivelang' ";
                                if ($mode=="preview") { $langbuf.= " target='_top' "; }
                                $langbuf.= ">";
                                if (isset($_SESSION['wspvars']['publisherdata']['showlang']) && $_SESSION['wspvars']['publisherdata']['showlang']=="icon") {
                                    $langbuf.= '<img src="/media/screen/lang/'.$lvalue.'.png" alt="'.$lvalue.'" title="'.$languages['longname'][$lkey].'" />';
                                }
                                else {
                                    $langbuf.= $languages['longname'][$lkey];
                                }
                                $langbuf.= "</a>";
                                $langbuf.= "</li>";
                            }
                        }
                    }
                }
                if (isset($_SESSION['wspvars']['publisherdata']['showlang']) && $_SESSION['wspvars']['publisherdata']['showlang']=="dropdown") {
                    $langbuf.= "</select></form>";
                }
                else {
                    $langbuf.= "</ul>";
                }
            }
            $tmpbuf = str_replace("[%LANGUAGE%]", ((count($languages['shortcut'])>1) ? $langbuf : '') , $tmpbuf);
            
            // replacing known wsp vars with values 
            // pagename
            $pagename = trim($mid_res['set'][0]['description']);
            $pagenamelng = unserializeBroken(trim($mid_res['set'][0]['langdescription']));
            if (is_array($pagenamelng) && array_key_exists($lang, $pagenamelng)): $pagename = $pagenamelng[$lang]; endif;
            $tmpbuf = str_replace("[%PAGENAME%]", $pagename, $tmpbuf);
            $tmpbuf = str_replace("[%LASTPUBLISHED%]", date("Y-m-d H:i:s"), $tmpbuf);
            $tmpbuf = str_replace("[%PUBLISHTIME%]", sprintf("%0.6f ms", (microtime(true)-$parsetime)), $tmpbuf);
            $tmpbuf = str_replace("[%FILEPATH%]", fileNamePath(intval($mid_res['set'][0]['mid'])), $tmpbuf);
            $tmpbuf = str_replace("[%FILEAUTHOR%]", (isset($_SESSION['wspvars']['realname'])?stripslashes($_SESSION['wspvars']['realname']):'unknown'), $tmpbuf);
            // content vars supported by WSP
            $tmpbuf = str_replace("[%GAOPTOUT%]", ' <a onclick="gaOptout();" style="cursor: pointer;">Disable Google Analytics</a>', $tmpbuf);
            
            // create menus and links
            // replace all wsp<6 menuvars with wsp>=6 menuvar style
            $tmpbuf = str_replace("[%MENUVAR ", "[%MENUVAR:", $tmpbuf);
            // create menus
            // run through tmpbuf to find all menu variables and convert var to content with menu data
            if ($mode=="preview"):
                $tmpbuf = createMenu($tmpbuf, intval($mid_res['set'][0]['mid']), $lang, true);
            else:
                $tmpbuf = createMenu($tmpbuf, intval($mid_res['set'][0]['mid']), $lang, false);
            endif;

            // replacing every known shortcut var with it's link
            $shortcut_sql = "SELECT `mid`, `linktoshortcut` FROM `menu` WHERE `linktoshortcut` != '' AND `trash` = 0";
            $shortcut_res = doSQL($shortcut_sql);
            if ($shortcut_res['num']>0) {
                foreach ($shortcut_res['set'] AS $scresk => $scresv) {
                    if ($mode=='preview'):
                        $shortcut = buildMenu(array(array('TYPE' => 'SHORTCUT', 'MENU.SHOW' => intval($scresv['mid']))), 0, intval($scresv['mid']), intval($scresv['mid']), $lang, 0, 1, true);
                    else:
                        $shortcut = buildMenu(array(array('TYPE' => 'SHORTCUT', 'MENU.SHOW' => intval($scresv['mid']))), 0, intval($scresv['mid']), intval($scresv['mid']), $lang, 0, 1, false);
                    endif;
                    $tmpbuf = str_replace("[%".strtoupper(trim($scresv['linktoshortcut']))."%]", $shortcut['menucode'], $tmpbuf);
                }
            }
            
            // replace ALL detected vars with contents or empty strings ...
            // MISSING!!!!!
                
            // set content to utf8
            $tmpbuf = setUTF8($tmpbuf);
            // FINALLY ............
            // write output to file
            if ($con!==false && $mode!="preview") {
                // define temp directory
                $tmppath = cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/");
                // define temp filename
                $tmpfile = tempnam($tmppath, 'wsp');
                // create temporary buffer file with file contents
                $fh = fopen($tmpfile, "r+");
                // write contents
                fwrite($fh, $tmpbuf);
                // close buffer
                fclose($fh);
                // creating final file
                $pathdata = fileNamePath(intval($mid_res['set'][0]['mid']), 0, 0, 0);
                if (defined('WSP_DEV') && WSP_DEV) {
                    echo '<pre>'.var_export($pathdata, true).'</pre>';
                }
                if ((isset($_SESSION['wspvars']['publisherdata']['parsedirectories']) && intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1) || $parsedir==1) {
                    // if setup is to create directories instead of files
                    // create the index.php IN folder
                    // setup LANGUAGE base folder on top of path if published language is different to base language
                    if (isset($lang) && trim($lang)!='' && $lang!=WSP_LANG) {
                        $dirpath = cleanPath('/'.$lang.'/'.$pathdata['folder']);
                        $filepath = cleanPath('/'.$lang.'/'.$pathdata['folderfile']);
                        $frwdir = cleanPath('/'.$lang.'/'.$pathdata['orig']);
                        $frwpath = cleanPath('/'.$lang.'/'.$pathdata['folder']);
                    } else {
                        $dirpath = cleanPath('/'.$pathdata['folder']);
                        $filepath = cleanPath('/'.$pathdata['folderfile']);
                        $frwdir = cleanPath('/'.$pathdata['orig']);
                        $frwpath = cleanPath('/'.$pathdata['folder']);
                    }

                    // create the folder (except if the MAIN INDEX is published)
                    if ($filepath!='/index.php') {
                        $dirreturn = createFolder($dirpath);
                        if ($dirreturn===false) {
                            addWSPMsg('errormsg', returnIntLang('publisher directory could not be created1', false)." \"".$dirpath."\" ".returnIntLang('publisher directory could not be created2', false));
                        }
                    }
                    $copyfile = copyFile(str_replace(DOCUMENT_ROOT, '', $tmpfile), $filepath);
                    if ($copyfile===false) {
                        addWSPMsg('errormsg', returnIntLang('publisher file could not be created1', false)." \"".$filepath."\" ".returnIntLang('publisher file could not be created2', false));
                    }
                    else {
                        doSQL("UPDATE `menu` SET `contentchanged` = 0, `lastpublish` = ".time()." WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
                        $returnstat = true;
                    }
                    @unlink($tmpfile);
                    
                    // create a header location file that points to final file
                    $tmpfile = tempnam($tmppath, 'frw');
                    $fh = fopen($tmpfile, "r+");
                    $frwbuf = '<?php header("location: . '.cleanPath('/'.trim($mid_res['set'][0]['filename']).'/').'"); ?>';
                    fwrite($fh, $frwbuf);
                    // (over)write an (existing) file ONE directory step UPWARDS with header location
                    // e.g. folder is /info/privacy/ the file /info/privacy.php will be overwritten with a header location to folder
                    $copyfile = copyFile(str_replace(DOCUMENT_ROOT, '', $tmpfile), $frwdir);
                    if (!$copyfile) { $returnstat = false; }
                    // AND, if this file is the index.php of home directory -
                    // 
                    if ($pathdata['filefolder']=='' && $pathdata['file']=='/index.php') { 
                        $copyfile = copyFile(str_replace(DOCUMENT_ROOT, '', $tmpfile), cleanPath('/'.$frwpath.'/index.php'));
                    }
                    // remove tmp file
                    @unlink($tmpfile);
                    
                } else {
                    
                    // setup LANGUAGE base folder on top of path if published language is different to base language
                    if ($lang!=WSP_LANG) {
                        $dirpath = cleanPath('/'.$lang.'/'.$pathdata['filefolder']);
                        $filepath = cleanPath('/'.$lang.'/'.$pathdata['file']);
                    } else {
                        $dirpath = cleanPath('/'.$pathdata['filefolder']);
                        $filepath = cleanPath('/'.$pathdata['file']);
                    }
                    // create the folder
                    if ($filepath!='/index.php') {
                        $dirreturn = createFolder($dirpath);
                        if ($dirreturn===false) {
                            addWSPMsg('errormsg', returnIntLang('publisher directory could not be created1', false)." \"".$dirpath."\" ".returnIntLang('publisher directory could not be created2', false));
                        }
                    }
                    // copy the INDEX-file
                    $copyfile = copyFile($tmpfile, $filepath);
                    if (!$copyfile) { $returnstat = false; }
                    unlink($tmpfile);
                }
            }
        } else {
            $returnstat = "notemplatefound";
            addWSPMsg('errormsg', "<em>Error 2 : ".$used_template."</em> | template not found regarding »".trim($mid_res['set'][0]['description'])."«");
        }

	} else {
		$returnstat = "nomenufound";
		addWSPMsg('errormsg', "<em>Error 1 : ".$pubid."</em> | menuid not found");
	}
	
	if ($mode!="preview") { 
        if($newendmenu) { createNewMenu(); }
        return isset($returnstat) ? $returnstat : false ;
    } else if ($mode=="preview") {
		$tmppath = cleanPath(DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/");
		// define temp filename
		$tmpfile = $tmppath."previewfile.php";
		$fh = fopen($tmpfile, "w+");
		$wr = fwrite($fh, $tmpbuf);
		fclose($fh);
	} else {
		return $returnstat;
	}
}
}

if (!(function_exists('createNewMenu'))) {
    function createNewMenu() {
        $drop_res = doSQL("DROP TABLE IF EXISTS `menu_public`");
        $create_res = doSQL("CREATE TABLE IF NOT EXISTS `menu_public` LIKE `menu`");
        if (!($create_res['res'])) { 
            addWSPMsg('errormsg', 'publisher creation of menu_public failed');
            if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg('errormsg', var_export($create_res, true));
            }
        }
        $trunc_res = doSQL("DELETE FROM `menu_public`");
        if (!($trunc_res['res'])) { 
            addWSPMsg('errormsg', 'publisher truncation of menu_public failed');
            if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg('errormsg', var_export($trunc_res, true));
            }
        }
        $insert_res = doSQL("INSERT IGNORE INTO `menu_public` SELECT * FROM `menu`");
        if (!($insert_res['res'])) { 
            addWSPMsg('errormsg', 'publisher adding data to menu_public failed'); 
            if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg('errormsg', var_export($insert_res, true));
            }
        }
    }
}
// EOF ?>