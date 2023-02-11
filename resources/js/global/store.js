import Vue from "vue";
import Vuex from "vuex";
import axios from "./axios";
// import router from "@/router/router";

Vue.use(Vuex);

const module = {
  state: {
    drawer:true,
    user:{},
    active_route: null,
    main_active_route: null,
    right_items:[]
  },
  getters: {
    drawer: state => state.drawer,
    active_route: state => state.active_route,
    main_active_route: state => state.main_active_route,
    user: state => state.user,
    right_items: state => state.right_items,
  },
  mutations: {
    setDrawer(state, value) {
      state.drawer = value;
    },
    setActiveRoute(state, value) {
      state.active_route = value;
    },
    setUser(state, value) {
      state.user = value;
    },
    setRightItems(state, value) {
      state.right_items = value;
    },
    setMainActiveRoute(state, value) {
      state.main_active_route = value;
    },
  },
  actions: {
    
    logOutUser({ commit, dispatch }) {
      axios.post("/logout").then(({ data }) => {
        localStorage.removeItem("token");
        commit("admin", null);
        commit("permission", []);
        if (router.currentRoute.path != "/") router.push({ path: "/" });
      });
    }
  }
};
export const store = new Vuex.Store({
  strict: true,
  modules: {
    module
  }
});