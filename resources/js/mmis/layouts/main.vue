<template>
  <!-- App.vue -->
  <v-app>
    <!-- <v-navigation-drawer app>
    </v-navigation-drawer> -->
    <side-bar @drawer="isdrawer = !isdrawer" />

    <!-- Sizes your content based upon application components -->
    <v-main>
      <!-- Provides the application the proper gutter -->
      <v-container fluid>
        <!-- If using vue-router -->
        <router-view
          :class="isdrawer ? 'router-main-container' : ''"
        ></router-view>
      </v-container>
        <!-- {{right_items}} -->
      <v-navigation-drawer width="220" v-model="isdrawer" absolute right>
        <v-list nav v-if="right_items.length > 0">
          <v-list-item
            v-for="(child, i) in right_items"
            @click="selectedRoute(child.route)"
            :key="i"
            class="pl-7"
            dense
            link
            :class="{ 'active-route': active_route == child.route, }"
          >
            <v-list-item-icon>
              <v-icon v-text="child.icon" color="primary" small></v-icon>
            </v-list-item-icon>
            <v-list-item-title v-text="child.name"></v-list-item-title>
          </v-list-item>
        </v-list>
        <v-divider></v-divider>
      </v-navigation-drawer>
    </v-main>

    <v-footer app>
      <!-- -->
    </v-footer>
  </v-app>
</template>
<script>
import { mapGetters } from "vuex";
import SideBar from "../components/layouts/SideBar.vue";
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
    selectedRoute(child, parent) {
      console.log(child, "child");
      console.log(this.$route.name, "this.$route.name");
      if (this.$route.name != child.route) this._push(child);
    },
  },
  computed: {
    ...mapGetters(["right_items", "active_route"]),
  },
};
</script>