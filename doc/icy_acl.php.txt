<?php

// ICY_ACL for plugin 'icy_picture_modify'

// WARNING | This is a PHP file. Wrong editting will break your
// WARNING | Piwigo installation. All lines started by // are comments.

// WARNING | Your Piwigon installation MUST NOT have a user whose
// WARNING | login identity is "owner". See below for a reason.

// When the plugin is working, it will first local default settings from
//
//  PIWIGO_ROOT/plugins/icy_picture_modify/include/icy_acl_default.php
//
// This file is written by plugin's author, which allows any users to
// change properties of their image (for example). You shouldn't modify
// that file, as it is overwritten when the plugin is updated/upgraded.

// After that, the plugin tries to load ACL from the file
//
//        PIWIGO_ROOT / PWG_LOCAL_DIR / config / icy_acl_default.php
//
// (for a normal installation, the PWG_LOCAL_DIR is ./local/). In this
// file you can change default behaviors of the plugin, and/or permissions
// for any users in your installation.

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

$ICY_ACL_DEFAULT =  // Default rules for logged in users
  array(
    // Images user can edit. Please note that 'owner' is a special keywords
    // to specify owner of an image. To support this purpose, your Piwigo
    // system shouldn't have any user whose login is "owner"
    'can_edit_image_of'    => 'owner',  // edit image of their own
    'can_delete_image_of'  => FALSE,    // can't delete image after uploading
    /* categories */
    'can_upload_image_to' => FALSE,             // no place for uploading
    'can_upload_image_to_sub_album' => TRUE,
    'can_associate_image_to' => FALSE,
    'can_associate_image_to_sub_album' => TRUE,
    'can_present_image_to' => FALSE,
    'can_present_image_to_sub_album' => TRUE,

    /* other properties */
    // The plugin can work with or without the plugin 'community'. List
    // of categories to which user can upload will be used by ICY_ACL.
    // Other settings of 'community' are ignored. To completely ignore
    // the plugin 'community', you may used "FALSE" instead of "TRUE"
    // Please note this setting may be overwritten by per-user settings.
    'load_plugin_community' => TRUE
  );

///// PER-USER SETTINGS ////////////////////////////////////////////////

// Settings for user 'ruby'

$ICY_ACL['ruby'] = array(
    /* list of galleries to which they can upload */
    'can_upload_image_to'    => array(12, 123, 312),

    // List of authors whose images are editable by this user
    // To allow user to edit all images, use
    //  'can_edit_image_of'  => "any",
    'can_edit_image_of'      => array('ruby','foobar'),
    'can_associate_image_to' => array(29, 26, 15, 16, 11),

    // list of categories user can change their presentation image
    // The keywords "any" means "all categories"
    'can_present_image_to'   => "any",

    /* 'ruby' can't delete their own images in these galleries */
    'can_delete_image_of' => FALSE,
  );

// Settings for user = 'python'

$ICY_ACL['khoalong'] =  array(
    'can_edit_image_of' => FALSE,
    'load_plugin_community' => FALSE,
  );

///// YOUR SETTINGS ARE ABOVE THIS LINE ////////////////////////////////
?>
