import Vue from "vue";
import PageHelper from "./mixins/pageHelper.vue";
import AuthHelper from "./mixins/AuthHelper.vue";

Vue.mixin(PageHelper);
Vue.mixin(AuthHelper);