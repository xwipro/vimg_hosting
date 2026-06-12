import { createRouter, createWebHistory } from "vue-router";
import { jwtDecode } from "jwt-decode";

const route = [
    {
        path: "/",
        name: "Home",
        meta: {
            title: "v图床",
            requireAuth: true
        },
        component: () => import("./views/Home.vue")
    },
    {
        path: "/login",
        name: "Login",
        meta: {
            title: "尊敬的管理员，请登录。",
            requireAuth: false
        },
        component: () => import("./views/Login.vue")
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes: route
})

/**
 * 校验JWT是否有效（是否过期、格式合法）
 * @param token 本地存储的token
 * @returns true=有效  false=无效/过期
 */
const checkTokenValid = (token: string | null): boolean => {
  if (!token) return false;

  try {
    // 解析jwt载荷
    const decoded: any = jwtDecode(token);
    // exp 是JWT标准过期时间戳(秒)，和当前时间对比
    const currentTime = Math.floor(Date.now() / 1000);
    // 过期 / 无exp字段 都判定无效
    if (!decoded.exp || decoded.exp < currentTime) {
      return false;
    }
    return true;
  } catch (err) {
    // 解析失败 = 非法token（篡改、格式错误）
    return false;
  }
}


// 前置守卫
router.beforeEach((to) => {
    document.title = to.meta.title as string || "v图床";
    const token = localStorage.getItem("token")
    const isTokenValid = checkTokenValid(token)
    if(to.meta.requireAuth && !isTokenValid) {
        // 清除token
        localStorage.removeItem("token")
        return "/login"
    }
    else if(to.path === "/login" && isTokenValid) {
        return "/"
    }
    return true
})

export default router
