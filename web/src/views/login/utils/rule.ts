import { reactive } from "vue";
import type { FormRules } from "element-plus";

/** 密码正则（密码格式应为6-18位数字、字母、符号的任意组合） */
export const REGEXP_PWD = /^.{6,18}$/;

/** 登录校验 */
const loginRules = reactive<FormRules>({
  username: [
    {
      required: true,
      message: "请输入账号",
      trigger: "blur"
    }
  ],
  password: [
    {
      required: true,
      message: "请输入密码",
      trigger: "blur"
    },
    {
      min: 6,
      max: 18,
      message: "密码长度应为6-18位",
      trigger: "blur"
    }
  ]
});

export { loginRules };
