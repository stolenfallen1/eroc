require('../bootstrap');
import Vue from "vue";
import {httpClient} from "../global/axios";
import vuetify from "../global/vuetify";
import router from "./plugins/router";
import '@global/mixin';
import { store } from '@global/store';

import App from "./layouts/main.vue";

const app = new Vue({
    vuetify,
    router,
    httpClient,
    // httpApiClient,
    store,
    // i18n,
    el: "#mmis",
    render: h => h(App),
});