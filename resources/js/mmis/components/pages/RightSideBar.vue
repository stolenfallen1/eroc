<template>
  <div>
    <v-navigation-drawer width="220" v-model="isdrawer" absolute right>
      <v-list nav>
        <v-list-group :value="true">
          <v-icon class="list-icon" slot="prependIcon" small color="white">mdi-contain</v-icon>
          <template v-slot:activator>
            <v-list-item-title>Sub-Components</v-list-item-title>
          </template>
          <v-list nav v-if="right_items.length > 0">
            <v-list-item
              v-for="(child, i) in right_items"
              @click="selectedRoute(child.route)"
              :key="i"
              class="ml-4"
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
        </v-list-group>
        <v-list-group :value="false">
          <v-icon class="list-icon" slot="prependIcon" small color="white">mdi-cursor-default-outline</v-icon>
          <template v-slot:activator>
            <v-list-item-title>Actions</v-list-item-title>
          </template>
          <side-actions @add="$emit('add')">
            <template v-slot:side_filter>
              <slot name="side_filter" />
            </template>
          </side-actions>
        </v-list-group>
      </v-list>
      <v-divider></v-divider>
    </v-navigation-drawer>
  </div>
</template>
<script>
import SideActions from "@global/components/SideActions.vue"
import { mapGetters } from "vuex";
export default {
  components:{
    SideActions
  },
  props: {},
  data() {
    return {
      isdrawer: true,
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