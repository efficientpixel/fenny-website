<?php

if (! defined('WYVERN_VERSION'))
{
    define('WYVERN_VERSION', '1.7.1');
    define('WYVERN_NAME', 'Wyvern');
    define('WYVERN_DESCRIPTION', 'A WYSIWYG editor with native File Browser and Pages/Structure support.');
}

$config['name'] = WYVERN_NAME;
$config['version'] = WYVERN_VERSION;
$config['description'] = WYVERN_DESCRIPTION;
$config['docs_url'] = 'http://boldminded.com/add-ons/wyvern';
$config['nsm_addon_updater']['versions_xml'] = 'http://boldminded.com/versions/wyvern';

/*
    Default buttons and visibility states.

    To add a new button uncomment the corresponding lines below. Change the value to 'yes'
    and it will be selected as visible by default in your toolbar configuration.
    Adding new buttons will not necessarily make them visible in all saved toolbar configurations,
    you will need to resave each configuration before the buttons appear in the entry publish page.

    Newly added/uncommented items below will display with a green background in the toolbar
    editing screen indicating they are a newly introduced option. Once you resave the toolbars
    the green background will disappear.

    Why wouldn't I enable all buttons by default? Well, that is a good question, but there
    is an easy answer. Simplicity. Most of the buttons that are disabled by default are what
    I consider superfluous. Wysiwyg editors provide great power, not all users are deserving
    of such power. Many of the buttons are also features that, in my opinion, shouldn't be
    managed in a textarea field in EE. There are better ways to implement them, for example, form fields.
    Justifying text would be better done with applying a CSS class to the item and handling
    it externally from the editor. These are just my opinions, if you don't agree, then
    we agree to disagree :) Just uncomment the lines below and you're free to use them however you want.
*/
$config['toolbar_buttons'] = array(
    'Bold' => 'yes',
    'Italic' => 'yes',
    'Superscript' => 'yes',
    'Subscript' => 'yes',
    'NumberedList' => 'yes',
    'BulletedList' => 'yes',
    'Blockquote' => 'no',
    'Indent' => 'yes',
    'Outdent' => 'yes',
    'Link' => 'yes',
    'Unlink' => 'yes',
    'Anchor' => 'yes',
    'Image' => 'yes',
    'FileManager' => 'yes',
    'NEW_ROW' => 'no',
    'Flash' => 'no',
    'Video' => 'no',
    'Table' => 'no',
    'HorizontalRule' => 'no',
    'SpecialChar' => 'no',
    'Undo' => 'no',
    'Redo' => 'no',
    'PasteText' => 'no',
    'PasteFromWord' => 'no',
    'Maximize' => 'no',
    'Source' => 'no',
    'Format' => 'yes',
    'Styles' => 'no',
    'Syntaxhighlight' => 'no',

    // 'ShowBlocks' => 'no',

    // 'SpellChecker' => 'no',
    // 'Find' => 'no',
    // 'Replace' => 'no',
    // 'Scayt' => 'no',

    // 'Form' => 'no',
    // 'Checkbox' => 'no',
    // 'Radio' => 'no',
    // 'TextField' => 'no',
    // 'Textarea' => 'no',
    // 'Select' => 'no',
    // 'Button' => 'no',
    // 'ImageButton' => 'no',
    // 'HiddenField' => 'no',

    // 'RemoveFormat' => 'no',
    // 'CreateDiv' => 'no',
    'JustifyLeft' => 'no',
    'JustifyRight' => 'no',
    'JustifyCenter' => 'no',
    'JustifyBlock' => 'no',
    // 'PageBreak' => 'no',
    // 'Iframe' => 'no',
    // 'Font' => 'no',
    // 'FontSize' => 'no',
    // 'TextColor' => 'no',
    // 'BGColor' => 'no'
);