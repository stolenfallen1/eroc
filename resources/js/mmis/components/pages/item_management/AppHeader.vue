<template>
  <v-card>
      <template>
        <v-tabs v-model="setting.tab" align-with-title>
          <v-tab class="pa-1 ma-2" @click="$emit('change', itemgroup.id)" v-for="(itemgroup,i) in item_groups" :key="i">
            {{itemgroup.name}}
          </v-tab>
        </v-tabs>
      </template>
  </v-card>
</template>
  <script>
import ItemsHelper from "@mmis/mixins/ItemsHelper.vue";
import { mapGetters } from "vuex";
export default {
  mixins: [ItemsHelper],
  props: {
    setting: {
      type: Object,
      default: () => {},
    },
  },
  data() {
    return {};
  },
  computed:{
    ...mapGetters(["item_groups"]),
  }
};
</script>