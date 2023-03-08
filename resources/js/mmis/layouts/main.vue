<template>
  <!-- App.vue -->
  <v-app id="inspire">
    <v-system-bar color="white"  height="50" app>
       <v-icon large>mdi-laptop</v-icon>
       <h3>Material Management and Inventory System</h3>
      <v-spacer></v-spacer>
      <appnotification></appnotification>
      <MessagesBar></MessagesBar>
      <UserMenuBar></UserMenuBar>
    </v-system-bar>
    
    <!-- <v-navigation-drawer app>
    </v-navigation-drawer> -->
    <side-bar @drawer="isdrawer = !isdrawer" />

    <!-- Sizes your content based upon application components -->
    <v-main v-if="user" class="main">
      <!-- Provides the application the proper gutter -->
      <v-container class="ma-0 pa-0" fluid>
        <!-- If using vue-router -->
        <router-view
          :class="isdrawer ? 'router-main-container' : ''"
        ></router-view>
      </v-container>
    </v-main>

    <v-footer app>
      <!-- -->
    </v-footer>
  </v-app>
</template>
<script>
import SideBar from "../components/layouts/SideBar.vue";
import Appnotification from "../components/layouts/NotificationBar.vue";
import MessagesBar from "../components/layouts/MessagesBar.vue";
import UserMenuBar from "../components/layouts/UserMenuBar.vue";
import { mapGetters } from "vuex"
export default {
  components: {
    SideBar, Appnotification,MessagesBar,UserMenuBar
  },
  data() {
    return {
      isdrawer: true,
    };
  },
  methods: {
    toggleSide(val) {
      this.isdrawer = val;
    },
  },
  computed: {
    ...mapGetters(["user"])
  },
  created(){
    
  }
};
</script>