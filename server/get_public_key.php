<?php

$config = array(
    "digest_alg"       => "sha512",
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);

// create the private and public key
$res = openssl_pkey_new($config);

// extract the private key from $res to $privKey
openssl_pkey_export($res, $privKey);

// extract the public key from $res to $pubKey
$pubKey = openssl_pkey_get_details($res);
$pubKey = $pubKey["key"];

// save the private key
$id = sha1($pubKey);
$filename = __DIR__ . '/keys/' . $id . '.key';
file_put_contents($filename, $privKey);

// return public key
$response = array(
    'id'       => $id,
    'key'      => $pubKey
);

echo json_encode($response);