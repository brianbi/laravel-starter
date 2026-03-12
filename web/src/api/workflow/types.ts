import type { PageParams } from "../system/types";

/** 工作流节点 */
export interface WorkflowNode {
  id: string;
  type: string;
  name: string;
  assignee_type?: string;
  assignee_ids?: number[];
  form_fields?: string[];
  conditions?: Array<{
    field: string;
    operator: string;
    value: any;
    target: string;
  }>;
  position?: { x: number; y: number };
}

/** 工作流边 */
export interface WorkflowEdge {
  id: string;
  source: string;
  target: string;
  condition?: string;
}

/** 工作流图形 */
export interface WorkflowGraph {
  nodes: WorkflowNode[];
  edges: WorkflowEdge[];
}

/** 工作流定义 */
export interface WorkflowDefinition {
  id: number;
  code: string;
  name: string;
  description: string;
  form_type: string;
  nodes: WorkflowNode[];
  version: number;
  status: number;
  created_by: number;
  creator?: { id: number; name: string };
  created_at: string;
  updated_at: string;
}

/** 工作流定义查询参数 */
export interface WorkflowDefinitionQuery extends PageParams {
  keyword?: string;
  status?: number;
}

/** 工作流定义表单 */
export interface WorkflowDefinitionForm {
  code: string;
  name: string;
  description?: string;
  form_type?: string;
  nodes?: WorkflowNode[];
}

/** 工作流实例 */
export interface WorkflowInstance {
  id: number;
  definition_id: number;
  definition?: {
    id: number;
    code: string;
    name: string;
  };
  initiator?: {
    id: number;
    name: string;
  };
  form_data: Record<string, any>;
  current_node_id: string;
  status: number;
  status_text: string;
  started_at: string;
  completed_at: string;
  created_at: string;
}

/** 工作流实例查询参数 */
export interface WorkflowInstanceQuery extends PageParams {
  status?: number;
}

/** 发起流程表单 */
export interface WorkflowInstanceForm {
  definition_code: string;
  form_data?: Record<string, any>;
  business_type?: string;
  business_id?: number;
}

/** 工作流任务 */
export interface WorkflowTask {
  id: number;
  instance_id: number;
  instance?: {
    id: number;
    definition?: {
      id: number;
      code: string;
      name: string;
    };
    initiator?: {
      id: number;
      name: string;
    };
    form_data: Record<string, any>;
    status: number;
    status_text: string;
  };
  node_id: string;
  node_name: string;
  node_type: string;
  assignee_id: number;
  status: number;
  status_text: string;
  delegate_from?: number;
  timeout_at: string;
  field_permissions?: Record<string, string>;
  created_at: string;
  completed_at: string;
}

/** 工作流任务查询参数 */
export interface WorkflowTaskQuery extends PageParams {
  instance_id?: number;
  status?: number;
}

/** 任务操作表单 */
export interface TaskActionForm {
  comment?: string;
  form_data?: Record<string, any>;
}

/** 流程时间线项 */
export interface TimelineItem {
  id: number;
  node_name: string;
  operator_name: string;
  action: string;
  action_text: string;
  comment: string;
  created_at: string;
}
