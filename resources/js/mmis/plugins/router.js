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