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
function icy_check_image_owner($image_id, $user_id = 0)
{
  if (!preg_match(PATTERN_ID, $image_id))
  {
    bad_request('invalid picture identifier');
  }
  if (!preg_match(PATTERN_ID, $user_id))
  {
    bad_request('invalid category identifier');
  }

  $query = '
SELECT COUNT(id)
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$image_id.'
  AND added_by = '.$user_id.'
;';

  list($count) = pwg_db_fetch_row(pwg_query($query));

  return ($count > 0 ? true: false);
}

/*
 * Check if an image does exist
 * @return bool
 * @author icy
 *
*/
function icy_does_image_exist($image_id)
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
function icy_image_editable($icy_acl, $image_id) {

  return false;
}

/*
 * Update the ACL by loading known data from plugin 'community'
 * @icy_acl   current ACL
 * @priority  community will be overwritten (0) or not (1)
 * @return    ACL merged with community support
 * @author    icy
 * @notes     community supports will be overwritten by default ACL
 */
function icy_include_community_acl($icy_acl, $priority = 0) {
  return $icy_acl;
}
?>
