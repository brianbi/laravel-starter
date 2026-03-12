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
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('操作用户ID');
            $table->string('username', 50)->nullable()->comment('用户名');
            $table->string('method', 10)->comment('请求方法');
            $table->string('router', 200)->comment('请求路由');
            $table->string('service_name', 100)->nullable()->comment('业务名称');
            $table->string('ip', 45)->nullable()->comment('IP地址');
            $table->string('ip_location', 200)->nullable()->comment('IP归属地');
            $table->text('request_data')->nullable()->comment('请求参数');
            $table->integer('response_code')->nullable()->comment('响应状态码');
            $table->text('response_data')->nullable()->comment('响应数据');
            $table->integer('execution_time')->nullable()->comment('执行时间(ms)');
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index('created_at');
            $table->index('method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_logs');
    }
};
