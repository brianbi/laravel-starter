<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { ElMessage } from "element-plus";
import {
  getWorkflowInstance,
  getWorkflowTimeline
} from "@/api/workflow/instance";
import type { WorkflowInstance, TimelineItem } from "@/api/workflow/types";

import CheckIcon from "~icons/ep/check";
import CloseIcon from "~icons/ep/close";
import ClockIcon from "~icons/ep/clock";

const props = defineProps<{
  visible: boolean;
  instance: WorkflowInstance | null;
}>();

const emit = defineEmits<{
  (e: "update:visible", value: boolean): void;
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

const loading = ref(false);
const detail = ref<any>(null);
const timeline = ref<TimelineItem[]>([]);

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

// 获取时间线项图标
const getTimelineIcon = (action: string) => {
  if (action === "approve" || action === "start") return CheckIcon;
  if (action === "reject") return CloseIcon;
  return ClockIcon;
};

// 获取时间线项类型
const getTimelineType = (action: string) => {
  if (action === "approve" || action === "start" || action === "complete")
    return "success";
  if (action === "reject") return "danger";
  return "primary";
};

// 加载详情和时间线
const loadDetail = async () => {
  if (!props.instance) return;

  loading.value = true;
  try {
    const [detailRes, timelineRes] = await Promise.all([
      getWorkflowInstance(props.instance.id),
      getWorkflowTimeline(props.instance.id)
    ]);
    detail.value = detailRes.data;
    timeline.value = timelineRes.data || [];
  } catch {
    ElMessage.error("加载失败");
  } finally {
    loading.value = false;
  }
};

// 监听弹窗打开
watch(
  () => props.visible,
  val => {
    if (val && props.instance) {
      loadDetail();
    }
  }
);
</script>

<template>
  <el-dialog
    v-model="dialogVisible"
    title="流程详情"
    width="700px"
    destroy-on-close
  >
    <div v-loading="loading">
      <!-- 基本信息 -->
      <el-descriptions :column="2" border class="mb-4">
        <el-descriptions-item label="流程名称">
          {{ detail?.definition?.name || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="流程编码">
          {{ detail?.definition?.code || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="发起人">
          {{ detail?.initiator?.name || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="getStatusType(detail?.status)" size="small">
            {{ detail?.status_text }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="当前节点">
          {{ detail?.current_node_id || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="发起时间">
          {{ detail?.started_at || "-" }}
        </el-descriptions-item>
        <el-descriptions-item label="完成时间" :span="2">
          {{ detail?.completed_at || "-" }}
        </el-descriptions-item>
      </el-descriptions>

      <!-- 表单数据 -->
      <el-card v-if="detail?.form_data" shadow="never" class="mb-4">
        <template #header>
          <span>表单数据</span>
        </template>
        <el-descriptions :column="2" border>
          <el-descriptions-item
            v-for="(value, key) in detail.form_data"
            :key="key"
            :label="String(key)"
          >
            {{ value }}
          </el-descriptions-item>
        </el-descriptions>
      </el-card>

      <!-- 审批时间线 -->
      <el-card shadow="never">
        <template #header>
          <span>审批记录</span>
        </template>
        <el-timeline v-if="timeline.length > 0">
          <el-timeline-item
            v-for="item in timeline"
            :key="item.id"
            :type="getTimelineType(item.action)"
            :timestamp="item.created_at"
            placement="top"
          >
            <div class="timeline-content">
              <div class="timeline-header">
                <span class="node-name">{{ item.node_name }}</span>
                <el-tag size="small" :type="getTimelineType(item.action)">
                  {{ item.action_text }}
                </el-tag>
              </div>
              <div class="timeline-operator">
                处理人：{{ item.operator_name }}
              </div>
              <div v-if="item.comment" class="timeline-comment">
                意见：{{ item.comment }}
              </div>
            </div>
          </el-timeline-item>
        </el-timeline>
        <el-empty v-else description="暂无审批记录" :image-size="60" />
      </el-card>
    </div>
    <template #footer>
      <el-button @click="dialogVisible = false">关闭</el-button>
    </template>
  </el-dialog>
</template>

<style lang="scss" scoped>
.timeline-content {
  .timeline-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;

    .node-name {
      font-weight: 500;
      color: #303133;
    }
  }

  .timeline-operator {
    font-size: 13px;
    color: #606266;
  }

  .timeline-comment {
    font-size: 13px;
    color: #909399;
    margin-top: 4px;
  }
}
</style>
