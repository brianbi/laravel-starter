<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import type { ReTreeSelectProps, TreeNode, FlatToTreeOptions } from "./types";

defineOptions({
  name: "ReTreeSelect"
});

const props = withDefaults(defineProps<ReTreeSelectProps>(), {
  modelValue: undefined,
  data: () => [],
  multiple: false,
  clearable: true,
  filterable: true,
  placeholder: "请选择",
  disabled: false,
  emptyText: "暂无数据",
  defaultExpandAll: false,
  checkStrictly: false,
  showCheckbox: false,
  nodeKey: "id",
  renderAfterExpand: true,
  lazy: false,
  size: "default",
  checkOnClickNode: false
});

const emit = defineEmits<{
  "update:modelValue": [
    value: string | number | (string | number)[] | undefined
  ];
  change: [
    value: string | number | (string | number)[] | undefined,
    node: TreeNode | TreeNode[] | null
  ];
  "node-click": [node: TreeNode, data: any];
  clear: [];
  "visible-change": [visible: boolean];
}>();

// 树形选择器 ref
const treeSelectRef = ref();

// 内部值
const innerValue = computed({
  get: () => props.modelValue,
  set: val => emit("update:modelValue", val)
});

// 默认节点配置
const defaultProps = computed(() => ({
  label: props.props?.label ?? "label",
  children: props.props?.children ?? "children",
  disabled: props.props?.disabled ?? "disabled",
  isLeaf: props.props?.isLeaf ?? "isLeaf",
  value: props.props?.value ?? "id"
}));

// 处理选中变化
const handleChange = (value: any) => {
  innerValue.value = value;

  // 查找选中的节点
  let selectedNode: TreeNode | TreeNode[] | null = null;
  if (props.multiple && Array.isArray(value)) {
    selectedNode = value
      .map(v => findNode(props.data, v))
      .filter(Boolean) as TreeNode[];
  } else if (value !== undefined && value !== null) {
    selectedNode = findNode(props.data, value);
  }

  emit("change", value, selectedNode);
};

// 查找节点
const findNode = (
  nodes: TreeNode[],
  value: string | number
): TreeNode | null => {
  for (const node of nodes) {
    const nodeValue = node[defaultProps.value.value] ?? node.id;
    if (nodeValue === value) {
      return node;
    }
    if (node.children && node.children.length > 0) {
      const found = findNode(node.children, value);
      if (found) return found;
    }
  }
  return null;
};

// 处理节点点击
const handleNodeClick = (data: TreeNode, node: any) => {
  emit("node-click", data, node);
};

// 处理清空
const handleClear = () => {
  emit("clear");
};

// 处理可见性变化
const handleVisibleChange = (visible: boolean) => {
  emit("visible-change", visible);
};

// 过滤节点
const filterNode = (value: string, data: TreeNode): boolean => {
  if (!value) return true;
  const label = data[defaultProps.value.label] ?? data.label;
  return label?.toLowerCase().includes(value.toLowerCase());
};

// 暴露方法
defineExpose({
  treeSelectRef,
  /** 获取当前选中的节点 */
  getCheckedNodes: () => {
    if (!props.multiple) {
      return innerValue.value ? findNode(props.data, innerValue.value as any) : null;
    }
    return (innerValue.value as any[])?.map(v => findNode(props.data, v)).filter(Boolean) ?? [];
  },
  /** 设置选中的节点 */
  setChecked: (value: string | number | (string | number)[]) => {
    innerValue.value = value;
  },
  /** 清空选中 */
  clear: () => {
    innerValue.value = props.multiple ? [] : undefined;
  }
});
</script>

<template>
  <el-tree-select
    ref="treeSelectRef"
    v-model="innerValue"
    :data="data"
    :multiple="multiple"
    :clearable="clearable"
    :filterable="filterable"
    :placeholder="placeholder"
    :disabled="disabled"
    :empty-text="emptyText"
    :default-expand-all="defaultExpandAll"
    :default-expanded-keys="defaultExpandedKeys"
    :check-strictly="checkStrictly"
    :show-checkbox="showCheckbox || multiple"
    :node-key="nodeKey"
    :props="defaultProps"
    :render-after-expand="renderAfterExpand"
    :load="load"
    :lazy="lazy"
    :size="size"
    :check-on-click-node="checkOnClickNode"
    :filter-node-method="filterNode"
    class="w-full"
    @change="handleChange"
    @node-click="handleNodeClick"
    @clear="handleClear"
    @visible-change="handleVisibleChange"
  >
    <!-- 自定义节点内容 -->
    <template v-if="$slots.default" #default="{ node, data }">
      <slot :node="node" :data="data" />
    </template>

    <!-- 自定义前缀 -->
    <template v-if="$slots.prefix" #prefix>
      <slot name="prefix" />
    </template>

    <!-- 空状态 -->
    <template v-if="$slots.empty" #empty>
      <slot name="empty" />
    </template>
  </el-tree-select>
</template>
