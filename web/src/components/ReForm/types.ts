import type { FormRules, FormItemRule } from "element-plus";
import type { VNode, Component } from "vue";

/** 表单项类型 */
export type FormItemType =
  | "input"
  | "textarea"
  | "number"
  | "select"
  | "radio"
  | "checkbox"
  | "switch"
  | "date"
  | "datetime"
  | "daterange"
  | "datetimerange"
  | "time"
  | "cascader"
  | "tree-select"
  | "upload"
  | "slot";

/** 选项配置 */
export interface OptionItem {
  label: string;
  value: any;
  disabled?: boolean;
  children?: OptionItem[];
}

/** 表单项配置 */
export interface FormField {
  /** 字段名（支持嵌套，如 user.name） */
  prop: string;
  /** 标签文本 */
  label: string;
  /** 表单项类型 */
  type: FormItemType;
  /** 默认值 */
  defaultValue?: any;
  /** 占位符 */
  placeholder?: string;
  /** 是否必填 */
  required?: boolean;
  /** 校验规则 */
  rules?: FormItemRule[];
  /** 是否禁用 */
  disabled?: boolean | ((form: Record<string, any>) => boolean);
  /** 是否隐藏 */
  hidden?: boolean | ((form: Record<string, any>) => boolean);
  /** 选项数据（select/radio/checkbox/cascader/tree-select） */
  options?: OptionItem[] | (() => Promise<OptionItem[]>);
  /** 栅格布局占比 */
  span?: number;
  /** 组件属性 */
  props?: Record<string, any>;
  /** 插槽名（type为slot时使用） */
  slot?: string;
  /** 表单项样式 */
  style?: Record<string, any>;
  /** 值变化回调 */
  onChange?: (value: any, form: Record<string, any>) => void;
  /** 自定义渲染 */
  render?: (form: Record<string, any>) => VNode;
}

/** 表单配置 */
export interface FormConfig {
  /** 表单项配置 */
  fields: FormField[];
  /** 标签宽度 */
  labelWidth?: string | number;
  /** 标签位置 */
  labelPosition?: "left" | "right" | "top";
  /** 是否行内表单 */
  inline?: boolean;
  /** 是否禁用 */
  disabled?: boolean;
  /** 栅格间距 */
  gutter?: number;
  /** 表单尺寸 */
  size?: "large" | "default" | "small";
}

/** ReForm 组件 Props */
export interface ReFormProps extends FormConfig {
  /** 表单数据（v-model） */
  modelValue?: Record<string, any>;
  /** 是否显示底部按钮 */
  showActions?: boolean;
  /** 提交按钮文本 */
  submitText?: string;
  /** 取消按钮文本 */
  cancelText?: string;
  /** 是否显示重置按钮 */
  showReset?: boolean;
  /** 重置按钮文本 */
  resetText?: string;
  /** 是否显示加载状态 */
  loading?: boolean;
}

/** ReForm 组件 Emits */
export interface ReFormEmits {
  /** 值更新 */
  (e: "update:modelValue", value: Record<string, any>): void;
  /** 提交事件 */
  (e: "submit", value: Record<string, any>): void;
  /** 取消事件 */
  (e: "cancel"): void;
  /** 重置事件 */
  (e: "reset"): void;
  /** 校验失败事件 */
  (e: "validate-error", errors: any): void;
}

/** 表单弹窗 Props */
export interface ReFormDialogProps extends ReFormProps {
  /** 弹窗标题 */
  title?: string;
  /** 弹窗宽度 */
  width?: string | number;
  /** 是否显示弹窗 */
  visible?: boolean;
  /** 是否全屏 */
  fullscreen?: boolean;
  /** 点击遮罩是否关闭 */
  closeOnClickModal?: boolean;
  /** 是否显示关闭按钮 */
  showClose?: boolean;
  /** 是否追加到 body */
  appendToBody?: boolean;
}
