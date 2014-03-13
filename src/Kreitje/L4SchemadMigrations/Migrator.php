<?php namespace Kreitje\L4SchemadMigrations;

use Illuminate\Database\Migrations\Migrator as M;
use Config;

class Migrator extends M {

	public function run( $path, $pretend = false) {

		parent::run( $path, $pretend );

		$key = Config::get('database.default');
		$database = Config::get('database.connections.' . $key . '.database');

		$this->note('<info>Generating schema for: ' . $database . '</info>');

		$builder = new Builder;
		$builder->convert( $database );
		$builder->write();

	}
}
