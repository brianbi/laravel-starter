import { http } from "@/utils/http";
import type { PageResult, DataResult } from "../system/types";
import type {
  WorkflowTask,
  WorkflowTaskQuery,
  TaskActionForm
} from "./types";

const BASE_URL = "/api/admin/workflow/tasks";

/** 获取我的待办任务 */
export const getMyPendingTasks = (params?: WorkflowTaskQuery) => {
  return http.request<PageResult<WorkflowTask>>("get", BASE_URL, {
    params
  });
};

/** 获取我的已办任务 */
export const getMyCompletedTasks = (params?: WorkflowTaskQuery) => {
  return http.request<PageResult<WorkflowTask>>("get", `${BASE_URL}/done`, {
    params
  });
};

/** 获取待办数量 */
export const getPendingTaskCount = () => {
  return http.request<DataResult<{ count: number }>>("get", `${BASE_URL}/count`);
};

/** 获取任务详情 */
export const getWorkflowTask = (id: number) => {
  return http.request<DataResult<WorkflowTask>>("get", `${BASE_URL}/${id}`);
};

/** 审批通过 */
export const approveTask = (id: number, data?: TaskActionForm) => {
  return http.request("post", `${BASE_URL}/${id}/approve`, { data });
};

/** 审批拒绝 */
export const rejectTask = (id: number, data?: TaskActionForm) => {
  return http.request("post", `${BASE_URL}/${id}/reject`, { data });
};

/** 退回 */
export const returnTask = (id: number, data: TaskActionForm & { target_node: string }) => {
  return http.request("post", `${BASE_URL}/${id}/return`, { data });
};

/** 转办任务 */
export const delegateTask = (id: number, data: TaskActionForm & { target_user: number }) => {
  return http.request("post", `${BASE_URL}/${id}/delegate`, { data });
};

/** 加签 */
export const addSignTask = (id: number, data: TaskActionForm & { target_users: number[]; sign_type?: string }) => {
  return http.request("post", `${BASE_URL}/${id}/add-sign`, { data });
};

/** 获取工作流统计 */
export const getWorkflowStatistics = () => {
  return http.request("get", `${BASE_URL}/statistics`);
};
