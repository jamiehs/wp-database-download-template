#!/bin/sh
cd `dirname $0`

# Script to download WordPress database backup and update local copy

#################################################
# Variables and Constants
#################################################

# Set the default database name, allow it to be overriden
: ${LOCAL_DATABASE_NAME:=wordpress}

BLOG_PREFIX="wp"
BLOG_HOSTNAME="www.example.com/blog"
BLOG_LOCAL_HOSTNAME="www.example.dev/blog"

BACKUPS_DIR="/somelocation/on/your/host/db_backups"
SSH_LOGIN_STRING="user@host"

LOCAL_TEMP_FILE="/tmp/database_file_name.sql.gz"
LOCAL_TEMP_FILE_EXTRACTED="/tmp/database_file_name.sql"

DISABLE_PLUGINS="akismet|wp-mail-smtp|cloudflare|varnish-http-purge|disqus-notify-content-author"


#################################################
# Commands
#################################################

echo "Downloading latest database backup..."
scp $SSH_LOGIN_STRING:$BACKUPS_DIR/$(ssh $SSH_LOGIN_STRING ls -t $BACKUPS_DIR | head -1) $LOCAL_TEMP_FILE

echo "Decompressing GZ file..."
gzip -df $LOCAL_TEMP_FILE

echo "Importing database file..."
mysql ${LOCAL_DATABASE_NAME} < $LOCAL_TEMP_FILE_EXTRACTED

echo "Updating domain references..."
mysql --execute="UPDATE ${LOCAL_DATABASE_NAME}.${BLOG_PREFIX}_posts SET guid=REPLACE(guid,'${BLOG_HOSTNAME}','${BLOG_LOCAL_HOSTNAME}');"
mysql --execute="UPDATE ${LOCAL_DATABASE_NAME}.${BLOG_PREFIX}_options SET option_value=REPLACE(option_value,'${BLOG_HOSTNAME}','${BLOG_LOCAL_HOSTNAME}');"

echo "Clearing transients..."
mysql --execute="DELETE FROM ${LOCAL_DATABASE_NAME}.${BLOG_PREFIX}_options WHERE option_name LIKE '_transient%' OR option_name LIKE '_site_transient%';"

echo "Turning off production only plugins..."
LOCAL_DATABASE_NAME=$LOCAL_DATABASE_NAME BLOG_PREFIX=$BLOG_PREFIX DISABLE_PLUGINS=$DISABLE_PLUGINS php dev-disable-plugins.php

echo "Cleaning up files.."
rm /tmp/database_file_name.sql

echo "DONE!"
