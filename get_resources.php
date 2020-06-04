<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 */
require(__DIR__ . '/lib/gpg_auth.php');

if (!extension_loaded('gnupg')) {
    trigger_error('You must enable the gnupg extension.', E_USER_ERROR);
}
if (!extension_loaded('curl')) {
    trigger_error('You must enable the curl extension.', E_USER_ERROR);
}

/**
 * Important: To use this example you will have to edit the configuration file located
 * in /config/config.php and adjust the variables (url and key).
 */

// Get config.
$config = require(__DIR__ . '/config/config.php');
$gpgAuth = new GpgAuth($config['server_url'], $config['private_key_path'], $config['private_key_passphrase']);

// Login in passbolt. This step will return a cookie that can be used in other curl calls.
$cookie = $gpgAuth->login();

// Simple example of returning the list of resources.
$ch = curl_init($config['server_url'] . '/resources.json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, $cookie);
$response = curl_exec($ch);
curl_close($ch);

echo $response;

