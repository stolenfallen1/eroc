<template>
  <v-row>
    <v-col cols="12" xs="12" md="6" xl="4">
      <p class="pa-0 ma-0">Inv. Group</p>
      <v-autocomplete
        v-model="payload.category"
        solo
        :items="categories"
        item-text="name"
        item-value="id"
        @change="fetchSubcategory"
        dense
        hide-details="auto"
        class="mb-2"
        attach
      ></v-autocomplete>
      <p class="pa-0 ma-0">Category</p>
      <v-autocomplete
        v-model="payload.sub_category"
        solo
        :items="sub_categories"
        item-text="name"
        item-value="id"
        @change="fetchClassifications"
        dense
        hide-details="auto"
        class="mb-2"
        attach
      ></v-autocomplete>
      <p class="pa-0 ma-0">Classification</p>
      <v-autocomplete
        v-model="payload.classification"
        solo
        :items="classifications"
        item-text="name"
        item-value="id"
        dense
        hide-details="auto"
        class="mb-2"
        attach
      ></v-autocomplete>
      <p class="pa-0 ma-0">Justication</p>
      <v-textarea
        v-model="payload.justication"
        rows="4"
        dense
        solo
        hide-details="auto"
        class="mb-2"
      ></v-textarea>

      <p class="pa-0 ma-0">Priority</p>
      <v-autocomplete
        v-model="payload.priority"
        :items="priorities"
        item-text="name"
        item-value="id"
        dense
        solo
        hide-details="auto"
        class="mb-2"
        attach
      ></v-autocomplete>
    </v-col>

    <v-col cols="12" xs="12" md="6" xl="4">
      <p class="pa-0 ma-0">Requested By</p>
      <v-text-field
        v-model="payload.requested_by"
        readonly
        solo
        dense
        class="mb-2"
        hide-details="auto"
      ></v-text-field>

      <p class="pa-0 ma-0">Department</p>
      <v-text-field
        v-model="payload.department"
        readonly
        solo
        class="mb-2"
        hide-details="auto"
        dense
      ></v-text-field>
      <p class="pa-0 ma-0">Date requested</p>
      <v-text-field
        :value="_dateFormat(payload.requested_date)"
        clearable
        readonly
        solo
        class="mb-2"
        hide-details="auto"
        dense
      ></v-text-field>
      <p class="pa-0 ma-0">Date Required</p>
      <v-menu
        v-model="required_date"
        :close-on-content-click="false"
        max-width="290"
      >
        <template v-slot:activator="{ on, attrs }">
          <v-text-field
            v-model="payload.required_date"
            clearable
            readonly
            v-bind="attrs"
            v-on="on"
            @click:clear="payload.required_date = null"
            solo
            class="mb-2"
            hide-details="auto"
            dense
          ></v-text-field>
        </template>
        <v-date-picker
          v-model="payload.required_date"
          @change="required_date = false"
        ></v-date-picker>
      </v-menu>
      <p class="pa-0 ma-0">Attachment</p>
      <v-file-input
        v-model="payload.attachments"
        show-size
        counter
        solo
        dense
        multiple
        hide-details="auto"
      ></v-file-input>
        <!-- @change="convertAttachment" -->
      <!--  -->
      <div class="d-flex flex-row-reverse">
        <v-btn
          :disabled="!payload.sub_category || !payload.category || !payload.department"
          class="mt-2"
          color="primary"
          @click="$emit('select')"
          >Select item</v-btn
        >
      </div>
    </v-col>
  </v-row>
</template>
<script>
import {
  apiGetAllSubCategories,
  apiGetAllClassifications,
} from "@global/api/categories";
import { mapGetters } from "vuex";
export default {
  props: {
    payload: {
      type: Object,
      default: () => {},
    },
  },
  data() {
    return {
      required_date: false,
      sub_categories: [],
      classifications: [],
      attachments:[],
      priorities:[]
    };
  },
  methods: {
    async fetchSubcategory(val) {
      let params = `category_id=${val}`;
      let res = await apiGetAllSubCategories(params);
      this.sub_categories = res.data.subcategories;
    },
    async fetchClassifications(val) {
      let params = `sub_category_id=${val}`;
      let res = await apiGetAllClassifications(params);
      this.classifications = res.data.classifications;
    },
    async convertAttachment(){
      console.log(this.attachments)
    }
  },
  computed: {
    ...mapGetters(["categories"]),
  },
  mounted(){
  }
};
</script>