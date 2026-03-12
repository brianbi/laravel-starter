<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useRouter } from "vue-router";
import { ElMessage, ElMessageBox } from "element-plus";
import { useTable } from "@/hooks/useTable";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { hasAuth } from "@/router/utils";
import FormDefinitionForm from "./components/FormDefinitionForm.vue";
import {
  getFormDefinitionList,
  deleteFormDefinition,
  batchDeleteFormDefinition,
  enableForm,
  disableForm,
  copyForm
} from "@/api/form/definition";
import type {
  FormDefinition,
  FormDefinitionQuery
} from "@/api/form/types";
import type { PaginationProps } from "@pureadmin/table";

import AddIcon from "~icons/ep/plus";
import EditIcon from "~icons/ep/edit";
import DeleteIcon from "~icons/ep/delete";
import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";
import CopyIcon from "~icons/ep/copy-document";
import SetUpIcon from "~icons/ep/set-up";
import ViewIcon from "~icons/ep/view";
import CheckIcon from "~icons/ep/check";
import CloseIcon from "~icons/ep/close";

defineOptions({
  name: "FormDefinition"
});

const router = useRouter();

// 表格 ref
const tableRef = ref();

// 搜索参数
const searchForm = reactive<FormDefinitionQuery>({
  name: "",
  code: "",
  status: undefined,
  created_by: undefined
});

// 表格数据管理
const {
  data: tableData,
  loading,
  pagination,
  fetchData,
  handlePaginationChange,
  handleSelectionChange,
  selectedRows
} = useTable<FormDefinition, FormDefinitionQuery>({
  fetchApi: async params => {
    const res = await getFormDefinitionList(params);
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
  { label: "启用", value: 1 },
  { label: "停用", value: 2 }
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
    1: "启用",
    2: "停用"
  };
  return textMap[status] || "未知";
};

// 表格列配置
const columns: TableColumnList = [
  { type: "selection", width: 55, align: "left", fixed: "left" },
  { label: "ID", prop: "id", width: 80 },
  { label: "表单编码", prop: "code", minWidth: 120 },
  { label: "表单名称", prop: "name", minWidth: 150 },
  {
    label: "描述",
    prop: "description",
    minWidth: 200,
    showOverflowTooltip: true
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
  { label: "创建时间", prop: "created_at", minWidth: 160 },
  {
    label: "操作",
    fixed: "right",
    width: 320,
    slot: "operation"
  }
];

// 表单弹窗
const formVisible = ref(false);
const formTitle = ref("新增表单");
const currentRow = ref<FormDefinition | null>(null);

// 搜索
const handleSearch = () => {
  fetchData();
  syncPagination();
};

// 重置
const handleReset = () => {
  searchForm.name = "";
  searchForm.code = "";
  searchForm.status = undefined;
  searchForm.created_by = undefined;
  fetchData();
  syncPagination();
};

// 新增
const handleAdd = () => {
  currentRow.value = null;
  formTitle.value = "新增表单";
  formVisible.value = true;
};

// 编辑
const handleEdit = (row: FormDefinition) => {
  currentRow.value = row;
  formTitle.value = "编辑表单";
  formVisible.value = true;
};

// 设计表单
const handleDesign = (row: FormDefinition) => {
  router.push({
    path: "/form/designer",
    query: { id: row.id }
  });
};

// 查看数据
const handleViewData = (row: FormDefinition) => {
  router.push({
    path: "/form/data",
    query: { formId: row.id, formName: row.name }
  });
};

// 启用
const handleEnable = async (row: FormDefinition) => {
  try {
    await ElMessageBox.confirm(`确认启用表单「${row.name}」吗？`, "提示", {
      type: "warning"
    });
    await enableForm(row.id);
    ElMessage.success("启用成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消
  }
};

// 停用
const handleDisable = async (row: FormDefinition) => {
  try {
    await ElMessageBox.confirm(`确认停用表单「${row.name}」吗？`, "提示", {
      type: "warning"
    });
    await disableForm(row.id);
    ElMessage.success("停用成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消
  }
};

// 复制
const handleCopy = async (row: FormDefinition) => {
  try {
    const { value } = await ElMessageBox.prompt(
      "请输入新表单的编码和名称",
      "复制表单",
      {
        inputValue: `${row.code}_copy`,
        inputPlaceholder: "请输入表单编码",
        inputValidator: (value: string) => {
          if (!value) return "请输入表单编码";
          if (!/^[a-zA-Z][a-zA-Z0-9_]*$/.test(value))
            return "编码只能包含字母、数字和下划线，且必须以字母开头";
          return true;
        }
      }
    );
    
    const name = await ElMessageBox.prompt("请输入新表单名称", "复制表单", {
      inputValue: `${row.name}(副本)`
    });
    
    await copyForm(row.id, value, name.value);
    ElMessage.success("复制成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消
  }
};

// 删除
const handleDelete = async (row: FormDefinition) => {
  try {
    await ElMessageBox.confirm(
      `确认删除表单「${row.name}」吗？删除后不可恢复。`,
      "提示",
      { type: "warning" }
    );
    await deleteFormDefinition(row.id);
    ElMessage.success("删除成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消
  }
};

// 批量删除
const handleBatchDelete = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning("请至少选择一个表单");
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确认删除选中的 ${selectedRows.value.length} 个表单吗？删除后不可恢复。`,
      "提示",
      { type: "warning" }
    );
    await batchDeleteFormDefinition(selectedRows.value.map(row => row.id));
    ElMessage.success("批量删除成功");
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
  handlePaginationChange({ page, pageSize: paginationConfig.pageSize });
  syncPagination();
};

const onSizeChange = (size: number) => {
  handlePaginationChange({ page: 1, pageSize: size });
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
        <el-form-item label="表单名称">
          <el-input
            v-model="searchForm.name"
            placeholder="请输入表单名称"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="表单编码">
          <el-input
            v-model="searchForm.code"
            placeholder="请输入表单编码"
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
    <PureTableBar title="表单定义" :columns="columns" @refresh="handleSearch">
      <template #buttons>
        <el-button
          v-if="hasAuth('form:definition:add')"
          type="primary"
          :icon="useRenderIcon(AddIcon)"
          @click="handleAdd"
        >
          新增表单
        </el-button>
        <el-button
          v-if="hasAuth('form:definition:delete') && selectedRows.length > 0"
          type="danger"
          :icon="useRenderIcon(DeleteIcon)"
          @click="handleBatchDelete"
        >
          批量删除
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
          @selection-change="handleSelectionChange"
          @page-size-change="onSizeChange"
          @page-current-change="onPageChange"
        >
          <template #operation="{ row }">
            <el-button
              v-if="hasAuth('form:definition:edit')"
              type="primary"
              link
              :icon="useRenderIcon(EditIcon)"
              @click="handleEdit(row)"
            >
              编辑
            </el-button>
            <el-button
              v-if="hasAuth('form:definition:design')"
              type="primary"
              link
              :icon="useRenderIcon(SetUpIcon)"
              @click="handleDesign(row)"
            >
              设计
            </el-button>
            <el-button
              v-if="hasAuth('form:definition:view-data')"
              type="primary"
              link
              :icon="useRenderIcon(ViewIcon)"
              @click="handleViewData(row)"
            >
              数据
            </el-button>
            <el-button
              v-if="hasAuth('form:definition:enable') && row.status !== 1"
              type="success"
              link
              :icon="useRenderIcon(CheckIcon)"
              @click="handleEnable(row)"
            >
              启用
            </el-button>
            <el-button
              v-if="hasAuth('form:definition:disable') && row.status !== 2"
              type="warning"
              link
              :icon="useRenderIcon(CloseIcon)"
              @click="handleDisable(row)"
            >
              停用
            </el-button>
            <el-button
              v-if="hasAuth('form:definition:copy')"
              type="info"
              link
              :icon="useRenderIcon(CopyIcon)"
              @click="handleCopy(row)"
            >
              复制
            </el-button>
            <el-button
              v-if="hasAuth('form:definition:delete')"
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
    <FormDefinitionForm
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
