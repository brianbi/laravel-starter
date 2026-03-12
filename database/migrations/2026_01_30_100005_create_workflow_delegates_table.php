<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_delegates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('委托人ID');
            $table->unsignedBigInteger('delegate_id')->comment('代理人ID');
            $table->unsignedBigInteger('definition_id')->nullable()->comment('指定流程（空=全部流程）');
            $table->timestamp('start_at')->comment('开始时间');
            $table->timestamp('end_at')->comment('结束时间');
            $table->tinyInteger('status')->default(1)->comment('状态：0禁用 1启用');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->index('user_id');
            $table->index('delegate_id');
            $table->index('definition_id');
            $table->index(['status', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_delegates');
    }
};
