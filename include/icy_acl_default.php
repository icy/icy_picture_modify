<?php
/*
 * @Purpose: Provide default ICY_ACL
 * @Author : Anh K. Huynh
 * @Date   : 2012 Jan 07
 * @License: GPL2
 */

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $ICY_ACL, $ICY_ACL_DEFAULT;

$ICY_ACL = array(); // Don't ever change this
$ICY_ACL_DEFAULT =  // Default rules for logged in users
  array(
    /* image */
    'can_edit_image_of' => 'owner',
    'can_delete_image_of' => FALSE,
    /* categories */
    'can_upload_image_to' => FALSE,
    'can_upload_image_to_sub_album' => TRUE,
    'can_associate_image_to' => FALSE,
    'can_associate_image_to_sub_album' => TRUE,
    'can_present_image_to' => FALSE,
    'can_present_image_to_sub_album' => TRUE,
    /* other properties */
    'load_plugin_community' => TRUE
  );

?>
