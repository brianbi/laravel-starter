<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Menu;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 创建部门
        $this->createDepartments();
        
        // 创建岗位
        $this->createPositions();
        
        // 创建菜单
        $this->createMenus();
        
        // 创建角色
        $this->createRoles();
        
        // 创建超级管理员
        $this->createSuperAdmin();
    }

    /**
     * 创建部门
     */
    protected function createDepartments(): void
    {
        $rootDept = Department::create([
            'parent_id' => 0,
            'name' => '总公司',
            'leader' => '管理员',
            'sort' => 1,
            'status' => 1,
        ]);
        
        Department::create([
            'parent_id' => $rootDept->id,
            'name' => '技术部',
            'leader' => '技术负责人',
            'sort' => 1,
            'status' => 1,
        ]);
        
        Department::create([
            'parent_id' => $rootDept->id,
            'name' => '运营部',
            'leader' => '运营负责人',
            'sort' => 2,
            'status' => 1,
        ]);
    }

    /**
     * 创建岗位
     */
    protected function createPositions(): void
    {
        Position::create([
            'name' => '董事长',
            'code' => 'chairman',
            'sort' => 1,
            'status' => 1,
        ]);
        
        Position::create([
            'name' => '项目经理',
            'code' => 'pm',
            'sort' => 2,
            'status' => 1,
        ]);
        
        Position::create([
            'name' => '开发工程师',
            'code' => 'developer',
            'sort' => 3,
            'status' => 1,
        ]);
    }

    /**
     * 创建菜单
     */
    protected function createMenus(): void
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

        // 用户管理
        $user = Menu::create([
            'parent_id' => $system->id,
            'name' => '用户管理',
            'code' => 'system:user',
            'type' => 'M',
            'icon' => 'user',
            'route' => '/system/user',
            'component' => 'system/user/index',
            'permission' => 'system:user:list',
            'sort' => 1,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $user->id, 'name' => '新增用户', 'code' => 'system:user:create', 'type' => 'B', 'permission' => 'system:user:create', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $user->id, 'name' => '编辑用户', 'code' => 'system:user:update', 'type' => 'B', 'permission' => 'system:user:update', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $user->id, 'name' => '删除用户', 'code' => 'system:user:delete', 'type' => 'B', 'permission' => 'system:user:delete', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $user->id, 'name' => '分配角色', 'code' => 'system:user:assign-role', 'type' => 'B', 'permission' => 'system:user:assign-role', 'sort' => 4, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $user->id, 'name' => '重置密码', 'code' => 'system:user:reset-password', 'type' => 'B', 'permission' => 'system:user:reset-password', 'sort' => 5, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 角色管理
        $role = Menu::create([
            'parent_id' => $system->id,
            'name' => '角色管理',
            'code' => 'system:role',
            'type' => 'M',
            'icon' => 'peoples',
            'route' => '/system/role',
            'component' => 'system/role/index',
            'permission' => 'system:role:list',
            'sort' => 2,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $role->id, 'name' => '新增角色', 'code' => 'system:role:create', 'type' => 'B', 'permission' => 'system:role:create', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $role->id, 'name' => '编辑角色', 'code' => 'system:role:update', 'type' => 'B', 'permission' => 'system:role:update', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $role->id, 'name' => '删除角色', 'code' => 'system:role:delete', 'type' => 'B', 'permission' => 'system:role:delete', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $role->id, 'name' => '分配权限', 'code' => 'system:role:assign-menu', 'type' => 'B', 'permission' => 'system:role:assign-menu', 'sort' => 4, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 菜单管理
        $menu = Menu::create([
            'parent_id' => $system->id,
            'name' => '菜单管理',
            'code' => 'system:menu',
            'type' => 'M',
            'icon' => 'menu',
            'route' => '/system/menu',
            'component' => 'system/menu/index',
            'permission' => 'system:menu:list',
            'sort' => 3,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $menu->id, 'name' => '新增菜单', 'code' => 'system:menu:create', 'type' => 'B', 'permission' => 'system:menu:create', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $menu->id, 'name' => '编辑菜单', 'code' => 'system:menu:update', 'type' => 'B', 'permission' => 'system:menu:update', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $menu->id, 'name' => '删除菜单', 'code' => 'system:menu:delete', 'type' => 'B', 'permission' => 'system:menu:delete', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 部门管理
        $dept = Menu::create([
            'parent_id' => $system->id,
            'name' => '部门管理',
            'code' => 'system:department',
            'type' => 'M',
            'icon' => 'tree',
            'route' => '/system/department',
            'component' => 'system/department/index',
            'permission' => 'system:department:list',
            'sort' => 4,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $dept->id, 'name' => '新增部门', 'code' => 'system:department:create', 'type' => 'B', 'permission' => 'system:department:create', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $dept->id, 'name' => '编辑部门', 'code' => 'system:department:update', 'type' => 'B', 'permission' => 'system:department:update', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $dept->id, 'name' => '删除部门', 'code' => 'system:department:delete', 'type' => 'B', 'permission' => 'system:department:delete', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 岗位管理
        $position = Menu::create([
            'parent_id' => $system->id,
            'name' => '岗位管理',
            'code' => 'system:position',
            'type' => 'M',
            'icon' => 'post',
            'route' => '/system/position',
            'component' => 'system/position/index',
            'permission' => 'system:position:list',
            'sort' => 5,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $position->id, 'name' => '新增岗位', 'code' => 'system:position:create', 'type' => 'B', 'permission' => 'system:position:create', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $position->id, 'name' => '编辑岗位', 'code' => 'system:position:update', 'type' => 'B', 'permission' => 'system:position:update', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $position->id, 'name' => '删除岗位', 'code' => 'system:position:delete', 'type' => 'B', 'permission' => 'system:position:delete', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 字典管理
        $dict = Menu::create([
            'parent_id' => $system->id,
            'name' => '字典管理',
            'code' => 'system:dictionary',
            'type' => 'M',
            'icon' => 'collection',
            'route' => '/system/dictionary',
            'component' => 'system/dictionary/index',
            'permission' => 'system:dictionary:list',
            'sort' => 6,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $dict->id, 'name' => '新增字典', 'code' => 'system:dictionary:create', 'type' => 'B', 'permission' => 'system:dictionary:create', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $dict->id, 'name' => '编辑字典', 'code' => 'system:dictionary:update', 'type' => 'B', 'permission' => 'system:dictionary:update', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $dict->id, 'name' => '删除字典', 'code' => 'system:dictionary:delete', 'type' => 'B', 'permission' => 'system:dictionary:delete', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $dict->id, 'name' => '管理字典项', 'code' => 'system:dictionary:item', 'type' => 'B', 'permission' => 'system:dictionary:item', 'sort' => 4, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

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

        // 操作日志
        $operationLog = Menu::create([
            'parent_id' => $monitor->id,
            'name' => '操作日志',
            'code' => 'monitor:operation-log',
            'type' => 'M',
            'icon' => 'document',
            'route' => '/monitor/operation-log',
            'component' => 'monitor/operationLog/index',
            'permission' => 'monitor:operation-log:list',
            'sort' => 1,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $operationLog->id, 'name' => '查看详情', 'code' => 'monitor:operation-log:view', 'type' => 'B', 'permission' => 'monitor:operation-log:view', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $operationLog->id, 'name' => '清理日志', 'code' => 'monitor:operation-log:clear', 'type' => 'B', 'permission' => 'monitor:operation-log:clear', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 登录日志
        $loginLog = Menu::create([
            'parent_id' => $monitor->id,
            'name' => '登录日志',
            'code' => 'monitor:login-log',
            'type' => 'M',
            'icon' => 'user',
            'route' => '/monitor/login-log',
            'component' => 'monitor/loginLog/index',
            'permission' => 'monitor:login-log:list',
            'sort' => 2,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $loginLog->id, 'name' => '清理日志', 'code' => 'monitor:login-log:clear', 'type' => 'B', 'permission' => 'monitor:login-log:clear', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 工作流管理
        $workflow = Menu::create([
            'parent_id' => 0,
            'name' => '工作流',
            'code' => 'workflow',
            'type' => 'M',
            'icon' => 'guide',
            'route' => '/workflow',
            'component' => 'Layout',
            'sort' => 3,
            'status' => 1,
        ]);

        // 流程定义
        $definition = Menu::create([
            'parent_id' => $workflow->id,
            'name' => '流程定义',
            'code' => 'workflow:definition',
            'type' => 'M',
            'icon' => 'edit',
            'route' => '/workflow/definition',
            'component' => 'workflow/definition/index',
            'permission' => 'workflow:definition:list',
            'sort' => 1,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $definition->id, 'name' => '新增流程', 'code' => 'workflow:definition:add', 'type' => 'B', 'permission' => 'workflow:definition:add', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $definition->id, 'name' => '编辑流程', 'code' => 'workflow:definition:edit', 'type' => 'B', 'permission' => 'workflow:definition:edit', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $definition->id, 'name' => '删除流程', 'code' => 'workflow:definition:delete', 'type' => 'B', 'permission' => 'workflow:definition:delete', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $definition->id, 'name' => '设计流程', 'code' => 'workflow:definition:design', 'type' => 'B', 'permission' => 'workflow:definition:design', 'sort' => 4, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $definition->id, 'name' => '发布流程', 'code' => 'workflow:definition:publish', 'type' => 'B', 'permission' => 'workflow:definition:publish', 'sort' => 5, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 流程实例
        $instance = Menu::create([
            'parent_id' => $workflow->id,
            'name' => '流程实例',
            'code' => 'workflow:instance',
            'type' => 'M',
            'icon' => 'list',
            'route' => '/workflow/instance',
            'component' => 'workflow/instance/index',
            'permission' => 'workflow:instance:list',
            'sort' => 2,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $instance->id, 'name' => '查看详情', 'code' => 'workflow:instance:view', 'type' => 'B', 'permission' => 'workflow:instance:view', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $instance->id, 'name' => '撤回流程', 'code' => 'workflow:instance:withdraw', 'type' => 'B', 'permission' => 'workflow:instance:withdraw', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 待办任务
        $task = Menu::create([
            'parent_id' => $workflow->id,
            'name' => '待办任务',
            'code' => 'workflow:task',
            'type' => 'M',
            'icon' => 'message',
            'route' => '/workflow/task',
            'component' => 'workflow/task/index',
            'permission' => 'workflow:task:list',
            'sort' => 3,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $task->id, 'name' => '审批通过', 'code' => 'workflow:task:approve', 'type' => 'B', 'permission' => 'workflow:task:approve', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $task->id, 'name' => '审批拒绝', 'code' => 'workflow:task:reject', 'type' => 'B', 'permission' => 'workflow:task:reject', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $task->id, 'name' => '转办任务', 'code' => 'workflow:task:delegate', 'type' => 'B', 'permission' => 'workflow:task:delegate', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 表单设计器
        $form = Menu::create([
            'parent_id' => 0,
            'name' => '表单设计',
            'code' => 'form',
            'type' => 'M',
            'icon' => 'edit',
            'route' => '/form',
            'component' => 'Layout',
            'sort' => 4,
            'status' => 1,
        ]);

        // 表单定义
        $formDefinition = Menu::create([
            'parent_id' => $form->id,
            'name' => '表单定义',
            'code' => 'form:definition',
            'type' => 'M',
            'icon' => 'document',
            'route' => '/form/definition',
            'component' => 'form/definition/index',
            'permission' => 'form:definition:list',
            'sort' => 1,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $formDefinition->id, 'name' => '新增表单', 'code' => 'form:definition:add', 'type' => 'B', 'permission' => 'form:definition:add', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $formDefinition->id, 'name' => '编辑表单', 'code' => 'form:definition:edit', 'type' => 'B', 'permission' => 'form:definition:edit', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $formDefinition->id, 'name' => '删除表单', 'code' => 'form:definition:delete', 'type' => 'B', 'permission' => 'form:definition:delete', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $formDefinition->id, 'name' => '设计表单', 'code' => 'form:definition:design', 'type' => 'B', 'permission' => 'form:definition:design', 'sort' => 4, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $formDefinition->id, 'name' => '启用表单', 'code' => 'form:definition:enable', 'type' => 'B', 'permission' => 'form:definition:enable', 'sort' => 5, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $formDefinition->id, 'name' => '停用表单', 'code' => 'form:definition:disable', 'type' => 'B', 'permission' => 'form:definition:disable', 'sort' => 6, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $formDefinition->id, 'name' => '复制表单', 'code' => 'form:definition:copy', 'type' => 'B', 'permission' => 'form:definition:copy', 'sort' => 7, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 表单数据管理
        $formData = Menu::create([
            'parent_id' => $form->id,
            'name' => '数据管理',
            'code' => 'form:data',
            'type' => 'M',
            'icon' => 'data',
            'route' => '/form/data',
            'component' => 'form/data/index',
            'permission' => 'form:data:list',
            'sort' => 2,
            'status' => 1,
        ]);

        Menu::insert([
            ['parent_id' => $formData->id, 'name' => '查看详情', 'code' => 'form:data:view', 'type' => 'B', 'permission' => 'form:data:view', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['parent_id' => $formData->id, 'name' => '删除数据', 'code' => 'form:data:delete', 'type' => 'B', 'permission' => 'form:data:delete', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * 创建角色
     */
    protected function createRoles(): void
    {
        // 超级管理员角色
        $superAdmin = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'api',
            'data_scope' => 1,
            'remark' => '超级管理员，拥有所有权限',
        ]);
        
        // 分配所有菜单
        $superAdmin->syncMenus(Menu::pluck('id')->toArray());
        
        // 普通管理员角色
        $admin = Role::create([
            'name' => 'admin',
            'guard_name' => 'api',
            'data_scope' => 3,
            'remark' => '普通管理员',
        ]);
        
        // 分配部分菜单
        $menuIds = Menu::whereIn('code', [
            'system', 'system:user', 'system:user:create', 'system:user:update',
            'system:department', 'system:position', 'system:dictionary',
            'monitor', 'monitor:operation-log', 'monitor:login-log',
            'workflow', 'workflow:definition', 'workflow:instance', 'workflow:task',
            'form', 'form:definition', 'form:data'
        ])->pluck('id')->toArray();
        $admin->syncMenus($menuIds);
    }

    /**
     * 创建超级管理员用户
     */
    protected function createSuperAdmin(): void
    {
        $user = User::create([
            'username' => 'admin',
            'name' => '超级管理员',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'department_id' => 1,
            'status' => 1,
        ]);
        
        // 分配超级管理员角色
        $user->assignRole('super-admin');
        
        // 分配岗位
        $user->positions()->sync([1]);
    }
}