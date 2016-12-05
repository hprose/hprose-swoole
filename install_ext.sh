wget https://pecl.php.net/get/swoole-1.8.8.tgz
tar zxvfp swoole-1.8.8.tgz
cd swoole-1.8.8
phpize
./configure
make
make install
echo "extension = swoole.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
wget https://pecl.php.net/get/hprose-1.6.5.tgz
tar zxvfp hprose-1.6.5.tgz
cd hprose-1.6.5
phpize
./configure
make
make install
echo "extension = hprose.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
cd ..
