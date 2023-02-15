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
        <v-btn :disabled="show_item_form" icon dark @click="close()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-card-text>
        <v-container fluid>
          <fields :payload="payload" @select="show_item_form = true"/>
          <item-table :items="payload.items"/>
          <div class="pr-form-actions">
            <v-btn class="mr-2" color="error" @click="close()">Cancel</v-btn>
            <v-btn @click="$emit('submit')" color="primary">Submit</v-btn>
          </div>
        </v-container>
      </v-card-text>
    </v-card>
    <items-form :payload="payload" :show="show_item_form" @cancel="show_item_form = false" @selected="setPayloadItems" />
  </v-dialog>
</template>
<script>
import Fields from "./includes/fields.vue";
import ItemTable from "./includes/ItemTable.vue"
import ItemsForm from "./includes/ItemsForm.vue"
export default {
  components:{
    ItemTable,
    ItemsForm,
    Fields
  },
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
      show_item_form: false,
    };       
  },
  methods: {
    close() {
      this.$emit("close");
    },
    setPayloadItems(val){
      this.payload.items = val
      console.log(this.payload.items,"sjhdsjdh")
      this.show_item_form = false
    }
  },
  watch: {
    show: {
      handler(val) {
        if (!val) {
        }
      },
    },
  },
};
</script>