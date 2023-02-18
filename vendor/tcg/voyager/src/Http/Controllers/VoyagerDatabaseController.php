<?php

namespace TCG\Voyager\Http\Controllers;

use Doctrine\DBAL\Schema\Schema;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as FacadesSchema;
use Illuminate\Support\Str;
use TCG\Voyager\Database\DatabaseUpdater;
use TCG\Voyager\Database\Schema\Column;
use TCG\Voyager\Database\Schema\Identifier;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Database\Schema\Table;
use TCG\Voyager\Database\Types\Type;
use TCG\Voyager\Events\TableAdded;
use TCG\Voyager\Events\TableDeleted;
use TCG\Voyager\Events\TableUpdated;
use TCG\Voyager\Facades\Voyager;
use App\Models\Database\Database;

class VoyagerDatabaseController extends Controller
{
    public function index()
    {
        $this->authorize('browse_database');

        $dataTypes = Voyager::model('DataType')->select('id', 'name', 'slug')->get()->keyBy('name')->toArray();

        // retrive all database connection in table
        $databaseconnection = Database::all();
        $list = [];
        $count =0;
        foreach ($databaseconnection as $row) {
            $count++;
            $tables = array_map(
                function ($table) use ($dataTypes, $row) {
                    $table = Str::replaceFirst(DB::getTablePrefix(), '', $table);
                    $table = [
                        'prefix'     => DB::getTablePrefix(),
                        'name'       => $table,
                        'slug'       => $dataTypes[$table]['slug'] ?? null,
                        'dataTypeId' => $dataTypes[$table]['id'] ?? null,
                        'driver' => $row->driver
                    ];
                    return $table;
                },
                SchemaManager::manager($row->driver)->listTableNames()
            );
            $list[] = $tables;
        }


        $merged_array = array();


        // merge all database connection
        $array  = [];
        for ($i=0; $i < $count; $i++) {
            foreach ($list[$i] as $key=> $row) {
                $array[] = 
                        array(
                            'prefix'     =>$row['prefix'],
                            'name'       => $row['name'],
                            'slug'       => $row['slug'] ?? null,
                            'dataTypeId' => $row['dataTypeId'] ?? null,
                            'driver' => $row['driver'] ?? null,

                    );
            }
        }
        return Voyager::view('voyager::tools.database.index')->with(compact('dataTypes', 'array'));
    }

    /**
     * Create database table.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        $this->authorize('browse_database');

        $db = $this->prepareDbManager('create');

        return Voyager::view('voyager::tools.database.edit-add', compact('db'));
    }

    /**
     * Store new database table.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */


    public function store(Request $request)
    {
        $this->authorize('browse_database');

        $databaseconnection = $request->databasename ?? '';
        $foldername = $request->foldername ?? '';
        try {
            $conn = 'database.connections.sqlsrv';
            Type::registerCustomPlatformTypes();
            $table = $request->table;
            if (!is_array($request->table)) {
                $table = json_decode($request->table, true);
            }
            $table['options']['collate'] = config($conn.'.collation', 'utf8mb4_unicode_ci');
            $table['options']['charset'] = config($conn.'.charset', 'utf8mb4');

            $table = Table::make($table);
            // Use the schema builder to create a new table in the database
            if (Request()->databasename =='core') {
                SchemaManager::manager()->createTable($table);
            } else {
                SchemaManager::manager($databaseconnection)->createTable($table);
            }

            if (isset($request->create_model) && $request->create_model == 'on') {
                $modelNamespace = config('voyager.models.namespace', app()->getNamespace().'Models\\');
                $params = [
                    // 'name' => $modelNamespace.Str::studly(Str::singular($foldername.'\\'.ucfirst($table->name))),
                    'name' => $modelNamespace.$foldername.'\\'.ucfirst($table->name),
                ];

                // if (in_array('deleted_at', $request->input('field.*'))) {
                //     $params['--softdelete'] = true;
                // }

                if (isset($request->create_migration) && $request->create_migration == 'on') {
                    $params['--migration'] = true;
                }
                Artisan::call('voyager:make:model', $params);
            } elseif (isset($request->create_migration) && $request->create_migration == 'on') {
                Artisan::call('make:migration', [
                    'name'    => 'create_'.$table->name.'_table',
                    '--table' => $table->name,
                ]);
            }

            event(new TableAdded($table));

            return redirect()
               ->route('voyager.database.index')
               ->with($this->alertSuccess(__('voyager::database.success_create_table', ['table' => $table->name])));
        } catch (Exception $e) {
            return back()->with($this->alertException($e))->withInput();
        }
    }

    /**
     * Edit database table.
     *
     * @param string $table
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function edit($table)
    {
        $this->authorize('browse_database');
        // if (!SchemaManager::manager('sqlsrv_mmis')->tableExists($table)) {
        //     return redirect()
        //         ->route('voyager.database.index')
        //         ->with($this->alertError(__('voyager::database.edit_table_not_exist')));
        // }

        $db = $this->prepareDbManager('update', $table);

        return Voyager::view('voyager::tools.database.edit-add', compact('db'));
    }

    /**
     * Update database table.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $this->authorize('browse_database');

        $table = json_decode($request->table, true);

        try {
            if ($request->databasename !='core') {
                DatabaseUpdater::update($table, $request->databasename);
            } else {
                DatabaseUpdater::update($table, 'sqlsrv');
            }
            // TODO: synch BREAD with Table
            // $this->cleanOldAndCreateNew($request->original_name, $request->name);
            event(new TableUpdated($table));
        } catch (Exception $e) {
            return back()->with($this->alertException($e))->withInput();
        }

        return redirect()
               ->route('voyager.database.index')
               ->with($this->alertSuccess(__('voyager::database.success_create_table', ['table' => $table['name']])));
    }

    protected function prepareDbManager($action, $table = '')
    {
        $db = new \stdClass();

        // Need to get the types first to register custom types
        $db->types = Type::getPlatformTypes();

        if ($action == 'update') {
            $db->table = SchemaManager::listTableDetails($table);
            $db->formAction = route('voyager.database.update', $table);
        } else {
            $db->table = new Table('New Table');

            // Add prefilled columns
            $db->table->addColumn('id', 'integer', [
                'unsigned'      => true,
                'notnull'       => true,
                'autoincrement' => true,
            ]);

            $db->table->setPrimaryKey(['id'], 'primary');

            $db->formAction = route('voyager.database.store');
        }

        $oldTable = old('table');
        $db->oldTable = $oldTable ? $oldTable : json_encode(null);
        $db->action = $action;
        $db->identifierRegex = Identifier::REGEX;
        $db->platform = SchemaManager::getDatabasePlatform()->getName();

        return $db;
    }

    public function cleanOldAndCreateNew($originalName, $tableName)
    {
        if (!empty($originalName) && $originalName != $tableName) {
            $dt = DB::table('data_types')->where('name', $originalName);
            if ($dt->get()) {
                $dt->delete();
            }

            $perm = DB::table('permissions')->where('table_name', $originalName);
            if ($perm->get()) {
                $perm->delete();
            }

            $params = ['name' => Str::studly(Str::singular($tableName))];
            Artisan::call('voyager:make:model', $params);
        }
    }

    public function reorder_column(Request $request)
    {
        $this->authorize('browse_database');

        if ($request->ajax()) {
            $table = $request->table;
            $column = $request->column;
            $after = $request->after;
            if ($after == null) {
                // SET COLUMN TO THE TOP
                DB::query("ALTER $table MyTable CHANGE COLUMN $column FIRST");
            }

            return 1;
        }

        return 0;
    }

    /**
     * Show table.
     *
     * @param string $table
     *
     * @return JSON
     */
    public function show($table)
    {
        $this->authorize('browse_database');

        $additional_attributes = [];
        $model_name = Voyager::model('DataType')->where('name', $table)->pluck('model_name')->first();
        if (isset($model_name)) {
            $model = app($model_name);
            if (isset($model->additional_attributes)) {
                foreach ($model->additional_attributes as $attribute) {
                    $additional_attributes[$attribute] = [];
                }
            }
        }

        return response()->json(collect(SchemaManager::describeTable($table))->merge($additional_attributes));
    }

    /**
     * Destroy table.
     *
     * @param string $table
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($table)
    {
        $this->authorize('browse_database');


        try {
            if ((Request()->databasename !='core' || Request()->databasename !='sqlsrv')) {
                SchemaManager::manager(Request()->databasename)->dropTable($table);
            } else {
                SchemaManager::dropTable($table);
            }

            event(new TableDeleted($table));

            return redirect()
                ->route('voyager.database.index')
                ->with($this->alertSuccess(__('voyager::database.success_delete_table', ['table' => $table])));
        } catch (Exception $e) {
            return back()->with($this->alertException($e));
        }
    }
}
