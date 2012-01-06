<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based photo gallery                                    |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2011 Piwigo Team                  http://piwigo.org |
// | Copyright(C) 2003-2008 PhpWebGallery Team    http://phpwebgallery.net |
// | Copyright(C) 2002-2003 Pierrick LE GALL   http://le-gall.net/pierrick |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

/*
 * Check if the current image is editable by the current user. The input
 * data $image_id and $user_id must be validated befored being used here.
 * @return bool
 * @author icy
 *
*/
function icy_check_image_owner($image_id)
{
  global $user;
  icy_log("icy_check_image_owner: current user, is_owner = "
            . $user['id']. ", "
            . icy_get_user_owner_of_image($image_id));
  return $user['id'] == icy_get_user_owner_of_image($image_id);
}

/*
 * Check if an image does exist
 * @return bool
 * @author icy
 *
*/
function icy_image_exists($image_id)
{
  if (!preg_match(PATTERN_ID, $image_id))
  {
    return false;
  }
  $query = '
SELECT COUNT(id)
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$image_id.'
;';
  list($count) = pwg_db_fetch_row(pwg_query($query));
  return ($count > 0 ? true: false);
}

/*
 * Check if an image is editable by current user
 * @icy_acl   access rules (provided by icy module)
 * @image_id  identity of the image
 * @return    boolean value
 * @author    icy
 */
function icy_image_editable($image_id) {
  $editable = true;

  if (is_admin()) return $editable;

  $editable = ($editable and icy_check_image_owner($image_id));

  icy_log("icy_image_editable: image_id, editable = $image_id, $editable");
  return $editable;
}

/*
 * Return list of visible/uploadable categories
 * @author  icy
 */
function icy_acl_get_data($symbol) {
  global $user, $ICY_ACL, $ICY_ACL_DEFAULT;

  // Load ACL setting for this user
  $this_user = $user['username'];
  $my_acl = $ICY_ACL_DEFAULT;
  if (array_key_exists($this_user, $ICY_ACL)) {
    $my_acl = array_replace($my_acl, $ICY_ACL[$this_user]);
  }

  // Load ACL setting for the symbol
  if (!array_key_exists($symbol, $my_acl)) {
    icy_log("icy_acl: symbol is invalid => $symbol");
    return NULL;
  }

  return $my_acl[$symbol];
}

/*
 *
 * visible: upload, assosiate,...
 * @symbol    must be ended by "_to" or "_from"
 */
function icy_acl_get_categories($symbol) {
  global $user;

  $all_categories = array();
  $symbol_categories = array();
  $symbol_settings = NULL;

  icy_log("icy_acl_get_categories: inspect data for symbol => $symbol");

  // check if $symbol is valid
  if (!preg_match("/_(to|from)$/", $symbol)) return $symbol_categories;
  $symbol_settings = icy_acl_get_data($symbol);
  icy_log("icy_acl_get_categories: data for symbol $symbol => "
            . print_r($symbol_settings, true));
  if (!$symbol_settings) {
    return $symbol_categories;
  }

  // all known categories in the system
  $query = 'SELECT category_id FROM '.IMAGE_CATEGORY_TABLE.';';
  $all_categories = array_from_query($query, 'category_id');
  $forbidden_categories = explode(',',calculate_permissions($user['id'], $user['status']));

  // ICY_ACL allows user to access all categories. In this case,
  // the plugin 'community' plays an empty role (we just supress it)
  if (icy_acl_symbol_data_wide_open($symbol_settings)) {
    icy_log("icy_acl_get_categories: symbol is too open => $symbol");
    $symbol_categories = $all_categories;
  }
  elseif (is_array($symbol_settings)) {
    if ($symbol == "can_upload_image_to") {
      // <community_support>
      // See also (plugins/community/include/functions_community.inc.php)
      $community_user_permissions = array(
          "upload_categories" => array(), // can_upload_image_to
          "create_categories" => array(), // NOTE: isn't ported to ICY_ACL
          "upload_whole_gallery" => FALSE // FIXME: isn't ported to ICY_ACL
          // NOTE: attribute 'recursive' isn't ported
        );
      if (icy_plugin_community_is_loadable()) {
        $community_user_permissions = community_get_user_permissions($user['id']);
        icy_log("icy_acl_get_categories: will get extra categories from plugin community => "
                . print_r($community_user_permissions, true));
      }
      // </community_support>
      $symbol_categories = $symbol_settings
                    + $community_user_permissions['upload_categories'];
    }
    else {
      icy_log("icy_acl_get_categories: generic symbol => $symbol; intial categories => "
              . print_r($symbol_categories, true));
      $symbol_categories = $symbol_settings;
    }
  }
  else {
    // not wide-open, not an-array. So waht!?
    // $symbol_settings = array();
  }

  // Make sure categories are in our sytem
  // remove all forbidden categories from the list
  // TODO: support negative directive in ICY_ACL
  $symbol_categories = array_values(array_intersect($symbol_categories, $all_categories));
  if (icy_acl_get_data("can_work_on_sub_album") == TRUE) {
    icy_log("icy_acl_get_categories: user is allowed to work on sub-album. size(before) => "
      . count($symbol_categories) . ", data => ". print_r($symbol_categories, true));
    // FIXME: (get_subcat_ids) requires a 0-based array
    $symbol_categories = $symbol_categories + get_subcat_ids($symbol_categories);
    icy_log("icy_acl_get_categories: and size(after) => " . count($symbol_categories));
  }
  $symbol_categories = array_diff($symbol_categories, $forbidden_categories);
  icy_log("icy_acl_get_categories: final categories => " . print_r($symbol_categories, true));
  return array_values($symbol_categories);
}
/*
 * Example
 *  Test if user can upload image to a category
 *    icy_acl("can_upload_image_to", 12, $owner_of_12th_category)
 *
 *  Here he owner of object (12) is provided. There are some cases
 *
 *    1.  ACL['can_upload_image_to'] = array(12, 14, 15)
 *          return: TRUE
 *
 *    2a. ACL['can_upload_image_to'] = array(13, 14, 15)
 *          return: FALSE
 *
 *    2b. ACL['can_upload_image_to'] = array(13, 14, 14, $other_user)
 *          return: FALSE
 *
 *    2c. ACL['can_upload_image_to'] = array($owner_of_12th_category)
 *        ACL['can_upload_image_to'] = array('owner', 14, 15)
 *          return: TRUE if $this_user == $owner_of_12th_category
 *
 *  Test if user can edit image of some other user
 *    icy_acl("can_edit_image_from", 'khoalong')
 *      return: TRUE if ACL contains 'khoalong' (or 'owner')
 *
 * FIXME: Test if current user is logged in
 * FIXME: $guestowner must be provided explicitly
 */
function icy_acl($symbol, $guestdata = NULL, $guestowner = NULL) {
  global $user, $ICY_ACL, $ICY_ACL_DEFAULT;

  // Load ACL setting for this user
  $this_user = $user['username'];
  $symbol_settings = icy_acl_get_data($symbol);

  // Check if $this_user has enough permission
  if (is_array($symbol_settings)) {
    if (in_array($guestdata, $symbol_settings)) {
      return TRUE;
    }
    else {
      // $guestdata doesn't exist in $symbol_settings. We need to check
      // if there is 'owner' in the setting. If 'yes', $this_user has
      // enough permission if they are also the $guestowner.
      if ($guestowner === $this_user) {
        // Replace 'owner' by the $guestowner. For example
        //  array('owner','ruby', 12) => array($guestowner, 'ruby', 12)
        array_walk($symbol_settings,
         create_function('&$val, $key',
           'if ($val == "owner") {$val = "'.$guestowner.'";}'));

        return in_array($this_user, $symbol_settings);
      }
      else {
        // FIXME: ???
        return FALSE;
      }
    }
  }
  else {
    if ($symbol_settings === 'owner') {
      return ($guestowner === $this_user);
    }
    elseif (icy_acl_symbol_data_wide_open($symbol_settings)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}

function icy_acl_symbol_data_wide_open($symbol_data) {
  return ($symbol_data === TRUE) or ($symbol_data === "any");
}

/*
 * Routine to test if (icy_acl) works as designed
 *
function icy_acl_test() {
  return FALSE;
}
 *
 */

/*
 * Write some logs for debugging
 * @notes     Data will be written to <ROOT>/_data/icy.log
 * @author    icy
 */
function icy_log($st) {
  $_f_log = PHPWG_ROOT_PATH.'_data/icy.log';
  $_f_handle = fopen($_f_log, 'a');
  if ($_f_handle) {
    fwrite($_f_handle, $st . "\n");
    fclose($_f_handle);
  }
  else {
    // FIXME: How we can report if we can't write to log file?
  }
}

/*
 * Get UserId from their UserName
 * @user_name   username as string
 * @author      icy
 */
function icy_get_user_id_from_name($user_name) {
  $user_name = pwg_db_real_escape_string($user_name);

  $query = '
SELECT id
  FROM '.USERS_TABLE.'
  WHERE username = "'.$user_name.'"
  LIMIT 1
;';

  list($user_id) = pwg_db_fetch_row(pwg_query($query));

  // FIXME: Is this the best way?
  if ($user_id == NULL) $user_id = 0;

  icy_log("icy_get_user_id_from_name: map userid <= username: $user_name <= $user_id");
  return $user_id;
}

/*
 * Rerturn the owner id of an image
 * @author    icy
 * @image_id  identity of the image
 */
function icy_get_user_owner_of_image($image_id) {
  // FIXME: Clean this up!!!
  if (!preg_match(PATTERN_ID, $image_id))
    bad_request('invalid picture identifier');

  $query = '
SELECT added_by
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$image_id.'
  LIMIT 1
;';

  list($owner) = pwg_db_fetch_row(pwg_query($query));

  icy_log("icy_get_user_owner_of_image: image_id, added_by = $image_id, $owner");

  return $owner ? $owner : 0;
}

/*
 * Check if a plugin is enabled
 * @plugin_name   name of the plugin
 * @author        icy
 */
function icy_plugin_enabled($plugin_name) {
  $query = '
SELECT count(id)
  FROM '.PLUGINS_TABLE.'
  WHERE id = "'.pwg_db_real_escape_string($plugin_name).'"
  AND state="active"
  LIMIT 1
;';

  list($count) = pwg_db_fetch_row(pwg_query($query));
  icy_log("icy_is_plugin_enabled: plugin, enabled = $plugin_name, $count");
  return ($count == 1 ? true : false);
}

function icy_plugin_community_is_loadable() {
  return icy_acl("load_plugin_community") and icy_plugin_enabled("community");
}
?>
