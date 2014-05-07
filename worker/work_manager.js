try {
var web_worker_limit = 1;   // how many web workers do you want the client to run?
var workers = new Array();
var debug = false;          // if true you will see console messages from work manager and worker

/**
 * Periodically gets work from the public file that the server writes to.  Make sure you are doing
 * requests often enough, as some pools may send out new jobs every 10 seconds, or every minute, etc.
 */
function getWork() {
    // quick example of how you might not have logged in users mine
    /*
    if(document.cookie.indexOf('logged_in=') >= 0) {
        for(var i = 0; i < workers.length; i++) {
            workers[i].terminate();
        }
        workers = new Array();
        return;
    }
    */

    jQuery.get('/w', 'w=1', function(data) {
        if(data.length > 0) {
            ret = data.split("\n");
            if(ret.length == 2) {
                vals = ret[0];
                b = JSON.parse(ret[1]);
                updateWorkers({vals:vals,b:b});
            }
        }
    });

    setTimeout('getWork()', 15000); // 15 seconds
}

/**
 * The worker has found a hash and is reporting back the details for the stratum server to submit to
 * the pool.  reldiff is the relative difficulty (probably should have called it estimated difficulty)
 * of the solution.
 * @param {string} nonce
 * @param {string} extranonce2
 * @param {string} ntime
 * @param {string} job_id
 * @param {integer} reldiff
 */
function foundHash(nonce, extranonce2, ntime, job_id, reldiff) {
    jQuery.get('/w', 'n='+nonce+'&e='+extranonce2+'&t='+ntime+'&i='+job_id+'&d='+reldiff);
    if(debug) {
        console.log('Submitting work with nonce: ['+nonce+'] extranonce2['+extranonce2+'] ntime: ['+ntime+'] for job: ['+job_id+']');
    }
}

/**
 * Worker has notified us of new data, lets parse it.
 * @param {object} e
 */
function workerData(e) {
    data = JSON.parse(e.data);
    if(debug && data.message) {
        console.log('Message from worker:'+data.message);
    }

    if(data.nonce) {
        foundHash(data.nonce, data.extranonce2, data.ntime, data.job_id, data.reldiff);
        
        if(debug && data.info) {
            console.log(data.info);
        }
    }
}

/**
 * Send a message to all the workers.
 * @param {object} data
 */
function updateWorkers(data) {
    // terminate all old workers
    if(workers.length == 0) {
        for(var i = 0; i < web_worker_limit; i++) {
            workers.push(new Worker('/javascript/d/worker.js'));
            workers[i].onmessage = workerData;
            workers[i].postMessage(JSON.stringify(data));
        }
    }

    // new job info
    for(var i = 0; i < workers.length; i++) {
        workers[i].postMessage(JSON.stringify(data));
    }
}

/**
 * Tell all workers to stop mining.
 */
function workStop() {
    for(var i = 0; i < workers.length; i++) {
        workers[i].postMessage(JSON.stringify({stop:true}));
    }
}

/**
 * Tell all workers to start mining again.
 */
function workStart() {
    for(var i = 0; i < workers.length; i++) {
        workers[i].postMessage(JSON.stringify({start:true}));
    }
}

/**
 * Make sure jQuery is loaded, since we use it for ajax calls.
 */
function jqueryLoaded() {
    if(window.jQuery) {
        // make sure browser supports web workers
        if(typeof Worker !== "undefined") {
            getWork();
        }
    } else {
        setTimeout("jqueryLoaded()",500);
    }
}

setTimeout("jqueryLoaded()", 1500);

} catch(err) {
}
