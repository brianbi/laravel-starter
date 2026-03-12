<script setup lang="ts">
import { ref, computed, watch } from "vue";
import ReForm from "./index.vue";
import type { ReFormDialogProps, FormField } from "./types";

defineOptions({
  name: "ReFormDialog"
});

const props = withDefaults(defineProps<ReFormDialogProps>(), {
  title: "表单",
  width: "600px",
  visible: false,
  fullscreen: false,
  closeOnClickModal: false,
  showClose: true,
  appendToBody: true,
  showActions: false, // 弹窗内不显示表单按钮，用弹窗自带的
  submitText: "确定",
  cancelText: "取消"
});

const emit = defineEmits<{
  "update:visible": [value: boolean];
  "update:modelValue": [value: Record<string, any>];
  submit: [value: Record<string, any>];
  cancel: [];
  close: [];
}>();

// 弹窗可见性
const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

// 表单数据
const formData = ref<Record<string, any>>({ ...props.modelValue });

// 表单 ref
const formRef = ref<InstanceType<typeof ReForm>>();

// 监听外部数据变化
watch(
  () => props.modelValue,
  val => {
    if (val) {
      formData.value = { ...val };
    }
  },
  { deep: true }
);

// 同步表单数据变化
watch(
  formData,
  val => {
    emit("update:modelValue", val);
  },
  { deep: true }
);

// 提交
const handleSubmit = async () => {
  try {
    await formRef.value?.validate();
    emit("submit", { ...formData.value });
  } catch (e) {
    // 校验失败
  }
};

// 关闭
const handleClose = () => {
  dialogVisible.value = false;
  emit("close");
};

// 取消
const handleCancel = () => {
  dialogVisible.value = false;
  emit("cancel");
};

// 打开弹窗时重置
const handleOpen = () => {
  if (props.modelValue) {
    formData.value = { ...props.modelValue };
  }
};

// 暴露方法
defineExpose({
  formRef,
  open: () => {
    dialogVisible.value = true;
  },
  close: handleClose,
  validate: () => formRef.value?.validate(),
  resetFields: () => formRef.value?.resetFields(),
  getFormData: () => formRef.value?.getFormData(),
  setFormData: (data: Record<string, any>) => formRef.value?.setFormData(data)
});
</script>

<template>
  <el-dialog
    v-model="dialogVisible"
    :title="title"
    :width="width"
    :fullscreen="fullscreen"
    :close-on-click-modal="closeOnClickModal"
    :show-close="showClose"
    :append-to-body="appendToBody"
    destroy-on-close
    @open="handleOpen"
    @close="handleClose"
  >
    <ReForm
      ref="formRef"
      v-model="formData"
      :fields="fields"
      :label-width="labelWidth"
      :label-position="labelPosition"
      :inline="inline"
      :disabled="disabled"
      :gutter="gutter"
      :size="size"
      :show-actions="false"
      :loading="loading"
    >
      <!-- 透传插槽 -->
      <template v-for="(_, name) in $slots" :key="name" #[name]="slotData">
        <slot :name="name" v-bind="slotData || {}" />
      </template>
    </ReForm>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="handleCancel">{{ cancelText }}</el-button>
        <el-button type="primary" :loading="loading" @click="handleSubmit">
          {{ submitText }}
        </el-button>
        <slot name="footer-extra" />
      </span>
    </template>
  </el-dialog>
</template>
