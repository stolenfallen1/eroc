import Vue from "vue";
import VueRouter from "vue-router";
import procurement from '../routes/procurements'

Vue.use(VueRouter);

const routes = [
    ...procurement
];

const router = new VueRouter({
    mode: "history",
    routes
})

router.beforeEach((to, from, next) => {
    next();
})

export default router;