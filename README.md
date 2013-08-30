## Name

  `icy_picture_modify` -- Piwigo plugin that allows user to modify pictures

## Description

  A piwigo extension that allows users to edit any set of images.
  This extension just works as the `picture_modify.php` for administrator.

  The code of this plugin is based on the original source
  `picture_modify.php` of the Piwigo distribution (version 2.2.3.)

  Advanced ACL is supported since he version 1.2.0.

## Features

  Normal users in Piwigo system can delete image or modify image's metadata
  _(author, date, tags, description, date, linking/represented categories.)_
  They can upload some images to some categories.

  Advanced ACL allows users to work with any set of images / categories.
  The newest verion of plugin allows you to work with groups in Piwigo,
  and allow user to update new version of the image _(inspired from the
  plugin `photo_update`)_

  This plugin can work on Piwigo 2.2.x, 2.3.x, 2.4.x and 2.5.x.

## Installation

  * Install and enable this plugin
  * Install and enable the plugin `community` if you want some users
    to upload images to your gallery
    * If the plugin `community` is installed but it isn't activated,
      most functions of the plugin `icy_picture_modify` is still available
      but user won't be able to upload images to gallery
    * You *don't need* to configure any thing in the plugin `community`
      that's because all settings for `community` will be overwritten by
      `icy_picture_modify`. However, the UI of that plugin is still
      available in case you want to moderate some pending photos.
  * If you have installed the plugin `photo_update` you can disable and/or
    uninstall that plugin because a similar function is supported in
    this plugin `icy_picture_modify`.

## Usage

  * Create and edit ACL file in `local/config/icy_acl.zml` in your Piwigo
    installation.
    * Examples can be found in `doc/*.sample*`
    * The syntax is described in the document `doc/zaml.md`. Please make
      sure that you read some important notes in this document.
    * You don't have to modify any settings for the plugin `community`
      Moverover, any settings of the plugin `community` will be ignored.
  * If you are using a development version of the plugin, please clean up
    the obsolete files (See `OBSOLETE FILES` below.)

## Obsolete files

  The following files are obsolsete. They need to be ported to new format
  and/or to be removed

  * `local/config/icy_acl.php`:
    need to be ported to `local/config/icy_acl.zml`. After your old ACL
    settings are ported to new format, you can safely delete this file;
  * `_data/icy.log`:
    if you are using `icy_picture_modify` version 2.0.0 or higher, you
    can safely remove this file.

## Known problems

  * No international support for Piwigo 2.5.x (See CHANGELOG for details)
  * No webUI for ACL editting
  * If an image is replaced by a new version (using plugin `Photo update`)
    the new version is owned by administrator, not the current user.
    Hence the image may not be editable anymore. You should not use that
    plugin to update image. You can use the setting `replace_image_of`
    in this plugin's configuration instead.
  * This plugin doesn't support all known templates. If you are using
    `stripped` or `stripped-galleria`, you may follow the instructions
    in this forum post
        http://piwigo.org/forum/viewtopic.php?pid=132380#p132380
  * User can delete an image which is associated to some albums to which
    the user doesn't have permission to write/access. This is true as the
    plugin only checks owner of the image
  * Images uploaded via Digikam Plugin will be in *pending* mode
  * When user creates new sub-album, their list of visible albums may
    be expanded and contain all albums on the Piwigo system. This is a
    problem with session data, and will be fixed immediately after the
    page is reloaded.

## Security issues

  * All versions from 2.1.0 to 2.4.0 have a security issue that may
    break your ACL settings, and that allows any user in Piwigo system
    to edit any images in the system. This problem is fixed in the version
    2.4.1. Please upgrade to this version or apply the patch file
    `/patches/IPM-SA-2013-08-14.patch` found in the source tree.
  * The guest account should have the identity `guest`. If you change
    guest account name, you need to explicitly update setting for this
    account.
  * The plugin won't check for user input from `icy_acl.zml`, so you
    may trick your site by some SQL injection. Yes, if you really want
    to trick your site.
  * Don't use any group name as any username or vice versa. That would make
    your configuration confused and some settings may have side-effects
    that are out of your control.

## Support

  To get support, please create new issue at
    https://github.com/icy/icy_picture_modify/issues

## Development

  The plugin requires a webUI for ACL editting. Unfortunately, the author
  isn't good at template system used by Piwigo. Feel free to help us to
  write a webUI :)

## Donation

  My paypal account was locked due to Paypal's stupid policy.
  I can't receive any kind of donation. However, you can always rate
  my plugin at the link http://piwigo.org/ext/extension_view.php?eid=563 ,
  and if possible please send a greeting message to me ;)

## Author

  The author's information

  * Real name: Anh K. Huỳnh
  * Email: kyanh@viettug.org, xkyanh@gmail.com
  * Nickname on Piwigo's forum: icy

## Thanks

  Special thanks to

  * plg     _(Piwigo forum)_
  * delakut _(Piwigo forum)_
  * IGraham _(Piwigo forum)_
  * Kalle   _(Piwigo forum)_ *IPM-SA-2013-08-13*
  * Flop25  _(flop25 at gmail dot com)_
  * werdnahman _(Piwigo forum)_ *Windows CR/LF bug*

## License

  GPL2

## Homepage

  * At Github.com:  https://github.com/icy/icy_picture_modify
  * At Piwigo.com:   http://piwigo.org/ext/extension_view.php?eid=563
  * Forum link:      http://piwigo.org/forum/viewtopic.php?id=17745,
                     http://piwigo.org/forum/viewtopic.php?id=20333,
                     http://piwigo.org/forum/viewtopic.php?pid=145328
  * SVN repository:  http://piwigo.org/svn/extensions/Icy_Picture_Modify/ (out-of-date)
