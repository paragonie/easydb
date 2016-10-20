#!/bin/bash
clean=1 # Clean up?
function cleanupPhpUnit
{
  # Cleanup
  if [ "$clean" -eq 1 ]; then
      echo -e "\033[32mCleaning Up!\033[0m"
      rm -f phpunit.phar
      rm -f phpunit.phar.asc
  fi
}
gpg --fingerprint D8406D0D82947747293778314AA394086372C20A
if [ $? -ne 0 ]; then
    echo -e "\033[33mDownloading PGP Public Key...\033[0m"
    gpg --recv-keys D8406D0D82947747293778314AA394086372C20A
    # Sebastian Bergmann <sb@sebastian-bergmann.de>
    gpg --fingerprint D8406D0D82947747293778314AA394086372C20A
    if [ $? -ne 0 ]; then
        echo -e "\033[31mCould not download PGP public key for verification\033[0m"
        exit
    fi
fi

if [ "$clean" -eq 1 ]; then
    # Let's clean them up, if they exist
    if [ -f phpunit.phar ]; then
        rm -f phpunit.phar
    fi
    if [ -f phpunit.phar.asc ]; then
        rm -f phpunit.phar.asc
    fi
fi

# Let's grab the latest release and its signature
if [ ! -f phpunit.phar ]; then
    if which wget; then
      wget https://phar.phpunit.de/phpunit.phar
    else
      php -r "stream_copy_to_stream(fopen('https://phar.phpunit.de/phpunit.phar', 'r'), fopen('./phpunit.phar', 'w'));"
    fi

fi
if [ ! -f phpunit.phar.asc ]; then
    if which wget; then
      wget https://phar.phpunit.de/phpunit.phar.asc
    else
      php -r "stream_copy_to_stream(fopen('https://phar.phpunit.de/phpunit.phar.asc', 'r'), fopen('./phpunit.phar.asc', 'w'));"
    fi
fi

# Verify before running
gpg --verify phpunit.phar.asc phpunit.phar
if [ $? -eq 0 ]; then
    echo
    echo -e "\033[33mBegin Unit Testing\033[0m"
    # Run the testing suite
    if [ "$TRAVIS_PHP_VERSION" = 'hhvm' ]; then
      echo 'xdebug.enable = On' >> /etc/hhvm/php.ini;
      if hhvm -v Eval.EnableHipHopSyntax=true phpunit.phar --verbose; then
        cleanupPhpUnit;
      else
        echo "build fa" 1>&2
        exit 1
      fi
    else
      if php phpunit.phar --verbose; then
        cleanupPhpUnit;
      else
        exit 1
      fi
    fi
else
    echo
    chmod -x phpunit.phar
    mv phpunit.phar /tmp/bad-phpunit.phar
    mv phpunit.phar.asc /tmp/bad-phpunit.phar.asc
    echo -e "\033[31mSignature did not match! Check /tmp/bad-phpunit.phar for trojans\033[0m"
    exit 1
fi
