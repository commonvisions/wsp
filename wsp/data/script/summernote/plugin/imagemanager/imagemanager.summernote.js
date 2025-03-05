/**
 * based on https://github.com/DiemenDesign/summernote-image-attributes
 * MIT License, Copyright (c) 2018 Diemen Design
 * 
 * Version: 0.1
 * Date: 2018-08-25T14:00Z
 *
 * usage:
 *
 * $(document).ready(function() {
 *   $('.summernote').summernote({
 *     toolbar: [
 *       ['extra', ['xtdimage']],
 *     ],
 *     buttons: {
 *       xtdimage: xtdimageButton,
 *     },
 *     popover: {
 *       image: [
 *         ['custom', ['imageManager']],
 *         ['imagesize', ['imageSize100', 'imageSize50', 'imageSize25']],
 *         ['float', ['floatLeft', 'floatRight', 'floatNone']],
 *         ['remove', ['removeMedia']]
 *       ],
 *     },
 *     imageManager:{ // optional
 *       icon:'<i class="note-icon-pencil"/>', // optional
 *       removeEmpty: true, // true = remove empty attributes | false = leave empty if present // optional
 *       disableUpload: false // true = don't display Upload Options | Display Upload Options // optional
 *     },
 *     linkList: {
 *       mediaList: './mediaList.json.php',
 *     },
 *   });
 * });
 *
 */

(function (factory) {
  if (typeof define === 'function' && define.amd) {
    define(['jquery'], factory);
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory(require('jquery'));
  } else {
    factory(window.jQuery);
  }
}(function ($) {
    var readFileAsDataURL = function (file) {
        return $.Deferred( function (deferred) {
            $.extend(new FileReader(),{
                onload: function (e) {
                    var sDataURL = e.target.result;
                    deferred.resolve(sDataURL);
                },
                onerror: function () {
                    deferred.reject(this);
                }
            }).readAsDataURL(file);
        }).promise();
    };
    $.extend(true,$.summernote.lang, {
        'en-US': {
            imageManager: {
                dialogTitle: 'XTDimage',
                tooltip: 'XTDimage',
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
    $.extend($.summernote.options, {
        imageManager: {
            icon: '<i class="note-icon-pencil"/>',
            removeEmpty: true,
            disableUpload: false,
        },
    });
    $.extend($.summernote.plugins, {
        'imageManager': function (context) {
            var self      = this,
                ui        = $.summernote.ui,
                $note     = context.layoutInfo.note,
                $editor   = context.layoutInfo.editor,
                $editable = context.layoutInfo.editable,
                options   = context.options,
                lang      = options.langInfo,
                imageManagerLimitation = '';
            if (options.linkList !== undefined) {
                if (options.linkList.mediaList !== undefined) {
                    var mediaList = options.linkList.mediaList;
                }
            }
            if (options.maximumImageFileSize) {
                var unit = Math.floor(Math.log(options.maximumImageFileSize) / Math.log(1024));
                var readableSize = (options.maximumImageFileSize/Math.pow(1024,unit)).toFixed(2) * 1 + ' ' + ' KMGTP'[unit] + 'B';
                imageManagerLimitation = '<small class="help-block note-help-block">' + lang.image.maximumFileSize + ' : ' + readableSize+'</small>';
            }
            context.memo('button.imageManager', function() {
                var button = ui.button({
                    contents: options.imageManager.icon,
                    tooltip:  lang.imageManager.tooltip,
                    click: function () {
                        context.invoke('imageManager.show');
                    }
                });
                return button.render();
            });
            
            // loads elements from json list
            this.loadList = function(targetList, givenVal) {
                return $.Deferred(function(deferred) {
                    if (self.data === undefined && eval(targetList) !== undefined) {
                        var jqxhr = $.getJSON(eval(targetList), function( data ) {
                            $('.xtdimage-preview-holder').html('');
                            $('.note-imageManager-' + targetList).empty().append($('<option>', { 
                                value: '',
                                text : lang.imageManager.loadListChoose,
                            }));
                            $.each( data, function( key, val ) {
                                if (val['0']=='option') {
                                    if (givenVal && givenVal==val['1']) {
                                        $('.note-imageManager-' + targetList).append($('<option>', { 
                                            value: val['1'],
                                            text : val['2']
                                        }).attr('selected','selected'));
                                        $('.xtdimage-preview-holder').html('<img src="' + val['1'] + '" class="imageselect-preview-image" style="max-width: 100%; max-height: 25vh;" />');
                                    } else {
                                        $('.note-imageManager-' + targetList).append($('<option>', { 
                                            value: val['1'],
                                            text : val['2']
                                        }));
                                    }
                                }
                                else if (val['0']=='html') {
                                    $('.note-imageManager-' + targetList).append($(val['1']));
                                }
                            });
                            // fires after creating select
                            loadListCompleted(targetList);
                            context.invoke('editor.restoreRange');
                        })
                        .fail(function() {
                            $('.note-imageManager-' + targetList).append($('<option>', { 
                                value: '',
                                text : lang.imageManager.loadListFailed,
                            }));
                        });
                        deferred.resolve();
                    } else {
                        deferred.resolve();
                    }
                });
            }
        
            this.initialize = function () {
                var $container = options.dialogsInBody ? $(document.body) : $editor;
                var timestamp = Date.now();
                var body = '<ul class="nav note-nav nav-tabs note-nav-tabs">' +
                    '  <li class="active"><a href="#note-imageManager' + timestamp + '" data-toggle="tab">' + lang.imageManager.tabImage + '</a></li>' +
                    '  <li><a href="#note-imageManager-selectfile' + timestamp + '" data-toggle="tab">' + lang.imageManager.tabSelect + '</a></li>' +
                    '  <li><a href="#note-imageManager-attributes' + timestamp + '" data-toggle="tab">' + lang.imageManager.tabAttributes + '</a></li>' +
                    '</ul>';
                     
                body += '<div class="tab-content note-tab-content">';
                // Select Tab
                body += '<div class="tab-pane note-tab-pane" id="note-imageManager-selectfile' + timestamp + '">' +
                    '   <div class="note-form-group form-group row note-group-imageManager-selector">' +
                    '      <div class="col-sm-3">' + lang.imageManager.imagelist + '</div>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <select class="note-imageManager-mediaList form-control note-form-control note-select searchselect fullwidth" onchange="updateXTDImage(this.value);" >' +
                    '           <option value="">' + lang.imageManager.loadListError + '</option>' +
                    '        </select>' +
                    '      </div>' +
                    '    </div>' +
                '    <div class="note-group-imageManager-selectpreview row">' +
                '      <div class="col-sm-3">' + lang.imageManager.imagepreview + '</div>' +
                '      <div class="xtdimage-preview-holder col-xs-12 col-sm-9"></div>' +
                    '      <hr style="width: 100%; height: 0.1px; position: relative; clear: both; float: none; border: none; background: none; visibility: hidden;">' +
                    '    </div>' +
                    '  </div>';
                    // Style Tab
        body +=     '  <div class="tab-pane note-tab-pane" id="note-imageManager-attributes' + timestamp + '">' +
                    '    <div class="note-form-group form-group note-group-imageManager-dimensions">' +
                    '      <label class="control-label note-form-label col-sm-3">' + lang.imageManager.dimensions + '</label>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <input class="note-imageManager-width form-control note-form-control note-input" type="text" />' +
                    '        <span class="input-group-addon note-input-group-addon">x</span>' +
                    '        <input class="note-imageManager-height form-control note-form-control note-input" type="text" />' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="note-form-group form-group note-group-imageManager-style">' +
                    '      <label class="control-label note-form-label col-sm-3">' + lang.imageManager.style + '</label>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <input class="note-imageManager-style form-control note-form-control note-input" type="text">' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="note-form-group form-group note-group-imageManager-class">' +
                    '      <label class="control-label note-form-label col-sm-3">' + lang.imageManager.class + '</label>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <input class="note-imageManager-class form-control note-form-control note-input" type="text">' +
                    '      </div>' +
                    '    </div>' +
                    '  </div>';
        // Tab 1
        body +=    '  <div class="tab-pane note-tab-pane fade in active" id="note-imageManager' + timestamp + '">' +
                   '    <div class="note-form-group form-group note-group-imageManager-url">' +
                   '      <label class="control-label note-form-label col-sm-3">' + lang.imageManager.src + '</label>' +
                   '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                   '        <input class="note-imageManager-src form-control note-form-control note-input" type="text" />' +
                   '      </div>' +
                   '    </div>' +
                   '    <div class="note-form-group form-group note-group-imageManager-title">' +
                   '      <label class="control-label note-form-label col-sm-3">' + lang.imageManager.title + '</label>' +
                   '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                   '        <input class="note-imageManager-title form-control note-form-control note-input" type="text" />' +
                   '      </div>' +
                   '    </div>' +
                   '    <div class="note-form-group form-group note-group-imageManager-alt">' +
                   '      <label class="control-label note-form-label col-sm-3">' + lang.imageManager.alt + '</label>' +
                   '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                   '        <input class="note-imageManager-alt form-control note-form-control note-input" type="text" />' +
                   '      </div>' +
                   '    </div>' +
                   '  </div>' +
                   '</div>';
            this.$dialog=ui.dialog({
                title:  lang.imageManager.dialogTitle,
                body:   body,
                footer: '<button href="#" class="btn btn-primary note-btn note-btn-primary note-imageManager-btn">' + lang.imageManager.editBtn + ' </button>'
            }).render().appendTo($container);
        };
        this.destroy = function () {
            ui.hideDialog(this.$dialog);
            this.$dialog.remove();
        };
        this.bindEnterKey = function ($input,$btn) {
            $input.on('keypress', function (e) {
                if (e.keyCode === 13) $btn.trigger('click');
            });
        };
        this.bindLabels = function () {
            self.$dialog.find('.form-control:first').focus().select();
            self.$dialog.find('label').on('click', function () {
                $(this).parent().find('.form-control:first').focus();
            });
        };
        this.show = function () {
            var $img    = $($editable.data('target'));
            var imgInfo = {
                imgDom:  $img,
                title:   $img.attr('title'),
                src:     $img.attr('src'),
                alt:     $img.attr('alt'),
                width:   $img.attr('width'),
                height:  $img.attr('height'),
                class:   $img.attr('class'),
                style:   $img.attr('style'),
            };
            
            this.loadList('mediaList', imgInfo.src);
            this.showImageManagerDialog(imgInfo).then( function (imgInfo) {
                ui.hideDialog(self.$dialog);
                var $img = imgInfo.imgDom;
                if (options.imageManager.removeEmpty) {
                    if (imgInfo.alt)    $img.attr('alt',   imgInfo.alt);    else $img.removeAttr('alt');
                    if (imgInfo.width)  $img.attr('width', imgInfo.width);  else $img.removeAttr('width');
                    if (imgInfo.height) $img.attr('height',imgInfo.height); else $img.removeAttr('height');
                    if (imgInfo.title)  $img.attr('title', imgInfo.title);  else $img.removeAttr('title');
                    if (imgInfo.src)    $img.attr('src',   imgInfo.src);    else $img.attr('src', '#');
                    if (imgInfo.class)  $img.attr('class', imgInfo.class);  else $img.removeAttr('class');
                    if (imgInfo.style)  $img.attr('style', imgInfo.style);  else $img.removeAttr('style');
                } else {
                    if (imgInfo.src)    $img.attr('src',   imgInfo.src);    else $img.attr('src', '#');
                    $img.attr('alt',    imgInfo.alt);
                    $img.attr('width',  imgInfo.width);
                    $img.attr('height', imgInfo.height);
                    $img.attr('title',  imgInfo.title);
                    $img.attr('class',  imgInfo.class);
                    $img.attr('style',  imgInfo.style);
                }
                if (imgInfo.imgDom.length<1) {
                    context.invoke('editor.restoreRange');
                    xtdimageParam = imgInfo;
                    context.invoke('editor.insertImage', imgInfo.src, setupXTDImage);
                };
                $note.val(context.invoke('code'));
                $note.change();
            });
        };
        this.showImageManagerDialog = function (imgInfo) {
            context.invoke('editor.saveRange');
            return $.Deferred( function (deferred) {
                var $imageTitle  = self.$dialog.find('.note-imageManager-title'),
                    $imageInput  = self.$dialog.find('.note-imageManager-input'),
                    $imageSrc    = self.$dialog.find('.note-imageManager-src'),
                    $imageAlt    = self.$dialog.find('.note-imageManager-alt'),
                    $imageWidth  = self.$dialog.find('.note-imageManager-width'),
                    $imageHeight = self.$dialog.find('.note-imageManager-height'),
                    $imageClass  = self.$dialog.find('.note-imageManager-class'),
                    $imageStyle  = self.$dialog.find('.note-imageManager-style'),
                    $editBtn     = self.$dialog.find('.note-imageManager-btn');
                ui.onDialogShown(self.$dialog, function () {
                    context.triggerEvent('dialog.shown');
                    $editBtn.click( function (e) {
                        e.preventDefault();
                        deferred.resolve({
                            imgDom:     imgInfo.imgDom,
                            title:      $imageTitle.val(),
                            src:        $imageSrc.val(),
                            alt:        $imageAlt.val(),
                            width:      $imageWidth.val(),
                            height:     $imageHeight.val(),
                            class:      $imageClass.val(),
                            style:      $imageStyle.val(),
                        }).then(function (img) {
                            context.triggerEvent('change', $editable.html());
                        });
                    });
                    $imageTitle.val(imgInfo.title);
                    $imageSrc.val(imgInfo.src);
                    $imageAlt.val(imgInfo.alt);
                    $imageWidth.val(imgInfo.width);
                    $imageHeight.val(imgInfo.height);
                    $imageClass.val(imgInfo.class);
                    $imageStyle.val(imgInfo.style);
                    self.bindEnterKey($editBtn);
                    self.bindLabels();
                });
                ui.onDialogHidden(self.$dialog, function () {
                    $editBtn.off('click');
                    if (deferred.state() === 'pending') deferred.reject();
                });
                ui.showDialog(self.$dialog);
            });
        };
    }
  });
}));

function updateXTDImage (xtdimagedata) {
    $('.note-imageManager-src').val(xtdimagedata);
    $('.xtdimage-preview-holder').html('<img src="' + xtdimagedata + '" class="imageselect-preview-image" style="max-width: 100%; max-height: 25vh;" />');
}

function setupXTDImage(imageObj) {
    if (xtdimageParam.alt)      imageObj.attr('alt',    xtdimageParam.alt);
    if (xtdimageParam.title)    imageObj.attr('title',  xtdimageParam.alt);
    if (xtdimageParam.class)    imageObj.attr('class',  xtdimageParam.alt);
    if (xtdimageParam.style)    imageObj.attr('style',  xtdimageParam.alt);
    if (xtdimageParam.width)    imageObj.attr('width',  xtdimageParam.alt);
    if (xtdimageParam.height)   imageObj.attr('height', xtdimageParam.alt);
}

function loadListCompleted(targetList) {
    if(jQuery().multiselect) {
        $('.note-select.searchselect').multiselect('destroy');
        $('.note-select.searchselect').multiselect({
            maxHeight: 300,
            enableFiltering: true,
        });
    }
}

var xtdimageParam;
var xtdimageButton = function (context) {
    var ui = $.summernote.ui;
    var lc = $.summernote.lang[$.summernote.options.lang];
    // create button
    var button = ui.button({
        contents: '<i class="note-icon-picture"></i>',
        tooltip: lc.imageManager.tooltip,
        click: function () {
            context.invoke('imageManager.show');
        }
    });
    return button.render(); // return button as jquery object
}

console.log('loaded imagemanager ' + Date.now());