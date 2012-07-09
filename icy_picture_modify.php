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
    $url = get_root_url().'admin.php?page=picture_modify';
    $url.= '&amp;image_id='.$_GET['image_id'];
    $url.= isset($_GET['cat_id']) ? '&amp;cat_id='.$_GET['cat_id'] : '';
    // FIXME: What happens if a POST data were sent within admin uid?
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

#! icy_log("body from icy_picture_modify.php");
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
if (!icy_acl("edit_image_of", $_GET['image_id'], icy_get_user_id_of_image($_GET['image_id'])))
{
  $url = make_picture_url(
      array(
        'image_id' => $_GET['image_id'],
        'cat_id' => isset($_GET['cat_id']) ? $_GET['cat_id'] : ""
      )
    );
  // FIXME: $_SESSION['page_infos'] = array(l10n('Permission denied'));
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
$my_categories = array();
// FIXME: delete this line ^^
$my_categories = array_from_query('SELECT category_id FROM '
                        .IMAGE_CATEGORY_TABLE.';', 'category_id');

// +-----------------------------------------------------------------------+
// |                             delete photo                              |
// +-----------------------------------------------------------------------+

// ACTION => :delete_image

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
// |                          synchronize metadata                         |
// +-----------------------------------------------------------------------+

// ACTION => synchronize_image_metadata
// This includes other sub-actions and other permissions
//  (tag update, timestamp updated, ...)

if (version_compare(PHPWG_VERSION, '2.4.0', '<')
    and isset($_GET['sync_metadata']))
{
  $query = '
SELECT path
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$_GET['image_id'].'
;';
  list($path) = pwg_db_fetch_row(pwg_query($query));
  update_metadata(array($_GET['image_id'] => $path));

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

if (isset($_POST['associate'])
    and isset($_POST['cat_dissociated'])
    and (count($_POST['cat_dissociated']) > 0)
  )
{
  $_categories = array_intersect($_POST['cat_dissociated'],
                    icy_acl_get_real_values("associate_image_to"));
  //! $_categories = array_filter($_categories,
  //!    create_function('$item', 'return icy_acl("associate_image_to", $item);'));

  associate_images_to_categories(array($_GET['image_id']), $_categories);
  invalidate_user_cache();
}

// SUB-ACTION => dissociate_image_from_gallery

// dissociate the element from categories (but not from its storage category)
if (isset($_POST['dissociate'])
    and isset($_POST['cat_associated'])
    and count($_POST['cat_associated']) > 0
  )
{

  $_categories = array_intersect($_POST['cat_associated'],
                    icy_acl_get_real_values("associate_image_to"));
  //! $_categories = array_filter($_categories,
  //!    create_function('$item', 'return icy_acl("associate_image_to", $item);'));

  $query = '
DELETE FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE image_id = '.$_GET['image_id'].'
    AND category_id IN (0'.join(',', $_categories).')
';

  pwg_query($query);
  update_category($_categories);
  invalidate_user_cache();
}

// +-----------------------------------------------------------------------+
// |                              representation                           |
// +-----------------------------------------------------------------------+

// SUB-ACTION => select the element to represent the given categories
// FIXME: select or elect?

if (isset($_POST['elect'])
    and isset($_POST['cat_dismissed'])
    and count($_POST['cat_dismissed']) > 0
  )
{
  $datas = array();
  $arr_dimissed = array_intersect($_POST['cat_dismissed'],
                        icy_acl_get_real_values("present_image_to"));

  if (count($arr_dimissed) > 0)
  {
    foreach ($arr_dimissed as $category_id)
    {
      array_push($datas,
                 array('id' => $category_id,
                       'representative_picture_id' => $_GET['image_id']));
    }
    $fields = array('primary' => array('id'),
                    'update' => array('representative_picture_id'));
    mass_updates(CATEGORIES_TABLE, $fields, $datas);
    invalidate_user_cache();
  }
}

// SUB-ACTION => dismiss the element as representant of the given categories

if (isset($_POST['dismiss'])
    and isset($_POST['cat_elected'])
    and count($_POST['cat_elected']) > 0
  )
{
  $arr_dismiss = array_intersect($_POST['cat_elected'],
                        icy_acl_get_real_values("present_image_to"));
  if (count($arr_dismiss) > 0)
  {
    set_random_representant($arr_dismiss);
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

$template->set_template_dir(ICY_PICTURE_MODIFY_PATH.'template/');
$template->set_filenames(array('icy_picture_modify' => 'icy_picture_modify.tpl'));

$admin_url_start = get_root_url().'index.php?/icy_picture_modify';
$admin_url_start.= '&amp;image_id='.$_GET['image_id'];
$admin_url_start.= isset($_GET['cat_id']) ? '&amp;cat_id='.$_GET['cat_id'] : '';

$template->assign(
  array(
    'ICY_PICTURE_MODIFY_PATH' => ICY_PICTURE_MODIFY_PATH,
    'ICY_ROOT_PATH' => realpath(dirname(PHPWG_PLUGINS_PATH)),
    'tag_selection' => $tag_selection,
    'tags' => $tags,

    'PATH'=>$row['path'],

    'TN_SRC' => get_thumbnail_url($row),

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

if (version_compare(PHPWG_VERSION, '2.4.0', '<')) {
  $template->assign(
    array(
      'U_SYNC' => $admin_url_start.'&amp;sync_metadata=1',
      'F_ACTION' => get_root_url() . get_query_string_diff(array('sync_metadata'))
    )
  );
}

if (icy_acl("delete_image_of", $_GET['image_id'])) {
  $template->assign(
    'U_DELETE', $admin_url_start.'&amp;delete=1&amp;pwg_token='.get_pwg_token()
  );
}

# If there are some categories to present image to
if (! empty(icy_acl_get_real_values("present_image_to"))) {
  $template->assign('U_PRESENT_IMAGE', 1);
}

# If there are some categories to associate image to
if (! empty(icy_acl_get_real_values("associate_image_to"))) {
  $template->assign('U_LINKING_IMAGE', 1);
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

// jump to link
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

$_categories = icy_acl_get_real_values("associate_image_to");
// Select list of categories this image is associcated to
$query = '
SELECT id,name,uppercats,global_rank
  FROM '.CATEGORIES_TABLE.'
    INNER JOIN '.IMAGE_CATEGORY_TABLE.' ON id = category_id
  WHERE image_id = '.$_GET['image_id'] . '
    AND id IN (0'.join(",",$_categories).')';
// FIMXE: if the image belongs to a physical storage,
// FIXME: we simply ignore that storage album
if (isset($storage_category_id))
{
  $query.= '
    AND id != '.$storage_category_id;
}
$query.= '
;';
display_select_cat_wrapper($query, array(), 'associated_options');

$result = pwg_query($query);
$associateds = array(-1);
if (isset($storage_category_id))
{
  array_push($associateds, $storage_category_id);
}
while ($row = pwg_db_fetch_assoc($result))
{
  array_push($associateds, $row['id']);
}
  // FIXME: Also display some forbidden presentations
$query = '
SELECT id,name,uppercats,global_rank
  FROM '.CATEGORIES_TABLE.'
  WHERE id NOT IN ('.implode(',', $associateds).')
  AND id IN (0'.join(",", $_categories).')
;';
display_select_cat_wrapper($query, array(), 'dissociated_options');

// display list of categories for representing
$_categories = icy_acl_get_real_values("present_image_to");
$query = '
SELECT id,name,uppercats,global_rank
  FROM '.CATEGORIES_TABLE.'
  WHERE representative_picture_id = '.$_GET['image_id'].'
    AND id IN (0'. join(",", $_categories).')
;';
display_select_cat_wrapper($query, array(), 'elected_options');
$query = '
SELECT id,name,uppercats,global_rank
  FROM '.CATEGORIES_TABLE.'
  WHERE id IN (0'. join(",", $_categories).')
    AND (representative_picture_id != '.$_GET['image_id'].'
    OR representative_picture_id IS NULL)
;';
display_select_cat_wrapper($query, array(), 'dismissed_options');

//----------------------------------------------------------- sending html code

$template->assign_var_from_handle('PLUGIN_INDEX_CONTENT_BEGIN', 'icy_picture_modify');

?>
