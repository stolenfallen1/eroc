require('../bootstrap');
import Vue from "vue";
import axios from "../global/axios";
import vuetify from "../global/vuetify";
import router from "./plugins/router";

import App from "./pages/index.vue";

const app = new Vue({
    vuetify,
    router,
    axios,
    // store,
    // i18n,
    el: "#app",
    render: h => h(App),
});
