cryptocoin_scrypt_stratum
=========================

A javascript scrypt cryptocurrency miner that uses a PHP built stratum server proxy that connects to a mining pool.

Idea: users that connect to your website mine cryptocurrency for you.

I originally created this to mine Dogecoins, but it should work for Litecoin or other cryptocurrencies that use scrypt as well, as long as they also use the stratum protocol for distributing work.

To install and run:
* edit w.php and run.php and modify the j.txt and w.txt file locations
* edit run.php and put in your pool information
* edit work_manager.js and make sure it is doing AJAX requests to the correct location for your particular web server setup, search for get(
* edit work_manager.js and change debug=true for testing
* run run.php in the background: php run.php &, or in a screen, etc.
* look at test.html and copy it or make a similar page (make sure the work_manager.js is pointed to the right place as well)
* load up test.html in your browser and open javascript console

This is purely CPU mining, which is pretty terrible in javascript from my testing.  Nearly all of the processing time takes place in scrypt(), so perhaps if a more optimized scrypt() javascript library comes out, this will work better.  One worker process on a decent system will perform about 300-600 hashes/sec/client.  Two workers will get you closer to 1k/sec/client.

Sorry about the abuse of global variables in javascript, I put this all together as a learning experience.  It does work though and has been tested :)

Resources used to research & build the miner:
* [Stratum mining protocol](http://mining.bitcoin.cz/stratum-mining)
* [Stratum network protocol specification (not as useful)](https://docs.google.com/a/armorgames.com/document/d/17zHy1SUlhgtCMbypO8cHgpWH73V5iUQKk_0rWvMqSNs/edit?hl=en_US)
* [Extranonce2 and other info](https://www.btcguild.com/new_protocol.php)
* [Scrypt details](https://litecoin.info/Scrypt)
* [Javascript scrypt library](https://github.com/tonyg/js-scrypt)
* [CryptoJS (for sha256 in javascript)](https://code.google.com/p/crypto-js/)
* [Full mining example, info about big-endian and little-endian](http://bitcoin.stackexchange.com/questions/22929/full-example-data-for-scrypt-stratum-client)
* [CGMiner source code](https://github.com/ckolivas/cgminer/blob/master/cgminer.c)
* [Stratum Server source code](https://github.com/Crypto-Expert/stratum-mining/blob/master/lib/template_registry.py#L187)
* [Another mining worked example](http://thedestitutedeveloper.blogspot.com/2014/03/stratum-mining-block-headers-worked.html)
