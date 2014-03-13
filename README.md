#Laravel4 Schemad Migrations

This will generate a schema.php file in your app/database/migrations file based on your current database schema. This 
only works with MySQL. When your run **php artisan migrate** it will automatically generate the schema file for you after the migrations have run.

This code is based off of http://laravelsnippets.com/snippets/convert-an-existing-mysql-database-to-migrations
 by [michaeljcalkins](https://twitter.com/michaeljcalkins)


##Setup

Modify your **config/app.php** and update the providers to:

Comment out the following line:

	'Illuminate\Database\MigrationServiceProvider',

Add in the following line:

	'Kreitje\L4SchemadMigrations\L4SchemadMigrationsServiceProvider'
