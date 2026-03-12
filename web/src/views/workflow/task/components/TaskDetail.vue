<script setup lang="ts">
import { ref, watch, computed, reactive } from "vue";
import { ElMessage } from "element-plus";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import {
  getWorkflowTask,
  approveTask,
  rejectTask,
  delegateTask
} from "@/api/workflow/task";
import { getUserList } from "@/api/system/user";
import type { WorkflowTask } from "@/api/workflow/types";

import CheckIcon from "~icons/ep/check";
import CloseIcon from "~icons/ep/close";
import SwitchIcon from "~icons/ep/switch";

const props = defineProps<{
  visible: boolean;
  task: WorkflowTask | null;
  readonly?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
  (e: "success"): void;
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

const loading = ref(false);
const submitting = ref(false);
const detail = ref<any>(null);
const userOptions = ref<Array<{ id: number; name: string }>>([]);

// 审批表单
const approvalForm = reactive({
  comment: "",
  action: "" as "approve" | "reject" | "delegate",
  target_user: undefined as number | undefined
});

// 获取状态标签类型
const getStatusType = (status: number) => {
  const typeMap: Record<number, string> = {
    1: "primary",
    2: "success",
    3: "danger",
    4: "info"
  };
  return typeMap[status] || "info";
};

// 加载任务详情
const loadDetail = async () => {
  if (!props.task) return;

  loading.value = true;
  try {
    const res = await getWorkflowTask(props.task.id);
    detail.value = res.data;
  } catch {
    ElMessage.error("加载失败");
  } finally {
    loading.value = false;
  }
};

// 加载用户列表（用于转办）
const loadUsers = async () => {
  try {
    const res = await getUserList({ per_page: 1000 });
    userOptions.value = res.data.map((u: any) => ({ id: u.id, name: u.name }));
  } catch {
    userOptions.value = [];
  }
};

// 通过
const handleApprove = async () => {
  submitting.value = true;
  try {
    await approveTask(props.task!.id, { comment: approvalForm.comment });
    ElMessage.success("审批通过");
    emit("success");
  } catch {
    // 错误处理
  } finally {
    submitting.value = false;
  }
};

// 拒绝
const handleReject = async () => {
  if (!approvalForm.comment) {
    ElMessage.warning("请输入拒绝原因");
    return;
  }
  submitting.value = true;
  try {
    await rejectTask(props.task!.id, { comment: approvalForm.comment });
    ElMessage.success("已拒绝");
    emit("success");
  } catch {
    // 错误处理
  } finally {
    submitting.value = false;
  }
};

// 转办
const handleDelegate = async () => {
  if (!approvalForm.target_user) {
    ElMessage.warning("请选择转办人");
    return;
  }
  submitting.value = true;
  try {
    await delegateTask(props.task!.id, {
      comment: approvalForm.comment,
      target_user: approvalForm.target_user
    });
    ElMessage.success("转办成功");
    emit("success");
  } catch {
    // 错误处理
  } finally {
    submitting.value = false;
  }
};

// 重置表单
const resetForm = () => {
  approvalForm.comment = "";
  approvalForm.action = "";
  approvalForm.target_user = undefined;
};

// 监听弹窗打开
watch(
  () => props.visible,
  val => {
    if (val && props.task) {
      loadDetail();
      loadUsers();
      resetForm();
    }
  }
);
</script>

<template>
  <el-dialog
    v-model="dialogVisible"
    title="任务详情"
    width="700px"
    destroy-on-close
  >
    <div v-loading="loading">
      <!-- 任务信息 -->
      <el-descriptions :column="2" border class="mb-4">
        <el-descriptions-item label="流程名称">
          {{ detail?.instance?.definition?.name || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="节点名称">
          {{ detail?.node_name || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="发起人">
          {{ detail?.instance?.initiator?.name || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="流程状态">
          <el-tag
            :type="getStatusType(detail?.instance?.status)"
            size="small"
          >
            {{ detail?.instance?.status_text }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="任务状态">
          <el-tag :type="getStatusType(detail?.status)" size="small">
            {{ detail?.status_text }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="创建时间">
          {{ detail?.created_at || "-" }}
        </el-descriptions-item>
      </el-descriptions>

      <!-- 表单数据 -->
      <el-card
        v-if="detail?.instance?.form_data"
        shadow="never"
        class="mb-4"
      >
        <template #header>
          <span>表单数据</span>
        </template>
        <el-descriptions :column="2" border>
          <el-descriptions-item
            v-for="(value, key) in detail.instance.form_data"
            :key="key"
            :label="String(key)"
          >
            {{ value }}
          </el-descriptions-item>
        </el-descriptions>
      </el-card>

      <!-- 审批操作 -->
      <el-card v-if="!readonly && detail?.status === 0" shadow="never">
        <template #header>
          <span>审批操作</span>
        </template>
        <el-form :model="approvalForm" label-width="80px">
          <el-form-item label="审批意见">
            <el-input
              v-model="approvalForm.comment"
              type="textarea"
              :rows="3"
              placeholder="请输入审批意见"
            />
          </el-form-item>
          <el-form-item v-if="approvalForm.action === 'delegate'" label="转办人">
            <el-select
              v-model="approvalForm.target_user"
              filterable
              placeholder="请选择转办人"
              class="w-full"
            >
              <el-option
                v-for="item in userOptions"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-space>
              <el-button
                type="success"
                :icon="useRenderIcon(CheckIcon)"
                :loading="submitting"
                @click="handleApprove"
              >
                通过
              </el-button>
              <el-button
                type="danger"
                :icon="useRenderIcon(CloseIcon)"
                :loading="submitting"
                @click="handleReject"
              >
                拒绝
              </el-button>
              <el-button
                v-if="approvalForm.action !== 'delegate'"
                type="warning"
                :icon="useRenderIcon(SwitchIcon)"
                @click="approvalForm.action = 'delegate'"
              >
                转办
              </el-button>
              <el-button
                v-else
                type="primary"
                :loading="submitting"
                @click="handleDelegate"
              >
                确认转办
              </el-button>
            </el-space>
          </el-form-item>
        </el-form>
      </el-card>
    </div>
    <template #footer>
      <el-button @click="dialogVisible = false">关闭</el-button>
    </template>
  </el-dialog>
</template>
