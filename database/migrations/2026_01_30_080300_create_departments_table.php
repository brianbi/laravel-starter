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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级部门ID');
            $table->string('name', 100)->comment('部门名称');
            $table->string('leader', 50)->nullable()->comment('负责人');
            $table->string('phone', 20)->nullable()->comment('联系电话');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1正常');
            $table->integer('level')->default(1)->comment('层级深度');
            $table->string('path', 500)->nullable()->comment('祖先路径');
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id');
            $table->index('status');
            $table->index('sort');
        });

        // 为 role_departments 添加外键
        Schema::table('role_departments', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_departments', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        Schema::dropIfExists('departments');
    }
};
