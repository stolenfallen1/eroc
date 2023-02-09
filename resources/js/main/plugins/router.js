import Vue from "vue";
import VueRouter from "vue-router";
import main from '../routes/index'

Vue.use(VueRouter);

const routes = [
    ...main
];

const router = new VueRouter({
    mode: "history",
    routes
})

router.beforeEach((to, from, next) => {
    next();
})

export default router;