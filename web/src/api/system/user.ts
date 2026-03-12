import { http } from "@/utils/http";
import type { User, UserQuery, UserForm, PageResult, DataResult } from "./types";

const BASE_URL = "/api/admin/users";

/** 获取用户列表 */
export const getUserList = (params?: UserQuery) => {
  return http.request<PageResult<User>>("get", BASE_URL, { params });
};

/** 获取用户详情 */
export const getUser = (id: number) => {
  return http.request<DataResult<User>>("get", `${BASE_URL}/${id}`);
};

/** 创建用户 */
export const createUser = (data: UserForm) => {
  return http.request<DataResult<User>>("post", BASE_URL, { data });
};

/** 更新用户 */
export const updateUser = (id: number, data: UserForm) => {
  return http.request<DataResult<User>>("put", `${BASE_URL}/${id}`, { data });
};

/** 删除用户 */
export const deleteUser = (id: number) => {
  return http.request("delete", `${BASE_URL}/${id}`);
};

/** 切换用户状态 */
export const updateUserStatus = (id: number, status: number) => {
  return http.request("put", `${BASE_URL}/${id}/status`, { data: { status } });
};

/** 重置用户密码 */
export const resetUserPassword = (id: number, password: string) => {
  return http.request("put", `${BASE_URL}/${id}/password`, {
    data: { password }
  });
};
