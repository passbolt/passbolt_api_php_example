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

// In the example we use an already encrypted secret
$postParams = [
    'name' => 'My password',
    'secrets' => [[
        'data' => "-----BEGIN PGP MESSAGE-----
Version: OpenPGP.js v4.6.2
Comment: https://openpgpjs.org

wcFMAxYTR81eetNbAQ/9Houri7re0RVIsPrvfY1EjdQoSZ9AyEXrh5HFJIwD
IVpBX8ox+2NIzEgBipusVdeSN2itFzjuckiiUDi5EZE9n3J4JHty0WRkyxEN
/QZUKv6m3m1umUYKRUPwthoqbMymFwJkjlna/EoFBOnN3bydEQWcMvf0VUWe
q9CzHBxP8VczUdqQJnrFz83UTX4XDH/v4cq9eUW6y8BhEVNRcA0bgXGvCdul
pslNtOw8kE5oY+3v4jsfsOIaJ/Ss6AR7jkM49ZM6Q1LDP5tFdiuS1FFX+BJc
3KZdpL8vYwwBSbVjYY6/LNdhV/GVWCB2eIbGrmdf1DrDi7e4pgBcaHM8uNs5
H7V0Ec1g7WlNn74Wo/dv0K9iPF75INgg2/IAC1I9h2dn4NmHpe/XszQfXi7M
+AHuNV4vxT6jlrLlGHEteN+/G/uV28i98WZt73L0nz/LfKStUa1LejL52IBP
vashFYRn21fxyszQqqa8a/XfSepC2PyYDbTzEYT+1zizQp5ICHJ2V3gjUQHV
GsDSyVVFOkv5A85t9zzGtkUGLUAuCBpeQLiRlSMyjC7xm5ckjpKN9Bq84aK2
H9jJw+OvDwfYTGRDhtR0xTtnP2HS0q2IZaEQLntpSi82EqkSnMyh766erbef
OIItCLvhzvqCeSu2r5xOfP2dh0eErFjMwhpiP0T5/0fSwbgB36ZFj8bdJBMg
yjW9OTx5MfiRDXqjGnnwAhgoEsVBFSVr8w0CRBNWSRGV//l33DesnvL5VgBe
DxZq3UqboFb5ONFUg1xM9feBBgkNs0UetZKgfZIZoNqQu/bW6X6N8TLpqYmM
4CiwgDqdvwr9xJoPWW4zHoLFPqxFSj5Ab13oE2WWBFpEXR9U1Q6tLlg32wSj
DmUsDusFr/utQWCwmecyxpmK/R/u6QjAxVIVFVBls3oEhgT489vn1uKf6qSH
pWe9ZWNP7wCpqRhLZjNJbR0YmlkORNWEEG7Fon0ApHE12NDOb96J5gOtQrdS
dMQ2gUjmgTC7CFbtqsLJplpctUhQHzgee5bII56sXb39gcDehferwk+VA3kF
qQvKV1pKhEEYryXAY6bKoxnEBcKfgZSvXaWZ0Qz8tFSuAaUdAgVmpYFyUPBZ
OlcAfJIPQ7pRkqhWii1QVtSqfozouh+kR8mka3frC2RMSLH23JSV5Nfr2Xsv
u30wuYqzJL776K1cA+UshSDG4Sp0EtqOvKlkzl9a5/38x3tt8wEXReZ2zsW4
3XCV0AELRlv1h4DQVbt/X7mnd6HWfmqMXGHaSDhh7XC06inqO9DD7yNSdTAs
E7bCo6+V6h45gcvmqkOeD5OR4PAvHGUBzP3oz6imXo5QDPn/FUbrpLhqKBF7
aT+i769muW2VNmXsjpGrBDVxcLn1lGnrUq0KMX4RhOmwhOabJZYpSQVhx6z8
EEGobrCgDXWItdo7YGHdunMFeG6P7QJl8BjZlp+ot471AjW4T32soDn0biWC
XULWuYQ9T7GLpupX+TLPI/jwQh+y0ezi2EkhdS72WF67vHDUWA==
=WAp0
-----END PGP MESSAGE-----
"
    ]]
];

// Simple example of creating a resource.
$ch = curl_init($config['server_url'] . '/resources.json?api-version=v2');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, $gpgAuth->getCookie(true));
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postParams));
$extraHeaders = array(
    "X-CSRF-Token: {$gpgAuth->csrfToken}",
);
curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHeaders);
$response = curl_exec($ch);
curl_close($ch);

echo $response;

