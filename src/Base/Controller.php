<?php

namespace IbnulHusainan\Arc\Base;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base Controller
 *
 * This abstract controller provides a standardized CRUD-like flow
 * with lifecycle hooks, service resolution, request validation,
 * and flexible response handling (JSON or redirect).
 *
 * Child controllers are expected to extend this class and optionally
 * override lifecycle hooks to customize behavior.
 */
abstract class Controller
{
    /**
     * Fully-qualified controller class name.
     *
     * @var string|null
     */
    private ?string $module = null;

    /**
     * Resolved service instance associated with the controller.
     *
     * @var mixed
     */
    protected $service;

    /**
     * Form request class name for save action.
     *
     * @var string|null
     */
    private ?string $saveRequest;

    /**
     * Form request class name for delete action.
     *
     * @var string|null
     */
    private ?string $deleteRequest;

    /**
     * Base view path for the module.
     *
     * @var string
     */
    protected string $viewPath;

    /**
     * Default view names.
     */
    protected string $listView = 'list';
    protected string $formView = 'form';
    protected string $detailView = 'detail';

    /**
     * Default redirect route path.
     *
     * Can be overridden in child controllers
     * (e.g. 'list', 'form', 'detail').
     */
    protected string $redirectRoutePath = 'list';

    /**
     * Primary data key used in views.
     *
     * @var string
     */
    protected string $dataKey = 'row';

    /**
     * Data container passed to views or JSON responses.
     *
     * @var array
     */
    private array $data = [];

    /**
     * Controller constructor.
     *
     * Resolves module name, service class, request classes,
     * and view path automatically based on naming conventions.
     */
    public function __construct()
    {
        $this->module = get_called_class();
        $this->service = $this->initService();
        $this->saveRequest = $this->saveRequestClass();
        $this->deleteRequest = $this->deleteRequestClass();
        $this->viewPath = $this->viewPath();
    }

    /**
     * Hook executed before request validation.
     *
     * Useful for request mutation or preparation.
     */
    protected function beforeValidate(Request $request) {}

    /**
     * Hook executed before saving data.
     *
     * @param Request $request
     * @param array|null $data Data to be saved (passed by reference)
     */
    protected function beforeSave(Request $request, ?array &$data) {}

    /**
     * Hook executed after data is saved.
     *
     * If a Response instance is returned, it will override
     * the default response handling.
     *
     * @param mixed   $model   Saved model or result
     * @param Request $request
     * @return Response|null
     */
    protected function afterSave(&$model, Request $request) {}

    /**
     * Hook executed before deleting data.
     *
     * @param array|null $data
     * @param Request    $request
     */
    protected function beforeDelete(?array &$data, Request $request) {}

    /**
     * Hook executed after deleting data.
     *
     * If a Response instance is returned, it will override
     * the default response handling.
     *
     * @param mixed   $model
     * @param Request $request
     * @return Response|null
     */
    protected function afterDelete(&$model, Request $request) {}

    /**
     * Display list page.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $this->listData();

        return $this->jsonOrRender($this->listView);
    }

    /**
     * Display create/edit form.
     *
     * @param mixed|null $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function form($id = null)
    {
        $this->formData($id);
        
        return $this->jsonOrRender($this->formView);
    }

    /**
     * Display detail page.
     *
     * @param mixed|null $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function detail($id = null)
    {
        $this->detailData($id);

        return $this->jsonOrRender($this->detailView);
    }

    /**
     * Handle create or update action.
     *
     * Supports custom validation, lifecycle hooks,
     * and optional response override via afterSave().
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {       
        if (class_exists($this->saveRequest)) {
            $this->beforeValidate($request);

            $request = app($this->saveRequest);
            $data = $request->validated();
        } else {
            $data = $request->all();
        }

        $this->beforeSave($request, $data);

        $saved = $this->service?->save($data);

        $response = $this->afterSave($saved, $request);
        if ($response instanceof Response) {
            return $response;
        }

        return $this->jsonOrRedirect(['success' => (bool) $saved]);
    }

    /**
     * Handle delete action.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        if (class_exists($this->deleteRequest)) {
            $this->beforeValidate($request);

            $request = app($this->deleteRequest);
            $data = $request->validated();
        } else {
            $data = $request->all();
        }

        $this->beforeDelete($data, $request);

        $deleted = $this->service?->delete($data);

        $response = $this->afterDelete($deleted, $request);
        if ($response instanceof Response) {
            return $response;
        }

        return $this->jsonOrRedirect(['success' => (bool) $deleted]);
    }

    /**
     * Provide datatable-compatible response.
     *
     * @return array
     */
    public function data()
    {
        return $this->service?->datatable() ?? [
            'draw' => request('draw'),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ];        
    }

    /**
     * Render a module view.
     */
    protected function render($viewModule)
    {
        return view("{$this->viewPath}::{$viewModule}", $this->data);
    }

    /**
     * Return JSON response for AJAX requests or render view otherwise.
     */
    protected function jsonOrRender($view)
    {
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json($this->data);
        }
    
        return $this->render($view);
    }

    /**
     * Return JSON response for AJAX requests or redirect to a configured route.
     *
     * @param  array|null  $data
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function jsonOrRedirect(array $data = null)
    {
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json($data);
        }

        return redirect()->route(
            $this->pageRouteName($this->redirectRoutePath)
        );
    }

    /**
     * Get resolved service instance.
     *
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Initialize service instance based on controller naming convention.
     *
     * @return mixed|null
     */
    protected function initService()
    {
        $serviceClass = $this->serviceClass();
        return class_exists($serviceClass) ? app($serviceClass) : null;
    }

    /**
     * Resolve service class name from controller.
     *
     * @return string|null
     */
    protected function serviceClass(): ?string
    {
        return controllerTo('service', $this->module);
    }

    /**
     * Resolve request validation class for save action.
     *
     * @return string|null
     */
    protected function saveRequestClass(): ?string
    {
        $request = controllerTo('saveRequest', $this->module);
        return class_exists($request) ? $request : null;
    }

    /**
     * Resolve request validation class for delete action.
     *
     * @return string|null
     */
    protected function deleteRequestClass(): ?string
    {
        $request = controllerTo('deleteRequest', $this->module);
        return class_exists($request) ? $request : null;
    }

    /**
     * Merge additional data into controller response payload.
     *
     * @param  array  $data
     * @return void
     */
    protected function addData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Prepare data for form view.
     *
     * @param  mixed  $id
     * @return void
     */
    protected function formData($id)
    {
        $this->data[$this->dataKey] = $this->service?->getFormData($id);
        $this->addData($this->service->getFormSubData($id));
    }

    /**
     * Prepare data for list view.
     *
     * @return void
     */
    protected function listData()
    {
        $this->data['row'] = $this->service?->getListData();
        $this->addData($this->service->getListSubData());
    }

    /**
     * Prepare data for detail view.
     *
     * @param  mixed  $id
     * @return void
     */
    protected function detailData($id)
    {
        $this->data[$this->dataKey] = $this->service?->getDetailData($id);
        $this->addData($this->service->getDetailSubData($id));
    }

    /**
     * Resolve view namespace path for this controller.
     *
     * @return string
     */
    protected function viewPath(): string
    {
        return $this->viewPath ?? strtolower(controllerTo('view', $this->module));
    }

    /**
     * Resolve route name for current controller by path keyword.
     *
     * @param  string  $path
     * @return string|null
     */
    protected function pageRouteName($path): string
    {
        return collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->first(fn ($route) =>
                str_contains($route->getActionName(), static::class) &&
                str_contains($route->getName(), $path)
            )?->getName();
    }    
}
