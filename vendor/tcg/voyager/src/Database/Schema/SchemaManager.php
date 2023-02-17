<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Types\Type;

abstract class SchemaManager
{
    // todo: trim parameters

    public static function __callStatic($method, $args)
    {
        return static::manager($table = null)->$method(...$args);
    }

    public static function manager($table = null)
    {
        $tables = 'sqlsrv';
        if((Request()->databasename !='core' || Request()->databasename !='sqlsrv')){
            $tables = Request()->databasename;
        }
        if((Request()->driver !='core' || Request()->driver !='sqlsrv')){
            $tables = Request()->driver;
        }
        if($table != null){
            $tables = $table;
        }
       
        return DB::connection($tables)->getDoctrineSchemaManager();
    }

    public static function getDatabaseConnection($table = null)
    {
        if((Request()->databasename !='core' || Request()->databasename !='sqlsrv')){
            $tables = Request()->databasename;
        }
        if((Request()->driver !='core' || Request()->driver !='sqlsrv')){
            $tables = Request()->driver;
        }
        if($table != null){
            $tables = $table;
        }
        return DB::connection($tables)->getDoctrineConnection();
    }

    public static function tableExists($table,$dbname=null)
    {
        if (!is_array($table)) {
            $table = [$table];
        }
        if((Request()->databasename !='core' || Request()->databasename !='sqlsrv')){
            $tables = Request()->databasename;
        }
        if((Request()->driver !='core' || Request()->driver !='sqlsrv')){
            $tables = Request()->driver;
        }
        if($table != null){
            $tables = $dbname;
        }
        return static::manager($tables)->tablesExist($table);
    }

    public static function listTables()
    {
        $tables = [];
        $dbname = 'sqlsrv';
        if((Request()->databasename !='core' || Request()->databasename !='sqlsrv')){
            $dbname = Request()->databasename;
        }
        if((Request()->driver !='core' || Request()->driver !='sqlsrv')){
            $dbname = Request()->driver;
        }
       
        foreach (static::manager($dbname)->listTableNames() as $tableName) {
            $tables[$tableName] = static::listTableDetails($tableName);
        }

        return $tables;
    }

    /**
     * @param string $tableName
     *
     * @return \TCG\Voyager\Database\Schema\Table
     */
    public static function listTableDetails($tableName,$dbconnect = null)
    {
        $columns = static::manager($dbconnect)->listTableColumns($tableName);

        $foreignKeys = [];

        if((Request()->databasename !='core' || Request()->databasename !='sqlsrv')){
            $tables = Request()->databasename;
        }
        if((Request()->driver !='core' || Request()->driver !='sqlsrv')){
            $tables = Request()->driver;
        }
        if($dbconnect != null){
            $tables = $dbconnect;
        }
        if (static::manager($tables)->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $foreignKeys = static::manager($tables)->listTableForeignKeys($tableName);
        }

        $indexes = static::manager($tables)->listTableIndexes($tableName);

        return new Table($tableName, $columns, $indexes, $foreignKeys, false, []);
    }

    /**
     * Describes given table.
     *
     * @param string $tableName
     *
     * @return \Illuminate\Support\Collection
     */
    public static function describeTable($tableName, $dbconnect=null)
    {
        Type::registerCustomPlatformTypes();
        if((Request()->databasename !='core' || Request()->databasename !='sqlsrv')){
            $tables = Request()->databasename;
        }
        if((Request()->driver !='core' || Request()->driver !='sqlsrv')){
            $tables = Request()->driver;
        }
        if($dbconnect != null){
            $tables = $dbconnect;
        }
        $table =static::listTableDetails($tableName,$tables);

        return collect($table->columns)->map(function ($column) use ($table) {
            $columnArr = Column::toArray($column);

            $columnArr['field'] = $columnArr['name'];
            $columnArr['type'] = $columnArr['type']['name'];

            // Set the indexes and key
            $columnArr['indexes'] = [];
            $columnArr['key'] = null;
            if ($columnArr['indexes'] = $table->getColumnsIndexes($columnArr['name'], true)) {
                // Convert indexes to Array
                foreach ($columnArr['indexes'] as $name => $index) {
                    $columnArr['indexes'][$name] = Index::toArray($index);
                }

                // If there are multiple indexes for the column
                // the Key will be one with highest priority
                $indexType = array_values($columnArr['indexes'])[0]['type'];
                $columnArr['key'] = substr($indexType, 0, 3);
            }

            return $columnArr;
        });
    }

    public static function listTableColumnNames($tableName,$dbconnection = null)
    {
        Type::registerCustomPlatformTypes();
        if((Request()->databasename !='core' || Request()->databasename !='sqlsrv')){
            $tables = Request()->databasename;
        }
        if((Request()->driver !='core' || Request()->driver !='sqlsrv')){
            $tables = Request()->driver;
        }
        if($dbconnection != null){
            $tables = $dbconnection;
        }
        $columnNames = [];

        foreach (static::manager($tables)->listTableColumns($tableName) as $column) {
            $columnNames[] = $column->getName();
        }

        return $columnNames;
    }

    public static function createTable($table)
    {
        if (!($table instanceof DoctrineTable)) {
            $table = Table::make($table);
        }
        $dbconnection = 'sqlsrv';
       if((Request()->databasename !='core' || Request()->databasename !='sqlsrv')){
            $tables = Request()->databasename;
        }
        if((Request()->driver !='core' || Request()->driver !='sqlsrv')){
            $tables = Request()->driver;
        }
        if($dbconnection != null){
            $tables = $dbconnection;
        }
        static::manager($tables)->createTable($table);
    }

    public static function getDoctrineTable($table)
    {
        $table = trim($table);

        if (!static::tableExists($table)) {
            throw SchemaException::tableDoesNotExist($table);
        }
        $dbconnection = 'sqlsrv';
        if((Request()->databasename !='core' || Request()->databasename !='sqlsrv')){
            $tables = Request()->databasename;
        }
        if((Request()->driver !='core' || Request()->driver !='sqlsrv')){
            $tables = Request()->driver;
        }
        if($dbconnection != null){
            $tables = $dbconnection;
        }
        return static::manager($tables)->listTableDetails($table);
    }

    public static function getDoctrineColumn($table, $column)
    {
        return static::getDoctrineTable($table)->getColumn($column);
    }
}
