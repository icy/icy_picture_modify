# PURPOSE

  This format is used to describe advance ACL for Piwigo installation.
  This ACL is supported by the plugin `icy_picture_modify`.

# FORMAT

  Format details

  * Name:     ZAML (not YAML)
  * Version:  1.0.0
  * Author:   icy (Anh K. Huynh)

# SYNTAX

  There are only three types of lines in the format. They follows
  a rule: a late one overwrites any similar mean of the previous lines.

  * user name specification   _(meanful)_
  * variable assignment       _(meanful)_
  * all other non-sense lines _(meanless)_

## User name specification

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

## Variable assignment

  A variable assignment has two parts: a variable name and its value.
  On the line, the variable name must be started by two spaces,
  followed by a colon (:) and its value.

  You can specify any variable name, though most of them are not
  officialy supported by `icy_picture_modify` (see EXAMPLES).

  To specify variable's value, you can use

  * nothing, or any spaces      _(meaning: `FALSE`)_
  * `no`, `false`               _(meaning: `FALSE`)_
  * `yes`, `true`               _(meaning: `TRUE`)_
  * Any other string. This string will be considered as an array
    whose its delimiter is any space, semi-colon (`;`), colon (`:`)
    or colon (`,`), or any combination of them. Each item of array
    is often a number, a user name, `sub` or `any` or `owner`.

    * number:     specify category identity
    * user name:  specify any Piwigo user name
    * `sub`:      action can work on sub categories
    * `any`:      action can work on any categories
    * `owner`:    action can work on any image of current user

## Meanless lines

  Any other lines are considered as comment. They are meanless.

# PARSER

  Simple parser can be found in the function `icy_zml_parser` in
  ```
    plugins/icy_picture_modify/include/ \
      functions_icy_picture_modify.inc.php
  ```

# EXAMPLE

## Default settings

  Default settings for all users (except guest user). These settings are
  put in the code of the plugin `icy_picture_modify` so you may not have
  to specify them. All of the following lines mean:

  *  User can edit their own images (image they uploaded)
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
