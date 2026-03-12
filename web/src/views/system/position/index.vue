<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { useTable } from "@/hooks/useTable";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { hasAuth } from "@/router/utils";
import PositionForm from "./components/PositionForm.vue";
import { getPositionList, deletePosition } from "@/api/system/position";
import type { Position, PositionQuery } from "@/api/system/types";
import type { PaginationProps } from "@pureadmin/table";

import AddIcon from "~icons/ep/plus";
import EditIcon from "~icons/ep/edit";
import DeleteIcon from "~icons/ep/delete";
import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";

defineOptions({ name: "SystemPosition" });

const searchForm = reactive<PositionQuery>({ name: "", code: "", status: undefined });

const {
  data: tableData,
  loading,
  pagination,
  fetchData,
  handlePaginationChange,
  selectedRows,
  handleSelectionChange
} = useTable<Position, PositionQuery>({
  fetchApi: async params => {
    const res = await getPositionList(params);
    return { data: res.data, total: res.meta.total };
  },
  defaultParams: searchForm,
  immediate: true
});

const paginationConfig = reactive<PaginationProps>({
  total: 0, pageSize: 10, currentPage: 1, background: true, pageSizes: [10, 20, 50, 100]
});

const syncPagination = () => {
  paginationConfig.total = pagination.total.value;
  paginationConfig.pageSize = pagination.pageSize.value;
  paginationConfig.currentPage = pagination.currentPage.value;
};

const columns: TableColumnList = [
  { type: "selection", width: 55, fixed: "left" },
  { label: "岗位名称", prop: "name", minWidth: 150 },
  { label: "岗位编码", prop: "code", minWidth: 120 },
  { label: "排序", prop: "sort", width: 80 },
  {
    label: "状态", prop: "status", width: 80,
    cellRenderer: ({ row }) => (
      <el-tag type={row.status === 1 ? "success" : "danger"} size="small">
        {row.status === 1 ? "启用" : "停用"}
      </el-tag>
    )
  },
  { label: "备注", prop: "remark", minWidth: 200 },
  { label: "创建时间", prop: "created_at", minWidth: 160 },
  { label: "操作", fixed: "right", width: 160, slot: "operation" }
];

const formVisible = ref(false);
const formTitle = ref("新增岗位");
const currentRow = ref<Position | null>(null);

const handleSearch = () => { fetchData(); syncPagination(); };
const handleReset = () => {
  searchForm.name = ""; searchForm.code = ""; searchForm.status = undefined;
  fetchData(); syncPagination();
};

const handleAdd = () => { currentRow.value = null; formTitle.value = "新增岗位"; formVisible.value = true; };
const handleEdit = (row: Position) => { currentRow.value = row; formTitle.value = "编辑岗位"; formVisible.value = true; };

const handleDelete = async (row: Position) => {
  try {
    await ElMessageBox.confirm(`确认删除岗位「${row.name}」吗？`, "提示", { type: "warning" });
    await deletePosition(row.id);
    ElMessage.success("删除成功");
    fetchData(); syncPagination();
  } catch {}
};

const handleFormSuccess = () => { formVisible.value = false; fetchData(); syncPagination(); };
const onPageChange = (page: number) => { handlePaginationChange({ page, pageSize: paginationConfig.pageSize }); syncPagination(); };
const onSizeChange = (size: number) => { handlePaginationChange({ page: 1, pageSize: size }); syncPagination(); };

onMounted(syncPagination);
</script>

<template>
  <div class="main">
    <el-card shadow="never" class="mb-4">
      <el-form :model="searchForm" inline>
        <el-form-item label="岗位名称">
          <el-input v-model="searchForm.name" placeholder="请输入" clearable @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item label="岗位编码">
          <el-input v-model="searchForm.code" placeholder="请输入" clearable @keyup.enter="handleSearch" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable class="w-[120px]">
            <el-option label="启用" :value="1" /><el-option label="停用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="useRenderIcon(SearchIcon)" @click="handleSearch">搜索</el-button>
          <el-button :icon="useRenderIcon(RefreshIcon)" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <PureTableBar title="岗位列表" :columns="columns" @refresh="handleSearch">
      <template #buttons>
        <el-button v-if="hasAuth('system:position:add')" type="primary" :icon="useRenderIcon(AddIcon)" @click="handleAdd">
          新增岗位
        </el-button>
      </template>
      <template #default="{ size, dynamicColumns }">
        <pure-table
          adaptive :adaptiveConfig="{ offsetBottom: 96 }" row-key="id" align-whole="center"
          :size="size" :data="tableData" :columns="dynamicColumns" :loading="loading"
          :pagination="paginationConfig" @selection-change="handleSelectionChange"
          @page-size-change="onSizeChange" @page-current-change="onPageChange"
        >
          <template #operation="{ row }">
            <el-button v-if="hasAuth('system:position:edit')" type="primary" link :icon="useRenderIcon(EditIcon)" @click="handleEdit(row)">编辑</el-button>
            <el-button v-if="hasAuth('system:position:delete')" type="danger" link :icon="useRenderIcon(DeleteIcon)" @click="handleDelete(row)">删除</el-button>
          </template>
        </pure-table>
      </template>
    </PureTableBar>

    <PositionForm v-model:visible="formVisible" :title="formTitle" :row="currentRow" @success="handleFormSuccess" />
  </div>
</template>

<style lang="scss" scoped>.main { padding: 16px; }</style>
