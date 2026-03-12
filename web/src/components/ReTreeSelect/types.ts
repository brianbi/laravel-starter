/** 树节点数据 */
export interface TreeNode {
  /** 节点唯一标识 */
  id: string | number;
  /** 节点显示文本 */
  label: string;
  /** 节点值 */
  value?: string | number;
  /** 子节点 */
  children?: TreeNode[];
  /** 是否禁用 */
  disabled?: boolean;
  /** 是否为叶子节点 */
  isLeaf?: boolean;
  /** 父节点ID */
  parentId?: string | number | null;
  /** 附加数据 */
  [key: string]: any;
}

/** ReTreeSelect 组件 Props */
export interface ReTreeSelectProps {
  /** 绑定值（v-model） */
  modelValue?: string | number | (string | number)[];
  /** 树形数据 */
  data?: TreeNode[];
  /** 是否多选 */
  multiple?: boolean;
  /** 是否可清除 */
  clearable?: boolean;
  /** 是否可搜索 */
  filterable?: boolean;
  /** 占位符 */
  placeholder?: string;
  /** 是否禁用 */
  disabled?: boolean;
  /** 空数据提示文本 */
  emptyText?: string;
  /** 是否默认展开全部 */
  defaultExpandAll?: boolean;
  /** 默认展开的节点key数组 */
  defaultExpandedKeys?: (string | number)[];
  /** 是否只能选择叶子节点 */
  checkStrictly?: boolean;
  /** 是否显示复选框 */
  showCheckbox?: boolean;
  /** 节点key字段 */
  nodeKey?: string;
  /** 节点配置 */
  props?: {
    label?: string;
    children?: string;
    disabled?: string;
    isLeaf?: string;
    value?: string;
  };
  /** 是否开启虚拟滚动 */
  renderAfterExpand?: boolean;
  /** 懒加载函数 */
  load?: (node: TreeNode, resolve: (data: TreeNode[]) => void) => void;
  /** 是否懒加载 */
  lazy?: boolean;
  /** 尺寸 */
  size?: "large" | "default" | "small";
  /** 下拉框宽度 */
  popperWidth?: string | number;
  /** 是否可勾选父节点（多选时） */
  checkOnClickNode?: boolean;
}

/** ReTreeSelect 组件 Emits */
export interface ReTreeSelectEmits {
  /** 值变化 */
  (e: "update:modelValue", value: string | number | (string | number)[]): void;
  /** 选中变化 */
  (e: "change", value: string | number | (string | number)[], node: TreeNode | TreeNode[]): void;
  /** 节点点击 */
  (e: "node-click", node: TreeNode, data: any): void;
  /** 清空 */
  (e: "clear"): void;
  /** 可见性变化 */
  (e: "visible-change", visible: boolean): void;
}

/** 转换扁平数据为树形数据的选项 */
export interface FlatToTreeOptions {
  /** ID 字段名 */
  idField?: string;
  /** 父ID 字段名 */
  parentIdField?: string;
  /** 子节点字段名 */
  childrenField?: string;
  /** 根节点父ID值 */
  rootParentId?: string | number | null;
}
