<?php namespace Kreitje\L4SchemadMigrations;

use DB;
use Log;
use Str;

/**
 * Class modified by kreitje
 * ---------------------------------------------------------
 * 
 * This script converts an existing MySQL database to migrations in Laravel 4.
 * 
 * Credits to @Christopher Pitt, Lee Zhen Yong <bruceoutdoors@gmail.com> and @michaeljcalkins, whom this is forked off
 **/

class Builder {

	private static $ignore = array('migrations');
    private static $database = "";
    private static $migrations = false;
    private static $schema = array();
    private static $selects = array('column_name as Field', 'column_type as Type', 'is_nullable as Null', 'column_key as Key', 'column_default as Default', 'extra as Extra', 'data_type as Data_Type');
    private static $instance;
    private static $up = "";
    private static $down = "";

    private static function getTables()
    {
        return DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema="' . self::$database . '" ORDER BY `table_name` ASC');
    }

    private static function getTableDescribes($table)
    {
        return DB::table('information_schema.columns')
                ->where('table_schema', '=', self::$database)
                ->where('table_name', '=', $table)
                ->get(self::$selects);
    }

    private static function getForeignTables()
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('CONSTRAINT_SCHEMA', '=', self::$database)
                ->where('REFERENCED_TABLE_SCHEMA', '=', self::$database)
                ->select('TABLE_NAME')->distinct()
                ->get();
    }

    private static function getForeigns($table)
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('CONSTRAINT_SCHEMA', '=', self::$database)
                ->where('REFERENCED_TABLE_SCHEMA', '=', self::$database)
                ->where('TABLE_NAME', '=', $table)
                ->select('COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME')
                ->get();
    }

    private static function compileSchema()
    {
        $upSchema = "";
        $downSchema = "";
        $newSchema = "";
        foreach (self::$schema as $name => $values) {
            if (in_array($name, self::$ignore)) {
                continue;
            }
            $upSchema .= "
		{$values['up']}";

            $downSchema .= "
		{$values['down']}";
        }

        $schema = "<?php
 
/**
 * Schema Created: " . date("Y-m-d H:i:s") . "
 * This migration file was automatically created based on your database the last time you 
 * ran php artisan migrate. This can be used as a readable representation of your database.
**/
 
class Create" . str_replace('_', '', Str::title(self::$database)) . "Database {
 
	public function up()
	{
		" . $upSchema . "
		" . self::$up . "
	}
 
	public function down()
	{
		" . $downSchema . "
		" . self::$down . "
	}
}";

        return $schema;
    }

    public function up($up)
    {
        self::$up = $up;
        return self::$instance;
    }

    public function down($down)
    {
        self::$down = $down;
        return self::$instance;
    }

    public function ignore($tables)
    {
        self::$ignore = array_merge($tables, self::$ignore);
        return self::$instance;
    }

    public function migrations()
    {
        self::$migrations = true;
        return self::$instance;
    }

    public function write()
    {
        $schema = self::compileSchema();
        $filename = "schema.php";

        file_put_contents( app_path() . "/database/migrations/{$filename}", $schema);
    }

    public function get()
    {
        return self::compileSchema();
    }

    public function convert($database)
    {
        self::$instance = new self();
        self::$database = $database;
        $table_headers = array('Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
        $tables = self::getTables();
        foreach ($tables as $key => $value) { Log::info('Table: ' . $key);
            if (in_array($value->table_name, self::$ignore)) {
                continue;
            }

            $down = "Schema::drop('{$value->table_name}');";
            $up = "Schema::create('{$value->table_name}', function($" . "table) {\n";
            $tableDescribes = self::getTableDescribes($value->table_name);

            $hasTimestamps = false;
            $hasSoftDeletes = false;

            $hasCreatedAt = false;
            $hasUpdatedAt = false;

            foreach ($tableDescribes as $values) {
                
                $method = "";
                $para = strpos($values->Type, '(');
                $type = $para > -1 ? substr($values->Type, 0, $para) : $values->Type;
                $numbers = "";
                $nullable = $values->Null == "NO" ? "" : "->nullable()";
                $default = empty($values->Default) ? "" : "->default(\"{$values->Default}\")";
                $unsigned = strpos($values->Type, "unsigned") === false ? '' : '->unsigned()';
                $unique = $values->Key == 'UNI' ? "->unique()" : "";

                if ($values->Field == 'deleted_at' && $values->Null != 'NO') {
                	$up .= "			$" . "table->softDeletes();\n";
                	continue;
                }

                switch ($type) {
                    case 'int' :
                        $method = 'integer';
                        break;
                    case 'char' :
                    case 'varchar' :
                        $para = strpos($values->Type, '(');
                        $numbers = ", " . substr($values->Type, $para + 1, -1);
                        $method = 'string';
                        break;
                    case 'float' :
                        $method = 'float';
                        break;
                    case 'decimal' :
                        $para = strpos($values->Type, '(');
                        $numbers = ", " . substr($values->Type, $para + 1, -1);
                        $method = 'decimal';
                        break;
                    case 'tinyint' :
                        $method = 'boolean';
                        break;
                    case 'date':
                        $method = 'date';
                        break;
                    case 'timestamp' :
                        $method = 'timestamp';
                        break;
                    case 'datetime' :
                        $method = 'dateTime';
                        break;
                    case 'mediumtext' :
                        $method = 'mediumtext';
                        break;
                    case 'text' :
                        $method = 'text';
                        break;
                }

                if ($values->Key == 'PRI')
                    $method = 'increments';
                
                $up .= "			$" . "table->{$method}('{$values->Field}'{$numbers}){$nullable}{$default}{$unsigned}{$unique};\n";
            }

            $up .= "		});\n\n";

			/**
			 * Changed created_at and updated_at to timestamps();
			 **/

			if (strpos( $up, '$table->timestamp(\'created_at\')->default("0000-00-00 00:00:00");') &&
				strpos( $up, '$table->timestamp(\'updated_at\')->default("0000-00-00 00:00:00");') ) {

				$up = str_replace('$table->timestamp(\'created_at\')->default("0000-00-00 00:00:00");', '$table->timestamps();', $up);
				$up = str_replace('			$table->timestamp(\'updated_at\')->default("0000-00-00 00:00:00");'."\n", '', $up);
			}
			


            self::$schema[$value->table_name] = array(
                'up' => $up,
                'down' => $down
            );
        }

        // add foreign constraints, if any
        $tableForeigns = self::getForeignTables();
        if (sizeof($tableForeigns) !== 0) {
            foreach ($tableForeigns as $key => $value) {
                $up = "Schema::table('{$value->TABLE_NAME}', function($" . "table) {\n";
                $foreign = self::getForeigns($value->TABLE_NAME);
                foreach ($foreign as $k => $v) {
                    $up .= " $" . "table->foreign('{$v->COLUMN_NAME}')->references('{$v->REFERENCED_COLUMN_NAME}')->on('{$v->REFERENCED_TABLE_NAME}');\n";
                }
                $up .= " });\n\n";
                self::$schema[$value->TABLE_NAME . '_foreign'] = array(
                    'up' => $up,
                    'down' => $down
                );
            }
        }

        return self::$instance;
    }

}