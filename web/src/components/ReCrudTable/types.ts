import type { VNode } from "vue";

/** 表格列配置 */
export interface TableColumn {
  /** 列标识 */
  prop?: string;
  /** 列标题 */
  label: string;
  /** 列宽度 */
  width?: number | string;
  /** 最小列宽 */
  minWidth?: number | string;
  /** 对齐方式 */
  align?: "left" | "center" | "right";
  /** 是否固定列 */
  fixed?: boolean | "left" | "right";
  /** 是否可排序 */
  sortable?: boolean | "custom";
  /** 是否隐藏 */
  hide?: boolean | (() => boolean);
  /** 插槽名称 */
  slot?: string;
  /** 格式化函数 */
  formatter?: (row: any, column: any, cellValue: any, index: number) => any;
  /** 自定义渲染 */
  cellRenderer?: (data: { row: any; column: any; $index: number }) => VNode;
  /** 子列（多级表头） */
  children?: TableColumn[];
}

/** 分页配置 */
export interface Pagination {
  /** 当前页 */
  currentPage: number;
  /** 每页条数 */
  pageSize: number;
  /** 总条数 */
  total: number;
  /** 每页条数选项 */
  pageSizes?: number[];
  /** 分页布局 */
  layout?: string;
  /** 是否显示背景色 */
  background?: boolean;
}

/** 搜索表单项 */
export interface SearchField {
  /** 字段名 */
  prop: string;
  /** 标签 */
  label: string;
  /** 组件类型 */
  type: "input" | "select" | "date" | "daterange" | "cascader" | "tree-select";
  /** 占位符 */
  placeholder?: string;
  /** 选项（type为select时使用） */
  options?: Array<{ label: string; value: any }>;
  /** 默认值 */
  defaultValue?: any;
  /** 组件属性 */
  props?: Record<string, any>;
}

/** 工具栏按钮 */
export interface ToolbarButton {
  /** 按钮文本 */
  label: string;
  /** 按钮类型 */
  type?: "primary" | "success" | "warning" | "danger" | "info";
  /** 图标 */
  icon?: string | object;
  /** 权限标识 */
  auth?: string | string[];
  /** 是否禁用 */
  disabled?: boolean | (() => boolean);
  /** 点击事件 */
  onClick?: () => void;
}

/** CRUD 表格组件 Props */
export interface CrudTableProps {
  /** 表格标题 */
  title?: string;
  /** 列配置 */
  columns: TableColumn[];
  /** 表格数据 */
  data?: any[];
  /** 是否显示加载状态 */
  loading?: boolean;
  /** 是否显示分页 */
  showPagination?: boolean;
  /** 分页配置 */
  pagination?: Pagination;
  /** 行唯一标识字段 */
  rowKey?: string;
  /** 是否显示选择列 */
  showSelection?: boolean;
  /** 是否显示序号列 */
  showIndex?: boolean;
  /** 搜索表单字段 */
  searchFields?: SearchField[];
  /** 工具栏按钮 */
  toolbarButtons?: ToolbarButton[];
  /** 是否显示工具栏 */
  showToolbar?: boolean;
  /** 表格高度 */
  height?: number | string;
  /** 表格最大高度 */
  maxHeight?: number | string;
  /** 是否开启斑马纹 */
  stripe?: boolean;
  /** 是否显示边框 */
  border?: boolean;
  /** 空数据提示文本 */
  emptyText?: string;
}

/** CRUD 表格组件 Emits */
export interface CrudTableEmits {
  /** 刷新事件 */
  (e: "refresh"): void;
  /** 搜索事件 */
  (e: "search", params: Record<string, any>): void;
  /** 重置搜索事件 */
  (e: "reset"): void;
  /** 分页变化事件 */
  (e: "pagination-change", pagination: { page: number; pageSize: number }): void;
  /** 选择变化事件 */
  (e: "selection-change", selection: any[]): void;
  /** 排序变化事件 */
  (e: "sort-change", sort: { prop: string; order: string }): void;
  /** 行点击事件 */
  (e: "row-click", row: any): void;
}
