require('../bootstrap');
import Vue from "vue";
import {httpClient} from "../global/axios";
import vuetify from "../global/vuetify";
import router from "./plugins/router";
import '@global/mixin';
import { store } from '@global/store';

import App from "./layouts/main.vue";

// auto-import components
const files = require.context("@global/components/", true, /\.vue$/i);
files.keys().map(key =>
    Vue.component(
        key
        .split("/")
        .pop()
        .split(".")[0],
        files(key).default
    )
);

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