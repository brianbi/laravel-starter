<script setup lang="ts">
import { ref, reactive, computed, watch } from "vue";
import { ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import { createMenu, updateMenu, getMenu } from "@/api/system/menu";
import type { Menu, MenuForm as MenuFormType } from "@/api/system/types";

const props = defineProps<{
  visible: boolean;
  title: string;
  row: Menu | null;
  parentId: number;
  menuTree: Menu[];
}>();

const emit = defineEmits<{
  "update:visible": [value: boolean];
  success: [];
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

const formRef = ref<FormInstance>();
const loading = ref(false);
const submitLoading = ref(false);

const formData = reactive<MenuFormType>({
  parent_id: 0,
  name: "",
  code: "",
  type: "menu",
  icon: "",
  route: "",
  component: "",
  redirect: "",
  permission: "",
  sort: 0,
  status: 1,
  is_hidden: 0,
  is_cache: 1
});

const rules = reactive<FormRules>({
  name: [{ required: true, message: "请输入菜单名称", trigger: "blur" }],
  type: [{ required: true, message: "请选择菜单类型", trigger: "change" }],
  route: [{ required: true, message: "请输入路由路径", trigger: "blur" }]
});

const isEdit = computed(() => !!props.row);

const menuTypeOptions = [
  { label: "目录", value: "directory" },
  { label: "菜单", value: "menu" },
  { label: "按钮", value: "button" }
];

// 常用图标
const iconOptions = [
  "HomeFilled", "User", "Setting", "Menu", "Document", "Folder",
  "Edit", "Delete", "Search", "Plus", "Minus", "Check", "Close",
  "Lock", "Unlock", "Key", "Message", "Bell", "Calendar", "Clock"
];

const loadMenuDetail = async () => {
  if (!props.row?.id) return;
  loading.value = true;
  try {
    const res = await getMenu(props.row.id);
    const menu = res.data;
    Object.assign(formData, {
      parent_id: menu.parent_id,
      name: menu.name,
      code: menu.code || "",
      type: menu.type,
      icon: menu.icon || "",
      route: menu.route || "",
      component: menu.component || "",
      redirect: menu.redirect || "",
      permission: menu.permission || "",
      sort: menu.sort,
      status: menu.status,
      is_hidden: menu.is_hidden,
      is_cache: menu.is_cache
    });
  } catch {
    ElMessage.error("获取菜单信息失败");
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  formData.parent_id = props.parentId;
  formData.name = "";
  formData.code = "";
  formData.type = "menu";
  formData.icon = "";
  formData.route = "";
  formData.component = "";
  formData.redirect = "";
  formData.permission = "";
  formData.sort = 0;
  formData.status = 1;
  formData.is_hidden = 0;
  formData.is_cache = 1;
  formRef.value?.clearValidate();
};

const handleOpen = () => {
  if (props.row) {
    loadMenuDetail();
  } else {
    resetForm();
    formData.parent_id = props.parentId;
  }
};

const handleSubmit = async () => {
  const valid = await formRef.value?.validate().catch(() => false);
  if (!valid) return;

  submitLoading.value = true;
  try {
    if (isEdit.value && props.row) {
      await updateMenu(props.row.id, formData);
      ElMessage.success("更新成功");
    } else {
      await createMenu(formData);
      ElMessage.success("创建成功");
    }
    emit("success");
    dialogVisible.value = false;
  } catch (error: any) {
    ElMessage.error(error?.message || "操作失败");
  } finally {
    submitLoading.value = false;
  }
};
</script>

<template>
  <el-dialog
    v-model="dialogVisible"
    :title="title"
    width="650px"
    :close-on-click-modal="false"
    destroy-on-close
    @open="handleOpen"
    @close="resetForm"
  >
    <el-form
      ref="formRef"
      v-loading="loading"
      :model="formData"
      :rules="rules"
      label-width="100px"
    >
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="上级菜单" prop="parent_id">
            <el-tree-select
              v-model="formData.parent_id"
              :data="[{ id: 0, name: '根目录', children: menuTree }]"
              :props="{ label: 'name', value: 'id', children: 'children' }"
              check-strictly
              filterable
              default-expand-all
              class="w-full"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="菜单类型" prop="type">
            <el-radio-group v-model="formData.type">
              <el-radio-button
                v-for="item in menuTypeOptions"
                :key="item.value"
                :value="item.value"
              >
                {{ item.label }}
              </el-radio-button>
            </el-radio-group>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="菜单名称" prop="name">
            <el-input v-model="formData.name" placeholder="请输入菜单名称" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item v-if="formData.type !== 'button'" label="菜单图标" prop="icon">
            <el-select v-model="formData.icon" placeholder="请选择图标" clearable filterable class="w-full">
              <el-option v-for="icon in iconOptions" :key="icon" :label="icon" :value="icon">
                <div class="flex items-center">
                  <el-icon class="mr-2"><component :is="icon" /></el-icon>
                  <span>{{ icon }}</span>
                </div>
              </el-option>
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row v-if="formData.type !== 'button'" :gutter="20">
        <el-col :span="12">
          <el-form-item label="路由路径" prop="route">
            <el-input v-model="formData.route" placeholder="如：/system/user" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="组件路径" prop="component">
            <el-input v-model="formData.component" placeholder="如：system/user/index" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="权限标识" prop="permission">
            <el-input v-model="formData.permission" placeholder="如：system:user:list" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="排序" prop="sort">
            <el-input-number v-model="formData.sort" :min="0" controls-position="right" class="w-full" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row v-if="formData.type !== 'button'" :gutter="20">
        <el-col :span="8">
          <el-form-item label="状态" prop="status">
            <el-radio-group v-model="formData.status">
              <el-radio :value="1">显示</el-radio>
              <el-radio :value="0">隐藏</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
        <el-col :span="8">
          <el-form-item label="隐藏菜单" prop="is_hidden">
            <el-radio-group v-model="formData.is_hidden">
              <el-radio :value="0">否</el-radio>
              <el-radio :value="1">是</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
        <el-col :span="8">
          <el-form-item label="缓存页面" prop="is_cache">
            <el-radio-group v-model="formData.is_cache">
              <el-radio :value="1">是</el-radio>
              <el-radio :value="0">否</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>
