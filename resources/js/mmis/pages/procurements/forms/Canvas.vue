<template>
  <v-dialog
    v-model="show"
    hide-overlay
    width="1250"
    transition="dialog-bottom-transition"
    scrollable
    persistent
  >
    <v-card tile :loading="isfetching">
      <v-toolbar flat dark color="primary">
        <v-toolbar-title>Purchase request</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn :disabled="show_canvas_form" icon dark @click="close()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-card-text>
        <Field :payload="payload" />
        <ItemTable
          :items="payload.purchase_request_details"
          @addCanvas="showCanvasForm"
        />
      </v-card-text>
    </v-card>
    <CanvasForm
      :show="show_canvas_form"
      :selected_item="selected_item"
      :payload="payload"
      @close="closeForm"
    />
  </v-dialog>
</template>
<script>
import Field from "./includes/canvas/fields.vue";
import CanvasForm from "./includes/canvas/CanvasForm.vue";
import ItemTable from "./includes/canvas/ItemTable.vue";
import { apiGetPurchaseRequest } from "@mmis/api/procurements.api";
export default {
  components: {
    Field,
    ItemTable,
    CanvasForm,
  },
  props: {
    show: {
      type: Boolean,
      required: true,
    },
    pr_id: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      isfetching: true,
      show_canvas_form: false,
      selected_item: {},
      payload: {},
    };
  },
  methods: {
    closeForm() {
      this.show_canvas_form = false;
    },
    showCanvasForm(item) {
      Object.assign(this.selected_item, item);
      setTimeout(() => {
        this.show_canvas_form = true;
      }, 50);
    },
    close() {
      this.$emit("close");
    },
    async fetchPR() {
      this.isfetching = true;
      let res = await apiGetPurchaseRequest(this.pr_id);

      if (res.status == 200) {
        this.payload = res.data;
        this.isfetching = false;
      }
    },
  },
  watch: {
    pr_id: {
      handler(val) {
        if (val) {
          this.fetchPR();
        }
      },
      immediate: true,
    },
  },
};
</script>