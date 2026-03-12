import { http } from "@/utils/http";
import type { PageResult, DataResult } from "../system/types";
import type { WorkflowInstance, WorkflowInstanceQuery, WorkflowInstanceForm } from "./types";

const BASE_URL = "/api/admin/workflow/instances";

/** 获取我发起的流程列表 */
export const getMyWorkflowInstances = (params?: WorkflowInstanceQuery) => {
  return http.request<PageResult<WorkflowInstance>>("get", `${BASE_URL}/initiated`, {
    params
  });
};

/** 发起流程 */
export const startWorkflow = (data: WorkflowInstanceForm) => {
  return http.request("post", BASE_URL, { data });
};

/** 获取流程实例详情 */
export const getWorkflowInstance = (id: number) => {
  return http.request<DataResult<WorkflowInstance>>("get", `${BASE_URL}/${id}`);
};

/** 获取流程时间线 */
export const getWorkflowTimeline = (id: number) => {
  return http.request("get", `${BASE_URL}/${id}/timeline`);
};

/** 撤回流程 */
export const withdrawWorkflowInstance = (id: number) => {
  return http.request("post", `${BASE_URL}/${id}/withdraw`);
};

/** 获取业务审批状态 */
export const getBusinessApprovalStatus = (businessType: string, businessId: number) => {
  return http.request("get", `${BASE_URL}/business-status`, {
    params: { business_type: businessType, business_id: businessId }
  });
};
