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
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->comment('登录账号');
            $table->string('ip', 45)->nullable()->comment('登录IP');
            $table->string('ip_location', 200)->nullable()->comment('IP归属地');
            $table->string('os', 100)->nullable()->comment('操作系统');
            $table->string('browser', 100)->nullable()->comment('浏览器');
            $table->tinyInteger('status')->default(1)->comment('状态: 0失败 1成功');
            $table->string('message', 200)->nullable()->comment('提示消息');
            $table->timestamp('login_at')->nullable()->comment('登录时间');

            $table->index('username');
            $table->index('status');
            $table->index('login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
