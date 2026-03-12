import { http } from "@/utils/http";
import type { PageResult, DataResult } from "../system/types";
import type { FormData, FormDataQuery } from "./types";

/** 获取表单数据列表 */
export const getFormDataList = (formId: number, params?: FormDataQuery) => {
  return http.request<PageResult<FormData>>(
    "get",
    `/api/admin/forms/${formId}/data`,
    { params }
  );
};

/** 获取表单数据详情 */
export const getFormData = (formId: number, id: number) => {
  return http.request<DataResult<FormData>>(
    "get",
    `/api/admin/forms/${formId}/data/${id}`
  );
};

/** 提交表单数据 */
export const submitFormData = (
  formId: number,
  data: { data: Record<string, any>; instance_id?: number }
) => {
  return http.request<DataResult<FormData>>(
    "post",
    `/api/admin/forms/${formId}/data`,
    { data }
  );
};

/** 更新表单数据 */
export const updateFormData = (
  formId: number,
  id: number,
  data: { data: Record<string, any> }
) => {
  return http.request<DataResult<FormData>>(
    "put",
    `/api/admin/forms/${formId}/data/${id}`,
    { data }
  );
};

/** 删除表单数据 */
export const deleteFormData = (formId: number, id: number) => {
  return http.request("delete", `/api/admin/forms/${formId}/data/${id}`);
};

/** 根据流程实例获取表单数据 */
export const getFormDataByInstance = (instanceId: number) => {
  return http.request<DataResult<FormData>>(
    "get",
    `/api/admin/form-data/by-instance/${instanceId}`
  );
};
