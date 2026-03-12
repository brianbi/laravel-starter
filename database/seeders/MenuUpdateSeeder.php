<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 删除现有菜单数据
        Menu::truncate();
        
        // 重新创建完整菜单
        $this->createCompleteMenus();
    }

    /**
     * 创建完整菜单
     */
    protected function createCompleteMenus(): void
    {
        // 系统管理
        $system = Menu::create([
            'parent_id' => 0,
            'name' => '系统管理',
            'code' => 'system',
            'type' => 'M',
            'icon' => 'setting',
            'route' => '/system',
            'component' => 'Layout',
            'sort' => 1,
            'status' => 1,
        ]);

        // 系统管理 - 子菜单
        $systemChildren = [
            ['name' => '用户管理', 'code' => 'system:user', 'route' => '/system/user', 'component' => 'system/user/index', 'permission' => 'system:user:list', 'icon' => 'user', 'sort' => 1],
            ['name' => '角色管理', 'code' => 'system:role', 'route' => '/system/role', 'component' => 'system/role/index', 'permission' => 'system:role:list', 'icon' => 'peoples', 'sort' => 2],
            ['name' => '菜单管理', 'code' => 'system:menu', 'route' => '/system/menu', 'component' => 'system/menu/index', 'permission' => 'system:menu:list', 'icon' => 'menu', 'sort' => 3],
            ['name' => '部门管理', 'code' => 'system:department', 'route' => '/system/department', 'component' => 'system/department/index', 'permission' => 'system:department:list', 'icon' => 'tree', 'sort' => 4],
            ['name' => '岗位管理', 'code' => 'system:position', 'route' => '/system/position', 'component' => 'system/position/index', 'permission' => 'system:position:list', 'icon' => 'post', 'sort' => 5],
            ['name' => '字典管理', 'code' => 'system:dictionary', 'route' => '/system/dictionary', 'component' => 'system/dictionary/index', 'permission' => 'system:dictionary:list', 'icon' => 'notebook', 'sort' => 6],
        ];

        $buttonPermissions = [
            'user' => ['create', 'update', 'delete', 'assign-role', 'reset-pwd'],
            'role' => ['create', 'update', 'delete', 'assign-menu'],
            'menu' => ['create', 'update', 'delete'],
            'department' => ['create', 'update', 'delete'],
            'position' => ['create', 'update', 'delete'],
            'dictionary' => ['create', 'update', 'delete', 'item-create', 'item-update', 'item-delete'],
        ];

        $menusData = [];

        // 为每个子菜单创建菜单项和按钮权限
        foreach ($systemChildren as $index => $child) {
            $menu = Menu::create([
                'parent_id' => $system->id,
                'name' => $child['name'],
                'code' => $child['code'],
                'type' => 'M',
                'icon' => $child['icon'],
                'route' => $child['route'],
                'component' => $child['component'],
                'permission' => $child['permission'],
                'sort' => $child['sort'],
                'status' => 1,
            ]);

            // 创建按钮权限
            $menuType = str_replace('system:', '', $child['code']);
            if (isset($buttonPermissions[$menuType])) {
                foreach ($buttonPermissions[$menuType] as $permIndex => $perm) {
                    $menusData[] = [
                        'parent_id' => $menu->id,
                        'name' => $this->getPermissionName($perm, $menuType),
                        'code' => $child['code'] . ':' . $perm,
                        'type' => 'B',
                        'permission' => $child['code'] . ':' . $perm,
                        'sort' => $permIndex + 1,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // 系统监控
        $monitor = Menu::create([
            'parent_id' => 0,
            'name' => '系统监控',
            'code' => 'monitor',
            'type' => 'M',
            'icon' => 'monitor',
            'route' => '/monitor',
            'component' => 'Layout',
            'sort' => 2,
            'status' => 1,
        ]);

        $monitorChildren = [
            ['name' => '操作日志', 'code' => 'monitor:operation-log', 'route' => '/monitor/operation-log', 'component' => 'monitor/operationLog/index', 'permission' => 'monitor:operation-log:list', 'icon' => 'edit', 'sort' => 1],
            ['name' => '登录日志', 'code' => 'monitor:login-log', 'route' => '/monitor/login-log', 'component' => 'monitor/loginLog/index', 'permission' => 'monitor:login-log:list', 'icon' => 'lock', 'sort' => 2],
        ];

        foreach ($monitorChildren as $index => $child) {
            $menu = Menu::create([
                'parent_id' => $monitor->id,
                'name' => $child['name'],
                'code' => $child['code'],
                'type' => 'M',
                'icon' => $child['icon'],
                'route' => $child['route'],
                'component' => $child['component'],
                'permission' => $child['permission'],
                'sort' => $child['sort'],
                'status' => 1,
            ]);

            // 清理权限
            if ($menu->code === 'monitor:operation-log' || $menu->code === 'monitor:login-log') {
                $menusData[] = [
                    'parent_id' => $menu->id,
                    'name' => '清空日志',
                    'code' => $child['code'] . ':clear',
                    'type' => 'B',
                    'permission' => $child['code'] . ':clear',
                    'sort' => 1,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 工作流管理
        $workflow = Menu::create([
            'parent_id' => 0,
            'name' => '工作流',
            'code' => 'workflow',
            'type' => 'M',
            'icon' => 'flow',
            'route' => '/workflow',
            'component' => 'Layout',
            'sort' => 3,
            'status' => 1,
        ]);

        $workflowChildren = [
            ['name' => '流程定义', 'code' => 'workflow:definition', 'route' => '/workflow/definition', 'component' => 'workflow/definition/index', 'permission' => 'workflow:definition:list', 'icon' => 'list', 'sort' => 1],
            ['name' => '流程设计器', 'code' => 'workflow:designer', 'route' => '/workflow/designer', 'component' => 'workflow/designer/index', 'permission' => 'workflow:definition:design', 'icon' => 'brush', 'sort' => 2],
            ['name' => '我的流程', 'code' => 'workflow:instance', 'route' => '/workflow/instance', 'component' => 'workflow/instance/index', 'permission' => 'workflow:instance:list', 'icon' => 'tickets', 'sort' => 3],
            ['name' => '我的待办', 'code' => 'workflow:task', 'route' => '/workflow/task', 'component' => 'workflow/task/index', 'permission' => 'workflow:task:list', 'icon' => 'message', 'sort' => 4],
        ];

        $workflowPermissions = [
            'definition' => ['create', 'update', 'delete', 'design', 'publish', 'copy'],
            'designer' => ['save', 'preview'],
            'instance' => ['start', 'withdraw', 'view'],
            'task' => ['approve', 'reject', 'delegate', 'complete'],
        ];

        foreach ($workflowChildren as $index => $child) {
            $menu = Menu::create([
                'parent_id' => $workflow->id,
                'name' => $child['name'],
                'code' => $child['code'],
                'type' => 'M',
                'icon' => $child['icon'],
                'route' => $child['route'],
                'component' => $child['component'],
                'permission' => $child['permission'],
                'sort' => $child['sort'],
                'status' => 1,
            ]);

            // 工作流权限按钮
            $menuType = str_replace('workflow:', '', $child['code']);
            if (isset($workflowPermissions[$menuType])) {
                foreach ($workflowPermissions[$menuType] as $permIndex => $perm) {
                    $menusData[] = [
                        'parent_id' => $menu->id,
                        'name' => $this->getPermissionName($perm, 'workflow-' . $menuType),
                        'code' => $child['code'] . ':' . $perm,
                        'type' => 'B',
                        'permission' => $child['code'] . ':' . $perm,
                        'sort' => $permIndex + 1,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // 表单设计
        $form = Menu::create([
            'parent_id' => 0,
            'name' => '表单设计',
            'code' => 'form',
            'type' => 'M',
            'icon' => 'form',
            'route' => '/form',
            'component' => 'Layout',
            'sort' => 4,
            'status' => 1,
        ]);

        $formChildren = [
            ['name' => '表单定义', 'code' => 'form:definition', 'route' => '/form/definition', 'component' => 'form/definition/index', 'permission' => 'form:definition:list', 'icon' => 'document', 'sort' => 1],
            ['name' => '表单设计器', 'code' => 'form:designer', 'route' => '/form/designer', 'component' => 'form/designer/index', 'permission' => 'form:definition:design', 'icon' => 'brush', 'sort' => 2],
            ['name' => '表单数据', 'code' => 'form:data', 'route' => '/form/data', 'component' => 'form/data/index', 'permission' => 'form:data:list', 'icon' => 'data', 'sort' => 3],
        ];

        $formPermissions = [
            'definition' => ['create', 'update', 'delete', 'design', 'enable', 'disable', 'copy'],
            'designer' => ['save', 'preview', 'field-add', 'field-edit', 'field-delete'],
            'data' => ['view', 'delete', 'export'],
        ];

        foreach ($formChildren as $index => $child) {
            $menu = Menu::create([
                'parent_id' => $form->id,
                'name' => $child['name'],
                'code' => $child['code'],
                'type' => 'M',
                'icon' => $child['icon'],
                'route' => $child['route'],
                'component' => $child['component'],
                'permission' => $child['permission'],
                'sort' => $child['sort'],
                'status' => 1,
            ]);

            // 表单权限按钮
            $menuType = str_replace('form:', '', $child['code']);
            if (isset($formPermissions[$menuType])) {
                foreach ($formPermissions[$menuType] as $permIndex => $perm) {
                    $menusData[] = [
                        'parent_id' => $menu->id,
                        'name' => $this->getPermissionName($perm, 'form-' . $menuType),
                        'code' => $child['code'] . ':' . $perm,
                        'type' => 'B',
                        'permission' => $child['code'] . ':' . $perm,
                        'sort' => $permIndex + 1,
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // 批量插入按钮权限
        if (!empty($menusData)) {
            Menu::insert($menusData);
        }
    }

    /**
     * 获取权限名称
     */
    protected function getPermissionName(string $permission, string $type): string
    {
        $names = [
            'create' => '新增',
            'update' => '编辑',
            'delete' => '删除',
            'assign-role' => '分配角色',
            'reset-pwd' => '重置密码',
            'assign-menu' => '分配菜单',
            'item-create' => '新增项',
            'item-update' => '编辑项',
            'item-delete' => '删除项',
            'clear' => '清空',
            'design' => '设计',
            'publish' => '发布',
            'copy' => '复制',
            'save' => '保存',
            'preview' => '预览',
            'field-add' => '添加字段',
            'field-edit' => '编辑字段',
            'field-delete' => '删除字段',
            'start' => '发起',
            'withdraw' => '撤回',
            'view' => '查看',
            'approve' => '通过',
            'reject' => '拒绝',
            'delegate' => '转办',
            'complete' => '完成',
            'enable' => '启用',
            'disable' => '停用',
            'export' => '导出',
        ];

        return $names[$permission] ?? $permission;
    }
}