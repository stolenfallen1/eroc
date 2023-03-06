<template>
  <v-row>
    <v-col cols="12" xs="12" md="6">
      <p class="pa-0 ma-0">
        Purchase number
      </p>
      <v-row no-gutters>
        <v-col cols="12" xs="12" md="3">
          <v-text-field
            placeholder="Prefix"
            v-model="payload.pr_Document_Prefix"
            solo
            dense
            hide-details="auto"
            class="mb-2 mr-1"
            readonly
          ></v-text-field>
        </v-col>
        <v-col cols="12" xs="12" md="6">
          <v-text-field
            placeholder="Number"
            v-model="payload.pr_Document_Number"
            solo
            dense
            hide-details="auto"
            class="mb-2 mr-1"
            type="number"
            readonly
          ></v-text-field>
        </v-col>
        <v-col cols="12" xs="12" md="3">
          <v-text-field
            placeholder="Suffix"
            v-model="payload.pr_Document_Suffix"
            solo
            dense
            hide-details="auto"
            class="mb-2"
            readonly
          ></v-text-field>
        </v-col>
      </v-row>
      <v-row no-gutters>
        <v-col cols="12" xs="12" md="6">
          <p class="pa-0 ma-0">Item group</p>
          <v-text-field
            v-model="payload.item_group.name"
            solo
            dense
            hide-details="auto"
            class="mb-2 mr-1"
            readonly
          ></v-text-field>
        </v-col>
        <v-col cols="12" xs="12" md="6">
          <p class="pa-0 ma-0">Category</p>
          <v-text-field
            v-model="payload.category.name"
            solo
            dense
            hide-details="auto"
            class="mb-2"
            readonly
          ></v-text-field>
        </v-col>
      </v-row>
      <v-row no-gutters>
        <v-col cols="12" xs="12" md="6">
          <p class="pa-0 ma-0">Sub category</p>
          <v-text-field
            v-model="payload.subcategory.name"
            solo
            dense
            hide-details="auto"
            class="mb-2 mr-1"
            readonly
          ></v-text-field>
        </v-col>
        <v-col cols="12" xs="12" md="6">
          <p class="pa-0 ma-0">Priority</p>
          <v-text-field
            v-model="payload.priority.priority_description"
            dense
            solo
            hide-details="auto"
            class="mb-2"
            readonly
          ></v-text-field>
        </v-col>
      </v-row>
      <p class="pa-0 ma-0">Justication</p>
      <v-textarea
        v-model="payload.pr_Justication"
        rows="4"
        dense
        solo
        hide-details="auto"
        class="mb-2"
        readonly
      ></v-textarea>
    </v-col>
    <v-col cols="12" xs="12" md="6">
      <p class="pa-0 ma-0">Requested By</p>
      <v-text-field
        v-model="payload.user.name"
        readonly
        solo
        dense
        class="mb-2"
        hide-details="auto"
      ></v-text-field>

      <p class="pa-0 ma-0">Department</p>
      <v-text-field
        v-model="payload.warehouse.warehouse_description"
        readonly
        solo
        class="mb-2"
        hide-details="auto"
        dense
      ></v-text-field>
      <v-row no-gutters>
        <v-col cols="12" xs="12" md="6">
          <p class="pa-0 ma-0">Date requested</p>
          <v-text-field
            :value="_dateFormat(payload.requested_date)"
            readonly
            solo
            class="mb-2 mr-1"
            hide-details="auto"
            dense
          ></v-text-field>
        </v-col>
        <v-col cols="12" xs="12" md="6">
          <p class="pa-0 ma-0">Date Required</p>
          <v-text-field
            :value="_dateFormat(payload.pr_Transaction_Date_Required)"
            readonly
            solo
            class="mb-2"
            hide-details="auto"
            dense
          ></v-text-field>
        </v-col>
      </v-row>

      <p class="pa-0 ma-0">Attachment</p>
      <v-text-field
        :value="`${
          payload.purchase_request_attachments ? payload.purchase_request_attachments.length + ' files' : ''
        }`"
        readonly
        solo
        class="mb-2 mr-1"
        hide-details="auto"
        :append-outer-icon="
          payload.purchase_request_attachments
            ? payload.purchase_request_attachments.length
              ? 'mdi-eye'
              : ''
            : ''
        "
        @click:append-outer="showAttachment(payload.purchase_request_attachments)"
        dense
      ></v-text-field>
      <!-- <v-file-input
        ref="attachments"
        v-model="payload.attachments"
        show-size
        accept="image/*,.pdf"
        counter
        solo
        dense
        class="d-none"
        multiple
        hide-details="auto"
      ></v-file-input> -->
      <!-- <div class="d-flex flex-row-reverse">
        <v-btn
          v-if="!isapprove"
          :disabled="
            !payload.item_SubCategory_Id ||
            !payload.item_Category_Id ||
            !payload.department
          "
          class="mt-2"
          color="primary"
          @click="$emit('select')"
          >Select item</v-btn
        >
      </div> -->
    </v-col>
    <AttachmentViewer
      @close="showviewfile = false"
      :show="showviewfile"
      :files="files"
    />
  </v-row>
</template>
<script>
import {
  apiGetAllSubCategories,
  apiGetAllCategories,
} from "@global/api/categories";
import { mapGetters } from "vuex";
import AttachmentViewer from "@global/components/AttachmentViewer.vue";
export default {
  components: {
    AttachmentViewer,
  },
  props: {
    payload: {
      type: Object,
      default: () => {},
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
      required_date: false,
      sub_categories: [],
      categories: [],
      attachments: [],
      files: [],
      showviewfile: false,
    };
  },
  methods: {
    async fetchSubcategory(val) {
      let params = `category_id=${val}`;
      let res = await apiGetAllSubCategories(params);
      this.sub_categories = res.data.subcategories;
    },
    async fetchCategory(val) {
      let params = `invgroup_id=${val}`;
      let res = await apiGetAllCategories(params);
      this.categories = res.data.categories;
    },
    async convertAttachment() {
      console.log(this.attachments);
    },
    triggerUpload() {
      if (this.isapprove) return;
      this.$refs.attachments.$refs.input.click();
    },
    showAttachment(files) {
      this.files = files;
      this.showviewfile = true;
      console.log("test append");
    },
  },
  computed: {
    ...mapGetters(["item_groups", "priorities", "prsn_settings"]),
  },
  mounted() {
    if (this.isedit || this.isapprove) {
      this.payload.attachments = this.payload.purchase_request_attachments;
      this.payload.item_Category_Id = parseInt(this.payload.item_Category_Id);
      this.payload.item_SubCategory_Id = parseInt(
        this.payload.item_SubCategory_Id
      );
      this.payload.invgroup_id = parseInt(this.payload.invgroup_id);
      this.payload.pr_Priority_Id = parseInt(this.payload.pr_Priority_Id);
      this.payload.requested_by = this.payload.user.name;
      this.payload.department = this.payload.warehouse.warehouse_description;
      this.fetchSubcategory(this.payload.item_Category_Id);
      this.fetchCategory(this.payload.item_SubCategory_Id);
    }
  },
};
</script>