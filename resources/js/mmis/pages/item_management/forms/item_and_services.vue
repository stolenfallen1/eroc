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
      <!-- <iframe :src="payload.purchase_request_attachments[0].filepath"
        /> -->
    </v-card>
    <items-form
      v-if="show"
      :isedit="isedit"
      :payload="payload"
      :show="show_item_form"
      @cancel="show_item_form = false"
      @selected="setPayloadItems"
    />
  </v-dialog>
</template>
  <script>
import Fields from "./includes/item_and_services/fields.vue";
import ItemTable from "./includes/item_and_services/ItemTable.vue";
import ItemsForm from "./includes/item_and_services/ItemsForm.vue";
import { mapGetters } from "vuex";
export default {
  components: {
    ItemTable,
    ItemsForm,
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
    setPayloadItems(val) {
      if (this.isedit) {
        val.map((val_item) => {
          let exist = false;
          this.payload.items.map((item) => {
            if (item.item_Id == val_item.id) {
              console.log(val_item.id, "test");
              exist = true;
            }
          });
          if (!exist) {
            this.payload.items.push(val_item);
          }
        });
        console.log(this.payload.items, "item after push");
        this.show_item_form = false;
        return;
      }
      this.payload.items = val;
      this.show_item_form = false;
    },
  },
  computed: {
    ...mapGetters(["prsn_settings"]),
  },
  watch: {
    show: {
      handler(val) {
        if (val && (this.isedit || this.isapprove)) {
          console.log(this.payload.items, "hshshsh");
        }
      },
    },
  },
};
</script>