<script setup lang="ts">
import { ref, reactive, watch, computed } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import {
  createWorkflowDefinition,
  updateWorkflowDefinition
} from "@/api/workflow/definition";
import type {
  WorkflowDefinition,
  WorkflowDefinitionForm
} from "@/api/workflow/types";

const props = defineProps<{
  visible: boolean;
  title: string;
  row: WorkflowDefinition | null;
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

const defaultForm: WorkflowDefinitionForm = {
  code: "",
  name: "",
  description: "",
  form_type: "custom"
};

const formData = reactive<WorkflowDefinitionForm>({ ...defaultForm });

const rules: FormRules = {
  code: [
    { required: true, message: "请输入流程编码", trigger: "blur" },
    {
      pattern: /^[a-zA-Z][a-zA-Z0-9_]*$/,
      message: "编码只能包含字母、数字和下划线，且必须以字母开头",
      trigger: "blur"
    }
  ],
  name: [
    { required: true, message: "请输入流程名称", trigger: "blur" },
    { min: 2, max: 50, message: "长度为 2-50 个字符", trigger: "blur" }
  ]
};

const formTypeOptions = [
  { label: "自定义表单", value: "custom" },
  { label: "关联表单定义", value: "form" }
];

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
          form_type: props.row.form_type || "custom"
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
      await updateWorkflowDefinition(props.row!.id, formData);
      ElMessage.success("更新成功");
    } else {
      await createWorkflowDefinition(formData);
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
    width="500px"
    destroy-on-close
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="formData"
      :rules="rules"
      label-width="100px"
    >
      <el-form-item label="流程编码" prop="code">
        <el-input
          v-model="formData.code"
          placeholder="请输入流程编码"
          :disabled="isEdit"
        />
      </el-form-item>
      <el-form-item label="流程名称" prop="name">
        <el-input v-model="formData.name" placeholder="请输入流程名称" />
      </el-form-item>
      <el-form-item label="表单类型" prop="form_type">
        <el-radio-group v-model="formData.form_type">
          <el-radio
            v-for="item in formTypeOptions"
            :key="item.value"
            :value="item.value"
          >
            {{ item.label }}
          </el-radio>
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
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="loading" @click="handleSubmit">
        确定
      </el-button>
    </template>
  </el-dialog>
</template>
