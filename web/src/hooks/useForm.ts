import { ref, reactive, computed, watch } from "vue";
import type { Ref, UnwrapRef } from "vue";
import type { FormInstance, FormRules, FormItemRule } from "element-plus";
import { ElMessage } from "element-plus";
import { cloneDeep } from "@pureadmin/utils";

/** 表单提交函数类型 */
export type SubmitFunction<T, R = any> = (data: T) => Promise<R>;

/** useForm 配置选项 */
export interface UseFormOptions<T extends Record<string, any>> {
  /** 初始表单数据 */
  defaultValues?: T;
  /** 表单校验规则 */
  rules?: FormRules;
  /** 提交函数 */
  submitApi?: SubmitFunction<T>;
  /** 提交成功回调 */
  onSuccess?: (result: any, data: T) => void;
  /** 提交失败回调 */
  onError?: (error: any) => void;
  /** 提交前数据转换 */
  transformSubmit?: (data: T) => any;
  /** 是否自动重置表单（提交成功后） */
  autoReset?: boolean;
  /** 成功提示消息 */
  successMessage?: string | false;
  /** 失败提示消息 */
  errorMessage?: string | false;
}

/** useForm 返回值 */
export interface UseFormReturn<T extends Record<string, any>> {
  /** 表单数据 */
  formData: T;
  /** 表单 ref */
  formRef: Ref<FormInstance | undefined>;
  /** 是否正在提交 */
  loading: Ref<boolean>;
  /** 表单校验规则 */
  rules: FormRules;
  /** 是否已修改 */
  isDirty: Ref<boolean>;
  /** 提交表单 */
  submit: () => Promise<boolean>;
  /** 验证表单 */
  validate: () => Promise<boolean>;
  /** 验证指定字段 */
  validateField: (props: string | string[]) => Promise<boolean>;
  /** 重置表单 */
  reset: () => void;
  /** 清除校验 */
  clearValidate: (props?: string | string[]) => void;
  /** 滚动到指定字段 */
  scrollToField: (prop: string) => void;
  /** 设置表单数据 */
  setFormData: (data: Partial<T>) => void;
  /** 获取表单数据 */
  getFormData: () => T;
  /** 设置字段值 */
  setFieldValue: (prop: string, value: any) => void;
  /** 获取字段值 */
  getFieldValue: (prop: string) => any;
  /** 设置校验规则 */
  setRules: (rules: FormRules) => void;
  /** 添加字段规则 */
  addFieldRule: (prop: string, rules: FormItemRule | FormItemRule[]) => void;
  /** 删除字段规则 */
  removeFieldRule: (prop: string) => void;
}

/**
 * 表单管理 Hook
 * @description 封装表单校验、提交、重置等通用逻辑
 */
export function useForm<T extends Record<string, any>>(
  options: UseFormOptions<T> = {}
): UseFormReturn<T> {
  const {
    defaultValues = {} as T,
    rules: initialRules = {},
    submitApi,
    onSuccess,
    onError,
    transformSubmit,
    autoReset = false,
    successMessage = "操作成功",
    errorMessage = "操作失败"
  } = options;

  // 表单实例
  const formRef = ref<FormInstance>();

  // 表单数据
  const formData = reactive<T>(cloneDeep(defaultValues)) as T;

  // 初始数据（用于重置）
  const initialData = cloneDeep(defaultValues);

  // 加载状态
  const loading = ref(false);

  // 校验规则
  const rules = reactive<FormRules>({ ...initialRules });

  // 是否修改过
  const isDirty = ref(false);

  // 监听表单数据变化
  watch(
    () => formData,
    () => {
      isDirty.value = true;
    },
    { deep: true }
  );

  // 验证表单
  const validate = async (): Promise<boolean> => {
    if (!formRef.value) return false;
    try {
      await formRef.value.validate();
      return true;
    } catch {
      return false;
    }
  };

  // 验证指定字段
  const validateField = async (props: string | string[]): Promise<boolean> => {
    if (!formRef.value) return false;
    try {
      await formRef.value.validateField(props);
      return true;
    } catch {
      return false;
    }
  };

  // 提交表单
  const submit = async (): Promise<boolean> => {
    const valid = await validate();
    if (!valid) return false;

    if (!submitApi) {
      console.warn("useForm: submitApi is not provided");
      return true;
    }

    loading.value = true;
    try {
      // 数据转换
      const submitData = transformSubmit
        ? transformSubmit({ ...formData } as T)
        : { ...formData };

      const result = await submitApi(submitData);

      // 成功提示
      if (successMessage !== false) {
        ElMessage.success(successMessage);
      }

      // 成功回调
      if (onSuccess) {
        onSuccess(result, { ...formData } as T);
      }

      // 自动重置
      if (autoReset) {
        reset();
      }

      return true;
    } catch (error: any) {
      // 失败提示
      if (errorMessage !== false) {
        ElMessage.error(error?.message || errorMessage);
      }

      // 失败回调
      if (onError) {
        onError(error);
      }

      return false;
    } finally {
      loading.value = false;
    }
  };

  // 重置表单
  const reset = () => {
    // 重置数据
    Object.keys(formData).forEach(key => {
      (formData as any)[key] = (initialData as any)[key] ?? undefined;
    });

    // 清除校验
    formRef.value?.clearValidate();

    // 重置状态
    isDirty.value = false;
  };

  // 清除校验
  const clearValidate = (props?: string | string[]) => {
    formRef.value?.clearValidate(props);
  };

  // 滚动到指定字段
  const scrollToField = (prop: string) => {
    formRef.value?.scrollToField(prop);
  };

  // 设置表单数据
  const setFormData = (data: Partial<T>) => {
    Object.assign(formData, data);
  };

  // 获取表单数据
  const getFormData = (): T => {
    return { ...formData } as T;
  };

  // 设置字段值
  const setFieldValue = (prop: string, value: any) => {
    const keys = prop.split(".");
    let current: any = formData;
    for (let i = 0; i < keys.length - 1; i++) {
      if (!(keys[i] in current)) {
        current[keys[i]] = {};
      }
      current = current[keys[i]];
    }
    current[keys[keys.length - 1]] = value;
  };

  // 获取字段值
  const getFieldValue = (prop: string): any => {
    return prop.split(".").reduce((acc: any, key) => acc?.[key], formData);
  };

  // 设置校验规则
  const setRules = (newRules: FormRules) => {
    Object.assign(rules, newRules);
  };

  // 添加字段规则
  const addFieldRule = (prop: string, fieldRules: FormItemRule | FormItemRule[]) => {
    const ruleArray = Array.isArray(fieldRules) ? fieldRules : [fieldRules];
    if (rules[prop]) {
      (rules[prop] as FormItemRule[]).push(...ruleArray);
    } else {
      rules[prop] = ruleArray;
    }
  };

  // 删除字段规则
  const removeFieldRule = (prop: string) => {
    delete rules[prop];
  };

  return {
    formData,
    formRef,
    loading,
    rules,
    isDirty,
    submit,
    validate,
    validateField,
    reset,
    clearValidate,
    scrollToField,
    setFormData,
    getFormData,
    setFieldValue,
    getFieldValue,
    setRules,
    addFieldRule,
    removeFieldRule
  };
}

export default useForm;
