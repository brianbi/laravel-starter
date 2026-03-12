import type { TreeNode, FlatToTreeOptions } from "./types";

/**
 * 将扁平数组转换为树形结构
 * @param flatData 扁平数组数据
 * @param options 配置选项
 * @returns 树形结构数据
 */
export function flatToTree<T extends Record<string, any>>(
  flatData: T[],
  options: FlatToTreeOptions = {}
): TreeNode[] {
  const {
    idField = "id",
    parentIdField = "parent_id",
    childrenField = "children",
    rootParentId = null
  } = options;

  const map = new Map<string | number, TreeNode>();
  const result: TreeNode[] = [];

  // 先创建所有节点的映射
  flatData.forEach(item => {
    const id = item[idField];
    const node: TreeNode = {
      ...item,
      id,
      label: item.label ?? item.name ?? item.title ?? String(id),
      value: id,
      children: []
    };
    map.set(id, node);
  });

  // 构建树形结构
  flatData.forEach(item => {
    const id = item[idField];
    const parentId = item[parentIdField];
    const node = map.get(id)!;

    if (parentId === rootParentId || parentId === 0 || !parentId) {
      result.push(node);
    } else {
      const parent = map.get(parentId);
      if (parent) {
        if (!parent.children) {
          parent.children = [];
        }
        parent.children.push(node);
      } else {
        // 父节点不存在，作为根节点
        result.push(node);
      }
    }
  });

  // 移除空的 children 数组
  const removeEmptyChildren = (nodes: TreeNode[]): void => {
    nodes.forEach(node => {
      if (node.children && node.children.length === 0) {
        delete node.children;
        node.isLeaf = true;
      } else if (node.children) {
        removeEmptyChildren(node.children);
      }
    });
  };
  removeEmptyChildren(result);

  return result;
}

/**
 * 将树形数据转换为扁平数组
 * @param treeData 树形数据
 * @param childrenField 子节点字段名
 * @returns 扁平数组
 */
export function treeToFlat<T extends TreeNode>(
  treeData: T[],
  childrenField = "children"
): Omit<T, "children">[] {
  const result: Omit<T, "children">[] = [];

  const traverse = (nodes: T[]) => {
    nodes.forEach(node => {
      const { [childrenField]: children, ...rest } = node as any;
      result.push(rest);
      if (children && children.length > 0) {
        traverse(children);
      }
    });
  };

  traverse(treeData);
  return result;
}

/**
 * 查找树节点
 * @param treeData 树形数据
 * @param predicate 查找条件
 * @param childrenField 子节点字段名
 * @returns 找到的节点或 null
 */
export function findTreeNode<T extends TreeNode>(
  treeData: T[],
  predicate: (node: T) => boolean,
  childrenField = "children"
): T | null {
  for (const node of treeData) {
    if (predicate(node)) {
      return node;
    }
    const children = (node as any)[childrenField] as T[] | undefined;
    if (children && children.length > 0) {
      const found = findTreeNode(children, predicate, childrenField);
      if (found) return found;
    }
  }
  return null;
}

/**
 * 过滤树节点
 * @param treeData 树形数据
 * @param predicate 过滤条件
 * @param childrenField 子节点字段名
 * @returns 过滤后的树形数据
 */
export function filterTree<T extends TreeNode>(
  treeData: T[],
  predicate: (node: T) => boolean,
  childrenField = "children"
): T[] {
  return treeData
    .filter(node => {
      const children = (node as any)[childrenField] as T[] | undefined;
      if (children && children.length > 0) {
        const filteredChildren = filterTree(children, predicate, childrenField);
        (node as any)[childrenField] = filteredChildren;
        return predicate(node) || filteredChildren.length > 0;
      }
      return predicate(node);
    })
    .map(node => ({ ...node }));
}

/**
 * 获取所有父节点ID
 * @param treeData 树形数据
 * @param targetId 目标节点ID
 * @param idField ID字段名
 * @param childrenField 子节点字段名
 * @returns 父节点ID数组（从根到父）
 */
export function getParentIds<T extends TreeNode>(
  treeData: T[],
  targetId: string | number,
  idField = "id",
  childrenField = "children"
): (string | number)[] {
  const path: (string | number)[] = [];

  const find = (nodes: T[], target: string | number): boolean => {
    for (const node of nodes) {
      const nodeId = (node as any)[idField];
      const children = (node as any)[childrenField] as T[] | undefined;

      if (nodeId === target) {
        return true;
      }

      if (children && children.length > 0) {
        path.push(nodeId);
        if (find(children, target)) {
          return true;
        }
        path.pop();
      }
    }
    return false;
  };

  find(treeData, targetId);
  return path;
}

/**
 * 获取所有子节点ID
 * @param node 节点
 * @param idField ID字段名
 * @param childrenField 子节点字段名
 * @returns 所有子节点ID
 */
export function getChildIds<T extends TreeNode>(
  node: T,
  idField = "id",
  childrenField = "children"
): (string | number)[] {
  const result: (string | number)[] = [];

  const traverse = (nodes: T[]) => {
    nodes.forEach(n => {
      result.push((n as any)[idField]);
      const children = (n as any)[childrenField] as T[] | undefined;
      if (children && children.length > 0) {
        traverse(children);
      }
    });
  };

  const children = (node as any)[childrenField] as T[] | undefined;
  if (children && children.length > 0) {
    traverse(children);
  }

  return result;
}
