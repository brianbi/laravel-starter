import type { PageParams } from "../system/types";

/** 表单字段 */
export interface FormField {
  name: string;
  label: string;
  type: string;
  placeholder?: string;
  default_value?: any;
  required?: boolean;
  rules?: FormFieldRule;
  options?: Array<{ label: string; value: any }>;
  props?: Record<string, any>;
}

/** 表单字段验证规则 */
export interface FormFieldRule {
  required?: boolean;
  min?: number;
  max?: number;
  min_length?: number;
  max_length?: number;
  pattern?: string;
  message?: string;
}

/** 表单定义 */
export interface FormDefinition {
  id: number;
  code: string;
  name: string;
  description: string;
  fields: FormField[];
  layout?: Record<string, any>;
  rules?: Record<string, any>;
  status: number;
  created_by: number;
  creator?: {
    id: number;
    name: string;
  };
  created_at: string;
  updated_at: string;
}

/** 表单定义查询参数 */
export interface FormDefinitionQuery extends PageParams {
  name?: string;
  code?: string;
  status?: number;
  created_by?: number;
  created_at_start?: string;
  created_at_end?: string;
}

/** 表单定义表单 */
export interface FormDefinitionForm {
  code: string;
  name: string;
  description?: string;
  fields: FormField[];
  layout?: Record<string, any>;
  rules?: Record<string, any>;
}

/** 表单数据 */
export interface FormData {
  id: number;
  form_id: number;
  form?: FormDefinition;
  instance_id: number;
  data: Record<string, any>;
  created_by: number;
  creator?: {
    id: number;
    name: string;
  };
  created_at: string;
  updated_at: string;
}

/** 表单数据查询参数 */
export interface FormDataQuery extends PageParams {
  form_id?: number;
  instance_id?: number;
  created_by?: number;
  created_at_start?: string;
  created_at_end?: string;
}

/** 表单字段类型 */
export interface FieldType {
  type: string;
  label: string;
  icon: string;
  defaultProps: Record<string, any>;
}

/** 表单统计数据 */
export interface FormStatistics {
  total_submissions: number;
  daily_submissions: number;
  weekly_submissions: number;
  monthly_submissions: number;
}
