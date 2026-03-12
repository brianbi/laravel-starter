<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instance_id')->comment('流程实例ID');
            $table->string('node_id', 50)->comment('节点ID');
            $table->string('node_type', 30)->comment('节点类型');
            $table->string('node_name', 100)->comment('节点名称');
            $table->unsignedBigInteger('assignee_id')->comment('指派人ID');
            $table->string('assignee_type', 20)->default('user')->comment('指派类型');
            $table->tinyInteger('status')->default(0)->comment('状态：0待处理 1已处理 2已转办 3已取消');
            $table->string('action', 20)->nullable()->comment('处理动作');
            $table->text('comment')->nullable()->comment('审批意见');
            $table->unsignedBigInteger('delegate_from')->nullable()->comment('委托来源');
            $table->timestamp('timeout_at')->nullable()->comment('超时时间');
            $table->timestamp('processed_at')->nullable()->comment('处理时间');
            $table->timestamp('created_at')->nullable();

            $table->foreign('instance_id')
                ->references('id')
                ->on('workflow_instances')
                ->onDelete('cascade');
            
            $table->index('instance_id');
            $table->index('assignee_id');
            $table->index('status');
            $table->index('timeout_at');
            $table->index(['node_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_tasks');
    }
};
