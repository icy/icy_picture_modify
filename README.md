## NAME

  `icy_picture_modify` -- Allow user to modify any pictures

## DESCRIPTION

  A piwigo extension that allows users to edit any set of images.
  This extension just works as the `picture_modify.php` for administrator.

  The code of this plugin is based on the original source
  `picture_modify.php` of the Piwigo distribution (version 2.2.3.)

  Advanced ACL is supported since version 1.2.0.

## FEATURES

  Normal users in Piwigo system can delete image or modify image's metadata
  (author, date, tags, description, metada, linking/represented categories).
  They can upload some images to some categories.

  Advanced ACL allows users to work with any set of images / categories.

## USAGE

  * Install and enable this plugin and the plugin `community`.
  * Create and edit ACL file in `local/config/icy_acl.zml` in your Piwigo
    installation. A sample file can be found in `doc/icy_acl.zml.sample`.
    You don't have to modify any settings for the plugin `community`.
    Moverover, any settings of the plugin `community` will be ignored.
  * If you are using a development of the plugin, please clean up the
    obsolete files (See `OBSOLETE FILES` below.)

## OBSOLETE FILES

  The following files are obsolsete. They need to be ported to new format
  and/or to be removed

  * `local/config/icy_acl.php`:
    need to be ported to `local/config/icy_acl.zml`. After your old ACL
    settings are ported to new format, you can safely delete this file;

  * `_data/icy.log`:
    if you are using `icy_picture_modify` version 2.0.0 or higher, you
    can safely remove this file.

## KNOWN PROBLEMS

  * No webUI for ACL editting
  * If an image is replaced by a new version (using plugin `Photo update`)
    the new version is owned by administrator, not the current user.
    Hence the image may not be editable by themself.
  * This plugin doesn't support all known templates. If you are using
    `stripped` or `stripped-galleria`, you may following the instructions
    in this forum post
        http://piwigo.org/forum/viewtopic.php?pid=132380#p132380
  * User can delete an image which is associated to some albums to which
    the user doesn't have permission to write/access. This is true as the
    plugin only checks owner of the image.

## SUPPORT

  To get support, please create new issue at
    https://github.com/icy/icy_picture_modify/issues

## DEVELOPMENT

  The plugin requires a webUI for ACL editting. Unfortunately, the author
  isn't good at template system used by Piwigo. Feel free to help us to
  write a webUI :)

## DONATION

  If you know what `donation` is and how it works in Open source develoment,
  feel free to donate ;) My Paypal email is xkyanh@gmail.com.

## AUTHOR

  The author's information

  * Real name: Anh K. Huỳnh
  * Email: kyanh@viettug.org, xkyanh@gmail.com
  * Nickname on Piwigo's forum: icy

## THANKS

  Special thanks to

  * plg     _(Piwigo forum)_
  * delakut _(Piwigo forum)_
  * IGraham _(Piwigo forum)_

## LICENSE

  GPL2

## HOMGEPAGE

  * At Github.com:  https://github.com/icy/icy_picture_modify
  * At Piwigo.com:  http://piwigo.org/ext/extension_view.php?eid=563
  * SVN repository: http://piwigo.org/svn/extensions/Icy_Picture_Modify/
  * Forum link:     http://piwigo.org/forum/viewtopic.php?pid=131585#p131585
