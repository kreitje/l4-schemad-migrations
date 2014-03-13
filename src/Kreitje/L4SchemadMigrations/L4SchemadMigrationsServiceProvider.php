<?php namespace Kreitje\L4SchemadMigrations;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\MigrationServiceProvider;


class L4SchemadMigrationsServiceProvider extends MigrationServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerRepository();
		$this->registerSchemaMigrator();
		$this->registerCommands();
	}

	public function registerSchemaMigrator() {
		$this->app->bindShared('migrator', function($app)
		{
			$repository = $app['migration.repository'];

			return new Migrator($repository, $app['db'], $app['files']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
