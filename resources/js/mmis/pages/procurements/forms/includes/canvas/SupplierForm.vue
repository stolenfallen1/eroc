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
      <v-card-text>
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
              v-model="payload.supplier_id"
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
                  :value="_dateFormat()"
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
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="12" md="6">
            <p class="pa-0 ma-0">Price</p>
            <v-text-field
              v-model="payload.price"
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
              label="File input"
              outlined
              dense
            ></v-file-input>
          </v-col>
          
        </v-row>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
<script>
export default {
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    selected_item: {
      type: Object,
      default: () => {},
    },
  },
  data() {
    return {
      payload: {},
      requested_date:false
    };
  },
  methods: {
    close() {
      this.$emit("close");
    },
  },
};
</script>