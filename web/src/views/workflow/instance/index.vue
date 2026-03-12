<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { useTable } from "@/hooks/useTable";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import InstanceDetail from "./components/InstanceDetail.vue";
import StartWorkflow from "./components/StartWorkflow.vue";
import {
  getMyWorkflowInstances,
  withdrawWorkflowInstance
} from "@/api/workflow/instance";
import type {
  WorkflowInstance,
  WorkflowInstanceQuery
} from "@/api/workflow/types";
import type { PaginationProps } from "@pureadmin/table";

import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";
import ViewIcon from "~icons/ep/view";
import AddIcon from "~icons/ep/plus";
import CloseIcon from "~icons/ep/close";

defineOptions({
  name: "WorkflowInstance"
});

// 搜索参数
const searchForm = reactive<WorkflowInstanceQuery>({
  status: undefined
});

// 表格数据管理
const {
  data: tableData,
  loading,
  pagination,
  fetchData
} = useTable<WorkflowInstance, WorkflowInstanceQuery>({
  fetchApi: async params => {
    const res = await getMyWorkflowInstances(params);
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
  { label: "进行中", value: 1 },
  { label: "已完成", value: 2 },
  { label: "已拒绝", value: 3 },
  { label: "已撤回", value: 4 }
];

// 获取状态标签类型
const getStatusType = (status: number) => {
  const typeMap: Record<number, string> = {
    1: "primary",
    2: "success",
    3: "danger",
    4: "info"
  };
  return typeMap[status] || "info";
};

// 表格列配置
const columns: TableColumnList = [
  { label: "ID", prop: "id", width: 80 },
  { label: "流程名称", prop: "definition.name", minWidth: 150 },
  {
    label: "状态",
    prop: "status",
    width: 100,
    cellRenderer: ({ row }) => (
      <el-tag type={getStatusType(row.status)} size="small">
        {row.status_text}
      </el-tag>
    )
  },
  { label: "当前节点", prop: "current_node_id", minWidth: 120 },
  { label: "发起时间", prop: "started_at", minWidth: 160 },
  { label: "完成时间", prop: "completed_at", minWidth: 160 },
  {
    label: "操作",
    fixed: "right",
    width: 150,
    slot: "operation"
  }
];

// 详情弹窗
const detailVisible = ref(false);
const currentInstance = ref<WorkflowInstance | null>(null);

// 发起流程弹窗
const startVisible = ref(false);

// 搜索
const handleSearch = () => {
  fetchData();
  syncPagination();
};

// 重置
const handleReset = () => {
  searchForm.status = undefined;
  fetchData();
  syncPagination();
};

// 发起流程
const handleStart = () => {
  startVisible.value = true;
};

// 查看详情
const handleView = (row: WorkflowInstance) => {
  currentInstance.value = row;
  detailVisible.value = true;
};

// 撤回
const handleWithdraw = async (row: WorkflowInstance) => {
  try {
    await ElMessageBox.confirm("确认撤回此流程吗？撤回后将无法继续审批。", "提示", {
      type: "warning"
    });
    await withdrawWorkflowInstance(row.id);
    ElMessage.success("撤回成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消
  }
};

// 发起成功
const handleStartSuccess = () => {
  startVisible.value = false;
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
    <PureTableBar title="我发起的流程" :columns="columns" @refresh="handleSearch">
      <template #buttons>
        <el-button
          type="primary"
          :icon="useRenderIcon(AddIcon)"
          @click="handleStart"
        >
          发起流程
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
              type="primary"
              link
              :icon="useRenderIcon(ViewIcon)"
              @click="handleView(row)"
            >
              详情
            </el-button>
            <el-button
              v-if="row.status === 1"
              type="warning"
              link
              :icon="useRenderIcon(CloseIcon)"
              @click="handleWithdraw(row)"
            >
              撤回
            </el-button>
          </template>
        </pure-table>
      </template>
    </PureTableBar>

    <!-- 详情弹窗 -->
    <InstanceDetail
      v-model:visible="detailVisible"
      :instance="currentInstance"
    />

    <!-- 发起流程弹窗 -->
    <StartWorkflow
      v-model:visible="startVisible"
      @success="handleStartSuccess"
    />
  </div>
</template>

<style lang="scss" scoped>
.main {
  padding: 16px;
}
</style>
