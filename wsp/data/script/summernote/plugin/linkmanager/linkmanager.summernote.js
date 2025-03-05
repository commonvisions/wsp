/* based on https://github.com/DiemenDesign/summernote-image-attributes */

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
            linkManager: {
                dialogTitle: 'XTDlink',
                tooltip: 'XTDlink',
                tabImage: 'Link',
                    src: 'Source',
                    browse: 'Browse',
                title: 'Title',
          alt: 'Alt Text',
          dimensions: 'Dimensions',
        tabSelect: 'Filesystem',
          pagelist: 'summernote xtd page list',
          documentlist: 'summernote xtd document list',
          imagelist: 'summernote xtd image list',
          imagepreview: 'Preview',
        tabAttributes: 'Styles',
          class: 'Class',
          style: 'Style',
          role: 'Role',
        tabLink: 'Link',
                linkText: 'Linked Text',
          linkHref: 'URL',
          linkTarget: 'Target',
          linkClass: 'Class',
          linkStyle: 'Style',
          linkRel: 'Rel',
          linkRole: 'Role',
        tabUpload: 'Upload',
          upload: 'Upload',
        tabBrowse: 'Browse',
        editBtn: 'OK',
          loadListChoose: 'Choose file from list',
                loadListFailed: 'JSON-file not found or false JSON-syntax',
                loadListError: 'If you see this, loading list did not happen by error',
            }
        }
    });
    $.extend($.summernote.options, {
        linkManager: {
            icon: '<i class="note-icon-pencil"/>',
            removeEmpty: true,
        },
    });
    $.extend($.summernote.plugins, {
        'linkManager': function (context) {
            var self      = this,
                ui        = $.summernote.ui,
                $note     = context.layoutInfo.note,
                $editor   = context.layoutInfo.editor,
                $editable = context.layoutInfo.editable,
                options   = context.options,
                lang      = options.langInfo;
            if (options.linkList !== undefined) {
                if (options.linkList.mediaList !== undefined) {
                    var mediaList = options.linkList.mediaList;
                }
                if (options.linkList.documentList !== undefined) {
                    var documentList = options.linkList.documentList;
                }
                if (options.linkList.pageList !== undefined) {
                    var pageList = options.linkList.pageList;
                }
            }
            context.memo('button.linkManager', function() {
                var button = ui.button({
                    contents: options.linkManager.icon,
                    tooltip:  lang.linkManager.tooltip,
                    click: function () {
                        context.invoke('linkManager.show');
                    }
                });
                return button.render();
            });
        
            // loads elements from json list
            this.loadList = function(targetList, givenVal) {
                return $.Deferred(function(deferred) {
                    if (self.data === undefined && eval(targetList) !== undefined) {
                        var jqxhr = $.getJSON(eval(targetList), function( data ) {
                            $('.note-linkManager-' + targetList).empty().append($('<option>', { 
                                value: '',
                                text : lang.linkManager.loadListChoose,
                            }));
                            $.each( data, function( key, val ) {
                                if (val['0']=='option') {
                                    if (givenVal && givenVal==val['1']) {
                                        $('.note-linkManager-' + targetList).append($('<option>', { 
                                            value: val['1'],
                                            text : val['2']
                                        }).attr('selected','selected'));
                                        $('.xtdimage-preview-holder').html('<img src="' + val['1'] + '" class="imageselect-preview-image" style="max-width: 100%; max-height: 25vh;" />');
                                    } else {
                                        $('.note-linkManager-' + targetList).append($('<option>', { 
                                            value: val['1'],
                                            text : val['2']
                                        }));
                                    }
                                }
                                else if (val['0']=='html') {
                                    $('.note-linkManager-' + targetList).append($(val['1']));
                                }
                            });
                            // fires after creating select
                            loadListCompleted(targetList);
                            context.invoke('editor.restoreRange');
                        })
                        .fail(function() {
                            $('.note-linkManager-' + targetList).append($('<option>', { 
                                value: '',
                                text : lang.linkManager.loadListFailed,
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
                var body =  '<ul class="nav note-nav nav-tabs note-nav-tabs">' +
                    '  <li class="active"><a href="#note-linkManager' + timestamp + '" data-toggle="tab">' + lang.linkManager.tabLink + '</a></li>' +
                    '  <li><a href="#note-linkManager-selectfile' + timestamp + '" data-toggle="tab">' + lang.linkManager.tabSelect + '</a></li>' +
                    '</ul>';
                        
                body +=     '<div class="tab-content note-tab-content">';
                    // Select Tab
                body +=     '  <div class="tab-pane note-tab-pane" id="note-linkManager-selectfile' + timestamp + '">' +
                    '    <div class="note-form-group form-group note-group-linkManager-selectpages">' +
                    '      <div class="col-sm-3">' + lang.linkManager.pagelist + '</div>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <select class="note-linkManager-list note-linkManager-pageList form-control note-form-control note-select searchselect fullwidth" onchange="updateXTDLink(\'pageList\', this.value);" >' +
                    '        </select>' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="note-form-group form-group note-group-linkManager-selectdocuments">' +
                    '      <div class="col-sm-3">' + lang.linkManager.documentlist + '</div>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <select class="note-linkManager-list note-linkManager-documentList form-control note-form-control note-select searchselect fullwidth" onchange="updateXTDLink(\'documentList\', this.value);" >' +
                    '        </select>' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="note-form-group form-group note-group-linkManager-selectimages">' +
                    '      <div class="col-sm-3">' + lang.linkManager.imagelist + '</div>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <select class="note-linkManager-list note-linkManager-mediaList form-control note-form-control note-select searchselect fullwidth" onchange="updateXTDLink(\'mediaList\', this.value);" >' +
                    '        </select>' +
                    '      </div>' +
                    '    </div>' +
                    '  </div>';
                // Link Tab = first open tab
                body +=     '  <div class="tab-pane note-tab-pane fade in active" id="note-linkManager' + timestamp + '">' +
                    '    <div class="note-form-group form-group note-group-linkManager-link-text">' +
                    '      <label class="control-label note-form-label col-xs-3">' + lang.linkManager.linkText + '</label>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <input class="note-linkManager-link-text form-control note-form-control note-input" type="text">' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="note-form-group form-group note-group-linkManager-link-href">' +
                    '      <label class="control-label note-form-label col-xs-3">' + lang.linkManager.linkHref + '</label>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <input class="note-linkManager-link-href form-control note-form-control note-input" type="text">' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="note-form-group form-group note-group-linkManager-link-target">' +
                    '      <label class="control-label note-form-label col-xs-3">' + lang.linkManager.linkTarget + '</label>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <select class="note-linkManager-link-target form-control note-form-control note-select singleselect" >' +
                    '            <option value="">-</option>' +
                    '            <option value="_self">_self</option>' +
                    '            <option value="_blank">_blank</option>' +
                    '            <option value="_top">_top</option>' +
                    '            <option value="_parent">_parent</option>' +
                    '       </select>' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="note-form-group form-group note-group-linkManager-link-class">' +
                    '      <label class="control-label note-form-label col-xs-3">' + lang.linkManager.linkClass + '</label>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <input class="note-linkManager-link-class form-control note-form-control note-input" type="text">' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="note-form-group form-group note-group-linkManager-link-style">' +
                    '      <label class="control-label note-form-label col-xs-3">' + lang.linkManager.linkStyle + '</label>' +
                    '      <div class="input-group note-input-group col-xs-12 col-sm-9">' +
                    '        <input class="note-linkManager-link-style form-control note-form-control note-input" type="text">' +
                    '      </div>' +
                    '    </div>' +
                    '  </div>';    
                body +=     '</div>';
                this.$dialog=ui.dialog({
                    title:  lang.linkManager.dialogTitle,
                    body:   body,
                    footer: '<button href="#" class="btn btn-primary note-btn note-btn-primary note-linkManager-btn">' + lang.linkManager.editBtn + ' </button>'
                }).render().appendTo($container);
                if(jQuery().multiselect) {
                    $('.note-select.searchselect').multiselect('destroy');
                    $('.note-select.searchselect').multiselect({maxHeight: 300, enableFiltering: true,});
                    $('.note-select.singleselect').multiselect('destroy');
                    $('.note-select.singleselect').multiselect();
                }
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
                context.invoke('editor.saveRange');
                var linkInfo = context.invoke('getLinkInfo');
                if (linkInfo.range) {
                    linkInfo['class'] = linkInfo.range.sc.className;
                    if (linkInfo.range.sc.style) { linkInfo['style'] = linkInfo.range.sc.style.cssText; } else { linkInfo['style'] = ''; }
                    if (linkInfo.range.sc.attributes && linkInfo.range.sc.attributes.target) { linkInfo['target'] = linkInfo.range.sc.attributes.target.nodeValue; } else { linkInfo['target'] = ''; }
                }
                
                console.log('linkInfo');
                console.log(linkInfo);
          
                this.showLinkManagerDialog(linkInfo).then( function (linkInfo) {
                    ui.hideDialog(self.$dialog);
                    if($link.parent().is("a")) $link.unwrap();
                    if (linkInfo.linkHref) {
                        var linkBody = '<a';
                        if (linkInfo.linkClass) linkBody += ' class="' + linkInfo.linkClass + '"';
                        if (linkInfo.linkStyle) linkBody += ' style="' + linkInfo.linkStyle + '"';
                        linkBody += ' href="' + linkInfo.linkHref + '" target="' + linkInfo.linkTarget + '"';
                        if (linkInfo.linkRel) linkBody += ' rel="' + linkInfo.linkRel + '"';
                        if (linkInfo.linkRole) linkBody += ' role="' + linkInfo.linkRole + '"';
                        linkBody += '></a>';
                        $link.wrap(linkBody);
                    }
                    $note.val(context.invoke('code'));
                    $note.change();
                });
            };
            this.loadList('mediaList', 'replace with var');
            this.loadList('pageList', 'replace with var');
            this.loadList('documentList', 'replace with var');
            this.showLinkManagerDialog = function (linkInfo) {
                context.invoke('editor.saveRange');
                
                console.log('showLinkManagerDialog');
                console.log(linkInfo);
                
                return $.Deferred( function (deferred) {
                    var $linkText    = self.$dialog.find('.note-linkManager-link-text'),
                        $linkHref    = self.$dialog.find('.note-linkManager-link-href'),
                        $linkTarget  = self.$dialog.find('.note-linkManager-link-target'),
                        $linkClass   = self.$dialog.find('.note-linkManager-link-class'),
                        $linkStyle   = self.$dialog.find('.note-linkManager-link-style'),
                        $editBtn     = self.$dialog.find('.note-linkManager-btn');
                    $linkText.val();
                    $linkHref.val();
                    $linkClass.val();
                    $linkStyle.val();
                    $linkTarget.val();
                    if (linkInfo.text) {
                        $linkText.val(linkInfo.text);
                        $linkHref.val(linkInfo.url);
                        $linkClass.val(linkInfo.class);
                        $linkStyle.val(linkInfo.style);
                        $linkTarget.val(linkInfo.target);
                    }
                    ui.onDialogShown(self.$dialog, function () {
                        context.triggerEvent('dialog.shown');
                        $editBtn.click( function (e) {
                            e.preventDefault();
                            deferred.resolve({
                                linkHref:   $linkHref.val(),
                                linkTarget: $linkTarget.val(),
                                linkClass:  $linkClass.val(),
                                linkStyle:  $linkStyle.val(),
                            }).then(function (img) {
                                context.triggerEvent('change', $editable.html());
                            });
                        });
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

function updateXTDLink (targetList, xtdlinkdata) {
    $('.note-linkManager-link-href').val(xtdlinkdata);
    $('.note-linkManager-list option').not('.note-linkManager-' + targetList + ' option').prop("selected", false);
    if(jQuery().multiselect) {
        $('.note-select.searchselect').multiselect('destroy');
        $('.note-select.searchselect').multiselect({
            maxHeight: 300,
            enableFiltering: true,
        });
    }
}

var xtdlinkButton = function (context) {
    var ui = $.summernote.ui;
    var lc = $.summernote.lang[$.summernote.options.lang];
    // create button
    var button = ui.button({
        contents: '<i class="note-icon-link"></i>',
        tooltip: lc.linkManager.tooltip,
        click: function () {
            context.invoke('linkManager.show');
        }
    });
    return button.render(); // return button as jquery object
}

console.log('loaded linkmanager ' + Date.now());