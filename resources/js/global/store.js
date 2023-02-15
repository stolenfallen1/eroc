import Vue from "vue";
import Vuex from "vuex";
import axios from "./axios";
import { apiGetAllUnits } from "./api/units"
// import router from "@/router/router";

Vue.use(Vuex);

const module = {
  state: {
    drawer:true,
    user:null,
    active_route: null,
    main_active_route: null,
    right_items:[],
    categories:[],
    units:[]
  },
  getters: {
    drawer: state => state.drawer,
    active_route: state => state.active_route,
    main_active_route: state => state.main_active_route,
    user: state => state.user,
    right_items: state => state.right_items,
    categories: state => state.categories,
    units: state => state.units,
  },
  mutations: {
    setDrawer(state) {
      state.drawer = !state.drawer;
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
    setCategories(state, value) {
      state.categories = value;
    },
    setUnits(state, value) {
      state.units = value;
    },
  },
  actions: {
    async fetchUnits({commit, dispatch}){
      let res = await apiGetAllUnits()
      commit("setUnits", res.data.units)
    },

    fetchCategories({commit, dispatch}){
      axios.get('api/categories').then(({data})=>{
        commit("setCategories", data.categories)
      })
    },

    fetchUserDetails({commit, dispatch}){
      axios.get('user-details').then(({data})=>{
        console.log(data)
        commit("setUser", data)
      })
    },

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