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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('id')->comment('登录账号');
            $table->string('phone', 20)->nullable()->after('email')->comment('手机号');
            $table->string('avatar')->nullable()->after('phone')->comment('头像');
            $table->unsignedBigInteger('department_id')->nullable()->after('avatar')->comment('所属部门');
            $table->tinyInteger('status')->default(1)->after('department_id')->comment('状态: 0禁用 1正常');
            $table->string('login_ip', 45)->nullable()->after('status')->comment('最后登录IP');
            $table->timestamp('login_at')->nullable()->after('login_ip')->comment('最后登录时间');
            $table->unsignedBigInteger('created_by')->nullable()->after('remember_token')->comment('创建人');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by')->comment('更新人');
            $table->softDeletes();

            $table->index('department_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['department_id']);
            $table->dropIndex(['status']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'username',
                'phone',
                'avatar',
                'department_id',
                'status',
                'login_ip',
                'login_at',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
