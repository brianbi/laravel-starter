<script setup lang="ts">
import { ref, computed, nextTick } from "vue";
import { ElMessage } from "element-plus";
import type { ElTree } from "element-plus";
import { getRole, assignMenus } from "@/api/system/role";
import { getMenuTree } from "@/api/system/menu";
import type { Menu } from "@/api/system/types";

const props = defineProps<{
  visible: boolean;
  roleId: number;
}>();

const emit = defineEmits<{
  "update:visible": [value: boolean];
  success: [];
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

const treeRef = ref<InstanceType<typeof ElTree>>();
const loading = ref(false);
const submitLoading = ref(false);
const menuTree = ref<Menu[]>([]);
const checkedKeys = ref<number[]>([]);
const expandAll = ref(true);
const checkStrictly = ref(false);

const treeProps = {
  label: "name",
  children: "children"
};

// 获取所有叶子节点ID
const getLeafKeys = (nodes: Menu[]): number[] => {
  const keys: number[] = [];
  const traverse = (items: Menu[]) => {
    items.forEach(item => {
      if (!item.children || item.children.length === 0) {
        keys.push(item.id);
      } else {
        traverse(item.children);
      }
    });
  };
  traverse(nodes);
  return keys;
};

// 加载菜单树
const loadMenuTree = async () => {
  try {
    const res = await getMenuTree();
    menuTree.value = res.data;
  } catch {
    menuTree.value = [];
  }
};

// 加载角色已有菜单
const loadRoleMenus = async () => {
  if (!props.roleId) return;
  loading.value = true;
  try {
    const res = await getRole(props.roleId);
    // 获取角色的菜单IDs（后端返回的是完整菜单，需要提取叶子节点）
    const menuIds = res.data.permissions || [];
    // 过滤出叶子节点（避免父节点被选中导致所有子节点都被选中）
    const leafKeys = getLeafKeys(menuTree.value);
    checkedKeys.value = menuIds.filter((id: number) => leafKeys.includes(id));
  } catch {
    checkedKeys.value = [];
  } finally {
    loading.value = false;
  }
};

// 打开弹窗
const handleOpen = async () => {
  await loadMenuTree();
  await loadRoleMenus();
  nextTick(() => {
    treeRef.value?.setCheckedKeys(checkedKeys.value);
  });
};

// 全选/全不选
const handleCheckAll = (checked: boolean) => {
  if (checked) {
    const allKeys: number[] = [];
    const traverse = (items: Menu[]) => {
      items.forEach(item => {
        allKeys.push(item.id);
        if (item.children) traverse(item.children);
      });
    };
    traverse(menuTree.value);
    treeRef.value?.setCheckedKeys(allKeys);
  } else {
    treeRef.value?.setCheckedKeys([]);
  }
};

// 展开/折叠
const handleExpandAll = (expand: boolean) => {
  const nodes = treeRef.value?.store?.nodesMap;
  if (nodes) {
    Object.values(nodes).forEach((node: any) => {
      node.expanded = expand;
    });
  }
};

// 提交
const handleSubmit = async () => {
  submitLoading.value = true;
  try {
    // 获取选中的节点（包括半选中的父节点）
    const checkedNodes = treeRef.value?.getCheckedKeys() as number[];
    const halfCheckedNodes = treeRef.value?.getHalfCheckedKeys() as number[];
    const allMenuIds = [...checkedNodes, ...halfCheckedNodes];

    await assignMenus(props.roleId, allMenuIds);
    ElMessage.success("菜单分配成功");
    emit("success");
    dialogVisible.value = false;
  } catch (error: any) {
    ElMessage.error(error?.message || "操作失败");
  } finally {
    submitLoading.value = false;
  }
};
</script>

<template>
  <el-dialog
    v-model="dialogVisible"
    title="分配菜单权限"
    width="500px"
    :close-on-click-modal="false"
    destroy-on-close
    @open="handleOpen"
  >
    <div class="mb-4 flex gap-4">
      <el-checkbox v-model="expandAll" @change="handleExpandAll">展开/折叠</el-checkbox>
      <el-checkbox @change="handleCheckAll">全选/全不选</el-checkbox>
      <el-checkbox v-model="checkStrictly">父子联动</el-checkbox>
    </div>

    <el-scrollbar max-height="400px" v-loading="loading">
      <el-tree
        ref="treeRef"
        :data="menuTree"
        :props="treeProps"
        node-key="id"
        show-checkbox
        :default-expand-all="expandAll"
        :check-strictly="!checkStrictly"
        :default-checked-keys="checkedKeys"
      >
        <template #default="{ node, data }">
          <span class="flex items-center">
            <el-icon v-if="data.icon" class="mr-1">
              <component :is="data.icon" />
            </el-icon>
            <span>{{ node.label }}</span>
            <el-tag v-if="data.type === 'button'" size="small" class="ml-2">按钮</el-tag>
          </span>
        </template>
      </el-tree>
    </el-scrollbar>

    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>
