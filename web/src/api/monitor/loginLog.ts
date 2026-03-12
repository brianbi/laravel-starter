import { http } from "@/utils/http";
import type { PageResult } from "../system/types";
import type { LoginLog, LoginLogQuery } from "./types";

const BASE_URL = "/api/admin/logs/login";

/** 获取登录日志列表 */
export const getLoginLogList = (params?: LoginLogQuery) => {
  return http.request<PageResult<LoginLog>>("get", BASE_URL, { params });
};

/** 清理登录日志 */
export const clearLoginLogs = (days: number) => {
  return http.request<{ deleted: number }>("delete", `${BASE_URL}/clear`, {
    data: { days }
  });
};
