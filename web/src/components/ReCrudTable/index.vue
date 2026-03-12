<script setup lang="ts">
import { ref, computed, watch, useSlots } from "vue";
import { PureTableBar } from "@/components/RePureTableBar";
import { hasAuth } from "@/router/utils";
import type {
  CrudTableProps,
  Pagination,
  SearchField,
  ToolbarButton
} from "./types";

import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";
import PlusIcon from "~icons/ep/plus";

defineOptions({
  name: "ReCrudTable"
});

const props = withDefaults(defineProps<CrudTableProps>(), {
  title: "列表",
  rowKey: "id",
  showPagination: true,
  showSelection: false,
  showIndex: false,
  showToolbar: true,
  stripe: true,
  border: false,
  emptyText: "暂无数据"
});

const emit = defineEmits<{
  refresh: [];
  search: [params: Record<string, any>];
  reset: [];
  "pagination-change": [pagination: { page: number; pageSize: number }];
  "selection-change": [selection: any[]];
  "sort-change": [sort: { prop: string; order: string }];
  "row-click": [row: any];
}>();

const slots = useSlots();

// 搜索表单数据
const searchForm = ref<Record<string, any>>({});

// 初始化搜索表单默认值
const initSearchForm = () => {
  if (props.searchFields) {
    props.searchFields.forEach(field => {
      if (field.defaultValue !== undefined) {
        searchForm.value[field.prop] = field.defaultValue;
      } else {
        searchForm.value[field.prop] = field.type === "daterange" ? [] : "";
      }
    });
  }
};
initSearchForm();

// 选中行
const selectedRows = ref<any[]>([]);

// 表格 ref
const tableRef = ref();

// 分页配置
const paginationConfig = computed<Pagination>(() => ({
  currentPage: props.pagination?.currentPage ?? 1,
  pageSize: props.pagination?.pageSize ?? 10,
  total: props.pagination?.total ?? 0,
  pageSizes: props.pagination?.pageSizes ?? [10, 20, 50, 100],
  layout: props.pagination?.layout ?? "total, sizes, prev, pager, next, jumper",
  background: props.pagination?.background ?? true
}));

// 处理分页变化
const handlePageChange = (page: number) => {
  emit("pagination-change", {
    page,
    pageSize: paginationConfig.value.pageSize
  });
};

const handleSizeChange = (size: number) => {
  emit("pagination-change", {
    page: 1,
    pageSize: size
  });
};

// 处理搜索
const handleSearch = () => {
  emit("search", { ...searchForm.value });
};

// 处理重置
const handleReset = () => {
  initSearchForm();
  emit("reset");
};

// 处理刷新
const handleRefresh = () => {
  emit("refresh");
};

// 处理选择变化
const handleSelectionChange = (selection: any[]) => {
  selectedRows.value = selection;
  emit("selection-change", selection);
};

// 处理排序变化
const handleSortChange = ({
  prop,
  order
}: {
  prop: string;
  order: string | null;
}) => {
  emit("sort-change", { prop, order: order ?? "" });
};

// 处理行点击
const handleRowClick = (row: any) => {
  emit("row-click", row);
};

// 检查按钮权限
const checkButtonAuth = (button: ToolbarButton): boolean => {
  if (!button.auth) return true;
  return hasAuth(button.auth);
};

// 检查按钮是否禁用
const isButtonDisabled = (button: ToolbarButton): boolean => {
  if (typeof button.disabled === "function") {
    return button.disabled();
  }
  return button.disabled ?? false;
};

// 暴露方法
defineExpose({
  tableRef,
  selectedRows,
  searchForm,
  clearSelection: () => tableRef.value?.clearSelection(),
  toggleRowSelection: (row: any, selected?: boolean) =>
    tableRef.value?.toggleRowSelection(row, selected),
  getSelectionRows: () => selectedRows.value
});
</script>

<template>
  <div class="re-crud-table">
    <!-- 搜索区域 -->
    <el-card v-if="searchFields && searchFields.length > 0" class="mb-4" shadow="never">
      <el-form :model="searchForm" inline>
        <template v-for="field in searchFields" :key="field.prop">
          <!-- 输入框 -->
          <el-form-item v-if="field.type === 'input'" :label="field.label">
            <el-input
              v-model="searchForm[field.prop]"
              :placeholder="field.placeholder ?? `请输入${field.label}`"
              clearable
              v-bind="field.props"
              @keyup.enter="handleSearch"
            />
          </el-form-item>

          <!-- 下拉选择 -->
          <el-form-item v-else-if="field.type === 'select'" :label="field.label">
            <el-select
              v-model="searchForm[field.prop]"
              :placeholder="field.placeholder ?? `请选择${field.label}`"
              clearable
              v-bind="field.props"
            >
              <el-option
                v-for="opt in field.options"
                :key="opt.value"
                :label="opt.label"
                :value="opt.value"
              />
            </el-select>
          </el-form-item>

          <!-- 日期选择 -->
          <el-form-item v-else-if="field.type === 'date'" :label="field.label">
            <el-date-picker
              v-model="searchForm[field.prop]"
              type="date"
              :placeholder="field.placeholder ?? `请选择${field.label}`"
              clearable
              value-format="YYYY-MM-DD"
              v-bind="field.props"
            />
          </el-form-item>

          <!-- 日期范围选择 -->
          <el-form-item v-else-if="field.type === 'daterange'" :label="field.label">
            <el-date-picker
              v-model="searchForm[field.prop]"
              type="daterange"
              range-separator="至"
              start-placeholder="开始日期"
              end-placeholder="结束日期"
              clearable
              value-format="YYYY-MM-DD"
              v-bind="field.props"
            />
          </el-form-item>
        </template>

        <el-form-item>
          <el-button type="primary" :icon="SearchIcon" @click="handleSearch">
            搜索
          </el-button>
          <el-button :icon="RefreshIcon" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格区域 -->
    <PureTableBar
      :title="title"
      :columns="columns"
      @refresh="handleRefresh"
    >
      <template #buttons>
        <!-- 自定义工具栏按钮 -->
        <template v-if="toolbarButtons && toolbarButtons.length > 0">
          <template v-for="(button, index) in toolbarButtons" :key="index">
            <el-button
              v-if="checkButtonAuth(button)"
              :type="button.type ?? 'primary'"
              :icon="button.icon"
              :disabled="isButtonDisabled(button)"
              @click="button.onClick"
            >
              {{ button.label }}
            </el-button>
          </template>
        </template>
        <!-- 插槽按钮 -->
        <slot name="toolbar-buttons" />
      </template>

      <template #default="{ size, dynamicColumns }">
        <pure-table
          ref="tableRef"
          adaptive
          :adaptiveConfig="{ offsetBottom: 96 }"
          align-whole="center"
          :size="size"
          :data="data"
          :columns="dynamicColumns"
          :row-key="rowKey"
          :loading="loading"
          :stripe="stripe"
          :border="border"
          :height="height"
          :max-height="maxHeight"
          row-class-name="cursor-pointer"
          @selection-change="handleSelectionChange"
          @sort-change="handleSortChange"
          @row-click="handleRowClick"
        >
          <!-- 选择列 -->
          <template v-if="showSelection" #selection>
            <el-table-column type="selection" width="55" fixed="left" />
          </template>

          <!-- 序号列 -->
          <template v-if="showIndex" #index>
            <el-table-column type="index" label="序号" width="60" fixed="left" />
          </template>

          <!-- 自定义列插槽 -->
          <template v-for="col in columns" :key="col.prop">
            <template v-if="col.slot" #[col.slot]="scope">
              <slot :name="col.slot" v-bind="scope" />
            </template>
          </template>

          <!-- 操作列插槽 -->
          <template v-if="slots.operation" #operation="scope">
            <slot name="operation" v-bind="scope" />
          </template>

          <!-- 空数据 -->
          <template #empty>
            <el-empty :description="emptyText" />
          </template>
        </pure-table>

        <!-- 分页 -->
        <div v-if="showPagination" class="flex justify-end mt-4">
          <el-pagination
            v-model:current-page="paginationConfig.currentPage"
            v-model:page-size="paginationConfig.pageSize"
            :page-sizes="paginationConfig.pageSizes"
            :layout="paginationConfig.layout"
            :total="paginationConfig.total"
            :background="paginationConfig.background"
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </template>
    </PureTableBar>
  </div>
</template>

<style lang="scss" scoped>
.re-crud-table {
  :deep(.el-card__body) {
    padding: 18px;
  }
}
</style>
