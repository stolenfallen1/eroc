<template>
  <div>
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
                :append-outer-icon="item.attachment?'mdi-eye':''"
                @click:append-outer="showAttachment(item.attachment)"
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
              <!-- <v-file-input
                ref="file_input"
                style="min-width: 200px"
                solo
                dense
                @change="isedit?updateAttachment(item):''"
                prepend-icon=""
                hide-details="auto"
              ></v-file-input> -->
            </td>
            <td>
              <v-text-field
                v-model="item.item_Request_Qty"
                style="max-width: 100px"
                solo
                dense
                hide-details="auto"
                type="number"
              ></v-text-field>
            </td>
            <td>
              <v-autocomplete
                v-model="item.item_Request_UnitofMeasurement_Id"
                solo
                :items="units"
                item-text="name"
                item-value="id"
                dense
                hide-details="auto"
                attach
              >
              </v-autocomplete>
            </td>
            <td>
              <v-btn @click="removeItem(index)" text small color="primary">
                <v-icon> mdi-close </v-icon>
              </v-btn>
            </td>
          </tr>
        </tbody>
      </template>
    </v-simple-table>
    <AttachmentViewer @close="showviewfile=false" :show="showviewfile" :files="files" />
  </div>
</template>
<script>
import { mapGetters } from "vuex";
import {
  apiRemovePurchaseRequestItem,
  apiUpdatePurchaseRequestItemAttachment,
} from "@mmis/api/procurements.api";
import AttachmentViewer from "@global/components/AttachmentViewer.vue"
export default {
  components:{
    AttachmentViewer
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
  },
  data() {
    return {
      isup:false,
      files:[],
      showviewfile:false
    };
  },
  methods: {
    showAttachment(file){
      this.files.push(file)
      this.showviewfile = true
    },
    async removeItem(index) {
      if (this.isedit) {
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
      if (this.isedit) if (item.item_master) return item.item_master.item_Name;
      return item.item_Name;
    },
    getId(item) {
      if (this.isedit) if (item.item_master) return item.item_Id;
      return item.id;
    },
    async onFileChange(file, item) {
      this.isup = true
      item.filename = file[0].name
      item.attachment = file[0]
      if (item.item_Id && this.isedit) {
        let fd = new FormData();
        fd.append("attachment", item.attachment);
        let res = await apiUpdatePurchaseRequestItemAttachment(item.id, fd);
      }
      setTimeout(() => {
        this.isup = false
      }, 20);
    },
    triggerUpload(index) {
      console.log(this.$refs);
      this.$refs.file_input[index].click();
    },
  },
  computed: {
    ...mapGetters(["units"]),
  },
};
</script>