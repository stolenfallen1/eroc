<template>
  <div>
    <v-navigation-drawer width="220" v-model="drawer" absolute right>
      <v-list nav v-if="right_items.length > 0">
        <v-list-item
          v-for="(child, i) in right_items"
          @click="selectedRoute(child.route)"
          :key="i"
          class="pl-7"
          dense
          link
          :class="{ 'active-route': active_route == child.route }"
        >
          <v-list-item-icon>
            <v-icon v-text="child.icon" color="primary" small></v-icon>
          </v-list-item-icon>
          <v-list-item-title v-text="child.name"></v-list-item-title>
        </v-list-item>
      </v-list>
      <v-divider></v-divider>
    </v-navigation-drawer>
  </div>
</template>
<script>
import { mapGetters } from "vuex";
export default {
  props: {},
  data() {
    return {
      menus: MenuItems,
      drawer: true,
    };
  },
  methods: {
    selectedRoute(child, parent) {
      console.log(child, "child");
      console.log(this.$route.name, "this.$route.name");
      if (this.$route.name != child.route) this._push(child);
    },
  },
  computed: {
    ...mapGetters(["right_items", "active_route", "drawer"]),
  },
  watch: {
    
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