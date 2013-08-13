# TABLE OF CONTENTS

  Major topics in this document

  * Purpose
  * Format information
  * Changes
  * Syntax
    * User specification
    * Group specification
    * Variable assigment
    * Known variables
    * Meanless lines (comments)
    * Guest account
  * Important Notes
  * Parser
  * Example
    * Default settings
    * A normal user
    * Reference
    * Group settings
    * User with multiple groups
    * Using group as reference

# PURPOSE

  This format is used to describe advanced ACL for Piwigo installation.
  This ACL is supported by the plugin `icy_picture_modify`.

# FORMAT INFORMATION

  Format details

  * Name:     ZAML (not YAML)
  * Version:  1.1.1
  * Author:   icy (Anh K. Huynh)

# CHANGES

  * 1.1.1: guest support
  * 1.1.0: add group support

# SYNTAX

  There are only three types of lines in the format. They follows
  a rule: a late one overwrites any similar mean of the previous lines.

  * user name specification   _(meanful)_
  * variable assignment       _(meanful)_
  * all other non-sense lines _(meanless)_

## User specification

  A user name (as in your Piwigo system) is put on a single line,
  ended by a colon (:), and optionally followed by `reference`.

  A `reference` is any user name started by `@`. The current user
  will have all settings of the `reference` user; those settings will
  be overwritten by other assignment for the current user. If the
  `reference` user has't any settings at the time it is declared,
  the reference will be ignored.

  * *Note 1:* The special name `default` is used specially by the format,
    and you should not use it for any user in your Piwigo system.
  * *Note 2:* User name must not contain a colon (:)

## Group specification

  As long as your group name doens't have space in its name, you can
  specify settings for that group in the same way as you do for user.
  (User is a special group that has only one user; that's why we don't
  have to bring mess to the syntax.)

  See NOTES for some tips.

## Variable assignment

  There are only two data types in ZAML: `Boolean` and `Array`.

  A variable assignment has two parts: a variable name and its value.
  On the line, the variable name must be started by two spaces,
  followed by a colon (:) and its value.

  You can specify any variable name, though most of them are not
  officialy supported by `icy_picture_modify` (see EXAMPLES).

  To specify variable's value, you can use

  * nothing, or any spaces      _(meaning: `FALSE` or `EMPTY ARRAY`)_
  * `no`, `false`               _(meaning: `FALSE`)_
  * `yes`, `true`               _(meaning: `TRUE`)_
  * Any other string. This string will be considered as an array
    whose its delimiter is any space, semi-colon (`;`), colon (`:`)
    or colon (`,`), or any combination of them. Each item of array
    is often a number, a user name, `sub` or `any` or `owner`.

    * number:     specify category identity
    * user name:  specify any Piwigo user name
    * `sub`:      action can work on sub categories
    * `hard`:     allow `sub` to acccess forbidden categories
    * `any`:      action can work on any categories
    * `owner`:    action can work on any image of current user

## Known variables

  The following variables are known

  * `edit_image_of`:      Default: *owner*.
                          Array of authors/groups whose images are editable by the current user
  * `replace_image_of`:   Default: *owner*.
                          Array of authors/groups whose images can be replaced by a new version
                            by the current user.
  * `delete_image_of`:    Default: *empty array*.
                          Array of authors/groups whose images are deletable by the current user

  * `upload_image_to`:    Default: *empty array*.
                          Array of albums to which the current user can upload new image
  * `create_gallery_to`:  Default: *empty array*.
                          Array of albums in which the current user can create new albums
  * `associate_image_to`: Default: *empty array*.
                          Array of albums to which the current user can add their image
  * `present_image_to`:   Default: *empty array*.
                          Array of albums for which the current user can make thumbnail
  * `moderate_image`:     Default: *false*.
                          If `true`, new image will be in PENDING mode (that needs
                            review from administrator) after it is uploaded

  * `allow_guest`:        Default: *false*.
                          If `true`, guest user is allowed to edit image.
                          This value is only fetched from settings for account `default`.
                          See the section GUEST ACCOUNT for details.

## Meanless lines (comments)

  Any other lines are considered as comment. They are meanless.

## Guest account

  By default, the guest account isn't allowed to edit picture. To allow
  them to edit or upload image, you must set `allow_guest` in the settings
  for the section `default` in the ACL file. This is the only place to do
  that.

  To increase security, even if the guest support is turned on, all of
  their settings need to be set up explicitly. This is due to the fact
  that some default settings for guest account is provided in the source
  code of the plugin.

  See some examples in `doc/` for details.

  Please note you shouldn't put guest account in any group. By doing that
  your gallery may be altered by any account. Be careful. You've been warned.

# IMPORTANT NOTES

  There are some important notes

  * As `default` is used as special keywords, you should not have any
    user / group that has the name `default` in Piwigo system.
  * A group shouldn't use name of any user. This means that if you have
    any user `my_user`, you should not use `my_user` as a group name.
  * A group should not contain any space in its name. Any restrictions
    for users are applied to group's name.
  * If a user belongs to one or more groups, they highest permissions
    from those groups are used. The order of permissions for a user is

      * default settings by the plugin _(you can't change these values)_
      * default settings in your configuration _(if any)_
      * highest settings from groups _(if any)_
      * private settings the user _(if any)_

    See the EXAMPLES for details.

# PARSER

  Simple parser can be found in the function `icy_zml_parser` in

  ```
    plugins/icy_picture_modify/include/ \
      functions_icy_picture_modify.inc.php
  ```

  This simple parse has nothing to do with group settings.
  See an example in the function `icy_acl_get_value`, though.

# EXAMPLE

## Default settings

  Default settings for all users (except guest user). These settings are
  put in the code of the plugin `icy_picture_modify` so you may not have
  to specify them. All of the following lines mean:

  *  User can edit their own images _(image they uploaded)_
  *  User can't delete any image
  *  User can upload image to sub categories. Unfortunately, there is no
  *  category specified, so they can't upload images to any category.
  *  All images are showed on the Piwigo system after they are uploaded;
  *  they will not require any moderation work
  *  User can create new gallery inside sub categories. As there isn't
  *  any category specified, they can't create any new gallery.
  *  User can't link an image to any album
  *  User can't create representation for any album

```
default:
  edit_image_of: owner
  delete_image_of:
  upload_image_to: sub
  moderate_image: no
  create_gallery_to: sub
  associate_image_to:
  present_image_to:
  allow_guest: no
```

## A normal user

  This user can upload image to category whose identity is `34`;
  He can edit image of his own and of the user `example_user0`;
  He can link images to any album in Piwigo system;
  He can create new gallery in the parent category `34` and its sub
  category; He can also make representation for any album
  Finally, he can't delete any images.

```
example_user1:
  upload_image_to: sub, 34
  edit_image_of:  example_user0, owner
  associate_image_to: any
  create_gallery_to: 34, sub
  present_image_to: any
  delete_image_of:
```

## Reference

  This user `example_user2` will have all settings of `example_user1`,
  except he can delete images of `example_user1` and his own

```
example_user2: @example_user1
  delete_image_of: owner, example_user1
```

## Groups settings

  'Friends' is a group name in Piwigo administrator management console.
  Any user in this group can edit their own image, but they can not
  present their images to any albums.

  'Authors' is another group: user in this group can edit any image of
  their own, or image of any user belongs to the 'Friends' group; and
  they can upload image to the album 15 and its sub-albums, and can also
  present images to those albums.

```
Friends:
  edit_image_of: owner
  present_image_to: no

Authors:
  edit_image_of: Friends, owner
  upload_image_to: sub, 15
  present_image_to: 15, sub
```

## User with multiple groups

  If the user 'special_user' belongs to two groups 'Authors' and 'Friends',
  the highest permissions in these two groups are used. And if there is
  any private settings for this user, those settings will replace any
  known groups settings for them.

  Alternatively, this user 'special_user' can be set up by

```
special_user:
  edit_image_of: owner, any
  present_image_to: yes
  upload_image_to: sub, 15
```

## Using group as reference

  Even if the user 'example_user3' doesn't belong to group 'Friends',
  you can use reference to load all settings from that group

```
example_user3: @Friends
```
