<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instance_id')->comment('流程实例ID');
            $table->unsignedBigInteger('task_id')->nullable()->comment('任务ID');
            $table->string('node_id', 50)->comment('节点ID');
            $table->string('node_name', 100)->comment('节点名称');
            $table->unsignedBigInteger('user_id')->comment('操作人ID');
            $table->string('action', 20)->comment('操作类型');
            $table->text('comment')->nullable()->comment('审批意见');
            $table->json('form_data')->nullable()->comment('修改的表单数据');
            $table->timestamp('created_at')->nullable();

            $table->foreign('instance_id')
                ->references('id')
                ->on('workflow_instances')
                ->onDelete('cascade');
            
            $table->index('instance_id');
            $table->index('user_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_records');
    }
};
