import { http } from "@/utils/http";
import type { PageResult, DataResult } from "../system/types";
import type {
  FormDefinition,
  FormDefinitionQuery,
  FormDefinitionForm,
  FieldType
} from "./types";

const BASE_URL = "/api/admin/forms";

/** 获取表单定义列表 */
export const getFormDefinitionList = (params?: FormDefinitionQuery) => {
  return http.request<PageResult<FormDefinition>>("get", BASE_URL, { params });
};

/** 获取启用的表单列表 */
export const getEnabledForms = () => {
  return http.request<DataResult<FormDefinition[]>>("get", `${BASE_URL}/enabled`);
};

/** 获取表单定义详情 */
export const getFormDefinition = (id: number) => {
  return http.request<DataResult<FormDefinition>>("get", `${BASE_URL}/${id}`);
};

/** 创建表单定义 */
export const createFormDefinition = (data: FormDefinitionForm) => {
  return http.request<DataResult<FormDefinition>>("post", BASE_URL, { data });
};

/** 更新表单定义 */
export const updateFormDefinition = (id: number, data: FormDefinitionForm) => {
  return http.request<DataResult<FormDefinition>>("put", `${BASE_URL}/${id}`, {
    data
  });
};

/** 删除表单定义 */
export const deleteFormDefinition = (id: number) => {
  return http.request("delete", `${BASE_URL}/${id}`);
};

/** 启用表单 */
export const enableForm = (id: number) => {
  return http.request("post", `${BASE_URL}/${id}/enable`);
};

/** 停用表单 */
export const disableForm = (id: number) => {
  return http.request("post", `${BASE_URL}/${id}/disable`);
};

/** 复制表单 */
export const copyForm = (id: number) => {
  return http.request<DataResult<FormDefinition>>("post", `${BASE_URL}/${id}/copy`);
};

/** 获取字段类型列表 */
export const getFieldTypes = () => {
  return http.request<DataResult<FieldType[]>>("get", `${BASE_URL}/field-types`);
};

/** 验证表单数据 */
export const validateFormData = (id: number, data: Record<string, any>) => {
  return http.request("post", `${BASE_URL}/${id}/validate`, { data });
};

/** 获取表单统计 */
export const getFormStatistics = (id: number) => {
  return http.request("get", `${BASE_URL}/${id}/statistics`);
};
