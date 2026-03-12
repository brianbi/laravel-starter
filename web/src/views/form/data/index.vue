<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useRoute } from "vue-router";
import { ElMessage, ElMessageBox } from "element-plus";
import { useTable } from "@/hooks/useTable";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { hasAuth } from "@/router/utils";
import FormDataDetail from "./components/FormDataDetail.vue";
import {
  getFormDataList,
  deleteFormData
} from "@/api/form/data";
import type { FormData, FormDataQuery } from "@/api/form/types";
import type { PaginationProps } from "@pureadmin/table";

import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";
import ViewIcon from "~icons/ep/view";
import DeleteIcon from "~icons/ep/delete";

defineOptions({
  name: "FormDataManagement"
});

const route = useRoute();

// 从路由获取表单ID和名称
const formId = Number(route.query.formId);
const formName = route.query.formName as string;

if (!formId) {
  ElMessage.error("表单ID不能为空");
  throw new Error("表单ID不能为空");
}

// 搜索参数
const searchForm = reactive<FormDataQuery>({
  form_id: formId,
  created_by: undefined,
  instance_id: undefined
});

// 表格数据管理
const {
  data: tableData,
  loading,
  pagination,
  fetchData
} = useTable<FormData, FormDataQuery>({
  fetchApi: async params => {
    const res = await getFormDataList(formId, params);
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

// 表格列配置
const columns: TableColumnList = [
  { label: "ID", prop: "id", width: 80 },
  { label: "流程实例ID", prop: "instance_id", width: 120 },
  { label: "提交人", prop: "creator.name", minWidth: 100 },
  { label: "提交时间", prop: "created_at", minWidth: 160 },
  {
    label: "操作",
    fixed: "right",
    width: 150,
    slot: "operation"
  }
];

// 详情弹窗
const detailVisible = ref(false);
const currentData = ref<FormData | null>(null);

// 搜索
const handleSearch = () => {
  fetchData();
  syncPagination();
};

// 重置
const handleReset = () => {
  searchForm.created_by = undefined;
  searchForm.instance_id = undefined;
  fetchData();
  syncPagination();
};

// 查看详情
const handleView = (row: FormData) => {
  currentData.value = row;
  detailVisible.value = true;
};

// 删除
const handleDelete = async (row: FormData) => {
  if (row.instance_id) {
    ElMessage.error("该数据已关联流程实例，无法删除");
    return;
  }

  try {
    await ElMessageBox.confirm("确认删除此条表单数据吗？删除后不可恢复。", "提示", {
      type: "warning"
    });
    await deleteFormData(formId, row.id);
    ElMessage.success("删除成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消
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
    <el-page-header :title="`表单数据 - ${formName}`" class="mb-4" @back="$router.back()" />

    <!-- 搜索区域 -->
    <el-card shadow="never" class="mb-4">
      <el-form :model="searchForm" inline>
        <el-form-item label="提交人">
          <el-input
            v-model="searchForm.created_by"
            placeholder="请输入提交人ID"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="流程实例ID">
          <el-input
            v-model="searchForm.instance_id"
            placeholder="请输入流程实例ID"
            clearable
            @keyup.enter="handleSearch"
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
    <PureTableBar :title="`${formName} - 数据列表`" :columns="columns" @refresh="handleSearch">
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
              v-if="hasAuth('form:data:delete') && !row.instance_id"
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

    <!-- 详情弹窗 -->
    <FormDataDetail
      v-model:visible="detailVisible"
      :data="currentData"
    />
  </div>
</template>

<style lang="scss" scoped>
.main {
  padding: 16px;
}
</style>
