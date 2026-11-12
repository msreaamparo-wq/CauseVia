#!/bin/sh
set -e

a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true

a2enmod mpm_prefork

echo "Checking Apache MPM modules..."
apache2ctl -M 2>&1 | grep mpm

exec apache2-foreground