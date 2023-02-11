<template>
  <v-dialog
    v-model="show"
    hide-overlay
    width="950"
    transition="dialog-bottom-transition"
    scrollable
    persistent
  >
    <v-card tile>
      <v-toolbar flat dark color="primary">
        <v-toolbar-title>Purchase request</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn icon dark @click="close()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-card-text>
        <v-container fluid>
          <v-row>
            <v-col cols="12" xs="12" md="6" xl="4">
              <v-autocomplete
                label="Inv. Group"
                v-model="payload.item_group"
                filled
                dense
              ></v-autocomplete>
              <v-autocomplete
                label="Category"
                v-model="payload.category"
                filled
                dense
              ></v-autocomplete>
              <v-textarea
                v-model="payload.remarks"
                label="Remarks"
                rows="4"
                dense
                filled
              ></v-textarea>
              <v-file-input
                show-size
                counter
                filled
                multiple
                label="File input"
              ></v-file-input>
            </v-col>

            <v-col cols="12" xs="12" md="6" xl="4">
              <v-text-field
                v-model="payload.requested_by"
                label="Requested By"
                readonly
                filled
                dense
              ></v-text-field>
              <v-text-field
                v-model="payload.department"
                label="Department"
                readonly
                filled
                dense
              ></v-text-field>
              <v-text-field
                :value="_dateFormat(payload.requested_date)"
                clearable
                label="Date requested"
                readonly
                filled
                dense
              ></v-text-field>
              <v-menu
                v-model="required_date"
                :close-on-content-click="false"
                max-width="290"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="payload.required_date"
                    clearable
                    label="Date Required"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                    @click:clear="payload.required_date = null"
                    filled
                    dense
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="payload.required_date"
                  @change="required_date = false"
                ></v-date-picker>
              </v-menu>
              <v-btn
                :disabled="!payload.item_group || !payload.category"
                class="mt-4"
                large
                color="primary"
                >Select item</v-btn
              >
            </v-col>
          </v-row>
          <v-simple-table fixed-header dense height="300px">
            <template v-slot:default>
              <thead>
                <tr>
                  <th class="text-left">Item Code</th>
                  <th class="text-left">Item Name / Description</th>
                  <th class="text-left">Attachment</th>
                  <th class="text-left">Qty</th>
                  <th class="text-left">UOM</th>
                  <th class="text-left">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in payload.items" :key="item.name">
                  <td>{{ item.code }}</td>
                  <td>{{ item.name }}</td>
                  <td style="width: 90px !important">
                    <input
                      type="file"
                      class="fields fields12163"
                      style="width: 94px !important"
                      accept="image/png"
                      name="attachment[]"
                    />
                  </td>
                  <td>
                    <v-text-field solo dense hide-details="auto" type="number"></v-text-field>
                  </td>
                  <td>
                    <v-autocomplete solo dense hide-details="auto">
                    </v-autocomplete>
                  </td>
                  <td>
                    <v-btn small color="primary">
                      <v-icon> mdi-close </v-icon>
                    </v-btn>
                  </td>
                </tr>
              </tbody>
            </template>
          </v-simple-table>
        </v-container>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
<script>
export default {
  props: {
    show: {
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
      requested_date: false,
      required_date: false,
    };
  },
  methods: {
    close() {
      this.$emit("close");
    },
  },
  watch: {
    show: {
      handler(val) {
        if (!val) {
          console.log("close");
        }
      },
    },
  },
};
</script>