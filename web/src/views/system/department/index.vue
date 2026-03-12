<script setup lang="ts">
import { ref, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { hasAuth } from "@/router/utils";
import DepartmentForm from "./components/DepartmentForm.vue";
import { getDepartmentTree, deleteDepartment } from "@/api/system/department";
import type { Department } from "@/api/system/types";

import AddIcon from "~icons/ep/plus";
import EditIcon from "~icons/ep/edit";
import DeleteIcon from "~icons/ep/delete";

defineOptions({
  name: "SystemDepartment"
});

const loading = ref(false);
const tableData = ref<Department[]>([]);

const columns: TableColumnList = [
  { label: "部门名称", prop: "name", minWidth: 200, align: "left" },
  { label: "负责人", prop: "leader", minWidth: 100 },
  { label: "联系电话", prop: "phone", minWidth: 120 },
  { label: "邮箱", prop: "email", minWidth: 150 },
  { label: "排序", prop: "sort", width: 80 },
  {
    label: "状态",
    prop: "status",
    width: 80,
    cellRenderer: ({ row }) => (
      <el-tag type={row.status === 1 ? "success" : "danger"} size="small">
        {row.status === 1 ? "启用" : "停用"}
      </el-tag>
    )
  },
  { label: "创建时间", prop: "created_at", minWidth: 160 },
  { label: "操作", fixed: "right", width: 200, slot: "operation" }
];

const formVisible = ref(false);
const formTitle = ref("新增部门");
const currentRow = ref<Department | null>(null);
const parentId = ref<number>(0);

const fetchData = async () => {
  loading.value = true;
  try {
    const res = await getDepartmentTree();
    tableData.value = res.data;
  } catch {
    tableData.value = [];
  } finally {
    loading.value = false;
  }
};

const handleAdd = (row?: Department) => {
  currentRow.value = null;
  parentId.value = row?.id || 0;
  formTitle.value = row ? `新增子部门 - ${row.name}` : "新增部门";
  formVisible.value = true;
};

const handleEdit = (row: Department) => {
  currentRow.value = row;
  parentId.value = row.parent_id;
  formTitle.value = "编辑部门";
  formVisible.value = true;
};

const handleDelete = async (row: Department) => {
  try {
    await ElMessageBox.confirm(`确认删除部门「${row.name}」吗？`, "提示", { type: "warning" });
    await deleteDepartment(row.id);
    ElMessage.success("删除成功");
    fetchData();
  } catch {}
};

const handleFormSuccess = () => {
  formVisible.value = false;
  fetchData();
};

onMounted(fetchData);
</script>

<template>
  <div class="main">
    <PureTableBar title="部门管理" :columns="columns" @refresh="fetchData">
      <template #buttons>
        <el-button
          v-if="hasAuth('system:department:add')"
          type="primary"
          :icon="useRenderIcon(AddIcon)"
          @click="handleAdd()"
        >
          新增部门
        </el-button>
      </template>

      <template #default="{ size, dynamicColumns }">
        <pure-table
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
              v-if="hasAuth('system:department:add')"
              type="primary"
              link
              :icon="useRenderIcon(AddIcon)"
              @click="handleAdd(row)"
            >
              新增
            </el-button>
            <el-button
              v-if="hasAuth('system:department:edit')"
              type="primary"
              link
              :icon="useRenderIcon(EditIcon)"
              @click="handleEdit(row)"
            >
              编辑
            </el-button>
            <el-button
              v-if="hasAuth('system:department:delete')"
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

    <DepartmentForm
      v-model:visible="formVisible"
      :title="formTitle"
      :row="currentRow"
      :parent-id="parentId"
      :department-tree="tableData"
      @success="handleFormSuccess"
    />
  </div>
</template>

<style lang="scss" scoped>
.main { padding: 16px; }
</style>
