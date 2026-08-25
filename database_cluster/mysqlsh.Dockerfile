# Minimal image containing only the MySQL Shell (AdminAPI) used by the
# one-shot `init-cluster` compose service to provision the InnoDB Cluster.
#
# mysql-shell comes from the Ubuntu archive (same 8.0 series as the mysql:8.0
# server images). There is no official Docker Hub image for MySQL Shell, so
# this small build replaces the previous "alpine + envsubst + mysqlsh" attempt
# (Alpine has no mysql-shell package at all, which is why init failed with
# exit 127).
FROM ubuntu:24.04

# C.UTF-8 is built into glibc; mysqlsh otherwise warns about en_US.UTF-8.
ENV LANG=C.UTF-8 LC_ALL=C.UTF-8

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        mysql-shell \
    && rm -rf /var/lib/apt/lists/*

ENTRYPOINT ["mysqlsh"]
