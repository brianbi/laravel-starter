<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('definition_id')->comment('流程定义ID');
            $table->unsignedInteger('definition_version')->comment('使用的流程版本');
            $table->string('business_type', 100)->nullable()->comment('业务类型');
            $table->unsignedBigInteger('business_id')->nullable()->comment('业务ID');
            $table->json('form_data')->nullable()->comment('表单数据');
            $table->unsignedBigInteger('initiator_id')->comment('发起人ID');
            $table->string('current_node_id', 50)->nullable()->comment('当前节点ID');
            $table->tinyInteger('status')->default(0)->comment('状态：0草稿 1进行中 2已通过 3已拒绝 4已撤回');
            $table->timestamp('started_at')->nullable()->comment('发起时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();

            $table->foreign('definition_id')
                ->references('id')
                ->on('workflow_definitions')
                ->onDelete('restrict');
            
            $table->index('definition_id');
            $table->index('initiator_id');
            $table->index('status');
            $table->index(['business_type', 'business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
