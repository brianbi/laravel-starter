<script setup lang="ts">
import { ref, watch, computed, reactive } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance } from "element-plus";
import { getEnabledWorkflows } from "@/api/workflow/definition";
import { startWorkflow } from "@/api/workflow/instance";
import type { WorkflowDefinition } from "@/api/workflow/types";

const props = defineProps<{
  visible: boolean;
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
const submitting = ref(false);
const workflows = ref<WorkflowDefinition[]>([]);

const formData = reactive({
  definition_code: "",
  form_data: {} as Record<string, any>
});

// 加载可用流程
const loadWorkflows = async () => {
  loading.value = true;
  try {
    const res = await getEnabledWorkflows();
    workflows.value = res.data;
  } catch {
    workflows.value = [];
  } finally {
    loading.value = false;
  }
};

// 提交
const handleSubmit = async () => {
  if (!formData.definition_code) {
    ElMessage.warning("请选择流程");
    return;
  }

  submitting.value = true;
  try {
    await startWorkflow(formData);
    ElMessage.success("发起成功");
    emit("success");
  } catch {
    // 错误处理
  } finally {
    submitting.value = false;
  }
};

// 关闭
const handleClose = () => {
  formData.definition_code = "";
  formData.form_data = {};
  dialogVisible.value = false;
};

// 监听弹窗打开
watch(
  () => props.visible,
  val => {
    if (val) {
      loadWorkflows();
    }
  }
);
</script>

<template>
  <el-dialog
    v-model="dialogVisible"
    title="发起流程"
    width="500px"
    destroy-on-close
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="formData"
      label-width="100px"
      v-loading="loading"
    >
      <el-form-item label="选择流程" required>
        <el-select
          v-model="formData.definition_code"
          placeholder="请选择流程"
          class="w-full"
          filterable
        >
          <el-option
            v-for="item in workflows"
            :key="item.code"
            :label="item.name"
            :value="item.code"
          >
            <div class="flex justify-between">
              <span>{{ item.name }}</span>
              <span class="text-gray-400 text-xs">{{ item.code }}</span>
            </div>
          </el-option>
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-alert
          type="info"
          :closable="false"
          show-icon
          title="提示"
          description="选择流程后将根据流程定义的表单填写相关信息"
        />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="handleSubmit">
        发起
      </el-button>
    </template>
  </el-dialog>
</template>
