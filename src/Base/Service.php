<?php

namespace IbnulHusainan\Arc\Base;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Base Service
 *
 * This abstract service acts as the core business layer between
 * controllers and repositories. It provides a standardized workflow
 * for data persistence, transactional safety, lifecycle hooks,
 * and optional datatable integration.
 *
 * Child services are expected to extend this class and override
 * lifecycle hooks or data providers as needed.
 */
abstract class Service
{
    /**
     * Authenticated user instance.
     *
     * @var mixed
     */
    protected $user;

    /**
     * Fully-qualified service class name.
     *
     * @var string|null
     */
    protected ?string $module = null;

    /**
     * Resolved repository instance.
     *
     * @var mixed
     */
    protected $repo;

    /**
     * Resolved datatable instance.
     *
     * @var mixed
     */
    protected $datatable;

    /**
     * Raw data passed to save().
     *
     * @var mixed
     */
    private $rawData;

    /**
     * Raw data passed to saveMany().
     *
     * @var mixed
     */
    private $rawManyData;

    /**
     * Temporary internal data holder.
     *
     * @var mixed
     */
    private $tempData;

    /**
     * Flags to skip lifecycle hooks.
     */
    private bool $skipBeforeSave = false;
    private bool $skipSave = false;
    private bool $skipAfterSave = false;

    /**
     * Service constructor.
     *
     * Resolves authenticated user, module name,
     * repository, and datatable instances automatically.
     */
    public function __construct()
    {
        $this->user = Auth::user();
        $this->module = get_called_class();
        $this->repo = $this->initRepo();
        $this->datatable = $this->initDatatable();
    }

    /**
     * Render datatable response.
     *
     * @return mixed
     */
    public function datatable()
    {
        $query = $this->getTableData() ?? $this->repo?->queryDatatable();
        return $this->datatable?->render($query);
    }

    /**
     * Hook executed before saving data.
     *
     * @param array $data Data to be saved (passed by reference)
     * @return void
     */
    protected function beforeSave(array &$data) {}

    /**
     * Perform actual save operation via repository.
     *
     * @param array $data
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function doSave(array $data)
    {
        try {
            $saved = $this->repo?->save($data);

            if (!$saved) {
                throw new \Exception('Failed to save data');
            }

            return $saved;

        } catch (\Throwable $e) {
            if (config('app.debug')) {
                throw $e;
            }

            throw new \Exception('Failed to save data');
        }
    }

    /**
     * Save a single record or multiple records.
     *
     * Automatically detects list-based input and delegates
     * to saveMany() when necessary.
     *
     * @param array $data
     * @return mixed
     */
    public function save(array $data)
    {
        $this->rawData = $data;

        return $this->transaction(function () use ($data) {
            if (!$this->skipBeforeSave) {
                $this->beforeSave($data);
            }

            // Handle bulk save
            if (array_is_list($data)) {
                $this->skipBeforeSave();
                return $this->saveMany($data);
            }

            $saved = null;
            if (!$this->skipSave) {
                $saved = $this->doSave($data);

                if (!($saved instanceof Model)) {
                    return $saved;
                }

                $data[$this->repo->pk()] = $saved->getKey();
            }

            if (!$this->skipAfterSave) {
                $this->afterSave($data, $saved);
            }

            return $saved;
        });
    }

    /**
     * Save multiple records in a single transaction.
     *
     * @param array $datas
     * @return array|mixed
     */
    public function saveMany(array $datas)
    {
        $this->rawManyData = $datas;

        return $this->transaction(function () use ($datas) {
            if (!$this->skipBeforeSave) {
                $this->beforeSave($datas);
            }

            $results = [];
            if (!$this->skipSave) {
                foreach ($datas as $i => $data) {
                    $saved = $this->doSave($data);

                    if (!($saved instanceof Model)) {
                        return $saved;
                    }

                    $datas[$i][$this->repo->pk()] = $saved->getKey();
                    $results[] = $saved;
                }
            }

            if (!$this->skipAfterSave) {
                $this->afterSave($datas, $results);
            }

            return $results;
        });
    }

    /**
     * Hook executed after saving data.
     *
     * @param array $data
     * @param mixed $result
     * @return void
     */
    protected function afterSave(array $data, &$result) {}

    /**
     * Skip beforeSave hook.
     *
     * @return void
     */
    protected function skipBeforeSave()
    {
        $this->skipBeforeSave = true;
    }

    /**
     * Skip save execution.
     *
     * @return void
     */
    protected function skipSave()
    {
        $this->skipSave = true;
    }

    /**
     * Skip afterSave hook.
     *
     * @return void
     */
    protected function skipAfterSave()
    {
        $this->skipAfterSave = true;
    }

    /**
     * Hook executed before delete.
     *
     * @param array $data
     * @return void
     */
    protected function beforeDelete(array &$data) {}

    /**
     * Perform actual delete operation via repository.
     *
     * @param array $data
     * @param bool  $force
     * @return mixed
     */
    protected function doDelete(array $data, $force)
    {
        try {
            $deleted = $this->repo?->delete($data, $force);

            if (!$deleted) {
                throw new \Exception('Failed to delete data');
            }

            return $deleted;

        } catch (\Throwable $e) {
            if (config('app.debug')) {
                throw $e;
            }

            throw new \Exception('Failed to delete data');
        }
    }

    /**
     * Delete data with transactional safety.
     *
     * @param array $data
     * @param bool  $force
     * @return mixed|null
     */
    public function delete(array $data, bool $force = false)
    {
        return $this->transaction(function () use ($data, $force) {
            $this->beforeDelete($data);

            $deleted = $this->doDelete($data, $force);
            if (!$deleted) {
                return null;
            }

            $this->afterDelete($data, $force);
            return $deleted;
        });
    }

    /**
     * Hook executed after delete.
     *
     * @param array $data
     * @param bool  $force
     * @return void
     */
    protected function afterDelete(array $data, bool $force = false) {}

    /**
     * Get raw data originally passed to save().
     *
     * @return mixed|null
     */
    protected function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Get raw data originally passed to saveMany().
     *
     * @return mixed|null
     */
    protected function getRawManyData()
    {
        return $this->rawManyData;
    }

    /**
     * Store temporary data for internal service processing.
     *
     * This data is not persisted and is intended to be used
     * within the current request lifecycle only.
     *
     * @param mixed $data
     * @return void
     */
    protected function setTempData($data): void
    {
        $this->tempData = $data;
    }

    /**
     * Retrieve previously stored temporary data.
     *
     * @return mixed|null
     */
    protected function getTempData()
    {
        return $this->tempData;
    }

    /**
     * Get resolved repository instance.
     *
     * @return mixed
     */
    public function getRepository()
    {
        return $this->repo;
    }

    /**
     * Initialize repository instance.
     *
     * @return mixed|null
     */
    protected function initRepo()
    {
        $repoClass = $this->repoClass();
        return class_exists($repoClass) ? app($repoClass) : null;
    }

    /**
     * Resolve repository class name.
     *
     * @return string|null
     */
    protected function repoClass()
    {
        return serviceTo('repository', $this->module);
    }

    /**
     * Initialize datatable instance.
     *
     * @return mixed|null
     */
    protected function initDatatable()
    {
        $datatableClass = $this->datatableClass();
        return class_exists($datatableClass) ? app($datatableClass) : null;
    }

    /**
     * Resolve datatable class name.
     *
     * @return string|null
     */
    protected function datatableClass()
    {
        return serviceTo('datatable', $this->module);
    }

    /**
     * Get data for list view.
     *
     * @return mixed
     */
    public function getListData()
    {
        return $this->repo?->all();
    }

    /**
     * Get additional data for list view.
     *
     * @return array
     */
    public function getListSubData(): array
    {
        return [];
    }

    /**
     * Get data for form view.
     *
     * @param mixed|null $id
     * @return mixed
     */
    public function getFormData($id = null)
    {
        return $this->repo?->findOrNew($id);
    }

    /**
     * Get additional data for form view.
     *
     * @param mixed|null $id
     * @return array
     */
    public function getFormSubData($id = null): array
    {
        return [];
    }

    /**
     * Get data for detail view.
     *
     * @param mixed $id
     * @return mixed
     */
    public function getDetailData($id)
    {
        return $this->repo?->findOrFail($id);
    }

    /**
     * Get additional data for detail view.
     *
     * @param mixed|null $id
     * @return array
     */
    public function getDetailSubData($id = null): array
    {
        return [];
    }

    /**
     * Override to customize datatable query source.
     *
     * @return mixed|null
     */
    public function getTableData()
    {
        return null;
    }

    /**
     * Replicate authenticated user while preserving primary key.
     *
     * @return mixed
     */
    protected function replicateUser()
    {
        $m_id = $this->user?->m_id;
        $replicatedUser = $this->user?->replicate();
        $replicatedUser->m_id = $m_id;

        return $replicatedUser;
    }

    /**
     * Execute a callback within a database transaction.
     *
     * Prevents nested transactions when already inside one.
     *
     * @param \Closure $callback
     * @return mixed
     */
    protected function transaction(\Closure $callback)
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        return DB::transaction($callback);
    }

    /**
     * Proxy unknown method calls to repository.
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->repo?->$method(...$args);
    }
}
