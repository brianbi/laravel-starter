<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useRouter } from "vue-router";
import { ElMessage, ElMessageBox } from "element-plus";
import { useTable } from "@/hooks/useTable";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { hasAuth } from "@/router/utils";
import DefinitionForm from "./components/DefinitionForm.vue";
import {
  getWorkflowDefinitionList,
  deleteWorkflowDefinition,
  publishWorkflow
} from "@/api/workflow/definition";
import type {
  WorkflowDefinition,
  WorkflowDefinitionQuery
} from "@/api/workflow/types";
import type { PaginationProps } from "@pureadmin/table";

import AddIcon from "~icons/ep/plus";
import EditIcon from "~icons/ep/edit";
import DeleteIcon from "~icons/ep/delete";
import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";
import SetUpIcon from "~icons/ep/set-up";
import UploadIcon from "~icons/ep/upload";

defineOptions({
  name: "WorkflowDefinition"
});

const router = useRouter();

// 搜索参数
const searchForm = reactive<WorkflowDefinitionQuery>({
  keyword: "",
  status: undefined
});

// 表格数据管理
const {
  data: tableData,
  loading,
  pagination,
  fetchData
} = useTable<WorkflowDefinition, WorkflowDefinitionQuery>({
  fetchApi: async params => {
    const res = await getWorkflowDefinitionList(params);
    return {
      data: res.data,
      total: res.meta.total
    };
  },
  defaultParams: searchForm,
  immediate: true
});

// 分页配置
const paginationConfig = reactive<PaginationProps>({
  total: 0,
  pageSize: 10,
  currentPage: 1,
  background: true,
  pageSizes: [10, 20, 50, 100]
});

// 同步分页
const syncPagination = () => {
  paginationConfig.total = pagination.total.value;
  paginationConfig.pageSize = pagination.pageSize.value;
  paginationConfig.currentPage = pagination.currentPage.value;
};

// 状态选项
const statusOptions = [
  { label: "草稿", value: 0 },
  { label: "已发布", value: 1 },
  { label: "已停用", value: 2 }
];

// 获取状态标签类型
const getStatusType = (status: number) => {
  const typeMap: Record<number, string> = {
    0: "info",
    1: "success",
    2: "danger"
  };
  return typeMap[status] || "info";
};

// 获取状态文本
const getStatusText = (status: number) => {
  const textMap: Record<number, string> = {
    0: "草稿",
    1: "已发布",
    2: "已停用"
  };
  return textMap[status] || "未知";
};

// 表格列配置
const columns: TableColumnList = [
  { label: "ID", prop: "id", width: 80 },
  { label: "流程编码", prop: "code", minWidth: 120 },
  { label: "流程名称", prop: "name", minWidth: 150 },
  {
    label: "描述",
    prop: "description",
    minWidth: 200,
    showOverflowTooltip: true
  },
  {
    label: "版本",
    prop: "version",
    width: 80,
    cellRenderer: ({ row }) => <span>v{row.version}</span>
  },
  {
    label: "状态",
    prop: "status",
    width: 100,
    cellRenderer: ({ row }) => (
      <el-tag type={getStatusType(row.status)} size="small">
        {getStatusText(row.status)}
      </el-tag>
    )
  },
  { label: "创建人", prop: "creator.name", minWidth: 100 },
  { label: "更新时间", prop: "updated_at", minWidth: 160 },
  {
    label: "操作",
    fixed: "right",
    width: 280,
    slot: "operation"
  }
];

// 表单弹窗
const formVisible = ref(false);
const formTitle = ref("新增流程");
const currentRow = ref<WorkflowDefinition | null>(null);

// 搜索
const handleSearch = () => {
  fetchData();
  syncPagination();
};

// 重置
const handleReset = () => {
  searchForm.keyword = "";
  searchForm.status = undefined;
  fetchData();
  syncPagination();
};

// 新增
const handleAdd = () => {
  currentRow.value = null;
  formTitle.value = "新增流程";
  formVisible.value = true;
};

// 编辑基本信息
const handleEdit = (row: WorkflowDefinition) => {
  currentRow.value = row;
  formTitle.value = "编辑流程";
  formVisible.value = true;
};

// 设计流程
const handleDesign = (row: WorkflowDefinition) => {
  router.push({
    path: "/workflow/designer",
    query: { id: row.id }
  });
};

// 发布
const handlePublish = async (row: WorkflowDefinition) => {
  try {
    await ElMessageBox.confirm(
      `确认发布流程「${row.name}」吗？发布后将可用于发起审批。`,
      "提示",
      { type: "warning" }
    );
    await publishWorkflow(row.id);
    ElMessage.success("发布成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消
  }
};

// 删除
const handleDelete = async (row: WorkflowDefinition) => {
  try {
    await ElMessageBox.confirm(
      `确认删除流程「${row.name}」吗？删除后不可恢复。`,
      "提示",
      { type: "warning" }
    );
    await deleteWorkflowDefinition(row.id);
    ElMessage.success("删除成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消
  }
};

// 表单保存成功
const handleFormSuccess = () => {
  formVisible.value = false;
  fetchData();
  syncPagination();
};

// 分页变化
const onPageChange = (page: number) => {
  pagination.currentPage.value = page;
  fetchData();
  syncPagination();
};

const onSizeChange = (size: number) => {
  pagination.pageSize.value = size;
  pagination.currentPage.value = 1;
  fetchData();
  syncPagination();
};

onMounted(() => {
  syncPagination();
});
</script>

<template>
  <div class="main">
    <!-- 搜索区域 -->
    <el-card shadow="never" class="mb-4">
      <el-form :model="searchForm" inline>
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="编码/名称"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select
            v-model="searchForm.status"
            placeholder="请选择"
            clearable
            class="w-[120px]"
          >
            <el-option
              v-for="item in statusOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button
            type="primary"
            :icon="useRenderIcon(SearchIcon)"
            @click="handleSearch"
          >
            搜索
          </el-button>
          <el-button :icon="useRenderIcon(RefreshIcon)" @click="handleReset">
            重置
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格区域 -->
    <PureTableBar title="流程定义" :columns="columns" @refresh="handleSearch">
      <template #buttons>
        <el-button
          v-if="hasAuth('workflow:definition:add')"
          type="primary"
          :icon="useRenderIcon(AddIcon)"
          @click="handleAdd"
        >
          新增流程
        </el-button>
      </template>

      <template #default="{ size, dynamicColumns }">
        <pure-table
          adaptive
          :adaptiveConfig="{ offsetBottom: 96 }"
          row-key="id"
          align-whole="center"
          :size="size"
          :data="tableData"
          :columns="dynamicColumns"
          :loading="loading"
          :pagination="paginationConfig"
          @page-size-change="onSizeChange"
          @page-current-change="onPageChange"
        >
          <template #operation="{ row }">
            <el-button
              v-if="hasAuth('workflow:definition:edit')"
              type="primary"
              link
              :icon="useRenderIcon(EditIcon)"
              @click="handleEdit(row)"
            >
              编辑
            </el-button>
            <el-button
              v-if="hasAuth('workflow:definition:design')"
              type="primary"
              link
              :icon="useRenderIcon(SetUpIcon)"
              @click="handleDesign(row)"
            >
              设计
            </el-button>
            <el-button
              v-if="hasAuth('workflow:definition:publish') && row.status === 0"
              type="success"
              link
              :icon="useRenderIcon(UploadIcon)"
              @click="handlePublish(row)"
            >
              发布
            </el-button>
            <el-button
              v-if="hasAuth('workflow:definition:delete')"
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

    <!-- 表单弹窗 -->
    <DefinitionForm
      v-model:visible="formVisible"
      :title="formTitle"
      :row="currentRow"
      @success="handleFormSuccess"
    />
  </div>
</template>

<style lang="scss" scoped>
.main {
  padding: 16px;
}
</style>
