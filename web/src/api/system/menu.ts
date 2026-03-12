import { http } from "@/utils/http";
import type { Menu, MenuForm, DataResult } from "./types";

const BASE_URL = "/api/admin/menus";

/** 获取菜单树 */
export const getMenuTree = () => {
  return http.request<DataResult<Menu[]>>("get", `${BASE_URL}/tree`);
};

/** 获取菜单列表 */
export const getMenuList = () => {
  return http.request<DataResult<Menu[]>>("get", BASE_URL);
};

/** 获取菜单详情 */
export const getMenu = (id: number) => {
  return http.request<DataResult<Menu>>("get", `${BASE_URL}/${id}`);
};

/** 创建菜单 */
export const createMenu = (data: MenuForm) => {
  return http.request<DataResult<Menu>>("post", BASE_URL, { data });
};

/** 更新菜单 */
export const updateMenu = (id: number, data: MenuForm) => {
  return http.request<DataResult<Menu>>("put", `${BASE_URL}/${id}`, { data });
};

/** 删除菜单 */
export const deleteMenu = (id: number) => {
  return http.request("delete", `${BASE_URL}/${id}`);
};
