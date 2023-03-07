<template>
  <v-dialog
    v-model="show"
    hide-overlay
    width="1050"
    transition="dialog-bottom-transition"
    scrollable
    persistent
  >
    <v-card v-if="show">
      <v-toolbar flat dark color="primary">
        <v-toolbar-title>Canvas</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn :disabled="show_supplier_form" icon dark @click="close()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-card-text>
        <v-row no-gutters>
          <v-col cols="12" sm="12" md="6">
            <v-row no-gutters>
              <v-col cols="12" sm="12">
                <p class="pa-0 ma-0">Purchase request number</p>
                <v-text-field
                  placeholder="Prefix"
                  :value="`${payload.pr_Document_Prefix}-${payload.pr_Document_Number}-${payload.pr_Document_Suffix}`"
                  solo
                  dense
                  hide-details="auto"
                  class="mb-2 mr-1"
                  readonly
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="12">
                <p class="pa-0 ma-0">Date requested</p>
                <v-text-field
                  placeholder="Prefix"
                  :value="_dateFormat(payload.requested_date)"
                  solo
                  dense
                  hide-details="auto"
                  class="mb-2 mr-1"
                  readonly
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="12">
                <p class="pa-0 ma-0">Date canvas</p>
                <v-text-field
                  placeholder="Prefix"
                  :value="_dateFormat()"
                  solo
                  dense
                  hide-details="auto"
                  class="mb-2 mr-1"
                  readonly
                ></v-text-field>
              </v-col>
            </v-row>
          </v-col>
          <v-col cols="12" sm="12" md="6">
            <v-row no-gutters>
              <v-col cols="12" sm="12">
                <p class="pa-0 ma-0">Requested By</p>
                <v-text-field
                  placeholder="Prefix"
                  :value="payload.user.name"
                  solo
                  dense
                  hide-details="auto"
                  class="mb-2 mr-1"
                  readonly
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="12">
                <p class="pa-0 ma-0">Department</p>
                <v-text-field
                  placeholder="Prefix"
                  :value="payload.warehouse.warehouse_description"
                  solo
                  dense
                  hide-details="auto"
                  class="mb-2 mr-1"
                  readonly
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="12">
                <p class="pa-0 ma-0">Canvas By</p>
                <v-text-field
                  placeholder="Prefix"
                  :value="$store.getters.user.name"
                  solo
                  dense
                  hide-details="auto"
                  class="mb-2 mr-1"
                  readonly
                ></v-text-field>
              </v-col>
            </v-row>
          </v-col>
          <v-col cols="12" sm="12">
            <p class="pa-0 ma-0">Item name</p>
            <v-text-field
              placeholder="Prefix"
              :value="selected_item.item_master.item_name"
              solo
              dense
              hide-details="auto"
              class="mb-2 mr-1"
              readonly
            ></v-text-field>
          </v-col>
        </v-row>
        <SupplierTable
          :canvases="canvases"
          @setIsRecommended="recommendConfirmation"
          @delete="removeConfirmation"
        />
        <div class="d-flex flex-row-reverse">
          <v-btn color="primary" @click="$emit('close')">close</v-btn>
          <v-btn v-if="$store.getters.user.role.name=='purchaser'" @click="show_supplier_form = true" color="success" class="mr-2"
            >Add supplier</v-btn
          >
        </div>
      </v-card-text>
    </v-card>
    <SupplierForm
      :data="payload"
      :selected_item="selected_item"
      :show="show_supplier_form"
      @close="closeForm"
    />
    <Confirmation
      @cancel="cancelConfirmation"
      @confirm="confirmAction"
      :show="isconfirmation"
    />
    <SnackBar
      class="class-snackbar"
      @close="isnotification = false"
      :data="notification"
      :show="isnotification"
    />
  </v-dialog>
</template>
<script>
import {
  apiGetAllCanvas,
  apiUpdateIsRecommended,
  apiRemoveCanvas,
} from "@mmis/api/procurements.api.js";
import SupplierTable from "./SupplierTable.vue";
import SupplierForm from "./SupplierForm.vue";
export default {
  components: {
    SupplierTable,
    SupplierForm,
  },
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    selected_item: {
      type: Object,
      default: () => {},
    },
    payload: {
      type: Object,
      default: () => {},
    },
  },
  data() {
    return {
      isfetching: false,
      show_supplier_form: false,
      canvases: [],
      selected_canvas: {},
      isconfirmation: false,
      isdelete: false,
      isnotification: false,
      notification: {
        messages: [],
      },
    };
  },
  methods: {
    cancelConfirmation() {
      this.selected_canvas = {};
      this.isdelete = false;
      this.isconfirmation = false;
      this.fetchAllCanvas();
    },
    confirmAction(code) {
      if (this.$store.getters.user.passcode != code || code == null) {
        this.showNotification("Incorrect passcode", "error")
        this.isnotification = true;
        return;
      }
      this.isconfirmation = false;
      if (this.isdelete) return this.removeCanvas();
      this.setIsRecommended();
    },
    showNotification(message, color){
      this.notification.messages = [];
      this.notification.messages.push(message);
      this.notification.color = color;
    },
    removeConfirmation(canvas) {
      Object.assign(this.selected_canvas, canvas);
      this.isdelete = true;
      this.isconfirmation = true;
    },
    recommendConfirmation(canvas) {
      Object.assign(this.selected_canvas, canvas);
      this.isconfirmation = true;
    },
    async removeCanvas() {
      let res = await apiRemoveCanvas(this.selected_canvas.id);
      if (res.status == 200) {
        this.showNotification("Canvas successfully removed", "success")
        this.isnotification = true;
        this.selected_canvas = {};
        this.isdelete = false;
        this.fetchAllCanvas();
      }
    },
    async setIsRecommended() {
      let res = await apiUpdateIsRecommended(this.selected_canvas.id, {
        is_recommended: this.selected_canvas.isRecommended,
        details_id: this.selected_canvas.pr_request_details_id,
      });
      if (res.status == 200) {
        this.showNotification("Canvas successfully set as recommended", "success")
        this.isnotification = true;
        this.selected_canvas = {};
        this.isdelete = false;
        this.fetchAllCanvas();
      }
    },
    async fetchAllCanvas() {
      let params = "details_id=" + this.selected_item.id;
      let res = await apiGetAllCanvas(params);
      if (res.status == 200) {
        this.canvases = res.data.data;
      }
      console.log(res, "status");
    },
    close() {
      this.$emit("close");
    },
    closeForm(val) {
      if (val) {
        this.fetchAllCanvas();
      }
      this.show_supplier_form = false;
    },
  },
  watch: {
    show: {
      handler(val) {
        console.log(val, "peste");
        if (val) {
          this.fetchAllCanvas();
        }
      },
      immediate: true,
      deep: true,
    },
  },
};
</script>
<style lang="scss" scoped>
.class-snackbar {
  position: absolute;
}
</style>