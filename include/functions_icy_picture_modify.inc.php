<?php
/*
 * Purpose: Provide functions for ICY ACL
 * Author : icy (Anh K. Huynh)
 * License: GPL2
 * Note   : The source is based on the `picture_modify.php` in Piwigo
 */

/*
 * Check if an image does exist
 * @return    bool
 * @image_id  a valid identity of the image
 * @author    icy
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
 * Return value of a variable in ACL. The value may contain some special
 * item (sub, any, owner), so they should be adapted by other methods.
 * In general, the value says there are something in the ACL, but it
 * doesn't say the user has permission on some object.
 *
 * @return An array, NULL or a Boolean value (See ZAML format)
 * @symbol A variable name in ACL
 * @author icy
 * @note: ORder of settings
 *    default < group(s) [merging] < private settings
 */
function icy_acl_get_value($symbol) {
  global $user, $ICY_ACL;

  // Load ACL setting for this user
  $this_user = $user['username'];
  $my_acl = $ICY_ACL['default'];
  $user_groups = icy_get_user_groups($user['id']);

  $group_acl = array();
  foreach ($user_groups as $group) {
    if (array_key_exists($group, $ICY_ACL)) {
      $group_acl = icy_acl_get_the_highest_value($group_acl, $ICY_ACL[$group]);
    }
  }

  $my_acl = array_replace($my_acl, $group_acl);

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
 * Get real values from a value of a symbol in ACL. The purpose
 * of this function is to analyze the value of a symbol, and replace
 * any of special value (sub, owner, ...) but real values
 *
 * @return Array of categories or authors
 * @symbol Any kind of symbol (A variable name in ACL)
 * @author icy
 */
function icy_acl_get_real_value($symbol) {
  global $user, $conf;

  $all_categories = array();
  $symbol_categories = array();
  $symbol_settings = NULL;

  if (icy_acl_is_guest_and_disallowed()) {
    return array();
  }

  // check if $symbol is valid. There are two kinds of variable that
  // can return a list of categories: _to and _from. Any other variable
  // with use the value in ACL setting.
  if (!preg_match("/_(to|from|of)$/", $symbol)) {
    return icy_acl_get_value($symbol);
  }

  // This is only a trick to speed up
  $symbol_settings = icy_acl_get_value($symbol);
  if (empty($symbol_settings)) {
    return array();
  }

  # Type A: _of, will return list of authors

  if (preg_match("/_of$/", $symbol)) {
    if (in_array('owner', $symbol_settings)) {
      array_walk($symbol_settings,
        create_function('&$val, $key',
        'if ($val == "owner") {$val = "'.$user['username'].'";}'));
    }
    return $symbol_settings;
  }

  # Type B: _to/_from, will return list of categories

  // all known categories in the system
  $query = 'SELECT id FROM '.CATEGORIES_TABLE.';';
  $all_categories = array_unique(array_from_query($query, 'id'));
  $forbidden_categories = explode(',',calculate_permissions($user['id'], $user['status']));

  // ICY_ACL allows user to access all categories. In this case,
  // the plugin 'community' plays an empty role (we just supress it)
  if (icy_acl_is_value_open($symbol_settings)) {
    $symbol_categories = $all_categories;
  }
  elseif (is_array($symbol_settings)) {
    $symbol_categories = array_values(array_intersect($symbol_settings, $all_categories));
  }
  else {
    // not wide-open, not an-array. So waht!?
    return array();
  }

  // Make sure categories are in our sytem
  // remove all forbidden categories from the list
  if (in_array('sub', $symbol_settings)) {
    // FIXME: (get_subcat_ids) requires a 0-based array
    // FIXME: + array(0) is really a trick :) In Piwigo 2.4, (get_subcat_ids)
    // FIXME: will generate NOTICE (SQL syntax error) if $symbol_categories is empty.
    $symbol_categories = array_merge($symbol_categories, get_subcat_ids($symbol_categories + array(0)));
  }

  if (! in_array('hard', $symbol_settings)) {
    $symbol_categories = array_diff($symbol_categories, $forbidden_categories);
  }

  return array_values($symbol_categories);
}

/*
 * Check if the current user has permission to do something
 *
 * @symbol     Action to be checked
 * @guestdata  Object of the action ($image_id, $category_id)
 * @return     Boolean
 *
 * There are two cases of @symbol:
 * - _from/_to: action on a $category_id
 * - _of      : action on a $image_id
 * - others   : boolean flag, $guestdata is not used
 *
 * FIXME: Need to use real values instead !!!
 * FIXME: When the API is used to upload images, after the user is
 * FIXME: authenticated, `icy_acl` still detects users as a guest
 */
 function icy_acl($symbol, $guestdata = NULL) {
  global $user, $ICY_ACL, $conf;

  // Load ACL setting for this user
  $this_user = $user['id'];

  if (icy_acl_is_guest_and_disallowed()) {
    return FALSE;
  }
  elseif (is_admin()) {
    return TRUE;
  }

  $symbol_data = icy_acl_get_real_value($symbol);

  if (! preg_match("/_(to|from|of)$/", $symbol)) {
    return is_bool($symbol_data) ? $symbol_data: FALSE;
  }

  if (! is_array($symbol_data) ) {
    return FALSE;
  } elseif (icy_acl_is_value_open($symbol_data)) {
    return TRUE;
  }

  # If there are not settings for this symbol
  if (empty($symbol_data)) {
    return FALSE;
  }

  if (preg_match("/_of$/", $symbol)) {
    $img_userid = icy_get_user_id_of_image($guestdata);
    $guestdata = array(icy_get_username_of($img_userid));
    $guestdata = array_merge($guestdata, icy_get_user_groups($img_userid));
    $guestdata = array_intersect($guestdata, $symbol_data);
    return ! empty($guestdata);
  }
  else {
    return in_array($guestdata, $symbol_data);
  }
}

function icy_acl_is_value_open($symbol_data) {
  return is_admin()
    or (is_array($symbol_data)
        and in_array("any", $symbol_data));
}

/*
 * Write some logs for debugging
 * @notes     Data will be written to STDERRR (default)
 *            or to file `<ROOT>/_data/icy.log`
 * @author    icy
 */
function icy_log($st, $stderr = FALSE, $log_file = "icy.log") {
  if ($stderr === TRUE) {
    $_f_log = "php://stderr";
  }
  else {
    $_f_log = PHPWG_ROOT_PATH . '/_data/' . $log_file;
  }

  $timestamp = date(DATE_RFC2822);

  $_f_handle = fopen($_f_log, 'a');
  if ($_f_handle) {
    fwrite($_f_handle, "$timestamp $st\n");
    if ($stderr !== TRUE) {
      fclose($_f_handle);
    }
  }
}

/*
 * Write user action into file
 */
function icy_action_log($action, $image_id = 0, $status = 'Success', $category_id = '') {
  global $user;
  $_username = $user['username'];
  if ($status == 'Success') {
    $status = "";
  }
  else {
    $status = ' [FAIL]';
  }
  icy_log("$_username, $action$status, image_id = $image_id, category_id = $category_id", FALSE, "icy_action.log");
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
function icy_get_user_id_of_image($image_id) {
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
  #! icy_log("icy_get_user_id_of_image: image_id, added_by = $image_id, $owner");
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

  return ($count == 1 ? true : false);
}

/*
 * Load ICY_ACL configuration from files
 * @author icy
 * @force  Force the new configuration to be loaded from file
 * @return TRUE if new configuration is loaded. FALSE otherwise
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

  # FIXME: We should get Guest UserName from database
  $ICY_ACL = icy_zml_parser(<<<EOF
default:
  edit_image_of: owner
  delete_image_of:
  upload_image_to: sub
  moderate_image: no
  create_gallery_to: sub
  associate_image_to:
  present_image_to: sub
  replace_image_of: owner
  allow_guest: no
guest:
  edit_image_of:
  delete_image_of:
  upload_image_to:
  moderate_image: yes
  create_gallery_to:
  associate_image_to:
  present_image_to:
  replace_image_of:
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
 * @return An array of ACL
 */
function icy_zml_parser($data) {
  $acl = array();
  $author = 'default';
  $acl[$author] = array();

  if (is_string($data)) {
    $data = preg_split("/[\r\n]/", $data);
  }

  foreach($data as $line) {
    $line = rtrim($line);
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
      $author = trim($gs[1]);
      if (! array_key_exists($author, $acl)) {
        $acl[$author] = array();
      }
      if (!array_key_exists($ref_author, $acl)) {
        continue;
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
 * @author icy
 * @force  Force the Community's ACL to be updated
 * @return TRUE
 */
function icy_acl_fix_community($force = FALSE) {
  global $user, $_SESSION;

  if (!icy_plugin_enabled("community")) {
    return TRUE;
  }

  require_once(PHPWG_PLUGINS_PATH.'community/include/functions_community.inc.php');

  $cache_key = community_get_cache_key();
  if (!isset($cache_key))  {
    $cache_key = community_update_cache_key();
  }

  if (($force == FALSE)
      and isset($_SESSION['community_user_permissions'])
      and isset($_SESSION['community_user_permissions']['icy_acl_fixed']))
  {
    return TRUE;
  }

  $return = array(
    'create_categories' => array(),
    'upload_categories' => array(),
    'permission_ids' => array(),
    );

  $return['upload_whole_gallery'] = icy_acl_is_value_open(icy_acl_get_value("upload_image_to"));
  $return['create_whole_gallery'] = icy_acl_is_value_open(icy_acl_get_value("create_gallery_to"));
  $return['upload_categories'] = icy_acl_get_real_value("upload_image_to");
  $return['create_categories'] = icy_acl_get_real_value("create_gallery_to");
  $return['permission_ids'] = array();
  $return['icy_acl_fixed'] = 1;
  /* Since Community 2.6 (!?), this setting is required for user */
  $return['community_enabled'] = 20140201;

  $_SESSION['community_user_permissions'] = $return;
  $_SESSION['community_cache_key'] = $cache_key;
  $_SESSION['community_user_id'] = $user['id'];

  return TRUE;
}

/*
 * Get list of all groups of a user
 * @user_id: identity of the user
 * @return : array of group names
 * @note:
 *  - FIXME: the output is not cached!!! so it's a bit slow
 *  - example sql query:
 *    select name from piwigo_groups
 *        left join piwigo_user_group as g
 *      on id = g.group_id and g.user_id = 8;
 */

function icy_get_user_groups($user_id) {
  if (!preg_match(PATTERN_ID, $user_id))
    bad_request('invalid user identifier');

  $groups = array();

  $query = '
SELECT name
  FROM '.GROUPS_TABLE.'
  JOIN '. USER_GROUP_TABLE. ' as g
  ON id = g.group_id AND g.user_id = '.$user_id.'
;';

  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    array_push($groups, $row['name']);
  }

  return $groups;
}

/* Merge two values in ACL. The highest value will win
 * @input: array of ACL atom
 * @output: new array of ACL atom
 * @note: there are only two types in ACL (boolean, array)
 * @example:
 *
 *  $acl = (:do_it => false, :members => array(1,2,3))
 *  $bcl = (:do_it => true,  :members => any)
 *
 *  $acl '+' $bcl => (:do_it => true, :members => array(1,2,3,any))
 */
function icy_acl_get_the_highest_value() {
  $acl = array();
  $n = func_num_args();
  while ($n-- > 0) {
    $bcl = func_get_arg($n);
    foreach (array_keys($bcl) as $key) {
      if (array_key_exists($key, $acl)) {
        if (is_bool($acl[$key])) {
          $acl[$key] = ($acl[$key] || $acl[$key]);
        }
        else {
          $acl[$key] = array_merge($acl[$key], $bcl[$key]);
        }
      }
      else {
        $acl[$key] = $bcl[$key];
      }
    }
  }
  return $acl;
}

function icy_acl_is_guest_and_disallowed() {
  global $ICY_ACL, $user, $conf;

  if ($user['id'] == $conf['guest_id']) {
    if (isset($ICY_ACL['default'])
        and isset($ICY_ACL['default']['allow_guest'])
        and ($ICY_ACL['default']['allow_guest'] == "true"))
    {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }
  return FALSE;
}

if (!function_exists('array_replace')) {
  /*
   * Replace items in an array with items in another array
   * @return An array
   */
  function array_replace() {
    $array=array();
    $n=func_num_args();
    while ($n-- >0) $array+=func_get_arg($n);
    return $array;
  }
}
?>
