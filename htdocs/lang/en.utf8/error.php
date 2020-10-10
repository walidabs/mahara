<?php
/**
 *
 * @package    mahara
 * @subpackage lang
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

defined('INTERNAL') || die();

// @todo<nigel>: most likely need much better descriptions here for these environment issues
$string['phpversion'] = 'Mahara will not run on PHP < %s. Please upgrade your PHP version or move Mahara to a different host.';
$string['jsonextensionnotloaded'] = 'Your server configuration does not include the JSON extension. Mahara requires this in order to send some data to and from the browser. Please make sure that it is loaded in php.ini or install it if it is not installed.';
$string['pgsqldbextensionnotloaded'] = 'Your server configuration does not include the pgsql extension. Mahara requires this in order to store data in a relational database. Please make sure that it is loaded in php.ini or install it if it is not installed.';
$string['mysqldbextensionnotloaded'] = 'Your server configuration does not include the mysqli or mysql extension. Mahara requires this in order to store data in a relational database. Please make sure that it is loaded in php.ini or install it if it is not installed.';
$string['mysqlmodulenolongersupported1'] = 'Your server configuration does not include the mysqli extension. Please make sure that it is loaded in php.ini or install it if it is not installed. Mahara stopped supporting the mysql extension in version 16.10';
$string['unknowndbtype'] = 'Your server configuration references an unknown database type. Valid values are "postgres" and "mysql". Please change the database type setting in config.php.';
$string['domextensionnotloaded'] = 'Your server configuration does not include the dom extension. Mahara requires this in order to parse XML data from a variety of sources.';
$string['mbstringextensionnotloaded'] = 'Your server configuration does not include the mbstring extension. Mahara requires this to parse multi-byte strings for varying languages.';
$string['xmlextensionnotloaded'] = 'Your server configuration does not include the %s extension. Mahara requires this in order to parse XML data from a variety of sources. Please make sure that it is loaded in php.ini or install it if it is not installed.';
$string['gdextensionnotloaded'] = 'Your server configuration does not include the gd extension. Mahara requires this in order to perform resizes and other operations on uploaded images. Please make sure that it is loaded in php.ini or install it if it is not installed.';
$string['gdfreetypenotloaded'] = 'Your server configuration of the gd extension does not include Freetype support. Please make sure that gd is configured with it.';
$string['sessionextensionnotloaded'] = 'Your server configuration does not include the session extension. Mahara requires this in order to support people logging in. Please make sure that it is loaded in php.ini or install it if it is not installed.';
$string['curllibrarynotinstalled'] = 'Your server configuration does not include the curl extension. Mahara requires this for Moodle integration and to retrieve external feeds. Please make sure that curl is loaded in php.ini or install it if it is not installed.';
$string['registerglobals'] = 'You have dangerous PHP settings: register_globals is on. Mahara is trying to work around this, but you should really fix it. If you are using shared hosting and your host allows for it, you should include the following line in your .htaccess file:
php_flag register_globals off';
$string['magicquotesgpc'] = 'You have dangerous PHP settings: magic_quotes_gpc is on. Mahara is trying to work around this, but you should really fix it. If you are using shared hosting and your host allows for it, you should include the following line in your .htaccess file:
php_flag magic_quotes_gpc off';
$string['magicquotesruntime'] = 'You have dangerous PHP settings: magic_quotes_runtime is on. Mahara is trying to work around this, but you should really fix it. If you are using shared hosting and your host allows for it, you should include the following line in your .htaccess file:
php_flag magic_quotes_runtime off';
$string['magicquotessybase'] = 'You have dangerous PHP settings: magic_quotes_sybase is on. Mahara is trying to work around this, but you should really fix it. If you are using shared hosting and your host allows for it, you should include the following line in your .htaccess file:
php_flag magic_quotes_sybase off';

$string['safemodeon'] = 'Your server appears to be running safe mode. Mahara does not support running in safe mode. You must turn this off in either the php.ini file or in your apache config for the site.

If you are on shared hosting, it is likely that there is little you can do to get safe mode turned off other than ask your hosting provider. Perhaps you could consider moving to a different host.';
$string['apcstatoff'] = 'Your server appears to be running APC with apc.stat=0. Mahara does not support this configuration. You must set apc.stat=1 in the php.ini file.

If you are on shared hosting, it is likely that there is little you can do to get apc.stat turned on other than ask your hosting provider. Perhaps you could consider moving to a different host.';
$string['datarootinsidedocroot'] = 'You have set up your data root to be inside your document root. This is a large security problem as then anyone can directly request session data (in order to hijack other people\'s sessions) or files that they are not allowed to access that other people have uploaded. Please configure the data root to be outside of the document root.';
$string['datarootnotwritable'] = 'Your defined data root directory, %s, is not writable. This means that neither session data, files nor anything else that needs to be uploaded can be saved on your server. Please create the directory if it does not exist or give ownership of the directory to the web server account if it does.';
$string['sessionpathnotwritable'] = 'Your session data directory, %s, is not writable. Please create the directory if it does not exist or give ownership of the directory to the web server account if it does.';
$string['wwwrootnothttps'] = 'Your defined wwwroot, %s, is not HTTPS. However, other settings (such as sslproxy) for your installation require that your wwwroot is a HTTPS address.

Please update your wwwroot setting to be a HTTPS address or fix the incorrect setting.';
$string['couldnotmakedatadirectories'] = 'For some reason some of the core data directories could not be created. This should not happen as Mahara previously detected that the dataroot directory was writable. Please check the permissions on the dataroot directory.';

$string['dbconnfailed'] = 'Mahara could not connect to the application database.

 * If you are using Mahara, please wait a minute and try again
 * If you are the administrator, please check your database settings and make sure your database is available

The error received was:
';
$string['dbnotutf8'] = 'You are not using a UTF-8 database. Mahara stores all data as UTF-8 internally. Please drop and re-create your database using UTF-8 encoding.';
$string['dbnotutf8mb4'] = 'You are not using a utf8mb4 Character Set (4-Byte UTF-8 Unicode Encoding) database. Mahara stores all data as utf8mb4 internally. Please drop and re-create your database using utf8mb4 encoding.';
$string['dbversioncheckfailed'] = 'Your database server version is not new enough to successfully run Mahara. Your server is %s %s, but Mahara requires at least version %s.';
$string['plpgsqlnotavailable'] = 'The PL/pgSQL language is not enabled in your Postgres installation, and Mahara cannot enable it. Please install PL/pgSQL in your database manually. For instructions on how to do this, see https://wiki.mahara.org/wiki/System_Administrator\'s_Guide/Enabling_Plpgsql';
$string['mysqlnotriggerprivilege'] = 'Mahara requires permission to create database triggers, but is unable to do so. Please ensure that the trigger privilege has been granted to the appropriate user in your MySQL installation. For instructions on how to do this, see https://wiki.mahara.org/wiki/System_Administrator\'s_Guide/Granting_Trigger_Privilege';
$string['mbstringneeded'] = 'Please install the mbstring extension for php. This is needed if you have UTF-8 characters in usernames. Otherwise, people might not be able to log in.';
$string['cssnotpresent'] = 'CSS files are not present in your htdocs/theme/raw/style directory. If you are running Mahara from a git checkout, run "make css" to build the CSS files. If you are running Mahara from a ZIP download, try downloading and unzipping again.';
$string['mahararootusermissing'] = 'The "root" account is missing from the database so we cannot continue. This account needs to be present for Mahara to function correctly. To add the root account back in, please make another install of the Mahara version you are using and see what is contained for account id = 0 in the "usr" and "usr_custom_layout" tables and add that data to your instance of Mahara before trying to upgrade again.';

// general exception error messages
$string['blocktypenametaken'] = "Block type %s is already taken by another plugin (%s).";
$string['artefacttypenametaken'] = "Artefact type %s is already taken by another plugin (%s).";
$string['artefacttypemismatch'] = "Artefact type mismatch. You are trying to use this %s as a %s.";
$string['classmissing'] = "class %s for type %s in plugin %s was missing.";
$string['artefacttypeclassmissing'] = "Artefact types must all implement a class. Missing %s.";
$string['artefactpluginmethodmissing'] =  "Artefact plugin %s must implement %s and does not.";
$string['blocktypelibmissing'] = 'Missing lib.php for block %s in artefact plugin %s.';
$string['unabletosetmultipleblogs'] = 'Enabling multiple journals for %s when copying page %s has failed. This can be set manually on the <a href="%s">account</a> page.';
$string['pleaseloginforjournals'] = 'You need to log out and log back in before you will see all your journals and posts.';
$string['blocktypemissingconfigform'] = 'Block type %s must implement instance_config_form.';
$string['versionphpmissing1'] = 'Plugin %s %s is missing version.php. If you are not expecting to have a plugin %s, please delete the folder at %s.';
$string['blocktypeprovidedbyartefactnotinstallable'] = 'This will be installed as part of the installation of artefact plugin %s.';
$string['blockconfigdatacalledfromset'] = 'Configdata should not be set directly. Use PluginBlocktype::instance_config_save instead.';
$string['invaliddirection'] = 'Invalid direction %s.';
$string['onlyoneprofileviewallowed'] = 'You are only allowed one profile page.';
$string['cannotputblocktypeintoview'] = 'Cannot put %s block types into this page';
$string['onlyoneblocktypeperview'] = 'Cannot put more than one "%s" block type into a page.';
$string['errorat'] = ' at ';

// if you change these next two , be sure to change them in libroot/errors.php
// as they are duplicated there, in the case that get_string was not available.
$string['unrecoverableerror'] = 'A nonrecoverable error occurred. This probably means that you have encountered a bug in the system.';
$string['unrecoverableerrortitle'] = '%s - Site unavailable';
$string['parameterexception'] = 'A required parameter was missing.';

$string['notfound'] = 'Not found';
$string['notfoundexception'] = 'The page you are looking for could not be found.';

$string['accessdenied'] = 'Access denied';
$string['accessdeniedobjection'] = 'Access denied. The objection has already been resolved by another administrator.';
$string['accessdeniedsuspension'] = 'This portfolio is under review.';
$string['accessdeniedexception'] =  'You do not have access to view this page.';
$string['accessdeniednourlsecret'] =  'You do not have access to this functionality. Please provide the value for "urlsecret" from your config.php file as part of the URL.';
$string['accessdeniedbadge'] =  'You do not have access to view this badge.';
$string['siteprivacystatementnotfound'] = 'The site privacy statement with ID %s was not found.';
$string['institutionprivacystatementnotfound'] = 'The privacy statement for "%s" with ID %s was not found.';
$string['viewnotfoundexceptiontitle'] = 'Page not found';
$string['viewnotfoundexceptionmessage'] = 'You tried to access a page that does not exist.';
$string['viewnotfound'] = 'Page with id %s not found.';
$string['collectionnotfound'] = 'Collection with id %s not found';
$string['viewnotfoundbyname'] = 'Page %s by %s not found.';
$string['youcannotviewthisusersprofile'] = 'You cannot view this profile.';
$string['notinthesamegroup'] = 'You cannot view this profile because you are not members of the same group.';
$string['notinthesameinstitution'] = 'You cannot view this profile because you are not members of the same institution.';
$string['notinstitutionmember'] = 'You cannot view this page because you are not a member of the institution to which this page belongs.';
$string['invalidlayoutselection'] = 'You tried to select a layout that doesn\'t exist.';
$string['previewimagegenerationfailed'] = 'Sorry, there was a problem generating the preview image.';
$string['viewtemplatenotfound'] = 'Default page template not found.';

$string['artefactnotfoundmaybedeleted'] = "Artefact with id %s not found (maybe it has been deleted already?)";
$string['artefactnotfound'] = 'Artefact with id %s not found';
$string['artefactsnotfound'] = 'No artefact(s) found with the id(s): %s';
$string['artefactnotinview'] = 'Artefact %s not in page %s';
$string['artefactonlyviewableinview'] = 'Artefacts of this type are only viewable within a page.';
$string['notartefactowner'] = 'You do not own this artefact.';

$string['blockinstancenotfound'] = 'Block instance with id %s not found.';
$string['interactioninstancenotfound'] = 'Activity instance with id %s not found.';

$string['invalidviewaction'] = 'Invalid page control action: %s';
$string['invaliduser'] = 'Invalid account selected';

$string['missingparamblocktype'] = 'Try selecting a block type to add first.';
$string['missingparamorder'] = 'Missing order specification';
$string['missingparamid'] = 'Missing id';

$string['themenameinvalid'] = "The name of the theme '%s' contains invalid characters.";

$string['timezoneidentifierunusable'] = 'PHP on your website host does not return a useful value for the time zone identifier (%%z). Certain date formatting, such as the Leap2A export, will be broken. %%z is a PHP date formatting code. This problem is usually due to a limitation in running PHP on Windows.';
$string['postmaxlessthanuploadmax'] = 'Your PHP post_max_size setting (%s) is smaller than your upload_max_filesize setting (%s). Uploads larger than %s will fail without displaying an error. Usually, post_max_size should be much larger than upload_max_filesize.';
$string['smallpostmaxsize'] = 'Your PHP post_max_size setting (%s) is very small. Uploads larger than %s will fail without displaying an error.';
$string['notenoughsessionentropy'] = 'Your PHP session.entropy_length setting is too small. Set it to at least 16 in your php.ini to ensure that generated session IDs are random and unpredictable enough.';
$string['switchtomysqli'] = 'The <strong>mysqli</strong> PHP extension is not installed on your server. Thus, Mahara is falling back to the deprecated original <strong>mysql</strong> PHP extension. We recommend installing <a href="http://php.net/manual/en/book.mysqli.php">mysqli</a>.';
$string['noreplyaddressmissingorinvalid'] = 'The noreply address setting is either empty or has an invalid email address. Please check the configuration in the <a href="%s">site options in the email settings</a>.';
$string['openbasedirenabled'] = 'Your server has the php open_basedir restriction enabled.';
$string['openbasedirpaths'] = 'Mahara can only open files within the following path(s): %s.';
$string['openbasedirwarning'] = 'Some requests for external sites may fail to complete. This could stop certain feeds from updating among other things.';
$string['resavecustomthemes'] = 'Your latest upgrade may have stopped your configurable themes from displaying correctly. To update a configurable theme, please go to Administration →  Institution -> Settings, configure the institution\'s settings, and save the form.<br>The following institutions use configurable themes:';

$string['gdlibrarylacksgifsupport'] = 'The installed PHP GD library does not support both creating and reading GIF images. Full support is needed to upload GIF images.';
$string['gdlibrarylacksjpegsupport'] = 'The installed PHP GD library does not support JPEG/JPG images. Full support is needed to upload JPEG/JPG images.';
$string['gdlibrarylackspngsupport'] = 'The installed PHP GD library does not support PNG images. Full support is needed to upload PNG images.';

$string['nopasswordsaltset'] = 'No sitewide password salt has been set. Edit your config.php and set the "passwordsaltmain" parameter to a reasonable secret phrase.';
$string['passwordsaltweak'] = 'Your sitewide password salt is not strong enough. Edit your config.php and set the "passwordsaltmain" parameter to a longer secret phrase.';
$string['urlsecretweak'] = 'The $cfg->urlsecret set for this site has not been changed from the default value. Edit your config.php and set the $cgf->urlsecret parameter to a different string (or null if you do not wish to use a urlsecret).';
$string['notproductionsite'] = 'This site is not in production mode. Some data may not be available and/or may be out of date.';
$string['badsessionhandle'] = 'The session save handler "%s" is not configured correctly. Please check the settings in your "config.php" file.';
$string['wrongsessionhandle'] = 'The session save handler "%s" is not supported in Mahara.';
$string['nomemcachedserver'] = 'The memcache server "%s" is not reachable. Please check the $cfg->memcacheservers value to make sure it is correct';
$string['nophpextension'] = 'The PHP extension "%s" is not enabled. Please enable the extension and restart your webserver or choose a different session option.';
$string['nomemcacheserversdefined'] = 'The session save handler "%s" has no related servers defined. Please set the $cfg->memcacheservers value, e.g. "localhost:11211".';
$string['memcacheusememcached'] = 'The "memcache" session storage is obsolete. Please use "memcached".';
$string['siteoutofsyncfor'] = 'This site has database information newer than %s files indicate it should be.';
$string['updatesitetimezone'] = 'The time zone for the site now needs to be set via "Configure site" →  "Site options" →  "Site settings". Please set it there and delete the $cfg->dbtimezone line from your config.php file.';
$string['pluginnotactive1'] = 'The plugin "%s" is not enabled. Please got to "Administration" →  "Extensions" →  "Plugin administration" to enable it.';

$string['fileuploadtoobig'] = 'The file upload is too big as it is bigger than "%s"';
$string['sideblockmenuclash'] = 'The sideblock name "%s" is already in use. Please choose a different one.';
$string['isolatedinstitutionsremoverules'] = 'We have hidden %s access rules due to isolated institutions being in effect. The hidden rules will be removed once the form is saved.';
