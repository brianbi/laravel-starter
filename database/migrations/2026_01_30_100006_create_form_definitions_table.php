<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('表单编码');
            $table->string('name', 100)->comment('表单名称');
            $table->string('description')->nullable()->comment('表单描述');
            $table->json('fields')->nullable()->comment('字段定义');
            $table->json('layout')->nullable()->comment('布局配置');
            $table->json('rules')->nullable()->comment('验证规则');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用 1启用');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->timestamps();

            $table->index('code');
            $table->index('status');
        });

        // 表单数据存储表
        Schema::create('form_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id')->comment('表单定义ID');
            $table->unsignedBigInteger('instance_id')->nullable()->comment('流程实例ID');
            $table->json('data')->nullable()->comment('表单数据');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->timestamps();

            $table->foreign('form_id')
                ->references('id')
                ->on('form_definitions')
                ->onDelete('cascade');

            $table->index('form_id');
            $table->index('instance_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_data');
        Schema::dropIfExists('form_definitions');
    }
};
