<template>
  <v-navigation-drawer v-model="drawer" permanent app>
    <div class="logo">MMIS</div>
    <v-list>
      <v-list-group
        v-for="(menu, index) in menus"
        :key="index"
        :value="true"
        dense
      >
        <v-icon slot="prependIcon" small color="primary">{{menu.icon}}</v-icon>
        <template v-slot:activator>
          <v-list-item-title>{{ menu.name }}</v-list-item-title>
        </template>
        <v-list-item
          v-for="(child, i) in menu.children"
          :key="i"
          class="pl-7"
          dense
          link
        >
          <v-list-item-icon>
            <v-icon v-text="child.icon" color="primary" small></v-icon>
          </v-list-item-icon>
          <v-list-item-title v-text="child.name"></v-list-item-title>
        </v-list-item>
      </v-list-group>
    </v-list>
  </v-navigation-drawer>
</template>
<script>
import MenuItems from "../../includes/MenuItems";
export default {
  props: {
    drawer: {
      type: Boolean,
      default() {
        return true;
      },
    },
  },
  data() {
    return {
      menus: MenuItems,
    };
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