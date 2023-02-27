<template>
  <v-dialog
    v-model="show"
    hide-overlay
    width="1050"
    transition="dialog-bottom-transition"
    scrollable
    persistent
  >
    <v-card tile v-if="show">
      <v-toolbar flat dark color="primary">
        <v-toolbar-title>Approve purchase request</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn :disabled="show_item_form" icon dark @click="close()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-toolbar>
      <v-card-text>
        <v-container fluid>
          <fields :payload="payload" @select="show_item_form = true" :isedit="isedit" />
          <item-table :items="payload.items" :isedit="isedit" @remove="removeItem" />
          <div class="pr-form-actions">
            <v-btn class="mr-2" color="error" @click="close()">Cancel</v-btn>
            <v-btn @click="$emit('submit')" color="primary">Submit</v-btn>
          </div>
        </v-container>
      </v-card-text>
    </v-card>
    <items-form
      v-if="show"
      :isedit="isedit"
      :payload="payload"
      :show="show_item_form"
      @cancel="show_item_form = false"
      @selected="setPayloadItems"
    />
  </v-dialog>
</template>
<script>
import Fields from "./includes/fields.vue";
import ItemTable from "./includes/ItemTable.vue";
import ItemsForm from "./includes/ItemsForm.vue";
import { mapGetters } from "vuex"
export default {
  props:{
    payload:{
      type: Object,
      default:()=>{}
    }
  },
  data(){
    return {

    }
  }
}
</script>