<?php

// get the version from config.php
require PATH_THIRD.'wyvern/config.php';

$lang = array(

"wyvern_module_name" => $config['name'],
"wyvern_module_description" => $config['description'],

"css_path" =>
"Path to custom CSS file
<p style=\"font-weight: normal;\">This is the path to the CSS file used to define custom styles used within the CKEditor field.
 <b>If you define a CSS file, a JavaScript file is required below.</b></p>
<p style=\"font-weight: normal;\">The default location is: <code>/themes/third_party/wyvern/wysiwyg.css</code></p>",

"js_path" =>
"Path to custom JavaScript file
<p style=\"font-weight: normal;\">This is the path to the JavaScript file used to create the select menu in the toolbar. It will normally contain definitions to the styles defined in the CSS file above.</p>
<p style=\"font-weight: normal;\">The default location is: <code>/themes/third_party/wyvern/wysiwyg.js</code></p>",


"image_paragraphs" =>
"Wrap images in &lt;p&gt; tags?
<p style=\"font-weight: normal;\">By default, CKEditor wraps all elements, including images, in some type of HTML container, and &lt;p&gt;
is the default tag. In most cases you don't want images wrapped in &lt;p&gt; tags, but in the event you do,
you can enable this option.</p><p>Note: when you click the Source button within CKEditor, images will be wrapped in a &lt;p&gt; tag, when
leaving Source view the image will be unwrapped.</p>
<p style=\"color: red;\">This feature is no longer supported. <a href=\"http://boldminded.com/news/one-step-back\"  style=\"color: red; text-decoration: underline;\">Read this for more information</a>.</p>",

"encode_ee_tags" =>
"Encode EE Tags?
<p style=\"font-weight: normal;\">This converts all curly braces to entities, thus the tags will not parse. <b>This does not convert {page_url:N}, {path='group/template'} or {filedir_N} variables</b>.</p>",

"extra_config" =>
"Extra CKEditor Config Options
<p style=\"font-weight: normal;\">Set any additional CKEditor configuration options here. A list of possible options can be <a href=\"http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html\">found in the CKEditor API</a>. Set the options in the following format, one option per line. You can also use <code>{site_url}</code> as a variable to link extra files.</p>
<p>
<code style=\"font-weight: normal;\">
<pre>
bodyClass: 'wysiwyg'
basicEntities: true
templates_files: ['{site_url}wysiwyg/wysiwyg-templates.js']
</pre>
</code>
</p>",

"obfuscate_email" =>
"Obfuscate email addresses?
<p style=\"font-weight: normal;\">This will obfuscate (encode) email addresses when used within Wyvern fields. <b>This will automatically link email addresses too</b>.</p>",

"toolbar_configuration" => 
"Toolbar Configuration
<p style=\"font-weight: normal;\">Here you can create and save multiple toolbar configurations for use in your custom fields. Below is a list of saved toolbars. To create a new one, 
simply select which buttons will appear and in which order, then choose a name for the configuration and save it. It will appear in the list below
and can be edited or deleted and used in multiple Wyvern fields.</p>",

"extra_plugins" =>
"Extra Plugins 
<p style=\"font-weight: normal;\">Select which extra CKEditor plugins you would like loaded.</p>",

"no_extra_plugins" =>
"No plugins found.",

"toolbar" =>
"Toolbar",

"display_height" =>
"Height of editing window (in pixels)",

"preference" =>
"Preference",

"setting" =>
"Setting",

"parse_order_title" => "{page_url:N} Variable Parsing Order",

"parse_order_info" => "<p><b>Parsing early</b> adds all the page variables to the global_vars array and can be used 
    inside or outside of an <code>{exp:channel:entries}</code> tag. global_vars do not work when entered into a custom field such as Wyvern.
    To fix this, additional parsing needs to be done in each <code>{exp:channel:entries}</code> tag.</p>
    <p><b>Parsing late</b> adds all the page variables to the User Defined Global Variables. An advantage of this is 
    it doesn't require extra parsing in an <code>{exp:channel:entries}</code> tag loop and can be used virtually anywhere in your 
    templates. A disadvantage is it adds entries to the User Defined Global Variables list. If you have 500 pages, 
    you will see 500 entries in the User Defined Global Variables list, thus adding clutter to an area 
    clients could potentially see. NOTE: You must save at least 1 entry before late parsing will work.</p>
    <p>The {page_url:N} variables have been successfully tested on a site with 800+ entries with no noticeable 
    performance loss. If you notice any performance loses, you may want to consider switching to Late Parsing.
    You can optionally disable parsing completely for debugging or performance testing.</p>",
    

"template_setting_title" =>
"Linkable Templates
<p style=\"font-weight: normal;\">Select which templates you want to display in the Link Dialog Template list.</p>",

"display_height" =>
"Field Display Height",

"toolbar_buttons" => 
"Toolbar Buttons",

"wymeditor_style" => 
"Add WYMeditor display style?
<p style=\"font-weight: normal;\">This will add dashed borders and visual markers to HTML containers.</p>",

"default_link_type" =>
"Default Link Type
<p style=\"font-weight: normal;\">Select what the default link type is in the CKEditor Link Dialog when it opens.</p>",

"file_manager" =>
"File Manager
<p style=\"font-weight: normal;\">Select which file manager to use. You can use the native EE File Manager, or Assets.</p>",

"upload_prefs" => 
"Allowed upload directories",

"google_fonts" =>
"Google Web Fonts
<p style=\"font-weight: normal;\">Paste the value of the <i>family</i> parameter found in your Google Fonts link code.
<br /><br />For example: <code>&lt;link href='http://fonts.googleapis.com/css?family=<b>Convergence|Lancelot|Arapey</b>' rel='stylesheet' type='text/css'&gt;</code>
<br /><br />Note, you will still need to add the code to the css file defined above as described by the <i>Integrate the fonts into your CSS</i> section
in your Google Web Fonts collection page.</p>",
    
"typekit" => 
"Typekit
<p style=\"font-weight: normal;\">Enter your Typekit Kit ID. You can find it in your embed code.
<br /><br />For example: <code>&lt;script type=\"text/javascript\" src=\"http://use.typekit.com/<b>abcdefg</b>.js\"&gt;&lt;/script&gt;</code>
<br /><br />Note, you will still need to add the code to the css file defined above as described by the <i>Using fonts in CSS</i> section
in your Kit Editor.</p>",

"auto_link_urls" =>
"Automatically turn URLs and email addresses into links?",

"channel_allow_img_urls" =>
"Allow image URLs in Wyvern fields?",

// IGNORE
''=>'');

