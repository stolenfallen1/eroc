import Vue from "vue";
import Vuex from "vuex";
import {httpClient, httpApiClient} from "./axios";
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
    units:[],
    status:[],
    priorities:[]
  },
  getters: {
    drawer: state => state.drawer,
    active_route: state => state.active_route,
    main_active_route: state => state.main_active_route,
    user: state => state.user,
    right_items: state => state.right_items,
    categories: state => state.categories,
    units: state => state.units,
    status: state => state.status,
    priorities: state => state.priorities,
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
    setStatus(state, value) {
      state.status = value;
    },
    setPriorities(state, value) {
      state.priorities = value;
    },
  },
  actions: {
    async fetchPriorities({commit, state}){
      httpApiClient.get('priorities',{ headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({data})=>{
        commit("setPriorities", data.priorities)
      })
    },

    async fetchStatus({commit, state}){
      httpApiClient.get('status',{ headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({data})=>{
        commit("setStatus", data.status)
      })
    },

    async fetchUnits({commit, state}){
      httpApiClient.get('units',{ headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({data})=>{
        commit("setUnits", data.units)
      })
    },

    async fetchCategories({commit, state}){
      console.log(state.user.api_token,"state.user")
      httpApiClient.get('categories',{ headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({data})=>{
        commit("setCategories", data.categories)
      })
    },

    async fetchUserDetails({commit, dispatch}){
      httpClient.get('user-details').then(({data})=>{
        // this.$store.dispatch("fetchUnits")
        console.log(data)
        commit("setUser", data)
        dispatch("fetchCategories")
        dispatch("fetchUnits")
        dispatch("fetchStatus")
        dispatch("fetchPriorities")
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