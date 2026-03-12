<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('流程编码');
            $table->string('name', 100)->comment('流程名称');
            $table->string('description')->nullable()->comment('流程描述');
            $table->string('form_type', 20)->default('model')->comment('表单类型：builtin/model');
            $table->unsignedBigInteger('form_id')->nullable()->comment('内置表单ID');
            $table->string('model_class', 200)->nullable()->comment('业务模型类名');
            $table->unsignedInteger('version')->default(1)->comment('版本号');
            $table->json('graph')->nullable()->comment('流程图定义（节点、连线）');
            $table->tinyInteger('status')->default(0)->comment('状态：0禁用 1启用');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新人');
            $table->timestamps();

            $table->index('code');
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_definitions');
    }
};
