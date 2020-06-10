# PASSBOLT API PHP EXAMPLE
This repository contains an example of [Passbolt api](https://help.passbolt.com/api) implementation in PHP. 

IMPORTANT: The source code provided is not to be used in production as it is. It is only a simplified example of how to connect to Passbolt API in php and perform operations.

# Examples included:
- get_resources.php : log in, retrieve passwords and display the request result.
- create_resource.php : log in and post an already encrypted secret

# Requirements:
- A Passbolt server (CE, Pro or Cloud)
- The private key of your user (Download it from the profile section in Passbolt)

# Usage:
The following php libraries are required:
- gnupg
- curl

Command line:
```bash
php ./get_resources.php
```

# Limitations:
Depending on your GPG configuration, the private key passphrase provided in the configuration might not be taken into account at runtime.
If that's the case, you'll need to preset it using gpg, or enter the passphrase manually when prompted.

To preset it in GPG:
1) get the keygrip
```bash
gpg --list-keys --with-keygrip
```

2) Preset it
(in Debian)
```bash
/usr/lib/gnupg/gpg-preset-passphrase --preset --passphrase <passphrase> <keygrip>
```

You might need to restart the gpg-agent in order for it to work:
```bash
gpg-agent --allow-preset-passphrase
```
