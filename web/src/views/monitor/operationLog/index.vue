<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { useTable } from "@/hooks/useTable";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { hasAuth } from "@/router/utils";
import {
  getOperationLogList,
  clearOperationLogs
} from "@/api/monitor/operationLog";
import type { OperationLog, OperationLogQuery } from "@/api/monitor/types";
import type { PaginationProps } from "@pureadmin/table";

import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";
import DeleteIcon from "~icons/ep/delete";
import ViewIcon from "~icons/ep/view";

defineOptions({
  name: "MonitorOperationLog"
});

// 表格 ref
const tableRef = ref();

// 搜索参数
const searchForm = reactive<OperationLogQuery>({
  username: "",
  method: undefined,
  service_name: "",
  ip: "",
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
} = useTable<OperationLog, OperationLogQuery>({
  fetchApi: async params => {
    const res = await getOperationLogList(params);
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

// 请求方法选项
const methodOptions = [
  { label: "GET", value: "GET" },
  { label: "POST", value: "POST" },
  { label: "PUT", value: "PUT" },
  { label: "DELETE", value: "DELETE" },
  { label: "PATCH", value: "PATCH" }
];

// 获取方法标签类型
const getMethodType = (method: string) => {
  const typeMap: Record<string, string> = {
    GET: "info",
    POST: "success",
    PUT: "warning",
    DELETE: "danger",
    PATCH: "warning"
  };
  return typeMap[method] || "info";
};

// 获取响应码标签类型
const getResponseCodeType = (code: number) => {
  if (code >= 200 && code < 300) return "success";
  if (code >= 400 && code < 500) return "warning";
  if (code >= 500) return "danger";
  return "info";
};

// 表格列配置
const columns: TableColumnList = [
  { label: "ID", prop: "id", width: 80 },
  { label: "操作用户", prop: "username", minWidth: 100 },
  {
    label: "请求方法",
    prop: "method",
    width: 100,
    cellRenderer: ({ row }) => (
      <el-tag type={getMethodType(row.method)} size="small">
        {row.method}
      </el-tag>
    )
  },
  { label: "业务名称", prop: "service_name", minWidth: 120 },
  {
    label: "请求路径",
    prop: "router",
    minWidth: 200,
    showOverflowTooltip: true
  },
  { label: "IP地址", prop: "ip", minWidth: 130 },
  { label: "IP归属地", prop: "ip_location", minWidth: 100 },
  {
    label: "响应码",
    prop: "response_code",
    width: 90,
    cellRenderer: ({ row }) => (
      <el-tag type={getResponseCodeType(row.response_code)} size="small">
        {row.response_code}
      </el-tag>
    )
  },
  {
    label: "耗时(ms)",
    prop: "execution_time",
    width: 90,
    cellRenderer: ({ row }) => (
      <span class={row.execution_time > 1000 ? "text-red-500" : ""}>
        {row.execution_time}
      </span>
    )
  },
  { label: "操作时间", prop: "created_at", minWidth: 160 },
  {
    label: "操作",
    fixed: "right",
    width: 80,
    slot: "operation"
  }
];

// 详情弹窗
const detailVisible = ref(false);
const currentRow = ref<OperationLog | null>(null);

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
  searchForm.method = undefined;
  searchForm.service_name = "";
  searchForm.ip = "";
  searchForm.start_time = "";
  searchForm.end_time = "";
  dateRange.value = null;
  fetchData();
  syncPagination();
};

// 查看详情
const handleView = (row: OperationLog) => {
  currentRow.value = row;
  detailVisible.value = true;
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
    const res = await clearOperationLogs(clearDays.value);
    ElMessage.success(`成功清理 ${res.deleted} 条操作日志`);
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

// 格式化JSON
const formatJson = (jsonStr: string) => {
  if (!jsonStr) return "-";
  try {
    return JSON.stringify(JSON.parse(jsonStr), null, 2);
  } catch {
    return jsonStr;
  }
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
        <el-form-item label="操作用户">
          <el-input
            v-model="searchForm.username"
            placeholder="请输入用户名"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="请求方法">
          <el-select
            v-model="searchForm.method"
            placeholder="请选择"
            clearable
            class="w-[120px]"
          >
            <el-option
              v-for="item in methodOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="业务名称">
          <el-input
            v-model="searchForm.service_name"
            placeholder="请输入业务名称"
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
        <el-form-item label="操作时间">
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
    <PureTableBar title="操作日志" :columns="columns" @refresh="handleSearch">
      <template #buttons>
        <el-button
          v-if="hasAuth('monitor:operation-log:clear')"
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
          </template>
        </pure-table>
      </template>
    </PureTableBar>

    <!-- 详情弹窗 -->
    <el-dialog
      v-model="detailVisible"
      title="操作日志详情"
      width="700px"
      destroy-on-close
    >
      <el-descriptions :column="2" border>
        <el-descriptions-item label="ID">
          {{ currentRow?.id }}
        </el-descriptions-item>
        <el-descriptions-item label="操作用户">
          {{ currentRow?.username }}
        </el-descriptions-item>
        <el-descriptions-item label="请求方法">
          <el-tag :type="getMethodType(currentRow?.method || '')" size="small">
            {{ currentRow?.method }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="响应码">
          <el-tag
            :type="getResponseCodeType(currentRow?.response_code || 0)"
            size="small"
          >
            {{ currentRow?.response_code }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="业务名称" :span="2">
          {{ currentRow?.service_name || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="请求路径" :span="2">
          {{ currentRow?.router }}
        </el-descriptions-item>
        <el-descriptions-item label="IP地址">
          {{ currentRow?.ip }}
        </el-descriptions-item>
        <el-descriptions-item label="IP归属地">
          {{ currentRow?.ip_location || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="执行耗时">
          {{ currentRow?.execution_time }} ms
        </el-descriptions-item>
        <el-descriptions-item label="操作时间">
          {{ currentRow?.created_at }}
        </el-descriptions-item>
        <el-descriptions-item label="请求参数" :span="2">
          <el-scrollbar max-height="200px">
            <pre class="text-xs">{{ formatJson(currentRow?.request_data || "") }}</pre>
          </el-scrollbar>
        </el-descriptions-item>
        <el-descriptions-item label="响应数据" :span="2">
          <el-scrollbar max-height="200px">
            <pre class="text-xs">{{ formatJson(currentRow?.response_data || "") }}</pre>
          </el-scrollbar>
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <!-- 清理弹窗 -->
    <el-dialog v-model="clearVisible" title="清理操作日志" width="400px">
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
