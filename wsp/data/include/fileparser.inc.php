<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 */

/*
 * 2021-03-02
 * removed parsing of contents if header location is set
 * removed sitekeys param
 *
 * 2022-10-25
 * added privacy stuff
 * 
 * 2023-01-04
 * fixed file system copy problems
 * 
 * 2023-01-13
 * fixed error with non-ZERO externpage
 * 
 * 2023-02-02
 * fixed bug with external forwarding = 0
 * 
 * 2025-02-14
 * bugfixes
 * 
 */

$_SESSION['wspvars']['logdevmsg'] = true;

if (!(function_exists('createPath'))) { function createPath() {} }

if (!(function_exists('getPrivacyMode'))) { function getPrivacyMode( $mid = null , $template = null ) {
	$useprivacy_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'privacy_sidewide'";
	$useprivacy_res = doResultSQL($useprivacy_sql);
	if (intval($useprivacy_res)===1) { return true; }
	if ((int)$template>0) {
		$template_sql = "SELECT `template` FROM `templates` WHERE `id` = ".intval($template);
		$template_res = doResultSQL($template_sql);
		if ($template_res) {
			// now we know the template (with global contents!?!?)
			// let's check for global contents within the template
			preg_match_all('/\[%GLOBALCONTENT:[0-9]*%\]/mi', trim($template_res), $globalcontents, PREG_SET_ORDER, 0);
			if (isset($globalcontents[0]) && is_array($globalcontents[0])) {
				foreach ($globalcontents[0] AS $gcData) {
					$gcavail_sql = "SELECT `interpreter_guid` FROM `globalcontent` WHERE `id` = ".intval(preg_replace ('/[^0-9]/mi', '' , $gcData))." AND `interpreter_guid` = '447ce0a0-25ad-4967-82b1-405e31bf5bb4'";
					$gcavail_res = doSQL($gcavail_sql);
					if ($gcavail_res['num']>0) { return true; }
				}
			}
		}
	}
	if ((int)$mid>0) {
		$content_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid)." && `interpreter_guid` = '447ce0a0-25ad-4967-82b1-405e31bf5bb4' && `trash` = 0";
		$content_res = doResultSQL($template_sql);
		if (!empty($content_res['num'])) { return true; }
	}
	return false;
}}

if (!(function_exists('getHeadVar'))) {
	function getHeadVar($mid, $publishlang, $preview = false, $metascript = array()) {
	// get site based data for building a head
	$globalhead_sql = "SELECT * FROM `wspproperties`";
	$globalhead_res = doSQL($globalhead_sql);
	if ($globalhead_res['num']>0) {
		$globalheaddata = array();
		foreach ($globalhead_res['set'] AS $ghresk => $ghresv) {
			$globalheaddata[trim($ghresv['varname'])] = $ghresv['varvalue'];
		}
	}
	$siteurl = $globalheaddata['siteurl'];
	$sitetitle = $globalheaddata['sitetitle'];
	$siterobots = $globalheaddata['siterobots'];
	$sitekeys = $globalheaddata['sitekeys'];
	$sitedesc = $globalheaddata['sitedesc'];
	$sitecopy = $globalheaddata['sitecopy'];
	$siteauthor = $globalheaddata['siteauthor'];
	$googleverify = $globalheaddata['googleverify'];
	$revisit = ($globalheaddata['revisit'] ?? 14);
	$doctype = $globalheaddata['doctype'];
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
    if ($middata_res['num']>0) {
		$visibility = intval($middata['visibility']);
		$lockpage = intval($middata['lockpage']);
		$denylang = !empty($middata['denylang']) ? unserializeBroken(trim($middata['denylang'])): [];
		if (is_array($denylang) && in_array($publishlang, $denylang)) {
			$visibility = 0;
		}
	}
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
    $onlyheader = false;

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
			$onlyheader = true;
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
			$blockfonts = getPrivacyMode( $mid, $used_template ) ? @((int)$globalheaddata['privacy_disablefonts']===1) : false;
			$blockstats = getPrivacyMode( $mid, $used_template ) ? @((int)$globalheaddata['privacy_disablestats']===1) : false;
			$blockscript = getPrivacyMode( $mid, $used_template ) ? @((int)$globalheaddata['privacy_disablescript']===1) : false;
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
			// removed keys 2021-03-02
			// $headvar.= "<meta name=\"keywords\" content=\"".setUTF8(str_replace("\"", "'", stripslashes($sitekeys)))."\" />\n";
			if (trim($siteauthor)!="") {
				$headvar.= "<meta name=\"author\" content=\"".setUTF8(str_replace("\"", "'", stripslashes($siteauthor)))."\" />\n";
			} else {
				$headvar.= "<meta name=\"author\" content=\"".setUTF8(str_replace("\"", "'", stripslashes($_SESSION['wspvars']['realname'])))."\" />\n";
			}
			$headvar.= "<meta name=\"generator\" content=\"".setUTF8($_SESSION['wspvars']['wsplongname'])."\" />\n";
			$headvar.= "<meta name=\"robots\" content=\"".$siterobots."\" />\n";
			// revisit
			if ($revisit!="") {
				$headvar .= "<meta name=\"revisit-after\" content=\"".$revisit."\" />\n";
			}
			$headvar .= "<!-- setup mobile + desktop classes -->\n";
			$headvar .= "<style type='text/css'>\n";
			$headvar .= ".mobile {display: none;} @media only screen and (max-width: 400px){ .mobile {display: inherit;} .desktop {display: none;} }\n";
			$headvar .= "</style>\n";
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
                    if ($tfwk=='bootstrap' && $tfwv==1) {
                        $headvar .= "<link rel='stylesheet' href='/media/layout/bootstrap/bootstrap.css'>\n";
                        $headvar .= "<script src='/data/script/bootstrap/bootstrap.js'></script>\n";
                    }
					if ($tfwk=='bootstrap_5' && $tfwv==1) {
                        $headvar .= "<link rel='stylesheet' href='/media/layout/bootstrap_5/bootstrap.css'>\n";
                        $headvar .= "<script src='/data/script/bootstrap_5/bootstrap.js'></script>\n";
                    }
					if ($tfwk=='covifuncs' && $tfwv==1) {
						$headvar .= "<script src=\"/data/script/covifuncs.js\" type=\"text/javascript\"></script>\n";
					}
                }
            }
			// predefined fonts
			if (is_array($template_fonts)) {
				$headvar .= $blockfonts ? "<"."?"."php if(!(isset(\$_SESSION['wsppage']['allowfonts'])) || (isset(\$_SESSION['wsppage']['allowfonts']) && \$_SESSION['wsppage']['allowfonts']===true)) { "."?".">\n" : "";
				$headvar .= "<!-- wsp integrated fonts -->\n";
				if (isset($template_fonts['source']) && $template_fonts['source']=='google' && isset($template_fonts['list']) && $template_fonts['list']!='') {
					$headvar .= "<link href=\"//fonts.googleapis.com/css?family=".trim($template_fonts['list'])."\" rel=\"stylesheet\" type=\"text/css\">\n";
				}
				$headvar .= $blockfonts ? "<"."?"."php } "."?".">\n" : "";
			}
			// js from template
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
							$jscript = unserializeBroken(trim($javascript_res['set'][0]['scriptcode']));
							// handling js-folders!!!
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
			if (!empty($middata['addscript'])) {
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
							// just files
							if (trim($style_res['set'][0]['browser'])=="all" || trim($style_res['set'][0]['browser'])=="") {
								$headvar .= "<link rel=\"stylesheet\" href='/media/layout/".trim($style_res['set'][0]['file']).".css' media=\"".trim($style_res['set'][0]['media'])."\" type=\"text/css\" />\n";
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
			// css addon from menu
			if (!empty($middata['addcss'])) {
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
			// favicon
			if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/screen/favicon.ico")) {
				$headvar .= "<!-- icons -->\n";
				$headvar .= "<link rel=\"shortcut icon\" href=\"/media/screen/favicon.ico\" />\n";
			}
			// iOS properties
			if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/screen/iphone_favicon.png")) {
				$headvar .= "<link rel=\"apple-touch-icon\" href=\"/media/screen/iphone_favicon.png\" />\n";
			}
			// viewport properties
			if ($template_generic_viewport!='') {
				$headvar .= "<meta name=\"viewport\" content=\"".$template_generic_viewport."\" />\n";
			}
			// opengraph properties
			// opengraph screenshot
			$headvar .= "<!-- og meta -->\n";
			if (is_file($_SERVER['DOCUMENT_ROOT']."/media/screen/fbscreenshot.png")) {
				$headvar .= "<meta property=\"og:image\" content=\"http://".$siteurl."/media/screen/fbscreenshot.png\" />\n";
            }
			// facebook title
			$headvar .= "<meta property=\"og:title\" content=\"".str_replace("\"", "'", stripslashes(trim($sitetitle)))."\" />\n"; //'
			// facebook url
			$headvar .= "<meta property=\"og:url\" content=\"".$_SERVER['REQUEST_SCHEME']."://".$siteurl."\" />\n";
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
			$analytics_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'analyticsprop'";
			$analytics_res = doResultSQL($analytics_sql);
            if ($analytics_res===false) {
                // analytics fallback
                $analytics_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'googleanalytics'";
                $analytics_res = doSQL($analytics_sql);
                if ($analytics_res['num']>0 && !($preview)) {
                    if (trim($analytics_res['set'][0]['varvalue'])!="") {
                    	$headvar .= $blockstats ? "<"."?"."php if(!(isset(\$_SESSION['wsppage']['allowstats'])) || (isset(\$_SESSION['wsppage']['allowstats']) && \$_SESSION['wsppage']['allowstats']===true)) { "."?".">\n" : "";
                        $headvar.= "<!-- analytics setup -->\n".stripslashes(trim($analytics_res['set'][0]['varvalue']))."\n";
						$headvar.= $blockstats ? "<"."?"."php"." } "."?".">\n" : "" ;
                    }
                }
            } else if (!($preview)) {
                if (trim($analytics_res)!="") {
					$headvar .= $blockstats ? "<"."?"."php if(!(isset(\$_SESSION['wsppage']['allowstats'])) || (isset(\$_SESSION['wsppage']['allowstats']) && \$_SESSION['wsppage']['allowstats']===true)) { "."?".">\n" : "";
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
                    $headvar .= "<script async src='https://www.googletagmanager.com/gtag/js?id=".trim($analytics_res)."'></script>\n";
                    $headvar .= "<script>\n";
                    $headvar .= "window.dataLayer = window.dataLayer || [];\n";
                    $headvar .= "function gtag(){dataLayer.push(arguments);}\n";
                    $headvar .= "gtag('js', new Date());\n";
                    $headvar .= "gtag('config', '".trim($analytics_res)."', { 'anonymize_ip': true });\n";
                    $headvar .= "</script>\n";
					$headvar.= $blockstats ? "<"."?"."php"." } "."?".">\n" : "" ;
                }
            }
			$headvar .= "</head>\n";
            // do js-based location reload if forwarding is active in any way ..
			$headvar .= "<body";
			if ($template_body!="") {
				$headvar .= " ".$bodytag;
            }
			$headvar .= ">\n";
        }
    }
	return array($headvar, $onlyheader);
	}
}

// close pages
if (!(function_exists('getFootVar'))) {
	function getFootVar() {
   		$footervar = '';
    	$footervar.= "</body>\n</html>\n<!-- content published with wsp -->";
		return $footervar;
	}	// getFootVar()
}

function getContentvars($buf) {
	$cnt = array();
	$pos = strpos($buf, '[%CONTENTVAR%]');
	if (!($pos===false)):
		$cnt[1] = "[%CONTENTVAR%]";
	endif;
	for ($cvar=1; $cvar<20; $cvar++):
		$pos = strpos($buf, "[%CONTENTVAR:".$cvar."%]");
		if (!($pos===false)):
			$cnt[($cvar+1)] = "[%CONTENTVAR:".$cvar."%]";
		endif;
	endfor;	
	return $cnt;
}
	
function getGlobalContentvars($buf) {
	$cnt = array();
	$gcid_sql = "SELECT MIN(id) AS min, MAX(id) AS max FROM `globalcontent`";
	$gcid_res = doSQL($gcid_sql);
	if ($gcid_res['num']>0):
		for ($cvar=intval($gcid_res['set'][0]['min']); $cvar<=intval($gcid_res['set'][0]['max']); $cvar++):
			$pos = strpos($buf, "[%GLOBALCONTENT:".$cvar."%]");
			if (!($pos===false)):
				$cnt[$cvar] = "[%GLOBALCONTENT:".$cvar."%]";
			endif;
		endfor;
	endif;
	return $cnt;
	}

// create missing FTP-directories
if (!(function_exists('createFTPPath'))) {
	function createFTPPath($ftphandle, $path) {
		$path = substr($path, 1);
		$aPath = explode('/', $path);
		$path = $_SESSION['wspvars']['ftpbasedir'];
		foreach ($aPath as $value) {
			$path .= "/".$value;
			@ftp_mkdir($ftphandle, $path);
			
		}
	}
}

/**
 * ermittelt den String für die if()-Bedingung für die Zeit/Tag-gesteuerte
 * Anzeige von Contents.
 *
 * @param integer $contentID
 * @return string
 */
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

/* publish selected pages */
function publishSites($pubid, $mode = 'publish', $lang = 'de', $newendmenu = false) {
	/* $mode => publishing possibilities */
	/* 'publish' publishs menu AND contents */ /* ??????? */
	/* 'structure' should read existing file from structure and replace menu parts with new menu */
	/* 'content' publishs contents */
	/* 'preview' publishs menu AND contents in preview mode */
	if ($mode!='preview'): $_SESSION['preview'] = 0; endif;

	if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
		error_log(date('Y-m-d H:i:s') . ' fileparser : start –––––––– ' . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
		error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : pubid ' . $pubid . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
		error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : mode ' . $mode . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
		error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : lang ' . $lang . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
		error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : newendmenu ' . intval($newendmenu) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
	}

	$returnstat = false;
	// define empty vars
	$header = '';
	$bodyfunc = '';
	$metapath = '';
	$metascript = array();
	$content = array();
	$globalcontent = array();
	$ccarray = array(0 => 'section', 1 => 'div', 2 => 'span', 3 => 'li');
	// grep parsedir information
	$parsedir = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'parsedirectories'"));
	// bind content view to menu visibility
	$bindcontentview = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'bindcontentview'"));
	// how to handle contents that are connected to hidden menupoints
	$hiddenmenu = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddenmenu'"));
	// how to handle pages without contents
	$nocontentmenu = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'nocontentmenu'"));
	
	/* grab menupoint information */
	$mid_sql = "SELECT * FROM `menu` WHERE `mid` = ".intval($pubid);
	$mid_res = doSQL($mid_sql);

	if ($mid_res['num']>0) {
		$midvisibility = 1;
		$lockpage = intval($mid_res['set'][0]['lockpage']);
		$denylang = !empty($mid_res['set'][0]['denylang']) ? unserializeBroken(trim($mid_res['set'][0]['denylang'])) : [];
		if (intval($mid_res['set'][0]['visibility'])==0):
			$midvisibility = 0;
		elseif (is_array($denylang) && in_array($lang, $denylang)):
			$midvisibility = 0;
		endif;
        // do only connect to ftp if preview is off
        if ($mode!='preview') {
			
			$ftp = null;
			$ftpt = 0;

			if (isset($_SESSION['wspvars']['directwriting']) && $_SESSION['wspvars']['directwriting']===true) {
				$usedirect = true;
			} else {
				while (empty($ftp) && $ftpt<3) {
					$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
					if ($ftp!==false) {
						if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { 
							$ftp = false; 
						}
					}
					if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { 
						ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); 
					}
					$ftpt++;
				}

			}
        }
		if ($mode=='preview' || $ftp!==false || $usedirect) {

			if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
				error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : connected ' . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
			}
			
			// get data from selfvars
			$selfvardata = array();
			$selfvar_sql = "SELECT `id`, `name`, `selfvar` FROM `selfvars`";
			$selfvar_res = doSQL($selfvar_sql);
			if ($selfvar_res['num']>0) {
				foreach ($selfvar_res['set'] AS $svresk => $svresv) {
					$vardata = trim($svresv['selfvar']);
					if (stristr($vardata, 92.92) || stristr($vardata, 92.34)) {
						$vardata = stripslashes($vardata);
					}
					if ($_SESSION['wspvars']['stripslashes']>0) {
						for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
							$vardata = stripslashes($vardata);
						endfor;
                    }
					$selfvardata['VAR:'.strtoupper(trim($svresv['name']))] = $vardata;
					$selfvardata[strtoupper(trim($svresv['name']))] = $vardata;
                }
			}
			// get template info
			$used_template = getTemplateID($pubid);
			if (isset($_REQUEST['previewtpl']) && intval($_REQUEST['previewtpl'])>0) {
				$used_template = intval($_REQUEST['previewtpl']);
			}
			if (isset($_REQUEST['previewtpl']) && intval($_REQUEST['previewtpl'])>0) {
				$used_template = intval($_REQUEST['previewtpl']);
			}
            // get template data from db
			$template_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($used_template);
			$template_res = doSQL($template_sql);
			if ($template_res['num']>0) {

				if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
					error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : template found ' . __LINE__ . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
				}

				$parsetime = microtime();
				$templatecode = stripslashes(trim($template_res['set'][0]['template']));
				$bodytagcode = stripslashes(trim($template_res['set'][0]['bodytag']));
				// check for forwarding
				// heading intern page
				$internPage = 0;
				if (!empty($mid_res['set'][0]['interntarget']) && trim($mid_res['set'][0]['interntarget'])=="_parent") {
					$internPage = intval($mid_res['set'][0]['internlink_id']);
				}
				$externPage = 0;
				if ($internPage==0 && (!empty($mid_res['set'][0]['externtarget']) && trim($mid_res['set'][0]['externtarget'])=="_parent")) {
					// heading to extern page
					$externPage = trim($mid_res['set'][0]['offlink']);
					if (empty($externPage)) {
						$externPage = 0;
					}
				}
				$forwardPage = intval($mid_res['set'][0]['forwarding_id']);
				// get origin filename
				$filename = trim($mid_res['set'][0]['filename']);
				// get is index flag
				$isindex = intval($mid_res['set'][0]['isindex']);
				// get contents
				$content_sql = "SELECT * FROM `content` WHERE `content_lang` = '".escapeSQL($lang)."' AND `mid` = ".intval($mid_res['set'][0]['mid'])." AND `visibility` > 0 AND `trash` = 0 ORDER BY `position` ASC";
				$content_res = doSQL($content_sql);
                $content_num = $content_res['num'];
				// setting real content num to get later info of existing contents
				$realcontent_num = $content_res['num'];
				
				if ($bindcontentview==1) {
					// set content num to 0 if visibility of content IS NOT locked to menupoint and menupoint is hidden 
					if ($midvisibility==0 && intval($mid_res['set'][0]['lockpage'])==0) {
                        $content_num = 0; 
                    }
                } else {
					// set content num to 0 if visibility of content IS locked to menupoint and menupoint is hidden 
					if ($midvisibility==0 && intval($mid_res['set'][0]['lockpage'])==1) {
                        $content_num = 0; 
                    }
                    // set content num to 0 if menupoint is dynamic
                    if (intval($mid_res['set'][0]['editable'])==9) {
                        $content_num = 0;
                    }
				}

				if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
					error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : real content num : ' . $realcontent_num . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
					error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : content num : ' . $content_num . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
					error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : forwardPage : ' . $forwardPage . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
					error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : internPage : ' . $internPage . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
					error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : externPage : ' . $externPage . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
				}

				// if viewable contents for the page are avaiable and no forwarding is defined
				if ($content_num>0 && $forwardPage==0 && $internPage==0 && $externPage==0) {

					if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
						error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : viewable contents ' . __LINE__ . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
					}

					// run every content element to fetch data
                    foreach ($content_res['set'] AS $coresk => $coresv) {
						// check for existing contents or use global content
						$interpreter = '';
						$contentvalue = '';
						if (intval($coresv['globalcontent_id'])!=0):
							$gc_sql = "SELECT * FROM `globalcontent` WHERE `id` = ".intval($coresv['globalcontent_id'])." LIMIT 0,1";
							$gc_res = doSQL($gc_sql);
							if ($gc_res['num']>0):
								$contentvalue = trim($gc_res['set'][0]['valuefield']);
								$interpreter = trim($gc_res['set'][0]['interpreter_guid']);
							endif;
						else:
							$contentvalue = trim($coresv['valuefields']);
							$interpreter = trim($coresv['interpreter_guid']);
						endif;
						if (!(array_key_exists(intval($coresv['content_area']), $content))): $content[intval($coresv['content_area'])] = ''; endif;
						// check visibility options
						if ($mode!='preview'):
							// 22-10-23
							// do the checks for block elem disabling
							$content[intval($coresv['content_area'])].= "<"."?"."php if(!(isset(\$_SESSION['wsppage']['blockelem'])) || (isset(\$_SESSION['wsppage']['blockelem']) && !in_array(". intval($coresv['cid']) .", \$_SESSION['wsppage']['blockelem'])) || (isset(\$_SESSION['wsppage']['allowblock']) && in_array(". intval($coresv['cid']) .", \$_SESSION['wsppage']['allowblock']))): "."?".">";
							
							// parse limited visibility to content
							if(intval($coresv['visibility'])==2):
								// show only without logged in user
								$content[intval($coresv['content_area'])].= "<"."?"."php if(isset(\$_SESSION['wsppage']['userlogin']) && \$_SESSION['wsppage']['userlogin']===false): "."?".">";	
							elseif(intval($coresv['visibility'])==3):
								// show only for logged in user
								$content[intval($coresv['content_area'])].= "<"."?"."php if(array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===true): "."?".">";
							elseif(intval($coresv['visibility'])==4):
								// show only for selected logged in user
								$logincontrol = unserializeBroken($coresv['logincontrol']);
								if (is_array($logincontrol) && count($logincontrol)>0):
									$loginLink = "<"."?"."php if(array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===true ";
									$UID = array();
									foreach ($logincontrol AS $lk => $lv):
										$UID[] = "\$_SESSION['wsppage']['uservalue']==".$lv;
									endforeach;
									$loginLink.= " AND (".implode(" OR ", $UID).")";
									$loginLink.= "): "."?".">";
								endif;
								$content[intval($coresv['content_area'])].= $loginLink;
							endif;
						else:
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
						endif;
						
						// lookup for time based visibiliy
						if ($mode!='preview'):
							$content[intval($coresv['content_area'])].= getShowtimeString(intval($coresv['cid']));
						elseif ($mode=='preview'):
							if (getShowtimeString(intval($coresv['cid']))!=''):
								$content[intval($coresv['content_area'])].= "<section style='background: rgba(207,225,230,0.5)' title='timebased'>";
							endif;
						endif;
						
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
						// 5 = COMBINE WITH ELEMENT BEFORE
                        if (intval($coresv['container'])<4): 
							$content[intval($coresv['content_area'])].= "<".$ccarray[intval($coresv['container'])]." id=\"".$containerid."\" ".$containerclass.">";
						endif;
						
                        // lookup for interpreter and parsefile
						$interpreter_sql = "SELECT `parsefile` FROM `interpreter` WHERE `guid` = '".escapeSQL(trim($interpreter))."'";
						$interpreter_res = doSQL($interpreter_sql);
						if ($interpreter_res['num']>0):
							// lookup for parsefile
							if (trim($interpreter_res['set'][0]['parsefile'])!='' && is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".trim($interpreter_res['set'][0]['parsefile']))):
								require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".trim($interpreter_res['set'][0]['parsefile']);
								$parser = new $interpreterClass();
							endif;
							// bring data to files php header ...
							if (intval(method_exists($parser,'getHeader'))==1) {
								$header .= $parser->getHeader($contentvalue, intval($mid_res['set'][0]['mid']), intval($coresv['cid']), trim($lang));
                                if (intval(method_exists($parser,'closeInterpreterDB'))==1) {
                                    $parser->closeInterpreterDB();
                                }
							}
							// bring function calls to body tag
							if (intval(method_exists($parser,'getBodyFunction'))==1) {
								$bodyfunc .= $parser->getBodyFunction(trim($contentvalue), intval($mid_res['set'][0]['mid']), intval($coresv['cid']));
                                if (intval(method_exists($parser,'closeInterpreterDB'))==1) {
                                    $parser->closeInterpreterDB();
                                }
                            }
							// bring data to files head ...
							if (intval(method_exists($parser,'getMetaScript'))==1) {
								$metascript[] = $parser->getMetaScript($contentvalue, intval($mid_res['set'][0]['mid']), intval($coresv['cid']), $lang);
                                if (intval(method_exists($parser,'closeInterpreterDB'))==1) {
                                    $parser->closeInterpreterDB();
                                }
                            }
							// finally parse real content
							if (intval(method_exists($parser,'getContent'))==1) {
								// returning content from parser class
								$content[intval($coresv['content_area'])].= $parser->getContent(trim($contentvalue), intval($mid_res['set'][0]['mid']), intval($coresv['cid']), $lang);
                                if (intval(method_exists($parser,'closeInterpreterDB'))==1) {
                                    $parser->closeInterpreterDB();
                                }
                            };
						elseif ($interpreter=='genericwysiwyg'):
							// if generic wysiwyg was used ... 
							$genericcontent = unserializeBroken($contentvalue);
							$content[intval($coresv['content_area'])].= trim($genericcontent['content']);
						endif;
    
    					// close container
						if (intval($coresv['container'])<4) {
							$content[intval($coresv['content_area'] ?? 0)].= "</".$ccarray[intval($coresv['container'])].">";	
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
							// 22-10-23
							// close block elem support
							$content[intval($coresv['content_area'])].= "<"."?"."php elseif (isset(\$_SESSION['wsppage']['blockelem']) && in_array(". intval($coresv['cid']) .", \$_SESSION['wsppage']['blockelem'])): "."?".">";
							$content[intval($coresv['content_area'])].= "<p>This area was disabled within privacy reasons. <a href='?set_privacy'>Change settings</a></p>";
							$content[intval($coresv['content_area'])].= "<"."?"."php endif; "."?".">";
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
					if (intval($internPage)!=0) {
						if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
							$metapath = returnPath($internPage, 1, '', $lang);
						else:
							$metapath = returnPath($internPage, 2, '', $lang);
						endif;
					} else if (intval($externPage)!=0) {
						$metapath = trim($externPage);
						// check for http ???
					} else {
						if ($forwardPage > 0) {
							if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
								$metapath = returnPath($forwardPage, 1, '', $lang);
							else:
								$metapath = returnPath($forwardPage, 2, '', $lang);
							endif;
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
                            if ($forwardPage>0) {
								if ((isset($_SESSION['wspvars']['publisherdata']['parsedirectories']) && intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1) || $parsedir==1) {
									$metapath = returnPath($forwardPage, 1, '', $lang);
                                } else {
									$metapath = returnPath($forwardPage, 2, '', $lang);
								}
							}
						}
					}
				}
				
				if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
					error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : contents finished ' . __LINE__ . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
				}

                // find global contents used in template ...
				$globalcontentvars = getGlobalContentvars($templatecode);
				foreach ($globalcontentvars AS $key => $value) {
					$interpreter = '';
					$contentvalue = '';
					$gccontent_sql = "SELECT * FROM `globalcontent` WHERE `id` = ".$key." AND (`content_lang` = '".$lang."' OR `content_lang` = '') AND `trash` = 0";
					$gccontent_res = doSQL($gccontent_sql);
                    $gccontent_num = $gccontent_res['num'];
					// set global content num to 0 if visibility of contents is locked to (hidden) menupoint 
					if ($midvisibility==0 && intval($mid_res['set'][0]['lockpage'])==1): $gccontent_num = 0; endif;
					if ($gccontent_num>0):
						$contentvalue = trim($gccontent_res['set'][0]['valuefield']);
						$interpreter = trim($gccontent_res['set'][0]['interpreter_guid']);
					endif;
					// lookup for interpreter and parsefile
					$interpreter_sql = "SELECT `parsefile` FROM `interpreter` WHERE `guid` = '".trim($interpreter)."'";
					$interpreter_res = doSQL($interpreter_sql);
					if ($interpreter_res['num']>0):
						// lookup for parsefile
						if (trim($interpreter_res['set'][0]['parsefile'])!='' && is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".trim($interpreter_res['set'][0]['parsefile']))):
							require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".trim($interpreter_res['set'][0]['parsefile']);
							$parser = new $interpreterClass();
						endif;
						// bring data from Interpreter to files php header ...
						if (intval(method_exists($parser,'getHeader'))==1):
							$header .= $parser->getHeader(trim($contentvalue), intval($mid_res['set'][0]['mid']));
                            $parser->closeInterpreterDB();
						endif;
						// bring function calls to body tag
						if (intval(method_exists($parser,'getBodyFunction'))==1):
							$bodyfunc .= $parser->getBodyFunction(trim($contentvalue), intval($mid_res['set'][0]['mid']));
                            $parser->closeInterpreterDB();
						endif;
						// bring data to files head ...
						if (intval(method_exists($parser,'getMetaScript'))==1):
							$metascript[] = $parser->getMetaScript(trim($contentvalue), intval($mid_res['set'][0]['mid']), 0, $lang);
                            $parser->closeInterpreterDB();
						endif;
						// finally parse real content
						if (intval(method_exists($parser,'getContent'))==1):
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

				if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
					error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : global contents finished ' . __LINE__ . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
				}

                // built final file data
				$tmpbuf = '';
				$onlyheader = false;
				$phpheader = '';
				// get some PHP header variables, if defined
				if ($_SESSION['wspvars']['usesession']==1 && $mode!="preview") {
					$phpheader.= "session_set_cookie_params(['lifetime' => 86400, 'path' => '/', 'domain' => \$_SERVER['HTTP_HOST'], 'secure' => true, 'httponly' => true, 'samesite' => 'lax']);\n";
					$phpheader.= "session_start();\n";
				}
				
				// get URL or VARIABLE based forwarding BEFORE doing other forwarding actions 
				$urlforward = '';
				$varforward = '';
				if ($isindex==1 && intval($mid_res['set'][0]['connected'])==0 && $mode!="preview") {
					// get URL based forwarding
					$urlforward_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` LIKE 'url_forward_%'";
					$urlforward_res = doSQL($urlforward_sql);
					if ($urlforward_res['num']>0) {
						$urlforward.= "/"."* ".$urlforward_res['num']." url based forwardings *"."/\n";
						foreach ($urlforward_res['set'] AS $ufresk => $ufresv) {
							$urlforwarddata = unserializeBroken(trim($ufresv['varvalue']));
							$targetdata = '';
							if (is_array($urlforwarddata)):
								$urlforward.= "if ((\$_SERVER['SERVER_NAME']=='".trim($urlforwarddata['url'])."' || \$_SERVER['SERVER_NAME']=='www.".trim($urlforwarddata['url'])."') && \$_SERVER['SERVER_NAME']!='".$_SESSION['wspvars']['liveurl']."'):\n";
								if (intval($urlforwarddata['rewrite'])==1):
									$targetdata.= "http://".$_SESSION['wspvars']['liveurl'];
								else:
									$targetdata.= "http://'".$urlforwarddata['url']."'";
								endif;
								// parsedir option
								if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
									$targetdata.= returnPath(intval($urlforwarddata['target']), 1);
								else:
									$targetdata.= returnPath(intval($urlforwarddata['target']), 2);
								endif;
								$urlforward.= "\tif('http://'.\$_SERVER['SERVER_NAME'].\$_SERVER['PHP_SELF']!='".$targetdata."'):\n";
								$urlforward.= "\t\theader('Location: ".$targetdata."');\n";
								$urlforward.= "\t\tdie();\n";
								$urlforward.= "\tendif;\n";
								$urlforward.= "endif;\n";
							endif;
							unset($urlforwarddata);
                        }
                    }
					// get VARIABLE based forwarding
					$varforward_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` LIKE 'var_forward_%'";
					$varforward_res = doSQL($varforward_sql);
					if ($varforward_res['num']>0) {
						$varforward.= "/"."* ".$varforward_num." var based forwardings *"."/\n";
						foreach ($varforward_res['set'] AS $vfresk => $vfresv) {
							$varforwarddata = unserializeBroken(trim($vfresv['varvalue']));
							$targetdata = '';
							if (is_array($varforwarddata)):
								if (trim($varforwarddata['varvalue'])!=""):
									$varforward.= "if(isset(\$_REQUEST['".trim($varforwarddata['varname'])."']) && \$_REQUEST['".trim($varforwarddata['varname'])."']=='".$varforwarddata['varvalue']."'):\n";
								else:
									$varforward.= "if(isset(\$_REQUEST['".trim($varforwarddata['varname'])."'])):\n";
								endif;
								$targetdata.= "http://'.\$_SERVER['SERVER_NAME'].'";
								if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
									$targetdata.= returnPath(intval($varforwarddata['target']), 1);
								else:
									$targetdata.= returnPath(intval($varforwarddata['target']), 2);
								endif;
								$varforward.= "\tif('http://'.\$_SERVER['SERVER_NAME'].\$_SERVER['PHP_SELF']!='".$targetdata."'):\n";
								$varforward.= "\t\theader('Location: ".$targetdata."');\n";
								$varforward.= "\t\tdie();\n";
								$varforward.= "\tendif;\n";
								$varforward.= "endif;\n";
							endif;
							unset($varforwarddata);
						}
					}
					// combine both forwarding options
					$phpheader.= $urlforward.$varforward."\n";
				}
				
				// use php based header forwarding, if $metapath not empty
				if (trim($metapath)!='' && $mode!="preview") {
					$onlyheader = true;
					if (trim($urlforward.$varforward)=='') {
						$phpheader = '';
					}
					$phpheader.= "header(\"HTTP/1.1 302 Found\");\n";
					$phpheader.= "header(\"location: ".$metapath."\");\n";
					$phpheader.= "die();\n";
				} else if (trim($metapath)!='' && $mode=="preview") {
					$_SESSION['previewforward'] = returnIntLang('preview forwarded to', false)." '".$metapath."'";
				} else {
					$_SESSION['previewforward'] = '';
				}

				// use globalvars include
				if ($onlyheader===false) {
					$phpheader.= str_replace("//", "/", str_replace("//", "/", "@include \$_SERVER['DOCUMENT_ROOT'].'/".$_SESSION['wspvars']['wspbasediradd']."/data/include/global.inc.php';\n"));
					// output parsedirectories property for dynamic link creation on page
					if (isset($_SESSION['wspvars']['publisherdata']['parsedirectories'])):
						$phpheader.= "\$wspvars['pagelinks'] = ".intval($_SESSION['wspvars']['publisherdata']['parsedirectories']).";\n";
						$phpheader.= "\$_SESSION['wsppage']['pagelinks'] = ".intval($_SESSION['wspvars']['publisherdata']['parsedirectories']).";\n";
					else:
						$phpheader.= "\$wspvars['pagelinks'] = 0;\n";
						$phpheader.= "\$_SESSION['wsppage']['pagelinks'] = 0;\n";
					endif;
					// output pagelang in file to use with localiced modules and contents
					$phpheader.= "\$wspvars['pagelang'] = '".trim($lang)."';\n";
					$phpheader.= "\$_SESSION['wsppage']['pagelang'] = '".trim($lang)."';\n";
					// output mid in file
					$phpheader.= "\$wspvars['mid'] = ".intval($pubid).";\n";
					$phpheader.= "\$_SESSION['wsppage']['mid'] = ".intval($pubid).";\n";
					// output mid tree in file
					$phpheader.= "\$wspvars['midtree'] = unserializeBroken(\"".serialize(returnIDTree($pubid))."\");\n";
					$phpheader.= "\$_SESSION['wsppage']['midtree'] = unserializeBroken(\"".serialize(returnIDTree($pubid))."\");\n";

					if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
						error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : getPrivacyMode ' . var_export(getPrivacyMode($pubid , $used_template), true) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
					}

					if (getPrivacyMode( $pubid , $used_template)) {	
						$blockfonts = ((int)doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'privacy_disablefonts'")===1);
						$blockstats = ((int)doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'privacy_disablestats'")===1);
						$phpheader.= str_replace("//", "/", str_replace("//", "/", "@include \$_SERVER['DOCUMENT_ROOT'].'/".$_SESSION['wspvars']['wspbasediradd']."/data/include/doprepareprivacy.inc.php';\n"));
						$phpheader.= "if (!isset(\$_COOKIE['cookieAccepted']) || intval(\$_COOKIE['cookieAccepted'])!=1) {\n";
						$phpheader.= $blockfonts ? "\t\$_SESSION['wsppage']['allowfonts'] == false;\n" : "" ;
						$phpheader.= $blockstats ? "\t\$_SESSION['wsppage']['allowstats'] == false;\n" : "" ;
						$phpheader.= "\t\$_SESSION['wsppage']['blockelem'] = [];\n";
						for ($e=0; $e<5; $e++) { 
							if (!empty(getWSPProperties('privacy_blockelement_' . $e . '_text'))) {
								$elemList = getWSPProperties('privacy_blockelement_' . $e . '_identifier');
								if ($elemList) {
									$elemList = explode(",", $elemList);
									foreach ($elemList AS $elemItem) {
										$phpheader.= "\t\$_SESSION['wsppage']['blockelem'][] = '" . trim($elemItem) ."';\n";
									}
								}
							}
						}
						$phpheader.= "\t\$_SESSION['wsppage']['blockelem'] = array_values(array_unique(\$_SESSION['wsppage']['blockelem']));\n";
						$phpheader.= "}\n";
					}

					// if menupoint should not be visible by day/time restriction or login ..
					if (intval($mid_res['set'][0]['login'])==1 && !($mode=="preview")) {
						$phpheader.= "if(!(array_key_exists('wsppage', \$_SESSION)) || (array_key_exists('wsppage', \$_SESSION) && !(array_key_exists('userlogin', \$_SESSION['wsppage']))) || (array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']!=true)): die('<em>Die Inhalte dieser Seite sind nicht mehr vorhanden oder der Zugang ist gesperrt. Wenn Sie sicher sind, dass hier ein Inhalt zu sehen sein sollte, kontaktieren Sie bitte unserer Webmaster.<br />This page is out of date or accessing this page is restricted. If you see this on a page you were awaiting contents for sure, please contact our webmaster.</em>'); endif;\n";
					}

					if ($content_num==0 && $content_num!=$realcontent_num):
						// the contents where hidden by lockpage preference
						if ($hiddenmenu==2 && $nocontentmenu==0):
							$phpheader.= "if(!(array_key_exists('wsppage', \$_SESSION)) || (array_key_exists('wsppage', \$_SESSION) && !(array_key_exists('userlogin', \$_SESSION['wsppage']))) || (array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']!=true)): die('<em>Die Inhalte dieser Seite sind nicht mehr vorhanden oder der Zugang ist gesperrt. Wenn Sie sicher sind, dass hier ein Inhalt zu sehen sein sollte, kontaktieren Sie bitte unserer Webmaster.<br />This page is out of date or accessing this page is restricted. If you see this on a page you were awaiting contents for sure, please contact our webmaster.</em>'); endif;\n";
						endif;
					elseif ($content_num==0):
						// there were really NO contents ;)
						if ($nocontentmenu==0):
							$phpheader.= "if(!(array_key_exists('wsppage', \$_SESSION)) || (array_key_exists('wsppage', \$_SESSION) && !(array_key_exists('userlogin', \$_SESSION['wsppage']))) || (array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']!=true)): die('<em>Die Inhalte dieser Seite sind nicht mehr vorhanden oder der Zugang ist gesperrt. Wenn Sie sicher sind, dass hier ein Inhalt zu sehen sein sollte, kontaktieren Sie bitte unserer Webmaster.<br />This page is out of date or accessing this page is restricted. If you see this on a page you were awaiting contents for sure, please contact our webmaster.</em>'); endif;\n";
						endif;
					endif;

					// clean paths used in php header
					if ($header!=""):
						$phpheader .= $header;
					endif;
					if ($phpheader!=""):
						$tmpbuf .= '<'.'?'.'php'."\n".$phpheader.'?'.'>';
					endif;
					unset($header);
					
					// create normalized header with title and metainformation
					if ($mode=="preview"):
						$headvar = getHeadVar(intval($mid_res['set'][0]['mid']), $lang, true, $metascript);
						$tmpbuf .= is_array($headvar)?$headvar[0]:$headvar;
					else:
						$headvar = getHeadVar(intval($mid_res['set'][0]['mid']), $lang, false, $metascript);
						if (is_array($headvar)) {
							$tmpbuf .= $headvar[0];
							$onlyheader = $headvar[1];
						} else {
							$tmpbuf .= $headvar;
						}
					endif;
					
					// publish the rest of the file only if no explizit forwarding was detected
					if ($onlyheader===false) {
						// if menupoint should not be visible by day/time restriction or login ..
						if (intval($mid_res['set'][0]['login'])==1 && !($mode=="preview")):
							// just dont insert templatecode for locked files or files that will not show anything ;)
							$tmpbuf .= "<!-- the content of this page is hidden and shall not be shown to anyone. if this text appears, something went wrong in php header -->";
						else:
							// inserting templatecode to file IF contents OR frame shall be displayed
							if (getPrivacyMode( $pubid , $used_template)) {	
								
								// getWSPProperties() !?!?!

								$tmpbuf.= "<style type='text/css'><!--\n";
								if (getWSPProperties('privacy_placement')==1):
									$tmpbuf.= ".cookiePolicy {position:fixed; bottom:0; left:0; width:100%; background: rgb(255,255,255); background: rgba(255,255,255,0.9); color:#000; z-index:999999; padding: 3vh 15%; box-sizing: border-box; margin:0;}\n";
								elseif (getWSPProperties('privacy_placement')==2):
									$tmpbuf.= ".cookiePolicy {}\n";
								else:
									$tmpbuf.= ".cookiePolicy {position:fixed; top:0; left:0; width:100%; background: rgb(255,255,255); background: rgba(255,255,255,0.9); color:#000; z-index:999999; padding: 3vh 15%; box-sizing: border-box; margin:0;}\n";
								endif;
								$tmpbuf.= "--></style>\n";

								$tmpbuf.= "<div id='cookiePolicy' class='cookiePolicy cookiePolicyCSS' style='display:none'>";
								$tmpbuf.= "<form id=\"cookieAcceptForm\" method=\"post\">";
								$tmpbuf.= "<p class=\"cookiePolicyText\">";
								if (trim(getWSPProperties('privacy_linktext'))!='') {
									$tmpbuf.= str_replace(trim(getWSPProperties('privacy_linktext')), "<a href='".returnInterpreterPath(intval(getWSPProperties('privacy_link')))."'>".trim(getWSPProperties('privacy_linktext'))."</a>", trim(getWSPProperties('privacy_text')));
								} else {
									$tmpbuf.= trim(getWSPProperties('privacy_text'));
								}
								$tmpbuf.= "</p>";

								$tmpbuf.= '<table class="cookiePolicyDisableTable">';
								if (intval(getWSPProperties('privacy_disabletech'))==1) {
									$tmpbuf.= "<tr><td class=\"cookiePolicyDisableTableDesc\"><p>Technisch notwendige Cookies</p></td>";
									$tmpbuf.= "<td class=\"cookiePolicyDisableTableButton\"><div class='form-check form-switch'><input class='form-check-input' type=\"checkbox\" name=\"allow_tech\" value=\"1\"";
									if (intval(getWSPProperties('privacy_disabletech_pref'))==0) {
										$tmpbuf.= " checked=\"checked\" ";
									} else if (intval(getWSPProperties('privacy_disabletech_pref'))==2) {
										$tmpbuf.= " checked=\"checked\" disabled=\"disabled\" ";
									} else if (intval(getWSPProperties('privacy_disabletech_pref'))==3) {
										$tmpbuf.= " disabled=\"disabled\" ";
									}
									$tmpbuf.= "/></div></td></tr>";
								}
								if (intval(getWSPProperties('privacy_disablestats'))==1) {
									$tmpbuf.= "<tr><td class=\"cookiePolicyDisableTableDesc\"><p>Statistik-Cookies</p></td>";
									$tmpbuf.= "<td class=\"cookiePolicyDisableTableButton\"><div class='form-check form-switch'><input class='form-check-input' type=\"checkbox\" name=\"allow_stats\" value=\"1\" ";
									if (intval(getWSPProperties('privacy_disablestats_pref'))==0) {
										$tmpbuf.= " checked=\"checked\" ";
									} else if (intval(getWSPProperties('privacy_disablestats_pref'))==2) {
										$tmpbuf.= " checked=\"checked\" disabled=\"disabled\" ";
									} else if (intval(getWSPProperties('privacy_disablestats_pref'))==3) {
										$tmpbuf.= " disabled=\"disabled\" ";
									}
									$tmpbuf.= "/></div></td></tr>";
								}
								if (intval(getWSPProperties('privacy_disablefonts'))==1) {
									$tmpbuf.= "<tr><td class=\"cookiePolicyDisableTableDesc\"><p>Externe Webfonts</p></td>";
									$tmpbuf.= "<td class=\"cookiePolicyDisableTableButton\"><div class='form-check form-switch'><input class='form-check-input' type=\"checkbox\" name=\"allow_fonts\" value=\"1\" ";
									if (intval(getWSPProperties('privacy_disablefonts_pref'))==0) {
										$tmpbuf.= " checked=\"checked\" ";
									} else if (intval(getWSPProperties('privacy_disablefonts_pref'))==2) {
										$tmpbuf.= " checked=\"checked\" disabled=\"disabled\" ";
									} else if (intval(getWSPProperties('privacy_disablefonts_pref'))==3) {
										$tmpbuf.= " disabled=\"disabled\" ";
									}
									$tmpbuf.= "/></div></td></tr>";
								}

								for ($e=0; $e<5; $e++) {
									if (!empty(getWSPProperties('privacy_blockelement_' . $e . '_text'))) {
										$tmpbuf.= "<tr><td class=\"cookiePolicyDisableTableDesc\"><p>". trim(getWSPProperties('privacy_blockelement_' . $e . '_text')) ."</p></td>";
										$tmpbuf.= "<td class=\"cookiePolicyDisableTableButton\"><div class='form-check form-switch'><input type=\"checkbox\" class=\"form-check-input\"  name=\"allow_block[]\" value=\"". getWSPProperties('privacy_blockelement_' . $e . '_identifier') ."\" ";
										if (intval(getWSPProperties('privacy_blockelement_' . $e . '_pref'))==0) {
											$tmpbuf.= " checked=\"checked\" ";
										} else if (intval(getWSPProperties('privacy_blockelement_' . $e . '_pref'))==2) {
											$tmpbuf.= " checked=\"checked\" disabled=\"disabled\" ";
										} else if (intval(getWSPProperties('privacy_blockelement_' . $e . '_pref'))==3) {
											$tmpbuf.= " disabled=\"disabled\" ";
										}
										$tmpbuf.= " /></div></td></tr>";
									}
								}
								$tmpbuf.= "</table>";						

								$tmpbuf.= "<p class=\"cookiePolicyButtons\"><a id='cookiePolicyAccept' class='cookiePolicyAcceptLink' style='cursor: pointer;'>".getWSPProperties('privacy_accept') ."</a> ";
								$tmpbuf.= "</p>";
								$tmpbuf.= "</form>";
								$tmpbuf.= "</div>";
								$tmpbuf.= "<script type='text/javascript' src='/data/script/447ce0a0-25ad-4967-82b1-405e31bf5bb4.js'></script>\n";
								$tmpbuf.= "<script>\n";
            
								$tmpbuf.= "$(document).ready(function(){\n";
								$tmpbuf.= "var cookieValue = getCookie('cookieAccepted');\n";
								$tmpbuf.= "if (cookieValue != 1){\n";
								$tmpbuf.= "  $('#cookiePolicy').fadeIn('slow');\n";
								$tmpbuf.= "  $('body').addClass('cookiepolicy');\n";
								$tmpbuf.= "}\n";
								$tmpbuf.= "$('#cookiePolicyAccept').bind('click', function() {\n";
								$tmpbuf.= "\t$('body').removeClass('cookiepolicy');\n";
								$tmpbuf.= "\tvar expiryDate = new Date();\n";
								$tmpbuf.= "\texpiryDate.setDate(expiryDate.getDate() + 5 * 365);\n";
								$tmpbuf.= "\tsetCookie('cookieAccepted', '1', expiryDate, '/');\n";
								$tmpbuf.= "\tdocument.getElementById('cookieAcceptForm').submit();\n";
								$tmpbuf.= "});\n";
								$tmpbuf.= "});\n";
								$tmpbuf.= "</script>\n\n";
							}
							$tmpbuf .= $templatecode;
						endif;
						
						// create footer
						$tmpbuf .= getFootVar();
					
						// replace selfvars with its contents
						foreach ($selfvardata as $key => $value) {
							if ($_SESSION['wspvars']['stripslashes']>0):
								for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
									$value = stripslashes($value);
								endfor;
							endif;
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
						$contentvars = getContentvars($tmpbuf, intval($mid_res['set'][0]['mid']));
						// replace wsp-content-vars with its contents
						foreach ($contentvars AS $key => $value) {
							// replace empty content vars on forwarding pages with a link
							if(!empty($metapath) && $mode=="preview" && trim($content[$key])==''):
								$content[$key] = returnIntLang('preview this page has no contents and is automatically forwarding to')." <a href='?previewid=".intval($forwardPage)."&previewlang=".$lang."'>".returnPath($forwardPage, 1, '', $lang)."</a>";
							endif;
							if (array_key_exists($key, $content)):
								$tmpbuf = str_replace($value, $content[$key], $tmpbuf);
							else:
								// replace empty content vars with ... EMPTY string 
								$tmpbuf = str_replace($value, '', $tmpbuf);
							endif;
						}
						
						// replace language placeholder
						$languages = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
						$langbuf = "";
						if (isset($_SESSION['wspvars']['publisherdata']['showlang']) && $_SESSION['wspvars']['publisherdata']['showlang']=="text") {
							if (count($languages['languages']['shortcut'])>0) {
								$langbuf.= "<ul class=\"langlist\">";
								foreach ($languages['languages']['shortcut'] AS $lkey => $lvalue):
									$l_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid_res['set'][0]['mid'])." AND `content_lang` = '".$languages['languages']['shortcut'][$lkey]."'";
									$l_res = doSQL($l_sql);
									if ($l_res['num']>0):
										$langbuf.= "<li class=\"langitem ".$languages['languages']['shortcut'][$lkey]." ";
										if ($languages['languages']['shortcut'][$lkey]==$lang):
											$langbuf.= " activelang ";
										else:
											$langbuf.= " inactivelang ";
										endif;
										$langbuf.= "\">";
										$langbuf.= "<a href=\"";
										if ($mode=="preview"):
											$langlink = "?previewid=".intval($mid_res['set'][0]['mid'])."&previewlang=".$languages['languages']['shortcut'][$lkey];
										else:
											if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
												if (intval($mid_res['set'][0]['isindex'])==1):
													if (intval($mid_res['set'][0]['level'])==1):
														$langlink = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 0, '', $languages['languages']['shortcut'][$lkey])."/"));
													else:
														$langlink = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 1, '', $languages['languages']['shortcut'][$lkey])."/"));
													endif;
												else:
													$langlink = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 1, '', $languages['languages']['shortcut'][$lkey])."/"));
												endif;
											else:
												if (intval($mid_res['set'][0]['isindex'])==1 && intval($mid_res['set'][0]['level'])==1):
													$langlink = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 0, '', $languages['languages']['shortcut'][$lkey])."/index.php"));
												else:
													$langlink = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 2, '', $languages['languages']['shortcut'][$lkey])));
												endif;
											endif;
										endif;
										$langbuf.= $langlink."\"";
										if ($languages['languages']['shortcut'][$lkey]==$lang):
											$langbuf.= " class=\"activelang\"";
										else:
											$langbuf.= " class=\"inactivelang\"";
										endif;
										$langbuf.= ">";
										$langbuf.= $languages['languages']['longname'][$lkey];
										$langbuf.= "</a>";
										$langbuf.= "</li>";
									endif;
								endforeach;
								$langbuf.= "</ul>";
							}
						}
						else if (isset($_SESSION['wspvars']['publisherdata']['showlang']) && $_SESSION['wspvars']['publisherdata']['showlang']=="icon") {
							if (count($languages['languages']['shortcut'])>0) {
								$langbuf.= "<ul class=\"langlist\">";
								foreach ($languages['languages']['shortcut'] AS $lkey => $lvalue):
									$l_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid_res['set'][0]['mid'])." AND `content_lang` = '".$languages['languages']['shortcut'][$lkey]."'";
									$l_res = doSQL($l_sql);
									if ($l_res['num']>0):
										$langbuf.= "<li class=\"langitem ".$languages['languages']['shortcut'][$lkey]." ";
										if ($languages['languages']['shortcut'][$lkey]==$lang):
											$langbuf.= " activelang ";
										else:
											$langbuf.= " inactivelang ";
										endif;
										$langbuf.= "\">";
										if ($languages['languages']['shortcut'][$lkey]=="de"):
											$langbuf.= "<a href=\"";
											$langlink = "/";
										else:
											$langbuf.= "<a href=\"";
											$langlink = "/".$languages['languages']['shortcut'][$lkey]."/";
										endif;
										$langlink.= returnPath(intval($mid_res['set'][0]['mid']), 2);
										$langbuf.= $langlink."\"";
										if ($languages['languages']['shortcut'][$lkey]==$lang):
											$langbuf.= " class=\"activelang\"";
										else:
											$langbuf.= " class=\"inactivelang\"";
										endif;
										
										$langbuf.= " title=\"".$languages['languages']['longname'][$lkey]."\"";
										$langbuf.= ">";
										$langbuf.= "<img src=\"/media/screen/lang/".$languages['languages']['shortcut'][$lkey].".png\" border=\"0\" alt=\"".$languages['languages']['longname'][$lkey]."\">";
										$langbuf.= "</a>";
										$langbuf.= "</li>";
									endif;
								endforeach;
								$langbuf.= "</ul>";
							}
						}
						else if (isset($_SESSION['wspvars']['publisherdata']['showlang']) && $_SESSION['wspvars']['publisherdata']['showlang']=="dropdown") {
							if (count($languages['languages']['shortcut'])>0) {
								$langbuf.= "<form name=\"switchpagelang\" id=\"switchpagelang\" method=\"post\"><select name=\"choosepagelang\" id=\"choosepagelang\" onchange=\"document.getElementById('switchpagelang').action = document.getElementById('choosepagelang').value; document.getElementById('switchpagelang').submit();\">";
								foreach ($languages['languages']['shortcut'] AS $lkey => $lvalue):
									$l_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid_res['set'][0]['mid'])." AND `content_lang` = '".$languages['languages']['shortcut'][$lkey]."'";
									$l_res = doSQL($l_sql);
									if ($l_res['num']>0):
										if ($languages['languages']['shortcut'][$lkey]=="de"):
											$langlink = "/";
										else:
											$langlink = "/".$languages['languages']['shortcut'][$lkey]."/";
										endif;
										$langlink.= returnPath(intval($mid_res['set'][0]['mid']), 2);
										$langbuf.= "<option value=\"".$langlink."\"";
										if ($languages['languages']['shortcut'][$lkey]==$lang):
											$langbuf.= " selected=\"selected\"";
										endif;
										$langbuf.= ">";
										$langbuf.= $languages['languages']['longname'][$lkey];
										$langbuf.= "</option>";
									endif;
								endforeach;
								$langbuf.= "</select></form>";
							}
						}

						if ((isset($_SESSION['wspvars']['publisherdata']['setoutputlang']) && $_SESSION['wspvars']['publisherdata']['setoutputlang']=='page') && count($languages['languages']['shortcut'])>1):
							$tmpbuf = str_replace("[%LANGUAGE%]", $langbuf, $tmpbuf);
						else:
							$tmpbuf = str_replace("[%LANGUAGE%]", '', $tmpbuf);
						endif;
						
						// replacing known wsp vars with values 
						// pagename
						$pagename = trim($mid_res['set'][0]['description']);
						$pagenamelng = !empty($mid_res['set'][0]['langdescription']) ? unserializeBroken(trim($mid_res['set'][0]['langdescription'])) : [];
						if (is_array($pagenamelng) && array_key_exists($lang, $pagenamelng)): $pagename = $pagenamelng[$lang]; endif;
						$tmpbuf = str_replace("[%PAGENAME%]", $pagename, $tmpbuf);
						$tmpbuf = str_replace("[%LASTPUBLISHED%]", date("Y-m-d H:i:s"), $tmpbuf);
						$tmpbuf = str_replace("[%PUBLISHTIME%]", sprintf("%0.6f ms", (floatval(microtime())-floatval(isset($parsetime)?$parsetime:0))), $tmpbuf);
						$tmpbuf = str_replace("[%FILEPATH%]", returnPath(intval($mid_res['set'][0]['mid']), 2, '', $lang), $tmpbuf);
						$tmpbuf = str_replace("[%FILEAUTHOR%]", stripslashes($_SESSION['wspvars']['realname']), $tmpbuf);
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
									$shortcut = buildMenu(array(array('TYPE' => 'SHORTCUT', 'MENU.SHOW' => intval($scresv['mid']))), 0, intval($scresv['mid']), intval($scresv['mid']), $lang, 0, 1, false, true);
								else:
									$shortcut = buildMenu(array(array('TYPE' => 'SHORTCUT', 'MENU.SHOW' => intval($scresv['mid']))), 0, intval($scresv['mid']), intval($scresv['mid']), $lang, 0, 1, false, false);
								endif;
								$tmpbuf = str_replace("[%".strtoupper(trim($scresv['linktoshortcut']))."%]", $shortcut['menucode'], $tmpbuf);
							}
						}
					}
				} 
				else {
					$tmpbuf.= '<'.'?'.'php'."\n".$phpheader."\n".'?'.'>';
				}

				if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
					error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : data created ' . __LINE__ . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
				}

				// replace ALL detected vars with contents or empty strings ...
				// MISSING!!!!!
                	
				// set content to utf8
				$tmpbuf = setUTF8($tmpbuf);
				// FINALLY ............
				// write output to file
				if ($mode!="preview" && (!empty($ftp) || $usedirect)) {

					if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
						error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : write data ' . __LINE__ . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
					}

					// define temp directory
					$tmppath = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/";
					// define temp filename
					$tmpfile = tempnam($tmppath, 'wsp');
					// create temporary buffer file with file contents
					$fh = fopen($tmpfile, "r+");
					// write contents
					fwrite($fh, $tmpbuf);
					// close buffer
					fclose($fh);
					// creating final file
					if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {

						if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
							error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : parsedir ' . __LINE__ . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
							error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : createPath ' . returnPath(intval($mid_res['set'][0]['mid'])) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
						}

						// if setup is to create directories instead of files
						// create ftp-based path for missing directories
						// call 1-function because structure is needed down to a directory
						if (!is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'] . '/'. $_SESSION['wspvars']['wspbasediradd']. '/' . returnPath(intval($mid_res['set'][0]['mid']), 1, '', $lang))))) {
							if (!empty($ftp)) {
								createFTPPath($ftp, returnPath(intval($mid_res['set'][0]['mid']), 1, '', $lang));
							} else {
								if (!mkdir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'] . '/'. $_SESSION['wspvars']['wspbasediradd']. '/' . returnPath(intval($mid_res['set'][0]['mid']), 1, '', $lang))), 0755, true)) {
									addWSPMsg('errormsg', sprintf(returnIntLang("the directory %s could not be created for unknown reason", false), returnPath(intval($mid_res['set'][0]['mid']), 1, '', $lang)));
								}
							}
						}

						// creating index.php
						if (intval($mid_res['set'][0]['isindex'])==1) {
							// runs only if the file is the index of this directory
							// first level
							if (intval($mid_res['set'][0]['level'])==1) {
								// SET filename to index.php
								$ftppath = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 0, '', $lang)."/index.php"));
								// ftp copy function

								if (!empty($ftp) && ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$ftppath), $tmpfile, FTP_BINARY)) {
									$returnstat = true;
								} else {
									$returnstat = false;
								}

								if (!$returnstat && copy($tmpfile, $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$ftppath)) {
									$returnstat = true;
								}

								if (!$returnstat) {
									addWSPMsg('errormsg', "<p>ERROR ".__LINE__.": ".returnIntLang('publisher file could not be created1', false)." \"".$ftppath."\" ".returnIntLang('publisher file could not be created2', false)."</p>");
								} else {
									doSQL("UPDATE `menu` SET `contentchanged` = 0 WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
								}

								$ftppath = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 1, '', $lang)."/index.php"));

								if (!empty($ftp) && ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$ftppath), $tmpfile, FTP_BINARY)) {
									$returnstat = true;
								} else {
									$returnstat = false;
								}

								if (!$returnstat && copy($tmpfile, $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$ftppath)) {
									$returnstat = true;
								}

								if (!$returnstat) {
									addWSPMsg('errormsg', "<p>ERROR ".__LINE__.": ".returnIntLang('publisher file could not be created1', false)." \"".$ftppath."\" ".returnIntLang('publisher file could not be created2', false)."</p>");
								} else {
									doSQL("UPDATE `menu` SET `contentchanged` = 0 WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
								}

								// remove old file
								unlink($tmpfile);
							
							} else {

								// create the 'real' file 
								$ftppath = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 1, '', $lang)."/index.php"));

								// ftp copy function
								if (!empty($ftp) && ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$ftppath), $tmpfile, FTP_BINARY)) {
									$returnstat = true;
								} else {
									$returnstat = false;
								}

								if (!$returnstat && copy($tmpfile, $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$ftppath)) {
									$returnstat = true;
								}

								if (!$returnstat) {
									addWSPMsg('errormsg', "<p>ERROR ".__LINE__.": ".returnIntLang('publisher file could not be created1', false)." \"".$ftppath."\" ".returnIntLang('publisher file could not be created2', false)."</p>");
								} else {
									doSQL("UPDATE `menu` SET `contentchanged` = 0 WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
								}
								unlink($tmpfile);
							}
						} else {
							// create the content of the forwarding file
							// define temp filename
							$fwdfile = tempnam($tmppath, 'fwd');
							$fwdtmp = fopen($fwdfile, 'r+');
							fwrite($fwdtmp, '<'.'?'.'php header("location: '.trim($mid_res['set'][0]['filename']).'/"); '.'?'.'>');
							fclose($fwdtmp);
							// create path with structure name as directory name and the index.php file to get requested by just the directory call 
							$ftppath = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 1, '', $lang)."/index.php"));
							$fwdpath = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 2, '', $lang)));
							// ftp copy function
							if (!empty($ftp) && ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$ftppath), $tmpfile, FTP_BINARY)) {
								$returnstat = true;
							} else {
								$returnstat = false;
							}
							
							if (!$returnstat && copy($tmpfile, $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$ftppath)) {
								$returnstat = true;
							}

							if (!$returnstat) {
								addWSPMsg('errormsg', "<p>ERROR ".__LINE__.": ".returnIntLang('publisher file could not be created1', false)." \"".$ftppath."\" ".returnIntLang('publisher file could not be created2', false)."</p>");
							} else {
								doSQL("UPDATE `menu` SET `contentchanged` = 0 WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
							}

							// create the FORWARDING file now
							if (!empty($ftp) && ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$fwdpath), $fwdfile, FTP_BINARY)) {
								$returnstat = true;
							} else {
								$returnstat = false;
							}
							// alternative -> direct
							if (!$returnstat && copy($fwdfile, $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fwdpath)) {
								$returnstat = true;
							}

							if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
								error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : ftpcopy ' . var_export($returnstat, true) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
							}

							unlink($tmpfile);
						}

                    } else {

						if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
							error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : parsefile ' . __LINE__ . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
						}

						// create ftp-based path for missing directories
						// only call 0-function because the file is located directly to this directory
						createFTPPath($ftp, returnPath(intval($mid_res['set'][0]['mid']), 0, '', $lang));
						// if setup is to create files
						if (intval($mid_res['set'][0]['isindex'])==1 && intval($mid_res['set'][0]['level'])==1) {
							// FIRST - create a file with regular name
							$ftppath = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 2, '', $lang)));
							if ($usedirect) {
								if (!(copy( $tmpfile , str_replace("//" , "/" , $_SERVER['DOCUMENT_ROOT']."/".$ftppath)))) {
									addWSPMsg('errormsg', "<p>".returnIntLang('publisher file could not be written directly1', false)." \"".$ftppath."\" ".returnIntLang('publisher file as index', false)." ".returnIntLang('publisher file could not be written directly2', false)."</p>");
									$returnstat = false;
								}
								else {
									doSQL("UPDATE `menu` SET `contentchanged` = 0 WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
									$returnstat = true;
								}
							}
							else if (!ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$ftppath), $tmpfile, FTP_BINARY)):
								addWSPMsg('errormsg', "<p>".returnIntLang('publisher file could not be created1', false)." \"".$ftppath."\" ".returnIntLang('publisher file as index', false)." ".returnIntLang('publisher file could not be created2', false)."</p>");
							endif;
							// THIS is the root (index) file, so filename has to be set to index.php for a second round
							$ftppath = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 0, '', $lang)."/index.php"));
							// ftp copy function
							if (!ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$ftppath), $tmpfile, FTP_BINARY)):
								addWSPMsg('errormsg', "<p>".returnIntLang('publisher file could not be created1', false)." \"".$ftppath."\" ".returnIntLang('publisher file could not be created1', false)."</p>");
								$returnstat = false;
							else:
								doSQL("UPDATE `menu` SET `contentchanged` = 0 WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
								$returnstat = true;
							endif;
							unlink($tmpfile);
						} else {
							$ftppath = str_replace("//", "/", str_replace("//", "/", returnPath(intval($mid_res['set'][0]['mid']), 2, '', $lang)));
							// ftp copy function

							if (!empty($ftp) && ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$ftppath), $tmpfile, FTP_BINARY)) {
								$returnstat = true;
							} else {
								$returnstat = false;
							}
							// alternative -> direct
							if (!$returnstat && copy($tmpfile, $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$ftppath)) {
								$returnstat = true;
							}

							if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
								error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : ftpcopy ' . var_export($returnstat, true) . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
							}

							if (!$returnstat) {
								addWSPMsg('errormsg', "<p>".returnIntLang('publisher file could not be created1', false)." \"".$ftppath."\" ".returnIntLang('publisher file could not be created1', false)."</p>");
							} else {
								doSQL("UPDATE `menu` SET `contentchanged` = 0 WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
							}

							unlink($tmpfile);
						}
					}
					if ($ftp) {
						ftp_close($ftp);
					}
				}
			} else {
				$returnstat = "notemplatefound";
				addWSPMsg('errormsg', "<em>Fehler 2.".$used_template."</em>: Template zu Punkt \"".trim($mid_res['set'][0]['description'])."\" nicht gefunden.");
			}
		}
	}
	else {
		$returnstat = "nomenufound";
		addWSPMsg('errormsg', "<em>Fehler 1.".$pubid."</em>: Men&uuml;punkt nicht gefunden.");
	}
	
	if (isset($_SESSION['wspvars']['logdevmsg']) && $_SESSION['wspvars']['logdevmsg']) {
		error_log(date('Y-m-d H:i:s') . ' fileparser : publishSites : return ' . __LINE__ . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
		error_log(date('Y-m-d H:i:s') . ' fileparser : end ––––––––––' . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/' . $_SESSION['wspvars']['wspbasedir'] . '/tmp/wsp.log');
	}

	if ($mode!="preview") {
		if($newendmenu) {
            $drop_res = doSQL("DROP TABLE IF EXISTS `end_menu`");
            $create_res = doSQL("CREATE TABLE `end_menu` LIKE `menu`");
            if (!($create_res['res'])) { addWSPMsg('errormsg', 'publisher creation of end menu table failed'); }
            $insert_res = doSQL("INSERT INTO `end_menu` SELECT * FROM `menu`");
            if (!($insert_res['res'])) { addWSPMsg('errormsg', 'publisher adding data to end menu table failed'); }
		}
	}
	
    if ($mode=="preview") {
		$tmppath = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/";
		// define temp filename
		$tmpfile = $tmppath."previewfile.php";
		$fh = fopen($tmpfile, "w+");
		$wr = fwrite($fh, $tmpbuf);
		fclose($fh);
	} else {
		return $returnstat;
	}
}

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
			$parsetime = microtime();
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
			
			doSQL("UPDATE `menu` SET `contentchanged` = ".$cchange." WHERE `mid` = ".intval($mid_res['set'][0]['mid']));
			$returnstat = true;
			
			if($newendmenu):
				$drop_res = doSQL("DROP TABLE IF EXISTS `end_menu`");
				$create_res = doSQL("CREATE TABLE `end_menu` LIKE `menu`");
				if (!($create_res['res'])) { addWSPMsg('errormsg', 'publisher creation of end menu table failed'); }
                $insert_res = doSQL("INSERT INTO `end_menu` SELECT * FROM `menu`");
                if (!($insert_res['res'])) { addWSPMsg('errormsg', 'publisher adding data to end menu table failed'); }
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

// EOF ?>