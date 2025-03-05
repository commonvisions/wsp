<?php

defined( 'DOCUMENT_ROOT' ) || exit;

?><link rel="stylesheet" href="/<?php echo WSP_DIR; ?>/media/layout/summernote-wsp.css">
<link rel="stylesheet" href="/<?php echo WSP_DIR; ?>/media/layout/bootstrap-multiselect.css">

<script src="/<?php echo WSP_DIR; ?>/data/script/bootstrap/bootstrap-multiselect.js"></script>
<script type="text/javascript" src="/<?php echo WSP_DIR; ?>/data/script/summernote/summernote.js"></script>
<script type="text/javascript" src="/<?php echo WSP_DIR; ?>/data/script/summernote/plugin/br/br.summernote.js"></script>
<script type="text/javascript" src="/<?php echo WSP_DIR; ?>/data/script/summernote/plugin/imagemanager/imagemanager.summernote.js"></script>
<script type="text/javascript" src="/<?php echo WSP_DIR; ?>/data/script/summernote/plugin/linkmanager/linkmanager.summernote.js"></script>

<script>

$(document).ready(function() {
    
    $.extend(true,$.summernote.lang, {
        'de-DE': {
            imageManager: {
                dialogTitle: '<?php echo returnIntLang('summernote image attributes', false); ?>',
                tooltip: '<?php echo returnIntLang('summernote image attributes', false); ?>',
                tabImage: 'Image',
                    src: 'URI/Source',
                    title: 'Title',
                    alt: 'Alt Text',
                tabSelect: 'Choose from list',
                    imagelist: 'List',
                    imagepreview: 'Preview',
                tabAttributes: 'Styling',
                    dimensions: 'Dimensions',
                    class: 'Class',
                    style: 'Style',
                editBtn: 'OK',
                loadListChoose: 'Choose file from list',
                loadListFailed: 'JSON-file not found or false JSON-syntax',
                loadListError: 'If you see this, loading list did not happen by error',
            }
        }
    });
    
    $('.summernote').summernote({
        toolbar: [
            // [groupName, [list of button]]
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript','clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['extra', ['xtdimage','xtdlink','link','video']],
            ['table', ['table']],
            ['misc', ['codeview']],
            ['work', ['undo','redo']],
        ],
        buttons: {
            xtdimage: xtdimageButton,
            xtdlink: xtdlinkButton,
        },
        
        popover: {
            image: [
                ['custom', ['imageManager']],
                ['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
                ['float', ['floatLeft', 'floatRight', 'floatNone']],
                ['remove', ['removeMedia']]
            ],
            link: [
                'link',
                ['custom', ['linkAttributes']],
                ['remove', ['unlink']]
            ],

        },
        lang: 'de-DE', // Change to your chosen language
        imageManager:{
            icon:'<i class="note-icon-pencil"/>',
            removeEmpty: true, // true = remove empty attributes | false = leave empty if present
            disableUpload: false // true = don't display Upload Options | Display Upload Options
        },
        linkList: {
            mediaList: '/<?php echo WSP_DIR; ?>/xajax/json.medialist.php',
            documentList: '/<?php echo WSP_DIR; ?>/xajax/json.documentlist.php',
            pageList: '/<?php echo WSP_DIR; ?>/xajax/json.pagelist.php',
        },
        minHeight: 300,
        maxHeight: 700,
    });
    
    $('.summertext').summernote({
        toolbar: [
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript','clear']],
            ['misc', ['codeview']],
            ['work', ['undo','redo']],
        ],
        lang: 'de-DE', // Change to your chosen language
        minHeight: 200,
        maxHeight: 400,
    });
    
    $('.singleselect').multiselect();
    
    $('.searchselect').multiselect({
        maxHeight: 300,
        enableFiltering: true,
    });
    
    $('.fullselect').multiselect({
            enableFiltering: true,
            numberDisplayed: 10,
            maxHeight: 300,
            optionClass: function(element) {
                var value = $(element).attr('class');
                return value;
            }
        });
    
});

</script>