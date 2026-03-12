/** 分页参数 */
export interface PageParams {
  page?: number;
  per_page?: number;
}

/** 分页响应元数据 */
export interface PageMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

/** 分页响应 */
export interface PageResult<T> {
  success: boolean;
  data: T[];
  meta: PageMeta;
}

/** 单条数据响应 */
export interface DataResult<T> {
  success: boolean;
  data: T;
  message?: string;
}

/** 用户 */
export interface User {
  id: number;
  username: string;
  name: string;
  email: string;
  phone: string;
  avatar: string;
  department_id: number;
  department?: Department;
  status: number;
  login_ip: string;
  login_at: string;
  created_at: string;
  updated_at: string;
  roles?: Role[];
  positions?: Position[];
}

/** 用户查询参数 */
export interface UserQuery extends PageParams {
  username?: string;
  name?: string;
  phone?: string;
  status?: number;
  department_id?: number;
}

/** 用户表单 */
export interface UserForm {
  username: string;
  name: string;
  email?: string;
  phone?: string;
  password?: string;
  department_id?: number;
  status?: number;
  role_ids?: number[];
  position_ids?: number[];
}

/** 角色 */
export interface Role {
  id: number;
  name: string;
  code: string;
  description: string;
  data_scope: number;
  status: number;
  sort: number;
  created_at: string;
  updated_at: string;
  permissions?: number[];
}

/** 角色查询参数 */
export interface RoleQuery extends PageParams {
  name?: string;
  code?: string;
  status?: number;
}

/** 角色表单 */
export interface RoleForm {
  name: string;
  code: string;
  description?: string;
  data_scope?: number;
  status?: number;
  sort?: number;
}

/** 菜单 */
export interface Menu {
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
  created_at: string;
  updated_at: string;
  children?: Menu[];
}

/** 菜单表单 */
export interface MenuForm {
  parent_id?: number;
  name: string;
  code?: string;
  type: string;
  icon?: string;
  route?: string;
  component?: string;
  redirect?: string;
  permission?: string;
  sort?: number;
  status?: number;
  is_hidden?: number;
  is_cache?: number;
}

/** 部门 */
export interface Department {
  id: number;
  parent_id: number;
  name: string;
  leader: string;
  phone: string;
  email: string;
  sort: number;
  status: number;
  level: number;
  path: string;
  created_at: string;
  updated_at: string;
  children?: Department[];
}

/** 部门表单 */
export interface DepartmentForm {
  parent_id?: number;
  name: string;
  leader?: string;
  phone?: string;
  email?: string;
  sort?: number;
  status?: number;
}

/** 岗位 */
export interface Position {
  id: number;
  name: string;
  code: string;
  sort: number;
  status: number;
  remark: string;
  created_at: string;
  updated_at: string;
}

/** 岗位查询参数 */
export interface PositionQuery extends PageParams {
  name?: string;
  code?: string;
  status?: number;
}

/** 岗位表单 */
export interface PositionForm {
  name: string;
  code: string;
  sort?: number;
  status?: number;
  remark?: string;
}

/** 字典类型 */
export interface Dictionary {
  id: number;
  name: string;
  code: string;
  status: number;
  remark: string;
  created_at: string;
  updated_at: string;
  items?: DictionaryItem[];
}

/** 字典查询参数 */
export interface DictionaryQuery extends PageParams {
  name?: string;
  code?: string;
  status?: number;
}

/** 字典表单 */
export interface DictionaryForm {
  name: string;
  code: string;
  status?: number;
  remark?: string;
}

/** 字典项 */
export interface DictionaryItem {
  id: number;
  dictionary_id: number;
  label: string;
  value: string;
  sort: number;
  status: number;
  remark: string;
  created_at: string;
  updated_at: string;
}

/** 字典项表单 */
export interface DictionaryItemForm {
  label: string;
  value: string;
  sort?: number;
  status?: number;
  remark?: string;
}
