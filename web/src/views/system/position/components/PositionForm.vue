<script setup lang="ts">
import { ref, reactive, computed } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import { createPosition, updatePosition, getPosition } from "@/api/system/position";
import type { Position, PositionForm as PositionFormType } from "@/api/system/types";

const props = defineProps<{ visible: boolean; title: string; row: Position | null }>();
const emit = defineEmits<{ "update:visible": [value: boolean]; success: [] }>();

const dialogVisible = computed({ get: () => props.visible, set: val => emit("update:visible", val) });
const formRef = ref<FormInstance>();
const loading = ref(false);
const submitLoading = ref(false);

const formData = reactive<PositionFormType>({ name: "", code: "", sort: 0, status: 1, remark: "" });
const rules = reactive<FormRules>({
  name: [{ required: true, message: "请输入岗位名称", trigger: "blur" }],
  code: [{ required: true, message: "请输入岗位编码", trigger: "blur" }]
});

const isEdit = computed(() => !!props.row);

const loadDetail = async () => {
  if (!props.row?.id) return;
  loading.value = true;
  try {
    const res = await getPosition(props.row.id);
    Object.assign(formData, res.data);
  } finally { loading.value = false; }
};

const resetForm = () => {
  Object.assign(formData, { name: "", code: "", sort: 0, status: 1, remark: "" });
  formRef.value?.clearValidate();
};

const handleOpen = () => { props.row ? loadDetail() : resetForm(); };

const handleSubmit = async () => {
  const valid = await formRef.value?.validate().catch(() => false);
  if (!valid) return;
  submitLoading.value = true;
  try {
    isEdit.value && props.row ? await updatePosition(props.row.id, formData) : await createPosition(formData);
    ElMessage.success(isEdit.value ? "更新成功" : "创建成功");
    emit("success"); dialogVisible.value = false;
  } catch (e: any) { ElMessage.error(e?.message || "操作失败"); }
  finally { submitLoading.value = false; }
};
</script>

<template>
  <el-dialog v-model="dialogVisible" :title="title" width="500px" :close-on-click-modal="false" destroy-on-close @open="handleOpen" @close="resetForm">
    <el-form ref="formRef" v-loading="loading" :model="formData" :rules="rules" label-width="100px">
      <el-form-item label="岗位名称" prop="name"><el-input v-model="formData.name" placeholder="请输入岗位名称" /></el-form-item>
      <el-form-item label="岗位编码" prop="code"><el-input v-model="formData.code" placeholder="请输入岗位编码" :disabled="isEdit" /></el-form-item>
      <el-form-item label="排序" prop="sort"><el-input-number v-model="formData.sort" :min="0" controls-position="right" /></el-form-item>
      <el-form-item label="状态" prop="status">
        <el-radio-group v-model="formData.status"><el-radio :value="1">启用</el-radio><el-radio :value="0">停用</el-radio></el-radio-group>
      </el-form-item>
      <el-form-item label="备注" prop="remark"><el-input v-model="formData.remark" type="textarea" :rows="3" placeholder="请输入备注" /></el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>
