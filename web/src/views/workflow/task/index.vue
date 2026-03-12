<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { useTable } from "@/hooks/useTable";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import TaskDetail from "./components/TaskDetail.vue";
import {
  getMyPendingTasks,
  getMyCompletedTasks,
  approveTask,
  rejectTask
} from "@/api/workflow/task";
import type { WorkflowTask, WorkflowTaskQuery } from "@/api/workflow/types";
import type { PaginationProps } from "@pureadmin/table";

import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";
import ViewIcon from "~icons/ep/view";
import CheckIcon from "~icons/ep/check";
import CloseIcon from "~icons/ep/close";

defineOptions({
  name: "WorkflowTask"
});

// 当前标签页
const activeTab = ref("pending");

// 搜索参数
const searchForm = reactive<WorkflowTaskQuery>({});

// 表格数据管理
const {
  data: tableData,
  loading,
  pagination,
  fetchData
} = useTable<WorkflowTask, WorkflowTaskQuery>({
  fetchApi: async params => {
    const api =
      activeTab.value === "pending" ? getMyPendingTasks : getMyCompletedTasks;
    const res = await api(params);
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

// 获取状态标签类型
const getStatusType = (status: number) => {
  const typeMap: Record<number, string> = {
    0: "info",
    1: "success",
    2: "danger"
  };
  return typeMap[status] || "info";
};

// 表格列配置
const columns: TableColumnList = [
  { label: "ID", prop: "id", width: 80 },
  { label: "流程名称", prop: "instance.definition.name", minWidth: 150 },
  { label: "节点名称", prop: "node_name", minWidth: 120 },
  { label: "发起人", prop: "instance.initiator.name", minWidth: 100 },
  {
    label: "任务状态",
    prop: "status",
    width: 100,
    cellRenderer: ({ row }) => (
      <el-tag type={getStatusType(row.status)} size="small">
        {row.status_text}
      </el-tag>
    )
  },
  { label: "创建时间", prop: "created_at", minWidth: 160 },
  { label: "完成时间", prop: "completed_at", minWidth: 160 },
  {
    label: "操作",
    fixed: "right",
    width: 200,
    slot: "operation"
  }
];

// 详情弹窗
const detailVisible = ref(false);
const currentTask = ref<WorkflowTask | null>(null);

// 切换标签
const handleTabChange = () => {
  pagination.currentPage.value = 1;
  fetchData();
  syncPagination();
};

// 搜索
const handleSearch = () => {
  fetchData();
  syncPagination();
};

// 查看详情
const handleView = (row: WorkflowTask) => {
  currentTask.value = row;
  detailVisible.value = true;
};

// 快捷通过
const handleQuickApprove = async (row: WorkflowTask) => {
  try {
    await ElMessageBox.confirm("确认通过此审批吗？", "提示", {
      type: "warning"
    });
    await approveTask(row.id);
    ElMessage.success("审批通过");
    fetchData();
    syncPagination();
  } catch {
    // 取消
  }
};

// 快捷拒绝
const handleQuickReject = async (row: WorkflowTask) => {
  try {
    const { value } = await ElMessageBox.prompt("请输入拒绝原因", "拒绝审批", {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      inputType: "textarea",
      inputPlaceholder: "请输入拒绝原因"
    });
    await rejectTask(row.id, { comment: value });
    ElMessage.success("已拒绝");
    fetchData();
    syncPagination();
  } catch {
    // 取消
  }
};

// 审批成功回调
const handleApprovalSuccess = () => {
  detailVisible.value = false;
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
    <!-- 标签页 -->
    <el-tabs v-model="activeTab" class="mb-4" @tab-change="handleTabChange">
      <el-tab-pane label="待办任务" name="pending" />
      <el-tab-pane label="已办任务" name="done" />
    </el-tabs>

    <!-- 表格区域 -->
    <PureTableBar
      :title="activeTab === 'pending' ? '待办任务' : '已办任务'"
      :columns="columns"
      @refresh="handleSearch"
    >
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
            <template v-if="activeTab === 'pending'">
              <el-button
                type="success"
                link
                :icon="useRenderIcon(CheckIcon)"
                @click="handleQuickApprove(row)"
              >
                通过
              </el-button>
              <el-button
                type="danger"
                link
                :icon="useRenderIcon(CloseIcon)"
                @click="handleQuickReject(row)"
              >
                拒绝
              </el-button>
            </template>
          </template>
        </pure-table>
      </template>
    </PureTableBar>

    <!-- 详情弹窗 -->
    <TaskDetail
      v-model:visible="detailVisible"
      :task="currentTask"
      :readonly="activeTab === 'done'"
      @success="handleApprovalSuccess"
    />
  </div>
</template>

<style lang="scss" scoped>
.main {
  padding: 16px;
}
</style>
