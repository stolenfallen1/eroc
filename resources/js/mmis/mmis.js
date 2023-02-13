require('../bootstrap');
import Vue from "vue";
import axios from "../global/axios";
import vuetify from "../global/vuetify";
import router from "./plugins/router";
import '@global/mixin';
import { store } from '@global/store';

import App from "./layouts/main.vue";

const app = new Vue({
    vuetify,
    router,
    axios,
    store,
    // i18n,
    el: "#mmis",
    render: h => h(App),
});