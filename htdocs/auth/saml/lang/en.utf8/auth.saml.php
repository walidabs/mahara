<?php
/**
 *
 * @package    mahara
 * @subpackage auth-internal
 * @author     Piers Harding <piers@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

defined('INTERNAL') || die();

$string['attributemapfilenotamap'] = 'The attribute map file "%s" didn\'t define an attribute map.';
$string['attributemapfilenotfound'] = 'Could not find attribute map file or it is not writable: %s';
$string['currentcertificate'] = 'SAML Service Provider signing and encryption certificate';
$string['oldcertificate'] = 'Old SAML Service Provider signing and encryption certificate';
$string['newcertificate'] = 'New SAML Service Provider signing and encryption certificate';
$string['confirmdeleteidp'] = 'Are you sure you want to delete this identity provider?';
$string['spmetadata'] = 'Service Provider metadata';
$string['metadatavewlink'] = '<a href="%s">View metadata</a>';
$string['newpublickey'] = 'New public key';
$string['ssphpnotconfigured'] = 'SimpleSAMLPHP is not configured.';
$string['manage_certificate2'] = 'This is the certificate generated as part of the SAML Service Provider.';
$string['manage_new_certificate'] = 'This is the new certificate generated as part of the SAML Service Provider.<br>
Both the new and old certificates will be valid. Once you have notified all Identity Providers of your new certificate, you can remove older certificates via the "Delete old certificate" button.';
$string['nullprivatecert'] = "Could not generate or save the private key";
$string['nullpubliccert'] = "Could not generate or save the public certificate";
$string['defaultinstitution'] = 'Default institution';
$string['description'] = 'Authenticate against a SAML 2.0 Identity Provider service';
$string['disco'] = 'Identity Provider discovery';
$string['errorbadinstitution'] = 'Institution for connecting account not resolved';
$string['errorbadssphp'] = 'Invalid SimpleSAMLphp session handler: Must not be phpsession';
$string['errorbadssphpmetadata'] = 'Invalid SimpleSAMLphp configuration: No Identity Provider metadata configured';
$string['errorbadssphpspentityid'] = 'Invalid Service Provider entityId';
$string['errorextrarequiredfield'] = 'This field is required when "We auto-create accounts" is enabled.';
$string['errorretryexceeded'] = 'Maximum number of retries exceeded (%s): There is a problem with the identity service';
$string['errnosamluser'] = 'No account found';
$string['errorssphpsetup'] = 'SAML is not set up correctly. You need to run "make ssphp" from the commandline first.';
$string['errorbadlib'] = 'The SimpleSAMLPHP library\'s "autoloader" file was not found at %s.<br>Make sure you install SimpleSAMLphp via "make ssphp" and the file is readable.';
$string['errorupdatelib'] = 'Your current SimpleSAMLPHP library version is out of date. You need to run "make cleanssphp && make ssphp".';
$string['errornovalidsessionhandler'] = 'The SimpleSAMLphp session handler is misconfigured or the server is currently unavailable.';
$string['errornomemcache'] = 'Memcache is misconfigured for auth/saml or a Memcache server is currently unavailable.';
$string['errornomemcache7php'] = 'Memcache is misconfigured for auth/saml or a Memcache server is currently unavailable.';
$string['errorbadconfig'] = 'The SimpleSAMLPHP config directory %s is incorrect.';
$string['errorbadmetadata1'] = 'Badly formed SAML metadata. The following problems were detected: %s';
$string['errorbadinstitutioncombo'] = 'There is already an existing authentication instance with this institution attribute and institution value combination.';
$string['errormissingmetadata'] = 'You have chosen to add new Identity Provider metadata but none is supplied.';
$string['errormissinguserattributes1'] = 'You seem to be authenticated, but we did not receive the required user attributes. Please check that your Identity Provider releases the first name, surname, and email fields for SSO to %s or inform the administrator.';
$string['errorregistrationenabledwithautocreate1'] = 'An institution has enabled registration. For security reasons, this excludes account auto-creation, unless you are using remote usernames.';
$string['errorremoteuser1'] = 'Matching on "remoteuser" is mandatory if "usersuniquebyusername" is turned off.';
$string['IdPSelection'] = 'Identity Provider selection';
$string['noidpsfound'] = 'No Identity Providers found';
$string['idpentityid'] = 'Identity Provider entity';
$string['idpentityadded'] = "Added the Identity Provider metadata for this SAML instance.";
$string['idpentityupdated'] = "Updated the Identity Provider metadata for this SAML instance.";
$string['idpentityupdatedduplicates'] = array(
    0 => "Updated the Identity Provider metadata for this and 1 other SAML instance.",
    1 => "Updated the Identity Provider metadata for this and %s other SAML instances."
);
$string['metarefresh_metadata_url'] = 'Metadata URL for auto-refresh';
$string['idpprovider'] = 'Provider';
$string['idptable'] = 'Installed Identity Providers';
$string['institutionattribute'] = 'Institution attribute (contains "%s")';
$string['institutionidp'] = 'Institution Identity Provider SAML metadata';
$string['institutionidpentity'] = 'Available Identity Providers';
$string['institutions'] = 'Institutions';
$string['institutionvalue'] = 'Institution value to check against attribute';
$string['libchecks'] = 'Checking for correct libraries installed: %s';
$string['link'] = 'Link accounts';
$string['linkaccounts'] = 'Do you want to link the remote account "%s" with the local account "%s"?';
$string['loginlink'] = 'Allow people to link their own account';
$string['logintolink'] = 'Local login to %s to link to remote account';
$string['logintolinkdesc'] = '<p><strong>You are currently connected with remote account "%s". Please log in with your local account to link them or register if you do not currently have an account on %s.</strong></p>';
$string['logo'] = 'Logo';
$string['institutionregex'] = 'Do partial string match with institution shortname';
$string['login'] = 'SSO';
$string['newidpentity'] = 'Add new Identity Provider';
$string['notusable'] = 'Please install the SimpleSAMLPHP libraries and configure the Memcache server for sessions.';
$string['obsoletesamlplugin'] = 'The auth/saml plugin needs to be reconfigured. Please update the plugin via the <a href="%s">plugin configuration</a> form.';
$string['obsoletesamlinstance'] = 'The SAML authentication instance <a href="%s">%s</a> for institution "%s" needs updating.';
$string['reallyreallysure1'] = "You are trying to save the Service Provider metadata for Mahara. This cannot be undone. Existing SAML logins will not work until you have reshared your new metadata with all Identity Providers.";
$string['reset'] = 'Reset metadata';
$string['resetmetadata'] = 'Reset the certificates for Mahara\'s metadata. This cannot be undone, and you will have to reshare your metadata with the Identity Provider.';
$string['samlconfig'] = 'SAML configuration';
$string['samlfieldforemail'] = 'SSO field for email';
$string['samlfieldforfirstname'] = 'SSO field for first name';
$string['samlfieldforsurname'] = 'SSO field for surname';
$string['samlfieldforstudentid'] = 'SSO field for student ID';
$string['samlfieldforavatar'] = 'SSO field for avatar icon';
$string['samlfieldforavatardescription'] = 'Supplied avatar needs to contain a base64 encoded image string';
$string['samlfieldforrole'] = 'SSO field for roles';
$string['samlfieldforroleprefix'] = 'SSO field for role prefix';
$string['samlfieldforrolesiteadmin'] = "Role mapping for 'Site administrator'";
$string['samlfieldforrolesitestaff'] = "Role mapping for 'Site staff'";
$string['samlfieldforroleinstadmin'] = "Role mapping for 'Institution administrator'";
$string['samlfieldforroleinststaff'] = "Role mapping for 'Institution staff'";
$string['samlfieldfororganisationname'] = "SSO field for 'Organisation'";
$string['populaterolestoallsaml'] = 'Copy roles to all SAML instances';
$string['populaterolestoallsamldescription'] = "If this switch is enabled, the values for all the 'Role' fields are copied to all other SAML authentication instances that use the same Identity Provider on submission of this form. This field then resets to 'No'.";
$string['samlfieldforautogroups'] = "Role mapping for 'Auto group administration'";
$string['samlfieldforautogroupsall'] = 'Auto group administration of all groups on the site';
$string['samlfieldforautogroupsalldescription'] = "If enabled, the person that has the 'Auto group administration' role will be added as a group administrator to all groups on the entire site. Otherwise, they are only added as a group administrator to groups within their institution.";
$string['samlfieldauthloginmsg'] = 'Wrong login message';
$string['spentityid'] = "Service Provider entityId";
$string['title'] = 'SAML';
$string['updateuserinfoonlogin'] = 'Update account details on login';
$string['userattribute'] = 'User attribute';
$string['simplesamlphplib'] = 'SimpleSAMLPHP lib directory';
$string['simplesamlphpconfig'] = 'SimpleSAMLPHP config directory';
$string['weautocreateusers'] = 'We auto-create accounts';
$string['remoteuser'] = 'Match username attribute to remote username';
$string['selectidp'] = 'Please select the Identity Provider that you wish to log in with.';
$string['sha1'] = 'Legacy SHA1 (Dangerous)';
$string['sha256'] = 'SHA256 (Default)';
$string['sha384'] = 'SHA384';
$string['sha512'] = 'SHA512';
$string['sigalgo'] = 'Signature algorithm';
$string['keypass'] = 'Private key passphrase';
$string['keypassdesc'] = 'Passphrase to protect the private key';
$string['newkeypass'] = 'New private key passphrase';
$string['newkeypassdesc'] = 'Passphrase to protect the new private key if you want it to be different to the current one';
$string['createnewkeytext'] = 'Create new key / certificate';
$string['newkeycreated'] = 'New key / certificate created';
$string['deleteoldkeytext'] = 'Delete old certificate';
$string['oldkeydeleted'] = 'Old key / certificate deleted';
$string['keyrollfailed'] = 'Failed to remove old key / certificate';
$string['missingnamespace'] = 'The XML expects the namespace "%s" to be defined in EntityDescriptor tag';

// SSO labels
// The SSO buttons will be labelled with the display name of your institution
$string['ssolabelfor'] = '%s login';
// If you want to have custom labels, you can either add them here
// or create a htdocs/local/lang/en.utf8/auth.saml.php file and add them there.
// They need to have the key 'login' + shortname of institution, e.g.
// For 'testinstitution' it would be: $string['logintestinstitution'] = 'Special label';

$string['noentityidpfound'] = 'No Identity Provider ID found';
$string['noentityidpneednamespace'] = 'Does your XML EntityDescriptor tag require "xmlns=" to be defined?';
$string['novalidauthinstanceprovided'] = 'Your selection is not possible. Please select a different institution.';
$string['identityprovider'] = 'Identity Provider';
$string['selectmigrateto'] = 'Select institution to move to...';
