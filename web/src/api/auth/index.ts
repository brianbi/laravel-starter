import { http } from "@/utils/http";
import type {
  LoginParams,
  LoginResult,
  RefreshTokenResult,
  UserInfoResult,
  AsyncRoutesResult,
  BackendLoginData,
  BackendMenu,
  LoginData,
  AsyncRoute
} from "./types";

/**
 * 转换后端登录响应为前端格式
 */
function transformLoginResponse(backendData: BackendLoginData): LoginData {
  const expiresIn = backendData.expires_in * 1000; // 转为毫秒
  const expires = new Date(Date.now() + expiresIn);
  return {
    accessToken: backendData.access_token,
    // JWT 模式下，使用 accessToken 作为 refreshToken（后端刷新时会验证当前token）
    refreshToken: backendData.access_token,
    expires
  };
}

/**
 * 转换后端菜单为前端路由格式
 */
function transformMenuToRoute(menu: BackendMenu): AsyncRoute {
  const route: AsyncRoute = {
    path: menu.route || `/${menu.code}`,
    name: menu.code
      ? menu.code.charAt(0).toUpperCase() +
        menu.code.slice(1).replace(/:(\w)/g, (_, c) => c.toUpperCase())
      : undefined,
    meta: {
      title: menu.name,
      icon: menu.icon || undefined,
      rank: menu.sort,
      showLink: menu.is_hidden !== 1,
      keepAlive: menu.is_cache === 1
    }
  };

  // 设置组件路径
  if (menu.component) {
    route.component = menu.component;
  }

  // 设置重定向
  if (menu.redirect) {
    route.redirect = menu.redirect;
  }

  // 设置权限标识
  if (menu.permission) {
    route.meta.auths = [menu.permission];
  }

  // 递归处理子菜单
  if (menu.children && menu.children.length > 0) {
    route.children = menu.children.map(transformMenuToRoute);
  }

  return route;
}

/**
 * 转换后端菜单列表为路由列表
 */
function transformMenusToRoutes(menus: BackendMenu[]): AsyncRoute[] {
  return menus.map(transformMenuToRoute);
}

/** 登录 */
export const login = (data: LoginParams): Promise<LoginResult> => {
  return http
    .request<{ success: boolean; data: BackendLoginData }>(
      "post",
      "/api/admin/auth/login",
      { data }
    )
    .then(res => ({
      success: res.success,
      data: transformLoginResponse(res.data)
    }));
};

/** 登出 */
export const logout = () => {
  return http.request("post", "/api/admin/auth/logout");
};

/** 刷新Token */
export const refreshToken = (
  _data?: { refreshToken: string } // 参数保留但后端使用当前token刷新
): Promise<RefreshTokenResult> => {
  return http
    .request<{ success: boolean; data: BackendLoginData }>(
      "post",
      "/api/admin/auth/refresh"
    )
    .then(res => ({
      success: res.success,
      data: transformLoginResponse(res.data)
    }));
};

/** 获取当前用户信息 */
export const getUserInfo = (): Promise<UserInfoResult> => {
  return http.request<UserInfoResult>("get", "/api/admin/auth/me");
};

/** 获取用户菜单（异步路由） */
export const getAsyncRoutes = (): Promise<AsyncRoutesResult> => {
  return http
    .request<{ success: boolean; data: BackendMenu[] }>(
      "get",
      "/api/admin/auth/menus"
    )
    .then(res => ({
      success: res.success,
      data: transformMenusToRoutes(res.data)
    }));
};

export * from "./types";
