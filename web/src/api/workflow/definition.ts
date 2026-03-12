import { http } from "@/utils/http";
import type { PageResult, DataResult } from "../system/types";
import type {
  WorkflowDefinition,
  WorkflowDefinitionQuery,
  WorkflowDefinitionForm
} from "./types";

const BASE_URL = "/api/admin/workflow/definitions";

/** 获取流程定义列表 */
export const getWorkflowDefinitionList = (params?: WorkflowDefinitionQuery) => {
  return http.request<PageResult<WorkflowDefinition>>("get", BASE_URL, {
    params
  });
};

/** 获取流程定义详情 */
export const getWorkflowDefinition = (id: number) => {
  return http.request<DataResult<WorkflowDefinition>>("get", `${BASE_URL}/${id}`);
};

/** 创建流程定义 */
export const createWorkflowDefinition = (data: WorkflowDefinitionForm) => {
  return http.request<DataResult<WorkflowDefinition>>("post", BASE_URL, {
    data
  });
};

/** 更新流程定义 */
export const updateWorkflowDefinition = (
  id: number,
  data: WorkflowDefinitionForm
) => {
  return http.request<DataResult<WorkflowDefinition>>(
    "put",
    `${BASE_URL}/${id}`,
    { data }
  );
};

/** 删除流程定义 */
export const deleteWorkflowDefinition = (id: number) => {
  return http.request("delete", `${BASE_URL}/${id}`);
};

/** 发布流程 */
export const publishWorkflow = (id: number) => {
  return http.request("post", `${BASE_URL}/${id}/publish`);
};

/** 获取版本历史 */
export const getWorkflowVersions = (id: number) => {
  return http.request("get", `${BASE_URL}/${id}/versions`);
};

/** 获取启用的流程定义 */
export const getEnabledWorkflows = () => {
  return http.request<DataResult<WorkflowDefinition[]>>("get", `${BASE_URL}/enabled`);
};

/** 获取可用节点类型 */
export const getNodeTypes = () => {
  return http.request("get", `${BASE_URL}/node-types`);
};
