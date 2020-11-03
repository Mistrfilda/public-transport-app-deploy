#!/bin/bash

echo "Delete older files";
sudo find /var/db-backups/ -type f -name '*.sql' -mtime +7 -exec rm {} \;
echo "Older files deleted";

filename="$(date +%s).sql";
echo "Creating db-backup $filename";

sudo mysqldump --databases public-transport-app --result-file=$filename;

echo "Db backup created - $filename";