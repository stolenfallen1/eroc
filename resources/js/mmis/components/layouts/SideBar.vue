<template>
  <div>
    <v-navigation-drawer  v-model="isdrawer" app>
      <v-list nav >
        <v-subheader>Main Explorer</v-subheader>
        <v-list-group
          v-for="(menu, index) in menus"
          :key="index"
          :value="true"
          dense
        >
          <v-icon class="list-icon" slot="prependIcon" small color="white">{{
            menu.icon
          }}</v-icon>
          <template v-slot:activator>
            <v-list-item-title>{{ menu.name }}</v-list-item-title>
          </template>
          
          <v-list-item
            v-for="(child, i) in menu.children"
            @click="selectedRoute(child)"
            :key="i"
            dense
            link
            :class="{ 'active-route': main_active_route == child.route }"
          >
            <v-list-item-icon>
              <v-icon v-text="child.icon" :color="main_active_route == child.route?'white':'primary'" small></v-icon>
            </v-list-item-icon>
            <v-list-item-title v-text="child.name"></v-list-item-title>
          </v-list-item>
        </v-list-group>
      </v-list>
    </v-navigation-drawer>
    <app-bar @toggle="toggleSide" />
  </div>
</template>
<script>
import MenuItems from "../../includes/MenuItems";
import AppBar from "./AppBar.vue";
import { mapMutations, mapGetters } from "vuex";
export default {
  components: {
    AppBar,
  },
  props: {},
  data() {
    return {
      menus: MenuItems,
      isdrawer: true,
    };
  },
  methods: {
    ...mapMutations(["setRightItems", "setDrawer"]),
    toggleSide() {
      this.setDrawer()
      this.$emit("drawer");
    },
    selectedRoute(child, parent) {
      this.active_route = child.route;
      if (child.sub_childrens && child.sub_childrens.length > 0)
        this.setRightItems(child.sub_childrens);

      if (this.$route.name != child.route) this._push(child.route);
    },
  },
  computed: {
    ...mapGetters(["main_active_route", "drawer"]),
    // activeRoute() {
    //   return this._getters("main_active_route");
    // },
  },
  watch: {
    $route(to, from) {
      let path = to.path.split("/");
      if (from.path == "/") {
        this.menus.map((menu) => {
          if (menu.children) {
            return menu.children.map((child) => {
              if (path[2] == child.route) {
                this.$store.commit("setRightItems", child.sub_childrens);
              }
            });
          }
        });
      }
    },
    drawer:{
      handler(val){
        this.isdrawer = val
      }

    }
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