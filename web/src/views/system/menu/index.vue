<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { hasAuth } from "@/router/utils";
import MenuForm from "./components/MenuForm.vue";
import { getMenuTree, deleteMenu } from "@/api/system/menu";
import type { Menu } from "@/api/system/types";

import AddIcon from "~icons/ep/plus";
import EditIcon from "~icons/ep/edit";
import DeleteIcon from "~icons/ep/delete";
import RefreshIcon from "~icons/ep/refresh";
import FolderIcon from "~icons/ep/folder";
import DocumentIcon from "~icons/ep/document";
import OperationIcon from "~icons/ep/operation";

defineOptions({
  name: "SystemMenu"
});

const tableRef = ref();
const loading = ref(false);
const tableData = ref<Menu[]>([]);

const columns: TableColumnList = [
  { label: "菜单名称", prop: "name", minWidth: 200, align: "left" },
  {
    label: "图标",
    prop: "icon",
    width: 80,
    cellRenderer: ({ row }) => (
      <el-icon size={18}>
        <component is={row.icon || "Menu"} />
      </el-icon>
    )
  },
  {
    label: "类型",
    prop: "type",
    width: 80,
    cellRenderer: ({ row }) => {
      const typeMap: Record<string, { label: string; type: string; icon: any }> = {
        directory: { label: "目录", type: "", icon: FolderIcon },
        menu: { label: "菜单", type: "success", icon: DocumentIcon },
        button: { label: "按钮", type: "warning", icon: OperationIcon }
      };
      const item = typeMap[row.type] || { label: row.type, type: "info", icon: null };
      return (
        <el-tag type={item.type as any} size="small">
          {item.label}
        </el-tag>
      );
    }
  },
  { label: "路由路径", prop: "route", minWidth: 150 },
  { label: "组件路径", prop: "component", minWidth: 180 },
  { label: "权限标识", prop: "permission", minWidth: 150 },
  { label: "排序", prop: "sort", width: 80 },
  {
    label: "状态",
    prop: "status",
    width: 80,
    cellRenderer: ({ row }) => (
      <el-tag type={row.status === 1 ? "success" : "danger"} size="small">
        {row.status === 1 ? "显示" : "隐藏"}
      </el-tag>
    )
  },
  { label: "操作", fixed: "right", width: 200, slot: "operation" }
];

const formVisible = ref(false);
const formTitle = ref("新增菜单");
const currentRow = ref<Menu | null>(null);
const parentId = ref<number>(0);

const fetchData = async () => {
  loading.value = true;
  try {
    const res = await getMenuTree();
    tableData.value = res.data;
  } catch {
    tableData.value = [];
  } finally {
    loading.value = false;
  }
};

const handleAdd = (row?: Menu) => {
  currentRow.value = null;
  parentId.value = row?.id || 0;
  formTitle.value = row ? `新增子菜单 - ${row.name}` : "新增菜单";
  formVisible.value = true;
};

const handleEdit = (row: Menu) => {
  currentRow.value = row;
  parentId.value = row.parent_id;
  formTitle.value = "编辑菜单";
  formVisible.value = true;
};

const handleDelete = async (row: Menu) => {
  try {
    await ElMessageBox.confirm(`确认删除菜单「${row.name}」吗？`, "提示", {
      type: "warning"
    });
    await deleteMenu(row.id);
    ElMessage.success("删除成功");
    fetchData();
  } catch {}
};

const handleFormSuccess = () => {
  formVisible.value = false;
  fetchData();
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="main">
    <PureTableBar title="菜单管理" :columns="columns" @refresh="fetchData">
      <template #buttons>
        <el-button
          v-if="hasAuth('system:menu:add')"
          type="primary"
          :icon="useRenderIcon(AddIcon)"
          @click="handleAdd()"
        >
          新增菜单
        </el-button>
      </template>

      <template #default="{ size, dynamicColumns }">
        <pure-table
          ref="tableRef"
          adaptive
          :adaptiveConfig="{ offsetBottom: 96 }"
          row-key="id"
          :size="size"
          :data="tableData"
          :columns="dynamicColumns"
          :loading="loading"
          :tree-props="{ children: 'children' }"
          default-expand-all
        >
          <template #operation="{ row }">
            <el-button
              v-if="hasAuth('system:menu:add') && row.type !== 'button'"
              type="primary"
              link
              :icon="useRenderIcon(AddIcon)"
              @click="handleAdd(row)"
            >
              新增
            </el-button>
            <el-button
              v-if="hasAuth('system:menu:edit')"
              type="primary"
              link
              :icon="useRenderIcon(EditIcon)"
              @click="handleEdit(row)"
            >
              编辑
            </el-button>
            <el-button
              v-if="hasAuth('system:menu:delete')"
              type="danger"
              link
              :icon="useRenderIcon(DeleteIcon)"
              @click="handleDelete(row)"
            >
              删除
            </el-button>
          </template>
        </pure-table>
      </template>
    </PureTableBar>

    <MenuForm
      v-model:visible="formVisible"
      :title="formTitle"
      :row="currentRow"
      :parent-id="parentId"
      :menu-tree="tableData"
      @success="handleFormSuccess"
    />
  </div>
</template>

<style lang="scss" scoped>
.main {
  padding: 16px;
}
</style>
