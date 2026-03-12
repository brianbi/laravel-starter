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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('岗位名称');
            $table->string('code', 50)->unique()->comment('岗位编码');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1正常');
            $table->string('remark', 200)->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('sort');
        });

        // 用户-岗位关联表
        Schema::create('user_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('position_id');

            $table->primary(['user_id', 'position_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_positions');
        Schema::dropIfExists('positions');
    }
};
