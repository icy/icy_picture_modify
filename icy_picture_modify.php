<?php
/*
 * Purpose: Main actions (modify, delete, update pictures)
 * Author : Piwigo, icy
 * License: GPL2
 * Note   : The source is based on the `picture_modify.php` in Piwigo
 */

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
if (!defined('ICY_PICTURE_MODIFY_PATH')) die('Hacking attempt!');

require_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
require_once(ICY_PICTURE_MODIFY_PATH.'include/functions_icy_picture_modify.inc.php');

// <ADMIN_ONLY>
if (is_admin())
{
  if (icy_image_exists($_GET['image_id']))
  {
    if (version_compare(PHPWG_VERSION, '2.4.0', '<')) {
      $url = get_root_url().'admin.php?page=picture_modify&amp;image_id=';
    }
    else {
      $url = get_root_url().'admin.php?page=photo-';
    }

    $url.= $_GET['image_id'];

    if (isset($_GET['cat_id']) and ! empty($_GET['cat_id'])) {
      $url .=  '&amp;cat_id='.$_GET['cat_id'];
    }
    redirect_http($url);
  }
  else
  {
    // FIXME: language support ^^
    bad_request('invalid picture identifier');
  }
}
// </ADMIN_ONLY>

global $template, $conf, $user, $page, $lang, $cache, $ICY_ACL;

// <load_from_admin.php>
$page['errors'] = array();
$page['infos']  = array();
$page['warnings']  = array();
// </load_from_admin.php>

icy_acl_load_configuration();

// +-----------------------------------------------------------------------+
// |                             check permission                          |
// +-----------------------------------------------------------------------+

// <CHECK_IF_IMAGE_ID_IS_VALID>
// redirect users to the index page or category page if 'image_id' isn't provided
if (!isset($_GET['image_id']))
{
  if (isset($_GET['cat_id']))
  {
    redirect_http(get_root_url().'?/category/'.$_GET['cat_id']);
  }
  else
  {
    // FIXME: $_SESSION['page_infos'] = array(l10n('Permission denied'));
    redirect_http(make_index_url());
  }
}
// </CHECK_IF_IMAGE_ID_IS_VALID>

// FIXME: check and then !?
check_input_parameter('cat_id', $_GET, false, PATTERN_ID);
check_input_parameter('image_id', $_GET, false, PATTERN_ID);

// Return if the image isn't editable
if (!icy_acl("edit_image_of", $_GET['image_id']))
{
  $url = make_picture_url(
      array(
        'image_id' => $_GET['image_id'],
        'cat_id' => isset($_GET['cat_id']) ? $_GET['cat_id'] : ""
      )
    );
  redirect_http($url);
}

// Update the page sessions
// FIXME: why?
if (isset($_SESSION['page_infos']))
{
  $page['infos'] = array_merge($page['infos'], $_SESSION['page_infos']);
  unset($_SESSION['page_infos']);
}

// <find writable categories>
// FIXME: Why is this needed?
$my_categories = array_from_query('SELECT category_id FROM '
                        .IMAGE_CATEGORY_TABLE.';', 'category_id');

########################################################################
# ACTION => :delete_image ##############################################
########################################################################

if (isset($_GET['delete'])
      and icy_acl("delete_image_of", $_GET['image_id']))
{
  check_pwg_token();

  delete_elements(array($_GET['image_id']), true);

  invalidate_user_cache();

  // where to redirect the user now?
  //
  // 1. if a category is available in the URL, use it
  // 2. else use the first reachable linked category
  // 3. redirect to gallery root

  if (isset($_GET['cat_id']) and !empty($_GET['cat_id']))
  {
    redirect(
      make_index_url(
        array(
          'category' => get_cat_info($_GET['cat_id'])
          )
        )
      );
  }

  $query = '
SELECT category_id
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE image_id = '.$_GET['image_id'].'
;';

  $authorizeds = array_intersect($my_categories,
    array_from_query($query, 'category_id'));

  foreach ($authorizeds as $category_id)
  {
    redirect(
      make_index_url(
        array(
          'category' => get_cat_info($category_id)
          )
        )
      );
  }

  redirect(make_index_url());
}

// +-----------------------------------------------------------------------+
// |                      replace image by a new one                       |
// +-----------------------------------------------------------------------+

// NOTE: The following code mainly comes from the main code of the plugin
// NOTE: `photo_date` (reference file is: plugins/photo_update/admin.php)

if (isset($_FILES['photo_update'])
    and icy_acl("replace_image_of", $_GET['image_id']))
{
  include_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');

  if ($_FILES['photo_update']['error'] !== UPLOAD_ERR_OK)
  {
    $error_message = file_upload_error_message($_FILES['photo_update']['error']);

    array_push(
      $page['errors'],
      $error_message
      );
  }
  else
  {
    add_uploaded_file(
      $_FILES['photo_update']['tmp_name'],
      $_FILES['photo_update']['name'],
      null,
      null,
      $_GET['image_id']
      );

    $page['photo_update_refresh_thumbnail'] = true;

    array_push(
      $page['infos'],
      l10n('The photo was updated')
      );

     invalidate_user_cache();
  }
}

// +-----------------------------------------------------------------------+
// |                          synchronize metadata                         |
// +-----------------------------------------------------------------------+

// ACTION => synchronize_image_metadata
// This includes other sub-actions and other permissions
//  (tag update, timestamp updated, ...)

if (isset($_GET['sync_metadata']))
{
  $query = '
SELECT path
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$_GET['image_id'].'
;';

  if(version_compare(PHPWG_VERSION, '2.4.0', '<')) {
    list($path) = pwg_db_fetch_row(pwg_query($query));
    update_metadata(array($_GET['image_id'] => $path));
  }
  else {
    sync_metadata(array( intval($_GET['image_id'])));
  }

  array_push($page['infos'], l10n('Metadata synchronized from file'));
}

// +-----------------------------------------------------------------------+
// |                          update informations                          |
// +-----------------------------------------------------------------------+

// first, we verify whether there is a mistake on the given creation date
if (isset($_POST['date_creation_action'])
    and 'set' == $_POST['date_creation_action'])
{
  if (!is_numeric($_POST['date_creation_year'])
    or !checkdate(
          $_POST['date_creation_month'],
          $_POST['date_creation_day'],
          $_POST['date_creation_year'])
    )
  {
    array_push($page['errors'], l10n('wrong date'));
  }
}

if (isset($_POST['submit']) and count($page['errors']) == 0)
{
  $data = array();
  $data{'id'} = $_GET['image_id'];
  $data{'name'} = $_POST['name'];
  $data{'author'} = $_POST['author'];
  $data['level'] = $_POST['level'];

  if ($conf['allow_html_descriptions'])
  {
    $data{'comment'} = @$_POST['description'];
  }
  else
  {
    $data{'comment'} = strip_tags(@$_POST['description']);
  }

  if (isset($_POST['date_creation_action']))
  {
    if ('set' == $_POST['date_creation_action'])
    {
      $data{'date_creation'} = $_POST['date_creation_year']
                                 .'-'.$_POST['date_creation_month']
                                 .'-'.$_POST['date_creation_day'];
    }
    else if ('unset' == $_POST['date_creation_action'])
    {
      $data{'date_creation'} = '';
    }
  }

  // FIXME: why mass_updates here ? Used with a simple array?
  mass_updates(
    IMAGES_TABLE,
    array(
      'primary' => array('id'),
      'update' => array_diff(array_keys($data), array('id'))
      ),
    array($data)
    );

  // time to deal with tags
  $tag_ids = array();
  if (!empty($_POST['tags']))
  {
    $tag_ids = get_tag_ids($_POST['tags']);
  }
  set_tags($tag_ids, $_GET['image_id']);

  array_push($page['infos'], l10n('Photo informations updated'));
}

// +-----------------------------------------------------------------------+
// |                              associate                                |
// +-----------------------------------------------------------------------+
// associate the element to other categories than its storage category
//

// SUB-ACTION => associate_image_to_gallery

if (isset($_POST['cat_associate'])) {

  $_categories = array_intersect($_POST['cat_associate'],
                    icy_acl_get_real_value("associate_image_to"));

  // let's first break links with all albums but their "storage album"
  // copied from Piwigo 2.4
  $query = '
DELETE '.IMAGE_CATEGORY_TABLE.'.*
  FROM '.IMAGE_CATEGORY_TABLE.'
    JOIN '.IMAGES_TABLE.' ON image_id=id
  WHERE image_id = '.$_GET['image_id'].'
    AND (storage_category_id IS NULL OR storage_category_id != category_id)
;';

  pwg_query($query);

  if (! empty($_categories) ) {
    associate_images_to_categories(array($_GET['image_id']), $_categories);
    invalidate_user_cache();
  }
}

// SUB-ACTION => dissociate_image_from_gallery

// +-----------------------------------------------------------------------+
// |                              representation                           |
// +-----------------------------------------------------------------------+

// SUB-ACTION => select the element to represent the given categories
// FIXME: select or elect?

if (isset($_POST['cat_elected']))
{
  $_categories = icy_acl_get_real_value("present_image_to");
  $datas = array();
  $update_cache = false;

  # List of categories has this picture as thumbnail
  $query = '
  SELECT id
    FROM '.CATEGORIES_TABLE.'
    WHERE representative_picture_id = '.$_GET['image_id'].'
  ;';
  $represent_options_selected = array_intersect($_categories, array_from_query($query, 'id'));

  # Make this pictures as thumbnail for $input_categories
  $input_categories = array_intersect($_POST['cat_elected'], $_categories);
  if (count($input_categories) > 0) {
    foreach ($input_categories as $category_id) {
      array_push($datas,
                 array('id' => $category_id,
                       'representative_picture_id' => $_GET['image_id']));
    }
    $fields = array('primary' => array('id'),
                    'update' => array('representative_picture_id'));
    mass_updates(CATEGORIES_TABLE, $fields, $datas);
    $update_cache = true;
  }

  # And set random thubnail for others
  $random_thumbs = array_diff($represent_options_selected, $input_categories);
  if (count($random_thumbs) > 0) {
    set_random_representant($random_thumbs);
    $update_cache = true;
  }

  if ($update_cache) {
    invalidate_user_cache();
  }
}

// +-----------------------------------------------------------------------+
// |                             tagging support                           |
// +-----------------------------------------------------------------------+

// FIXME: tag is always updatable?

if (version_compare(PHPWG_VERSION, '2.2.5', '<')) {
  $q_tag_selection = "tag_id, name AS tag_name";
  $q_tags = 'id AS tag_id, name AS tag_name';
}
else {
  $q_tag_selection = "tag_id AS id, name";
  $q_tags = 'id, name';
}

$query = '
SELECT
    '.$q_tag_selection.'
  FROM '.IMAGE_TAG_TABLE.' AS it
    JOIN '.TAGS_TABLE.' AS t ON t.id = it.tag_id
  WHERE image_id = '.$_GET['image_id'].'
;';
$tag_selection = get_taglist($query);

$query = '
SELECT
    '.$q_tags.'
  FROM '.TAGS_TABLE.'
;';
$tags = get_taglist($query);

// retrieving direct information about picture
$query = '
SELECT *
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$_GET['image_id'].'
;';
$row = pwg_db_fetch_assoc(pwg_query($query));

// the physical storage directory contains the image
$storage_category_id = null;
if (!empty($row['storage_category_id']))
{
  $storage_category_id = $row['storage_category_id'];
}

$image_file = $row['file'];

// +-----------------------------------------------------------------------+
// |                             template init                             |
// +-----------------------------------------------------------------------+

# Special thanks to Oscar on Piwigo Forum
# See also http://piwigo.org/forum/viewtopic.php?pid=136917
# FIXME: Don't use absolute value for height|width. Where to get options?
if (icy_plugin_enabled("FCKEditor")) {
  set_fckeditor_instance($areas = 'description', $toolbar = 'Basic', $width = '80%', $height = '200px');
}

$template->set_template_dir(ICY_PICTURE_MODIFY_PATH.'template/');
$template->set_filenames(array('icy_picture_modify' => 'icy_picture_modify.tpl'));

$admin_url_start = get_root_url().'index.php?/icy_picture_modify';
$admin_url_start.= '&amp;image_id='.$_GET['image_id'];
$admin_url_start.= isset($_GET['cat_id']) ? '&amp;cat_id='.$_GET['cat_id'] : '';

if (isset($page['photo_update_refresh_thumbnail']) and $page['photo_update_refresh_thumbnail']) {
  $thumbnail_signature = "?".time();
} else {
  $thumbnail_signature = "";
}

if (function_exists('get_thumbnail_url')) {
  $template->assign('TN_SRC', get_thumbnail_url($row) . $thumbnail_signature);
} else {
  // This completely replaces (get_thumbnail_url) since 2.5.0
  $template->assign('TN_SRC', DerivativeImage::thumb_url($row) . $thumbnail_signature);
}

$template->assign(
  array(
    'ICY_PICTURE_MODIFY_PATH' => ICY_PICTURE_MODIFY_PATH,
    'ICY_ROOT_PATH' => realpath(dirname(PHPWG_PLUGINS_PATH)),
    'tag_selection' => $tag_selection,
    'tags' => $tags,

    'PATH'=>$row['path'],

    'NAME' =>
      isset($_POST['name']) ?
        stripslashes($_POST['name']) : @$row['name'],

    'DIMENSIONS' => @$row['width'].' * '.@$row['height'],

    'FILESIZE' => @$row['filesize'].' KB',

    'REGISTRATION_DATE' => format_date($row['date_available']),

    'AUTHOR' => htmlspecialchars(
      isset($_POST['author'])
        ? stripslashes($_POST['author'])
        : @$row['author']
      ),

    'DESCRIPTION' =>
      htmlspecialchars( isset($_POST['description']) ?
        stripslashes($_POST['description']) : @$row['comment'] ),
    )
  );

$template->assign(
  array(
    'U_SYNC' => $admin_url_start.'&amp;sync_metadata=1',
    'F_ACTION' => get_root_url() . get_query_string_diff(array('sync_metadata'))
  )
);

if (icy_acl("delete_image_of", $_GET['image_id'])) {
  $template->assign(
    'U_DELETE', $admin_url_start.'&amp;delete=1&amp;pwg_token='.get_pwg_token()
  );
}

if (icy_acl("replace_image_of", $_GET['image_id'])) {
  $template->assign('U_UPDATE_PHOTO', "YES");
}

if (array_key_exists('has_high', $row) and $row['has_high'] == 'true')
{
  $template->assign(
    'HIGH_FILESIZE',
    isset($row['high_filesize'])
        ? $row['high_filesize'].' KB'
        : l10n('unknown')
    );
}

// image level options
$selected_level = isset($_POST['level']) ? $_POST['level'] : $row['level'];
$template->assign(
    array(
      'level_options'=> get_privacy_level_options(),
      'level_options_selected' => array($selected_level)
    )
  );

// creation date
unset($day, $month, $year);

if (isset($_POST['date_creation_action'])
    and 'set' == $_POST['date_creation_action'])
{
  foreach (array('day', 'month', 'year') as $varname)
  {
    $$varname = $_POST['date_creation_'.$varname];
  }
}
else if (isset($row['date_creation']) and !empty($row['date_creation']))
{
  list($year, $month, $day) = explode('-', $row['date_creation']);
}
else
{
  list($year, $month, $day) = array('', 0, 0);
}


$month_list = $lang['month'];
$month_list[0]='------------';
ksort($month_list);

$template->assign(
    array(
      'DATE_CREATION_DAY_VALUE' => $day,
      'DATE_CREATION_MONTH_VALUE' => $month,
      'DATE_CREATION_YEAR_VALUE' => $year,
      'month_list' => $month_list,
      )
    );

$query = '
SELECT category_id, uppercats
  FROM '.IMAGE_CATEGORY_TABLE.' AS ic
    INNER JOIN '.CATEGORIES_TABLE.' AS c
      ON c.id = ic.category_id
  WHERE image_id = '.$_GET['image_id'].'
;';
$result = pwg_query($query);

while ($row = pwg_db_fetch_assoc($result))
{
  $name =
    get_cat_display_name_cache(
      $row['uppercats'],
      get_root_url().'index.php?/icy_picture_modify&amp;cat_id=',
      false
      );

  if ($row['category_id'] == $storage_category_id)
  {
    $template->assign('STORAGE_CATEGORY', $name);
  }
  else
  {
    $template->append('related_categories', $name);
  }
}

########################################################################
# jump to link #########################################################
########################################################################

//
// 1. find all linked categories that are reachable for the current user.
// 2. if a category is available in the URL, use it if reachable
// 3. if URL category not available or reachable, use the first reachable
//    linked category
// 4. if no category reachable, no jumpto link

$query = '
SELECT category_id
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE image_id = '.$_GET['image_id'].'
;';

// list of categories (OF THIS IMAGE) to which the user can access
$authorizeds = array_intersect($my_categories,
  array_from_query($query, 'category_id'));

// if current category belongs to list of authorized categories
// we simply provide link to that category
if (isset($_GET['cat_id'])
    and in_array($_GET['cat_id'], $authorizeds))
{
  $url_img = make_picture_url(
    array(
      'image_id' => $_GET['image_id'],
      'image_file' => $image_file,
      'category' => $cache['cat_names'][ $_GET['cat_id'] ],
      )
    );
}
// otherwise we provide links to the *first* category in the list
else
{
  foreach ($authorizeds as $category)
  {
    $url_img = make_picture_url(
      array(
        'image_id' => $_GET['image_id'],
        'image_file' => $image_file,
        'category' => $cache['cat_names'][ $category ],
        )
      );
    // FIXME: why the first category is selected?
    break;
  }
}

if (isset($url_img))
{
  $template->assign( 'U_JUMPTO', $url_img );
}

########################################################################
## ASSOCIATION (LINKING IMAGE TO ANOTHER ALBUM) ########################
########################################################################

$_categories = icy_acl_get_real_value("associate_image_to");

if (count($_categories)) {
  $template->assign('U_LINKING_IMAGE', 1);

  $query = '
  SELECT id
    FROM '.CATEGORIES_TABLE.'
      INNER JOIN '.IMAGE_CATEGORY_TABLE.' ON id = category_id
    WHERE image_id = '.$_GET['image_id'] . '
      AND id IN (0'.join(",",$_categories).')';

  // FIMXE: if the image belongs to a physical storage,
  // FIXME: we simply ignore that storage album
  if (isset($storage_category_id)) {
    $query.= ' AND id != '.$storage_category_id;
  }
  $query.= '
  ;';

  $selected_ones = array_from_query($query, 'id');
  if (isset($storage_category_id)) {
    array_push($selected_ones, $storage_category_id);
  }

  $query = '
  SELECT id,name,uppercats,global_rank
    FROM '.CATEGORIES_TABLE.'
      WHERE id IN (0'.join(",",$_categories).')';

  display_select_cat_wrapper($query, $selected_ones, 'associate_options');
}

########################################################################
# PRESENTATION (MAKE IMAGE AS THUMBNAIL FOR SOME ALBUMS) ###############
########################################################################

$_categories = icy_acl_get_real_value("present_image_to");

if (count($_categories) > 0) {
  $template->assign('U_PRESENT_IMAGE', 1);

  $query = '
  SELECT id
    FROM '.CATEGORIES_TABLE.'
    WHERE id IN (0'. join(",", $_categories).')
      AND (representative_picture_id = '.$_GET['image_id'].');';

  $selected_ones = array_from_query($query, 'id');

  $query = '
  SELECT id,name,uppercats,global_rank
    FROM '.CATEGORIES_TABLE.'
      WHERE id IN (0'.join(",",$_categories).')';

  display_select_cat_wrapper($query, $selected_ones, 'represent_options');
}

########################################################################
# TEMPLATE: FINALIZING #################################################
########################################################################

$template->assign_var_from_handle('PLUGIN_INDEX_CONTENT_BEGIN', 'icy_picture_modify');

?>
