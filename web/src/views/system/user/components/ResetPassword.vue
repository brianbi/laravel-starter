<script setup lang="ts">
import { ref, reactive, computed } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import { resetUserPassword } from "@/api/system/user";

const props = defineProps<{
  visible: boolean;
  userId: number;
}>();

const emit = defineEmits<{
  "update:visible": [value: boolean];
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

const formRef = ref<FormInstance>();
const submitLoading = ref(false);

const formData = reactive({
  password: "",
  confirmPassword: ""
});

const validateConfirmPassword = (rule: any, value: string, callback: any) => {
  if (value !== formData.password) {
    callback(new Error("两次输入的密码不一致"));
  } else {
    callback();
  }
};

const rules = reactive<FormRules>({
  password: [
    { required: true, message: "请输入新密码", trigger: "blur" },
    { min: 6, max: 20, message: "长度在 6 到 20 个字符", trigger: "blur" }
  ],
  confirmPassword: [
    { required: true, message: "请确认密码", trigger: "blur" },
    { validator: validateConfirmPassword, trigger: "blur" }
  ]
});

const resetForm = () => {
  formData.password = "";
  formData.confirmPassword = "";
  formRef.value?.clearValidate();
};

const handleSubmit = async () => {
  const valid = await formRef.value?.validate().catch(() => false);
  if (!valid) return;

  submitLoading.value = true;
  try {
    await resetUserPassword(props.userId, formData.password);
    ElMessage.success("密码重置成功");
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
    title="重置密码"
    width="400px"
    :close-on-click-modal="false"
    destroy-on-close
    @close="resetForm"
  >
    <el-form ref="formRef" :model="formData" :rules="rules" label-width="100px">
      <el-form-item label="新密码" prop="password">
        <el-input
          v-model="formData.password"
          type="password"
          placeholder="请输入新密码"
          show-password
        />
      </el-form-item>
      <el-form-item label="确认密码" prop="confirmPassword">
        <el-input
          v-model="formData.confirmPassword"
          type="password"
          placeholder="请再次输入密码"
          show-password
        />
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
