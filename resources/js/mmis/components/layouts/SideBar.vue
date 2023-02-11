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
          <v-icon class="list-icon" slot="prependIcon" small color="primary">{{
            menu.icon
          }}</v-icon>
          <template v-slot:activator>
            <v-list-item-title>{{ menu.name }}</v-list-item-title>
          </template>
          <v-list-item
            v-for="(child, i) in menu.children"
            @click="selectedRoute(child)"
            :key="i"
            class="ml-4"
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
    <!-- <v-navigation-drawer width="220" v-model="drawer" right app>
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
            :class="{'active-route': activeRoute == child.route,}"
          >
            <v-list-item-icon>
              <v-icon v-text="child.icon" color="primary" small></v-icon>
            </v-list-item-icon>
            <v-list-item-title v-text="child.name"></v-list-item-title>
          </v-list-item>
        </v-list-group>
      </v-list>
    </v-navigation-drawer> -->
    <app-bar :drawer="drawer" @toggle="toggleSide" />
  </div>
</template>
<script>
import MenuItems from "../../includes/MenuItems";
import AppBar from "./AppBar.vue";
import { mapMutations } from "vuex";
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
    ...mapMutations(["setRightItems"]),
    toggleSide(val) {
      this.$emit("drawer");
      this.drawer = val;
    },
    selectedRoute(child, parent) {
      console.log(child, "child");
      this.active_route = child.route;
      if (child.sub_childrens && child.sub_childrens.length > 0)
        this.setRightItems(child.sub_childrens);

      if (this.$route.name != child.route) this._push(child.route);
    },
  },
  computed: {
    activeRoute() {
      return this._getters("main_active_route");
    },
  },
  watch: {
    $route(to, from) {
      let path = to.path.split("/");
      console.log(from, "from watch");
      if (from.path == "/") {
        let items = this.menus.map((menu) => {
          if (menu.children) {
            return menu.children.map((child) => {
              if (path[2] == child.route) {
                console.log(child.sub_childrens, "child.sub_childrens");
                this.$store.commit("setRightItems", child.sub_childrens);
              }
            });
          }
        })
        items.filter(function (el) {
          return el != null;
        });
        console.log(items, "items")
      }
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
</style>