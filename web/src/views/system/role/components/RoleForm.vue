<script setup lang="ts">
import { ref, reactive, computed } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import { createRole, updateRole, getRole } from "@/api/system/role";
import type { Role, RoleForm as RoleFormType } from "@/api/system/types";

const props = defineProps<{
  visible: boolean;
  title: string;
  row: Role | null;
}>();

const emit = defineEmits<{
  "update:visible": [value: boolean];
  success: [];
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

const formRef = ref<FormInstance>();
const loading = ref(false);
const submitLoading = ref(false);

const formData = reactive<RoleFormType>({
  name: "",
  code: "",
  description: "",
  data_scope: 1,
  status: 1,
  sort: 0
});

const rules = reactive<FormRules>({
  name: [
    { required: true, message: "请输入角色名称", trigger: "blur" },
    { min: 2, max: 30, message: "长度在 2 到 30 个字符", trigger: "blur" }
  ],
  code: [
    { required: true, message: "请输入角色标识", trigger: "blur" },
    { pattern: /^[a-zA-Z][a-zA-Z0-9_]*$/, message: "以字母开头，只能包含字母数字下划线", trigger: "blur" }
  ]
});

const isEdit = computed(() => !!props.row);

const dataScopeOptions = [
  { label: "全部数据", value: 1 },
  { label: "本部门及以下数据", value: 2 },
  { label: "本部门数据", value: 3 },
  { label: "仅本人数据", value: 4 },
  { label: "自定义数据", value: 5 }
];

const loadRoleDetail = async () => {
  if (!props.row?.id) return;
  loading.value = true;
  try {
    const res = await getRole(props.row.id);
    const role = res.data;
    formData.name = role.name;
    formData.code = role.code;
    formData.description = role.description || "";
    formData.data_scope = role.data_scope;
    formData.status = role.status;
    formData.sort = role.sort;
  } catch {
    ElMessage.error("获取角色信息失败");
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  formData.name = "";
  formData.code = "";
  formData.description = "";
  formData.data_scope = 1;
  formData.status = 1;
  formData.sort = 0;
  formRef.value?.clearValidate();
};

const handleOpen = () => {
  if (props.row) {
    loadRoleDetail();
  } else {
    resetForm();
  }
};

const handleSubmit = async () => {
  const valid = await formRef.value?.validate().catch(() => false);
  if (!valid) return;

  submitLoading.value = true;
  try {
    if (isEdit.value && props.row) {
      await updateRole(props.row.id, formData);
      ElMessage.success("更新成功");
    } else {
      await createRole(formData);
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
    width="500px"
    :close-on-click-modal="false"
    destroy-on-close
    @open="handleOpen"
    @close="resetForm"
  >
    <el-form
      ref="formRef"
      v-loading="loading"
      :model="formData"
      :rules="rules"
      label-width="100px"
    >
      <el-form-item label="角色名称" prop="name">
        <el-input v-model="formData.name" placeholder="请输入角色名称" />
      </el-form-item>
      <el-form-item label="角色标识" prop="code">
        <el-input v-model="formData.code" placeholder="请输入角色标识" :disabled="isEdit" />
      </el-form-item>
      <el-form-item label="数据权限" prop="data_scope">
        <el-select v-model="formData.data_scope" placeholder="请选择" class="w-full">
          <el-option
            v-for="item in dataScopeOptions"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="排序" prop="sort">
        <el-input-number v-model="formData.sort" :min="0" controls-position="right" />
      </el-form-item>
      <el-form-item label="状态" prop="status">
        <el-radio-group v-model="formData.status">
          <el-radio :value="1">启用</el-radio>
          <el-radio :value="0">停用</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="描述" prop="description">
        <el-input
          v-model="formData.description"
          type="textarea"
          :rows="3"
          placeholder="请输入描述"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>
