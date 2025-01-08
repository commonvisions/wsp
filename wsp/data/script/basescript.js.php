<?php
/**
 * 
 * @author info@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8
 * @lastchange 2018-09-18
 */
header( 'Cache-Control: no-store, no-cache, must-revalidate' ); 
header( 'Cache-Control: post-check=0, pre-check=0', false ); 
header( 'Pragma: no-cache' );
header("Expires: Sat, 7 Apr 2018 05:00:00 GMT");
header("content-type: application/javascript");
?>
function showQuality(fieldname, bestlength, maxlength) { var stringlength = document.getElementById(fieldname).value.length;var qualstat = 1;document.getElementById('show_'+fieldname+'_length').innerHTML=stringlength;
	if (stringlength<=(bestlength/4)) { $('#' + fieldname).css('background-color','#DA5358'); }
	else if (stringlength<=(bestlength/4*3)) { $('#' + fieldname).css('background-color','#FEE1B0'); }
	else if (stringlength<=bestlength) { $('#' + fieldname).css('background-color','#7DB83A'); }
	else if (stringlength<=bestlength+((maxlength-bestlength)/3)) { $('#' + fieldname).css('background-color','#7DB83A'); }
	else if (stringlength<=bestlength+((maxlength-bestlength)/3*2)) { $('#' + fieldname).css('background-color','#FEE1B0'); }
	else { $('#' + fieldname).css('background-color','#DA5358'); }
	}
	
function showPageQuality(fieldname, bestlength, maxlength, sitelength) { var stringlength = document.getElementById(fieldname).value.length; if (fieldname=='pagekeys') { stringlength = stringlength + sitelength } if (stringlength<1) { stringlength = sitelength}; var qualstat = 1; document.getElementById('show_' + fieldname + '_length').innerHTML = stringlength; if (stringlength<=(bestlength/4)) { $('#' + fieldname).css('background-color','#DA5358'); } else if (stringlength<=(bestlength/4*3)) { $('#' + fieldname).css('background-color','#FEE1B0'); } else if (stringlength<=bestlength) { $('#' + fieldname).css('background-color','#7DB83A'); } else if (stringlength<=bestlength+((maxlength-bestlength)/3)) { $('#' + fieldname).css('background-color','#7DB83A'); } else if (stringlength<=bestlength+((maxlength-bestlength)/3*2)) { $('#' + fieldname).css('background-color','#FEE1B0'); } else { $('#' + fieldname).css('background-color','#DA5358');}}

function passHeight(pe,ce,cn,ca,tc){var bcs=new Array();var boxSum=0;var i=0;var boxCount=0;var mch=0;var bc=0;var wc=new Array();$(pe).each(function(){$(this).children(ce).each(function(){i++;$(this).addClass(tc+i);for(var a=0;a<ca.length;++a){if($(this).hasClass(ca[a])){bcs[i]=(a+1);}}});});for(var b=1;b<bcs.length;++b){bc=bc+bcs[b];$('.'+tc+b).height('auto');if($('.'+tc+b).height()>mch){mch=$('.'+tc+b).height();}wc.push('.'+tc+b);if(bc>=cn){for(var w=0;w<wc.length;w++){$(wc[w]).css('height', mch);}bc=0;mch=0;wc=new Array();}}}

function passLiTable(pe,ce,cn,ca,tc){var bcs=new Array();var boxSum=0;var i=0;var l=0;var boxCount=0;var mch=0;var bc=0; var ccv=0;var wc=new Array();var le=new Array(); var cc=new Array('','switchclass');var ub=0;$(pe).each(function(){if($(this).css('display')!='none'){l++;bcs[l]=new Array();$(this).children(ce).each(function(){if($(this).css('display')!='none'){i++;
// wie kriegt man hier 'tblc' mit dem wert aus tc dynamisch gefüllt ?
$(this).removeClass(function (index, css) { return (css.match (/\btblc-\S+/g) || []).join(' '); });
$(this).removeClass('switchclass');$(this).addClass(tc+'-'+i);for(var a=0;a<ca.length;++a){if($(this).hasClass(ca[a])){bcs[l][i]=(a+1);}}}});}});for(var l=1;l<bcs.length;l++){for(var b=1+ub;b<bcs[l].length;b++){bc=bc+bcs[l][b];$('.'+tc+'-'+b).height('auto');if($('.'+tc+'-'+b).height()>mch){mch=$('.'+tc+'-'+b).height();}wc.push('.'+tc+'-'+b);if(bc>=cn){for(var w=0;w<wc.length;w++){if(mch<10){mch='1.5em';}$(wc[w]).css('min-height',mch);$(wc[w]).addClass(cc[ccv]);}bc=0;mch=0;ccv++;if(ccv>1)ccv=0;wc=new Array();}else{for(var w=0;w<wc.length;w++){if(mch<10){mch='1.5em';}$(wc[w]).css('min-height',mch);$(wc[w]).addClass(cc[ccv]);}}ub=b;}bc=0;mch = 0;}}

// extending jquery to allow tabs in 

(function($) { function pasteIntoInput(el, text) { el.focus(); var val = el.value; if (typeof el.selectionStart == "number") { var selStart = el.selectionStart; el.value = val.slice(0, selStart) + text + val.slice(el.selectionEnd); el.selectionEnd = el.selectionStart = selStart + text.length; } else if (typeof document.selection != "undefined") { var textRange = document.selection.createRange();textRange.text = text;textRange.collapse(false);textRange.select();}} function allowTabChar(el){ $(el).keydown(function(e) {if(e.which==9){pasteIntoInput(this,"\t");return false;}}); $(el).keypress(function(e){if(e.which==9){return false;}});} $.fn.allowTabChar = function(){if(this.jquery){this.each(function(){if(this.nodeType==1){var nodeName = this.nodeName.toLowerCase();if(nodeName=="textarea" || (nodeName=="input" && this.type=="text")){allowTabChar(this);}}})}return this;}})(jQuery);

// call function
// $(function(){$("SELECTOR").allowTabChar();})
// EOF