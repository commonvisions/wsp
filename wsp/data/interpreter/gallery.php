<?php
$interpreterClass = "gallery";

if (!class_exists("gallery")) {
	class gallery extends clsInterpreter {
    	
        const title = "Bildergalerie";	

        var $title = "Bildergalerie";
		var $classname = "";
		var $parsefile = "gallery.php";
		var $version = "6.26.11";
		var $guid = "57a40fe7-4a53-129e-134b-7ecc0828adf3";
		var $phpvars = false;
		var $bodyfunc = false;
		var $metascript = true;
		var $multilang = array('de');
		var $flexible = true;
		
        function init() {

        }

		/**
		* Titel des Contents ermitteln
		*/
		function getView($value, $mid, $cid) {
			
			if (($value['display'] ?? '')=="dispoverview"):
				return "&Uuml;bersicht ausgewählter Galerien";
			elseif (($value['display'] ?? '')=="dispgallery"):
				return "Darstellung ausgew&auml;hlte Bildergalerie";
			else:
				return "Kein Darstellungsmodus gew&auml;hlt";
			endif;
			
			}	// getView()

		/**
		* Inhalt bearbeiten
		*/
		function getEdit($value, $mid, $cid) {
			ob_start();
			
			if (($value['display'] ?? '')==""):
				$value['display'] = "dispoverview";
			endif;
			
			?>
			<script language="JavaScript" type="text/javascript">
			<!--
		
			function submitForm() {
				return true;
				}
			
			function showHide(shFieldset) {
				document.getElementById('fieldset_dispgallery').style.display = 'none';
				document.getElementById('fieldset_dispoverview').style.display = 'none';
				document.getElementById('fieldset_' + shFieldset).style.display = 'block';
				return true;
				}
			
			// -->
			</script>
            <?php
                    
            // get all folders from /data/modules/gallery
            $folders = array();
            $displays = array();
            if (is_dir($_SERVER['DOCUMENT_ROOT']."/data/modules/gallery/")) {
                $showdir = dir($_SERVER['DOCUMENT_ROOT']."/data/modules/gallery/");
                while (false !== ($entries = $showdir->read())):
                    if (substr($entries, 0, 1) != '.'):
                        if (is_dir($_SERVER['DOCUMENT_ROOT']."/data/modules/gallery/".$entries)):
                            if (is_file($_SERVER['DOCUMENT_ROOT']."/data/modules/gallery/".$entries."/folders.inc.php")):
                                $folders[] = $entries;
                            endif;
                            if (is_file($_SERVER['DOCUMENT_ROOT']."/data/modules/gallery/".$entries."/images.inc.php")):
                                $displays[] = $entries;
                            endif;
                        endif;
                    endif;
                endwhile;
                sort($folders);
                sort($displays);
                
                $gallery_sql = "SELECT * FROM `f12bec9f-ea83-5fa5-b618-a114f569c7bd_1` WHERE `ignore` != 1 ORDER BY `gallery`";
                $gallery_res = doSQL($gallery_sql);
                $gallery_num = intval($gallery_res['num']);
            
                ?>
                <fieldset>
                    <legend>Konfiguration</legend>
                    <div id="fieldset_content">
                        <table class="tablelist">
                            <tr>
                                <td class="tablecell two head">Darstellungsmodus</td>
                                <td class="tablecell six head"><select name="field[display]" id="field_display" size="1" onchange="showHide(this.value);">
                                    <option value="dispoverview" <?php if ($value['display']=="dispoverview"): echo "selected=\"selected\""; endif; ?>>&Uuml;bersicht ausgewählter Galerien</option>
                                    <option value="dispgallery" <?php if ($value['display']=="dispgallery"): echo "selected=\"selected\""; endif; ?>>Darstellung ausgew&auml;hlte Bildergalerie</option>
                                </select></td>
                            </tr>
                        </table>
                        
                        <div id="fieldset_dispoverview" <?php if($value['display']=="dispgallery") echo "style=\"display: none;\""; ?>><table class="tablelist">
                            <tr>
                                <td class="tablecell two">Darstellungs-Plugin</td>
                                <td class="tablecell six"><?php
                                
                                if (!(is_array($folders)) || count($folders)==0): 
                                    echo "<em>Kein Plugin installiert</em><input type=\"hidden\" name=\"field[overviewplugin]\" value=\"\" />";
                                else:
                                    echo "<select name=\"field[overviewplugin]\">";
                                    foreach ($folders AS $fkey => $fvalue):
                                        echo "<option value=\"".str_replace("//", "/", str_replace("//", "/", "/data/modules/gallery/".trim($fvalue)."/folders.inc.php"))."\" ";
                                        if ($value['overviewplugin']==str_replace("//", "/", str_replace("//", "/", "/data/modules/gallery/".trim($fvalue)."/folders.inc.php"))):
                                            echo "selected=\"selected\"";
                                        endif;
                                        echo ">".ucfirst(trim($fvalue))."</option>";
                                    endforeach;
                                    echo "</select>";
                                endif;
                                
                                ?></td>
                            </tr>
                            <?php if($gallery_num>0): for($a=0;$a<$gallery_num;$a++): ?>
                                <tr>
                                    <td class="tablecell two"><?php if ($a==0): ?>Galerien ausw&auml;hlen<?php endif; ?></td>
                                    <td class="tablecell two"><input type="checkbox" name="field[showgals][<?php echo intval($a); ?>]" value="<?php echo intval($gallery_res['set'][$a]["id"]); ?>" <?php if(in_array(intval($gallery_res['set'][$a]["id"]), $value['showgals'])) echo ' checked="checked" '; ?> /> <?php echo trim($gallery_res['set'][$a]["gallery"]); ?></td>
                                    <td class="tablecell two">Ziel zum Aufruf</td>
                                    <td class="tablecell two"><select name="field[showtargets][<?php echo intval($a); ?>]" size="1" class="one full">
                                        <?php 
                                        if (intval($value['showtargets'][$a])>0):
                                            $selectpage = intval($value['showtargets'][$a]);
                                        else:
                                            $selectpage = intval($_SESSION['pathmid']);
                                        endif;
                                        getMenuLevel(0, 0, 1, array($selectpage)); ?>
                                    </select></td>
                                </tr>
                            <?php endfor; else: ?>
                            <tr>
                                <td class="tablecell two">Galerien ausw&auml;hlen</td>
                                <td class="tablecell six">Keine Galerien vorhanden.<input name="field[showgals]" id="field_showgals" type="hidden" value="" /></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="tablecell two">Max. Bildhöhe</td>
                                <td class="tablecell two"><input type="text" name="field[galheight]" value="<?php echo intval($value['galheight']); ?>" /> px</td>
                                <td class="tablecell two">Max. Bildbreite</td>
                                <td class="tablecell two"><input type="text" name="field[galwidth]" value="<?php echo intval($value['galwidth']); ?>" /> px</td>
                            </tr>
                            <tr>
                                <td class="tablecell two">Max. Iconhöhe</td>
                                <td class="tablecell two"><input type="text" name="field[galiconheight]" value="<?php echo intval($value['galiconheight']); ?>" /> px</td>
                                <td class="tablecell two">Max. Iconbreite</td>
                                <td class="tablecell two"><input type="text" name="field[galiconwidth]" value="<?php echo intval($value['galiconwidth']); ?>" /> px</td>
                            </tr>
                        </table></div>
                        
                        <div id="fieldset_dispgallery" <?php if($value['display']!="dispgallery") echo "style=\"display: none;\""; ?>>
                        <table class="tablelist">
                            <tr>
                                <td class="tablecell two">Galerie ausw&auml;hlen</td>
                                <td class="tablecell two"><?php
                    
                                if($gallery_num>0):
                                    echo "<select name=\"field[desc]\" id=\"field_desc\" size=\"1\" class='one full'>";
                                    echo "<option value='0'>Nach Aufruf</option>";
                                    for($a=0;$a<$gallery_num;$a++):
                                        ?>
                                        <option value="<?php echo intval($gallery_res['set'][$a]["id"]); ?>" <?php if($value['desc']==intval($gallery_res['set'][$a]["id"])): echo "selected=\"selected\""; endif; ?>><?php echo trim($gallery_res['set'][$a]["gallery"]); ?></option>
                                        <?php
                                    endfor;
                                    echo "</select>";
                                else:
                                    echo "Keine Galerien vorhanden.<input name=\"field[desc]\" id=\"field_desc\" type=\"hidden\" value=\"\">";
                                endif;
                                ?>
                                </td>
                                <td class="tablecell two">Darstellungs-Plugin</td>
                                <td class="tablecell two"><?php
                                
                                if (!(is_array($displays)) || count($displays)==0): 
                                    echo "<em>Kein Plugin installiert<input type=\"hidden\" name=\"field[plugin]\" value=\"\" /></em>";
                                else:
                                    echo "<select name=\"field[plugin]\" class=\"one full\">";
                                    foreach ($displays AS $dkey => $dvalue):
                                        echo "<option value=\"".str_replace("//", "/", str_replace("//", "/", "/data/modules/gallery/".trim($dvalue)."/images.inc.php"))."\" ";
                                        if ($value['plugin']==str_replace("//", "/", str_replace("//", "/", "/data/modules/gallery/".trim($dvalue)."/images.inc.php"))):
                                            echo "selected=\"selected\"";
                                        endif;
                                        echo ">".ucfirst(trim($dvalue))."</option>";
                                    endforeach;
                                    echo "</select>";
                                endif;
                                
                                ?></td>
                            </tr>
                        </table>
                        
                        <table class="tablelist">
                            <tr>
                                <td class="tablecell two">Thumbnails präferieren</td>
                                <td class="tablecell two"><input type="hidden" name="field[iconshow]" value="0" /><input type="checkbox" name="field[iconshow]" value="1" <?php if(intval($value['iconshow'])==1) echo "checked=\"checked\""; ?> />&nbsp;</td>
                                <td class="tablecell two">Originale anbieten</td>
                                <td class="tablecell two"><input type="hidden" name="field[showoriginals]" value="0" /><input type="checkbox" name="field[showoriginals]" value="1" <?php if(intval($value['showoriginals'])==1) echo "checked=\"checked\""; ?> />&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="tablecell eight head">Darstellungsoptionen (optional)</td>
                            </tr>
                            <tr>
                                <td class="tablecell two">Max. Bildhöhe px</td>
                                <td class="tablecell two"><input type="text" name="field[height]" value="<?php echo intval($value['height']); ?>" placeholder="Pixel" /></td>
                                <td class="tablecell two">Max. Bildbreite px</td>
                                <td class="tablecell two"><input type="text" name="field[width]" value="<?php echo intval($value['width']); ?>" placeholder="Pixel" /></td>
                            </tr>
                            <tr>
                                <td class="tablecell two">Max. Iconhöhe px</td>
                                <td class="tablecell two"><input type="text" name="field[iconheight]" value="<?php echo intval($value['iconheight']); ?>" placeholder="Pixel" /></td>
                                <td class="tablecell two">Max. Iconbreite px</td>
                                <td class="tablecell two"><input type="text" name="field[iconwidth]" value="<?php echo intval($value['iconwidth']); ?>" placeholder="Pixel" /></td>
                            </tr>
                            <tr>
                                <td class="tablecell two">Eigene Parameter</td>
                                <td class="tablecell six"><textarea name="field[param]" class="medium full noresize" placeholder="name:wert"><?php echo $value['param']; ?></textarea></td>
                            </tr>
                        </table>
                        </div>
                        
                    </div>
                </fieldset>
                
                <?php
                } else {
                    echo '<fieldset><p>Das Dateiverzeichnis konnte nicht gefunden werden. Bitte installieren sie das Modul erneut oder aktualisieren Sie die Version.</p></fieldset>';
                }
				
			$edit = ob_get_contents();
			ob_end_clean();

			return $edit;
		}	// getEdit()
			
		// parse contents
		function getContent($value, $mid, $cid, $publishlang) {
			$value = unserializeBroken($value);
			$parser = "<?php\n";
			$parser.= "// generic facts\n";
			$parser.= "\$gallery = array();\n";

            if ($value['display']=='dispoverview') {
				// darstellung von uebersichten
				$parser.= "\$gallery['overviewplugin'] = '".$value['overviewplugin']."';\n";	 // '
				$parser.= "\$gallery['folders'] = array();\n";

				if (is_array($value['showgals']) && count($value['showgals'])>0) {
					foreach ($value['showgals'] as $skey => $svalue) {
						if (intval($svalue)>0) {
							$gal_sql = "SELECT * FROM `f12bec9f-ea83-5fa5-b618-a114f569c7bd_1` WHERE `id` = ".intval($svalue);
							$gal_res = doSQL($gal_sql);
                            $gal_num = intval($gal_res['num']);
							if ($gal_num>0) {
								$parser.= "\$gallery['folders'][".intval($svalue)."] = array();\n";
								$parser.= "\$gallery['folders'][".intval($svalue)."]['name'] = '".trim($gal_res['set'][0]['gallery'])."';\n";	// '
								$parser.= "\$gallery['folders'][".intval($svalue)."]['target'] = '".returnInterpreterPath(intval($value['showtargets'][intval($skey)]))."';\n"; //'
								$img_sql = "SELECT * FROM `f12bec9f-ea83-5fa5-b618-a114f569c7bd_2` AS `img` WHERE `img`.`gallery` = ".intval($svalue)." AND `img`.`show` = 1 ORDER BY `img`.`position`";
								$img_res = doSQL($img_sql);
								$img_num = intval($img_res['num']);
								if ($img_num>0) {
									$basepath = "/".substr(trim($gal_res['set'][0]['path']),8)."/".trim($img_res['set'][0]['image']);
									$parser.= str_replace("//", "/", str_replace("//", "/", "\$gallery['folders'][".intval($svalue)."]['preview'] = '/gallery".$basepath."';\n"));
									if (is_file($_SERVER['DOCUMENT_ROOT']."/media/images/thumbs/gallery".$basepath)) {
										$parser.= str_replace("//", "/", str_replace("//", "/", "\$gallery['folders'][".intval($svalue)."]['preview'] = '/thumbs/gallery".$basepath."';\n"));
									}
									$parser.= "\$gallery['folders'][".intval($svalue)."]['imgnum'] = ".intval($img_num).";\n";
								}
							}
						}
					}
				}

				$parser.= "\$gallery['galheight'] = ".intval($value['galheight']).";\n";
				$parser.= "\$gallery['galwidth'] = ".intval($value['galwidth']).";\n";
				$parser.= "\$gallery['galiconheight'] = ".intval($value['galiconheight']).";\n";
				$parser.= "\$gallery['galiconwidth'] = ".intval($value['galiconwidth']).";\n";
				$parser.= "\$gallery['galid'] = '".md5($basepath.microtime())."';\n";
				$parser.= "// gallery include\n";
				$parser.= "include (DOCUMENT_ROOT.\"/data/modules/gallery/folders.inc.php\");\n";
            }
			else if ($value['display']=='dispgallery') {
				// darstellung der eigentlichen galerien
				$parser.= "\$gallery['images'] = array();\n";
				if (intval($value['desc'])>0) {
					$img_sql = "SELECT * FROM `f12bec9f-ea83-5fa5-b618-a114f569c7bd_1` AS `gal`, `f12bec9f-ea83-5fa5-b618-a114f569c7bd_2` AS `img` WHERE `img`.`gallery` = ".intval($value['desc'])." AND `img`.`show` = 1 AND `img`.`gallery` = `gal`.`id` ORDER BY `img`.`position`";
					$img_res = doSQL($img_sql);
                    $img_num = intval($img_res['num']);
					if ($img_num>0) {
						$i = 0;
						$parser.= "\$gallery['imagenum'] = '".$img_num."';\n";
						for ($ires=0; $ires<$img_num; $ires++) {
							$basepath = trim($img_res['set'][$ires]['path'])."/".trim($img_res['set'][$ires]['image']);
							if (is_file($_SERVER['DOCUMENT_ROOT']."/media/images/".$basepath)) {
								$parser.= "\$gallery['images'][".$i."]['file'] = '".$basepath."';\n";
								if (is_file($_SERVER['DOCUMENT_ROOT']."/media/images/thumbs".$basepath)) {
									$parser.= "\$gallery['images'][".$i."]['icon'] = '/thumbs".$basepath."';\n";
                                }
								else {
									$parser.= "\$gallery['images'][".$i."]['icon'] = '';\n";
								}
								$parser.= "\$gallery['images'][".$i."]['desc'] = '".str_replace("\"", "”", str_replace("'", "’", stripslashes(stripslashes(stripslashes(trim($img_res['set'][$ires]['desc']))))))."';\n"; //'
								$parser.= "\$gallery['images'][".$i."]['titl'] = '".str_replace("\"", "”", str_replace("'", "’", stripslashes(stripslashes(stripslashes(trim($img_res['set'][$ires]['title']))))))."';\n"; //'
								$i++;
							}
						}			
					}
					$parser.= "\$gallery['mode'] = 'setup';\n";
                }
				else {
					$img_sql = "SELECT * FROM `f12bec9f-ea83-5fa5-b618-a114f569c7bd_1` AS `gal`, `f12bec9f-ea83-5fa5-b618-a114f569c7bd_2` AS `img` WHERE `img`.`show` = 1 AND `img`.`gallery` = `gal`.`id` ORDER BY `img`.`gallery`, `img`.`position`";
					$img_res = doSQL($img_sql);
                    $img_num = intval($img_res['num']);
					if ($img_num>0) {
						$i = 0;
						for ($ires=0; $ires<$img_num; $ires++) {
							$basepath = trim($img_res['set'][$ires]['path'])."/".trim($img_res['set'][$ires]['image']);
							if (is_file($_SERVER['DOCUMENT_ROOT']."/media/images/".$basepath)) {
								$parser.= "\$gallery['images'][".intval($img_res['set'][$ires]['id'])."][".$i."]['file'] = '".$basepath."';\n";
								if (is_file($_SERVER['DOCUMENT_ROOT']."/media/images/thumbs".$basepath)) {
									$parser.= "\$gallery['images'][".intval($img_res['set'][$ires]['id'])."][".$i."]['icon'] = '/thumbs".$basepath."';\n";
                                }
                                else {
									$parser.= "\$gallery['images'][".intval($img_res['set'][$ires]['id'])."][".$i."]['icon'] = '';\n";
                                }
								$parser.= "\$gallery['images'][".intval($img_res['set'][$ires]['id'])."][".$i."]['desc'] = '".str_replace("\"", "”", str_replace("'", "’", stripslashes(stripslashes(stripslashes(trim($img_res['set'][$ires]['desc']))))))."';\n"; //'
								$parser.= "\$gallery['images'][".intval($img_res['set'][$ires]['id'])."][".$i."]['titl'] = '".str_replace("\"", "”", str_replace("'", "’", stripslashes(stripslashes(stripslashes(trim($img_res['set'][$ires]['title']))))))."';\n"; //'
								$i++;
							}
						}
					}
					$parser.= "\$gallery['mode'] = 'call';\n";
				}
				$parser.= "\$gallery['plugin'] = '".$value['plugin']."';\n";
				$parser.= "\$gallery['height'] = ".intval($value['height']).";\n";
				$parser.= "\$gallery['width'] = ".intval($value['width']).";\n";
				$parser.= "\$gallery['iconshow'] = ".intval($value['iconshow']).";\n";
				$parser.= "\$gallery['iconheight'] = ".intval($value['iconheight']).";\n";
				$parser.= "\$gallery['iconwidth'] = ".intval($value['iconwidth']).";\n";
				$parser.= "\$gallery['galid'] = '".md5($basepath.microtime())."';\n";
				$parser.= "\$gallery['showoriginals'] = '".intval($value['showoriginals'])."';\n";
				$parser.= "// gallery include\n";
				$parser.= "include (DOCUMENT_ROOT.\"/data/modules/gallery/images.inc.php\");\n";
			}
			
			$parser.= "?>\n";
			return $parser;
		}
	}	// class gallery
}	// if
