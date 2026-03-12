import { http } from "@/utils/http";
import type {
  Position,
  PositionQuery,
  PositionForm,
  PageResult,
  DataResult
} from "./types";

const BASE_URL = "/api/admin/positions";

/** 获取岗位列表 */
export const getPositionList = (params?: PositionQuery) => {
  return http.request<PageResult<Position>>("get", BASE_URL, { params });
};

/** 获取所有岗位（不分页） */
export const getAllPositions = () => {
  return http.request<DataResult<Position[]>>("get", `${BASE_URL}/all`);
};

/** 获取岗位详情 */
export const getPosition = (id: number) => {
  return http.request<DataResult<Position>>("get", `${BASE_URL}/${id}`);
};

/** 创建岗位 */
export const createPosition = (data: PositionForm) => {
  return http.request<DataResult<Position>>("post", BASE_URL, { data });
};

/** 更新岗位 */
export const updatePosition = (id: number, data: PositionForm) => {
  return http.request<DataResult<Position>>("put", `${BASE_URL}/${id}`, {
    data
  });
};

/** 删除岗位 */
export const deletePosition = (id: number) => {
  return http.request("delete", `${BASE_URL}/${id}`);
};
