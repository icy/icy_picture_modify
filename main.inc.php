<?php
/*
Plugin Name: Icy Modify Picture
Version: 2.0.0
Description: Allow normal users to upload / modify pictures
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=563
Author: icy
Author URI: http://metakyanh.sarovar.org/
License: GPL2
*/

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

define('ICY_PICTURE_MODIFY_PATH' , PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
require_once(ICY_PICTURE_MODIFY_PATH.'include/functions_icy_picture_modify.inc.php');

# Variable declarations ################################################

global $ICY_ACL;

# Hooks declarations ###################################################

add_event_handler('loc_end_section_init', 'icy_picture_modify_section_init');
remove_event_handler('loc_end_index', 'community_index');
add_event_handler('loc_end_index', 'icy_picture_modify_index', 40);

add_event_handler('loc_begin_picture', 'icy_picture_modify_loc_begin_picture');
add_event_handler('init','icy_picture_modify_fix_community_acl', 40);
# add_event_handler('login_success', );

add_event_handler('blockmanager_apply', 'icy_picture_modify_fix_community_acl', 40);
add_event_handler('ws_invoke_allowed', 'icy_picture_modify_fix_community_acl', 40);
add_event_handler('ws_add_methods', 'icy_picture_modify_fix_community_acl', 40);
add_event_handler('community_ws_categories_getList', 'icy_picture_modify_fix_community_acl', 40);
add_event_handler('sendResponse', 'icy_picture_modify_fix_community_acl', 40);

# Hooks definitions ####################################################

function icy_picture_modify_fix_community_acl()
{
  icy_acl_fix_community(icy_acl_load_configuration());
}

function icy_picture_modify_section_init()
{
  global $tokens, $page;

  if ($tokens[0] == 'icy_picture_modify')
  {
    $page['section'] = 'icy_picture_modify';
  }
}

function icy_picture_modify_index()
{
  global $page;

  if (! isset($page['section'])) {
    return TRUE;
  }

  if ($page['section'] == 'icy_picture_modify')
  {
    include(ICY_PICTURE_MODIFY_PATH.'icy_picture_modify.php');
  }
  elseif ($page['section'] == 'add_photos') {
    icy_picture_modify_fix_community_acl(TRUE);
    include(ICY_PICTURE_MODIFY_PATH.'add_photos.php');
  }
}

// provide the link to modify the picture
// FIXME: Why use $page['image_id'] instead of $_GET['image_id']
function icy_picture_modify_loc_begin_picture()
{
  global $conf, $template, $page, $user;

  icy_acl_load_configuration();

  if (icy_acl("edit_image_of",$page['image_id']))
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
