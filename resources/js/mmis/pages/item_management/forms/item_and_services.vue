<template>
  <v-dialog
    v-model="show"
    hide-overlay
    max-width="1020px"
    scrollable
    persistent
  >
    <v-card tile v-if="show">
      <v-toolbar flat dark color="primary">
        <v-toolbar-title>Manage Items & Services Details..</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn :disabled="show_item_form" icon dark @click="close()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-divider></v-divider>
      <v-card-text>
          <fields
            :payload="payload"
            @select="show_item_form = true"
            :isedit="isedit"
            :isapprove="isapprove"
          />
      </v-card-text>
      <v-divider></v-divider>
        <v-card-actions>
            <v-btn class="mr-2" color="error" @click="close()">Cancel</v-btn>
            <v-spacer></v-spacer>
            <v-btn v-if="isapprove" @click="$emit('submit')" color="primary"
              >Submit</v-btn
            >
            <v-btn v-else @click="$emit('submit')" color="primary"
              >Submit</v-btn
            >
        </v-card-actions>
      </v-card>
    </v-card>
  </v-dialog>
</template>
  <script>
import Fields from "./includes/item_and_services/fields.vue";
import { mapGetters } from "vuex";
export default {
  components: {
    Fields,
  },
  props: {
    show: {
      type: Boolean,
      default: () => false,
    },
    isedit: {
      type: Boolean,
      default: () => false,
    },
    isapprove: {
      type: Boolean,
      default: () => false,
    },
    payload: {
      type: Object,
      default: () => {},
    },
  },
  data() {
    return {
      show_item_form: false,
      has_items: false,
      isfetching: false,
    };
  },
  methods: {
    close() {
      this.$emit("close");
    },
    removeItem(index) {
      this.payload.items.splice(index, 1);
    },
   
  },
  computed: {
  },
  watch: {
    show: {
      handler(val) {
        if (val && (this.isedit)) {
          console.log(this.payload.items, "hshshsh");
        }
      },
    },
  },
};
</script>