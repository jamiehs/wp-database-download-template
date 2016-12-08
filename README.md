# wp-database-download-template
A template for a quick download and import strategy for complex WordPress sites.

## What this script does (tested on OS X)

1. Downloads the latest database backup from a remote drirectory you specify (using `scp`)
1. Decompresses the gzipped database dump
1. Imports the database to the local database specified (uses [`.my.cnf`](https://gist.github.com/jamiehs/dabf901768a0987cc8f542fdd0155d05))
1. Runs a couple of find and replace statements on the WordPress database to update domain references
1. Deletes WordPress transient caches in the database
1. Turns off plugins specified in the config
1. Deletes temporary file for database backup
