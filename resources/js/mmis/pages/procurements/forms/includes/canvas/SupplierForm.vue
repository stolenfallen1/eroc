<template>
  <v-dialog
    v-model="show"
    hide-overlay
    width="700"
    transition="dialog-bottom-transition"
    scrollable
    persistent
  >
    <v-card>
      <v-toolbar flat dark color="primary">
        <v-toolbar-title>Add Supplier</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn icon dark @click="close()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-card-text v-if="!isfetching">
        <v-row no-gutters>
          <v-col cols="12" sm="12">
            <p class="pa-0 ma-0">Item Name</p>
            <v-text-field
              :value="selected_item.item_master.item_name"
              solo
              dense
              hide-details="auto"
              class="mb-2 mr-1"
              readonly
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="12">
            <p class="pa-0 ma-0">Supplier</p>
            <v-autocomplete
              v-model="payload.vendor_id"
              :items="suppliers"
              item-text="vendor_Name"
              item-value="id"
              solo
              dense
              hide-details="auto"
              class="mb-2 mr-1"
            ></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="12" md="6">
            <p class="pa-0 ma-0">UOM</p>
            <v-autocomplete
              v-model="payload.oum"
              :items="$store.getters.units"
              item-text="name"
              item-value="id"
              solo
              dense
              hide-details="auto"
              class="mb-2 mr-1"
            ></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="12" md="6">
            <p class="pa-0 ma-0">Date Requested</p>
            <v-menu
              v-model="requested_date"
              :close-on-content-click="false"
              max-width="290"
            >
              <template v-slot:activator="{ on, attrs }">
                <v-text-field
                  v-model="payload.requested_date"
                  readonly
                  v-bind="attrs"
                  v-on="on"
                  @click:clear="payload.requested_date = null"
                  solo
                  class="mb-2"
                  hide-details="auto"
                  dense
                ></v-text-field>
              </template>
              <v-date-picker
                v-model="payload.requested_date"
                @change="requested_date = false"
                no-title
              ></v-date-picker>
            </v-menu>
          </v-col>
          <v-col cols="12" sm="12" md="6">
            <p class="pa-0 ma-0">Qty</p>
            <v-text-field
              v-model="selected_item.item_Branch_Level1_Approved_Qty"
              solo
              dense
              hide-details="auto"
              class="mb-2 mr-1"
              readonly
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="12" md="6">
            <p class="pa-0 ma-0">Price</p>
            <v-text-field
              v-model="payload.canvas_item_amount"
              solo
              type="number"
              dense
              hide-details="auto"
              class="mb-2 mr-1"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="12" md="6">
            <p class="pa-0 ma-0">Discount</p>
            <v-text-field
              v-model="payload.discount"
              type="number"
              solo
              dense
              hide-details="auto"
              class="mb-2 mr-1"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="12" md="6">
            <p class="pa-0 ma-0">Lead Time</p>
            <v-text-field
              v-model="payload.lead_time"
              type="number"
              solo
              dense
              hide-details="auto"
              class="mb-2 mr-1"
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="12">
            <p class="pa-0 ma-0">Attachments</p>
            <v-file-input
              v-model="payload.attachments"
              outlined
              multiple
              dense
              prepend-icon=""
              prepend-inner-icon="mdi-paperclip"
              hide-details="auto"
            ></v-file-input>
          </v-col>
          <v-col cols="12" sm="12">
            <p class="pa-0 ma-0">Remarks</p>
            <v-textarea
              v-model="payload.canvas_remarks"
              outlined
              dense
            ></v-textarea>
          </v-col>
        </v-row>
        <div class="d-flex flex-row-reverse">
          <v-btn @click="forConfirmation" color="primary">Submit</v-btn>
        </div>
      </v-card-text>
      <SnackBar
        class="class-snackbar"
        @close="isnotification = false"
        :data="notification"
        :show="isnotification"
      />
      <Confirmation
        @cancel="cancelConfirmation"
        @confirm="submit"
        :show="isconfirmation"
      />
    </v-card>
  </v-dialog>
</template>
<script>
import { apiGetAllSuppliers } from "@global/api/suppliers.js";
import { apiAddCanvas } from "@mmis/api/procurements.api.js";
import SnackBar from "@global/components/SnackBar.vue";
import Confirmation from "@global/components/Confirmation.vue";
import CanvasHelper from "@mmis/mixins/CanvasHelper.vue";
export default {
  mixins: [CanvasHelper],
  components: {
    SnackBar,
    Confirmation,
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
    data:{
      type: Object,
      default: () => {},
    }
  },
  data() {
    return {
      payload: {},
      requested_date: false,
      isfetching: true,
      suppliers: [],
      notification: {
        messages: [],
      },
      isnotification: false,
      isconfirmation: false,
    };
  },
  methods: {
    forConfirmation() {
      this.notification.messages = [];
      let errors = this.checkCanvasPayload(this.payload);
      if (errors.length) {
        errors.forEach((error) => {
          this.notification.messages.push(error.message);
          this.notification.color = "error";
          this.isnotification = true;
        });
        return;
      }
      console.log();
      this.isconfirmation = true;
    },
    cancelConfirmation() {
      this.isconfirmation = false;
    },
    async submit(code) {
      if (this.$store.getters.user.passcode != code || code == null) {
        this.notification.messages = [];
        this.notification.messages.push("Incorrect passcode");
        this.notification.color = "error";
        this.isnotification = true;
        return;
      }
      let fd = new FormData();
      
      if (this.payload.attachments && this.payload.attachments.length) {
        this.payload.attachments.forEach((attachment) => {
          fd.append("attachments[]", attachment);
        });
      }
      fd.append("vendor_id", this.payload.vendor_id ?? "");
      fd.append("pr_request_id", this.data.id ?? "");
      fd.append("pr_request_details_id", this.selected_item.id ?? "");
      fd.append("canvas_Item_Id", this.selected_item.item_Id ?? "");
      fd.append("canvas_Item_Qty", this.selected_item.item_Branch_Level1_Approved_Qty ?? "");
      fd.append("canvas_Item_UnitofMeasurement_Id", this.payload.oum ?? "");
      fd.append("canvas_item_amount", this.payload.canvas_item_amount ?? "");
      fd.append("canvas_discount_percent", this.payload.discount ?? null);
      fd.append("canvas_lead_time", this.payload.lead_time ?? "");
      fd.append("canvas_remarks", this.payload.canvas_remarks ?? null);
      console.log(this.payload, "submit");

      let res = await apiAddCanvas(fd)
      if(res.status == 200){
        this.payload = {}
        this.isconfirmation = false
        this.$emit('close', true)
      }
    },
    close() {
      this.$emit("close", false);
    },
    async fetchSuppliers() {
      this.isfetching = true;
      let res = await apiGetAllSuppliers();
      if (res.status == 200) {
        this.suppliers = res.data.suppliers;
        this.isfetching = false;
      }
    },
  },
  created() {
    this.fetchSuppliers();
  },
};
</script>
<style lang="scss" scoped>
.class-snackbar {
  position: absolute;
}
</style>