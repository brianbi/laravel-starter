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
        // 为角色表添加额外字段
        Schema::table('roles', function (Blueprint $table) {
            $table->tinyInteger('data_scope')->default(1)->after('guard_name')->comment('数据权限范围: 1全部 2自定义 3本部门 4本部门及以下 5仅本人');
            $table->string('remark', 200)->nullable()->after('data_scope')->comment('备注');
        });

        // 角色-部门关联表（用于自定义数据权限）
        Schema::create('role_departments', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('department_id');

            $table->primary(['role_id', 'department_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_departments');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['data_scope', 'remark']);
        });
    }
};
