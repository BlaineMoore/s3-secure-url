=== S3 Secure URL ===
Contributors: maxkostinevich
Donate link: https://maxkostinevich.com
Tags: s3, aws, amazon, secure, url, link, temporary link, temporary url, expiring url, expire links, private files, share link
Requires at least: 3.5.1
Tested up to: 5.6
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

S3 Secure URL plugin allows you to create temporary links using Amazon S3 service.

== Description ==

Create temporary links to files which are stored in Amazon S3 service using simple shortcode.

**Shortcode builder** also available (see screenshots).

Read the [detailed guide](https://maxkostinevich.com/blog/simple-way-to-sell-and-share-files-with-wordpress "Simple way to sell and share files with WordPress") which describes how to use this plugin.


= Shortcode usage =

Wrap text ('Download Now') in the link (**a-tag**):

    [s3secureurl bucket='bucket-name' target='/path/to/file.ext' expires='5']Download Now[/s3secureurl]

**Output**: [Download](http://example.com/secure-url)

Display the raw link as text:

    [s3secureurl bucket='bucket-name' target='/path/to/file.ext' expires='5' /]

**Output**: http://example.com/secure-url


== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 's3-secure-url' or for 'S3 Secure URL'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `s3-secure-url.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `s3-secure-url.zip`
2. Extract the `s3-secure-url` directory to your computer
3. Upload the `s3-secure-url` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


== Frequently Asked Questions ==

= What do I need to get started?  =

You need an Amazon Web Services (AWS) account and the AWS Access Key and AWS Secret Key

= What are benefits of this plugin? =

You can use this plugin to share or sell files on your website.
Read the [detailed guide](https://maxkostinevich.com/blog/simple-way-to-sell-and-share-files-with-wordpress "Simple way to sell and share files with WordPress") which describes how to use this plugin.

== Screenshots ==

1. Plugin settings page
2. You can add shortcode using shortcode builder
2. Shortcode builder popup form


== Changelog ==

= 1.0.1 = 
* Blaine Moore: added urlformat parameter to get around bug with subdomains and DNS not working for buckets

= 1.0.1 =
* Blaine Moore: updated static methods to keep plugin working

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release