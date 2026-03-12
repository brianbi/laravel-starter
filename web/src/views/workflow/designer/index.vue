<script setup lang="ts">
import { ref, reactive, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ElMessage, ElMessageBox } from "element-plus";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import {
  getWorkflowDefinition,
  updateWorkflowDefinition
} from "@/api/workflow/definition";
import { getRoleList } from "@/api/system/role";
import { getUserList } from "@/api/system/user";
import { getDepartmentTree } from "@/api/system/department";
import type { WorkflowDefinition, WorkflowNode } from "@/api/workflow/types";

import SaveIcon from "~icons/ep/check";
import BackIcon from "~icons/ep/back";
import AddIcon from "~icons/ep/plus";
import DeleteIcon from "~icons/ep/delete";
import EditIcon from "~icons/ep/edit";
import UserIcon from "~icons/ep/user";
import SettingIcon from "~icons/ep/setting";

defineOptions({
  name: "WorkflowDesigner"
});

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const saving = ref(false);
const definition = ref<WorkflowDefinition | null>(null);

// 节点列表
const nodes = ref<WorkflowNode[]>([]);

// 当前编辑的节点
const currentNode = ref<WorkflowNode | null>(null);
const nodeFormVisible = ref(false);

// 节点类型选项
const nodeTypeOptions = [
  { label: "开始节点", value: "start", color: "#67c23a" },
  { label: "审批节点", value: "approval", color: "#409eff" },
  { label: "抄送节点", value: "cc", color: "#909399" },
  { label: "条件分支", value: "condition", color: "#e6a23c" },
  { label: "结束节点", value: "end", color: "#f56c6c" }
];

// 审批人类型选项
const assigneeTypeOptions = [
  { label: "指定用户", value: "user" },
  { label: "指定角色", value: "role" },
  { label: "部门负责人", value: "department_leader" },
  { label: "发起人自选", value: "initiator_select" },
  { label: "发起人本人", value: "initiator" }
];

// 用户、角色、部门数据
const userOptions = ref<Array<{ id: number; name: string }>>([]);
const roleOptions = ref<Array<{ id: number; name: string }>>([]);
const departmentTree = ref<any[]>([]);

// 节点表单
const nodeForm = reactive<{
  id: string;
  type: string;
  name: string;
  assignee_type: string;
  assignee_ids: number[];
}>({
  id: "",
  type: "approval",
  name: "",
  assignee_type: "user",
  assignee_ids: []
});

// 获取节点类型配置
const getNodeTypeConfig = (type: string) => {
  return nodeTypeOptions.find(item => item.value === type) || nodeTypeOptions[1];
};

// 加载流程定义
const loadDefinition = async () => {
  const id = Number(route.query.id);
  if (!id) {
    ElMessage.error("流程ID不能为空");
    router.back();
    return;
  }

  loading.value = true;
  try {
    const res = await getWorkflowDefinition(id);
    definition.value = res.data;
    nodes.value = res.data.nodes || [];

    // 如果没有节点，添加默认的开始和结束节点
    if (nodes.value.length === 0) {
      nodes.value = [
        { id: "start", type: "start", name: "开始" },
        { id: "end", type: "end", name: "结束" }
      ];
    }
  } catch {
    ElMessage.error("加载流程失败");
    router.back();
  } finally {
    loading.value = false;
  }
};

// 加载用户、角色、部门数据
const loadOptions = async () => {
  try {
    const [userRes, roleRes, deptRes] = await Promise.all([
      getUserList({ per_page: 1000 }),
      getRoleList({ per_page: 1000 }),
      getDepartmentTree()
    ]);
    userOptions.value = userRes.data.map((u: any) => ({ id: u.id, name: u.name }));
    roleOptions.value = roleRes.data.map((r: any) => ({ id: r.id, name: r.name }));
    departmentTree.value = deptRes.data;
  } catch {
    // 忽略
  }
};

// 添加节点
const handleAddNode = () => {
  currentNode.value = null;
  Object.assign(nodeForm, {
    id: `node_${Date.now()}`,
    type: "approval",
    name: "",
    assignee_type: "user",
    assignee_ids: []
  });
  nodeFormVisible.value = true;
};

// 编辑节点
const handleEditNode = (node: WorkflowNode) => {
  if (node.type === "start" || node.type === "end") {
    ElMessage.warning("开始和结束节点不可编辑");
    return;
  }
  currentNode.value = node;
  Object.assign(nodeForm, {
    id: node.id,
    type: node.type,
    name: node.name,
    assignee_type: node.assignee_type || "user",
    assignee_ids: node.assignee_ids || []
  });
  nodeFormVisible.value = true;
};

// 删除节点
const handleDeleteNode = async (node: WorkflowNode) => {
  if (node.type === "start" || node.type === "end") {
    ElMessage.warning("开始和结束节点不可删除");
    return;
  }
  try {
    await ElMessageBox.confirm(`确认删除节点「${node.name}」吗？`, "提示", {
      type: "warning"
    });
    const index = nodes.value.findIndex(n => n.id === node.id);
    if (index > -1) {
      nodes.value.splice(index, 1);
    }
  } catch {
    // 取消
  }
};

// 保存节点
const handleSaveNode = () => {
  if (!nodeForm.name) {
    ElMessage.warning("请输入节点名称");
    return;
  }

  const nodeData: WorkflowNode = {
    id: nodeForm.id,
    type: nodeForm.type,
    name: nodeForm.name,
    assignee_type: nodeForm.assignee_type,
    assignee_ids: nodeForm.assignee_ids
  };

  if (currentNode.value) {
    // 编辑
    const index = nodes.value.findIndex(n => n.id === nodeForm.id);
    if (index > -1) {
      nodes.value[index] = nodeData;
    }
  } else {
    // 新增（插入到结束节点之前）
    const endIndex = nodes.value.findIndex(n => n.type === "end");
    if (endIndex > -1) {
      nodes.value.splice(endIndex, 0, nodeData);
    } else {
      nodes.value.push(nodeData);
    }
  }

  nodeFormVisible.value = false;
  ElMessage.success("保存成功");
};

// 保存流程
const handleSave = async () => {
  if (!definition.value) return;

  saving.value = true;
  try {
    await updateWorkflowDefinition(definition.value.id, {
      code: definition.value.code,
      name: definition.value.name,
      description: definition.value.description,
      nodes: nodes.value
    });
    ElMessage.success("保存成功");
  } catch {
    ElMessage.error("保存失败");
  } finally {
    saving.value = false;
  }
};

// 返回
const handleBack = () => {
  router.back();
};

// 获取审批人显示文本
const getAssigneeText = (node: WorkflowNode) => {
  if (!node.assignee_type) return "-";

  const typeLabel =
    assigneeTypeOptions.find(o => o.value === node.assignee_type)?.label || "";

  if (
    node.assignee_type === "user" &&
    node.assignee_ids &&
    node.assignee_ids.length > 0
  ) {
    const names = node.assignee_ids
      .map(id => userOptions.value.find(u => u.id === id)?.name)
      .filter(Boolean);
    return names.length > 0 ? names.join(", ") : typeLabel;
  }

  if (
    node.assignee_type === "role" &&
    node.assignee_ids &&
    node.assignee_ids.length > 0
  ) {
    const names = node.assignee_ids
      .map(id => roleOptions.value.find(r => r.id === id)?.name)
      .filter(Boolean);
    return names.length > 0 ? names.join(", ") : typeLabel;
  }

  return typeLabel;
};

onMounted(() => {
  loadDefinition();
  loadOptions();
});
</script>

<template>
  <div class="workflow-designer" v-loading="loading">
    <!-- 顶部工具栏 -->
    <div class="toolbar">
      <div class="toolbar-left">
        <el-button :icon="useRenderIcon(BackIcon)" @click="handleBack">
          返回
        </el-button>
        <span v-if="definition" class="workflow-title">
          {{ definition.name }} ({{ definition.code }})
        </span>
      </div>
      <div class="toolbar-right">
        <el-button
          type="primary"
          :icon="useRenderIcon(SaveIcon)"
          :loading="saving"
          @click="handleSave"
        >
          保存
        </el-button>
      </div>
    </div>

    <!-- 设计器主体 -->
    <div class="designer-body">
      <!-- 左侧工具面板 -->
      <div class="tool-panel">
        <div class="panel-title">节点类型</div>
        <div class="node-types">
          <div
            v-for="item in nodeTypeOptions"
            :key="item.value"
            class="node-type-item"
            :style="{ borderLeftColor: item.color }"
          >
            {{ item.label }}
          </div>
        </div>
        <el-button
          type="primary"
          class="add-btn"
          :icon="useRenderIcon(AddIcon)"
          @click="handleAddNode"
        >
          添加节点
        </el-button>
      </div>

      <!-- 中间画布 -->
      <div class="canvas">
        <div class="node-list">
          <div
            v-for="(node, index) in nodes"
            :key="node.id"
            class="node-item"
            :class="[`node-${node.type}`]"
          >
            <div
              class="node-icon"
              :style="{ backgroundColor: getNodeTypeConfig(node.type).color }"
            >
              <component
                :is="
                  useRenderIcon(
                    node.type === 'start' || node.type === 'end'
                      ? SettingIcon
                      : UserIcon
                  )
                "
              />
            </div>
            <div class="node-content">
              <div class="node-name">{{ node.name }}</div>
              <div class="node-type">
                {{ getNodeTypeConfig(node.type).label }}
              </div>
              <div
                v-if="node.type === 'approval'"
                class="node-assignee"
              >
                审批人：{{ getAssigneeText(node) }}
              </div>
            </div>
            <div
              v-if="node.type !== 'start' && node.type !== 'end'"
              class="node-actions"
            >
              <el-button
                type="primary"
                link
                size="small"
                :icon="useRenderIcon(EditIcon)"
                @click="handleEditNode(node)"
              />
              <el-button
                type="danger"
                link
                size="small"
                :icon="useRenderIcon(DeleteIcon)"
                @click="handleDeleteNode(node)"
              />
            </div>
            <!-- 连接线 -->
            <div v-if="index < nodes.length - 1" class="node-connector">
              <div class="connector-line" />
              <div class="connector-arrow" />
            </div>
          </div>
        </div>
      </div>

      <!-- 右侧属性面板 -->
      <div class="property-panel">
        <div class="panel-title">流程属性</div>
        <div v-if="definition" class="property-list">
          <div class="property-item">
            <span class="label">编码</span>
            <span class="value">{{ definition.code }}</span>
          </div>
          <div class="property-item">
            <span class="label">名称</span>
            <span class="value">{{ definition.name }}</span>
          </div>
          <div class="property-item">
            <span class="label">版本</span>
            <span class="value">v{{ definition.version }}</span>
          </div>
          <div class="property-item">
            <span class="label">节点数</span>
            <span class="value">{{ nodes.length }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 节点编辑弹窗 -->
    <el-dialog
      v-model="nodeFormVisible"
      :title="currentNode ? '编辑节点' : '添加节点'"
      width="500px"
      destroy-on-close
    >
      <el-form :model="nodeForm" label-width="100px">
        <el-form-item label="节点类型">
          <el-select v-model="nodeForm.type" :disabled="!!currentNode">
            <el-option
              v-for="item in nodeTypeOptions.filter(
                t => t.value !== 'start' && t.value !== 'end'
              )"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="节点名称" required>
          <el-input v-model="nodeForm.name" placeholder="请输入节点名称" />
        </el-form-item>
        <template v-if="nodeForm.type === 'approval'">
          <el-form-item label="审批人类型">
            <el-select v-model="nodeForm.assignee_type">
              <el-option
                v-for="item in assigneeTypeOptions"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </el-form-item>
          <el-form-item
            v-if="nodeForm.assignee_type === 'user'"
            label="指定用户"
          >
            <el-select
              v-model="nodeForm.assignee_ids"
              multiple
              filterable
              placeholder="请选择用户"
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
          <el-form-item
            v-if="nodeForm.assignee_type === 'role'"
            label="指定角色"
          >
            <el-select
              v-model="nodeForm.assignee_ids"
              multiple
              filterable
              placeholder="请选择角色"
              class="w-full"
            >
              <el-option
                v-for="item in roleOptions"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
        </template>
      </el-form>
      <template #footer>
        <el-button @click="nodeFormVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveNode">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style lang="scss" scoped>
.workflow-designer {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 86px);
  background: #f5f7fa;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #fff;
  border-bottom: 1px solid #e4e7ed;

  .toolbar-left {
    display: flex;
    align-items: center;
    gap: 16px;

    .workflow-title {
      font-size: 16px;
      font-weight: 500;
      color: #303133;
    }
  }
}

.designer-body {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.tool-panel,
.property-panel {
  width: 240px;
  background: #fff;
  border-right: 1px solid #e4e7ed;
  padding: 16px;
  overflow-y: auto;

  .panel-title {
    font-size: 14px;
    font-weight: 500;
    color: #303133;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e4e7ed;
  }
}

.property-panel {
  border-right: none;
  border-left: 1px solid #e4e7ed;

  .property-list {
    .property-item {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px dashed #e4e7ed;

      .label {
        color: #909399;
      }

      .value {
        color: #303133;
      }
    }
  }
}

.node-types {
  .node-type-item {
    padding: 8px 12px;
    margin-bottom: 8px;
    background: #f5f7fa;
    border-radius: 4px;
    border-left: 3px solid;
    font-size: 13px;
    color: #606266;
  }
}

.add-btn {
  width: 100%;
  margin-top: 16px;
}

.canvas {
  flex: 1;
  padding: 24px;
  overflow: auto;
  display: flex;
  justify-content: center;
}

.node-list {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 400px;
}

.node-item {
  position: relative;
  display: flex;
  align-items: center;
  width: 320px;
  padding: 16px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  }

  .node-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
  }

  .node-content {
    flex: 1;
    margin-left: 12px;
    overflow: hidden;

    .node-name {
      font-size: 14px;
      font-weight: 500;
      color: #303133;
    }

    .node-type {
      font-size: 12px;
      color: #909399;
      margin-top: 4px;
    }

    .node-assignee {
      font-size: 12px;
      color: #606266;
      margin-top: 4px;
    }
  }

  .node-actions {
    display: flex;
    gap: 4px;
  }

  .node-connector {
    position: absolute;
    left: 50%;
    bottom: -32px;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;

    .connector-line {
      width: 2px;
      height: 24px;
      background: #dcdfe6;
    }

    .connector-arrow {
      width: 0;
      height: 0;
      border-left: 6px solid transparent;
      border-right: 6px solid transparent;
      border-top: 8px solid #dcdfe6;
    }
  }
}

.node-item + .node-item {
  margin-top: 32px;
}

.node-start,
.node-end {
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e7ed 100%);
}
</style>
