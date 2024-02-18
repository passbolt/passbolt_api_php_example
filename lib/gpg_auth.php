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

class GpgAuth
{

    private $serverUrl;

    private $privateKey = [
        'keydata' => '',
        'info'    => [],
        'passphrase' => null,
    ];

    private $serverKey = [
        'keydata' => '',
        'info'    => []
    ];

    private $gpg;

    public $sessionId = null;

    public $csrfToken = null;


    public function __construct($serverUrl, $privateKeyPath, $privateKeyPassphrase = null)
    {
        $this->privateKey['keydata'] = file_get_contents($privateKeyPath);
        $this->privateKey['passphrase'] = $privateKeyPassphrase;
        $this->serverUrl             = $serverUrl;
        $this->gpg                   = new \gnupg();
        $this->gpg->seterrormode(\gnupg::ERROR_EXCEPTION);
        $this->sessionId            = null;
        $this->csrfToken            = null;
    }

    public function generateToken()
    {
        return 'gpgauthv1.3.0|36|' . $this->uuid() . '|gpgauthv1.3.0';
    }

    function uuid()
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function _curlPost($url, $postParams)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($postParams));
        curl_setopt($c, CURLOPT_HEADER, 1);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $response   = curl_exec($c);
        $headerSize = curl_getinfo($c, CURLINFO_HEADER_SIZE);
        $header     = substr($response, 0, $headerSize);
        curl_close($c);

        return $header;
    }

    public function initKeyring()
    {
        $this->privateKey['info'] = $this->gpg->import($this->privateKey['keydata']);
    }

    public function getServerKey()
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $this->serverUrl . '/auth/verify.json');
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($c);
        curl_close($c);

        $responseJson    = json_decode($response, true);
        $this->serverKey = $responseJson['body'];

        $this->serverKey['info'] = $this->gpg->import($this->serverKey['keydata']);

        return $this->serverKey;
    }

    private function _getHeader($header, $name)
    {
        $headerLines = explode("\n", $header);
        $res         = [];

        foreach ($headerLines as $headerLine) {
            if (strpos(strtolower($headerLine), strtolower($name) . ':') !== false) {
                $res = array_merge($res, explode(';', trim(explode(":", $headerLine)[1])));
            }
        }

        if ($res) {
            return implode(';', $res);
        }

        return false;
    }

    public function stage0()
    {
        $token = $this->generateToken();
        $this->getServerKey();
        $this->gpg->addencryptkey($this->serverKey['info']['fingerprint']);
        $encryptedToken = $this->gpg->encrypt($token);

        $post = [
            'data' => [
                'gpg_auth' => [
                    'keyid'               => $this->privateKey['info']['fingerprint'],
                    'server_verify_token' => $encryptedToken,
                ],
            ],
        ];


        $header         = $this->_curlPost($this->serverUrl . '/auth/verify.json', $post);
        $retrievedToken = $this->_getHeader($header, 'X-GPGAuth-Verify-Response');

        if ($retrievedToken !== $token) {
            throw new Exception('Stage 0: Tokens mismatch');
        }

        return $token;
    }

    public function stage1A()
    {
        $post = [
            'data' => [
                'gpg_auth' => [
                    'keyid' => $this->privateKey['info']['fingerprint'],
                ],
            ],
        ];

        $header         = $this->_curlPost($this->serverUrl . '/auth/login.json', $post);
        $encryptedToken = $this->_getHeader($header, 'X-GPGAuth-User-Auth-Token');
        $encryptedToken = (stripslashes(urldecode($encryptedToken)));

        $this->gpg->adddecryptkey($this->privateKey['info']['fingerprint'], $this->privateKey['passphrase']);
        $verify = $this->gpg->decryptverify($encryptedToken, $decryptedToken);

        if ($verify[0]['fingerprint'] !== $this->serverKey['info']['fingerprint']) {
            throw new Exception('Stage 1A: Signature mismatch');
        }

        return $decryptedToken;
    }

    public function stage1B($token)
    {
        $post = [
            'data' => [
                'gpg_auth' => [
                    'keyid'             => $this->privateKey['info']['fingerprint'],
                    'user_token_result' => $token,
                ],
            ],
        ];

        $header = $this->_curlPost($this->serverUrl . '/auth/login.json', $post);

        $status        = $this->_getHeader($header, 'X-GPGAuth-Progress');
        $authenticated = $this->_getHeader($header, 'X-GPGAuth-Authenticated');

        $authenticated = ($status === 'complete' && $authenticated === 'true');
        if ( ! $authenticated) {
            throw new Exception('Stage 1B: Authentication failure');
        }

        $cookieHeader = $this->_getHeader($header, 'Set-Cookie');
        preg_match_all('/passbolt_session=([^;]*);/mi', $cookieHeader, $matches);
        $this->sessionId = $matches[1][0];
    }

    public function getCsrfToken()
    {
        if (!$this->csrfToken) {
            $c = curl_init();
            curl_setopt($c, CURLOPT_URL, $this->serverUrl);
            curl_setopt($c, CURLOPT_HEADER, 1);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($c);
            $headerSize = curl_getinfo($c, CURLINFO_HEADER_SIZE);
            $header     = substr($response, 0, $headerSize);
            curl_close($c);

            $cookieHeader = $this->_getHeader($header, 'Set-Cookie');
            preg_match_all('/csrfToken=([^;]*);/mi', $cookieHeader, $matches);
            $this->csrfToken = $matches[1][0];
        }

        return $this->csrfToken;
    }

    public function getCookie(bool $addCsrfToken = false)
    {
        $cookie = "passbolt_session={$this->sessionId}; path=/; HttpOnly;";
        if ($addCsrfToken) {
            $cookie .= " csrfToken={$this->getCsrfToken()}";
        }

        return $cookie;
    }

    public function login()
    {
        $this->initKeyring();
        $this->stage0();
        $token  = $this->stage1A();
        $this->stage1B($token);
    }
}
