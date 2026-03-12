<script setup lang="ts">
import { ref, reactive, watch, computed } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import {
  createFormDefinition,
  updateFormDefinition
} from "@/api/form/definition";
import type {
  FormDefinition,
  FormDefinitionForm,
  FormField
} from "@/api/form/types";

const props = defineProps<{
  visible: boolean;
  title: string;
  row: FormDefinition | null;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
  (e: "success"): void;
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

const formRef = ref<FormInstance>();
const loading = ref(false);

const defaultForm: FormDefinitionForm = {
  code: "",
  name: "",
  description: "",
  fields: []
};

const formData = reactive<FormDefinitionForm>({ ...defaultForm });

const rules: FormRules = {
  code: [
    { required: true, message: "请输入表单编码", trigger: "blur" },
    {
      pattern: /^[a-zA-Z][a-zA-Z0-9_]*$/,
      message: "编码只能包含字母、数字和下划线，且必须以字母开头",
      trigger: "blur"
    }
  ],
  name: [
    { required: true, message: "请输入表单名称", trigger: "blur" },
    { min: 2, max: 50, message: "长度为 2-50 个字符", trigger: "blur" }
  ]
};

const isEdit = computed(() => !!props.row?.id);

// 监听弹窗打开
watch(
  () => props.visible,
  val => {
    if (val) {
      if (props.row) {
        Object.assign(formData, {
          code: props.row.code,
          name: props.row.name,
          description: props.row.description || "",
          fields: props.row.fields || []
        });
      } else {
        Object.assign(formData, { ...defaultForm });
      }
    }
  }
);

// 提交
const handleSubmit = async () => {
  try {
    await formRef.value?.validate();
    loading.value = true;

    if (isEdit.value) {
      await updateFormDefinition(props.row!.id, formData);
      ElMessage.success("更新成功");
    } else {
      await createFormDefinition(formData);
      ElMessage.success("创建成功");
    }
    emit("success");
  } catch {
    // 验证失败或请求失败
  } finally {
    loading.value = false;
  }
};

// 关闭
const handleClose = () => {
  formRef.value?.resetFields();
  dialogVisible.value = false;
};
</script>

<template>
  <el-dialog
    v-model="dialogVisible"
    :title="title"
    width="600px"
    destroy-on-close
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="formData"
      :rules="rules"
      label-width="100px"
    >
      <el-form-item label="表单编码" prop="code">
        <el-input
          v-model="formData.code"
          placeholder="请输入表单编码"
          :disabled="isEdit"
        />
      </el-form-item>
      <el-form-item label="表单名称" prop="name">
        <el-input v-model="formData.name" placeholder="请输入表单名称" />
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
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="loading" @click="handleSubmit">
        确定
      </el-button>
    </template>
  </el-dialog>
</template>
