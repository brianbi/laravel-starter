/** 登录请求参数 */
export interface LoginParams {
  username: string;
  password: string;
}

/** 后端返回的登录数据 */
export interface BackendLoginData {
  access_token: string;
  token_type: string;
  expires_in: number; // 秒
}

/** 前端使用的登录数据 */
export interface LoginData {
  /** `token` */
  accessToken: string;
  /** 用于调用刷新`accessToken`的接口时所需的`token` */
  refreshToken: string;
  /** `accessToken`的过期时间 */
  expires: Date;
}

/** 登录响应 */
export interface LoginResult {
  success: boolean;
  data: LoginData;
  message?: string;
}

/** 刷新Token响应 */
export interface RefreshTokenResult {
  success: boolean;
  data: LoginData;
  message?: string;
}

/** 用户信息 */
export interface UserInfo {
  id: number;
  username: string;
  name: string;
  email: string;
  phone: string;
  avatar: string;
  department: { id: number; name: string } | null;
  positions: Array<{ id: number; name: string }>;
  roles: string[];
  permissions: string[];
}

/** 用户信息响应 */
export interface UserInfoResult {
  success: boolean;
  data: UserInfo;
  message?: string;
}

/** 后端菜单数据 */
export interface BackendMenu {
  id: number;
  parent_id: number;
  name: string;
  code: string;
  type: string;
  icon: string;
  route: string;
  component: string;
  redirect: string;
  permission: string;
  sort: number;
  status: number;
  is_hidden: number;
  is_cache: number;
  children?: BackendMenu[];
}

/** 路由元数据 */
export interface RouteMeta {
  title: string;
  icon?: string;
  rank?: number;
  roles?: Array<string>;
  auths?: Array<string>;
  keepAlive?: boolean;
  showLink?: boolean;
}

/** 异步路由 */
export interface AsyncRoute {
  path: string;
  name?: string;
  component?: string;
  redirect?: string;
  meta?: RouteMeta;
  children?: Array<AsyncRoute>;
}

/** 异步路由响应 */
export interface AsyncRoutesResult {
  success: boolean;
  data: Array<AsyncRoute>;
}
