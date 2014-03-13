#Laravel4 Schemad Migrations

This will generate a schema.php file in your app/database/migrations file with the current database schema. This 
only works with MySQL.

This code is based off of http://laravelsnippets.com/snippets/convert-an-existing-mysql-database-to-migrations
 by [michaeljcalkins](https://twitter.com/michaeljcalkins)


##Setup

Modify your **config/app.php** and update the providers to:

Comment out the following line:

	'Illuminate\Database\MigrationServiceProvider',

Add in the following line:

	'Kreitje\L4SchemadMigrations\L4SchemadMigrationsServiceProvider'