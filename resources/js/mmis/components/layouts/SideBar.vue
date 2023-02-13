<template>
  <div>
    <v-navigation-drawer width="220" v-model="drawer" app>
      <div class="logo">MMIS</div>
      <v-list nav>
        <v-list-group
          v-for="(menu, index) in menus"
          :key="index"
          :value="true"
          dense
        >
          <v-icon slot="prependIcon" small color="primary">{{
            menu.icon
          }}</v-icon>
          <template v-slot:activator>
            <v-list-item-title>{{ menu.name }}</v-list-item-title>
          </template>
          <v-list-item
            v-for="(child, i) in menu.children"
            @click="selectedRoute(child.route)"
            :key="i"
            class="pl-7"
            dense
            link
            :class="{ 'active-route': activeRoute == child.route }"
          >
            <v-list-item-icon>
              <v-icon v-text="child.icon" color="primary" small></v-icon>
            </v-list-item-icon>
            <v-list-item-title v-text="child.name"></v-list-item-title>
          </v-list-item>
        </v-list-group>
      </v-list>
    </v-navigation-drawer>
    <app-bar :drawer="drawer" @toggle="toggleSide" />
  </div>
</template>
<script>
import MenuItems from "../../includes/MenuItems";
import AppBar from "./AppBar.vue";
export default {
  components: {
    AppBar,
  },
  props: {},
  data() {
    return {
      menus: MenuItems,
      drawer: true,
    };
  },
  methods: {
    toggleSide(val) {
      this.drawer = val;
    },
    selectedRoute(child, parent) {
      this.active_route = child;

      if (this.$route.name != child) this._push(child);
    },
  },
  computed: {
    activeRoute() {
      return this._getters("active_route");
    },
  },
};
</script>
<style lang="scss" scoped>
.logo {
  height: 63.5px;
  font-size: 1.5rem;
  display: grid;
  place-items: center;
}
.active-route {
  background: #00acc4;
}
.active-route div {
  color: white;
}
</style>