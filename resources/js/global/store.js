import Vue from "vue";
import Vuex from "vuex";
import {httpClient, httpApiClient} from "./axios";
// import router from "@/router/router";

Vue.use(Vuex);

const module = {
  state: {
    drawer:true,
    user:null,
    user_permissions:[],
    active_route: null,
    main_active_route: null,
    right_items:[],
    item_groups:[],
    units:[],
    status:[],
    priorities:[],
    prsn_settings:null,
  },
  getters: {
    prsn_settings: state => state.prsn_settings,
    drawer: state => state.drawer,
    active_route: state => state.active_route,
    main_active_route: state => state.main_active_route,
    user: state => state.user,
    user_permissions: state => state.user_permissions,
    right_items: state => state.right_items,
    item_groups: state => state.item_groups,
    units: state => state.units,
    status: state => state.status,
    priorities: state => state.priorities,
  },
  mutations: {
    setPRSNSettings(state, value) {
      state.prsn_settings = value;
    },
    setDrawer(state) {
      state.drawer = !state.drawer;
    },
    setActiveRoute(state, value) {
      state.active_route = value;
    },
    setUser(state, value) {
      state.user = value;
    },
    setUserPermissions(state, value) {
      state.user_permissions = value;
    },
    setRightItems(state, value) {
      state.right_items = value;
    },
    setMainActiveRoute(state, value) {
      state.main_active_route = value;
    },
    setItemGroups(state, value) {
      state.item_groups = value;
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
    async fetchSettings({commit, state}){
      httpApiClient.get('system-settings',{ headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({data})=>{
        commit("setPRSNSettings", data.settings)
      })
    },

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

    async fetchItemGroups({commit, state}){
      console.log(state.user.api_token,"state.user")
      httpApiClient.get('items-group',{ headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({data})=>{
        commit("setItemGroups", data.item_groups)
      })
    },

    async fetchUserDetails({commit, dispatch}){
      httpClient.get('user-details').then(({data})=>{
        // this.$store.dispatch("fetchUnits")
        console.log(data)
        commit("setUser", data.usersdetails)
        if(data.usersdetails.role){
          commit("setUserPermissions", data.usersdetails.role.permissions)
        }
        dispatch("fetchItemGroups")
        dispatch("fetchUnits")
        dispatch("fetchStatus")
        dispatch("fetchPriorities")
        dispatch("fetchSettings")
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