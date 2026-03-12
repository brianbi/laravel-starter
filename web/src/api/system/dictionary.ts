import { http } from "@/utils/http";
import type {
  Dictionary,
  DictionaryQuery,
  DictionaryForm,
  DictionaryItem,
  DictionaryItemForm,
  PageResult,
  DataResult
} from "./types";

const BASE_URL = "/api/admin/dictionaries";

/** 获取字典列表 */
export const getDictionaryList = (params?: DictionaryQuery) => {
  return http.request<PageResult<Dictionary>>("get", BASE_URL, { params });
};

/** 获取字典详情 */
export const getDictionary = (id: number) => {
  return http.request<DataResult<Dictionary>>("get", `${BASE_URL}/${id}`);
};

/** 根据编码获取字典 */
export const getDictionaryByCode = (code: string) => {
  return http.request<DataResult<Dictionary>>("get", `${BASE_URL}/code/${code}`);
};

/** 创建字典 */
export const createDictionary = (data: DictionaryForm) => {
  return http.request<DataResult<Dictionary>>("post", BASE_URL, { data });
};

/** 更新字典 */
export const updateDictionary = (id: number, data: DictionaryForm) => {
  return http.request<DataResult<Dictionary>>("put", `${BASE_URL}/${id}`, {
    data
  });
};

/** 删除字典 */
export const deleteDictionary = (id: number) => {
  return http.request("delete", `${BASE_URL}/${id}`);
};

/** 获取字典项列表 */
export const getDictionaryItems = (dictionaryId: number) => {
  return http.request<DataResult<DictionaryItem[]>>(
    "get",
    `${BASE_URL}/${dictionaryId}/items`
  );
};

/** 创建字典项 */
export const createDictionaryItem = (
  dictionaryId: number,
  data: DictionaryItemForm
) => {
  return http.request<DataResult<DictionaryItem>>(
    "post",
    `${BASE_URL}/${dictionaryId}/items`,
    { data }
  );
};

/** 更新字典项 */
export const updateDictionaryItem = (
  dictionaryId: number,
  itemId: number,
  data: DictionaryItemForm
) => {
  return http.request<DataResult<DictionaryItem>>(
    "put",
    `${BASE_URL}/${dictionaryId}/items/${itemId}`,
    { data }
  );
};

/** 删除字典项 */
export const deleteDictionaryItem = (dictionaryId: number, itemId: number) => {
  return http.request("delete", `${BASE_URL}/${dictionaryId}/items/${itemId}`);
};
