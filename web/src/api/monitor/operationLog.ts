import { http } from "@/utils/http";
import type { PageResult } from "../system/types";
import type { OperationLog, OperationLogQuery } from "./types";

const BASE_URL = "/api/admin/logs/operation";

/** 获取操作日志列表 */
export const getOperationLogList = (params?: OperationLogQuery) => {
  return http.request<PageResult<OperationLog>>("get", BASE_URL, { params });
};

/** 清理操作日志 */
export const clearOperationLogs = (days: number) => {
  return http.request<{ deleted: number }>("delete", `${BASE_URL}/clear`, {
    data: { days }
  });
};
