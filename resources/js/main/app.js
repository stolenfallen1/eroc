require('../bootstrap');
import Vue from "vue";
import {httpClient, httpApiClient} from "../global/axios";
import vuetify from "../global/vuetify";
import router from "./plugins/router";
import {store} from "@global/store"

import App from "./pages/index.vue";

const app = new Vue({
    vuetify,
    router,
    httpClient,
    httpApiClient,
    store,
    // i18n,
    el: "#app",
    render: h => h(App),
});
