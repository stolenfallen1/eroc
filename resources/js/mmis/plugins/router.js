import Vue from "vue";
import VueRouter from "vue-router";
import procurement from '../routes/procurements'
import item_management from '../routes/item_management'


import { store } from "@global/store";

Vue.use(VueRouter);

const routes = [
    ...procurement,
    ...item_management
];

const router = new VueRouter({
    mode: "history",
    routes
})

router.beforeEach((to, from, next) => {
    store.dispatch("fetchUserDetails")
    let path = to.path.split("/")
    if (path.length == 4) {
        store.commit("setActiveRoute", to.name);
    } else {
        store.commit("setActiveRoute", null);
    }
    store.commit("setMainActiveRoute", path[2]);
    next();
})

export default router;