<script setup lang="ts">
import { ref, computed, watch, onMounted, useSlots } from "vue";
import type { FormInstance, FormRules } from "element-plus";
import type { ReFormProps, FormField, OptionItem } from "./types";

defineOptions({
  name: "ReForm"
});

const props = withDefaults(defineProps<ReFormProps>(), {
  modelValue: () => ({}),
  labelWidth: "100px",
  labelPosition: "right",
  inline: false,
  disabled: false,
  gutter: 20,
  size: "default",
  showActions: false,
  submitText: "提交",
  cancelText: "取消",
  showReset: true,
  resetText: "重置",
  loading: false
});

const emit = defineEmits<{
  "update:modelValue": [value: Record<string, any>];
  submit: [value: Record<string, any>];
  cancel: [];
  reset: [];
  "validate-error": [errors: any];
}>();

const slots = useSlots();

// 表单实例
const formRef = ref<FormInstance>();

// 表单数据
const formData = ref<Record<string, any>>({});

// 动态选项缓存
const optionsCache = ref<Record<string, OptionItem[]>>({});

// 初始化表单数据
const initFormData = () => {
  const data: Record<string, any> = {};
  props.fields.forEach(field => {
    const defaultVal = getDefaultValue(field);
    setNestedValue(data, field.prop, props.modelValue?.[field.prop] ?? defaultVal);
  });
  formData.value = data;
};

// 获取默认值
const getDefaultValue = (field: FormField): any => {
  if (field.defaultValue !== undefined) return field.defaultValue;

  switch (field.type) {
    case "checkbox":
      return [];
    case "switch":
      return false;
    case "number":
      return undefined;
    case "daterange":
    case "datetimerange":
      return [];
    default:
      return "";
  }
};

// 设置嵌套值
const setNestedValue = (obj: Record<string, any>, path: string, value: any) => {
  const keys = path.split(".");
  let current = obj;
  for (let i = 0; i < keys.length - 1; i++) {
    if (!(keys[i] in current)) {
      current[keys[i]] = {};
    }
    current = current[keys[i]];
  }
  current[keys[keys.length - 1]] = value;
};

// 获取嵌套值
const getNestedValue = (obj: Record<string, any>, path: string): any => {
  return path.split(".").reduce((acc, key) => acc?.[key], obj);
};

// 监听外部数据变化
watch(
  () => props.modelValue,
  val => {
    if (val && JSON.stringify(val) !== JSON.stringify(formData.value)) {
      Object.keys(val).forEach(key => {
        setNestedValue(formData.value, key, val[key]);
      });
    }
  },
  { deep: true }
);

// 监听内部数据变化
watch(
  formData,
  val => {
    emit("update:modelValue", { ...val });
  },
  { deep: true }
);

// 加载动态选项
const loadOptions = async (field: FormField) => {
  if (typeof field.options === "function" && !optionsCache.value[field.prop]) {
    try {
      optionsCache.value[field.prop] = await field.options();
    } catch (e) {
      console.error(`加载 ${field.prop} 选项失败:`, e);
      optionsCache.value[field.prop] = [];
    }
  }
};

// 获取字段选项
const getFieldOptions = (field: FormField): OptionItem[] => {
  if (typeof field.options === "function") {
    return optionsCache.value[field.prop] || [];
  }
  return field.options || [];
};

// 生成校验规则
const formRules = computed<FormRules>(() => {
  const rules: FormRules = {};
  props.fields.forEach(field => {
    if (field.rules) {
      rules[field.prop] = field.rules;
    } else if (field.required) {
      rules[field.prop] = [
        {
          required: true,
          message: `${field.label}不能为空`,
          trigger: field.type === "select" ? "change" : "blur"
        }
      ];
    }
  });
  return rules;
});

// 判断字段是否禁用
const isFieldDisabled = (field: FormField): boolean => {
  if (props.disabled) return true;
  if (typeof field.disabled === "function") {
    return field.disabled(formData.value);
  }
  return field.disabled ?? false;
};

// 判断字段是否隐藏
const isFieldHidden = (field: FormField): boolean => {
  if (typeof field.hidden === "function") {
    return field.hidden(formData.value);
  }
  return field.hidden ?? false;
};

// 处理字段值变化
const handleFieldChange = (field: FormField, value: any) => {
  if (field.onChange) {
    field.onChange(value, formData.value);
  }
};

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    emit("submit", { ...formData.value });
  } catch (errors) {
    emit("validate-error", errors);
  }
};

// 重置表单
const handleReset = () => {
  formRef.value?.resetFields();
  initFormData();
  emit("reset");
};

// 取消
const handleCancel = () => {
  emit("cancel");
};

// 初始化
onMounted(() => {
  initFormData();
  // 加载动态选项
  props.fields.forEach(field => {
    if (typeof field.options === "function") {
      loadOptions(field);
    }
  });
});

// 暴露方法
defineExpose({
  formRef,
  formData,
  validate: () => formRef.value?.validate(),
  validateField: (props: string | string[]) => formRef.value?.validateField(props),
  resetFields: () => formRef.value?.resetFields(),
  clearValidate: (props?: string | string[]) => formRef.value?.clearValidate(props),
  scrollToField: (prop: string) => formRef.value?.scrollToField(prop),
  getFormData: () => ({ ...formData.value }),
  setFormData: (data: Record<string, any>) => {
    Object.keys(data).forEach(key => {
      setNestedValue(formData.value, key, data[key]);
    });
  }
});
</script>

<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="formRules"
    :label-width="labelWidth"
    :label-position="labelPosition"
    :inline="inline"
    :disabled="disabled"
    :size="size"
    @submit.prevent="handleSubmit"
  >
    <el-row :gutter="gutter">
      <template v-for="field in fields" :key="field.prop">
        <el-col
          v-if="!isFieldHidden(field)"
          :span="field.span ?? (inline ? undefined : 24)"
        >
          <el-form-item
            :label="field.label"
            :prop="field.prop"
            :style="field.style"
          >
            <!-- 自定义渲染 -->
            <template v-if="field.render">
              <component :is="field.render(formData)" />
            </template>

            <!-- 插槽 -->
            <template v-else-if="field.type === 'slot' && field.slot">
              <slot :name="field.slot" :form="formData" :field="field" />
            </template>

            <!-- 输入框 -->
            <el-input
              v-else-if="field.type === 'input'"
              v-model="formData[field.prop]"
              :placeholder="field.placeholder ?? `请输入${field.label}`"
              :disabled="isFieldDisabled(field)"
              clearable
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 文本域 -->
            <el-input
              v-else-if="field.type === 'textarea'"
              v-model="formData[field.prop]"
              type="textarea"
              :placeholder="field.placeholder ?? `请输入${field.label}`"
              :disabled="isFieldDisabled(field)"
              :rows="3"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 数字输入 -->
            <el-input-number
              v-else-if="field.type === 'number'"
              v-model="formData[field.prop]"
              :placeholder="field.placeholder"
              :disabled="isFieldDisabled(field)"
              controls-position="right"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 下拉选择 -->
            <el-select
              v-else-if="field.type === 'select'"
              v-model="formData[field.prop]"
              :placeholder="field.placeholder ?? `请选择${field.label}`"
              :disabled="isFieldDisabled(field)"
              clearable
              class="w-full"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            >
              <el-option
                v-for="opt in getFieldOptions(field)"
                :key="opt.value"
                :label="opt.label"
                :value="opt.value"
                :disabled="opt.disabled"
              />
            </el-select>

            <!-- 单选 -->
            <el-radio-group
              v-else-if="field.type === 'radio'"
              v-model="formData[field.prop]"
              :disabled="isFieldDisabled(field)"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            >
              <el-radio
                v-for="opt in getFieldOptions(field)"
                :key="opt.value"
                :value="opt.value"
                :disabled="opt.disabled"
              >
                {{ opt.label }}
              </el-radio>
            </el-radio-group>

            <!-- 多选 -->
            <el-checkbox-group
              v-else-if="field.type === 'checkbox'"
              v-model="formData[field.prop]"
              :disabled="isFieldDisabled(field)"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            >
              <el-checkbox
                v-for="opt in getFieldOptions(field)"
                :key="opt.value"
                :value="opt.value"
                :disabled="opt.disabled"
              >
                {{ opt.label }}
              </el-checkbox>
            </el-checkbox-group>

            <!-- 开关 -->
            <el-switch
              v-else-if="field.type === 'switch'"
              v-model="formData[field.prop]"
              :disabled="isFieldDisabled(field)"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 日期选择 -->
            <el-date-picker
              v-else-if="field.type === 'date'"
              v-model="formData[field.prop]"
              type="date"
              :placeholder="field.placeholder ?? `请选择${field.label}`"
              :disabled="isFieldDisabled(field)"
              value-format="YYYY-MM-DD"
              class="w-full!"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 日期时间选择 -->
            <el-date-picker
              v-else-if="field.type === 'datetime'"
              v-model="formData[field.prop]"
              type="datetime"
              :placeholder="field.placeholder ?? `请选择${field.label}`"
              :disabled="isFieldDisabled(field)"
              value-format="YYYY-MM-DD HH:mm:ss"
              class="w-full!"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 日期范围选择 -->
            <el-date-picker
              v-else-if="field.type === 'daterange'"
              v-model="formData[field.prop]"
              type="daterange"
              range-separator="至"
              start-placeholder="开始日期"
              end-placeholder="结束日期"
              :disabled="isFieldDisabled(field)"
              value-format="YYYY-MM-DD"
              class="w-full!"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 日期时间范围选择 -->
            <el-date-picker
              v-else-if="field.type === 'datetimerange'"
              v-model="formData[field.prop]"
              type="datetimerange"
              range-separator="至"
              start-placeholder="开始时间"
              end-placeholder="结束时间"
              :disabled="isFieldDisabled(field)"
              value-format="YYYY-MM-DD HH:mm:ss"
              class="w-full!"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 时间选择 -->
            <el-time-picker
              v-else-if="field.type === 'time'"
              v-model="formData[field.prop]"
              :placeholder="field.placeholder ?? `请选择${field.label}`"
              :disabled="isFieldDisabled(field)"
              value-format="HH:mm:ss"
              class="w-full!"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 级联选择 -->
            <el-cascader
              v-else-if="field.type === 'cascader'"
              v-model="formData[field.prop]"
              :options="getFieldOptions(field)"
              :placeholder="field.placeholder ?? `请选择${field.label}`"
              :disabled="isFieldDisabled(field)"
              clearable
              class="w-full"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />

            <!-- 树形选择 -->
            <el-tree-select
              v-else-if="field.type === 'tree-select'"
              v-model="formData[field.prop]"
              :data="getFieldOptions(field)"
              :placeholder="field.placeholder ?? `请选择${field.label}`"
              :disabled="isFieldDisabled(field)"
              clearable
              class="w-full"
              v-bind="field.props"
              @change="handleFieldChange(field, formData[field.prop])"
            />
          </el-form-item>
        </el-col>
      </template>
    </el-row>

    <!-- 表单按钮 -->
    <el-form-item v-if="showActions" class="mt-4">
      <el-button type="primary" :loading="loading" @click="handleSubmit">
        {{ submitText }}
      </el-button>
      <el-button v-if="showReset" @click="handleReset">
        {{ resetText }}
      </el-button>
      <el-button @click="handleCancel">
        {{ cancelText }}
      </el-button>
      <slot name="actions" />
    </el-form-item>

    <!-- 额外内容插槽 -->
    <slot name="extra" :form="formData" />
  </el-form>
</template>
