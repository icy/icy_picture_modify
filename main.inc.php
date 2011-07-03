<?php
/*
Plugin Name: Icy Modify Picture
Version: 1.0.0
Description: Allow users to modify users they uploaded
Plugin URI: https://github.com/icy/icy_picture_modify
Author: icy
Author URI: http://metakyanh.sarovar.org/
*/

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

# Should be ./plugins/icy_picture_modify/
define('ICY_PICTURE_MODIFY_PATH' , PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
include_once(ICY_PICTURE_MODIFY_PATH.'include/functions_icy_picture_modify.inc.php');

add_event_handler('loc_end_section_init', 'icy_picture_modify_section_init');
function icy_picture_modify_section_init()
{
  global $tokens, $page;

  if ($tokens[0] == 'icy_picture_modify')
  {
    $page['section'] = 'icy_picture_modify';
  }
}

add_event_handler('loc_end_index', 'icy_picture_modify_index');
function icy_picture_modify_index()
{
  global $page;

  if (isset($page['section']) and $page['section'] == 'icy_picture_modify')
  {
    include(ICY_PICTURE_MODIFY_PATH.'icy_picture_modify.php');
  }
}

// provide the link to modify the picture
add_event_handler('loc_begin_picture', 'icy_picture_modify_loc_begin_picture');
function icy_picture_modify_loc_begin_picture()
{
  global $conf, $template, $page, $user;
  if ((!is_admin()) and icy_check_image_owner($page['image_id'], $user['id']))
  {
    $url_admin =
      get_root_url().'index.php?/icy_picture_modify'
      .'&amp;cat_id='.(isset($page['category']) ? $page['category']['id'] : '')
      .'&amp;image_id='.$page['image_id'];

    $template->assign(
      array(
        'U_ADMIN' => $url_admin,
        )
      );
  }
}

?>
