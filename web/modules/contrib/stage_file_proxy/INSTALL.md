STEPS TO INSTALL
----------------

Install the Stage File Proxy module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for more information.

1. Obtain the module.
2. Enable the module.
3. Configure module either via admin UI or Add variables to $config in
settings.php.


CONFIGURATION
-------------
There are two options to configure this module.

1. Through the admin UI at Configuration > Stage File Proxy Settings
   (admin/config/system/stage_file_proxy).
2. By specifying configuration keys in settings.local.php.

If you choose the first option and make use of configuration synchronization
between environments, please also make use of something like the
[Configuration Split](https://www.drupal.org/project/config_split) module; this
module should not be used on production sites.

### The origin website
Required.
```php
// Origin - no trailing slash.
$config['stage_file_proxy.settings']['origin'] = 'http://example.com';  // no trailing slash
```
Drush variable set:
```bash
drush config-set stage_file_proxy.settings origin http://example.com
```
If the site is using HTTP Basic Authentication (the browser popup for username
and password) you can embed those in the URL. Be sure to URL encode any
special characters.

For example, setting a user name of "myusername" and password as, "letme&in" the
configuration would be the following:
```php
$config['stage_file_proxy.settings']['origin'] = 'http://myusername:letme%26in@example.com';
```
Drush variable set:
```bash
drush config-set stage_file_proxy.settings origin http://myusername:letme%26in@example.com
```

### SSL verification
Optional.
```php
$config['stage_file_proxy.settings']['verify'] = TRUE;
```
Drush variable set:
```bash
drush config-set stage_file_proxy.settings verify TRUE
```
Default is TRUE.

If this is true (default) then the request will be done by doing the SSL
verification if the origin is using https.

### Request original image when using image styles
Optional.
```php
$config['stage_file_proxy.settings']['use_imagecache_root'] = TRUE;
```
Drush variable set:
```bash
drush config-set stage_file_proxy.settings use_imagecache_root TRUE
```
Default is TRUE.

If this is true (default) then Stage File Proxy will look for /style/ in
the URL and determine the original file and request that rather than the
processed file, then send a header to the browser to refresh the image and let
image module handle it. This will speed up future requests for a different
style of the same original file.

### Hotlink
Optional.
```php
$config['stage_file_proxy.settings']['hotlink'] = FALSE;
```
Drush variable set:
```bash
drush config-set stage_file_proxy.settings hotlink FALSE
```
Default is FALSE.

If this is true then Stage File Proxy will not transfer the remote file to the
local machine, it will just serve a 301 to the remote file and let the origin
webserver handle it.

### Origin dir

```php
$config['stage_file_proxy.settings']['origin_dir'] = 'sites/default/files';
```
Drush variable set:
```bash
drush config-set stage_file_proxy.settings origin_dir sites/default/files
```
Default is 'sites/default/files';

If this is set then Stage File Proxy will use a different path for the remote
files. This is useful for multisite installations where the sites directory
contains different names for each URL. If this is not set, it defaults to the
same path as the local site (sites/default/files).

## Automatically enable when using drush sql-sync

To automatically enable stage_file_proxy on your dev machine after sql-sync, add
the following to your dev site alias file:
```php
$aliases['dev'] = [
  'root' => '/path/to/drupalroot',
  'uri' => 'http://example.org',
  'target-command-specific' => [
    'sql-sync' => [
      'enable' => ['stage_file_proxy'],
    ],
  ],
];
```
In order for this to work, you must copy the file
drush/examples/sync_enable.drush.inc to your ~/.drush folder.
For more information, see [/examples/sync_enable.drush.inc] from the Drush
project.

[/examples/sync_enable.drush.inc]: https://github.com/drush-ops/drush/blob/8.x/examples/sync_enable.drush.inc
