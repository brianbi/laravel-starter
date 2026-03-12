import { ref, computed, onMounted } from "vue";
import type { Ref } from "vue";

/** 字典项 */
export interface DictItem {
  /** 标签 */
  label: string;
  /** 值 */
  value: string | number;
  /** 是否禁用 */
  disabled?: boolean;
  /** 标签类型（用于 el-tag） */
  tagType?: "success" | "warning" | "danger" | "info" | "";
  /** 扩展属性 */
  [key: string]: any;
}

/** 字典缓存 */
const dictCache = new Map<string, DictItem[]>();

/** 加载状态缓存（防止重复请求） */
const loadingPromises = new Map<string, Promise<DictItem[]>>();

/** 字典获取函数类型 */
export type DictFetchFunction = (dictType: string) => Promise<DictItem[]>;

/** 全局字典获取函数 */
let globalDictFetcher: DictFetchFunction | null = null;

/**
 * 设置全局字典获取函数
 * @param fetcher 字典获取函数
 */
export function setDictFetcher(fetcher: DictFetchFunction): void {
  globalDictFetcher = fetcher;
}

/**
 * 获取全局字典获取函数
 */
export function getDictFetcher(): DictFetchFunction | null {
  return globalDictFetcher;
}

/**
 * 清除字典缓存
 * @param dictType 字典类型，不传则清除所有
 */
export function clearDictCache(dictType?: string): void {
  if (dictType) {
    dictCache.delete(dictType);
  } else {
    dictCache.clear();
  }
}

/**
 * 预加载字典
 * @param dictTypes 字典类型数组
 */
export async function preloadDict(dictTypes: string[]): Promise<void> {
  if (!globalDictFetcher) {
    console.warn("useDict: global dict fetcher is not set");
    return;
  }

  await Promise.all(
    dictTypes.map(async type => {
      if (!dictCache.has(type)) {
        try {
          const data = await globalDictFetcher!(type);
          dictCache.set(type, data);
        } catch (error) {
          console.error(`Failed to preload dict: ${type}`, error);
        }
      }
    })
  );
}

/** useDict 配置选项 */
export interface UseDictOptions {
  /** 自定义获取函数 */
  fetcher?: DictFetchFunction;
  /** 是否立即加载 */
  immediate?: boolean;
  /** 是否使用缓存 */
  useCache?: boolean;
  /** 默认值（加载中显示） */
  defaultValue?: DictItem[];
}

/** useDict 返回值 */
export interface UseDictReturn {
  /** 字典数据 */
  data: Ref<DictItem[]>;
  /** 是否加载中 */
  loading: Ref<boolean>;
  /** 是否加载失败 */
  error: Ref<Error | null>;
  /** 重新加载 */
  reload: () => Promise<void>;
  /** 获取标签（根据值） */
  getLabel: (value: string | number) => string;
  /** 获取值（根据标签） */
  getValue: (label: string) => string | number | undefined;
  /** 获取字典项（根据值） */
  getItem: (value: string | number) => DictItem | undefined;
  /** 获取标签类型（根据值） */
  getTagType: (value: string | number) => string;
  /** 格式化选项（用于 el-select） */
  options: Ref<DictItem[]>;
}

/**
 * 字典数据管理 Hook
 * @description 封装字典数据获取、缓存等通用逻辑
 * @param dictType 字典类型
 * @param options 配置选项
 */
export function useDict(
  dictType: string,
  options: UseDictOptions = {}
): UseDictReturn {
  const {
    fetcher = globalDictFetcher,
    immediate = true,
    useCache = true,
    defaultValue = []
  } = options;

  // 字典数据
  const data = ref<DictItem[]>(defaultValue);

  // 加载状态
  const loading = ref(false);

  // 错误信息
  const error = ref<Error | null>(null);

  // 加载字典数据
  const loadDict = async (): Promise<DictItem[]> => {
    if (!fetcher) {
      console.warn("useDict: dict fetcher is not provided");
      return [];
    }

    // 检查缓存
    if (useCache && dictCache.has(dictType)) {
      return dictCache.get(dictType)!;
    }

    // 检查是否正在加载（防止重复请求）
    if (loadingPromises.has(dictType)) {
      return loadingPromises.get(dictType)!;
    }

    // 创建加载 Promise
    const loadPromise = (async () => {
      try {
        const result = await fetcher(dictType);
        // 缓存结果
        if (useCache) {
          dictCache.set(dictType, result);
        }
        return result;
      } finally {
        // 清除加载状态
        loadingPromises.delete(dictType);
      }
    })();

    loadingPromises.set(dictType, loadPromise);
    return loadPromise;
  };

  // 重新加载
  const reload = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    try {
      // 清除缓存强制重新加载
      dictCache.delete(dictType);
      loadingPromises.delete(dictType);
      data.value = await loadDict();
    } catch (e) {
      error.value = e as Error;
      console.error(`Failed to load dict: ${dictType}`, e);
    } finally {
      loading.value = false;
    }
  };

  // 获取标签
  const getLabel = (value: string | number): string => {
    const item = data.value.find(item => item.value === value);
    return item?.label ?? String(value);
  };

  // 获取值
  const getValue = (label: string): string | number | undefined => {
    const item = data.value.find(item => item.label === label);
    return item?.value;
  };

  // 获取字典项
  const getItem = (value: string | number): DictItem | undefined => {
    return data.value.find(item => item.value === value);
  };

  // 获取标签类型
  const getTagType = (value: string | number): string => {
    const item = getItem(value);
    return item?.tagType ?? "";
  };

  // 选项（用于 el-select）
  const options = computed(() => data.value);

  // 立即加载
  if (immediate) {
    onMounted(async () => {
      loading.value = true;
      try {
        data.value = await loadDict();
      } catch (e) {
        error.value = e as Error;
      } finally {
        loading.value = false;
      }
    });
  }

  return {
    data,
    loading,
    error,
    reload,
    getLabel,
    getValue,
    getItem,
    getTagType,
    options
  };
}

/**
 * 批量获取字典 Hook
 * @param dictTypes 字典类型数组
 * @param options 配置选项
 */
export function useDicts(
  dictTypes: string[],
  options: Omit<UseDictOptions, "immediate"> = {}
): Record<string, UseDictReturn> {
  const result: Record<string, UseDictReturn> = {};

  dictTypes.forEach(dictType => {
    result[dictType] = useDict(dictType, { ...options, immediate: true });
  });

  return result;
}

export default useDict;
