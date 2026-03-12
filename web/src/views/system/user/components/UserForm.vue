<script setup lang="ts">
import { ref, reactive, watch, computed } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import { createUser, updateUser, getUser } from "@/api/system/user";
import { getRoleList } from "@/api/system/role";
import { getPositionList } from "@/api/system/position";
import type { User, UserForm as UserFormType, Department, Role, Position } from "@/api/system/types";

const props = defineProps<{
  visible: boolean;
  title: string;
  row: User | null;
  departmentTree: Department[];
}>();

const emit = defineEmits<{
  "update:visible": [value: boolean];
  success: [];
}>();

// 弹窗可见性
const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

// 表单实例
const formRef = ref<FormInstance>();

// 加载状态
const loading = ref(false);
const submitLoading = ref(false);

// 角色列表
const roleList = ref<Role[]>([]);

// 岗位列表
const positionList = ref<Position[]>([]);

// 表单数据
const formData = reactive<UserFormType>({
  username: "",
  name: "",
  email: "",
  phone: "",
  password: "",
  department_id: undefined,
  status: 1,
  role_ids: [],
  position_ids: []
});

// 表单校验规则
const rules = reactive<FormRules>({
  username: [
    { required: true, message: "请输入用户名", trigger: "blur" },
    { min: 2, max: 20, message: "长度在 2 到 20 个字符", trigger: "blur" }
  ],
  name: [
    { required: true, message: "请输入姓名", trigger: "blur" },
    { min: 2, max: 20, message: "长度在 2 到 20 个字符", trigger: "blur" }
  ],
  password: [
    { required: true, message: "请输入密码", trigger: "blur" },
    { min: 6, max: 20, message: "长度在 6 到 20 个字符", trigger: "blur" }
  ],
  email: [{ type: "email", message: "请输入正确的邮箱地址", trigger: "blur" }],
  phone: [
    { pattern: /^1[3-9]\d{9}$/, message: "请输入正确的手机号", trigger: "blur" }
  ]
});

// 是否编辑模式
const isEdit = computed(() => !!props.row);

// 密码规则（编辑时非必填）
const passwordRules = computed(() => {
  if (isEdit.value) {
    return [
      { min: 6, max: 20, message: "长度在 6 到 20 个字符", trigger: "blur" }
    ];
  }
  return rules.password;
});

// 加载用户详情
const loadUserDetail = async () => {
  if (!props.row?.id) return;
  loading.value = true;
  try {
    const res = await getUser(props.row.id);
    const user = res.data;
    formData.username = user.username;
    formData.name = user.name;
    formData.email = user.email || "";
    formData.phone = user.phone || "";
    formData.department_id = user.department_id || undefined;
    formData.status = user.status;
    formData.role_ids = user.roles?.map(r => r.id) || [];
    formData.position_ids = user.positions?.map(p => p.id) || [];
  } catch {
    ElMessage.error("获取用户信息失败");
  } finally {
    loading.value = false;
  }
};

// 加载角色列表
const loadRoleList = async () => {
  try {
    const res = await getRoleList({ per_page: 1000, status: 1 });
    roleList.value = res.data;
  } catch {
    roleList.value = [];
  }
};

// 加载岗位列表
const loadPositionList = async () => {
  try {
    const res = await getPositionList({ per_page: 1000, status: 1 });
    positionList.value = res.data;
  } catch {
    positionList.value = [];
  }
};

// 重置表单
const resetForm = () => {
  formData.username = "";
  formData.name = "";
  formData.email = "";
  formData.phone = "";
  formData.password = "";
  formData.department_id = undefined;
  formData.status = 1;
  formData.role_ids = [];
  formData.position_ids = [];
  formRef.value?.clearValidate();
};

// 打开弹窗
const handleOpen = () => {
  loadRoleList();
  loadPositionList();
  if (props.row) {
    loadUserDetail();
  } else {
    resetForm();
  }
};

// 关闭弹窗
const handleClose = () => {
  resetForm();
};

// 提交表单
const handleSubmit = async () => {
  const valid = await formRef.value?.validate().catch(() => false);
  if (!valid) return;

  submitLoading.value = true;
  try {
    const submitData = { ...formData };
    // 编辑模式下，如果密码为空则不提交密码字段
    if (isEdit.value && !submitData.password) {
      delete submitData.password;
    }

    if (isEdit.value && props.row) {
      await updateUser(props.row.id, submitData);
      ElMessage.success("更新成功");
    } else {
      await createUser(submitData);
      ElMessage.success("创建成功");
    }
    emit("success");
    dialogVisible.value = false;
  } catch (error: any) {
    ElMessage.error(error?.message || "操作失败");
  } finally {
    submitLoading.value = false;
  }
};
</script>

<template>
  <el-dialog
    v-model="dialogVisible"
    :title="title"
    width="600px"
    :close-on-click-modal="false"
    destroy-on-close
    @open="handleOpen"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      v-loading="loading"
      :model="formData"
      :rules="rules"
      label-width="100px"
    >
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="用户名" prop="username">
            <el-input
              v-model="formData.username"
              placeholder="请输入用户名"
              :disabled="isEdit"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="姓名" prop="name">
            <el-input v-model="formData.name" placeholder="请输入姓名" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="密码" prop="password" :rules="passwordRules">
            <el-input
              v-model="formData.password"
              type="password"
              :placeholder="isEdit ? '不修改请留空' : '请输入密码'"
              show-password
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="手机号" prop="phone">
            <el-input v-model="formData.phone" placeholder="请输入手机号" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="邮箱" prop="email">
            <el-input v-model="formData.email" placeholder="请输入邮箱" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="部门" prop="department_id">
            <el-tree-select
              v-model="formData.department_id"
              :data="departmentTree"
              :props="{ label: 'name', value: 'id', children: 'children' }"
              placeholder="请选择部门"
              clearable
              check-strictly
              filterable
              class="w-full"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="角色" prop="role_ids">
            <el-select
              v-model="formData.role_ids"
              multiple
              placeholder="请选择角色"
              clearable
              class="w-full"
            >
              <el-option
                v-for="item in roleList"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="岗位" prop="position_ids">
            <el-select
              v-model="formData.position_ids"
              multiple
              placeholder="请选择岗位"
              clearable
              class="w-full"
            >
              <el-option
                v-for="item in positionList"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="状态" prop="status">
        <el-radio-group v-model="formData.status">
          <el-radio :value="1">启用</el-radio>
          <el-radio :value="0">停用</el-radio>
        </el-radio-group>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
        确定
      </el-button>
    </template>
  </el-dialog>
</template>
