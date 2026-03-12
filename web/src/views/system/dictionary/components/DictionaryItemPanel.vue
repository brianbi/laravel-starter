<script setup lang="ts">
import { ref, reactive, computed, watch } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import {
  getDictionaryItems,
  createDictionaryItem,
  updateDictionaryItem,
  deleteDictionaryItem
} from "@/api/system/dictionary";
import type { Dictionary, DictionaryItem, DictionaryItemForm } from "@/api/system/types";

import AddIcon from "~icons/ep/plus";
import EditIcon from "~icons/ep/edit";
import DeleteIcon from "~icons/ep/delete";

const props = defineProps<{ visible: boolean; dictionary: Dictionary | null }>();
const emit = defineEmits<{ "update:visible": [value: boolean] }>();

const drawerVisible = computed({ get: () => props.visible, set: val => emit("update:visible", val) });

const loading = ref(false);
const tableData = ref<DictionaryItem[]>([]);

// 表单
const formVisible = ref(false);
const formTitle = ref("新增字典项");
const formRef = ref<FormInstance>();
const submitLoading = ref(false);
const currentItem = ref<DictionaryItem | null>(null);

const formData = reactive<DictionaryItemForm>({ label: "", value: "", sort: 0, status: 1, remark: "" });
const rules = reactive<FormRules>({
  label: [{ required: true, message: "请输入标签", trigger: "blur" }],
  value: [{ required: true, message: "请输入值", trigger: "blur" }]
});

const isEdit = computed(() => !!currentItem.value);

const loadItems = async () => {
  if (!props.dictionary?.id) return;
  loading.value = true;
  try {
    const res = await getDictionaryItems(props.dictionary.id);
    tableData.value = res.data;
  } catch {
    tableData.value = [];
  } finally {
    loading.value = false;
  }
};

watch(() => props.visible, (val) => { if (val) loadItems(); });

const handleAdd = () => {
  currentItem.value = null;
  formTitle.value = "新增字典项";
  Object.assign(formData, { label: "", value: "", sort: 0, status: 1, remark: "" });
  formVisible.value = true;
};

const handleEdit = (row: DictionaryItem) => {
  currentItem.value = row;
  formTitle.value = "编辑字典项";
  Object.assign(formData, row);
  formVisible.value = true;
};

const handleDelete = async (row: DictionaryItem) => {
  if (!props.dictionary?.id) return;
  try {
    await ElMessageBox.confirm(`确认删除字典项「${row.label}」吗？`, "提示", { type: "warning" });
    await deleteDictionaryItem(props.dictionary.id, row.id);
    ElMessage.success("删除成功");
    loadItems();
  } catch {}
};

const handleSubmit = async () => {
  if (!props.dictionary?.id) return;
  const valid = await formRef.value?.validate().catch(() => false);
  if (!valid) return;
  submitLoading.value = true;
  try {
    if (isEdit.value && currentItem.value) {
      await updateDictionaryItem(props.dictionary.id, currentItem.value.id, formData);
    } else {
      await createDictionaryItem(props.dictionary.id, formData);
    }
    ElMessage.success(isEdit.value ? "更新成功" : "创建成功");
    formVisible.value = false;
    loadItems();
  } catch (e: any) {
    ElMessage.error(e?.message || "操作失败");
  } finally {
    submitLoading.value = false;
  }
};
</script>

<template>
  <el-drawer v-model="drawerVisible" :title="`字典项管理 - ${dictionary?.name}`" size="700px" destroy-on-close>
    <div class="mb-4">
      <el-button type="primary" :icon="AddIcon" @click="handleAdd">新增字典项</el-button>
    </div>

    <el-table v-loading="loading" :data="tableData" border stripe>
      <el-table-column label="标签" prop="label" min-width="120" />
      <el-table-column label="值" prop="value" min-width="120" />
      <el-table-column label="排序" prop="sort" width="80" />
      <el-table-column label="状态" prop="status" width="80">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
            {{ row.status === 1 ? "启用" : "停用" }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="备注" prop="remark" min-width="150" />
      <el-table-column label="操作" width="140" fixed="right">
        <template #default="{ row }">
          <el-button type="primary" link :icon="EditIcon" @click="handleEdit(row)">编辑</el-button>
          <el-button type="danger" link :icon="DeleteIcon" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 字典项表单弹窗 -->
    <el-dialog v-model="formVisible" :title="formTitle" width="450px" :close-on-click-modal="false" append-to-body>
      <el-form ref="formRef" :model="formData" :rules="rules" label-width="80px">
        <el-form-item label="标签" prop="label">
          <el-input v-model="formData.label" placeholder="请输入标签" />
        </el-form-item>
        <el-form-item label="值" prop="value">
          <el-input v-model="formData.value" placeholder="请输入值" />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="formData.sort" :min="0" controls-position="right" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="formData.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">停用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注" prop="remark">
          <el-input v-model="formData.remark" type="textarea" :rows="2" placeholder="请输入备注" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </el-drawer>
</template>
