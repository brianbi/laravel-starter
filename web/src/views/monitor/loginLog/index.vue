<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessage } from "element-plus";
import { useTable } from "@/hooks/useTable";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { hasAuth } from "@/router/utils";
import { getLoginLogList, clearLoginLogs } from "@/api/monitor/loginLog";
import type { LoginLog, LoginLogQuery } from "@/api/monitor/types";
import type { PaginationProps } from "@pureadmin/table";

import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";
import DeleteIcon from "~icons/ep/delete";

defineOptions({
  name: "MonitorLoginLog"
});

// 表格 ref
const tableRef = ref();

// 搜索参数
const searchForm = reactive<LoginLogQuery>({
  username: "",
  ip: "",
  status: undefined,
  start_time: "",
  end_time: ""
});

// 时间范围
const dateRange = ref<[string, string] | null>(null);

// 表格数据管理
const {
  data: tableData,
  loading,
  pagination,
  fetchData
} = useTable<LoginLog, LoginLogQuery>({
  fetchApi: async params => {
    const res = await getLoginLogList(params);
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
  { label: "成功", value: 1 },
  { label: "失败", value: 0 }
];

// 获取状态标签类型
const getStatusType = (status: number) => {
  return status === 1 ? "success" : "danger";
};

// 获取状态文本
const getStatusText = (status: number) => {
  return status === 1 ? "成功" : "失败";
};

// 表格列配置
const columns: TableColumnList = [
  { label: "ID", prop: "id", width: 80 },
  { label: "登录用户", prop: "username", minWidth: 120 },
  { label: "IP地址", prop: "ip", minWidth: 130 },
  { label: "IP归属地", prop: "ip_location", minWidth: 120 },
  { label: "操作系统", prop: "os", minWidth: 120 },
  { label: "浏览器", prop: "browser", minWidth: 130 },
  {
    label: "登录状态",
    prop: "status",
    width: 100,
    cellRenderer: ({ row }) => (
      <el-tag type={getStatusType(row.status)} size="small">
        {getStatusText(row.status)}
      </el-tag>
    )
  },
  {
    label: "提示信息",
    prop: "message",
    minWidth: 150,
    showOverflowTooltip: true
  },
  { label: "登录时间", prop: "login_at", minWidth: 160 }
];

// 清理弹窗
const clearVisible = ref(false);
const clearDays = ref(30);
const clearLoading = ref(false);

// 搜索
const handleSearch = () => {
  // 处理时间范围
  if (dateRange.value && dateRange.value.length === 2) {
    searchForm.start_time = dateRange.value[0];
    searchForm.end_time = dateRange.value[1];
  } else {
    searchForm.start_time = "";
    searchForm.end_time = "";
  }
  fetchData();
  syncPagination();
};

// 重置
const handleReset = () => {
  searchForm.username = "";
  searchForm.ip = "";
  searchForm.status = undefined;
  searchForm.start_time = "";
  searchForm.end_time = "";
  dateRange.value = null;
  fetchData();
  syncPagination();
};

// 打开清理弹窗
const handleOpenClear = () => {
  clearDays.value = 30;
  clearVisible.value = true;
};

// 确认清理
const handleClear = async () => {
  try {
    clearLoading.value = true;
    const res = await clearLoginLogs(clearDays.value);
    ElMessage.success(`成功清理 ${res.deleted} 条登录日志`);
    clearVisible.value = false;
    fetchData();
    syncPagination();
  } catch {
    // 错误处理
  } finally {
    clearLoading.value = false;
  }
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
        <el-form-item label="登录用户">
          <el-input
            v-model="searchForm.username"
            placeholder="请输入用户名"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="IP地址">
          <el-input
            v-model="searchForm.ip"
            placeholder="请输入IP地址"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="登录状态">
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
        <el-form-item label="登录时间">
          <el-date-picker
            v-model="dateRange"
            type="datetimerange"
            range-separator="至"
            start-placeholder="开始时间"
            end-placeholder="结束时间"
            value-format="YYYY-MM-DD HH:mm:ss"
            class="!w-[360px]"
          />
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
    <PureTableBar title="登录日志" :columns="columns" @refresh="handleSearch">
      <template #buttons>
        <el-button
          v-if="hasAuth('monitor:login-log:clear')"
          type="danger"
          :icon="useRenderIcon(DeleteIcon)"
          @click="handleOpenClear"
        >
          清理日志
        </el-button>
      </template>

      <template #default="{ size, dynamicColumns }">
        <pure-table
          ref="tableRef"
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
        />
      </template>
    </PureTableBar>

    <!-- 清理弹窗 -->
    <el-dialog v-model="clearVisible" title="清理登录日志" width="400px">
      <el-form label-width="100px">
        <el-form-item label="保留天数">
          <el-input-number
            v-model="clearDays"
            :min="1"
            :max="365"
            controls-position="right"
          />
          <span class="ml-2 text-gray-500">天</span>
        </el-form-item>
        <el-form-item>
          <el-alert
            type="warning"
            :closable="false"
            show-icon
            title="警告"
            description="此操作将永久删除指定天数之前的日志数据，不可恢复！"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="clearVisible = false">取消</el-button>
        <el-button type="danger" :loading="clearLoading" @click="handleClear">
          确认清理
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style lang="scss" scoped>
.main {
  padding: 16px;
}
</style>
