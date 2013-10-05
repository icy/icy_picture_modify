<?php
/*
Plugin Name: Icy Modify Picture
Version: 2.4.4
Description: Allow normal users to upload / modify pictures
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=563
Author: icy
Author URI: http://metakyanh.sarovar.org/
License: GPL2
*/

if (!defined('PHPWG_ROOT_PATH')) {
  die('Hacking attempt!');
}

define('ICY_PICTURE_MODIFY_PATH' , PHPWG_PLUGINS_PATH .'icy_picture_modify/');
require_once(ICY_PICTURE_MODIFY_PATH.'include/functions_icy_picture_modify.inc.php');

# Variable declarations ################################################

global $ICY_ACL;

# Hooks declarations ###################################################

add_event_handler('loc_end_section_init', 'icy_picture_modify_section_init');
add_event_handler('loc_end_index', 'icy_picture_modify_index', EVENT_HANDLER_PRIORITY_NEUTRAL - 10);

add_event_handler('loc_begin_picture', 'icy_picture_modify_loc_begin_picture');
add_event_handler('init','icy_picture_modify_fix_community_acl', EVENT_HANDLER_PRIORITY_NEUTRAL - 10);
# add_event_handler('login_success', );

add_event_handler('blockmanager_apply', 'icy_picture_modify_fix_community_acl', EVENT_HANDLER_PRIORITY_NEUTRAL - 10);
add_event_handler('ws_invoke_allowed', 'icy_picture_modify_fix_community_acl', EVENT_HANDLER_PRIORITY_NEUTRAL - 10);
add_event_handler('ws_add_methods', 'icy_picture_modify_fix_community_acl', EVENT_HANDLER_PRIORITY_NEUTRAL - 10);
add_event_handler('sendResponse', 'icy_picture_modify_fix_community_acl', EVENT_HANDLER_PRIORITY_NEUTRAL - 10);

if (icy_plugin_enabled("community")) {
  remove_event_handler('loc_end_index', 'community_index');
  add_event_handler('community_ws_categories_getList', 'icy_picture_modify_fix_community_acl', EVENT_HANDLER_PRIORITY_NEUTRAL - 10);
}

# Hooks definitions ####################################################

function icy_picture_modify_fix_community_acl()
{
  if (is_admin()) return TRUE;
  icy_acl_fix_community(icy_acl_load_configuration());
}

function icy_picture_modify_section_init()
{
  global $tokens, $page;

  if (is_admin()) return TRUE;

  if ($tokens[0] == 'icy_picture_modify')
  {
    $page['section'] = 'icy_picture_modify';
  }
}

function icy_picture_modify_index()
{
  global $page;

  if (is_admin()) return TRUE;

  if (! isset($page['section'])) {
    return TRUE;
  }

  if ($page['section'] == 'icy_picture_modify')
  {
    require(ICY_PICTURE_MODIFY_PATH.'icy_picture_modify.php');
  }
  elseif ($page['section'] == 'add_photos') {
    require(ICY_PICTURE_MODIFY_PATH.'add_photos.php');
  }
}

// provide the link to modify the picture
function icy_picture_modify_loc_begin_picture()
{
  global $conf, $template, $page, $user;

  if (is_admin()) return TRUE;

  icy_acl_load_configuration();

  if (icy_acl("edit_image_of",$page['image_id']))
  {
    $url_admin =
      get_root_url().'index.php?/icy_picture_modify&amp;'.'image_id='.$page['image_id'];

    if (isset($page['category'])
        and isset($page['category']['id'])
          and ! empty($page['category']['id']))
    {
      $url_admin .= '&amp;cat_id='.$page['category']['id'];
    }

    if (version_compare(PHPWG_VERSION, '2.5.0', '<')) {
      $template->assign('U_ADMIN', $url_admin);
    }
    else {
      // Piwigo 2.5 doesn't support U_ADMIN in the `picture.tpl` and
      // this just makes this plugin sucks. Using new style? Like this?
      // What a boring f***cking style I have to find another way
      // FIXME: + translation here
      $url_admin = "<a href=\"$url_admin\" title=\"Modify information\" class=\"pwg-state-default pwg-button\" rel=\"nofollow\"> <span class=\"pwg-icon pwg-icon-edit\"> </span><span class=\"pwg-button-text\">Edit</span></a>";
      $template->add_picture_button($url_admin, 0);
    }
  }

}

?>
