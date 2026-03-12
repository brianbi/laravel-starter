<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { ElMessage } from "element-plus";
import { getUser, updateUser } from "@/api/system/user";
import { getRoleList } from "@/api/system/role";
import type { Role } from "@/api/system/types";

const props = defineProps<{
  visible: boolean;
  userId: number;
}>();

const emit = defineEmits<{
  "update:visible": [value: boolean];
  success: [];
}>();

const dialogVisible = computed({
  get: () => props.visible,
  set: val => emit("update:visible", val)
});

const loading = ref(false);
const submitLoading = ref(false);
const roleList = ref<Role[]>([]);
const selectedRoles = ref<number[]>([]);

// 加载角色列表
const loadRoleList = async () => {
  try {
    const res = await getRoleList({ per_page: 1000, status: 1 });
    roleList.value = res.data;
  } catch {
    roleList.value = [];
  }
};

// 加载用户角色
const loadUserRoles = async () => {
  if (!props.userId) return;
  loading.value = true;
  try {
    const res = await getUser(props.userId);
    selectedRoles.value = res.data.roles?.map(r => r.id) || [];
  } catch {
    selectedRoles.value = [];
  } finally {
    loading.value = false;
  }
};

// 打开弹窗
const handleOpen = () => {
  loadRoleList();
  loadUserRoles();
};

// 提交
const handleSubmit = async () => {
  submitLoading.value = true;
  try {
    await updateUser(props.userId, { role_ids: selectedRoles.value } as any);
    ElMessage.success("角色分配成功");
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
    title="分配角色"
    width="500px"
    :close-on-click-modal="false"
    destroy-on-close
    @open="handleOpen"
  >
    <el-checkbox-group v-model="selectedRoles" v-loading="loading">
      <el-checkbox
        v-for="role in roleList"
        :key="role.id"
        :value="role.id"
        :label="role.name"
        class="w-full mb-2"
      >
        <span>{{ role.name }}</span>
        <span class="text-gray-400 text-xs ml-2">{{ role.description }}</span>
      </el-checkbox>
    </el-checkbox-group>

    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
        确定
      </el-button>
    </template>
  </el-dialog>
</template>
