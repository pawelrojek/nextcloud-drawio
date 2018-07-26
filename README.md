# ![](screenshots/icon.png) Nextcloud Draw.io integration app

This app allows users to create and edit diagrams in [Nextcloud](https://nextcloud.com) using [Draw.io](https://draw.io) on-line editor.
App Store link: https://apps.nextcloud.com/apps/drawio

![](screenshots/drawio_add.png)

![](screenshots/drawio_integration.png)


## Info ##
- Requires [Nextcloud](https://nextcloud.com) >11.0.0
- Tested with Chrome 58 and Firefox 53
- Multi language support (l10n)
- Inspired by the old Draw.io Integration and OnlyOffice

## Mimetype detection ##

Unfortunately, apps can't declare new mimetypes on the fly. To make
Draw.io work properly, you need to add a new mimetypes in the
`mimetypemapping.json` file (at Nextcloud level).

To proceed, just copy `/resources/config/mimetypemapping.dist.json` to
`/config/mimetypemapping.json` (in the `config/` folder at Nextcloud’s
root directory; the file should be stored next to the `config.php`
file). Afterwards add the two following line just after the “_comment”
lines.

    "drawio": ["application/x-drawio"],

If all other mimetypes are not working properly, just run the
following command:

    sudo -u www-data php occ files:scan --all

## Download ##
[zip](https://github.com/pawelrojek/nextcloud-drawio/releases/download/v0.8.9/drawio-v0.8.9.zip) or [tar.gz](https://github.com/pawelrojek/nextcloud-drawio/releases/download/v0.8.9/drawio-v0.8.9.tar.gz)


## Changelog ##

## v0.9.0
- Added German translation (PR #38)
- Added "offline mode" (PR #43)
- Added .drawio file type in addtion to .xml (PR #41)
- Querystring in custom drawioUrl (#PR 37)
- Minor other fixes (PR #39)

## v0.8.9
- NC13 compatibility (issue #25)
- IE support (PR #27)
- Brazilian Portuguese translation (PR #26)

## v0.8.8
- NC12 compatibility (issue #10)
- "Origin" integration issue (issue #9)

## v0.8.7
- Edited files are now opened in the same window
- Code changes

[Complete changelog](https://github.com/pawelrojek/nextcloud-drawio/blob/master/drawio/CHANGELOG.md)


## Installation ##
1. Copy Nextcloud draw.io integration app ("drawio" directory) to your Nextcloud server into the /apps/ directory
2. Go to Apps -> "+ Apps" > "Not Enabled" and _Enable_ the **Draw.io** application


# Configuration
Go to Admin page and change the settings you want:

![](screenshots/drawio_admin.png)

Click "Save" when you're done.


## License ##
- Released under the Affero General Public License version 3 or later.
- [CC 3.0 BY] File icon made by [DinosoftLabs](http://www.flaticon.com/authors/dinosoftlabs) / [Link](http://www.flaticon.com/free-icon/organization_348440)


## Contributors ##
- [arnowelzel](https://github.com/arnowelzel)
- [xlyz](https://github.com/xlyz)
- [cuthulino](https://github.com/cuthulino)
- [tavinus](https://github.com/tavinus)
- [LEDfan](https://github.com/LEDfan)
- [mario](https://github.com/mario)
- [ColdSphinX](https://github.com/ColdSphinX)
- [acidhunter](https://github.com/acidhunter)
- [geiseri](https://github.com/geiseri)

[View all](https://github.com/pawelrojek/nextcloud-drawio/graphs/contributors)



## Support ##
 * Any feedback and code is greatly appreciated!
