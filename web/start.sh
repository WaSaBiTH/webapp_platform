#!/bin/bash
# Start cron daemon
service cron start

# Ensure environment variables are passed to cron tasks
printenv | grep -v "no_proxy" > /etc/environment

# Start apache foreground
exec apache2-foreground
