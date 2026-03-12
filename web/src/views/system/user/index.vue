<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { useTable } from "@/hooks/useTable";
import { PureTableBar } from "@/components/RePureTableBar";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import { hasAuth } from "@/router/utils";
import UserForm from "./components/UserForm.vue";
import AssignRole from "./components/AssignRole.vue";
import ResetPassword from "./components/ResetPassword.vue";
import {
  getUserList,
  deleteUser,
  updateUserStatus
} from "@/api/system/user";
import { getDepartmentTree } from "@/api/system/department";
import type { User, UserQuery, Department } from "@/api/system/types";
import type { PaginationProps } from "@pureadmin/table";

import AddIcon from "~icons/ep/plus";
import EditIcon from "~icons/ep/edit";
import DeleteIcon from "~icons/ep/delete";
import SearchIcon from "~icons/ep/search";
import RefreshIcon from "~icons/ep/refresh";
import KeyIcon from "~icons/ep/key";
import UserIcon from "~icons/ep/user";

defineOptions({
  name: "SystemUser"
});

// 表格 ref
const tableRef = ref();

// 搜索参数
const searchForm = reactive<UserQuery>({
  username: "",
  name: "",
  phone: "",
  status: undefined,
  department_id: undefined
});

// 部门树
const departmentTree = ref<Department[]>([]);

// 表格数据管理
const {
  data: tableData,
  loading,
  pagination,
  fetchData,
  handlePaginationChange,
  handleSelectionChange,
  selectedRows
} = useTable<User, UserQuery>({
  fetchApi: async params => {
    const res = await getUserList(params);
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
  { type: "selection", width: 55, align: "left", fixed: "left" },
  { label: "用户名", prop: "username", minWidth: 100 },
  { label: "姓名", prop: "name", minWidth: 100 },
  { label: "部门", prop: "department.name", minWidth: 120 },
  { label: "手机号", prop: "phone", minWidth: 120 },
  { label: "邮箱", prop: "email", minWidth: 150 },
  {
    label: "状态",
    prop: "status",
    minWidth: 80,
    cellRenderer: ({ row }) => (
      <el-switch
        v-model={row.status}
        active-value={1}
        inactive-value={0}
        inline-prompt
        active-text="启"
        inactive-text="停"
        disabled={!hasAuth("system:user:status")}
        onChange={() => handleStatusChange(row)}
      />
    )
  },
  {
    label: "最后登录",
    prop: "login_at",
    minWidth: 160,
    formatter: ({ login_at }) => login_at || "-"
  },
  {
    label: "操作",
    fixed: "right",
    width: 240,
    slot: "operation"
  }
];

// 用户表单弹窗
const formVisible = ref(false);
const formTitle = ref("新增用户");
const currentRow = ref<User | null>(null);

// 角色分配弹窗
const roleVisible = ref(false);
const roleUserId = ref<number>(0);

// 密码重置弹窗
const passwordVisible = ref(false);
const passwordUserId = ref<number>(0);

// 搜索
const handleSearch = () => {
  fetchData();
  syncPagination();
};

// 重置
const handleReset = () => {
  searchForm.username = "";
  searchForm.name = "";
  searchForm.phone = "";
  searchForm.status = undefined;
  searchForm.department_id = undefined;
  fetchData();
  syncPagination();
};

// 新增
const handleAdd = () => {
  currentRow.value = null;
  formTitle.value = "新增用户";
  formVisible.value = true;
};

// 编辑
const handleEdit = (row: User) => {
  currentRow.value = row;
  formTitle.value = "编辑用户";
  formVisible.value = true;
};

// 删除
const handleDelete = async (row: User) => {
  try {
    await ElMessageBox.confirm(`确认删除用户「${row.name}」吗？`, "提示", {
      type: "warning"
    });
    await deleteUser(row.id);
    ElMessage.success("删除成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消删除
  }
};

// 批量删除
const handleBatchDelete = async () => {
  if (selectedRows.value.length === 0) {
    ElMessage.warning("请选择要删除的用户");
    return;
  }
  try {
    await ElMessageBox.confirm(
      `确认删除选中的 ${selectedRows.value.length} 个用户吗？`,
      "提示",
      { type: "warning" }
    );
    await Promise.all(selectedRows.value.map(row => deleteUser(row.id)));
    ElMessage.success("删除成功");
    fetchData();
    syncPagination();
  } catch {
    // 取消删除
  }
};

// 切换状态
const handleStatusChange = async (row: User) => {
  try {
    await updateUserStatus(row.id, row.status);
    ElMessage.success(row.status === 1 ? "启用成功" : "停用成功");
  } catch {
    row.status = row.status === 1 ? 0 : 1;
  }
};

// 分配角色
const handleAssignRole = (row: User) => {
  roleUserId.value = row.id;
  roleVisible.value = true;
};

// 重置密码
const handleResetPassword = (row: User) => {
  passwordUserId.value = row.id;
  passwordVisible.value = true;
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

// 加载部门树
const loadDepartmentTree = async () => {
  try {
    const res = await getDepartmentTree();
    departmentTree.value = res.data;
  } catch {
    departmentTree.value = [];
  }
};

onMounted(() => {
  loadDepartmentTree();
  syncPagination();
});
</script>

<template>
  <div class="main">
    <!-- 搜索区域 -->
    <el-card shadow="never" class="mb-4">
      <el-form :model="searchForm" inline>
        <el-form-item label="用户名">
          <el-input
            v-model="searchForm.username"
            placeholder="请输入用户名"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="姓名">
          <el-input
            v-model="searchForm.name"
            placeholder="请输入姓名"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="手机号">
          <el-input
            v-model="searchForm.phone"
            placeholder="请输入手机号"
            clearable
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item label="部门">
          <el-tree-select
            v-model="searchForm.department_id"
            :data="departmentTree"
            :props="{ label: 'name', value: 'id', children: 'children' }"
            placeholder="请选择部门"
            clearable
            check-strictly
            filterable
            class="w-[200px]"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select
            v-model="searchForm.status"
            placeholder="请选择"
            clearable
            class="w-[120px]"
          >
            <el-option label="启用" :value="1" />
            <el-option label="停用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="useRenderIcon(SearchIcon)" @click="handleSearch">
            搜索
          </el-button>
          <el-button :icon="useRenderIcon(RefreshIcon)" @click="handleReset">
            重置
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格区域 -->
    <PureTableBar title="用户列表" :columns="columns" @refresh="handleSearch">
      <template #buttons>
        <el-button
          v-if="hasAuth('system:user:add')"
          type="primary"
          :icon="useRenderIcon(AddIcon)"
          @click="handleAdd"
        >
          新增用户
        </el-button>
        <el-button
          v-if="hasAuth('system:user:delete')"
          type="danger"
          :icon="useRenderIcon(DeleteIcon)"
          :disabled="selectedRows.length === 0"
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
              v-if="hasAuth('system:user:edit')"
              type="primary"
              link
              :icon="useRenderIcon(EditIcon)"
              @click="handleEdit(row)"
            >
              编辑
            </el-button>
            <el-button
              v-if="hasAuth('system:user:role')"
              type="primary"
              link
              :icon="useRenderIcon(UserIcon)"
              @click="handleAssignRole(row)"
            >
              角色
            </el-button>
            <el-button
              v-if="hasAuth('system:user:password')"
              type="warning"
              link
              :icon="useRenderIcon(KeyIcon)"
              @click="handleResetPassword(row)"
            >
              重置密码
            </el-button>
            <el-button
              v-if="hasAuth('system:user:delete')"
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

    <!-- 用户表单弹窗 -->
    <UserForm
      v-model:visible="formVisible"
      :title="formTitle"
      :row="currentRow"
      :department-tree="departmentTree"
      @success="handleFormSuccess"
    />

    <!-- 角色分配弹窗 -->
    <AssignRole
      v-model:visible="roleVisible"
      :user-id="roleUserId"
      @success="handleSearch"
    />

    <!-- 重置密码弹窗 -->
    <ResetPassword v-model:visible="passwordVisible" :user-id="passwordUserId" />
  </div>
</template>

<style lang="scss" scoped>
.main {
  padding: 16px;
}
</style>
