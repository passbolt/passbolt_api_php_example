<?php
return [
     // The server url is the url at which your passbolt server is located.
    'server_url' => 'https://cloud.passbolt.com/your_instance',

    // The private key can be downloaded from the Passbolt web interface, in profile > keys inspector.
    'private_key_path' => __DIR__ . '/private.key',

    // The passphrase of the the private key. null if no passphrase.
    'private_key_passphrase' => null,
];