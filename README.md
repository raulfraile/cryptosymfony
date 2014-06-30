CryptoSymfony: Cryptovirus for Symfony apps
===========================================

CryptoSymfony is a simple cryptovirus created and shared for educational purposes.

More info: [$kernel->infect(): Creating a cryptovirus for Symfony2 apps](http://www.slideshare.net/raulfraile/kernelinfect-creating-a-cryptovirus-for-symfony2-apps)

Warning: This is only for *educational purposes*

## Documentation

The project is divided in two directories:

* `server`: Contains the code to be included in the hacker's server. The `get_public_key` and `get_private_key` scripts
and the backdoor to get the stolen information and recover the data.

* `virus`: Contains the virus code, which will be able to infect the `bootstrap.php.cache` file of a Symfony project. The
`reset.sh` script resets the project.

## Credits

* Raul Fraile ([@raulfraile](https://twitter.com/raulfraile))
* [All contributors](https://github.com/raulfraile/cryptosymfony/contributors)

## License

CryptoSymfony is released under the MIT License. See the bundled LICENSE file for details.