<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
date_default_timezone_set('America/Los_Angeles');

require_once('StratumServer.php');
require_once('Util.php');
require_once('colour.php');

$pools = array(
    0 => array(
        'name' => 'Whatever you want to call this connection',
        'host' => 'some.stratum.com',
        'port' => '3333',
        'user' => 'my.user',
        'pass' => 'password'
    ),
    1 => array(
        'name' => 'Whatever you want to call this connection',
        'host' => 'some.stratum.com',
        'port' => '3333',
        'user' => 'my.user',
        'pass' => 'password'
    ),
);

$chosen_pool = 1;
$name = $pools[$chosen_pool]['name'];

echo "Chosen pool: {$name}\n";

$server = new StratumServer();

stratumInit($chosen_pool);

while(1) {
    if(!$server->connected()) {
        stratumInit($chosen_pool);
        continue;
    }

    check($server->poll(30,1,'/full/path/to/where/you/write/worker/solution/w.txt'));
}

echo "-=Finished=-\r\n";
die;

function stratumInit($chosen_pool) {
    global $server, $pools;
    
    $host = $pools[$chosen_pool]['host'];
    $port = $pools[$chosen_pool]['port'];
    $user = $pools[$chosen_pool]['user'];
    $pass = $pools[$chosen_pool]['pass'];

    if(!$server->connected()) {
        check($server->connect($host, $port), true);

        check($server->subscribe(), true);
        check($server->poll(5));

        check($server->authorize($user, $pass), true);
        check($server->poll(5));
    } else {
        // don't want to spam trying connections
        sleep(1);
    }
}

/**
 * Checks the results from a stdResult and echoes the..result
 * @param array $result
 * @param boolean $die die on a failed result?
 */
function check(array $result, $die = false) {
    if(is_array($result[0])) {
        foreach($result as $result_next) {
            check($result_next, $die);
        }
    }

    $date_str = date('d/m/y H:i:s')." ";

    if($result[0] === false) {
        echo $date_str.B_RED."FAIL: ".CLEAR.trim($result[1])."\r\n";
        if($die) {
            die;
        }
    }

    if($result[0] === true && isset($result[1]) && is_string($result[1]) && strlen($result[1]) > 0) {
        echo $date_str.B_GREEN."SUCCESS: ".CLEAR.trim($result[1])."\r\n";
    }
}
