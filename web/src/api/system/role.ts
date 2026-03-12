import { http } from "@/utils/http";
import type { Role, RoleQuery, RoleForm, PageResult, DataResult } from "./types";

const BASE_URL = "/api/admin/roles";

/** 获取角色列表 */
export const getRoleList = (params?: RoleQuery) => {
  return http.request<PageResult<Role>>("get", BASE_URL, { params });
};

/** 获取所有角色（不分页） */
export const getAllRoles = () => {
  return http.request<DataResult<Role[]>>("get", `${BASE_URL}/all`);
};

/** 获取角色详情 */
export const getRole = (id: number) => {
  return http.request<DataResult<Role>>("get", `${BASE_URL}/${id}`);
};

/** 创建角色 */
export const createRole = (data: RoleForm) => {
  return http.request<DataResult<Role>>("post", BASE_URL, { data });
};

/** 更新角色 */
export const updateRole = (id: number, data: RoleForm) => {
  return http.request<DataResult<Role>>("put", `${BASE_URL}/${id}`, { data });
};

/** 删除角色 */
export const deleteRole = (id: number) => {
  return http.request("delete", `${BASE_URL}/${id}`);
};

/** 分配权限 */
export const assignPermissions = (id: number, permissionIds: number[]) => {
  return http.request("put", `${BASE_URL}/${id}/permissions`, {
    data: { permission_ids: permissionIds }
  });
};

/** 分配菜单 */
export const assignMenus = (id: number, menuIds: number[]) => {
  return http.request("put", `${BASE_URL}/${id}/menus`, {
    data: { menu_ids: menuIds }
  });
};
