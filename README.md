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
