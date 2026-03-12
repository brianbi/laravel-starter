<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级ID');
            $table->string('name', 50)->comment('菜单名称');
            $table->string('code', 100)->nullable()->comment('权限标识');
            $table->char('type', 1)->default('M')->comment('类型: M菜单 B按钮 L外链');
            $table->string('icon', 100)->nullable()->comment('图标');
            $table->string('route', 200)->nullable()->comment('前端路由');
            $table->string('component', 200)->nullable()->comment('前端组件路径');
            $table->string('redirect', 200)->nullable()->comment('重定向');
            $table->string('permission', 100)->nullable()->comment('权限标识');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1正常');
            $table->tinyInteger('is_hidden')->default(0)->comment('是否隐藏: 0否 1是');
            $table->tinyInteger('is_cache')->default(1)->comment('是否缓存: 0否 1是');
            $table->string('remark', 200)->nullable()->comment('备注');
            $table->timestamps();

            $table->index('parent_id');
            $table->index('status');
            $table->index('sort');
        });

        // 角色-菜单关联表
        Schema::create('role_menus', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('menu_id');

            $table->primary(['role_id', 'menu_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_menus');
        Schema::dropIfExists('menus');
    }
};
