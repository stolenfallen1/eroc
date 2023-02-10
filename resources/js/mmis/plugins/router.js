import Vue from "vue";
import VueRouter from "vue-router";
import procurement from '../routes/procurements'
import { store } from "@global/store";

Vue.use(VueRouter);

const routes = [
    ...procurement
];

const router = new VueRouter({
    mode: "history",
    routes
})

router.beforeEach((to, from, next) => {
    store.commit("setActiveRoute", to.name);
    next();
})

export default router;