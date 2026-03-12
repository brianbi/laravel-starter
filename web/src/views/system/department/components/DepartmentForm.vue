<script setup lang="ts">
import { ref, reactive, computed } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import { createDepartment, updateDepartment, getDepartment } from "@/api/system/department";
import type { Department, DepartmentForm as DepartmentFormType } from "@/api/system/types";

const props = defineProps<{
  visible: boolean;
  title: string;
  row: Department | null;
  parentId: number;
  departmentTree: Department[];
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

const formData = reactive<DepartmentFormType>({
  parent_id: 0,
  name: "",
  leader: "",
  phone: "",
  email: "",
  sort: 0,
  status: 1
});

const rules = reactive<FormRules>({
  name: [{ required: true, message: "请输入部门名称", trigger: "blur" }],
  email: [{ type: "email", message: "请输入正确的邮箱", trigger: "blur" }],
  phone: [{ pattern: /^1[3-9]\d{9}$/, message: "请输入正确的手机号", trigger: "blur" }]
});

const isEdit = computed(() => !!props.row);

const loadDetail = async () => {
  if (!props.row?.id) return;
  loading.value = true;
  try {
    const res = await getDepartment(props.row.id);
    Object.assign(formData, res.data);
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  Object.assign(formData, {
    parent_id: props.parentId,
    name: "",
    leader: "",
    phone: "",
    email: "",
    sort: 0,
    status: 1
  });
  formRef.value?.clearValidate();
};

const handleOpen = () => {
  if (props.row) {
    loadDetail();
  } else {
    resetForm();
    formData.parent_id = props.parentId;
  }
};

const handleSubmit = async () => {
  const valid = await formRef.value?.validate().catch(() => false);
  if (!valid) return;
  submitLoading.value = true;
  try {
    if (isEdit.value && props.row) {
      await updateDepartment(props.row.id, formData);
    } else {
      await createDepartment(formData);
    }
    ElMessage.success(isEdit.value ? "更新成功" : "创建成功");
    emit("success");
    dialogVisible.value = false;
  } catch (e: any) {
    ElMessage.error(e?.message || "操作失败");
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
    <el-form ref="formRef" v-loading="loading" :model="formData" :rules="rules" label-width="100px">
      <el-form-item label="上级部门">
        <el-tree-select
          v-model="formData.parent_id"
          :data="[{ id: 0, name: '顶级部门', children: departmentTree }]"
          :props="{ label: 'name', value: 'id', children: 'children' }"
          check-strictly
          filterable
          default-expand-all
          class="w-full"
        />
      </el-form-item>
      <el-form-item label="部门名称" prop="name">
        <el-input v-model="formData.name" placeholder="请输入部门名称" />
      </el-form-item>
      <el-form-item label="负责人" prop="leader">
        <el-input v-model="formData.leader" placeholder="请输入负责人" />
      </el-form-item>
      <el-form-item label="联系电话" prop="phone">
        <el-input v-model="formData.phone" placeholder="请输入联系电话" />
      </el-form-item>
      <el-form-item label="邮箱" prop="email">
        <el-input v-model="formData.email" placeholder="请输入邮箱" />
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
    </el-form>
    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>
