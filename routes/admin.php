<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\DictionaryController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\NotificationRecordController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\Workflow\DefinitionController as WorkflowDefinitionController;
use App\Http\Controllers\Admin\Workflow\InstanceController as WorkflowInstanceController;
use App\Http\Controllers\Admin\Workflow\TaskController as WorkflowTaskController;
use App\Http\Controllers\Admin\Workflow\DelegateController as WorkflowDelegateController;
use App\Http\Controllers\Admin\Form\FormDefinitionController;
use App\Http\Controllers\Admin\Form\FormDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| 后台管理系统 API 路由
|
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // 无需认证的路由
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');

    // 需要认证的路由
    Route::middleware(['auth:api', 'operation.log'])->group(function () {
        // 认证相关
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::get('auth/menus', [AuthController::class, 'menus'])->name('auth.menus');

        // 用户管理
        Route::post('users/export', [UserController::class, 'export'])->name('users.export');
        Route::get('users/export/fields', [UserController::class, 'exportFields'])->name('users.export.fields');
        Route::put('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
        Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::apiResource('users', UserController::class);

        // 角色管理
        Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions');
        Route::put('roles/{role}/menus', [RoleController::class, 'updateMenus'])->name('roles.menus');
        Route::apiResource('roles', RoleController::class);

        // 菜单管理
        Route::get('menus/tree', [MenuController::class, 'tree'])->name('menus.tree');
        Route::apiResource('menus', MenuController::class);

        // 部门管理
        Route::get('departments/tree', [DepartmentController::class, 'tree'])->name('departments.tree');
        Route::apiResource('departments', DepartmentController::class);

        // 岗位管理
        Route::apiResource('positions', PositionController::class);

        // 字典管理
        Route::get('dictionaries/{dictionary}/items', [DictionaryController::class, 'items'])->name('dictionaries.items.index');
        Route::post('dictionaries/{dictionary}/items', [DictionaryController::class, 'storeItem'])->name('dictionaries.items.store');
        Route::put('dictionaries/{dictionary}/items/{item}', [DictionaryController::class, 'updateItem'])->name('dictionaries.items.update');
        Route::delete('dictionaries/{dictionary}/items/{item}', [DictionaryController::class, 'destroyItem'])->name('dictionaries.items.destroy');
        Route::apiResource('dictionaries', DictionaryController::class);

        // 日志管理
        Route::get('logs/operation', [LogController::class, 'operationLogs'])->name('logs.operation');
        Route::get('logs/login', [LogController::class, 'loginLogs'])->name('logs.login');
        Route::delete('logs/operation/clear', [LogController::class, 'clearOperationLogs'])->name('logs.operation.clear');
        Route::delete('logs/login/clear', [LogController::class, 'clearLoginLogs'])->name('logs.login.clear');

        // 导出管理
        Route::get('export/tasks', [ExportController::class, 'tasks'])->name('export.tasks');
        Route::get('export/tasks/{uuid}', [ExportController::class, 'status'])->name('export.status');
        Route::get('export/download/{uuid}', [ExportController::class, 'download'])->name('export.download');
        Route::get('export/file/{filename}', [ExportController::class, 'downloadByFilename'])->name('export.file');

        // 通知中心 - 我的通知
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::put('notifications/read', [NotificationController::class, 'readBatch'])->name('notifications.read.batch');
        Route::put('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::delete('notifications/batch', [NotificationController::class, 'destroyBatch'])->name('notifications.destroy.batch');
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

        // 通知管理 - 发送记录
        Route::get('notification-records', [NotificationRecordController::class, 'index'])->name('notification-records.index');
        Route::get('notification-records/statistics', [NotificationRecordController::class, 'statistics'])->name('notification-records.statistics');
        Route::get('notification-records/{id}', [NotificationRecordController::class, 'show'])->name('notification-records.show');

        // 通知管理 - 模板
        Route::post('notification-templates/{id}/preview', [NotificationTemplateController::class, 'preview'])->name('notification-templates.preview');
        Route::apiResource('notification-templates', NotificationTemplateController::class);

        // ==================== 工作流模块 ====================

        // 工作流定义管理
        Route::prefix('workflow/definitions')->name('workflow.definitions.')->group(function () {
            Route::get('/', [WorkflowDefinitionController::class, 'index'])->name('index');
            Route::get('/enabled', [WorkflowDefinitionController::class, 'enabled'])->name('enabled');
            Route::get('/node-types', [WorkflowDefinitionController::class, 'nodeTypes'])->name('node-types');
            Route::post('/', [WorkflowDefinitionController::class, 'store'])->name('store');
            Route::get('/{id}', [WorkflowDefinitionController::class, 'show'])->name('show');
            Route::put('/{id}', [WorkflowDefinitionController::class, 'update'])->name('update');
            Route::delete('/{id}', [WorkflowDefinitionController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/publish', [WorkflowDefinitionController::class, 'publish'])->name('publish');
            Route::get('/{id}/versions', [WorkflowDefinitionController::class, 'versions'])->name('versions');
        });

        // 工作流实例操作
        Route::prefix('workflow/instances')->name('workflow.instances.')->group(function () {
            Route::get('/initiated', [WorkflowInstanceController::class, 'initiated'])->name('initiated');
            Route::get('/business-status', [WorkflowInstanceController::class, 'businessStatus'])->name('business-status');
            Route::post('/', [WorkflowInstanceController::class, 'store'])->name('store');
            Route::get('/{id}', [WorkflowInstanceController::class, 'show'])->name('show');
            Route::get('/{id}/timeline', [WorkflowInstanceController::class, 'timeline'])->name('timeline');
            Route::post('/{id}/withdraw', [WorkflowInstanceController::class, 'withdraw'])->name('withdraw');
        });

        // 工作流任务处理
        Route::prefix('workflow/tasks')->name('workflow.tasks.')->group(function () {
            Route::get('/', [WorkflowTaskController::class, 'pending'])->name('pending');
            Route::get('/done', [WorkflowTaskController::class, 'done'])->name('done');
            Route::get('/count', [WorkflowTaskController::class, 'count'])->name('count');
            Route::get('/statistics', [WorkflowTaskController::class, 'statistics'])->name('statistics');
            Route::get('/{id}', [WorkflowTaskController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [WorkflowTaskController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [WorkflowTaskController::class, 'reject'])->name('reject');
            Route::post('/{id}/return', [WorkflowTaskController::class, 'return'])->name('return');
            Route::post('/{id}/delegate', [WorkflowTaskController::class, 'delegate'])->name('delegate');
            Route::post('/{id}/add-sign', [WorkflowTaskController::class, 'addSign'])->name('add-sign');
        });

        // 工作流代理委托
        Route::prefix('workflow/delegates')->name('workflow.delegates.')->group(function () {
            Route::get('/', [WorkflowDelegateController::class, 'index'])->name('index');
            Route::get('/as-delegate', [WorkflowDelegateController::class, 'asDelegate'])->name('as-delegate');
            Route::post('/', [WorkflowDelegateController::class, 'store'])->name('store');
            Route::get('/{id}', [WorkflowDelegateController::class, 'show'])->name('show');
            Route::put('/{id}', [WorkflowDelegateController::class, 'update'])->name('update');
            Route::delete('/{id}', [WorkflowDelegateController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/enable', [WorkflowDelegateController::class, 'enable'])->name('enable');
            Route::post('/{id}/disable', [WorkflowDelegateController::class, 'disable'])->name('disable');
        });

        // ==================== 表单设计器模块 ====================

        // 表单定义管理
        Route::prefix('forms')->name('forms.')->group(function () {
            Route::get('/', [FormDefinitionController::class, 'index'])->name('index');
            Route::get('/enabled', [FormDefinitionController::class, 'enabled'])->name('enabled');
            Route::get('/field-types', [FormDefinitionController::class, 'fieldTypes'])->name('field-types');
            Route::post('/', [FormDefinitionController::class, 'store'])->name('store');
            Route::delete('/batch', [FormDefinitionController::class, 'batchDestroy'])->name('batch-destroy');
            Route::get('/{id}', [FormDefinitionController::class, 'show'])->name('show');
            Route::put('/{id}', [FormDefinitionController::class, 'update'])->name('update');
            Route::delete('/{id}', [FormDefinitionController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/enable', [FormDefinitionController::class, 'enable'])->name('enable');
            Route::post('/{id}/disable', [FormDefinitionController::class, 'disable'])->name('disable');
            Route::post('/{id}/copy', [FormDefinitionController::class, 'copy'])->name('copy');
            Route::post('/{id}/validate', [FormDefinitionController::class, 'validate'])->name('validate');
            Route::get('/{id}/statistics', [FormDefinitionController::class, 'statistics'])->name('statistics');

            // 表单数据管理（嵌套路由）
            Route::get('/{formId}/data', [FormDataController::class, 'index'])->name('data.index');
            Route::post('/{formId}/data', [FormDataController::class, 'store'])->name('data.store');
            Route::get('/{formId}/data/{id}', [FormDataController::class, 'show'])->name('data.show');
            Route::put('/{formId}/data/{id}', [FormDataController::class, 'update'])->name('data.update');
            Route::delete('/{formId}/data/{id}', [FormDataController::class, 'destroy'])->name('data.destroy');
        });

        // 根据流程实例获取表单数据
        Route::get('form-data/by-instance/{instanceId}', [FormDataController::class, 'byInstance'])->name('form-data.by-instance');
    });
});
