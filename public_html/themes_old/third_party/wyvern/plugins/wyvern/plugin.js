// debugger;

/*
 * @package     ExpressionEngine
 * @subpackage  Fieldtypes
 * @category    Wyvern
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2010, 2011 - Brian Litzinger
 * @link        http://boldminded.com/add-ons/wyvern
 * @license
 *
 * Copyright (c) 2011, 2012. BoldMinded, LLC
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

(function() {

// Figure out if the default FM is defined, and if we can even load it on the current page.
wyvern_config.error_displayed = false;
wyvern_config.valid_filemanager = true;

if(wyvern_config.file_manager != 'assets' && typeof $.ee_filebrowser == "undefined")
{
    wyvern_config.valid_filemanager = false;
}

CKEDITOR.plugins.add('wyvern', {

    requires : ['dialog'],

    init: function(editor, pluginPath)
    {
        // Register our FileManager button so it works
        editor.ui.addButton('FileManager', {
            label: 'File Manager',
            command: 'filemanager'
        });

        CKEDITOR.on('dialogDefinition', function(e)
        {
            // Overrides definition.
            var definition = e.data.definition;
            var infoTab = definition.getContents('info');
            var dialogType = e.data.name;
            var ee_field_id = e.editor.name;

            // TODO: temporary fix so dialog works
            // Button styles below are not added if specialchar is the first dialog loaded.
            if(dialogType == 'specialchar') return;

            // First time it's loaded, not subsequent loads
            definition.onLoad = function()
            {
                if(dialogType == 'link')
                {
                    var assetPath = this.getContentElement('info', 'assetPath');
                    var protocol = this.getContentElement('info', 'protocol');
                    var url = this.getContentElement( 'info', 'url' );

                    bind_file_manager($('.asset_instructions_container .cke_button__filemanager'), url.domId, ee_field_id, ee_field_id, function(file){
                        // Add the path to the input.
                        if (file.directory && file.name) {
                            assetPath.setValue('{filedir_'+ file.directory +'}'+ file.name);
                        // It's an Assets file from S3 or another external source.
                        } else if (file.url) {
                            assetPath.setValue(file.url);
                        // Don't know how to handle it, let the user know.
                        } else {
                            assetPath.setValue('Unknown file path.');
                        }

                        // Add the thumbnail to the dialog for preview
                        if(file.thumb) {
                            $('.asset_thumbnail').html('<img src="'+ file.thumb + '" />');
                        } else {
                            // Expected URL
                            thumb_url = (wyvern_config.ee_version < 220) ? path.url +'_thumbs/thumb_'+ file.name : path.url +'_thumbs/'+ file.name;
                            $('.asset_thumbnail').html('<img src="'+ thumb_url + '" />');
                        }

                        $('.asset_instructions_container .instructions').hide();
                        $('#load_file_manager .submit').text('Change');
                    });

                    // Fixes a display issue where smaller contents, e.g. File Asset align vertically in the middle, looks ugly.
                    $('td.cke_dialog_contents').attr('vAlign', 'top');
                }

                if(dialogType == 'image')
                {
                    // Find the URL field in the link dialog
                    var url = this.getContentElement( 'info', 'txtUrl' );

                    // Append our link
                    var text_div = $('#'+ url.domId +' .cke_dialog_ui_input_text').css({'position': 'relative', 'width': '85%'});

                    $(text_div).closest('div').append('<a href="#" class="cke_button__filemanager cke_dialog_ui_button"><span class="submit">Browse</span></a>');

                    // Now bind the file manager trigger to our newly added link
                    var field = $('#'+ url.domId +' input.cke_dialog_ui_input_text');
                    // The image dialog does not have a name attribute on the input field, so assign the id to the name
                    // because the file manager is looking for an element name, not id.
                    field.attr('name', field.attr('id'));

                    // B/c CKeditor's API sucks, can't figure out how to find this element the "CKeditor way".
                    var previewImage = $('.ImagePreviewBox a img');

                    bind_file_manager('.cke_dialog_body a.cke_button__filemanager', field, field.attr('id'), ee_field_id, previewImage);
                }
            }

            // Remove the linking option in the image dialog, we handle this through the Link button instead.
            // for some reason, this doesn't work in the above conditional with this.removeContents, go figure.
            if (dialogType == 'image'){
                definition.removeContents('Link');
            }

            // Remove some fields and tabs that are overkill, I want to simplify things
            if (dialogType == 'image' || dialogType == 'link'){
                infoTab.remove('browse');
                // infoTab.remove('cmbAlign');
            }

            // =======================================================
            // Everything after this point is for the link dialog only
            // =======================================================

            if ((e.editor != editor) || (dialogType != 'link')) return;

            var content = getById(infoTab.elements, 'linkType');

            // Add new options to the drop down list
            content.items.unshift(['File Asset', 'asset']);
            content.items.unshift(['Template', 'template']);
            content.items.unshift(['Site Page', 'site_pages']);
            content['default'] = 'url';

            // If we don't have a valid FM, remove the Asset option
            if(!wyvern_config.valid_filemanager)
            {
                for(i = 0; i < content.items.length; i++)
                {
                    if(content.items[i][1] == 'asset')
                    {
                        content.items.splice(i, 1);
                    }
                }
            }

            definition.onFocus = CKEDITOR.tools.override(definition.onFocus, function(original) {
                return function() {
                    original.call(this);
                    if (this.getValueOf('info', 'linkType') == 'site_pages') {
                        this.getContentElement('info', 'url').select();
                    }
                };
            });

            infoTab.elements.push({
                type: 'vbox',
                id: 'pageWrapper',
                children: [{
                    type: 'text',
                    id: 'pagePath',
                    label: wyvern_config.link_type == 'id' ? 'Page Variable' : 'Page Path',
                    className: 'pages_container',
                    required: true,
                    onLoad: function() {
                        var dialog = this.getDialog();
                        var pagesContainer = dialog.getContentElement('info', 'pagesContainer').getElement().getParent().getParent();
                        var protocol = dialog.getContentElement('info', 'protocol').getElement().getParent();
                        // Hide on load
                        pagesContainer.hide();
                        protocol.hide();
                    },
                    setup: function(data) {
                        this.setValue(data.site_pages || '');
                    },
                    validate: function() {
                        var dialog = this.getDialog();
                        if (dialog.getValueOf('info', 'linkType') != 'site_pages') {
                            return true;
                        }
                            return true;
                        }
                    }
                ]
            });

            infoTab.elements.push({
                type: 'vbox',
                id: 'assetWrapper',
                children: [{
                    type: 'text',
                    id: 'assetPath',
                    label: 'Asset Path',
                    className: 'asset_container',
                    required: true,
                    onLoad: function() {
                        var dialog = this.getDialog();
                        var assetContainer = dialog.getContentElement('info', 'assetContainer').getElement().getParent().getParent();
                        // Hide on load
                        assetContainer.hide();
                    },
                    setup: function(data) {
                        this.setValue(data.asset || '');
                    },
                    validate: function() {
                        var dialog = this.getDialog();
                        if (dialog.getValueOf('info', 'linkType') != 'asset') {
                            return true;
                        }
                            return true;
                        }
                    }
                ]
            });

            infoTab.elements.push({
                type: 'vbox',
                id: 'templatetWrapper',
                children: [{
                    type: 'text',
                    id: 'templatePath',
                    label: 'Template Path',
                    className: 'template_container',
                    required: true,
                    onLoad: function() {
                        var dialog = this.getDialog();
                        var templateContainer = dialog.getContentElement('info', 'templateContainer').getElement().getParent().getParent();
                        // Hide on load
                        templateContainer.hide();
                    },
                    setup: function(data) {
                        this.setValue(data.template || '');
                    },
                    validate: function() {
                        var dialog = this.getDialog();
                        if (dialog.getValueOf('info', 'linkType') != 'template') {
                            return true;
                        }
                            return true;
                        }
                    }
                ]
            });

            // Add our site pages to the dialog content
            infoTab.elements.push({
                type: 'html',
                label: 'Pages',
                id: 'pagesContainer',
                html: '<div class="dialog page_listing"></div>'
            });

            infoTab.elements.push({
                type: 'html',
                label: 'Asset',
                id: 'assetContainer',
                html: '<div class="asset_preview_container">'+
                        '<div class="asset_thumbnail"></div>'+
                        '<div class="asset_instructions_container">'+
                            '<span class="instructions">No asset selected.</span> <a href="#" id="load_file_manager" class="cke_button__filemanager cke_dialog_ui_button"><span class="submit">Browse</span></a>'+
                        '</div>'+
                    '</div>'
            });

            infoTab.elements.push({
                type: 'html',
                label: 'Template',
                id: 'templateContainer',
                html: '<div class="dialog page_listing"></div>'
            });

            content.onChange = CKEDITOR.tools.override(content.onChange, function(original)
            {
                return function()
                {
                    original.call(this);
                    var dialog = this.getDialog();

                    // This getParent stuff is ridiculous...
                    var pagesContainer = dialog.getContentElement('info', 'pagesContainer').getElement().getParent().getParent();
                    var pagePath = dialog.getContentElement('info', 'pagePath').getElement().getParent().getParent().getParent().getParent().getParent().getParent();

                    var assetContainer = dialog.getContentElement('info', 'assetContainer').getElement().getParent().getParent();
                    var assetPath = dialog.getContentElement('info', 'assetPath').getElement().getParent().getParent().getParent().getParent().getParent().getParent();

                    var templateContainer = dialog.getContentElement('info', 'templateContainer').getElement().getParent().getParent();
                    var templatePath = dialog.getContentElement('info', 'templatePath').getElement().getParent().getParent().getParent().getParent().getParent().getParent();

                    var protocol = dialog.getContentElement('info', 'protocol').getElement().getParent();

                    switch(this.getValue())
                    {
                        case 'site_pages':
                            pagesContainer.show();
                            pagePath.show();
                            assetContainer.hide();
                            assetPath.hide();
                            templateContainer.hide();
                            templatePath.hide();
                            protocol.hide();

                            if (editor.config.linkShowTargetTab)
                            {
                                dialog.showPage('target');
                            }

                            $('.page_listing').html('<img src="'+ wyvern_config.cp_global_images +'indicator.gif" />');

                            $.ajax({
                                type: "GET",
                                url: wyvern_config.load_pages_url,
                                success: function(html){
                                    $('.page_listing').html(html);
                                    bind_pages(definition.dialog);
                                }
                            });

                        break;

                        case 'template':
                            pagesContainer.hide();
                            pagePath.hide();
                            assetContainer.hide();
                            assetPath.hide();
                            templateContainer.show();
                            templatePath.show();
                            protocol.hide();

                            if (editor.config.linkShowTargetTab)
                            {
                                dialog.showPage('target');
                            }

                            $('.page_listing').html('<img src="'+ wyvern_config.cp_global_images +'indicator.gif" />');

                            $.ajax({
                                type: "GET",
                                url: wyvern_config.load_templates_url,
                                success: function(html){
                                    $('.page_listing').html(html);
                                    bind_pages(definition.dialog);
                                }
                            });

                        break;

                        case 'asset':
                            pagesContainer.hide();
                            pagePath.hide();
                            assetContainer.show();
                            assetPath.show();
                            templateContainer.hide();
                            templatePath.hide();
                            protocol.hide();

                            if (editor.config.linkShowTargetTab)
                            {
                                dialog.showPage('target');
                            }
                        break;

                        default:
                            pagesContainer.hide();
                            pagePath.hide();
                            assetContainer.hide();
                            assetPath.hide();
                            templateContainer.hide();
                            templatePath.hide();
                            protocol.show();
                        break;
                    }
                }
            });

            content.setup = function(data)
            {
                // Make sure all other links are not selected
                $('.page_listing a').removeClass('selected');

                if(data.url)
                {
                    var path = data.url.url;

                    // Figure out what type of custom URL we're using on dialog load
                    if(path.substr(0, 9) == '{filedir_')
                    {
                        data.type = 'asset';
                        data.asset = path;
                    }
                    else if(path.substr(0, 10) == '{page_url:' || path.substr(0, 10) == '{site_url}')
                    {
                        data.type = 'site_pages';
                        data.site_pages = path;
                    }
                    else if(path.substr(0, 6) == '{path=')
                    {
                        data.type = 'template';
                        data.template = path;
                    }

                    // Add selected class to the correct items on dialog load
                    if(path && data.type == 'site_pages')
                    {
                        // Find and select our link based on the URL value
                        if(path.substr(0, 10) == '{page_url:')
                        {
                            page_id = path.match(/\d+/);
                            $('.page_listing a[data-id="'+ page_id +'"]').addClass('selected');
                        }
                        else if(path.substr(0, 10) == '{site_url}')
                        {
                            page_url = path.substr(10);
                            $('.page_listing a[data-url="'+ page_url +'"]').addClass('selected');
                        }
                    }

                    if(path && data.type == 'template')
                    {
                        // Find and select our link based on the URL value
                        template_path = path.match(/{path='(.*?)'}/)[1];
                        $('.page_listing a[data-url='+ template_path +']').addClass('selected');
                    }

                    if(path && data.type == 'asset')
                    {
                        id = path.match(/{filedir_(\d+)}/)[1];
                        image = path.replace(/{filedir_(\d+)}/, '');

                        num = wyvern_config.upload_paths.length;

                        $('#load_file_manager .submit').text('Select');
                        $('.asset_instructions_container .instructions').show();

                        for(i = 0; i < num; i++)
                        {
                            path = wyvern_config.upload_paths[i];

                            if(path.directory == id && path.is_image == "true")
                            {
                                // Expected URL
                                thumb_url = (wyvern_config.ee_version < 220) ? path.url +'_thumbs/thumb_'+ image : path.url +'_thumbs/'+ image;

                                // Do a simple file_exists check to see if the thumb really exists
                                $.ajax({
                                    type: 'POST',
                                    url: wyvern_config.theme_url + 'ajax.php',
                                    data: 'file='+ thumb_url,
                                    success: function(msg){
                                        // Will return 200 or 404
                                        thumb_url = msg == '200' ? thumb_url : wyvern_config.cp_global_images + 'default.png';
                                        $('.asset_thumbnail').html('<img src="'+ thumb_url + '" />');
                                    }
                                 });

                                $('#load_file_manager .submit').text('Change');
                                $('.asset_instructions_container .instructions').hide();

                                break;
                            }
                        }
                    }

                    this.setValue(data.type);
                }
                else
                {
                    // Default to whatever the user wants as the default
                    this.setValue(wyvern_config.default_link_type);
                }
            }

            // When OK is pressed
            content.commit = function(data)
            {
                data.type = this.getValue();

                switch(data.type)
                {
                    case 'site_pages':
                        data.type = 'url';
                        var dialog = this.getDialog();
                        var pagePath = dialog.getContentElement('info', 'pagePath');

                        dialog.setValueOf('info', 'url', pagePath.getValue());
                        dialog.setValueOf('info', 'protocol', '');
                    break;

                    case 'template':
                        data.type = 'url';
                        var dialog = this.getDialog();
                        var templatePath = dialog.getContentElement('info', 'templatePath');

                        dialog.setValueOf('info', 'url', templatePath.getValue());
                        dialog.setValueOf('info', 'protocol', '');
                    break;

                    case 'asset':
                        data.type = 'url';
                        var dialog = this.getDialog();
                        var assetPath = dialog.getContentElement('info', 'assetPath');
                        var value = assetPath.getValue();
                        var protocol = value.indexOf('http://') !== -1 ? 'http://' : '';

                        dialog.setValueOf('info', 'url', value);
                        dialog.setValueOf('info', 'protocol', protocol);
                    break;
                }
            };

        });
    }

});

})();

// Add Autosave support
if(typeof EE.publish == "object" && EE.publish.autosave == "object" && EE.publish.autosave.interval > 10)
{
    // 3 seconds before the defined interval, update our text area so it gets saved correctly.
    interval = (EE.publish.autosave.interval - 3) * 1000;

    setInterval({
        run: function() {
            for(var i in CKEDITOR.instances)
            {
               instance = CKEDITOR.instances[i];
               instance.updateElement();
            }
        }
    }.run, interval);
}

var cke_skin_ee = '';

// http://recklessrecursion.com/2010/10/05/typekit-and-tinymce/

CKEDITOR.on('instanceReady', function(e)
{
    var editor = e.editor;
    var field_name = editor.name;
    var ee_field_id = editor.name;
    var cke_viewing_source = false;

    init_typekit(editor);

    // Cleanup source formatting
    var myTags = new Array ('p','h1','h2','h3','h4','h5','h6','ol','ul');

    for (var Tag in myTags)
    {
        editor.dataProcessor.writer.setRules(myTags[Tag], {
            indent : false,
            breakBeforeOpen : false,
            breakAfterOpen : false,
            breakBeforeClose : false,
            breakAfterClose : true
        });
    }

    // Wrap each instance with another class with our EE theme name on it
    $('span.cke_skin_ee').addClass('ee_theme_'+wyvern_config.theme);

    // Find all instances of CKEditor and bind the ee_filebrowser trigger to each button
    bind_file_manager('#cke_'+ field_name + ' a.cke_button__filemanager', editor, field_name, ee_field_id, false);

    // Cleanup a few things. Instead of doing mouseup, just call on selectionChange so it isn't as often
    editor.on('selectionChange', function()
    {
        // Webkit based browsers add some silly <span> tag around things, lets remove them
        if(CKEDITOR.env.webkit) fixWebkit('#cke_'+ field_name);

        // Move images on every selection change, keep things in order
        if(wyvern_config.image_paragraphs == 'no') moveImages('#cke_'+ field_name, editor);
    });

    // Move Images on initial load
    if(wyvern_config.image_paragraphs == 'no') moveImages('#cke_'+ field_name, editor);

    source_button = $('#cke_'+ field_name + ' a.cke_button_source');
    td_height = 0;

    // First grab our td height based on the iframe height
    source_button.live("mousedown", function()
    {
        container = $(this).closest('.cke_skin_ee');
        iframe = container.find('iframe');
        td_height = iframe.closest('td').height();
    });

    // When switching to source view, it puts images back into <p> tags. This is meant for
    // when leaving source view, it will reset the images to be outside <p> tags.
    source_button.live("click", function()
    {
        var container = $(this).closest('.cke_skin_ee');
        var textarea = container.find('textarea.cke_source');
        var height = container.prev('textarea').height();
        var buttons = container.find('table.cke_editor tbody tr:first-child');
        var matrix = container.closest('table').closest('table.matrix');

        // Viewing source
        if(textarea.length > 0)
        {
            cke_viewing_source = true;

            if(wyvern_config.image_paragraphs == 'no') moveImages('#cke_'+ field_name, editor, textarea);

            // Subtract the toolbar height to reduce page shifting
            min_height = height - buttons.height();
            // Get a weird shift in height when in matrix cells
            reduce_by = matrix.length > 0 ? 8 : 0;

            // compare the td height to config height, and figure out which is best to use.
            height = td_height < min_height ? min_height : td_height - 3;
            textarea.height(height - reduce_by);
        }
        // Viewing editor
        else
        {
            cke_viewing_source = false;

            if(wyvern_config.image_paragraphs == 'no') moveImages('#cke_'+ field_name, editor);

            // Add a slight timeout as the editor.document.$ object is null immediately after swithing back.
            setTimeout({
                run: function() {
                    init_typekit(editor);
                }
            }.run, 150);
        }
    });

    // Update the textareas everytime someone leaves a CKeditor field so it is always posted correctly.
    // Color change was introduced in EE 2.2.1
    editor.on('blur', function(){
        if(!cke_viewing_source){
            this.updateElement();
            this.document.$.childNodes[1].childNodes[1].style.backgroundColor = '#ffffff';
            this.container.$.childNodes[1].style.backgroundColor = '#ffffff';
        }
    });

    editor.on('focus', function(){
        if(!cke_viewing_source){
            this.document.$.childNodes[1].childNodes[1].style.backgroundColor = '#fffff5';
            this.container.$.childNodes[1].style.backgroundColor = '#fffff5';
        }
    });

    // $('#publishForm').submit(function(e){
    //     editor.updateElement();
    //     e.preventDefault();
    // });
    // for(var instanceName in CKEDITOR.instances)
    // CKEDITOR.instances[instanceName].updateElement();
    //
});


var init_typekit = function(editor)
{
    // Add Typekit support if an ID is defined.
    if(wyvern_config.typekit_id == "")
        return;

    var async = "TypekitConfig = { \
        kitId: '"+ wyvern_config.typekit_id +"', \
        scriptTimeout: 3000 \
      }; \
      (function() { \
        var h = document.getElementsByTagName('html')[0]; \
        h.className += ' wf-loading'; \
        var t = setTimeout(function() { \
          h.className = h.className.replace(/(\s|^)wf-loading(\s|$)/g, ''); \
          h.className += ' wf-inactive'; \
        }, TypekitConfig.scriptTimeout); \
        var tk = document.createElement('script'); \
        tk.src = '//use.typekit.com/' + TypekitConfig.kitId + '.js'; \
        tk.type = 'text/javascript'; \
        tk.async = 'true'; \
        tk.onload = tk.onreadystatechange = function() { \
          var rs = this.readyState; \
          if (rs && rs != 'complete' && rs != 'loaded') return; \
          clearTimeout(t); \
          try { Typekit.load(TypekitConfig); } catch (e) {} \
        }; \
        var s = document.getElementsByTagName('script')[0]; \
        s.parentNode.insertBefore(tk, s); \
      })();";

    var loader = editor.document.$.createElement('script');
        loader.setAttribute("type", "text/javascript");

        editor.document.$.getElementsByTagName("head")[0].insertBefore(loader, editor.document.$.head.firstChild);
        loader.innerHTML = async;
}

var bind_pages = function(dialog)
{
    var pagePath = dialog.getContentElement('info', 'pagePath');
    var templatePath = dialog.getContentElement('info', 'templatePath');

    var currentPageValue = pagePath.getValue();
    var currentTemplateValue = templatePath.getValue();

    if(currentPageValue != '')
    {
        if(currentPageValue.substr(0, 10) == '{page_url:')
        {
            $('.page_listing a[data-value="'+ currentPageValue +'"]').addClass('selected');
        }
        else
        {
            currentPageValue = currentPageValue.replace('{site_url}', '')
                                               .replace('{path=', '')
                                               .replace('}', '');

            $('.page_listing a[data-url="'+ currentPageValue +'"]').addClass('selected');
        }
    }

    $('.page_listing a').not('.expand').click(function(e){
        var id = $(this).attr('data-id');
        var href = $(this).attr('data-url');
        var link_type = $(this).attr('data-type');
        var title = $(this).text();

        $('.page_listing a').removeClass('selected');
        $(this).addClass('selected');

        // Set our field values
        if(link_type == 'template' && $(this).attr('data-taxonomy') != 'yes')
        {
            href = '{path=\''+ href +'\'}';
            templatePath.setValue(href);
        }
        else if(link_type == 'template' && $(this).attr('data-taxonomy') == 'yes')
        {
            href = '{site_url}'+ href;
            pagePath.setValue(href);
        }
        else if(link_type == 'navee')
        {
            // If the first character is a / assume it's a normal EE link type,
            // but remove it and prepend with {site_url}, which is a better way
            // to handle such things.
            if(href.substr(0, 1) == '/')
            {
                href = '{site_url}'+ href.substr(1);
                pagePath.setValue(href);
            }
            else
            {
                pagePath.setValue(href);
            }
        }
        else if(link_type == 'custom')
        {
            pagePath.setValue(href);
        }
        else
        {
            href = wyvern_config.link_type == 'id' ? '{page_url:'+ id +'}' : '{site_url}' + href;
            pagePath.setValue(href);
        }

        // So clicking the link doesn't reload the page
        e.preventDefault();
    });

    $(".page_listing a.expand").toggle(function(e){
        $(this).text(" - ");
        $(this).closest(".item_wrapper").next(".listings").slideDown();
        e.preventDefault();
    }, function(e){
        $(this).text(" + ");
        $(this).closest(".item_wrapper").next(".listings").slideUp();
        e.preventDefault();
    });

    if($('.wyvern_pages_search').length == 0){
        $('#'+ pagePath.domId).find('input').width('60%').after(' <input type="text" class="wyvern_pages_search" name="wyvern_pages_search" placeholder="Search Pages" />');
    }

    wyvern_search = $(".wyvern_pages_search");
    wyvern_search.val( wyvern_search.attr('placeholder') );

    wyvern_search.focus(function(){
        if(wyvern_search.val() == wyvern_search.attr('placeholder')){
            wyvern_search.val('');
        }
    });

    wyvern_search.blur(function(){
        if(wyvern_search.val() == ''){
            wyvern_search.val( wyvern_search.attr('placeholder') );
        }
    });

    $(".wyvern_pages_search").width('25%').keyup(function(){
        val = $(this).val().toLowerCase();
        items = $(".dialog .structure_pages .item_wrapper");
        if(val.length > 2){
            $(".dialog .structure_pages ul").show();
            items.hide();
            items.each(function(){
                text = $(this).find("a").text().toLowerCase();
                if(text.search(val) != -1){
                    $(this).show();
                }
            });
        }
        else
        {
            items.show();
            $('.dialog .structure_pages .listings').hide();
        }
    });
}


var insert_file = function(file, return_to, field_name, return_path_only)
{
    // Yeah, thats right, eval!
    // I could/should redo this as JSON, but this works...
    upload_paths = eval(wyvern_config.upload_paths);

    // EE 2.2 changed the file object property names
    if(wyvern_config.ee_version > 220)
    {
        file.directory = file.upload_location_id;
        file.name = file.file_name;
    }

    for(i = 0; i < upload_paths.length; i++)
    {
        path = upload_paths[i];

        if(file.directory == path.directory)
        {
            break;
        }
    }

    // If we have a url property, its probably an Assets object
    // which already has the full valid path, so just use it.
    if ('url' in file && file.url != '')
    {
        var url = file.url;
    }
    else
    {
        var url = path.url + file.name;
    }

    // 2.2+ removed the dimensions? Add them if present.
    dimensions = file.dimensions ? file.dimensions : '';

    var selectedText = null;

    // If .element is set then we are returning the value to a CKeditor text selection.
    if (return_to && return_to.element) {
        var editor = return_to.ui.editor;
        var selection = editor.getSelection();
        selectedText = selection.getSelectedText();
    }

    // Place the image in the editing window
    if(file.is_image && !return_path_only && !selectedText)
    {
        // var image = '<figure><img src="'+ path.url + file.name +'" '+ dimensions +' /></figure>';
        var image = '<img src="'+ url +'" '+ dimensions +' />';

        // return_to being the editor object
        return_to.insertHtml(image);
    }
    // File isn't an image, and return_path isn't true, then embed it as a link
    else if( !return_path_only)
    {
        // Update the url to be a reference to an EE {filedir_N} tag instead
        var filedir = '{filedir_'+ path.directory +'}';
        url = url.replace(path.url, filedir);

        var link = '<a href="'+ url +'" title="">'+ (selectedText || file.name) +'</a>';

        // return_to being the editor object
        return_to.insertHtml(link);
    }
    // Otherwise we want to only return the path value to a form field
    else if(return_path_only && typeof return_path_only !== 'function')
    {
        // return_to being a jQuery element
        $(return_to).val(url);

        // If the return_path_only var happens to be a valid jQuery object, insert the image.
        // Right now this is expecting it to be an <img> tag
        if(return_path_only.jquery)
        {
            return_path_only.show().attr('src', url);
        }
    }
    // We have a callback
    else if(typeof return_path_only === 'function')
    {
        return_path_only(file);
    }
}

/*
    Assets returns a slightly different object needed for insert_file above
*/
var get_file_object = function(file)
{
    // See if it's an image
    var file_types = ['png', 'jpg', 'gif', 'svg'];

    var extension = file.url.substr(-3);
    var is_image = ($.inArray(extension, file_types) != -1) ? true : false;

    // Assets 1.x
    if (wyvern_config.assets_version == '1')
    {
        var directory = file.path.match(/\{filedir_(\d+)\}/);
        directory = directory[1];

        var file_name = file.path.replace(/\{filedir_(\d+)\}/, '');
    }
    // Assets 2.x
    else
    {
        upload_paths = eval(wyvern_config.upload_paths);

        for(i = 0; i < upload_paths.length; i++)
        {
            path = upload_paths[i];

            if(file.url.indexOf(path.url) !== -1)
            {
                var file_name = file.url.replace(path.url, '');
                var directory = path.directory;
                break;
            }
        }
    }

    var updated = {
        path: file.path,
        url: file.url,
        is_image: is_image,
        file_name: file_name,
        upload_location_id: directory,
        dimensions: file.dimensions ? file.dimensions : ''
    };

    return updated;
}

/*
    field_name and ee_field_id are usually going to be the same, except when field_name
    is a jQuery element, not the field_name string. ee_field_id is used for the upload_prefs.
*/

var bind_file_manager = function(button, return_to, field_name, ee_field_id, return_path_only)
{
    var field_settings = wyvern_config[ee_field_id].upload_prefs;

    if(wyvern_config.file_manager == 'assets')
    {
        $(button).click(function(){
            var sheet = new Assets.Sheet({
                filedirs: field_settings.directory,
                kinds: field_settings.content_type,
                onSelect: function(files)
                {
                    for(i = 0; i < files.length; i++)
                    {
                        file = get_file_object(files[i]);
                        insert_file(file, return_to, field_name, return_path_only);
                    }
                }
            });

            sheet.show();
        });
    }
    else
    {
        if( !wyvern_config.valid_filemanager && !wyvern_config.error_displayed)
        {
            if ($.ee_notice)
            {
                $.ee_notice('Sorry, but the File Manager is not available on this page. Wyvern can not load the File Manager.', 'notice');
            }
            wyvern_config.error_displayed = true;
        }
        else if(wyvern_config.valid_filemanager)
        {
            $(button).each(function()
            {
                if(wyvern_config.ee_version < 220)
                {
                    $.ee_filebrowser.add_trigger($(this), field_name, function(file){
                        insert_file(file, return_to, field_name, return_path_only);
                    });
                }
                else
                {
                    var settings = {
                        directory: field_settings.directory,
                        content_type: field_settings.content_type
                    };

                    $.ee_filebrowser.add_trigger($(this), field_name, settings, function(file){
                        insert_file(file, return_to, field_name, return_path_only);
                    });
                }
            });
        }
    }
}

// Get a CKEDITOR.dialog.contentDefinition object by its ID.
var getById = function(array, id, recurse)
{
    for (var i = 0, item; (item = array[i]); i++)
    {
        if (item.id == id) return item;
        if (recurse && item[recurse])
        {
            var retval = getById(item[recurse], id, recurse);
            if (retval) return retval;
        }
    }

    return null;
};

// TODO: remove these too: <meta content="text/html;charset=UTF-8" http-equiv="Content-Type" />
// TODO: moving 1 image occassionally erases the others, no idea why.

// Found at http://dev.ckeditor.com/ticket/5052
var fixWebkit = function(field)
{
    contents = $(field).find('iframe').contents();

    contents.find('span').each(function()
    {
        var span = $(this);

        if(span.hasClass('Apple-style-span'))
        {
            span.remove();
        }
    });

    // Upon moving an image webkit likes to insert meta tags... wtf?
    contents.find('meta').each(function(){
        $(this).remove();
    });
}

$.fn.getAttributes = function() {
    var attributes = {};

    if(!this.length)
        return this;

    $.each(this[0].attributes, function(index, attr) {
        attributes[attr.name] = attr.value;
    });

    return attributes;
}

var getFrameContents = function(field)
{
   var iFrame =  document.getElementById(field);
   var iFrameBody;
   if ( iFrame.contentDocument )
   { // FF
     iFrameBody = iFrame.contentDocument.getElementsByTagName('body')[0];
   }
   else if ( iFrame.contentWindow )
   { // IE
     iFrameBody = iFrame.contentWindow.document.getElementsByTagName('body')[0];
   }

   return iFrameBody.innerHTML;
}

var moveImages = function(field, editor, textarea)
{
    if(textarea && $(textarea).val())
    {
        var contents = $('<div>'+ $(textarea).val() +'</div>');
    }
    else
    {
        var contents = $(field).find('iframe').contents();
    }

    contents.find('p').each(function()
    {
        var p = $(this);

        var images = $(this).find('img');

        if( images.length > 0 )
        {
            images.each(function()
            {
                $(this).insertBefore(p);

                if(p.text() == "")
                {
                    p.remove();
                }
            });
        }
    });

    // If textarea, send the contents back to it
    if(textarea && $(textarea).val())
    {
        textarea.val(contents.html());
    }
}