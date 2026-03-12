<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * 创建用户
     */
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // 处理密码
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user = $this->repository->create($data);

            // 分配角色
            if (!empty($data['role_ids'])) {
                $user->syncRoles($data['role_ids']);
            }

            // 分配岗位
            if (!empty($data['position_ids'])) {
                $user->positions()->sync($data['position_ids']);
            }

            return $user;
        });
    }

    /**
     * 更新用户
     */
    public function update(int $id, array $data): Model
    {
        return DB::transaction(function () use ($id, $data) {
            // 处理密码
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user = $this->repository->update($id, $data);

            // 同步角色
            if (isset($data['role_ids'])) {
                $user->syncRoles($data['role_ids']);
            }

            // 同步岗位
            if (isset($data['position_ids'])) {
                $user->positions()->sync($data['position_ids']);
            }

            return $user;
        });
    }

    /**
     * 重置密码
     */
    public function resetPassword(int $id, string $password): bool
    {
        return $this->repository->update($id, [
            'password' => Hash::make($password),
        ]) instanceof User;
    }

    /**
     * 检查用户名是否存在
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        return $this->repository->usernameExists($username, $excludeId);
    }

    /**
     * 检查邮箱是否存在
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        return $this->repository->emailExists($email, $excludeId);
    }
}
