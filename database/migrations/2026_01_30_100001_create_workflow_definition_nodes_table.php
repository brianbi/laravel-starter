<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definition_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('definition_id')->comment('流程定义ID');
            $table->string('node_id', 50)->comment('节点标识');
            $table->string('type', 30)->comment('节点类型');
            $table->string('name', 100)->comment('节点名称');
            $table->json('config')->nullable()->comment('节点配置');
            $table->json('field_permissions')->nullable()->comment('字段权限配置');
            $table->json('timeout_config')->nullable()->comment('超时配置');
            $table->unsignedInteger('sort')->default(0)->comment('排序号');

            $table->foreign('definition_id')
                ->references('id')
                ->on('workflow_definitions')
                ->onDelete('cascade');
            
            $table->unique(['definition_id', 'node_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_definition_nodes');
    }
};
