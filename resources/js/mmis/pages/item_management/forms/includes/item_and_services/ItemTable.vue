<template>
  <v-card-text>
    <v-simple-table fixed-header  height="100vh">
      <template v-slot:default>
        <thead>
          <tr>
            <th class="text-left">Item Code</th>
            <th class="text-left">Item Name / Description</th>
            <th class="text-left">Attachment</th>
            <th
              class="text-left"
              v-if="
                $store.getters.user.role.name != 'administrator' &&
                $store.getters.user.role.name != 'consultant'
              "
            >
              Qty
            </th>
            <th
              class="text-left"
              v-if="
                $store.getters.user.role.name != 'administrator' &&
                $store.getters.user.role.name != 'consultant'
              "
            >
              UOM
            </th>
            <th class="text-left" v-if="isapprove">APPD Qty</th>
            <th class="text-left" v-if="isapprove">APPD UOM</th>
            <th class="text-left">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in items" :key="index">
            <td>{{ getId(item) }}</td>
            <td>{{ getName(item) }}</td>
            <td>
              <v-text-field
                v-if="!isup"
                v-model="item.filename"
                readonly
                dense
                solo
                hide-details="auto"
                :append-outer-icon="
                  item.attachment || item.filepath ? 'mdi-eye' : ''
                "
                @click:append-outer="showAttachment(item)"
                @click="triggerUpload(index)"
              >
              </v-text-field>
              <input
                ref="file_input"
                type="file"
                class="hidden"
                accept=".pdf,.jpg,.png,.jpeg"
                @change="onFileChange($event.target.files, item)"
              />
            </td>
            <td
              v-if="
                $store.getters.user.role.name != 'administrator' &&
                $store.getters.user.role.name != 'consultant'
              "
            >
              <v-text-field
                v-model="item.item_Request_Qty"
                style="max-width: 100px"
                solo
                dense
                hide-details="auto"
                type="number"
                :readonly="isapprove"
              ></v-text-field>
            </td>
            <td
              v-if="
                $store.getters.user.role.name != 'administrator' &&
                $store.getters.user.role.name != 'consultant'
              "
            >
              <v-autocomplete
                v-model="item.item_Request_UnitofMeasurement_Id"
                solo
                :items="units"
                item-text="name"
                item-value="id"
                dense
                hide-details="auto"
                attach
                :readonly="isapprove"
              >
              </v-autocomplete>
            </td>
            <td v-if="isapprove">
              <v-text-field
                v-model="item.item_Request_Department_Approved_Qty"
                style="max-width: 100px"
                solo
                dense
                :readonly="
                  $store.getters.user.role.name == 'administrator' ||
                  $store.getters.user.role.name == 'consultant'
                "
                hide-details="auto"
                type="number"
              ></v-text-field>
            </td>
            <td v-if="isapprove">
              <v-autocomplete
                v-model="
                  item.item_Request_Department_Approved_UnitofMeasurement_Id
                "
                solo
                :items="units"
                item-text="name"
                item-value="id"
                dense
                hide-details="auto"
                attach
                :readonly="
                  $store.getters.user.role.name == 'administrator' ||
                  $store.getters.user.role.name == 'consultant'
                "
              >
              </v-autocomplete>
            </td>
            <td v-if="!isapprove">
              <v-btn
                @click="removeItem(index)"
                text
                small
                fab
                icon
                color="primary"
              >
                <v-icon> mdi-close </v-icon>
              </v-btn>
            </td>
            <td v-else>
              <v-tooltip top>
                <template v-slot:activator="{ on, attrs }">
                  <v-btn
                    @click="approveItem(item)"
                    text
                    small
                    fab
                    icon
                    v-bind="attrs"
                    v-on="on"
                  >
                    <v-icon v-if="item.isapproved" color="success">
                      mdi-thumb-up-outline
                    </v-icon>
                    <v-icon v-else color="error">
                      mdi-thumb-down-outline
                    </v-icon>
                  </v-btn>
                </template>
                <span>{{
                  !item.isapproved == true ? "Decline" : "Approve"
                }}</span>
              </v-tooltip>
            </td>
          </tr>
        </tbody>
      </template>
    </v-simple-table>
    <AttachmentViewer
      @close="showviewfile = false"
      :show="showviewfile"
      :files="files"
    />
  </v-card-text>
</template>
  <script>
import { mapGetters } from "vuex";
import {
  apiRemovePurchaseRequestItem,
  apiUpdatePurchaseRequestItemAttachment,
} from "@mmis/api/procurements.api";
import AttachmentViewer from "@global/components/AttachmentViewer.vue";
export default {
  components: {
    AttachmentViewer,
  },
  props: {
    items: {
      type: Array,
      default: () => [],
    },
    isedit: {
      type: Boolean,
      default: () => false,
    },
    isapprove: {
      type: Boolean,
      default: () => false,
    },
  },
  data() {
    return {
      isup: false,
      files: [],
      showviewfile: false,
    };
  },

  methods: {
    approveItem(item) {
      this.isup = true;
      item.isapproved = !item.isapproved;
      console.log(item, "approve");
      setTimeout(() => {
        this.isup = false;
      }, 20);
    },
    showAttachment(file) {
      this.files = [];
      this.files.push(file);
      this.showviewfile = true;
    },
    async removeItem(index) {
      if (this.isedit) {
        console.log(this.items);
        if (this.items[index].item_Id) {
          let res = await apiRemovePurchaseRequestItem(this.items[index].id);
          if (res.status == 200) {
            this.items.splice(index, 1);
          }
        } else {
          this.items.splice(index, 1);
        }
      }
      this.$emit("remove", index);
    },
    getName(item) {
      if (this.isedit || this.isapprove)
        if (item.item_master) return item.item_master.item_name;
      return item.item_name;
    },
    getId(item) {
      if (this.isedit || this.isapprove)
        if (item.item_master) return item.item_Id;
      return item.id;
    },
    async onFileChange(file, item) {
      this.isup = true;
      item.filename = file[0].name;
      item.attachment = file[0];
      if (item.item_Id && this.isedit) {
        let fd = new FormData();
        fd.append("attachment", item.attachment);
        let res = await apiUpdatePurchaseRequestItemAttachment(item.id, fd);
      }
      setTimeout(() => {
        this.isup = false;
      }, 20);
    },
    triggerUpload(index) {
      if (this.isapprove) return;
      console.log(this.$refs);
      this.$refs.file_input[index].click();
    },
  },
  computed: {
    ...mapGetters(["units"]),
  },
};
</script>