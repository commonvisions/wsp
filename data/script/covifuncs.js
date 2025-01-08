function passHeight(pe,ce,cn,ca,tc){var bcs=new Array();var boxSum=0;var i=0;var boxCount=0;var mch=0;var bc=0;var wc=new Array();$(pe).each(function(){$(this).children(ce).each(function(){i++;$(this).addClass(tc+i);for(var a=0;a<ca.length;++a){if($(this).hasClass(ca[a])){bcs[i]=(a+1);}}});});for(var b=1;b<bcs.length;++b){bc=bc+bcs[b];$('.'+tc+b).height('auto');if($('.'+tc+b).height()>mch){mch=$('.'+tc+b).height();}wc.push('.'+tc+b);if(bc>=cn){for(var w=0;w<wc.length;w++){$(wc[w]).height(mch);}bc=0;mch=0;wc=new Array();}}}

$(document).ready(function(){
    $('img[width][height]').each(function(){
        $(this).removeAttr('height');
    });
});