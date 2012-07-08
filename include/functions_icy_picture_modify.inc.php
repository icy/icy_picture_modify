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
  $editable = icy_acl("edit_image_of", $image_id, icy_get_user_owner_of_image($image_id));
  return $editable;
}

/*
 * Return list of visible/uploadable categories
 * @author  icy
 */
function icy_acl_get_data($symbol) {
  global $user, $ICY_ACL;

  // Load ACL setting for this user
  $this_user = $user['username'];
  $my_acl = $ICY_ACL['default'];
  if (array_key_exists($this_user, $ICY_ACL)) {
    $my_acl = array_replace($my_acl, $ICY_ACL[$this_user]);
  }

  // Load ACL setting for the symbol
  if (!array_key_exists($symbol, $my_acl)) {
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
  global $user, $conf;

  $all_categories = array();
  $symbol_categories = array();
  $symbol_settings = NULL;

  // It's always EMPTY array for any kind of guests
  if ($user['id'] == $conf['guest_id']) {
    return $symbol_categories;
  }

  // check if $symbol is valid
  if (!preg_match("/_(to|from)$/", $symbol)) {
    return $symbol_categories;
  }

  $symbol_settings = icy_acl_get_data($symbol);
  if (!$symbol_settings) {
    return $symbol_categories;
  }

  // all known categories in the system
  $query = 'SELECT id FROM '.CATEGORIES_TABLE.';';
  $all_categories = array_unique(array_from_query($query, 'id'));
  $forbidden_categories = explode(',',calculate_permissions($user['id'], $user['status']));

  // ICY_ACL allows user to access all categories. In this case,
  // the plugin 'community' plays an empty role (we just supress it)
  if (icy_acl_symbol_data_wide_open($symbol_settings)) {
    $symbol_categories = $all_categories;
  }
  elseif (is_array($symbol_settings)) {
    $symbol_categories = array_values(array_intersect($symbol_settings, $all_categories));
  }
  else {
    // not wide-open, not an-array. So waht!?
    $symbol_settings = array();
  }

  // Make sure categories are in our sytem
  // remove all forbidden categories from the list
  if (in_array('sub', icy_acl_get_data($symbol))) {
    // FIXME: (get_subcat_ids) requires a 0-based array
    // FIXME: + array(0) is really a trick :) In Piwigo 2.4, (get_subcat_ids)
    // FIXME: will generate NOTICE (SQL syntax error) if $symbol_categories is empty.
    $symbol_categories = array_merge($symbol_categories, get_subcat_ids($symbol_categories + array(0)));
  }
  $symbol_categories = array_diff($symbol_categories, $forbidden_categories);
  return array_values($symbol_categories);
}
/*
 * FIXME: Test if current user is logged in
 * FIXME: $guestowner must be provided explicitly
 *
 * Check if the current user has permission to do something
 * @symbol     Action to be checked
 * @guestdata  Object of the action
 * @guestowner Owner of @guestdata
 *
 * There are two cases of @symbol:
 * - _from/_to: action on an category
 * - _of      : action on the author
 * - others   : boolean flag
 *
 * There are three cases of symbol data
 * - Array of categories (' identities)    [_from/_to]
 *    $guestowner is simply ignored
 * - Array of usernames (list of authors)  [_of]
 *    $guestowner must be specified
 * - Others: {"any", "owner", TRUE, FALSE} [others]
 */
 function icy_acl($symbol, $guestdata = NULL, $guestowner = NULL) {
  global $user, $ICY_ACL, $conf;

  // Load ACL setting for this user
  $this_user = $user['id'];

  if ($user['id'] == $conf['guest_id']) {
    return FALSE;
  }
  elseif (is_admin()) {
    return TRUE;
  }

  $symbol_settings = icy_acl_get_data($symbol);

  // If $symbol_settings is an array
  //
  if (is_array($symbol_settings)) {
    if (icy_acl_symbol_data_wide_open($symbol_settings)) {
      return TRUE;
    }
    elseif (preg_match("/_(to|from)$/", $symbol)) {
      return in_array($guestdata, $symbol_settings);
    }
    elseif (preg_match("/_of$/", $symbol)) {
      $guestowner = icy_get_username_of($guestowner);
      // Replace 'owner' by the $guestowner. For example
      //  array('owner','ruby', 12) => array($guestowner, 'ruby', 12)
      array_walk($symbol_settings,
       create_function('&$val, $key',
         'if ($val == "owner") {$val = "'.$user['username'].'";}'));
      return in_array($guestowner, $symbol_settings);
    }
    else {
      # FIXME: why?
      return FALSE;
    }
  }
  else {
    return is_bool($symbol_settings) ? $symbol_settings: FALSE;
  }
}

function icy_acl_symbol_data_wide_open($symbol_data) {
  return (is_array($symbol_data) and in_array("any", $symbol_data));
}

/*
 * Write some logs for debugging
 * @notes     Data will be written to STDERRR (default)
 *            or to file `<ROOT>/_data/icy.log`
 * @author    icy
 */
function icy_log($st, $stderr = FALSE) {
  if ($stderr === TRUE) {
    $_f_log = "php://stderr";
  }
  else {
    $_f_log = PHPWG_ROOT_PATH.'_data/icy.log';
  }

  $_f_handle = fopen($_f_log, 'a');
  if ($_f_handle) {
    $new_line = "\n";
    fwrite($_f_handle, "piwigo/icy_picture_modify: $st". $new_line );
    if ($stderr !== TRUE) {
      fclose($_f_handle);
    }
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

  #! icy_log("icy_get_user_id_from_name: map userid <= username: $user_name <= $user_id");
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
  #! icy_log("icy_get_user_owner_of_image: image_id, added_by = $image_id, $owner");
  return $owner ? $owner : 0;
}

/*
 * Return the username from user_id
 */
function icy_get_username_of($user_id) {
  if (!preg_match(PATTERN_ID, $user_id))
    bad_request('invalid user identifier');

  $query = '
SELECT username
  FROM '.USERS_TABLE.'
  WHERE id = '.$user_id.'
  LIMIT 1
;';

  list($username) = pwg_db_fetch_row(pwg_query($query));
  #! icy_log("icy_get_username_of: user_id, user_name = $user_id, $username");
  return $username;
}

/*
 * Check if a plugin is enabled
 * @plugin_name   name of the plugin
 * @author        icy
 */
function icy_plugin_enabled($plugin_name) {
  $return = false;

  $query = '
SELECT count(id)
  FROM '.PLUGINS_TABLE.'
  WHERE id = "'.pwg_db_real_escape_string($plugin_name).'"
  AND state="active"
  LIMIT 1
;';

  list($count) = pwg_db_fetch_row(pwg_query($query));
  $return = ($count == 1 ? true : false);

  // we need the file ^^
  if ($plugin_name == "community")
    $return = $return
                and is_file(PHPWG_PLUGINS_PATH
                  .'community/include/functions_community.inc.php');

  return $return;
}

/*
 * Load ICY_ACL configuration from files
 * @author   icy
 */
function icy_acl_load_configuration($force = FALSE) {
  global $ICY_ACL;
  $conf_path = PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'config/icy_acl.zml';

  if (($force == FALSE)
      and isset($ICY_ACL['default'])
      and isset($_SESSION['icy_picture_modify_acl_mtime'])
      and ($_SESSION['icy_picture_modify_acl_mtime'] == filemtime($conf_path))) {
    #! icy_log("icy_acl_load_configuration: configuration is up-to-date");
    return FALSE;
  }

  $ICY_ACL = icy_zml_parser(<<<EOF
default:
  edit_image_of: owner
  delete_image_of:
  upload_image_to: sub
  moderate_image: no
  create_gallery_to: sub
  associate_image_to:
  present_image_to: sub
EOF
);

  if (file_exists($conf_path)) {
    #! icy_log("icy_acl_load_configuration: now loading ACL from $conf_path");
    $ICY_ACL = array_replace($ICY_ACL, icy_zml_parser(file($conf_path)));
    $_SESSION['icy_picture_modify_acl_mtime'] = filemtime($conf_path);
  }

  return TRUE;
}

/*
 * Return array of variable from a `.zml` array  / string
 * Syntax of the `.zml` file can be found in `doc/zaml.md`
 * @author icy
 */
function icy_zml_parser($data) {
  $acl = array();
  $author = 'default';
  $acl[$author] = array();

  if (is_string($data)) {
    $data = preg_split("/[\r\n]/", $data);
  }

  foreach($data as $line) {
    # AUTHOR:
    if (preg_match('/^([^[:space:]:]+):$/', $line, $gs)) {
      $author = trim($gs[1]);
      if (! array_key_exists($author, $acl)) {
        $acl[$author] = array();
      }
      continue;
    }

    # AUTHOR: @REFERENCE
    if (preg_match('/^([^[:space:]:]+):[[:space:]]+@([^[:space:]:]+)$/', $line, $gs)) {
      $ref_author = trim($gs[2]);
      if (!array_key_exists($ref_author, $acl)) {
        continue;
      }
      $author = trim($gs[1]);
      if (! array_key_exists($author, $acl)) {
        $acl[$author] = array();
      }
      $acl[$author] = array_replace($acl[$ref_author], $acl[$author]);
    }

    # <two spaces> KEY: [VALUE]
    if (preg_match('/  ([^:]+):(.*)$/', $line, $gs)) {
      $key = $gs[1];
      $val = trim($gs[2]);
      if (in_array($val, array("","false","no"))) {
        $val = FALSE;
      }
      elseif (in_array($val, array("yes", "true"))) {
        $val = TRUE;
      }
      else {
        $val = array_unique(preg_split("/[[:space:],:;]+/", $val));
      }
      $acl[$author][$key] = $val;
    }

    # Other line is ignored :)
  }
  return $acl;
}

/*
 * Overwrite the ACl setings from community plugin
 * @author: icy
 */
function icy_acl_fix_community($force = FALSE) {
  global $user, $_SESSION;

  if (!icy_plugin_enabled("community")) {
    return TRUE;
  }

  require_once(PHPWG_PLUGINS_PATH.'community/include/functions_community.inc.php');

  # <community_support>
  $cache_key = community_get_cache_key();
  if (!isset($cache_key))  {
    $cache_key = community_update_cache_key();
  }

  if (($force == FALSE)
      and isset($_SESSION['community_user_permissions'])
      and isset($_SESSION['community_user_permissions']['icy_acl_fixed'])) {
    #! icy_log("icy_fix_community_acl: the fix is up-to-date " . print_r($_SESSION['community_user_permissions'], true));
    return TRUE;
  }

  # icy_log("WARNING: icy_fix_community_acl: the fix is out-of-date. will fix it again");
  # </community_support>

  $return = array(
    'create_categories' => array(),
    'upload_categories' => array(),
    'permission_ids' => array(),
    );

  $return['upload_whole_gallery'] = icy_acl_symbol_data_wide_open(icy_acl_get_data("upload_image_to"));
  $return['create_whole_gallery'] = icy_acl_symbol_data_wide_open(icy_acl_get_data("create_gallery_to"));
  $return['upload_categories'] = icy_acl_get_categories("upload_image_to");
  $return['create_categories'] = icy_acl_get_categories("create_gallery_to");
  $return['permission_ids'] = array();
  $return['icy_acl_fixed'] = 1;

  $_SESSION['community_user_permissions'] = $return;
  $_SESSION['community_cache_key'] = $cache_key;
  $_SESSION['community_user_id'] = $user['id'];
}


if (!function_exists('array_replace')) {
  function array_replace() {
    $array=array();
    $n=func_num_args();
    while ($n-- >0) $array+=func_get_arg($n);
    return $array;
  }
}
?>
