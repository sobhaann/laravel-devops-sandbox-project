// Bootstrap the 'epochCluster' InnoDB Cluster (run inside the temporary
// init-cluster container by mysqlsh --js -f):
//
//   1. connect to the seed node mysql1
//   2. create the cluster (or reuse it if it already exists)
//   3. add mysql2 and mysql3 with clone-based recovery
//   4. block until every member reports ONLINE
//
// Exit-code contract: this script must NOT swallow errors. If anything below
// throws, mysqlsh exits non-zero and docker-compose's
//   depends_on: { init-cluster: { condition: service_completed_successfully } }
// keeps the router from starting against a broken/partial cluster.
//
// Why we retry connections here: on first boot the official mysql image runs
// a short-lived "temporary server" while initializing, and a healthcheck can
// report the container healthy during that window. The retries below are the
// authoritative readiness gate, not the healthcheck.

var rootPassword = os.getenv('MYSQL_ROOT_PASSWORD');
if (!rootPassword) {
    throw new Error('MYSQL_ROOT_PASSWORD is not set in the environment');
}
var rootUserPrefix = 'root:' + rootPassword + '@';

function retryOnConnectionError(description, fn, maxAttempts, delaySeconds) {
    var transientPattern = /Can't connect|Lost connection|Connection refused|2002|2003|2013/;
    for (var attempt = 1; attempt <= maxAttempts; attempt++) {
        try {
            return fn();
        } catch (e) {
            var message = e.message || String(e);
            var isTransient = transientPattern.test(message);
            if (!isTransient || attempt === maxAttempts) {
                print(description + ' failed permanently: ' + message + '\n');
                throw e;
            }
            print(description + ': attempt ' + attempt + '/' + maxAttempts +
                  ' failed (' + message + '), retrying in ' + delaySeconds + 's...\n');
            os.sleep(delaySeconds);
        }
    }
}

print('Connecting to seed instance mysql1:3306...\n');
retryOnConnectionError(
    'Connecting to mysql1',
    function () { shell.connect(rootUserPrefix + 'mysql1:3306'); },
    20,
    3
);

var cluster;
try {
    cluster = dba.getCluster('epochCluster');
    print('Cluster "epochCluster" already exists - reusing it.\n');
} catch (e) {
    print('No existing cluster found. Creating "epochCluster"...\n');
    // super_read_only is cleared automatically by modern AdminAPI versions;
    // clearReadOnly is deprecated.
    cluster = dba.createCluster('epochCluster');
}

function ensureMember(instanceAddress) {
    var topology = cluster.status().defaultReplicaSet.topology;

    if (topology[instanceAddress] !== undefined) {
        print(instanceAddress + ' is already a cluster member - skipping.\n');
        return;
    }

    // recoveryMethod 'clone': the joining node wipes its datadir and copies
    // everything from a donor. mysqld restarts itself afterwards, which is
    // why the server containers need `restart: always`.
    print('Adding ' + instanceAddress + ' to the cluster (recoveryMethod: clone)...\n');
    retryOnConnectionError(
        'Adding ' + instanceAddress,
        function () {
            cluster.addInstance(rootUserPrefix + instanceAddress, { recoveryMethod: 'clone' });
        },
        20,
        3
    );
}

ensureMember('mysql2:3306');
ensureMember('mysql3:3306');

// addInstance may return before distributed recovery has fully finished;
// do not hand the cluster to the router until every member is ONLINE.
function waitForAllOnline(timeoutSeconds) {
    print('\nWaiting up to ' + timeoutSeconds + 's for all members to become ONLINE...\n');
    var deadline = Date.now() + timeoutSeconds * 1000;
    while (Date.now() < deadline) {
        var topology = cluster.status().defaultReplicaSet.topology;
        var allOnline = true;
        for (var address in topology) {
            if (topology[address].status !== 'ONLINE') {
                allOnline = false;
                print('  ' + address + ': ' + topology[address].status + '\n');
            }
        }
        if (allOnline) {
            print('All members ONLINE.\n');
            return;
        }
        os.sleep(5);
    }
    throw new Error('Timed out waiting for all members to be ONLINE');
}

waitForAllOnline(300);

print('\n--- INNODB CLUSTER FULLY PROVISIONED ---\n');
print(cluster.status());
