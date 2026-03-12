import { http } from "@/utils/http";
import type { Department, DepartmentForm, DataResult } from "./types";

const BASE_URL = "/api/admin/departments";

/** 获取部门树 */
export const getDepartmentTree = () => {
  return http.request<DataResult<Department[]>>("get", `${BASE_URL}/tree`);
};

/** 获取部门列表 */
export const getDepartmentList = () => {
  return http.request<DataResult<Department[]>>("get", BASE_URL);
};

/** 获取部门详情 */
export const getDepartment = (id: number) => {
  return http.request<DataResult<Department>>("get", `${BASE_URL}/${id}`);
};

/** 创建部门 */
export const createDepartment = (data: DepartmentForm) => {
  return http.request<DataResult<Department>>("post", BASE_URL, { data });
};

/** 更新部门 */
export const updateDepartment = (id: number, data: DepartmentForm) => {
  return http.request<DataResult<Department>>("put", `${BASE_URL}/${id}`, {
    data
  });
};

/** 删除部门 */
export const deleteDepartment = (id: number) => {
  return http.request("delete", `${BASE_URL}/${id}`);
};
