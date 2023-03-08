import Vue from "vue";
import Vuex from "vuex";
import { httpClient, httpApiClient } from "./axios";
// import router from "@/router/router";

Vue.use(Vuex);

const module = {
    state: {
        drawer: true,
        user: null,
        user_permissions: [],
        active_route: null,
        main_active_route: null,
        right_items: [],
        item_groups: [],
        units: [],
        status: [],
        priorities: [],
        prsn_settings: null,
        brand: [],
        drug_administration: [],
        antibiotic_class: [],
        therapeutic_class: [],
        generic_names: [],
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
        brand: state => state.brand,
        drug_administration: state => state.drug_administration,
        antibiotic_class: state => state.antibiotic_class,
        therapeutic_class: state => state.therapeutic_class,
        generic_names: state => state.generic_names,
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
        setBrand(state, value) {
            state.brand = value;
        },
        setDrugAdministration(state, value) {
            state.drug_administration = value;
        },
        setAntibioticClass(state, value) {
            state.antibiotic_class = value;
        },
        setGenericName(state, value) {
            state.generic_names = value;
        },
        setTherapeuticClass(state, value) {
            state.therapeutic_class = value;
        },
    },
    actions: {
        async fetchSettings({ commit, state }) {
            httpApiClient.get('system-settings', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setPRSNSettings", data.settings)
            })
        },

        async fetchPriorities({ commit, state }) {
            if (state.priorities.length) return
            httpApiClient.get('priorities', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setPriorities", data.priorities)
            })
        },

        async fetchStatus({ commit, state }) {
            if (state.status.length) return
            httpApiClient.get('status', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setStatus", data.status)
            })
        },

        async fetchUnits({ commit, state }) {
            if (state.units.length) return
            httpApiClient.get('units', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setUnits", data.units)
            })
        },

        async fetchItemGroups({ commit, state }) {
            if (state.item_groups.length) return
            console.log(state.user.api_token, "state.user")
            httpApiClient.get('items-group', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setItemGroups", data.item_groups)
            })
        },

        async fetchUserDetails({ commit, dispatch, state }) {
            if (state.user != null) return
            httpClient.get('user-details').then(({ data }) => {
                // this.$store.dispatch("fetchUnits")
                console.log(data)
                commit("setUser", data.usersdetails)
                if (data.usersdetails.role) {
                    commit("setUserPermissions", data.usersdetails.role.permissions)
                }
                dispatch("fetchSettings")
                    // dispatch("fetchItemGroups")
                    // dispatch("fetchUnits")
                    // dispatch("fetchStatus")
                    // dispatch("fetchPriorities")
            })
        },

        async fetchBrand({ commit, state }) {
            if (state.brand.length) return
            httpApiClient.get('brand', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setBrand", data.brand)
            })
        },

        async fetchDrugAdministration({ commit, state }) {
            httpApiClient.get('drug-administration', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setDrugAdministration", data.drug_administration)
            })
        },

        async fetchAntibioticClass({ commit, state }) {
            httpApiClient.get('antibiotic', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setAntibioticClass", data.antibiotics)
            })
        },

        async fetchGenericNames({ commit, state }) {
            httpApiClient.get('generic-name', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setGenericName", data.generic_name)
            })
        },

        async fetchTherapeuticClass({ commit, state }) {
            httpApiClient.get('therapeutic-class', { headers: { Authorization: 'Bearer ' + state.user.api_token } }).then(({ data }) => {
                commit("setTherapeuticClass", data.therapeutic_class)
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