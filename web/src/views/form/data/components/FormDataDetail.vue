<script setup lang="ts">
import { computed } from "vue";
import type { FormData } from "@/api/form/types";

const props = defineProps<{
  visible: boolean;
  data: FormData | null;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

// 获取字段标签
const getFieldLabel = (fieldName: string) => {
  if (props.data?.form?.fields) {
    const field = props.data.form.fields.find(f => f.name === fieldName);
    return field?.label || fieldName;
  }
  return fieldName;
};
</script>

<template>
  <el-dialog
    v-model="dialogVisible"
    title="表单数据详情"
    width="700px"
    destroy-on-close
  >
    <el-descriptions :column="2" border>
      <el-descriptions-item label="ID">
        {{ data?.id }}
      </el-descriptions-item>
      <el-descriptions-item label="表单名称">
        {{ data?.form?.name || "-" }}
      </el-descriptions-item>
      <el-descriptions-item label="流程实例ID">
        {{ data?.instance_id || "-" }}
      </el-descriptions-item>
      <el-descriptions-item label="提交人">
        {{ data?.creator?.name || "-" }}
      </el-descriptions-item>
      <el-descriptions-item label="提交时间">
        {{ data?.created_at || "-" }}
      </el-descriptions-item>
      <el-descriptions-item label="更新时间">
        {{ data?.updated_at || "-" }}
      </el-descriptions-item>
    </el-descriptions>

    <!-- 表单数据 -->
    <el-card shadow="never" class="mt-4">
      <template #header>
        <span>表单数据</span>
      </template>
      <el-descriptions :column="2" border>
        <el-descriptions-item
          v-for="(value, key) in data?.data"
          :key="key"
          :label="getFieldLabel(String(key))"
        >
          {{ value }}
        </el-descriptions-item>
      </el-descriptions>
    </el-card>

    <template #footer>
      <el-button @click="dialogVisible = false">关闭</el-button>
    </template>
  </el-dialog>
</template>
