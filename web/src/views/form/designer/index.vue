<script setup lang="ts">
import { ref, reactive, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ElMessage, ElMessageBox } from "element-plus";
import { useRenderIcon } from "@/components/ReIcon/src/hooks";
import {
  getFormDefinition,
  updateFormDefinition,
  getFieldTypes
} from "@/api/form/definition";
import type { FormDefinition, FormField, FieldType } from "@/api/form/types";

import SaveIcon from "~icons/ep/check";
import BackIcon from "~icons/ep/back";
import AddIcon from "~icons/ep/plus";
import DeleteIcon from "~icons/ep/delete";
import EditIcon from "~icons/ep/edit";
import DragIcon from "~icons/ep/operation";

defineOptions({
  name: "FormDesigner"
});

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const saving = ref(false);
const definition = ref<FormDefinition | null>(null);
const fieldTypes = ref<FieldType[]>([]);

// 字段列表
const fields = ref<FormField[]>([]);

// 当前编辑的字段
const currentField = ref<FormField | null>(null);
const fieldFormVisible = ref(false);

// 字段表单
const fieldForm = reactive<FormField>({
  name: "",
  label: "",
  type: "input",
  placeholder: "",
  default_value: "",
  required: false,
  rules: {},
  options: []
});

// 加载表单定义
const loadDefinition = async () => {
  const id = Number(route.query.id);
  if (!id) {
    ElMessage.error("表单ID不能为空");
    router.back();
    return;
  }

  loading.value = true;
  try {
    const res = await getFormDefinition(id);
    definition.value = res.data;
    fields.value = res.data.fields || [];
  } catch {
    ElMessage.error("加载表单失败");
    router.back();
  } finally {
    loading.value = false;
  }
};

// 加载字段类型
const loadFieldTypes = async () => {
  try {
    const res = await getFieldTypes();
    fieldTypes.value = res.data;
  } catch {
    fieldTypes.value = [];
  }
};

// 添加字段
const handleAddField = () => {
  currentField.value = null;
  Object.assign(fieldForm, {
    name: "",
    label: "",
    type: "input",
    placeholder: "",
    default_value: "",
    required: false,
    rules: {},
    options: []
  });
  fieldFormVisible.value = true;
};

// 编辑字段
const handleEditField = (field: FormField, index: number) => {
  currentField.value = field;
  Object.assign(fieldForm, field);
  fieldFormVisible.value = true;
};

// 删除字段
const handleDeleteField = async (field: FormField, index: number) => {
  try {
    await ElMessageBox.confirm(`确认删除字段「${field.label}」吗？`, "提示", {
      type: "warning"
    });
    fields.value.splice(index, 1);
  } catch {
    // 取消
  }
};

// 保存字段
const handleSaveField = () => {
  if (!fieldForm.name || !fieldForm.label) {
    ElMessage.warning("请输入字段名称和标签");
    return;
  }

  // 检查字段名称是否重复
  if (
    fields.value.some(
      f => f !== currentField.value && f.name === fieldForm.name
    )
  ) {
    ElMessage.warning("字段名称不能重复");
    return;
  }

  const fieldData: FormField = { ...fieldForm };

  // 设置默认属性
  const fieldType = fieldTypes.value.find(t => t.type === fieldData.type);
  if (fieldType && fieldType.defaultProps) {
    fieldData.props = { ...fieldType.defaultProps, ...fieldData.props };
  }

  if (currentField.value) {
    // 编辑
    const index = fields.value.findIndex(f => f.name === currentField.value?.name);
    if (index > -1) {
      fields.value[index] = fieldData;
    }
  } else {
    // 新增
    fields.value.push(fieldData);
  }

  fieldFormVisible.value = false;
  ElMessage.success("保存成功");
};

// 保存表单
const handleSave = async () => {
  if (!definition.value) return;

  saving.value = true;
  try {
    await updateFormDefinition(definition.value.id, {
      code: definition.value.code,
      name: definition.value.name,
      description: definition.value.description,
      fields: fields.value
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

// 获取字段类型标签
const getFieldTypeLabel = (type: string) => {
  const field = fieldTypes.value.find(t => t.type === type);
  return field ? field.label : type;
};

onMounted(() => {
  loadDefinition();
  loadFieldTypes();
});
</script>

<template>
  <div class="form-designer" v-loading="loading">
    <!-- 顶部工具栏 -->
    <div class="toolbar">
      <div class="toolbar-left">
        <el-button :icon="useRenderIcon(BackIcon)" @click="handleBack">
          返回
        </el-button>
        <span v-if="definition" class="form-title">
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
        <div class="panel-title">字段类型</div>
        <div class="field-types">
          <div
            v-for="item in fieldTypes"
            :key="item.type"
            class="field-type-item"
            draggable
            @dragstart="($event) => ($event.dataTransfer!.setData('fieldType', JSON.stringify(item)))"
          >
            <component :is="useRenderIcon(item.icon)" class="mr-2" />
            {{ item.label }}
          </div>
        </div>
        <el-button
          type="primary"
          class="add-btn"
          :icon="useRenderIcon(AddIcon)"
          @click="handleAddField"
        >
          添加字段
        </el-button>
      </div>

      <!-- 中间画布 -->
      <div class="canvas">
        <div class="field-list">
          <div
            v-for="(field, index) in fields"
            :key="field.name"
            class="field-item"
          >
            <div class="field-header">
              <div class="field-info">
                <component :is="useRenderIcon(DragIcon)" class="drag-icon mr-2" />
                <span class="field-label">{{ field.label }}</span>
                <el-tag size="small" type="info" class="ml-2">
                  {{ getFieldTypeLabel(field.type) }}
                </el-tag>
                <el-tag v-if="field.required" size="small" type="danger" class="ml-2">
                  必填
                </el-tag>
              </div>
              <div class="field-actions">
                <el-button
                  type="primary"
                  link
                  size="small"
                  :icon="useRenderIcon(EditIcon)"
                  @click="handleEditField(field, index)"
                />
                <el-button
                  type="danger"
                  link
                  size="small"
                  :icon="useRenderIcon(DeleteIcon)"
                  @click="handleDeleteField(field, index)"
                />
              </div>
            </div>
            <div class="field-preview">
              <!-- 根据字段类型渲染预览 -->
              <el-input
                v-if="field.type === 'input'"
                v-model="field.default_value"
                :placeholder="field.placeholder || ''"
                :disabled="true"
              />
              <el-input
                v-else-if="field.type === 'textarea'"
                v-model="field.default_value"
                type="textarea"
                :rows="2"
                :placeholder="field.placeholder || ''"
                :disabled="true"
              />
              <el-input-number
                v-else-if="field.type === 'number'"
                v-model="field.default_value"
                :disabled="true"
              />
              <el-select
                v-else-if="field.type === 'select'"
                v-model="field.default_value"
                :placeholder="field.placeholder || ''"
                :disabled="true"
                class="w-full"
              >
                <el-option
                  v-for="option in field.options || []"
                  :key="option.value"
                  :label="option.label"
                  :value="option.value"
                />
              </el-select>
              <el-radio-group
                v-else-if="field.type === 'radio'"
                v-model="field.default_value"
                :disabled="true"
              >
                <el-radio
                  v-for="option in field.options || []"
                  :key="option.value"
                  :label="option.value"
                >
                  {{ option.label }}
                </el-radio>
              </el-radio-group>
              <el-checkbox-group
                v-else-if="field.type === 'checkbox'"
                v-model="field.default_value"
                :disabled="true"
              >
                <el-checkbox
                  v-for="option in field.options || []"
                  :key="option.value"
                  :label="option.value"
                >
                  {{ option.label }}
                </el-checkbox>
              </el-checkbox-group>
              <el-date-picker
                v-else-if="field.type === 'date'"
                v-model="field.default_value"
                type="date"
                :placeholder="field.placeholder || ''"
                :disabled="true"
                class="w-full"
              />
              <el-date-picker
                v-else-if="field.type === 'datetime'"
                v-model="field.default_value"
                type="datetime"
                :placeholder="field.placeholder || ''"
                :disabled="true"
                class="w-full"
              />
              <el-switch
                v-else-if="field.type === 'switch'"
                v-model="field.default_value"
                :disabled="true"
              />
              <div v-else class="text-gray-500 text-sm">
                {{ getFieldTypeLabel(field.type) }} 预览
              </div>
            </div>
          </div>
          <el-empty v-if="fields.length === 0" description="暂无字段，请添加" :image-size="60" />
        </div>
      </div>

      <!-- 右侧属性面板 -->
      <div class="property-panel">
        <div class="panel-title">表单属性</div>
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
            <span class="label">字段数</span>
            <span class="value">{{ fields.length }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 字段编辑弹窗 -->
    <el-dialog
      v-model="fieldFormVisible"
      :title="currentField ? '编辑字段' : '添加字段'"
      width="600px"
      destroy-on-close
    >
      <el-form :model="fieldForm" label-width="100px">
        <el-form-item label="字段类型">
          <el-select v-model="fieldForm.type" :disabled="!!currentField">
            <el-option
              v-for="item in fieldTypes"
              :key="item.type"
              :label="item.label"
              :value="item.type"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="字段名称" required>
          <el-input v-model="fieldForm.name" placeholder="请输入字段名称（英文标识）" />
        </el-form-item>
        <el-form-item label="字段标签" required>
          <el-input v-model="fieldForm.label" placeholder="请输入字段标签（显示名称）" />
        </el-form-item>
        <el-form-item label="占位符">
          <el-input v-model="fieldForm.placeholder" placeholder="请输入占位符" />
        </el-form-item>
        <el-form-item label="默认值">
          <el-input v-model="fieldForm.default_value" placeholder="请输入默认值" />
        </el-form-item>
        <el-form-item label="是否必填">
          <el-switch v-model="fieldForm.required" />
        </el-form-item>
        <el-form-item
          v-if="['select', 'radio', 'checkbox'].includes(fieldForm.type)"
          label="选项配置"
        >
          <el-button
            type="primary"
            link
            @click="fieldForm.options = fieldForm.options || []; fieldForm.options.push({ label: '', value: '' })"
          >
            + 添加选项
          </el-button>
          <div class="mt-2">
            <div
              v-for="(option, idx) in fieldForm.options || []"
              :key="idx"
              class="flex items-center mb-2"
            >
              <el-input
                v-model="option.label"
                placeholder="标签"
                class="mr-2"
                style="width: 120px"
              />
              <el-input
                v-model="option.value"
                placeholder="值"
                class="mr-2"
                style="width: 120px"
              />
              <el-button
                type="danger"
                link
                @click="fieldForm.options?.splice(idx, 1)"
              >
                删除
              </el-button>
            </div>
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="fieldFormVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSaveField">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style lang="scss" scoped>
.form-designer {
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

    .form-title {
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
  width: 260px;
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

.field-types {
  .field-type-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    margin-bottom: 8px;
    background: #f5f7fa;
    border-radius: 4px;
    font-size: 13px;
    color: #606266;
    cursor: grab;

    &:active {
      cursor: grabbing;
    }
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

.field-list {
  width: 100%;
  max-width: 600px;
}

.field-item {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  padding: 16px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;
  margin-bottom: 16px;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  }

  .field-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;

    .field-info {
      display: flex;
      align-items: center;

      .field-label {
        font-size: 14px;
        font-weight: 500;
        color: #303133;
      }

      .drag-icon {
        color: #909399;
        cursor: move;
      }
    }

    .field-actions {
      display: flex;
      gap: 4px;
    }
  }

  .field-preview {
    padding: 8px 0;
  }
}
</style>
