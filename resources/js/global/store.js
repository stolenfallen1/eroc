import Vue from "vue";
import Vuex from "vuex";
import axios from "./axios";
// import router from "@/router/router";

Vue.use(Vuex);

const module = {
  state: {
    active_route: null,
    user:{}
  },
  getters: {
    active_route: state => state.active_route,
    user: state => state.user,
  },
  mutations: {
    setActiveRoute(state, value) {
      state.active_route = value;
    },
    setUser(state, value) {
      state.user = value;
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