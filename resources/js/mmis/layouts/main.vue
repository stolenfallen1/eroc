<template>
  <!-- App.vue -->
  <v-app>
    <!-- <v-navigation-drawer app>
    </v-navigation-drawer> -->
    <side-bar @drawer="isdrawer = !isdrawer" />

    <!-- Sizes your content based upon application components -->
    <v-main v-if="user">
      <!-- Provides the application the proper gutter -->
      <v-container fluid>
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
import { mapGetters } from "vuex"
export default {
  components: {
    SideBar,
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
    this.$store.dispatch("fetchUserDetails")
    this.$store.dispatch("fetchCategories")
    this.$store.dispatch("fetchUnits")
  }
};
</script>