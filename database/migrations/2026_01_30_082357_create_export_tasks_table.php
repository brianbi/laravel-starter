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
        Schema::create('export_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('任务唯一标识');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('用户ID');
            $table->string('resource', 50)->comment('资源类型');
            $table->string('scope', 20)->default('all')->comment('导出范围: all/page/selected');
            $table->json('filters')->nullable()->comment('筛选条件');
            $table->json('fields')->nullable()->comment('导出字段');
            $table->string('format', 10)->default('xlsx')->comment('格式: xlsx/csv');
            $table->unsignedInteger('total_count')->default(0)->comment('总记录数');
            $table->tinyInteger('status')->default(0)->comment('状态: 0待处理 1处理中 2成功 3失败');
            $table->string('file_path')->nullable()->comment('文件路径');
            $table->unsignedBigInteger('file_size')->default(0)->comment('文件大小(bytes)');
            $table->string('error_message')->nullable()->comment('错误信息');
            $table->timestamp('started_at')->nullable()->comment('开始处理时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamp('expires_at')->nullable()->comment('过期时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_tasks');
    }
};
