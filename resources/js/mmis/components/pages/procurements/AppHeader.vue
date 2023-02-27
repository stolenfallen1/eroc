<template>
  <v-card elevation="2">
    <v-app-bar dense>
      <template>
        <v-tabs v-model="setting.tab" align-with-title>
          <v-tab class="pa-1 ma-2"  @click="$emit('change', 1)" > For Approval Request </v-tab>
          <v-tab @click="$emit('change', 2)" > Department Head </v-tab>
          <v-tab v-if="hasTab('consultant')" @click="$emit('change', 3)" > Consultant </v-tab>
          <v-tab v-if="hasTab('administrator')" @click="$emit('change', 4)" >  Administrator </v-tab>
        </v-tabs>
      </template>
    </v-app-bar>
  </v-card>
</template>
<script>
import PurchaseHelper from "@mmis/mixins/PurchaseHelper.vue"
export default{
  mixins:[PurchaseHelper],
  props:{
    setting:{
      type:Object,
      default: () => {}
    }
  },
  data(){
    return{

    }
  }
}
</script>