import { ref, reactive, computed, onMounted, watch } from "vue";
import type { Ref } from "vue";

/** 分页参数 */
export interface PaginationParams {
  page: number;
  pageSize: number;
}

/** 分页结果 */
export interface PaginationResult<T> {
  data: T[];
  total: number;
}

/** API 请求函数类型 */
export type FetchFunction<T, P = Record<string, any>> = (
  params: P & PaginationParams
) => Promise<PaginationResult<T>>;

/** useTable 配置选项 */
export interface UseTableOptions<T, P = Record<string, any>> {
  /** 数据获取函数 */
  fetchApi: FetchFunction<T, P>;
  /** 初始搜索参数 */
  defaultParams?: P;
  /** 初始每页条数 */
  defaultPageSize?: number;
  /** 是否立即加载 */
  immediate?: boolean;
  /** 数据转换函数 */
  transform?: (data: T[]) => T[];
  /** 请求前回调 */
  beforeFetch?: (params: P & PaginationParams) => P & PaginationParams;
  /** 请求后回调 */
  afterFetch?: (data: T[], total: number) => void;
  /** 错误处理回调 */
  onError?: (error: any) => void;
  /** 行唯一标识字段 */
  rowKey?: string;
}

/** useTable 返回值 */
export interface UseTableReturn<T, P = Record<string, any>> {
  /** 表格数据 */
  data: Ref<T[]>;
  /** 是否加载中 */
  loading: Ref<boolean>;
  /** 分页信息 */
  pagination: {
    currentPage: Ref<number>;
    pageSize: Ref<number>;
    total: Ref<number>;
  };
  /** 搜索参数 */
  searchParams: P;
  /** 选中的行 */
  selectedRows: Ref<T[]>;
  /** 选中的行 keys */
  selectedKeys: Ref<(string | number)[]>;
  /** 加载数据 */
  fetchData: () => Promise<void>;
  /** 刷新（保持当前页） */
  refresh: () => Promise<void>;
  /** 重置并刷新（回到第一页） */
  reset: () => Promise<void>;
  /** 搜索（回到第一页） */
  search: (params?: Partial<P>) => Promise<void>;
  /** 分页变化处理 */
  handlePaginationChange: (pagination: {
    page: number;
    pageSize: number;
  }) => void;
  /** 选择变化处理 */
  handleSelectionChange: (selection: T[]) => void;
  /** 清空选择 */
  clearSelection: () => void;
  /** 设置搜索参数 */
  setSearchParams: (params: Partial<P>) => void;
  /** 获取搜索参数 */
  getSearchParams: () => P & PaginationParams;
}

/**
 * 表格数据管理 Hook
 * @description 封装表格数据加载、分页、搜索等通用逻辑
 */
export function useTable<T extends Record<string, any>, P = Record<string, any>>(
  options: UseTableOptions<T, P>
): UseTableReturn<T, P> {
  const {
    fetchApi,
    defaultParams = {} as P,
    defaultPageSize = 10,
    immediate = true,
    transform,
    beforeFetch,
    afterFetch,
    onError,
    rowKey = "id"
  } = options;

  // 表格数据
  const data = ref<T[]>([]) as Ref<T[]>;

  // 加载状态
  const loading = ref(false);

  // 分页
  const currentPage = ref(1);
  const pageSize = ref(defaultPageSize);
  const total = ref(0);

  // 搜索参数
  const searchParams = reactive<P>({ ...defaultParams });

  // 选中行
  const selectedRows = ref<T[]>([]) as Ref<T[]>;
  const selectedKeys = computed(() =>
    selectedRows.value.map(row => row[rowKey])
  );

  // 获取完整请求参数
  const getSearchParams = (): P & PaginationParams => {
    return {
      ...searchParams,
      page: currentPage.value,
      pageSize: pageSize.value
    } as P & PaginationParams;
  };

  // 加载数据
  const fetchData = async () => {
    loading.value = true;
    try {
      let params = getSearchParams();

      // 请求前回调
      if (beforeFetch) {
        params = beforeFetch(params);
      }

      const result = await fetchApi(params);

      // 数据转换
      let tableData = result.data || [];
      if (transform) {
        tableData = transform(tableData);
      }

      data.value = tableData;
      total.value = result.total || 0;

      // 请求后回调
      if (afterFetch) {
        afterFetch(tableData, total.value);
      }
    } catch (error) {
      console.error("Table fetch error:", error);
      if (onError) {
        onError(error);
      }
      data.value = [];
      total.value = 0;
    } finally {
      loading.value = false;
    }
  };

  // 刷新（保持当前页）
  const refresh = async () => {
    await fetchData();
  };

  // 重置并刷新（回到第一页）
  const reset = async () => {
    // 重置搜索参数
    Object.keys(searchParams).forEach(key => {
      (searchParams as any)[key] = (defaultParams as any)[key] ?? "";
    });
    currentPage.value = 1;
    await fetchData();
  };

  // 搜索（回到第一页）
  const search = async (params?: Partial<P>) => {
    if (params) {
      Object.assign(searchParams, params);
    }
    currentPage.value = 1;
    await fetchData();
  };

  // 分页变化处理
  const handlePaginationChange = (pagination: {
    page: number;
    pageSize: number;
  }) => {
    const pageSizeChanged = pagination.pageSize !== pageSize.value;
    currentPage.value = pageSizeChanged ? 1 : pagination.page;
    pageSize.value = pagination.pageSize;
    fetchData();
  };

  // 选择变化处理
  const handleSelectionChange = (selection: T[]) => {
    selectedRows.value = selection;
  };

  // 清空选择
  const clearSelection = () => {
    selectedRows.value = [];
  };

  // 设置搜索参数
  const setSearchParams = (params: Partial<P>) => {
    Object.assign(searchParams, params);
  };

  // 立即加载
  if (immediate) {
    onMounted(() => {
      fetchData();
    });
  }

  return {
    data,
    loading,
    pagination: {
      currentPage,
      pageSize,
      total
    },
    searchParams: searchParams as P,
    selectedRows,
    selectedKeys,
    fetchData,
    refresh,
    reset,
    search,
    handlePaginationChange,
    handleSelectionChange,
    clearSelection,
    setSearchParams,
    getSearchParams
  };
}

export default useTable;
