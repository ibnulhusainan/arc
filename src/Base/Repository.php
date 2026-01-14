<?php

namespace IbnulHusainan\Arc\Base;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

/**
 * Base repository abstraction.
 *
 * Provides common data access operations and bridges
 * between services and Eloquent models.
 */
abstract class Repository
{
    /**
     * Authenticated user instance.
     *
     * @var mixed
     */
    protected $user;

    /**
     * Fully-qualified repository class name.
     *
     * Used for resolving related classes (model, etc).
     *
     * @var string|null
     */
    protected ?string $module = null;

    /**
     * Eloquent model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Repository constructor.
     *
     * Resolves authenticated user, module name,
     * and initializes the model instance.
     */
    public function __construct()
    {
        $this->user = Auth::user();
        $this->module = get_called_class();
        $this->model = app($this->modelClass());
    }

    /**
     * Get primary key name of the model.
     *
     * @return string
     */
    public function pk()
    {
        return $this->model->getKeyName();
    }

    /**
     * Get base query builder for datatable usage.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryDatatable()
    {
        return $this->model->query();
    }

    /**
     * Create or update a model record.
     *
     * If no custom where condition is provided, the model
     * primary key will be used automatically.
     *
     * @param array $data   Data to be saved
     * @param array $where  Custom where condition
     * @return \Illuminate\Database\Eloquent\Model|false
     */
    public function save(array $data = [], array $where = [])
    {
        $data = collect($data);

        $saveWhere = $where ?: [$this->pk() => $data->get($this->pk())];
        $saveData  = $where
            ? $data->all()
            : $data->only($this->fillable())->toArray();

        $result = $saveWhere && $saveData
            ? $this->model->updateOrCreate($saveWhere, $saveData)
            : false;

        return $result;
    }

    /**
     * Delete model records matching given conditions.
     *
     * @param array $data   Where conditions
     * @param bool  $force  Force delete (bypass soft delete)
     * @return \Illuminate\Support\Collection
     */
    public function delete(array $data, bool $force = false)
    {
        $query = $this->model->newQuery();

        foreach ($data as $column => $value) {
            $query->where($column, $value);
        }

        return $query->get()
            ->each(fn ($m) => $force ? $m->forceDelete() : $m->delete());
    }

    /**
     * Get underlying Eloquent model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Resolve model class name for this repository.
     *
     * @return string
     */
    protected function modelClass()
    {
        return repositoryTo('model', $this->module);
    }

    /**
     * Determine fillable attributes for mass assignment.
     *
     * Falls back to schema inspection if the model
     * does not explicitly define $fillable.
     *
     * @return array
     */
    protected function fillable(): array
    {
        $model = $this->model;

        $fillable = $model->getFillable();
        if (!empty($fillable)) {
            return $fillable;
        }

        $guarded = $model->getGuarded();
        $table   = $model->getTable();
        $columns = $model->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($table);

        if (empty($guarded)) {
            return $columns;
        }

        return array_values(array_diff($columns, $guarded));
    }

    /**
     * Proxy dynamic method calls to the model instance.
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->model->$method(...$args);
    }
}
