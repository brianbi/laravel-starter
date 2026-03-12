import type { PageParams } from "../system/types";

/** 操作日志 */
export interface OperationLog {
  id: number;
  user_id: number;
  username: string;
  method: string;
  router: string;
  service_name: string;
  ip: string;
  ip_location: string;
  request_data: string;
  response_code: number;
  response_data: string;
  execution_time: number;
  created_at: string;
}

/** 操作日志查询参数 */
export interface OperationLogQuery extends PageParams {
  username?: string;
  service_name?: string;
  method?: string;
  ip?: string;
  response_code?: number;
  start_time?: string;
  end_time?: string;
}

/** 登录日志 */
export interface LoginLog {
  id: number;
  username: string;
  ip: string;
  ip_location: string;
  os: string;
  browser: string;
  status: number;
  message: string;
  login_at: string;
}

/** 登录日志查询参数 */
export interface LoginLogQuery extends PageParams {
  username?: string;
  ip?: string;
  status?: number;
  start_time?: string;
  end_time?: string;
}
