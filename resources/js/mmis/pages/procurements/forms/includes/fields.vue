<template>
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
        <!-- :disabled="!payload.item_group || !payload.category" -->
      <v-btn
        class="mt-4"
        large
        color="primary"
        @click="$emit('select')"
        >Select item</v-btn
      >
    </v-col>
  </v-row>
</template>
<script>
export default {
  props: {
    payload:{
      type: Object,
      default:() => {}
    }
  },
  data(){
    return{
      required_date:false
    }
  }
};
</script>