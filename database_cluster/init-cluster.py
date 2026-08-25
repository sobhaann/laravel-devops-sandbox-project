# InnoDB Cluster bootstrap via the MySQL Shell AdminAPI (Python mode).
#
# Executed by the one-shot `init-cluster` compose service:
#   mysqlsh --py --file /init-cluster.py
#
# Python mode is used because Ubuntu's mysql-shell package is built without
# JavaScript support. Credentials are read from the environment with
# os.environ, so no template substitution (envsubst) is needed.
#
# The script is idempotent: it can safely be re-run after a partial bootstrap.

import os

root_pw = os.environ.get("MYSQL_ROOT_PASSWORD")
if not root_pw:
    raise RuntimeError("MYSQL_ROOT_PASSWORD is not set in the environment.")

SEED_HOST = "mysql1"                # instance that bootstraps the group
JOIN_HOSTS = ["mysql2", "mysql3"]   # instances that join afterwards
CLUSTER_NAME = "epochCluster"


def uri(host):
    return "root:%s@%s:3306" % (root_pw, host)


# ---------------------------------------------------------------------------
# Step 1: bring every instance up to Group Replication requirements.
# dba.configure_instance() validates each server and persists whatever is
# still missing. With the shared innodb-cluster.cnf this is normally a no-op,
# which keeps provisioning deterministic and restart-free.
# ---------------------------------------------------------------------------
for host in [SEED_HOST] + JOIN_HOSTS:
    print("\n=== Configuring instance %s ===" % host)
    dba.configure_instance(uri(host), {"restart": False})

# ---------------------------------------------------------------------------
# Step 2: create the cluster on the seed instance (or reuse it when this
# script is re-run after a previous partial run).
# ---------------------------------------------------------------------------
print('\n=== Creating cluster "%s" on %s ===' % (CLUSTER_NAME, SEED_HOST))
shell.connect(uri(SEED_HOST))

try:
    cluster = dba.get_cluster(CLUSTER_NAME)
    print("\nCluster already exists, reusing it.")
except Exception:
    cluster = dba.create_cluster(CLUSTER_NAME, {"clearReadOnly": True})

# ---------------------------------------------------------------------------
# Step 3: join the remaining instances. Fresh instances carry local GTID
# history generated during their own initialization (before ever joining a
# group), so 'clone' recovery is used deliberately: the joining instance's
# data is replaced with a consistent snapshot from the group.
# ---------------------------------------------------------------------------
for join_host in JOIN_HOSTS:
    member = join_host + ":3306"
    topology = cluster.status()["defaultReplicaSet"]["topology"]

    if member in topology:
        print("\n%s is already part of the cluster, skipping." % member)
        continue

    print("\n=== Adding %s (clone recovery) ===" % member)
    cluster.add_instance(uri(join_host), {"recoveryMethod": "clone"})

print("\n--- INNODB CLUSTER READY ---")
print(cluster.status())
