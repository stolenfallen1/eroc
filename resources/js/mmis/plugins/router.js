import Vue from "vue";
import VueRouter from "vue-router";
import procurement from '../routes/procurements'
import { store } from "@global/store";
import menus from "../includes/MenuItems"

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
  console.log(path, "to")
  if (path.length == 4) {
    console.log(to.name, "to name")
    store.commit("setActiveRoute", to.name);
  } else {
    store.commit("setActiveRoute", null);
  }
  store.commit("setMainActiveRoute", path[2]);
  next();
})

export default router;