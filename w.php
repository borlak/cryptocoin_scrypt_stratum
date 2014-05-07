<?php

/**
 * This is the file that workers(users/clients) will be AJAX'ing to get their job data, and to write any solutions
 * they find from scrypt.
 */

if((!isset($_GET['n']) || !isset($_GET['i'])) && !isset($_GET['w'])) {
    die;
}

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if(isset($_GET['w'])) {
    $job_file_location = '/full/path/to/where/StratumServer/writes/jobdata/j.txt';
    if(($data = file_get_contents($job_file_location)) !== false) {
        echo $data;
    }
    die();
}

/**
 * At this point, worker is submitting data, not requesting it, so we save the data to
 * a location that the running stratum proxy will read and submit.
 */

// sanitize?  sanitize
$data = array(
    'job_id'      => filter_input(INPUT_GET, 'i', FILTER_SANITIZE_STRING),
    'nonce'       => filter_input(INPUT_GET, 'n', FILTER_SANITIZE_STRING),
    'extranonce2' => filter_input(INPUT_GET, 'e', FILTER_SANITIZE_STRING),
    'ntime'       => filter_input(INPUT_GET, 't', FILTER_SANITIZE_STRING),
    'relative_difficulty' => filter_input(INPUT_GET, 'd', FILTER_SANITIZE_NUMBER_INT),
);

$work_file_location = '/full/path/to/where/you/write/worker/solution/w.txt';

file_put_contents($work_file_location, json_encode($data)."\n", FILE_APPEND);
